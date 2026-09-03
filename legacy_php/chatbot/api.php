<?php
session_start();
header('Content-Type: application/json');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'wrench_parts_db');

define('GEMINI_MODEL', 'gemini-3.5-flash');
define('GEMINI_FALLBACK_MODELS', ['gemini-flash-lite-latest', 'gemini-flash-latest', 'gemini-3.6-flash']);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['response' => 'Service temporarily unavailable. Please try again later.']);
    exit;
}
$conn->set_charset("utf8mb4");

if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {

$check = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'chatbot_enabled'");
$row = $check ? $check->fetch_assoc() : null;
if ($row && $row['setting_value'] === 'disabled') {
    echo json_encode(['response' => 'Chat support is currently unavailable. Please contact us directly for assistance.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = trim($_POST['message'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;
$sessionId = session_id();

if (empty($message) || mb_strlen($message) > 500) {
    echo json_encode(['response' => 'Please enter a valid message (1-500 characters).']);
    exit;
}

// FEEDBACK ENDPOINT
if ($action === 'feedback' && !empty($message)) {
    $feedback = (int)($_POST['feedback'] ?? 0);
    $starRating = isset($_POST['star_rating']) ? (int)$_POST['star_rating'] : null;
    if ($starRating !== null && ($starRating < 1 || $starRating > 5)) $starRating = null;
    $responseGiven = $_POST['response'] ?? '';
    saveFeedback($conn, $sessionId, $user_id, $message, $responseGiven, $feedback, $starRating);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'chat' && !empty($message)) {
    // INTENT DETECTION
    $intent = detectIntent($message);
    logIntent($conn, $sessionId, $message, $intent);

    // EMERGENCY CHECK
    if ($intent['intent'] === 'emergency') {
        $response = handleEmergency($message, $conn, $sessionId, $user_id);
        saveConversationMessage($conn, $sessionId, $user_id, 'user', $message);
        saveConversationMessage($conn, $sessionId, $user_id, 'assistant', strip_tags($response));
        echo json_encode(['response' => $response, 'intent' => $intent['intent'], 'confidence' => $intent['confidence']]);
        exit;
    }

    // MULTI-STEP DIAGNOSIS FLOW (for diagnosis intent)
    $vehicleState = loadChatState($conn, $sessionId);
    $history = loadConversationHistory($conn, $sessionId);
    $response = generateResponse($message, $user_id, $conn, $history);

    saveConversationMessage($conn, $sessionId, $user_id, 'user', $message);
    saveConversationMessage($conn, $sessionId, $user_id, 'assistant', strip_tags($response));

    if ($user_id) {
        $stmt = $conn->prepare("INSERT INTO chatbot_logs (user_id, question, response) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $message, $response);
        $stmt->execute();
        $stmt->close();
    }

    // TRACK SERVICE HISTORY
    trackServiceHistory($conn, $sessionId, $user_id, $vehicleState, $message, $response);

    // COST ESTIMATION
    $costEst = estimateRepairCost($message, $intent, $conn);

    // MAINTENANCE PREDICTION
    $maintPred = '';
    $km = $vehicleState['mileage'] ?? null;
    if ($km) $maintPred = predictMaintenance($km, $conn);

    // CONFIDENCE SCORE
    $confidence = extractConfidence($response, $intent);

    // BUILD RESPONSE WITH METADATA
    $meta = ['intent' => $intent['intent'], 'confidence' => $confidence];
    if ($costEst) $meta['cost_estimate'] = $costEst;
    if ($maintPred) $meta['maintenance'] = $maintPred;

    echo json_encode(array_merge(['response' => $response], $meta));
    exit;
}

echo json_encode(['response' => 'Invalid request.']);
exit;
}

// ============================================================
// GEMINI API
// ============================================================
function getGeminiApiKey($conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gemini_api_key'");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? trim($row['setting_value']) : '';
}

function getGeminiModel($conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gemini_model'");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($row && trim($row['setting_value'])) ? trim($row['setting_value']) : GEMINI_MODEL;
}

// Free tier allows ~20 requests/minute - throttle to 4s between AI calls
function geminiRateLimitOk($conn) {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'gemini_last_call'");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $last = $row ? (int)$row['setting_value'] : 0;
    $now = time();
    if ($now - $last < 4) return false;
    $stmt2 = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'gemini_last_call'");
    $stmt2->bind_param("s", $now);
    $stmt2->execute();
    $stmt2->close();
    return true;
}

function formatGeminiText($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/^#{2,3}\s*(.+)$/m', '<strong>$1</strong>', $text);
    $text = preg_replace('/^[-*]\s+/m', '&bull; ', $text);
    $text = preg_replace_callback('/\[IMG:([^\]]+)\]/', function ($m) {
        $src = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        if (!preg_match('#^(/|https?://)#', $src)) return '';
        return '<br><img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" style="max-width:130px;max-height:90px;border-radius:10px;margin:4px 0;object-fit:cover;" onerror="this.style.display=\'none\'">';
    }, $text);
    $text = preg_replace('/([>\s])(\/Wrench_n_Parts\/[A-Za-z0-9_\-.\/?=&%]+)/', '$1<a href="$2" style="color:#667eea;text-decoration:underline;">$2</a>', $text);
    return nl2br($text);
}

// ============================================================
// ADVANCED RAG: VECTOR EMBEDDING SEARCH (Semantic + Rerank)
// ============================================================
define('EMBED_MODEL', 'gemini-embedding-001');

function getEmbedding($text, $apiKey) {
    if (empty($apiKey) || empty($text)) return null;
    $text = mb_substr($text, 0, 1500);
    $model = 'gemini-embedding-001';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':embedContent';
    $payload = ['model' => 'models/' . $model, 'content' => ['parts' => [['text' => $text]]]];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $apiKey],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200 && $raw) {
        $j = json_decode($raw, true);
        $vals = $j['embedding']['values'] ?? null;
        if (is_array($vals) && count($vals) > 100) return $vals;
    }
    return null;
}

function cosineSimilarity($a, $b) {
    if (count($a) !== count($b)) return 0;
    $dot = $magA = $magB = 0;
    for ($i = 0, $n = count($a); $i < $n; $i++) {
        $dot += $a[$i] * $b[$i];
        $magA += $a[$i] * $a[$i];
        $magB += $b[$i] * $b[$i];
    }
    $denom = sqrt($magA) * sqrt($magB);
    return $denom > 0 ? $dot / $denom : 0;
}

function vectorSearch($message, $conn, $apiKey, $topN = 5) {
    $queryVec = getEmbedding($message, $apiKey);
    if (!$queryVec) return [];
    $all = $conn->query("SELECT source_type, source_id, label, embedding FROM kb_embeddings WHERE embedding IS NOT NULL AND embedding != '[]'");
    if (!$all || $all->num_rows === 0) return [];
    $results = [];
    while ($row = $all->fetch_assoc()) {
        $docVec = json_decode($row['embedding']);
        if (!is_array($docVec) || count($docVec) < 100) continue;
        $cos = cosineSimilarity($queryVec, $docVec);
        if ($cos > 0.35) {
            $results[] = ['type' => $row['source_type'], 'id' => $row['source_id'], 'label' => $row['label'], 'sim' => $cos];
        }
    }
    usort($results, function ($x, $y) { return $y['sim'] - $x['sim']; });
    return array_slice($results, 0, $topN);
}

function vectorToContext($matches, $conn) {
    $contexts = [];
    foreach ($matches as $m) {
        if ($m['type'] === 'problem') {
            $stmt = $conn->prepare("SELECT system, problem, symptoms, causes, solution FROM kb_problems WHERE id = ?");
            $stmt->bind_param("i", $m['id']);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($p) {
                $scorePct = round($m['sim'] * 100);
                $contexts[] = "[VECTOR MATCH {$scorePct}%: " . strtoupper($p['system']) . " - " . $p['problem'] . "]\nSymptoms: " . $p['symptoms'] .
                    "\nCauses: " . $p['causes'] . "\nSolution: " . $p['solution'];
            }
        } elseif ($m['type'] === 'article') {
            $stmt = $conn->prepare("SELECT title, category, content FROM kb_articles WHERE id = ?");
            $stmt->bind_param("i", $m['id']);
            $stmt->execute();
            $a = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($a) $contexts[] = "[VECTOR MATCH " . round($m['sim'] * 100) . "%: " . strtoupper(str_replace('_', ' ', $a['category'])) . "] " . $a['title'] . ":\n" . mb_substr($a['content'], 0, 500);
        } elseif ($m['type'] === 'dtc') {
            $stmt = $conn->prepare("SELECT code, system, description, causes, fixes FROM kb_dtc_codes WHERE id = ?");
            $stmt->bind_param("i", $m['id']);
            $stmt->execute();
            $d = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($d) $contexts[] = "[VECTOR MATCH " . round($m['sim'] * 100) . "%: " . strtoupper($d['system']) . "] " . $d['code'] . " " . $d['description'] . "\nCauses: " . $d['causes'] . "\nFixes: " . $d['fixes'];
        } elseif ($m['type'] === 'faq') {
            $stmt = $conn->prepare("SELECT question, answer FROM kb_faqs WHERE id = ?");
            $stmt->bind_param("i", $m['id']);
            $stmt->execute();
            $f = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($f) $contexts[] = "[VECTOR MATCH " . round($m['sim'] * 100) . "%: FAQ] " . $f['question'] . "\n" . $f['answer'];
        }
    }
    return $contexts;
}

// ============================================================
// MULTI-TURN STATE (vehicle profile tracking across messages)
// ============================================================
function loadChatState($conn, $sessionId) {
    $stmt = $conn->prepare("SELECT state FROM chatbot_state WHERE session_id = ?");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || !$row['state']) return [];
    return json_decode($row['state'], true) ?: [];
}

function saveChatState($conn, $sessionId, $state) {
    $json = json_encode($state, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("INSERT INTO chatbot_state (session_id, state) VALUES (?, ?) ON DUPLICATE KEY UPDATE state = ?");
    $stmt->bind_param("sss", $sessionId, $json, $json);
    $stmt->execute();
    $stmt->close();
}

function extractVehicleInfo($message, $state) {
    $m = mb_strtolower(trim($message));
    $brands = ['toyota','honda','suzuki','kia','hyundai','nissan','mitsubishi','ford','mazda','subaru','volkswagen','vw','bmw','mercedes','benz','audi','land rover','jac','changan','byd','mg','mg3','mg hs','mg zs','haval','ford'];
    if (empty($state['brand'])) {
        foreach ($brands as $b) {
            if (strpos($m, $b) !== false) { $state['brand'] = $b; break; }
        }
    }
    if (empty($state['year'])) {
        if (preg_match('/\b(19|20)(9|0|1|2)\d\b/', $m, $y)) { $state['year'] = $y[0]; }
    }
    if (empty($state['engine'])) {
        if (preg_match('/(\d{1,2}[.,]?\d?)\s*(litre|liter|l)\b/i', $message, $e)) {
            $state['engine'] = $e[0];
        } elseif (preg_match('/\b(\d{3,4})\s*cc\b/i', $message, $e)) {
            $state['engine'] = $e[1] . 'cc';
        } elseif (preg_match('/\b(1[0-3]\d0|1[4-6]\d0|2[0-4]\d0)\b/', $m, $e)) {
            $state['engine'] = $e[0];
        }
    }
    $fuels = ['diesel','petrol','cng','lpg','hybrid','ev','electric'];
    if (empty($state['fuel'])) {
        foreach ($fuels as $f) {
            if (strpos($m, $f) !== false) { $state['fuel'] = $f; break; }
        }
    }
    if (empty($state['mileage'])) {
        if (preg_match('/(\d[\d,\.]*)\s*(km|k\b)/i', $message, $k)) {
            $state['mileage'] = str_replace(',', '', $k[1]) . ' km';
        }
    }
    if (!isset($state['symptoms'])) $state['symptoms'] = [];
    $symptomWords = ['noise','awaz','grinding','ragar','knocking','vibration','vibrate','smoke','dhuwaan','dhuwa','overheat','garam','leak','pasina','squealing','whistling','hissing','misfire','jerks','jhatke','hesitation','stalling','stall','slow','dheema','hard','sakht','soft','naram','spongy'];
    foreach ($symptomWords as $sw) {
        if (strpos($m, $sw) !== false && !in_array($sw, $state['symptoms'])) {
            $state['symptoms'][] = $sw;
        }
    }
    return $state;
}

function formatVehicleProfile($state) {
    if (empty($state)) return '';
    $parts = [];
    if (!empty($state['brand'])) $parts[] = "Brand: " . ucfirst($state['brand']);
    if (!empty($state['year'])) $parts[] = "Year: " . $state['year'];
    if (!empty($state['engine'])) $parts[] = "Engine: " . $state['engine'];
    if (!empty($state['fuel'])) $parts[] = "Fuel: " . ucfirst($state['fuel']);
    if (!empty($state['mileage'])) $parts[] = "Mileage: " . $state['mileage'];
    if (!empty($state['symptoms'])) $parts[] = "Reported symptoms: " . implode(', ', $state['symptoms']);
    if (empty($parts)) return '';
    return "COLLECTED VEHICLE PROFILE (across conversation - DO NOT re-ask these):\n" . implode("\n", $parts) . "\n\n";
}

// ============================================================
// INTENT DETECTION CLASSIFIER
// ============================================================
function detectIntent($message) {
    $m = mb_strtolower(trim($message));
    $intents = [
        'obd_code' => '/\b[PCBU]\d{4}\b|obd|scan|code|dtc|error code/',
        'emergency' => '/emergency|accident|fire|smoke|stranded|stuck|help|sos|broke down|break down|roadside|tow|towing|crash/',
        'workshop' => '/workshop|garage|mechanic|repair shop|fix karwani|service karwani|book|appointment|near me|nearest|konsa|recommended|best workshop|kahan karwai|nearby|batao.*workshop|workshop.*batao/',
        'cost_estimate' => '/cost estimate|how much will|kitna lagega|repair cost|total cost|budget|afford|price estimate|karcha|kharcha|kitna/',
        'modification' => '/modif|upgrade|turbo|exhaust|intake|ecu|tune|performance|horsepower|hp|body kit|spoiler|coilover|lowering|lift|lift kit|cold air|downpipe|intercooler|blow off|sound|audio|stereo|rim|alloy|lagana hai/',
        'maintenance' => '/service|maintain|oil change|change karwane|replace|interval|kilomet|km|service due|when to|how often|schedule|timing belt|coolant|transmission|gear oil|spark plug|air filter|cabin filter|kab change/',
        'diagnosis' => '/problem|issue|fault|noise|awaz|vibration|smoke|leak|stall|overheat|misfire|grinding|squealing|knocking|tick|jerk|hesitat|rough|weak|slow|hard start|no start|start nai|dheema|garam|pasina|dhuwa|ragar|thanda|nai ho raha|kaam nai|kaam nahi|band|dead|fail|kharab|dubara/',
        'parts' => '/part|price|buy|stock|available|spare|product|battery|filter|oil|tyre|tire|plug|wiper|alternator|clutch|radiator|bulb|headlight|spark|piston|sensor|cable|bearing|shop|order|purchase/',
        'vehicle_info' => '/what.*car|which car|my car|gari|vehicle info|my vehicle|specs|specification|engine size|fuel type/',
        'greeting' => '/^(hi|hello|hey|salam|assalam|good\s*(morning|afternoon|evening)|greetings)/',
        'help' => '/^(help|what can you do|options|menu|features|kya kar sakte)/',
        'thanks' => '/^(thank|thanks|thx|appreciate|shukriya|meherbani)/',
        'goodbye' => '/^(bye|goodbye|see you|take care|later|alvida)/',
    ];
    foreach ($intents as $intent => $pattern) {
        if (preg_match($pattern, $m)) {
            $sub = null;
            if ($intent === 'diagnosis') {
                if (preg_match('/(engine|motor|oil|plug|coil|piston|cam|crank|turbo|vvt|misfire|knock|tick|backfire|surge|idle)/i', $m)) $sub = 'engine';
                elseif (preg_match('/(gear|transmission|clutch|shift|cvt|dsg|torque|reverse|first|fifth)/i', $m)) $sub = 'transmission';
                elseif (preg_match('/(brake|pad|rotor|caliper|abs|disc|drum|parking|squeal|grind)/i', $m)) $sub = 'brake';
                elseif (preg_match('/(suspension|shock|spring|strut|ball joint|bush|sway|wheel bearing|steering)/i', $m)) $sub = 'suspension';
                elseif (preg_match('/(battery|alternator|starter|light|fuse|wire|electrical|horn|window|lock|key|immobilizer)/i', $m)) $sub = 'electrical';
                elseif (preg_match('/(cool|radiator|thermostat|water pump|heater|overheat|garam|fan|coolant)/i', $m)) $sub = 'cooling';
                elseif (preg_match('/(ac|air condition|compressor|condenser|evaporator|cooling|blower|cabin)/i', $m)) $sub = 'ac';
                elseif (preg_match('/(fuel|petrol|diesel|injector|pump|filter|tank|cng|lpg)/i', $m)) $sub = 'fuel';
                elseif (preg_match('/(sensor|o2|maf|map|tps|crank|cam|knock|coolant temp|tpms|airbag)/i', $m)) $sub = 'sensor';
            }
            return ['intent' => $intent, 'sub' => $sub, 'confidence' => 0.85];
        }
    }
    return ['intent' => 'unknown', 'sub' => null, 'confidence' => 0.3];
}

// ============================================================
// REPAIR COST ESTIMATOR (uses live parts prices from DB)
// ============================================================
function estimateRepairCost($message, $intent, $conn) {
    $m = mb_strtolower($message);
    $costs = [
        'engine' => ['parts' => [8000, 45000], 'labor' => [3000, 8000]],
        'transmission' => ['parts' => [15000, 80000], 'labor' => [5000, 15000]],
        'brake' => ['parts' => [3000, 12000], 'labor' => [1000, 3000]],
        'suspension' => ['parts' => [4000, 20000], 'labor' => [2000, 5000]],
        'electrical' => ['parts' => [2000, 25000], 'labor' => [1000, 5000]],
        'cooling' => ['parts' => [3000, 15000], 'labor' => [2000, 5000]],
        'ac' => ['parts' => [5000, 30000], 'labor' => [2000, 6000]],
        'fuel' => ['parts' => [2000, 15000], 'labor' => [1000, 4000]],
    ];
    $sub = $intent['sub'] ?? null;
    if (!$sub || !isset($costs[$sub])) return null;
    $c = $costs[$sub];
    // Search DB for actual parts prices
    $tokens = array_slice(tokenize($message), 0, 3);
    $dbMin = PHP_INT_MAX; $dbMax = 0;
    if (!empty($tokens)) {
        $where = "p.status = 'available'";
        $params = [];
        foreach ($tokens as $t) {
            $where .= " AND (p.product_name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
            $like = '%' . $t . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        $stmt = $conn->prepare("SELECT p.price, p.discount_price FROM products p WHERE $where LIMIT 5");
        if (!empty($params)) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($rows as $r) {
            $price = $r['discount_price'] > 0 ? $r['discount_price'] : $r['price'];
            if ($price < $dbMin) $dbMin = $price;
            if ($price > $dbMax) $dbMax = $price;
        }
    }
    $partsMin = $dbMin < PHP_INT_MAX ? max($c['parts'][0], $dbMin) : $c['parts'][0];
    $partsMax = $dbMax > 0 ? max($c['parts'][1], $dbMax) : $c['parts'][1];
    return [
        'parts_min' => $partsMin, 'parts_max' => $partsMax,
        'labor_min' => $c['labor'][0], 'labor_max' => $c['labor'][1],
        'total_min' => $partsMin + $c['labor'][0], 'total_max' => $partsMax + $c['labor'][1]
    ];
}

// ============================================================
// MAINTENANCE PREDICTION (mileage-based service reminders)
// ============================================================
function predictMaintenance($mileage, $conn) {
    if (!$mileage || !is_numeric(str_replace([',', ' '], '', $mileage))) return '';
    $km = (int)str_replace([',', ' '], '', $mileage);
    $rules = [
        ['service' => 'Engine Oil & Filter Change', 'interval' => 10000, 'cost' => 'Rs.3,000-5,000'],
        ['service' => 'Air Filter Replacement', 'interval' => 20000, 'cost' => 'Rs.1,500-3,000'],
        ['service' => 'Cabin (AC) Filter', 'interval' => 20000, 'cost' => 'Rs.1,000-2,000'],
        ['service' => 'Brake Pad Inspection', 'interval' => 30000, 'cost' => 'Rs.3,000-8,000'],
        ['service' => 'Spark Plug Replacement', 'interval' => 40000, 'cost' => 'Rs.2,000-4,000'],
        ['service' => 'Coolant Flush', 'interval' => 40000, 'cost' => 'Rs.2,000-4,000'],
        ['service' => 'Transmission Fluid', 'interval' => 60000, 'cost' => 'Rs.4,000-8,000'],
        ['service' => 'Timing Belt Replacement', 'interval' => 100000, 'cost' => 'Rs.8,000-15,000'],
    ];
    $due = [];
    foreach ($rules as $r) {
        $nextDue = ceil($km / $r['interval']) * $r['interval'];
        $remaining = $nextDue - $km;
        if ($remaining <= 5000) {
            $status = $remaining <= 0 ? 'OVERDUE' : "due in {$remaining} km";
            $due[] = "- {$r['service']} ({$status}, approx {$r['cost']})";
        }
    }
    if (empty($due)) return '';
    return "MAINTENANCE PREDICTION (based on {$km} km mileage):\n" . implode("\n", $due) . "\nSchedule service: /Wrench_n_Parts/workshop-finder.php";
}

// ============================================================
// EMERGENCY HANDLER — Premium Automotive Dashboard Style
// ============================================================
function handleEmergency($message, $conn, $sessionId, $userId) {
    $m = mb_strtolower($message);
    $type = 'general';
    if (preg_match('/(fire|aag|flame|dhua.*aag|smoke|dhuan)/i', $m)) $type = 'fire';
    elseif (preg_match('/(accident|crash|takkar|hit|hadsa)/i', $m)) $type = 'accident';
    elseif (preg_match('/(stranded|stuck|tow|towing|lift)/i', $m)) $type = 'tow_needed';
    elseif (preg_match('/(brake fail|brake.*fail|brake.*cut|brake.*nahi)/i', $m)) $type = 'brake_emergency';
    elseif (preg_match('/(overheat|garam|boil|steam|temperature.*high)/i', $m)) $type = 'overheating';
    elseif (preg_match('/(oil.*leak|fuel.*leak|petrol.*leak|diesel.*leak)/i', $m)) $type = 'fluid_leak';
    elseif (preg_match('/(battery.*dead|battery.*khatam|start.*nahi|self.*nahi)/i', $m)) $type = 'battery';
    elseif (preg_match('/(flat.*tyre|puncture|tyre.*fail|ban.*fail|tyre.*phat)/i', $m)) $type = 'flat_tyre';
    elseif (preg_match('/(light.*nahi|lights.*nahi|headlight.*nahi)/i', $m)) $type = 'lights';

    $stmt = $conn->prepare("INSERT INTO chatbot_emergency (session_id, user_id, message, emergency_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $sessionId, $userId, $message, $type);
    $stmt->execute();
    $stmt->close();

    // === EMERGENCY TYPE DATA ===
    $types = [
        'fire' => [
            'title' => 'FIRE / SMOKE EMERGENCY',
            'warning_title' => 'Your vehicle is on fire or producing smoke.',
            'warning_desc' => 'Immediate action is needed to prevent serious injury or total vehicle loss.',
            'danger_level' => 'EXTREME',
            'danger_color' => '#DC2626',
            'est_time' => '15-30 min',
            'difficulty' => 'Critical',
            'safety' => 'Evacuate',
            'steps' => [
                ['icon' => 'stop', 'title' => 'TURANT GAADI ROKEIN', 'desc' => 'Side par le jaein, engine BAND karein.', 'accent' => '#DC2626'],
                ['icon' => 'hood', 'title' => 'HOOD MAT KHOLEIN', 'desc' => 'Steam/dhuan bahut garam hota hai, 5 min wait karein.', 'accent' => '#DC2626'],
                ['icon' => 'evacuate', 'title' => 'GAADI SE DOOR JAEIN', 'desc' => 'Agar aag lagi hai to 50m door khade ho jaein.', 'accent' => '#DC2626'],
                ['icon' => 'extinguisher', 'title' => 'FIRE EXTINGUISHER USE KAREIN', 'desc' => 'Agar hai to engine bay mein spray karein.', 'accent' => '#F97316'],
                ['icon' => 'phone', 'title' => '115 YA 1122 CALL KAREIN', 'desc' => 'Fire brigade ya rescue team ko turant inform karein.', 'accent' => '#DC2626'],
                ['icon' => 'coolant', 'title' => 'COOLANT LEAK CHECK', 'desc' => 'Agar coolant kam hai to overheating se aag lagi hai.', 'accent' => '#F97316'],
                ['icon' => 'tow', 'title' => 'WORKSHOP TOW KARWAEIN', 'desc' => 'Gaadi start mat karein jab tak check na ho.', 'accent' => '#DC2626'],
            ],
            'dos' => ['Engine BAND karein', 'Hazard lights ON karein', '115/1122 call karein', 'Door jaein 50m tak'],
            'donts' => ['Hood mat kholein', 'Paani mat daalein aag par', 'Gaadi start mat karein', 'Wapas mat jaein'],
            'pro_tip' => 'Engine thanda hone ke baad hi radiator check karein aur zarurat par mechanic se rabta karein.',
        ],
        'brake_emergency' => [
            'title' => 'BRAKE FAILURE EMERGENCY',
            'warning_title' => 'Your vehicle brakes have failed or are not responding.',
            'warning_desc' => 'This is a life-threatening situation. Act immediately to slow down safely.',
            'danger_level' => 'EXTREME',
            'danger_color' => '#DC2626',
            'est_time' => '10-20 min',
            'difficulty' => 'Critical',
            'safety' => 'Hazardous',
            'steps' => [
                ['icon' => 'steering', 'title' => 'PANIC MAT KAREIN', 'desc' => 'Steering mazbooti se pakrein, calm rahein.', 'accent' => '#DC2626'],
                ['icon' => 'handbrake', 'title' => 'HANDBRAKE GRADUALLY', 'desc' => 'Handbrake aahista se press karein, ek dam mat khichein.', 'accent' => '#DC2626'],
                ['icon' => 'gear', 'title' => 'GEAR DOWN KAREIN', 'desc' => 'Engine braking se gaadi slow hogi automatically.', 'accent' => '#F97316'],
                ['icon' => 'hazard', 'title' => 'HAZARD LIGHTS ON', 'desc' => 'Peeche waali gaadiyon ko signal dein turant.', 'accent' => '#DC2626'],
                ['icon' => 'road', 'title' => 'SIDE PAR LE JAEIN', 'desc' => 'Aahista aahista slow karein, road side par.', 'accent' => '#F97316'],
                ['icon' => 'workshop', 'title' => 'TURANT WORKSHOP JAEIN', 'desc' => 'Brake repair Rs.2,000-15,000 depending on issue.', 'accent' => '#DC2626'],
                ['icon' => 'tow', 'title' => 'TOWING SERVICE BULAEIN', 'desc' => 'Agar bilkul brake nahi, gaadi mat chalaein.', 'accent' => '#DC2626'],
            ],
            'dos' => ['Handbrake gradually use karein', 'Gear down karein', 'Side par jaein', 'Towing bulaein'],
            'donts' => ['Panic mat karein', 'Ek dam brake mat dabaein', 'Tez speed par mat karein', 'Gaadi mat chalaein further'],
            'pro_tip' => 'Brake fluid level check karein. Agar kam hai to leak ho sakta hai. Turant workshop jaein.',
        ],
        'overheating' => [
            'title' => 'ENGINE OVERHEATING EMERGENCY',
            'warning_title' => 'Your vehicle is overheating.',
            'warning_desc' => 'Immediate action is needed to prevent engine damage or breakdown.',
            'danger_level' => 'HIGH',
            'danger_color' => '#F97316',
            'est_time' => '20-45 min',
            'difficulty' => 'Moderate',
            'safety' => 'Caution',
            'steps' => [
                ['icon' => 'ac', 'title' => 'AC BAND + HEATER ON', 'desc' => 'Engine se heat door nikaalega automatically.', 'accent' => '#F97316'],
                ['icon' => 'stop', 'title' => 'GAADI SIDE PAR', 'desc' => 'Slow karein, side par le jaein, engine BAND karein.', 'accent' => '#DC2626'],
                ['icon' => 'hood', 'title' => 'HOOD MAT KHOLEIN', 'desc' => '15-20 min wait karein, steam bahut garam hoti hai.', 'accent' => '#DC2626'],
                ['icon' => 'coolant', 'title' => 'COOLANT LEVEL CHECK', 'desc' => 'Overflow tank mein paani daalein (jab thanda ho).', 'accent' => '#16A34A'],
                ['icon' => 'fan', 'title' => 'RADIATOR FAN CHECK', 'desc' => 'AC ON karein, fan spin hona chahiye.', 'accent' => '#F97316'],
                ['icon' => 'tow', 'title' => 'WORKSHOP TOW KARWAEIN', 'desc' => 'Engine damage ho sakta hai, professional check zaroori.', 'accent' => '#DC2626'],
            ],
            'dos' => ['AC band karein', 'Heater ON karein', 'Side par jaein', 'Coolant check karein'],
            'donts' => ['Hood mat kholein garam mein', 'Paani mat daalein garam radiator mein', 'Continue mat karein drive', 'AC ON mat rakhein'],
            'pro_tip' => 'Temperature gauge high ho to turant action lein. Engine repair bahut mehnga pad sakta hai.',
        ],
        'accident' => [
            'title' => 'ACCIDENT EMERGENCY',
            'warning_title' => 'You have been in a vehicle accident.',
            'warning_desc' => 'Check for injuries and secure the scene immediately.',
            'danger_level' => 'HIGH',
            'danger_color' => '#DC2626',
            'est_time' => '30-60 min',
            'difficulty' => 'Critical',
            'safety' => 'Hazardous',
            'steps' => [
                ['icon' => 'stop', 'title' => 'GAADI ROKEIN', 'desc' => 'Hazard lights ON, engine BAND karein.', 'accent' => '#DC2626'],
                ['icon' => 'medical', 'title' => 'INJURY CHECK', 'desc' => 'Koi injured hai? Agar hai to 112 (ambulance) call karein.', 'accent' => '#DC2626'],
                ['icon' => 'phone', 'title' => 'POLICE KO CALL', 'desc' => '15 ya nearest police station ko inform karein.', 'accent' => '#F97316'],
                ['icon' => 'camera', 'title' => 'PHOTOS LEIN', 'desc' => 'Damage ki photos lein insurance ke liye.', 'accent' => '#16A34A'],
                ['icon' => 'insurance', 'title' => 'INSURANCE INFORM', 'desc' => '24 hours ke andar report karein.', 'accent' => '#F97316'],
                ['icon' => 'tow', 'title' => 'TOWING SERVICE', 'desc' => 'Gaadi drive mat karein agar damage hai.', 'accent' => '#DC2626'],
            ],
            'dos' => ['112/15 call karein', 'Photos lein', 'Insurance inform karein', 'Calm rahein'],
            'donts' => ['Gaadi start mat karein', 'Argument mat karein', 'Scene mat chodein', 'Bina proof jaein'],
            'pro_tip' => 'Police report aur photos dono zaroori hain insurance claim ke liye.',
        ],
        'battery' => [
            'title' => 'BATTERY DEAD EMERGENCY',
            'warning_title' => 'Your vehicle battery is dead or not responding.',
            'warning_desc' => 'Follow these steps to jump start or get assistance.',
            'danger_level' => 'MODERATE',
            'danger_color' => '#F97316',
            'est_time' => '10-30 min',
            'difficulty' => 'Easy',
            'safety' => 'Safe',
            'steps' => [
                ['icon' => 'jumpstart', 'title' => 'JUMP START KAREIN', 'desc' => 'Doosri gaadi se jumper cables lagaein (+ to +, - to -).', 'accent' => '#16A34A'],
                ['icon' => 'cable', 'title' => 'JUMPER CABLES', 'desc' => 'Cables nahi? Kisi se maang lein ya mechanic bulaein.', 'accent' => '#F97316'],
                ['icon' => 'power', 'title' => 'LIGHTS/AC BAND', 'desc' => 'Battery aur drain hoga, sab power off karein.', 'accent' => '#F97316'],
                ['icon' => 'start', 'title' => '5-10 SEC CRANK', 'desc' => 'Agar ho jae to 20 min chalaein, battery charge hogi.', 'accent' => '#16A34A'],
                ['icon' => 'mechanic', 'title' => 'MECHANIC BULAEIN', 'desc' => 'Agar start na ho, towing Rs.500-2,000.', 'accent' => '#F97316'],
                ['icon' => 'battery', 'title' => 'BATTERY REPLACE', 'desc' => '3+ saal purana hai to Rs.4,000-12,000.', 'accent' => '#16A34A'],
            ],
            'dos' => ['Jump start try karein', 'Lights band karein', 'Cables check karein', 'Mechanic bulaein'],
            'donts' => ['Cables ulta mat lagaein', 'Lights ON mat rakhein', 'Bar bar crank mat karein', 'Paani mat daalein battery par'],
            'pro_tip' => 'Battery terminals saaf rakhein. Corrosion se starting problems hoti hain.',
        ],
        'flat_tyre' => [
            'title' => 'FLAT TYRE EMERGENCY',
            'warning_title' => 'Your vehicle has a flat or punctured tyre.',
            'warning_desc' => 'Follow these steps to safely change your tyre.',
            'danger_level' => 'MODERATE',
            'danger_color' => '#F97316',
            'est_time' => '15-30 min',
            'difficulty' => 'Easy',
            'safety' => 'Caution',
            'steps' => [
                ['icon' => 'slow', 'title' => 'SLOW DOWN', 'desc' => 'Immediately slow karein, side par le jaein.', 'accent' => '#F97316'],
                ['icon' => 'hazard', 'title' => 'HAZARD LIGHTS ON', 'desc' => 'Digger (triangle) road par 50m peeche rakhein.', 'accent' => '#F97316'],
                ['icon' => 'jack', 'title' => 'JACK & WRENCH', 'desc' => 'Jack car frame ke niche rakhein, wheel kholein.', 'accent' => '#16A34A'],
                ['icon' => 'tyre', 'title' => 'SPARE TYRE LAGAEIN', 'desc' => 'Air pressure check karein (30-35 PSI).', 'accent' => '#16A34A'],
                ['icon' => 'speed', 'title' => '80 KM/H LIMIT', 'desc' => 'Spare tyre temporary hai, zyada speed mat karein.', 'accent' => '#F97316'],
                ['icon' => 'workshop', 'title' => 'REPAIR SHOP JAEIN', 'desc' => 'Puncture Rs.200-500, naya tyre Rs.3,000-15,000.', 'accent' => '#16A34A'],
            ],
            'dos' => ['Side par jaein', 'Hazard ON karein', 'Jack properly rakhein', 'Spare tyre lagaein'],
            'donts' => ['Highway par mat rokein bina hazard', 'Jack uneven surface par mat rakhein', 'Spare tyre par zyada na chalaein', 'Ignore mat karein'],
            'pro_tip' => 'Tyre pressure monthly check karein. Proper pressure se mileage badhti hai.',
        ],
        'lights' => [
            'title' => 'LIGHTS NOT WORKING',
            'warning_title' => 'Your vehicle lights are not functioning.',
            'warning_desc' => 'This is dangerous, especially at night. Take immediate action.',
            'danger_level' => 'MODERATE',
            'danger_color' => '#F97316',
            'est_time' => '10-20 min',
            'difficulty' => 'Easy',
            'safety' => 'Caution',
            'steps' => [
                ['icon' => 'hazard', 'title' => 'HAZARD LIGHTS ON', 'desc' => 'Gaadi side par le jaein, hazard ON karein.', 'accent' => '#F97316'],
                ['icon' => 'fuse', 'title' => 'FUSE CHECK', 'desc' => 'Fuse box dashboard ke neeche ya engine bay mein.', 'accent' => '#16A34A'],
                ['icon' => 'bulb', 'title' => 'BULB CHECK', 'desc' => 'Ek bulb dead ho sakta hai, doosra lagaein.', 'accent' => '#16A34A'],
                ['icon' => 'battery', 'title' => 'BATTERY VOLTAGE', 'desc' => 'Multimeter se 12.6V hona chahiye.', 'accent' => '#F97316'],
                ['icon' => 'night', 'title' => 'NIGHT MAT CHALAEIN', 'desc' => 'Bina headlight ke night mein dangerous hai.', 'accent' => '#DC2626'],
                ['icon' => 'workshop', 'title' => 'WORKSHOP CHECK', 'desc' => 'Rs.200-2,000 depending on issue.', 'accent' => '#16A34A'],
            ],
            'dos' => ['Hazard ON karein', 'Fuse check karein', 'Bulb change karein', 'Workshop jaein'],
            'donts' => ['Night mein na chalaein', 'Ignore mat karein', 'High beam mat chalaein', 'Police se na bhaagein'],
            'pro_tip' => 'Headlight alignment check karwaein. Galat alignment se doosron ko problem hoti hai.',
        ],
        'fluid_leak' => [
            'title' => 'FLUID LEAK EMERGENCY',
            'warning_title' => 'Your vehicle is leaking fluid.',
            'warning_desc' => 'Identify the leak type and stop driving immediately.',
            'danger_level' => 'HIGH',
            'danger_color' => '#F97316',
            'est_time' => '15-30 min',
            'difficulty' => 'Moderate',
            'safety' => 'Caution',
            'steps' => [
                ['icon' => 'stop', 'title' => 'TURANT GAADI ROKEIN', 'desc' => 'Leak band na hone par engine BAND karein.', 'accent' => '#DC2626'],
                ['icon' => 'color', 'title' => 'COLOR CHECK', 'desc' => 'Green (coolant), brown (oil), pink (transmission).', 'accent' => '#F97316'],
                ['icon' => 'under', 'title' => 'UNDER CAR CHECK', 'desc' => 'Kahan se leak ho raha hai identify karein.', 'accent' => '#F97316'],
                ['icon' => 'smell', 'title' => 'SMOKE/SMELL', 'desc' => 'Agar hai to gaadi se door jaein, fuel leak ho sakta hai.', 'accent' => '#DC2626'],
                ['icon' => 'tow', 'title' => 'TOWING BULAEIN', 'desc' => 'Leak band hone tak gaadi mat chalaein.', 'accent' => '#DC2626'],
                ['icon' => 'workshop', 'title' => 'WORKSHOP CHECK', 'desc' => 'Rs.500-3,000 depending on leak type.', 'accent' => '#16A34A'],
            ],
            'dos' => ['Turant rokein', 'Color identify karein', 'Towing bulaein', 'Workshop jaein'],
            'donts' => ['Continue mat karein drive', 'Smoke ke paas mat jaein', 'Lighter mat jalaein', 'Ignore mat karein'],
            'pro_tip' => 'Fluid leak agar diesel/petrol hai to fire risk hai. Turant door jaein.',
        ],
        'tow_needed' => [
            'title' => 'TOWING NEEDED',
            'warning_title' => 'Your vehicle needs to be towed.',
            'warning_desc' => 'Follow these steps to get safely towed.',
            'danger_level' => 'LOW',
            'danger_color' => '#16A34A',
            'est_time' => '30-60 min',
            'difficulty' => 'Easy',
            'safety' => 'Safe',
            'steps' => [
                ['icon' => 'hazard', 'title' => 'HAZARD LIGHTS ON', 'desc' => 'Safe location par gaadi rokein.', 'accent' => '#F97316'],
                ['icon' => 'phone', 'title' => 'TOWING SERVICE', 'desc' => 'Rs.500-2,000 city mein, highway zyada.', 'accent' => '#16A34A'],
                ['icon' => 'address', 'title' => 'WORKSHOP ADDRESS', 'desc' => 'Nearest workshop ka address tow driver ko dein.', 'accent' => '#16A34A'],
                ['icon' => 'insurance', 'title' => 'INSURANCE CHECK', 'desc' => 'Koi policies towing cover karti hai.', 'accent' => '#F97316'],
                ['icon' => 'workshop', 'title' => 'REPAIR KARWAEIN', 'desc' => 'Nearest workshop mein jaake repair karein.', 'accent' => '#16A34A'],
            ],
            'dos' => ['Hazard ON karein', 'Safe location par rokein', 'Insurance check karein', 'Address dein tow driver ko'],
            'donts' => ['Highway ke beech mat rokein', 'Bina hazard ke mat rokein', 'Unknown tow par bharosa mat karein', 'Argue mat karein'],
            'pro_tip' => 'Towing service ka number pehle se save karein. Emergency mein kaam aata hai.',
        ],
        'general' => [
            'title' => 'EMERGENCY ASSISTANCE',
            'warning_title' => 'Your vehicle needs immediate attention.',
            'warning_desc' => 'Follow these general emergency steps.',
            'danger_level' => 'MODERATE',
            'danger_color' => '#F97316',
            'est_time' => '15-30 min',
            'difficulty' => 'Moderate',
            'safety' => 'Caution',
            'steps' => [
                ['icon' => 'stop', 'title' => 'SIDE PAR LE JAEIN', 'desc' => 'Gaadi safe location par rokein.', 'accent' => '#DC2626'],
                ['icon' => 'hazard', 'title' => 'HAZARD LIGHTS ON', 'desc' => 'Digger road par rakhein, hazard ON karein.', 'accent' => '#F97316'],
                ['icon' => 'calm', 'title' => 'STAY CALM', 'desc' => 'Situation assess karein, koi injured to nahi.', 'accent' => '#16A34A'],
                ['icon' => 'phone', 'title' => 'ASSISTANCE CALL', 'desc' => 'Roadside assistance ya mechanic bulaein.', 'accent' => '#F97316'],
            ],
            'dos' => ['Side par jaein', 'Calm rahein', 'Hazard ON karein', 'Mechanic bulaein'],
            'donts' => ['Panic mat karein', 'Ignore mat karein', 'Continue mat karein', 'Unknown logo se help mat lein'],
            'pro_tip' => 'Emergency numbers pehle se save karein. Har gaadi mein emergency kit honi chahiye.',
        ],
    ];

    $data = $types[$type] ?? $types['general'];

    // === SVG ICONS ===
    $icons = [
        'stop' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>',
        'hood' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M2 15h20"/><path d="M4 15V9a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6"/><path d="M12 7V3"/></svg>',
        'evacuate' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="11" x2="23" y2="11"/></svg>',
        'extinguisher' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M6 6h10a4 4 0 0 1 0 8H8a2 2 0 0 0 0 4h8"/><path d="M10 2v4"/><path d="M14 2v4"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>',
        'coolant' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M12 2v6"/><path d="M8 8l4 4 4-4"/><path d="M6 14h12"/><path d="M8 18h8"/></svg>',
        'tow' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5a1 1 0 0 1-1 1h-1"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
        'steering' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="1"/><path d="M12 2v4"/><path d="M12 18v4"/></svg>',
        'handbrake' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 2v10l7-3"/></svg>',
        'gear' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'hazard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'road' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M4 19L8 5"/><path d="M16 5l4 14"/><line x1="12" y1="6" x2="12" y2="8"/><line x1="12" y1="12" x2="12" y2="14"/><line x1="12" y1="18" x2="12" y2="20"/></svg>',
        'workshop' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'ac' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>',
        'fan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="3"/><path d="M12 1v2"/><path d="M12 21v2"/><path d="M1 12h2"/><path d="M21 12h2"/></svg>',
        'medical' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
        'camera' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
        'insurance' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
        'jumpstart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'cable' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>',
        'power' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>',
        'start' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>',
        'mechanic' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'battery' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><rect x="1" y="6" width="18" height="12" rx="2"/><line x1="23" y1="10" x2="23" y2="14"/><line x1="6" y1="10" x2="6" y2="14"/><line x1="10" y1="10" x2="10" y2="14"/><line x1="14" y1="10" x2="14" y2="14"/></svg>',
        'slow' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 8 14"/></svg>',
        'jack' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M5 20h14"/><path d="M12 4v12"/><path d="M8 16l4-4 4 4"/></svg>',
        'tyre' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>',
        'speed' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M12 2a10 10 0 1 0 10 10H12V2z"/><path d="M12 12l6-6"/></svg>',
        'fuse' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><rect x="6" y="2" width="12" height="20" rx="2"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="10" y1="6" x2="10" y2="8"/><line x1="14" y1="6" x2="14" y2="8"/></svg>',
        'bulb' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/></svg>',
        'night' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>',
        'color' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>',
        'under' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>',
        'smell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M8 16a4 4 0 1 1 8 0"/><path d="M12 8v4"/><path d="M12 16v.01"/></svg>',
        'address' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'calm' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
    ];

    // === BUILD PREMIUM HTML ===
    $html = '<div class="mech-emergency-panel">';

    // --- HEADER BANNER ---
    $html .= '<div class="mech-em-header">';
    $html .= '<div class="mech-em-header-bg"></div>';
    $html .= '<div class="mech-em-header-content">';
    $html .= '<div class="mech-em-siren">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="52" height="52"><circle cx="12" cy="14" r="8" fill="rgba(255,255,255,0.15)"/><path d="M12 2C10 2 8 4 8 6v2h8V6c0-2-2-4-4-4z" fill="#fff" opacity="0.9"/><rect x="10" y="8" width="4" height="3" rx="1" fill="#FACC15"/><circle cx="12" cy="14" r="4" fill="#FACC15" opacity="0.6"><animate attributeName="opacity" values="0.6;1;0.6" dur="1s" repeatCount="indefinite"/></circle></svg>';
    $html .= '</div>';
    $html .= '<div class="mech-em-header-text">';
    $html .= '<h1>IMMEDIATE ACTION REQUIRED</h1>';
    $html .= '<p>Follow these steps to keep yourself and your vehicle safe</p>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="mech-em-header-badges">';
    $html .= '<span class="mech-em-badge" style="background:rgba(255,255,255,0.2);color:#fff;">'. $data['danger_level'] .'</span>';
    $html .= '<span class="mech-em-badge" style="background:rgba(255,255,255,0.15);color:#fff;">&#128336; '. $data['est_time'] .'</span>';
    $html .= '<span class="mech-em-badge" style="background:rgba(255,255,255,0.15);color:#fff;">&#128736; '. $data['difficulty'] .'</span>';
    $html .= '</div>';
    $html .= '</div>';

    // --- WARNING CARD ---
    $html .= '<div class="mech-em-warning">';
    $html .= '<div class="mech-em-warning-icon">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="40" height="40"><path d="M12 2L2 22h20L12 2z" fill="#FEF3C7" stroke="#F59E0B" stroke-width="1.5"/><text x="12" y="18" text-anchor="middle" fill="#F59E0B" font-size="12" font-weight="bold">!</text></svg>';
    $html .= '</div>';
    $html .= '<div class="mech-em-warning-text">';
    $html .= '<h2>WARNING!</h2>';
    $html .= '<p><strong>'. $data['warning_title'] .'</strong></p>';
    $html .= '<p>'. $data['warning_desc'] .'</p>';
    $html .= '</div>';
    $html .= '<div class="mech-em-warning-watermark">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="80" height="80" opacity="0.08"><path d="M12 2L2 22h20L12 2z" fill="#F59E0B"/><text x="12" y="18" text-anchor="middle" fill="#F59E0B" font-size="14" font-weight="bold">!</text></svg>';
    $html .= '</div>';
    $html .= '</div>';

    // --- SAFETY LEVEL INDICATOR ---
    $html .= '<div class="mech-em-safety-bar">';
    $html .= '<div class="mech-em-safety-label">Safety Level</div>';
    $html .= '<div class="mech-em-safety-meter">';
    $safetyWidth = $type === 'general' || $type === 'tow_needed' ? '30%' : ($type === 'battery' || $type === 'flat_tyre' || $type === 'lights' ? '50%' : ($type === 'overheating' || $type === 'fluid_leak' ? '70%' : '95%'));
    $safetyColor = $type === 'general' || $type === 'tow_needed' ? '#16A34A' : ($type === 'battery' || $type === 'flat_tyre' || $type === 'lights' ? '#F97316' : '#DC2626');
    $html .= '<div class="mech-em-safety-fill" style="width:'.$safetyWidth.';background:'.$safetyColor.';"></div>';
    $html .= '</div>';
    $html .= '<div class="mech-em-safety-text" style="color:'.$safetyColor.';">'. $data['safety'] .'</div>';
    $html .= '</div>';

    // --- STEPS HEADER ---
    $html .= '<div class="mech-em-steps-header">';
    $html .= '<div class="mech-em-steps-icon">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" fill="#FEF3C7" stroke="#F59E0B" stroke-width="1.5"/><line x1="12" y1="9" x2="12" y2="13" stroke="#F59E0B" stroke-width="2"/><line x1="12" y1="17" x2="12.01" y2="17" stroke="#F59E0B" stroke-width="2"/></svg>';
    $html .= '</div>';
    $html .= '<h3>FOLLOW THESE STEPS IMMEDIATELY</h3>';
    $html .= '</div>';

    // --- STEP CARDS (Timeline) ---
    $html .= '<div class="mech-em-timeline">';
    foreach ($data['steps'] as $i => $step) {
        $iconSvg = $icons[$step['icon']] ?? $icons['stop'];
        $html .= '<div class="mech-em-step" style="animation-delay:'. ($i * 0.1) .'s;">';
        $html .= '<div class="mech-em-step-line"></div>';
        $html .= '<div class="mech-em-step-icon" style="background:'. $step['accent'] .'15;color:'. $step['accent'] .';">';
        $html .= $iconSvg;
        $html .= '</div>';
        $html .= '<div class="mech-em-step-num" style="background:'. $step['accent'] .';">'. ($i+1) .'</div>';
        $html .= '<div class="mech-em-step-content">';
        $html .= '<h4>'. $step['title'] .'</h4>';
        $html .= '<div class="mech-em-step-divider" style="background:'. $step['accent'] .';"></div>';
        $html .= '<p>'. $step['desc'] .'</p>';
        $html .= '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';

    // --- DO / DON'T SECTION ---
    $html .= '<div class="mech-em-dodont">';
    $html .= '<div class="mech-em-do">';
    $html .= '<div class="mech-em-do-header">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="22" height="22"><circle cx="12" cy="12" r="10" fill="#DCFCE7" stroke="#16A34A" stroke-width="1.5"/><path d="M8 12l3 3 5-6" stroke="#16A34A" stroke-width="2" fill="none"/></svg>';
    $html .= '<h4>THINGS TO DO</h4>';
    $html .= '</div>';
    $html .= '<ul>';
    foreach ($data['dos'] as $do) {
        $html .= '<li><span class="mech-em-do-check">&#10003;</span> '. $do .'</li>';
    }
    $html .= '</ul>';
    $html .= '</div>';
    $html .= '<div class="mech-em-dont">';
    $html .= '<div class="mech-em-dont-header">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="22" height="22"><circle cx="12" cy="12" r="10" fill="#FEE2E2" stroke="#DC2626" stroke-width="1.5"/><line x1="8" y1="8" x2="16" y2="16" stroke="#DC2626" stroke-width="2"/><line x1="16" y1="8" x2="8" y2="16" stroke="#DC2626" stroke-width="2"/></svg>';
    $html .= '<h4>THINGS NOT TO DO</h4>';
    $html .= '</div>';
    $html .= '<ul>';
    foreach ($data['donts'] as $dont) {
        $html .= '<li><span class="mech-em-dont-x">&#10007;</span> '. $dont .'</li>';
    }
    $html .= '</ul>';
    $html .= '</div>';
    $html .= '</div>';

    // --- PRO TIP ---
    $html .= '<div class="mech-em-pro-tip">';
    $html .= '<div class="mech-em-pro-tip-icon">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="28" height="28"><circle cx="12" cy="12" r="10" fill="#DCFCE7"/><path d="M12 6a4 4 0 0 0-4 4c0 2 1 3 2 4h4c1-1 2-2 2-4a4 4 0 0 0-4-4z" fill="#16A34A"/><rect x="10" y="16" width="4" height="2" rx="1" fill="#16A34A"/></svg>';
    $html .= '</div>';
    $html .= '<div class="mech-em-pro-tip-text">';
    $html .= '<h4>PRO TIP</h4>';
    $html .= '<p>'. $data['pro_tip'] .'</p>';
    $html .= '</div>';
    $html .= '</div>';

    // --- EMERGENCY CONTACTS ---
    $html .= '<div class="mech-em-contacts">';
    $html .= '<h4>EMERGENCY CONTACTS</h4>';
    $html .= '<div class="mech-em-contacts-grid">';
    $contacts = [
        ['name' => 'Edhi Ambulance', 'num' => '115', 'color' => '#DC2626'],
        ['name' => 'Rescue', 'num' => '1122', 'color' => '#F97316'],
        ['name' => 'Motorway Police', 'num' => '130', 'color' => '#2563EB'],
        ['name' => 'Police', 'num' => '15', 'color' => '#7C3AED'],
    ];
    foreach ($contacts as $c) {
        $html .= '<a href="tel:'. $c['num'] .'" class="mech-em-contact">';
        $html .= '<div class="mech-em-contact-name">'. $c['name'] .'</div>';
        $html .= '<div class="mech-em-contact-num" style="color:'. $c['color'] .';">'. $c['num'] .'</div>';
        $html .= '</a>';
    }
    $html .= '</div>';
    $html .= '</div>';

    // --- ACTION BUTTONS ---
    $html .= '<div class="mech-em-actions">';
    $html .= '<a href="tel:1122" class="mech-em-btn mech-em-btn-danger">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke="#fff" stroke-width="2"/></svg>';
    $html .= 'Call Mechanic</a>';
    $html .= '<a href="/Wrench_n_Parts/workshop-finder.php" class="mech-em-btn mech-em-btn-primary">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" stroke="#fff" stroke-width="2"/></svg>';
    $html .= 'Book Workshop</a>';
    $html .= '<a href="/Wrench_n_Parts/chatbot/" class="mech-em-btn mech-em-btn-outline">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="2"/></svg>';
    $html .= 'AI Diagnosis</a>';
    $html .= '</div>';

    // --- FOOTER SUCCESS CARD ---
    $html .= '<div class="mech-em-footer">';
    $html .= '<div class="mech-em-footer-icon">';
    $html .= '<svg viewBox="0 0 24 24" fill="none" width="24" height="24"><circle cx="12" cy="12" r="10" fill="#DCFCE7" stroke="#16A34A" stroke-width="1.5"/><path d="M8 12l3 3 5-6" stroke="#16A34A" stroke-width="2.5" fill="none"/></svg>';
    $html .= '</div>';
    $html .= '<p>If the problem continues, <strong>contact a certified mechanic immediately</strong>. Do not attempt repairs beyond your skill level.</p>';
    $html .= '</div>';

    // --- EMERGENCY NUMBERS FOOTER ---
    $html .= renderEmergencyNumbersFooter();

    $html .= '</div>';
    return $html;
}

// ============================================================
// USER FEEDBACK (thumbs up/down)
// ============================================================
function saveFeedback($conn, $sessionId, $userId, $messageSent, $responseGiven, $feedback, $starRating = null) {
    $stmt = $conn->prepare("INSERT INTO chatbot_feedback (session_id, user_id, message_sent, response_given, feedback, star_rating) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssi", $sessionId, $userId, $messageSent, $responseGiven, $feedback, $starRating);
    $stmt->execute();
    $stmt->close();
    return true;
}

function logIntent($conn, $sessionId, $message, $intent) {
    $detected = $intent['intent'];
    $sub = $intent['sub'] ?? null;
    $conf = $intent['confidence'] ?? 0;
    $stmt = $conn->prepare("INSERT INTO chatbot_intents (session_id, message, detected_intent, confidence, sub_intent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssds", $sessionId, $message, $detected, $conf, $sub);
    $stmt->execute();
    $stmt->close();
}

// ============================================================
// VEHICLE SERVICE HISTORY TRACKING
// ============================================================
function trackServiceHistory($conn, $sessionId, $userId, $state, $message, $response) {
    $m = mb_strtolower($message);
    if (!preg_match('/(service|repair|change|replace|fix|done|completed|kalwaya|karwai|lagwaya)/i', $m)) return;
    $brand = $state['brand'] ?? '';
    $model = $state['model'] ?? '';
    $year = $state['year'] ?? '';
    $engine = $state['engine'] ?? '';
    $fuel = $state['fuel'] ?? '';
    $mileage = $state['mileage'] ?? '';
    $stmt = $conn->prepare("INSERT INTO vehicle_service_history (user_id, session_id, vehicle_brand, vehicle_model, vehicle_year, engine_size, fuel_type, mileage, service_type, problem_description, diagnosis) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $svcType = 'general_service';
    if (preg_match('/(oil|filter)/i', $m)) $svcType = 'oil_change';
    elseif (preg_match('/(brake|pad)/i', $m)) $svcType = 'brake_service';
    elseif (preg_match('/(clutch|gear|transmission)/i', $m)) $svcType = 'transmission';
    elseif (preg_match('/(ac|air condition)/i', $m)) $svcType = 'ac_service';
    elseif (preg_match('/(battery|alternator)/i', $m)) $svcType = 'electrical';
    $stmt->bind_param("issssssssss", $userId, $sessionId, $brand, $model, $year, $engine, $fuel, $mileage, $svcType, $message, $response);
    $stmt->execute();
    $stmt->close();
}

// ============================================================
// CONFIDENCE SCORE EXTRACTOR (from AI response)
// ============================================================
function extractConfidence($response, $intent) {
    $base = $intent['confidence'] ?? 0.5;
    if ($intent['intent'] === 'obd_code') return min($base + 0.2, 0.98);
    if ($intent['intent'] === 'diagnosis' && !empty($intent['sub'])) return min($base + 0.1, 0.92);
    if ($intent['intent'] === 'maintenance') return min($base + 0.15, 0.95);
    if ($intent['intent'] === 'parts' || $intent['intent'] === 'workshop') return 0.90;
    return $base;
}

// ============================================================
// MAIN RESPONSE GENERATOR (with intent detection + routing)
// ============================================================
function getGeminiReply($message, $conn, $history, $user_id) {
    $apiKey = getGeminiApiKey($conn);
    if (empty($apiKey)) return null;
    if (!geminiRateLimitOk($conn)) return null;

    // MULTI-TURN STATE: load, extract, save
    $sessionId = session_id();
    $state = loadChatState($conn, $sessionId);
    $state = extractVehicleInfo($message, $state);
    saveChatState($conn, $sessionId, $state);
    $vehicleProfile = formatVehicleProfile($state);

    // RAG: pull relevant knowledge base context (vector + keyword)
    $kbContext = retrieveKnowledgeContext($message, $conn);
    // TOOLS: pull live database data (products, orders, bookings)
    $toolContext = executeTools($message, $user_id, $conn);
    // SAFETY rules server-side check
    $safety = safetyCheck($message);

    $systemPrompt = "You are MechBot, an EXPERIENCED AUTOMOBILE MECHANIC with 20+ years of workshop experience, working as the AI assistant for Wrench n Parts (auto parts store, Pakistan).\n\n" .
        "EXPERTISE AREAS (answer professionally and confidently in ALL of these):\n" .
        "Vehicle diagnosis, Engine problems, Transmission, Suspension, Brakes, Battery, Tyres, Oil & lubrication recommendations, Coolant, Air filters, Spark plugs, Turbo, ECU, Electrical issues, AC problems, Fuel system, Performance upgrades, Body kits, Vehicle accessories, Service intervals, Maintenance schedules, Repair cost estimates, Parts recommendation, Workshop advice, Safety advice, DTC/OBD-II code explanations, Modification suggestions, Repair procedures.\n\n" .
        "DIAGNOSIS RULES (for any car fault):\n" .
        "FOLLOW THIS FLOW: Understand the problem -> match symptoms -> search the EXPERT DIAGNOSIS data provided below -> rank matching problems by probability -> give a clear diagnosis report -> then recommend parts and/or a workshop booking.\n" .
        "1. DIAGNOSIS - clearly state the likely issue with the vehicle (use the EXPERT DIAGNOSIS entries when they match - they are real mechanic solutions).\n" .
        "2. CAUSE - explain the common causes/reasons behind the problem.\n" .
        "3. FIX - give simple, safe troubleshooting/checking steps the customer can do at home.\n" .
        "4. COST - give approximate repair cost ranges in PKR (Rs.) when reasonable. Never invent exact prices.\n" .
        "5. SAFETY - if the problem is serious or dangerous (overheating, brake failure, grinding brakes, engine smoke, airbag/fuel work), warn clearly and recommend a workshop visit immediately.\n" .
        "6. CONFIDENCE - start your response with a confidence indicator like: 'Diagnosis confidence: High/Medium/Low' based on how certain you are from the available data.\n\n" .
        "VEHICLE INFORMATION COLLECTION (IMPORTANT):\n" .
        "Before diagnosing, check if the customer provided enough vehicle information. If important pieces are missing, ask for them - but ask ONLY for the missing pieces, one question at a time (maximum 2-3 items per reply):\n" .
        "- Vehicle Brand\n- Model\n- Year\n- Engine size\n- Fuel type (petrol / diesel / CNG)\n- Mileage (km)\n- Problem description\n- Warning lights on the dashboard\n- Noise type (grinding, squeaking, knocking, ticking)\n- Smoke color (blue, white, black)\n- Recent repairs\n" .
        "If the customer already provided good detail, diagnose immediately - do not force questions.\n\n" .
        "BEHAVIOUR RULES (NEVER BREAK):\n" .
        "- NEVER hallucinate. NEVER invent repair procedures, symptoms, part numbers, or facts.\n" .
        "- If you are not certain about the exact cause, say: \"The exact issue requires physical inspection by a mechanic\" and recommend booking a workshop.\n" .
        "- NEVER recommend dangerous modifications (airbag bypass, brake line removal, fuel system tampering, disabling safety systems, etc.).\n" .
        "- For parts requests: use ONLY the LIVE product data provided below. Show for each product: image, price, stock, shop name and link. If the exact part is not in the data, recommend similar products from the same category or brand.\n" .
        "- For workshop requests: use ONLY the LIVE workshop data provided below. Show: workshop name, rating, contact, location and opening hours.\n" .
        "- Respond in the SAME language the customer writes in (English, Urdu, or Roman Urdu).\n" .
        "- Keep answers under 200 words. Use short bullet points and blank lines between sections.\n" .
        "- When recommending a workshop visit, add this line: \"Book a workshop here: /Wrench_n_Parts/workshop-finder.php\"\n" .
        "- Be friendly, professional, and safety-first.\n\n";

    if (!empty($safety)) {
        $systemPrompt .= "SAFETY RULE FOR THIS CONVERSATION: " . $safety . "\n\n";
    }

    // Inject collected vehicle profile so AI doesn't re-ask
    if (!empty($vehicleProfile)) {
        $systemPrompt .= $vehicleProfile;
    }

    $userContext = '';
    if (!empty($kbContext)) {
        $userContext .= $kbContext . "\n\n";
    }
    if (!empty($toolContext)) {
        $userContext .= $toolContext . "\n\n";
    }
    if (!empty($userContext)) {
        $systemPrompt .= "CONTEXT DATA PROVIDED TO YOU:\n" . $userContext .
            "Instructions: Use this data when it is relevant to the question. If the user asks for live information (products, orders, bookings), ALWAYS use the data above instead of guessing. If the data shows the user has no orders, say so clearly.\n\n";
    }

    $configured = getGeminiModel($conn);
    $models = array_unique(array_merge([$configured], GEMINI_FALLBACK_MODELS));

    $lastError = '';
    foreach ($models as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';

        $contents = [];
        if (is_array($history)) {
            $contents = array_slice($history, -8);
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        $payload = [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
            'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 1200, 'thinkingConfig' => ['thinkingBudget' => 0]]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-goog-api-key: ' . $apiKey],
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($code !== 200 || empty($raw)) {
            $lastError = 'HTTP:' . $code . ' ' . $err;
            continue;
        }
        $json = json_decode($raw, true);
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (empty($text)) {
            $lastError = 'empty response';
            continue;
        }
        return formatGeminiText($text);
    }

    @file_put_contents(__DIR__ . '/api_error.log', date('Y-m-d H:i:s') . " | ALL MODELS FAILED | LAST:" . $lastError . "\n", FILE_APPEND);
    return null;
}

// ============================================================
// CONVERSATION MEMORY
// ============================================================
function loadConversationHistory($conn, $sessionId) {
    $stmt = $conn->prepare("SELECT role, message FROM chatbot_conversations WHERE session_id = ? ORDER BY id DESC LIMIT 8");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $rows = array_reverse($rows);
    $history = [];
    foreach ($rows as $r) {
        $history[] = [
            'role' => $r['role'] === 'user' ? 'user' : 'model',
            'parts' => [['text' => mb_substr($r['message'], 0, 800)]]
        ];
    }
    return $history;
}

function saveConversationMessage($conn, $sessionId, $user_id, $role, $message) {
    $stmt = $conn->prepare("INSERT INTO chatbot_conversations (user_id, session_id, role, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $sessionId, $role, $message);
    $stmt->execute();
    $stmt->close();
}

// ============================================================
// RAG - KNOWLEDGE BASE RETRIEVAL
// ============================================================
function tokenize($text) {
    $text = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $text));
    $stopwords = ['the','a','an','and','or','is','are','of','to','in','for','my','me','i','car','gari','vehicle','hai','raha','rahi','ho','ka','ki','ke','se','ma','mein','that','this','with','what','how','why','does','do','s','t','ye'];
    $words = array_filter(explode(' ', $text), function($w) use ($stopwords) {
        return strlen($w) > 2 && !in_array($w, $stopwords);
    });
    return array_values(array_unique($words));
}

function retrieveKnowledgeContext($message, $conn) {
    $apiKey = getGeminiApiKey($conn);
    $contexts = [];
    $seenKeys = [];

    // 1. DTC / OBD-II codes (e.g. P0301, C0035)
    preg_match_all('/\b([PCBU][0-9]{4})\b/i', $message, $m);
    if (!empty($m[1])) {
        foreach (array_slice($m[1], 0, 3) as $code) {
            $stmt = $conn->prepare("SELECT code, system, description, causes, fixes FROM kb_dtc_codes WHERE code = ?");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $contexts[] = "[DTC CODE " . $row['code'] . " | " . $row['system'] . "] " . $row['description'] .
                    "\nCauses: " . $row['causes'] . "\nFixes: " . $row['fixes'];
                $seenKeys['dtc:' . $row['code']] = true;
            }
            $stmt->close();
        }
    }

    // 2. Vector semantic search (embeddings: 530 problems + articles + DTC + FAQ)
    if ($apiKey) {
        $vecMatches = vectorSearch($message, $conn, $apiKey, 5);
        $vecCtx = vectorToContext($vecMatches, $conn);
        foreach ($vecCtx as $vc) {
            $contexts[] = $vc;
        }
    }

    // 3. Keyword search (hybrid: keyword-only entries fill gaps)
    $tokens = tokenize($message);
    if (empty($tokens) && empty($contexts)) return '';
    if (!empty($tokens)) {
        // Articles
        $arts = $conn->query("SELECT id, title, category, keywords, content FROM kb_articles");
        $scores = [];
        while ($a = $arts->fetch_assoc()) {
            if (isset($seenKeys['article:' . $a['id']])) continue;
            $searchable = strtolower($a['title'] . ' ' . $a['keywords'] . ' ' . mb_substr($a['content'], 0, 3000));
            $score = 0;
            foreach ($tokens as $t) {
                $needle = preg_quote($t, '/');
                if (preg_match('/\b' . $needle . '/', $searchable)) $score += 3;
                elseif (strpos($searchable, $t) !== false) $score += 1;
            }
            if ($score > 0) $scores[] = ['score' => $score, 'a' => $a];
        }
        usort($scores, function ($x, $y) { return $y['score'] - $x['score']; });
        foreach (array_slice($scores, 0, 2) as $s) {
            $a = $s['a'];
            $contexts[] = "[KB GUIDE: " . strtoupper(str_replace('_', ' ', $a['category'])) . "] " . $a['title'] .
                ":\n" . mb_substr($a['content'], 0, 500);
            $seenKeys['article:' . $a['id']] = true;
        }

        // Problems (keyword-only, vector already covered most)
        $probs = $conn->query("SELECT id, system, problem, symptoms, causes, solution FROM kb_problems");
        $pScores = [];
        while ($p = $probs->fetch_assoc()) {
            if (isset($seenKeys['problem:' . $p['id']])) continue;
            $probText = strtolower($p['problem'] . ' ' . $p['symptoms'] . ' ' . $p['causes']);
            $score = 0;
            foreach ($tokens as $t) {
                $needle = preg_quote($t, '/');
                if (preg_match('/\b' . $needle . '/', $probText)) $score += 2;
                elseif (strpos($probText, $t) !== false) $score += 1;
            }
            if (strpos(strtolower($p['problem']), implode(' ', $tokens)) !== false) $score += 4;
            if ($score > 0) $pScores[] = ['score' => $score, 'p' => $p];
        }
        usort($pScores, function ($x, $y) { return $y['score'] - $x['score']; });
        foreach (array_slice($pScores, 0, 2) as $s) {
            $pr = $s['p'];
            $contexts[] = "[EXPERT DIAGNOSIS: " . strtoupper($pr['system']) . " - " . $pr['problem'] . "]\nSymptoms: " . $pr['symptoms'] .
                "\nPossible Causes: " . $pr['causes'] . "\nSolution: " . $pr['solution'];
            $seenKeys['problem:' . $pr['id']] = true;
        }

        // FAQs
        $faqs = $conn->query("SELECT id, question, answer FROM kb_faqs");
        $fScores = [];
        while ($f = $faqs->fetch_assoc()) {
            if (isset($seenKeys['faq:' . $f['id']])) continue;
            $score = 0;
            foreach ($tokens as $t) {
                if (strpos(strtolower($f['question']), $t) !== false) $score += 2;
            }
            if ($score > 0) $fScores[] = ['score' => $score, 'f' => $f];
        }
        usort($fScores, function ($x, $y) { return $y['score'] - $x['score']; });
        foreach (array_slice($fScores, 0, 1) as $s) {
            $f = $s['f'];
            $contexts[] = "[FAQ] Q: " . $f['question'] . "\nA: " . mb_substr($f['answer'], 0, 300);
        }
    }

    if (empty($contexts)) return '';
    return "RELEVANT KNOWLEDGE BASE (official manuals/FAQ - use when relevant):\n" . implode("\n\n", array_slice($contexts, 0, 8));
}

// ============================================================
// TOOLS - LIVE DATABASE FUNCTION CALLING
// ============================================================
function executeTools($message, $user_id, $conn) {
    $q = strtolower($message);
    $results = [];

    // Tool: search products (image, price, stock, shop, link)
    if (preg_match('/(product|part|price|cost|how much|buy|stock|available|brake pad|battery|filter|oil|tire|tyre|plug|wiper|alternator|clutch|radiator|bulb|headlight|spark|piston|sensor|cable|bearing)/i', $q)) {
        $tokens = array_slice(tokenize($message), 0, 3);
        $rows = [];
        if (!empty($tokens)) {
            $where = "p.status = 'available'";
            $params = [];
            foreach ($tokens as $t) {
                $where .= " AND (p.product_name LIKE ? OR p.brand LIKE ? OR p.description LIKE ?)";
                $like = '%' . $t . '%';
                $params[] = $like; $params[] = $like; $params[] = $like;
            }
            $stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.brand, p.price, p.discount_price, p.stock, p.product_image, p.category_id, s.shop_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE $where LIMIT 5");
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
        if (empty($rows)) {
            $rows = $conn->query("SELECT p.product_id, p.product_name, p.brand, p.price, p.discount_price, p.stock, p.product_image, p.category_id, s.shop_name FROM products p LEFT JOIN shops s ON p.shop_id = s.shop_id WHERE p.status = 'available' ORDER BY RAND() LIMIT 3")->fetch_all(MYSQLI_ASSOC);
        }
        if (!empty($rows)) {
            $lines = array_map(function ($p) {
                $price = $p['discount_price'] && $p['discount_price'] > 0 ? $p['discount_price'] : $p['price'];
                $img = !empty($p['product_image']) ? "[IMG:/Wrench_n_Parts/uploads/" . $p['product_image'] . "]" : '';
                $shop = $p['shop_name'] ? $p['shop_name'] : 'Wrench n Parts';
                return "- " . $p['product_name'] . " (" . $p['brand'] . ") | Price: Rs." . number_format($price, 0) . " | Stock: " . ($p['stock'] > 0 ? 'In Stock' : 'Out of Stock') . " | Shop: " . $shop . " " . $img . " | Link: /Wrench_n_Parts/product-detail.php?id=" . $p['product_id'];
            }, $rows);
            $results[] = "LIVE PRODUCTS FROM DATABASE:\n" . implode("\n", $lines);
            $results[] = "RULE: If the user asked for a specific part and it is NOT listed above, say it is currently not available and recommend the closest similar products from this list (same category or brand).";
        }
    }

    // Tool: workshops (name, rating, contact, location, opening hours)
    if (preg_match('/(workshop|garage|mechanic|repair shop|service karwani|fix karwani|workshop.*recommend|konsa workshop|nearest|near me)/i', $q)) {
        $tokens = array_slice(tokenize($message), 0, 2);
        $where = "w.status IN ('active','approved')";
        $params = [];
        if (!empty($tokens)) {
            foreach ($tokens as $t) {
                if (in_array($t, ['workshop', 'garage', 'mechanic', 'repair', 'service', 'nearest', 'near'])) continue;
                $where .= " AND (w.workshop_name LIKE ? OR w.location LIKE ? OR w.services LIKE ?)";
                $like = '%' . $t . '%';
                $params[] = $like; $params[] = $like; $params[] = $like;
            }
        }
        $sql = "SELECT w.workshop_id, w.workshop_name, w.location, w.contact, w.rating, w.total_reviews, w.services, w.opening_time, w.closing_time FROM workshops w WHERE $where ORDER BY w.rating DESC LIMIT 4";
        $stmt = $conn->prepare($sql);
        if (!empty($params)) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        if (!empty($rows)) {
            $lines = array_map(function ($w) {
                $hours = date('H:i', strtotime($w['opening_time'])) . ' - ' . date('H:i', strtotime($w['closing_time']));
                return "- " . $w['workshop_name'] . " | Rating: " . $w['rating'] . "/5 (" . $w['total_reviews'] . " reviews) | Contact: " . ($w['contact'] ?: 'n/a') . " | Location: " . $w['location'] . " | Hours: " . $hours . " | Link: /Wrench_n_Parts/workshop-finder.php";
            }, $rows);
            $results[] = "LIVE WORKSHOPS FROM DATABASE:\n" . implode("\n", $lines);
            $results[] = "RULE: Recommend these workshops showing name, rating, contact, location and opening hours. Tell the user to book via /Wrench_n_Parts/workshop-finder.php";
        }
    }

    // Tool: customer orders (needs login)
    if ($user_id && preg_match('/(order|delivery|shipped|track|purchase)/i', $q)) {
        $stmt = $conn->prepare("SELECT order_id, order_status, total_amount, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 3");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        if (!empty($rows)) {
            $lines = array_map(function ($o) {
                return "- Order #" . $o['order_id'] . ": " . strtoupper($o['order_status']) . " - Rs." . number_format($o['total_amount'], 0) . " (" . $o['created_at'] . ")";
            }, $rows);
            $results[] = "CUSTOMER ORDERS FROM DATABASE:\n" . implode("\n", $lines);
        } else {
            $results[] = "CUSTOMER ORDERS FROM DATABASE: This customer has no orders yet.";
        }
    }

    // Tool: workshop bookings/appointments (needs login)
    if ($user_id && preg_match('/(appointment|booking|booked|schedule|workshop.*(status|book)|service.*book)/i', $q)) {
        $stmt = $conn->prepare("SELECT a.appointment_id, a.service_type, a.appointment_date, a.appointment_time, a.status, w.workshop_name FROM appointments a LEFT JOIN workshops w ON a.workshop_id = w.workshop_id WHERE a.customer_id = ? ORDER BY a.appointment_date DESC LIMIT 3");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        if (!empty($rows)) {
            $lines = array_map(function ($b) {
                return "- " . $b['workshop_name'] . ": " . $b['service_type'] . " on " . $b['appointment_date'] . " " . $b['appointment_time'] . " [" . strtoupper($b['status']) . "]";
            }, $rows);
            $results[] = "WORKSHOP BOOKINGS FROM DATABASE:\n" . implode("\n", $lines);
        } else {
            $results[] = "WORKSHOP BOOKINGS FROM DATABASE: This customer has no workshop bookings yet.";
        }
    }

    if (empty($results)) return '';
    return "LIVE DATABASE DATA (fetched in real-time - use this when the user asks about their data):\n" . implode("\n\n", $results);
}

// ============================================================
// SAFETY RULES
// ============================================================
function safetyCheck($message) {
    $q = strtolower($message);
    $rules = [
        'brake fluid' => 'Brake fluid/hydraulic work is CRITICAL SAFETY WORK - if the customer is not an experienced mechanic, insist they get it done at a workshop. A brake failure at speed can kill.',
        'airbag' => 'AIRBAG work is extremely dangerous - always disconnect the battery and wait 10+ minutes before touching anything near airbags, or better: leave it to a professional with proper training.',
        'fuel tank|fuel pump|fuel line|petrol|diesel.*(tank|line)|fuel system' => 'FUEL SYSTEM work is highly flammable - no smoking or sparks, disconnect the battery, work only in a ventilated area, and never open a pressurized fuel line without proper procedure.',
        'coolant|radiator|overheat|over.heat' => 'NEVER open the radiator cap while the engine is HOT - pressurized coolant causes severe burns. Always let the engine cool completely first.',
        'hybrid|electric car|high voltage|ev battery' => 'HIGH-VOLTAGE systems (hybrid/EV) can KILL instantly - only factory-trained technicians should work on the high-voltage components.',
        'jack|lifting|raise.*car|under.*car' => 'Always use axle stands after jacking a car - NEVER work under a car supported only by a jack. Set the parking brake and chock the wheels.',
        'welding|grinder' => 'Disconnect the battery and remove/move the fuel tank before welding or grinding near the fuel system. Welding on a car with a connected battery damages the ECU.',
        'clutch' => 'If the clutch pedal goes to the floor with no resistance, do NOT drive - get the car towed to a workshop. A lost clutch is safer than an accident on the road.',
        'suspension|coil spring|strut' => 'Coil springs are under EXTREME tension - using improvised tools to remove them can cause fatal injury. Only use proper spring compressors or a professional.',
        'exhaust|manifold|gasket.*replace' => 'Warn the customer: exhaust components may be extremely hot; work only after the car has fully cooled.',
    ];
    foreach ($rules as $pattern => $advice) {
        if (preg_match('/' . $pattern . '/i', $q)) {
            return $advice . ' Include a clear safety warning in your answer when this topic is discussed.';
        }
    }
    return '';
}

// ============================================================
// EMERGENCY NUMBERS FOOTER - Appended to every response
// ============================================================
function renderEmergencyNumbersFooter() {
    return '<div class="mbot-emergency-footer">
        <div class="mbot-emergency-footer-header">
            <svg viewBox="0 0 24 24" fill="none" width="16" height="16"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" stroke="currentColor" stroke-width="2"/></svg>
            <span>EMERGENCY NUMBERS</span>
        </div>
        <div class="mbot-emergency-footer-grid">
            <a href="tel:130" class="mbot-emergency-footer-item">
                <div class="mbot-emergency-footer-icon" style="background:#2563EB;">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M12 2L2 7l10 5 10-5-10-5z" fill="#fff"/><path d="M2 17l10 5 10-5" stroke="#fff" stroke-width="2" fill="none"/><path d="M2 12l10 5 10-5" stroke="#fff" stroke-width="2" fill="none"/></svg>
                </div>
                <div class="mbot-emergency-footer-info">
                    <span class="mbot-emergency-footer-name">Motorway Police</span>
                    <span class="mbot-emergency-footer-num">130</span>
                </div>
            </a>
            <a href="tel:1122" class="mbot-emergency-footer-item">
                <div class="mbot-emergency-footer-icon" style="background:#DC2626;">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M22 12h-4l-3 9L9 3l-3 9H2" stroke="#fff" stroke-width="2" fill="none"/></svg>
                </div>
                <div class="mbot-emergency-footer-info">
                    <span class="mbot-emergency-footer-name">Rescue</span>
                    <span class="mbot-emergency-footer-num">1122</span>
                </div>
            </a>
            <a href="tel:101" class="mbot-emergency-footer-item">
                <div class="mbot-emergency-footer-icon" style="background:#7C3AED;">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" fill="#fff"/></svg>
                </div>
                <div class="mbot-emergency-footer-info">
                    <span class="mbot-emergency-footer-name">Police</span>
                    <span class="mbot-emergency-footer-num">101</span>
                </div>
            </a>
            <a href="tel:102" class="mbot-emergency-footer-item">
                <div class="mbot-emergency-footer-icon" style="background:#F97316;">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><path d="M12 2C10 2 8 4 8 6v2h8V6c0-2-2-4-4-4z" fill="#fff"/><rect x="10" y="8" width="4" height="3" rx="1" fill="#FACC15"/><circle cx="12" cy="14" r="4" fill="#fff" opacity="0.6"/></svg>
                </div>
                <div class="mbot-emergency-footer-info">
                    <span class="mbot-emergency-footer-name">Fire Brigade</span>
                    <span class="mbot-emergency-footer-num">102</span>
                </div>
            </a>
            <a href="tel:103" class="mbot-emergency-footer-item">
                <div class="mbot-emergency-footer-icon" style="background:#16A34A;">
                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14"><rect x="3" y="3" width="18" height="18" rx="2" fill="#fff"/><line x1="12" y1="8" x2="12" y2="16" stroke="#16A34A" stroke-width="2"/><line x1="8" y1="12" x2="16" y2="12" stroke="#16A34A" stroke-width="2"/></svg>
                </div>
                <div class="mbot-emergency-footer-info">
                    <span class="mbot-emergency-footer-name">Ambulance</span>
                    <span class="mbot-emergency-footer-num">103</span>
                </div>
            </a>
        </div>
    </div>';
}

// ============================================================
// RESPONSE GENERATOR (AI first, rule-based fallback)
// ============================================================
function generateResponse($message, $user_id, $conn, $history) {
    $msg = strtolower(trim($message));

    // Try AI first (Gemini)
    $ai = getGeminiReply($message, $conn, $history, $user_id);
    if ($ai !== null) return $ai . renderEmergencyNumbersFooter();

    // ===== 3-AGENT FALLBACK SYSTEM =====
    return routeToAgent($msg, $message, $user_id, $conn) . renderEmergencyNumbersFooter();
}

// ============================================================
// AGENT ROUTER - Determines which agent handles the message
// ============================================================
function routeToAgent($msg, $rawMsg, $user_id, $conn) {

    // --- SIMPLE INTENTS (no agent needed) ---
    if (preg_match('/^(hi|hello|hey|good\s*(morning|afternoon|evening)|assalam|salam)/i', $msg)) {
        return renderGreeting();
    }
    if (preg_match('/^(help|what can you do|options|menu)/i', $msg)) {
        return renderHelp();
    }
    if (preg_match('/(thank|thanks|thx|appreciate)/i', $msg)) {
        return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-ac"><div class="mbot-icon">&#128522;</div><div class="mbot-label">MechBot</div><div class="mbot-title">You\'re Welcome!</div></div><div class="mbot-card-body"><div style="font-size:0.85rem;color:#444;">Happy to help! Feel free to ask anything about parts, repairs, or services. Drive safe!</div></div></div>';
    }
    if (preg_match('/^(bye|goodbye|see you|take care|later)/i', $msg)) {
        return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-info"><div class="mbot-icon">&#128075;</div><div class="mbot-label">MechBot</div><div class="mbot-title">Goodbye!</div></div><div class="mbot-card-body"><div style="font-size:0.85rem;color:#444;">Drive safe and take care of your vehicle. Come back anytime you need help!</div></div></div>';
    }

    // --- AGENT 1: DIAGNOSTIC AGENT (vehicle issues, symptoms, problems) ---
    $isDiagnosis = preg_match('/(problem|issue|fault|kharab|masla|dikkat|noise|awaaz|shor|grind|squeak|knock|vibrat|shake|shak|smoke|dhuan|leak|rish|overheat|over.heat|heat|garam|temperature|stall|start|crank|self|brake|clutch|gear|transmission|battery|tyre|tire|puncture|oil|coolant|radiator|ac|aircon|pickup|mileage|engine|motor|check.*engine|warning.*light|dashboard|malfunction|fail|broken|damaged|not.*working|kaam|chalu|band|nahi|nai|dor|pull|drift|jerks|jhatka|hard|difficult|mushkil)/i', $msg);

    // --- AGENT 2: KNOWLEDGE AGENT (maintenance, DTC, general) ---
    $isKnowledge = preg_match('/(maintain|service\s*interval|oil.*change|when.*service|schedule|dtc|obd|code|p\d{4}|b\d{4}|c\d{4}|u\d{4}|specification|spec|torque|capacity|dimensions|compatible|year|model|fuel|petrol|diesel|cng|hybrid|electric|what is|kya hai|kaise|how|why|difference|compare)/i', $msg);

    // --- AGENT 3: PARTS & SERVICE (products, workshops, orders, booking) ---
    $isPartsService = preg_match('/(product|spare\s*part|part|buy|price|cost|how much|available|kitna|dam|rate|shop|store|tim|hour|open|close|location|address|contact|phone|email|order|delivery|shipped|track|book|appointment|schedule|reserve|workshop|mechanic|repair|service|garage|fix|categor)/i', $msg);

    // Priority: Diagnostic > Knowledge > Parts & Service
    if ($isDiagnosis && !$isPartsService) {
        return agentDiagnostic($msg, $rawMsg, $user_id, $conn);
    }
    if ($isKnowledge) {
        return agentKnowledge($msg, $rawMsg, $user_id, $conn);
    }
    if ($isPartsService) {
        return agentPartsService($msg, $rawMsg, $user_id, $conn);
    }

    // Try KB search as last resort
    $kbResults = searchKbProblems($msg, $conn, 0.3);
    if (!empty($kbResults)) {
        return agentDiagnostic($msg, $rawMsg, $user_id, $conn);
    }

    return renderFallback();
}

// ============================================================
// AGENT 1: DIAGNOSTIC AGENT
// Flow: Alert → Cause → Symptoms → Solution → Cost → Workshop
// NO products shown before diagnosis is complete
// ============================================================
function agentDiagnostic($msg, $rawMsg, $user_id, $conn) {
    // Check for emergency situations first
    $emergency = getEmergencyInstructions($rawMsg);
    $emergencyHtml = '';
    if ($emergency) {
        $emergencyHtml = '<div class="mbot-section mbot-emergency" style="margin-bottom:14px;border:2px solid ' . $emergency['color'] . ';background:' . $emergency['color'] . '08;padding:14px;border-radius:12px;">';
        $emergencyHtml .= '<div style="font-size:0.9rem;font-weight:700;color:' . $emergency['color'] . ';margin-bottom:8px;">' . $emergency['title'] . '</div>';
        $emergencyHtml .= '<ol style="margin:0;padding-left:20px;font-size:0.82rem;color:#444;line-height:2;">';
        foreach ($emergency['steps'] as $step) {
            $emergencyHtml .= '<li>' . $step . '</li>';
        }
        $emergencyHtml .= '</ol></div>';
    }

    $kbResults = searchKbProblems($msg, $conn);
    if (empty($kbResults)) {
        $kbResults = searchKbProblems($msg, $conn, 0.2);
    }

    if (empty($kbResults)) {
        $fallback = renderDiagnosisFallback($rawMsg);
        // Add quick tips to fallback too
        $tips = renderQuickTips($rawMsg);
        return $emergencyHtml . $fallback . $tips;
    }

    $best = $kbResults[0];
    $alt = array_slice($kbResults, 1, 3);
    $repairCost = estimateRepairCostFromKB($best['system'], $conn);
    $relatedProducts = getRelatedProducts($best['system'], $conn);

    // Add system-specific instructions to diagnosis report
    $instructionsHtml = getSystemInstructions($best['system'], $rawMsg);
    $tipsHtml = renderQuickTips($rawMsg);

    $report = renderDiagnosisReport($best, $alt, $repairCost, $relatedProducts, $conn);
    return $emergencyHtml . $report . $instructionsHtml . $tipsHtml;
}

// ============================================================
// AGENT 2: KNOWLEDGE AGENT
// Handles: DTC codes, maintenance schedules, general questions
// ============================================================
function agentKnowledge($msg, $rawMsg, $user_id, $conn) {
    // Check for emergency situations
    $emergency = getEmergencyInstructions($rawMsg);
    $emergencyHtml = '';
    if ($emergency) {
        $emergencyHtml = '<div class="mbot-section mbot-emergency" style="margin-bottom:14px;border:2px solid ' . $emergency['color'] . ';background:' . $emergency['color'] . '08;padding:14px;border-radius:12px;">';
        $emergencyHtml .= '<div style="font-size:0.9rem;font-weight:700;color:' . $emergency['color'] . ';margin-bottom:8px;">' . $emergency['title'] . '</div>';
        $emergencyHtml .= '<ol style="margin:0;padding-left:20px;font-size:0.82rem;color:#444;line-height:2;">';
        foreach ($emergency['steps'] as $step) {
            $emergencyHtml .= '<li>' . $step . '</li>';
        }
        $emergencyHtml .= '</ol></div>';
    }

    $tipsHtml = renderQuickTips($rawMsg);

    // DTC CODE
    if (preg_match('/([PBCU]\d{4})/i', $msg, $dtcMatch)) {
        $code = strtoupper($dtcMatch[1]);
        return $emergencyHtml . renderDTCReport($code, $conn) . $tipsHtml;
    }

    // MAINTENANCE SCHEDULE
    if (preg_match('/(maintain|service\s*interval|oil.*change|when.*service|schedule)/i', $msg)) {
        return $emergencyHtml . renderMaintenanceSchedule() . $tipsHtml;
    }

    // GENERAL KNOWLEDGE
    return $emergencyHtml . renderKnowledgeResponse($msg, $rawMsg, $conn) . $tipsHtml;
}

// ============================================================
// AGENT 3: PARTS & SERVICE AGENT
// Handles: products, workshops, orders, booking
// ============================================================
function agentPartsService($msg, $rawMsg, $user_id, $conn) {
    // Check for emergency situations
    $emergency = getEmergencyInstructions($rawMsg);
    $emergencyHtml = '';
    if ($emergency) {
        $emergencyHtml = '<div class="mbot-section mbot-emergency" style="margin-bottom:14px;border:2px solid ' . $emergency['color'] . ';background:' . $emergency['color'] . '08;padding:14px;border-radius:12px;">';
        $emergencyHtml .= '<div style="font-size:0.9rem;font-weight:700;color:' . $emergency['color'] . ';margin-bottom:8px;">' . $emergency['title'] . '</div>';
        $emergencyHtml .= '<ol style="margin:0;padding-left:20px;font-size:0.82rem;color:#444;line-height:2;">';
        foreach ($emergency['steps'] as $step) {
            $emergencyHtml .= '<li>' . $step . '</li>';
        }
        $emergencyHtml .= '</ol></div>';
    }

    $tipsHtml = renderQuickTips($rawMsg);

    // ORDER STATUS
    if (preg_match('/(order|delivery|shipped|track|where.*order)/i', $msg)) {
        return $emergencyHtml . renderOrderStatus($msg, $user_id, $conn) . $tipsHtml;
    }

    // BOOKING
    if (preg_match('/(book|appointment|schedule|reserve)/i', $msg)) {
        return $emergencyHtml . renderBooking() . $tipsHtml;
    }

    // STORE INFO
    if (preg_match('/(store|shop|tim|hour|open|close|location|address|contact|phone|email|where.*located)/i', $msg)) {
        return $emergencyHtml . renderStoreInfo() . $tipsHtml;
    }

    // WORKSHOP
    if (preg_match('/(workshop|mechanic|repair|service|garage)/i', $msg)) {
        return $emergencyHtml . renderWorkshopList($conn) . $tipsHtml;
    }

    // CATEGORIES
    if (preg_match('/(categor|browse)/i', $msg)) {
        return $emergencyHtml . renderCategoryList($conn) . $tipsHtml;
    }

    // PRODUCT SEARCH
    return $emergencyHtml . renderProductSearch($msg, $rawMsg, $conn) . $tipsHtml;
}

// ============================================================
// RENDERERS - Beautiful styled HTML responses
// ============================================================

function renderGreeting() {
    return '<div class="mbot-greeting">' .
           '<div class="mbot-greeting-icon">&#129302;</div>' .
           '<div class="mbot-greeting-title">Welcome to Wrench n Parts!</div>' .
           '<div class="mbot-greeting-desc">I\'m <strong>MechBot</strong>, your AI mechanic with 20+ years of workshop experience. I\'ll diagnose your vehicle problem step by step.</div>' .
           '<div class="mbot-pills">' .
           '<span class="mbot-pill">&#128295; Diagnosis</span>' .
           '<span class="mbot-pill">&#128176; Prices</span>' .
           '<span class="mbot-pill">&#128736; Workshops</span>' .
           '<span class="mbot-pill">&#128230; Parts</span>' .
           '</div></div>';
}

function renderHelp() {
    return '<div class="mbot-card">' .
           '<div class="mbot-card-header mbot-bg-info">' .
           '<div class="mbot-icon">&#129302;</div>' .
           '<div class="mbot-label">MechBot Capabilities</div>' .
           '<div class="mbot-title">What I Can Do</div></div>' .
           '<div class="mbot-card-body">' .
           '<div class="mbot-section"><div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-engine">&#128295;</span> Vehicle Diagnosis</div><ul><li>Describe your car problem in detail</li><li>I\'ll analyze causes, symptoms, provide fix steps, cost estimate & parts if needed</li></ul></div>' .
           '<div class="mbot-section"><div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-warning">&#128666;</span> DTC / OBD Codes</div><ul><li>Enter a code like P0301 for instant diagnosis report</li></ul></div>' .
           '<div class="mbot-section"><div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-product">&#128176;</span> Parts & Prices</div><ul><li>Search products by name, brand, or vehicle</li></ul></div>' .
           '<div class="mbot-section"><div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-workshop">&#128736;</span> Workshops</div><ul><li>Find trusted workshops and book appointments</li></ul></div>' .
           '<div style="margin-top:10px;padding:10px 14px;background:#f8f9fa;border-radius:10px;font-size:0.78rem;color:#666;"><strong>Try:</strong> <em>"meri gari start nahi ho rahi"</em> or <em>"brake pads ka price?"</em></div>' .
           '</div></div>';
}

function renderFallback() {
    $options = [
        "I can help you with vehicle diagnosis, parts search, workshop bookings, and more! Try describing your car problem or ask about spare parts.",
        "I didn't quite catch that. You can:\n- Describe a car problem for diagnosis\n- Ask about part prices\n- Search for workshops\n- Check order status",
        "I specialize in auto repair and parts. Try telling me about a car issue you're having, or ask about prices and workshops!"
    ];
    return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-info"><div class="mbot-icon">&#129302;</div><div class="mbot-label">MechBot</div><div class="mbot-title">How Can I Help?</div></div><div class="mbot-card-body"><div style="font-size:0.85rem;color:#444;line-height:1.6;">' . $options[array_rand($options)] . '</div></div></div>';
}

// ============================================================
// DIAGNOSIS REPORT RENDERER
// Flow: Alert → Cause → Symptoms → Solution → Cost → Workshop
// ============================================================
function renderDiagnosisReport($best, $alt, $repairCost, $relatedProducts, $conn) {
    $problem = htmlspecialchars($best['problem']);
    $system  = htmlspecialchars($best['system']);
    $sysLower = strtolower($system);

    $sysIcons = [
        'Engine' => '&#9881;', 'Transmission' => '&#9881;', 'Brake' => '&#128694;',
        'Suspension' => '&#128694;', 'Electrical' => '&#9889;', 'Cooling' => '&#127777;',
        'AC' => '&#9729;', 'Fuel' => '&#9981;', 'Hybrid' => '&#9889;',
        'EV' => '&#9889;', 'Sensors' => '&#128225;'
    ];
    $icon = $sysIcons[$system] ?? '&#128295;';

    // Determine severity
    $severityMap = [
        'Engine' => 'high', 'Brake' => 'high', 'Cooling' => 'high', 'Transmission' => 'high',
        'Hybrid' => 'high', 'EV' => 'high', 'Suspension' => 'medium', 'Electrical' => 'medium',
        'Fuel' => 'medium', 'AC' => 'low', 'Sensors' => 'low'
    ];
    $severity = $severityMap[$system] ?? 'medium';
    $severityLabels = [
        'high' => ['text' => '&#9888; URGENT - Do Not Drive', 'class' => 'high', 'color' => '#dc3545'],
        'medium' => ['text' => '&#9888; Needs Attention Soon', 'class' => 'medium', 'color' => '#e67e22'],
        'low' => ['text' => '&#9899; Monitor Closely', 'class' => 'low', 'color' => '#198754']
    ];
    $sev = $severityLabels[$severity];

    // Alert messages based on severity
    $alertMessages = [
        'high' => 'This issue can cause serious damage or safety risk if ignored. Stop driving and get it inspected immediately.',
        'medium' => 'This should be checked within 1-2 days to prevent further damage or costly repairs.',
        'low' => 'This is not an emergency but should be inspected during your next service visit.'
    ];

    $resp = '<div class="mbot-card">';

    // === HEADER ===
    $resp .= '<div class="mbot-card-header mbot-bg-' . $sysLower . '">';
    $resp .= '<div class="mbot-icon">' . $icon . '</div>';
    $resp .= '<div class="mbot-label">MechBot Diagnosis Report</div>';
    $resp .= '<div class="mbot-title">' . $problem . '</div>';
    $resp .= '</div>';

    $resp .= '<div class="mbot-card-body">';

    // Badges
    $resp .= '<span class="mbot-badge mbot-bg-' . $sysLower . '">' . $system . ' System</span>';
    $resp .= '<span class="mbot-severity ' . $sev['class'] . '">' . $sev['text'] . '</span>';

    // === SECTION 1: AWARENESS ALERT ===
    $resp .= '<div class="mbot-section" style="margin-top:14px;">';
    $resp .= '<div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-warning">&#9888;&#65039;</span> Awareness Alert</div>';
    $resp .= '<div style="background:linear-gradient(135deg,' . $sev['color'] . '11,' . $sev['color'] . '05);border:1px solid ' . $sev['color'] . '33;border-radius:10px;padding:12px 14px;font-size:0.82rem;color:#444;line-height:1.6;">';
    $resp .= '<strong style="color:' . $sev['color'] . ';">' . $alertMessages[$severity] . '</strong>';
    $resp .= '<div style="margin-top:6px;font-size:0.78rem;color:#666;">Ignoring this issue may lead to more expensive repairs, reduced vehicle performance, or safety hazards.</div>';
    $resp .= '</div></div>';

    // === SECTION 2: CAUSE ===
    $resp .= '<div class="mbot-section">';
    $resp .= '<div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-' . $sysLower . '">&#128161;</span> Root Cause</div>';
    $resp .= '<ul>' . formatBulletList($best['causes']) . '</ul>';
    $resp .= '</div>';

    // === SECTION 3: SYMPTOMS ===
    $resp .= '<div class="mbot-section">';
    $resp .= '<div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-warning">&#128269;</span> Symptoms to Watch For</div>';
    $resp .= '<ul>' . formatBulletList($best['symptoms']) . '</ul>';
    $resp .= '</div>';

    // === SECTION 4: SOLUTION ===
    $resp .= '<div class="mbot-section">';
    $resp .= '<div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-cost">&#128736;</span> Recommended Solution</div>';
    $resp .= '<ul>' . formatBulletList($best['solution']) . '</ul>';
    $resp .= '</div>';

    // === SECTION 5: TOOLS NEEDED ===
    $toolsMap = [
        'Engine' => 'OBD2 Scanner, Multimeter, Compression Tester, Socket Set',
        'Transmission' => 'OBD2 Scanner, Transmission Fluid Dipstick, Jack & Stands',
        'Brake' => 'Jack & Stands, Brake Spanner, Brake Fluid, Torque Wrench',
        'Suspension' => 'Jack & Stands, Socket Set, Pry Bar, Spring Compressor',
        'Electrical' => 'Multimeter, Test Light, Wire Tracer, Fuse Puller',
        'Cooling' => 'Coolant Pressure Tester, Thermometer, Funnel',
        'AC' => 'Manifold Gauge Set, UV Leak Detector, Refrigerant',
        'Fuel' => 'Fuel Pressure Gauge, OBD2 Scanner, Safety Goggles',
        'Hybrid' => 'OBD2 Scanner, Insulated Gloves, Multimeter, Safety Goggles',
        'EV' => 'OBD2 Scanner, Insulated Tools, Multimeter, Safety Goggles',
        'Sensors' => 'OBD2 Scanner, Multimeter, Oscilloscope'
    ];
    if (isset($toolsMap[$system])) {
        $resp .= '<div class="mbot-section">';
        $resp .= '<div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-tool">&#128295;</span> Tools & Equipment Needed</div>';
        $resp .= '<ul>';
        foreach (explode(', ', $toolsMap[$system]) as $tool) {
            $resp .= '<li>' . htmlspecialchars($tool) . '</li>';
        }
        $resp .= '</ul></div>';
    }

    // === SECTION 6: ESTIMATED COST ===
    if ($repairCost) {
        $resp .= '<div class="mbot-cost mbot-border-' . $sysLower . '">';
        $resp .= '<div class="mbot-cost-label">&#128176; Estimated Repair Cost</div>';
        $resp .= '<div class="mbot-cost-value">' . $repairCost . '</div>';
        $resp .= '<div class="mbot-cost-note">*Cost varies by workshop, parts brand, and vehicle model. Get an exact quote at a workshop.</div>';
        $resp .= '</div>';
    }

    // === SECTION 7: PARTS (only if serious need) ===
    if ($relatedProducts && in_array($severity, ['high', 'medium'])) {
        $resp .= '<div class="mbot-section">';
        $resp .= '<div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-product">&#128722;</span> Parts You May Need</div>';
        $resp .= '<div class="mbot-products">';
        foreach ($relatedProducts as $p) {
            $stockClass = $p['stock'] > 0 ? 'in' : 'out';
            $stockText  = $p['stock'] > 0 ? '&#9989; In Stock' : '&#10060; Low Stock';
            $price = $p['discount_price'] && $p['discount_price'] < $p['price'] ? formatCurrency($p['discount_price']) : formatCurrency($p['price']);
            $hasDiscount = $p['discount_price'] && $p['discount_price'] < $p['price'];

            $resp .= '<div class="mbot-product">';
            $resp .= '<div class="mbot-product-info">';
            $resp .= '<div class="mbot-product-name">' . htmlspecialchars($p['product_name']) . '</div>';
            $resp .= '<div class="mbot-product-meta"><span>' . htmlspecialchars($p['brand']) . '</span>';
            $resp .= '<span class="mbot-product-stock ' . $stockClass . '">' . $stockText . '</span></div></div>';
            $resp .= '<div class="mbot-product-price"><div class="mbot-price-new">' . $price . '</div>';
            if ($hasDiscount) $resp .= '<div class="mbot-price-old">' . formatCurrency($p['price']) . '</div>';
            $resp .= '</div></div>';
        }
        $resp .= '</div>';
        $resp .= '<a href="/Wrench_n_Parts/products.php" class="mbot-quick-btn" style="border-color:#6f42c1;color:#6f42c1;">Browse All Parts &#8594;</a>';
        $resp .= '</div>';
    }

    // === SECTION 8: ALTERNATIVES ===
    if (!empty($alt)) {
        $resp .= '<div class="mbot-alt">';
        $resp .= '<div class="mbot-alt-title">Other Possible Issues</div>';
        foreach ($alt as $a) {
            $resp .= '<div class="mbot-alt-item">' . htmlspecialchars($a['problem']) . ' <span style="color:#999;">(' . htmlspecialchars($a['system']) . ')</span></div>';
        }
        $resp .= '</div>';
    }

    // === SECTION 9: SAFETY ===
    $resp .= '<div class="mbot-safety" style="background:#fff3cd;border:1px solid #ffc107;">';
    $resp .= '<div class="mbot-safety-icon">&#9888;</div>';
    $resp .= '<div class="mbot-safety-text"><strong style="color:#856404;">Professional Inspection Recommended</strong>';
    $resp .= '<span style="color:#856404;">This diagnosis is based on symptoms. For accurate diagnosis and repair, visit a trusted workshop. Do not ignore warning signs.</span></div>';
    $resp .= '</div>';

    // === SECTION 10: WORKSHOP CTA ===
    $resp .= '<a href="/Wrench_n_Parts/workshop-finder.php" class="mbot-cta mbot-bg-' . $sysLower . '">&#128736; Book a Workshop Now</a>';

    $resp .= '</div></div>';
    return $resp;
}

function renderDiagnosisFallback($rawMsg) {
    return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-info"><div class="mbot-icon">&#128269;</div><div class="mbot-label">Diagnosis Agent</div><div class="mbot-title">Tell Me More</div></div><div class="mbot-card-body">' .
           '<div style="font-size:0.85rem;color:#444;line-height:1.6;margin-bottom:12px;">I need more details to diagnose your vehicle accurately. Please tell me:</div>' .
           '<ul style="padding-left:18px;font-size:0.82rem;color:#444;line-height:1.8;">' .
           '<li><strong>What\'s the problem?</strong> (e.g. noise, vibration, warning light, smoke, leak)</li>' .
           '<li><strong>When does it happen?</strong> (starting, braking, accelerating, idle)</li>' .
           '<li><strong>Any warning lights?</strong> (check engine, battery, oil, temperature)</li>' .
           '<li><strong>Your vehicle details?</strong> (brand, model, year, fuel type)</li>' .
           '</ul>' .
           '<div style="margin-top:12px;padding:10px 14px;background:#f8f9fa;border-radius:10px;font-size:0.78rem;color:#666;"><strong>Example:</strong> <em>"Toyota Corolla 2020 - engine mein awaaz aa rahi hai, especially when accelerating"</em></div>' .
           '</div></div>';
}

// ============================================================
// DTC REPORT RENDERER
// ============================================================
function renderDTCReport($code, $conn) {
    $dtc = $conn->query("SELECT * FROM kb_dtc_codes WHERE UPPER(dtc_code) = '" . $conn->real_escape_string($code) . "' LIMIT 1");
    if ($dtc && $dtc->num_rows > 0) {
        $d = $dtc->fetch_assoc();
        $resp = '<div class="mbot-card">';
        $resp .= '<div class="mbot-card-header mbot-bg-dtc">';
        $resp .= '<div class="mbot-icon">&#128666;</div>';
        $resp .= '<div class="mbot-label">DTC Diagnostic Report</div>';
        $resp .= '<div class="mbot-title">Code: ' . $code . '</div></div>';
        $resp .= '<div class="mbot-card-body">';
        $resp .= '<span class="mbot-badge mbot-bg-dtc">' . htmlspecialchars($d['description'] ?? 'Fault Detected') . '</span>';
        $resp .= '<div class="mbot-section"><div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-warning">&#9888;&#65039;</span> Symptoms</div><ul>' . formatBulletList($d['symptoms'] ?? 'Check engine light') . '</ul></div>';
        $resp .= '<div class="mbot-section"><div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-engine">&#128161;</span> Possible Causes</div><ul>' . formatBulletList($d['possible_causes'] ?? 'Sensor issue') . '</ul></div>';
        $resp .= '<div class="mbot-section"><div class="mbot-section-title"><span class="mbot-sec-icon mbot-sec-icon-cost">&#128736;</span> Recommended Fix</div><ul>' . formatBulletList($d['recommended_fix'] ?? 'Professional diagnosis') . '</ul></div>';
        if (!empty($d['estimated_cost'])) {
            $resp .= '<div class="mbot-cost mbot-border-engine"><div class="mbot-cost-label">&#128176; Estimated Cost</div><div class="mbot-cost-value">' . htmlspecialchars($d['estimated_cost']) . '</div></div>';
        }
        $resp .= '<a href="/Wrench_n_Parts/workshop-finder.php" class="mbot-cta mbot-bg-dtc">&#128736; Get Professional Diagnosis</a>';
        $resp .= '</div></div>';
        return $resp;
    }
    return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-dtc"><div class="mbot-icon">&#128666;</div><div class="mbot-label">DTC Code</div><div class="mbot-title">Code: ' . $code . '</div></div><div class="mbot-card-body"><div style="font-size:0.85rem;color:#444;margin-bottom:10px;">This code indicates a fault. A proper scan tool diagnosis is needed at a workshop.</div><a href="/Wrench_n_Parts/workshop-finder.php" class="mbot-cta mbot-bg-dtc">&#128736; Find Workshop</a></div></div>';
}

// ============================================================
// MAINTENANCE SCHEDULE RENDERER
// ============================================================
function renderMaintenanceSchedule() {
    $schedule = [
        ['icon' => '&#128187;', 'name' => 'Engine Oil', 'km' => '5,000 - 7,500 km', 'color' => '#dc3545'],
        ['icon' => '&#128168;', 'name' => 'Air Filter', 'km' => '15,000 - 20,000 km', 'color' => '#0d6efd'],
        ['icon' => '&#128167;', 'name' => 'Cabin Filter', 'km' => '15,000 - 20,000 km', 'color' => '#0dcaf0'],
        ['icon' => '&#128694;', 'name' => 'Brake Pads', 'km' => '30,000 - 50,000 km', 'color' => '#fd7e14'],
        ['icon' => '&#9889;', 'name' => 'Spark Plugs', 'km' => '30,000 - 50,000 km', 'color' => '#ffc107'],
        ['icon' => '&#127777;', 'name' => 'Coolant Flush', 'km' => '40,000 - 50,000 km', 'color' => '#0dcaf0'],
        ['icon' => '&#9881;', 'name' => 'Transmission Fluid', 'km' => '60,000 - 80,000 km', 'color' => '#6f42c1'],
        ['icon' => '&#129529;', 'name' => 'Timing Belt', 'km' => '80,000 - 100,000 km', 'color' => '#dc3545'],
    ];
    $resp = '<div class="mbot-card">';
    $resp .= '<div class="mbot-card-header mbot-bg-maintenance">';
    $resp .= '<div class="mbot-icon">&#128336;</div>';
    $resp .= '<div class="mbot-label">Knowledge Agent</div>';
    $resp .= '<div class="mbot-title">Maintenance Schedule</div></div>';
    $resp .= '<div class="mbot-card-body">';
    $resp .= '<ul class="mbot-schedule">';
    foreach ($schedule as $s) {
        $resp .= '<li>';
        $resp .= '<div class="mbot-schedule-icon" style="background:' . $s['color'] . '15;color:' . $s['color'] . ';">' . $s['icon'] . '</div>';
        $resp .= '<div class="mbot-schedule-text"><strong>' . $s['name'] . '</strong></div>';
        $resp .= '<div class="mbot-schedule-km" style="color:' . $s['color'] . ';">' . $s['km'] . '</div>';
        $resp .= '</li>';
    }
    $resp .= '</ul>';
    $resp .= '<div style="margin-top:12px;padding:10px 14px;background:#f8f9fa;border-radius:10px;font-size:0.78rem;color:#666;">Tell me your car\'s mileage for personalized recommendations!</div>';
    $resp .= '</div></div>';
    return $resp;
}

// ============================================================
// KNOWLEDGE AGENT - General questions
// ============================================================
function renderKnowledgeResponse($msg, $rawMsg, $conn) {
    return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-info"><div class="mbot-icon">&#128218;</div><div class="mbot-label">Knowledge Agent</div><div class="mbot-title">General Information</div></div><div class="mbot-card-body">' .
           '<div style="font-size:0.85rem;color:#444;line-height:1.6;">I can help with vehicle maintenance schedules, OBD/DTC code explanations, specifications, and general automotive questions.</div>' .
           '<div style="margin-top:10px;font-size:0.82rem;color:#666;">Try asking about:<br>- Maintenance intervals<br>- DTC codes (e.g. P0301)<br>- Vehicle specifications<br>- What a warning light means</div>' .
           '</div></div>';
}

// ============================================================
// ORDER STATUS RENDERER
// ============================================================
function renderOrderStatus($msg, $user_id, $conn) {
    if (!$user_id) {
        return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-order"><div class="mbot-icon">&#128230;</div><div class="mbot-label">Orders</div><div class="mbot-title">Track Your Orders</div></div><div class="mbot-card-body"><div style="font-size:0.85rem;color:#444;margin-bottom:10px;">Login to check your order status and delivery updates.</div><a href="/Wrench_n_Parts/login.php" class="mbot-cta mbot-bg-order">Login Now</a></div></div>';
    }
    $stmt = $conn->prepare("SELECT order_id, order_status, total_amount, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 3");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders = $stmt->get_result();
    if ($orders->num_rows === 0) {
        return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-order"><div class="mbot-icon">&#128230;</div><div class="mbot-label">Orders</div><div class="mbot-title">No Orders Yet</div></div><div class="mbot-card-body"><div style="font-size:0.85rem;color:#444;margin-bottom:10px;">You haven\'t placed any orders yet.</div><a href="/Wrench_n_Parts/products.php" class="mbot-cta mbot-bg-order">Start Shopping</a></div></div>';
    }
    $resp = '<div class="mbot-listing"><div class="mbot-listing-header mbot-bg-order"><div class="mbot-label">Your Orders</div><div class="mbot-title">&#128230; Recent Orders</div></div><div class="mbot-listing-body">';
    $statusColors = ['delivered'=>'#198754','confirmed'=>'#20c997','processing'=>'#ffc107','pending'=>'#ffc107','shipped'=>'#0d6efd','cancelled'=>'#dc3545'];
    while ($o = $orders->fetch_assoc()) {
        $sc = $statusColors[$o['order_status']] ?? '#6c757d';
        $resp .= '<div class="mbot-order"><div><div class="mbot-order-id">#' . $o['order_id'] . '</div><div class="mbot-order-details">' . formatCurrency($o['total_amount']) . ' &middot; ' . date('M d, Y', strtotime($o['created_at'])) . '</div></div><span class="mbot-order-status" style="background:' . $sc . ';">' . ucfirst($o['order_status']) . '</span></div>';
    }
    $resp .= '<a href="/Wrench_n_Parts/customer/dashboard.php" class="mbot-quick-btn" style="border-color:#0d6efd;color:#0d6efd;margin-top:6px;">View All Orders &#8594;</a>';
    $resp .= '</div></div>';
    return $resp;
}

// ============================================================
// BOOKING RENDERER
// ============================================================
function renderBooking() {
    return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-workshop"><div class="mbot-icon">&#128197;</div><div class="mbot-label">Service Agent</div><div class="mbot-title">Book a Workshop</div></div><div class="mbot-card-body">' .
           '<ol style="margin:0;padding-left:18px;font-size:0.82rem;color:#444;line-height:2;">' .
           '<li>Visit <a href="/Wrench_n_Parts/workshop-finder.php" style="color:#198754;font-weight:600;">Workshop Finder</a></li>' .
           '<li>Choose a workshop near you</li>' .
           '<li>Click "Book Appointment"</li>' .
           '<li>Fill in vehicle details</li>' .
           '<li>Choose your preferred date & time</li></ol>' .
           '<a href="/Wrench_n_Parts/workshop-finder.php" class="mbot-cta mbot-bg-workshop" style="margin-top:12px;">&#128736; Open Workshop Finder</a></div></div>';
}

// ============================================================
// STORE INFO RENDERER
// ============================================================
function renderStoreInfo() {
    return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-info"><div class="mbot-icon">&#127970;</div><div class="mbot-label">Store Information</div><div class="mbot-title">Wrench n Parts</div></div><div class="mbot-card-body">' .
           '<div style="font-size:0.85rem;color:#444;line-height:2;">' .
           '<div>&#128205; <strong>Location:</strong> Pakistan</div>' .
           '<div>&#128222; <strong>Phone:</strong> +92 300 1234567</div>' .
           '<div>&#9993; <strong>Email:</strong> info@wrenchnparts.com</div>' .
           '<div>&#128336; <strong>Hours:</strong> Mon-Sat 8:00 AM - 6:00 PM</div>' .
           '<div>&#128336; <strong>Sunday:</strong> Closed</div>' .
           '<div style="margin-top:6px;color:#999;font-size:0.78rem;">Online 24/7 at <a href="/Wrench_n_Parts/" style="color:#6c757d;font-weight:600;">wrenchnparts.com</a></div>' .
           '</div></div></div>';
}

// ============================================================
// WORKSHOP LIST RENDERER
// ============================================================
function renderWorkshopList($conn) {
    $ws = $conn->query("SELECT workshop_name, location, services, rating FROM workshops WHERE status IN ('active','approved') ORDER BY rating DESC LIMIT 3");
    if ($ws->num_rows === 0) {
        return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-workshop"><div class="mbot-icon">&#128736;</div><div class="mbot-label">Workshops</div><div class="mbot-title">Find a Workshop</div></div><div class="mbot-card-body"><div style="font-size:0.85rem;color:#444;margin-bottom:10px;">Discover trusted workshops near you.</div><a href="/Wrench_n_Parts/workshop-finder.php" class="mbot-cta mbot-bg-workshop">Open Workshop Finder</a></div></div>';
    }
    $resp = '<div class="mbot-listing"><div class="mbot-listing-header mbot-bg-workshop"><div class="mbot-label">Trusted Workshops</div><div class="mbot-title">&#128736; Top-Rated Near You</div></div><div class="mbot-listing-body">';
    while ($w = $ws->fetch_assoc()) {
        $resp .= '<div class="mbot-ws"><div class="mbot-ws-name">' . htmlspecialchars($w['workshop_name']) . '</div><div class="mbot-ws-meta"><span>&#128205; ' . htmlspecialchars($w['location']) . '</span><span class="mbot-ws-rating">&#11088; ' . $w['rating'] . '/5</span></div><div class="mbot-ws-services">' . htmlspecialchars($w['services']) . '</div></div>';
    }
    $resp .= '<a href="/Wrench_n_Parts/workshop-finder.php" class="mbot-cta mbot-bg-workshop" style="margin-top:8px;">&#128736; Find More Workshops</a>';
    $resp .= '</div></div>';
    return $resp;
}

// ============================================================
// CATEGORY LIST RENDERER
// ============================================================
function renderCategoryList($conn) {
    $cats = $conn->query("SELECT category_name, (SELECT COUNT(*) FROM products WHERE category_id = categories.category_id AND status='available') as cnt FROM categories ORDER BY category_name");
    $resp = '<div class="mbot-listing"><div class="mbot-listing-header mbot-bg-category"><div class="mbot-label">Product Categories</div><div class="mbot-title">&#128194; Browse by Category</div></div><div class="mbot-listing-body">';
    while ($c = $cats->fetch_assoc()) {
        $resp .= '<div class="mbot-product"><div class="mbot-product-info"><div class="mbot-product-name">' . htmlspecialchars($c['category_name']) . '</div></div><div class="mbot-product-price"><div class="mbot-price-new" style="font-size:0.8rem;">' . $c['cnt'] . ' products</div></div></div>';
    }
    $resp .= '<a href="/Wrench_n_Parts/products.php" class="mbot-quick-btn" style="border-color:#0d6efd;color:#0d6efd;margin-top:6px;">Browse All &#8594;</a>';
    $resp .= '</div></div>';
    return $resp;
}

// ============================================================
// PRODUCT SEARCH RENDERER
// ============================================================
function renderProductSearch($msg, $rawMsg, $conn) {
    // Build smart search query
    $searchTerms = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $msg);
    $searchTerms = preg_replace('/\s+/', ' ', trim($searchTerms));
    $words = explode(' ', $searchTerms);
    $stopWords = ['meri','mera','ka','ki','ke','hai','ho','raha','rahi','me','my','the','a','an','or','and','is','kya','price','cost','kitna','dam','rate','buy','available','part','parts','spare','product'];
    $keywords = array_diff($words, $stopWords);
    $keywords = array_filter($keywords, fn($w) => strlen($w) >= 2);

    if (!empty($keywords)) {
        $likes = [];
        $params = [];
        $types = '';
        foreach ($keywords as $kw) {
            $likes[] = '(product_name LIKE ? OR brand LIKE ? OR description LIKE ?)';
            $like = "%{$kw}%";
            $params = array_merge($params, [$like, $like, $like]);
            $types .= 'sss';
        }
        $sql = "SELECT product_name, price, discount_price, brand, stock FROM products WHERE status = 'available' AND (" . implode(' OR ', $likes) . ") ORDER BY product_name LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $products = $stmt->get_result();
    } else {
        $products = $conn->query("SELECT product_name, price, discount_price, brand, stock FROM products WHERE status = 'available' ORDER BY RAND() LIMIT 5");
    }

    if ($products->num_rows > 0) {
        $resp = '<div class="mbot-listing"><div class="mbot-listing-header mbot-bg-product"><div class="mbot-label">Product Search</div><div class="mbot-title">&#128722; Available Parts</div></div><div class="mbot-listing-body"><div class="mbot-products">';
        while ($p = $products->fetch_assoc()) {
            $stockClass = $p['stock'] > 0 ? 'in' : 'out';
            $stockText  = $p['stock'] > 0 ? '&#9989; In Stock' : '&#10060; Low Stock';
            $price = $p['discount_price'] && $p['discount_price'] < $p['price'] ? formatCurrency($p['discount_price']) : formatCurrency($p['price']);
            $hasDiscount = $p['discount_price'] && $p['discount_price'] < $p['price'];
            $resp .= '<div class="mbot-product"><div class="mbot-product-info"><div class="mbot-product-name">' . htmlspecialchars($p['product_name']) . '</div><div class="mbot-product-meta"><span>' . htmlspecialchars($p['brand']) . '</span><span class="mbot-product-stock ' . $stockClass . '">' . $stockText . '</span></div></div><div class="mbot-product-price"><div class="mbot-price-new">' . $price . '</div>';
            if ($hasDiscount) $resp .= '<div class="mbot-price-old">' . formatCurrency($p['price']) . '</div>';
            $resp .= '</div></div>';
        }
        $resp .= '</div><a href="/Wrench_n_Parts/products.php" class="mbot-quick-btn" style="border-color:#6f42c1;color:#6f42c1;margin-top:6px;">Browse All Products &#8594;</a></div></div>';
        return $resp;
    }
    return '<div class="mbot-card"><div class="mbot-card-header mbot-bg-product"><div class="mbot-icon">&#128722;</div><div class="mbot-label">Products</div><div class="mbot-title">Browse Parts</div></div><div class="mbot-card-body"><div style="font-size:0.85rem;color:#444;margin-bottom:10px;">No products found matching your search. Try different keywords.</div><a href="/Wrench_n_Parts/products.php" class="mbot-cta mbot-bg-product">View All Products</a></div></div>';
}

// ============================================================
// KB PROBLEMS SEARCH (keyword-based, falls back to LIKE)
// ============================================================
function searchKbProblems($msg, $conn, $minScore = 0.4) {
    $stopwords = ['the','a','an','and','or','is','are','of','to','in','for','my','me','i','car','gari','vehicle','hai','raha','rahi','ho','ka','ki','ke','se','ma','mein','that','this','with','what','how','why','does','do','s','t','ye','meri','mera','kya','hain','ek','bhi','nahi','abhi','kal','pehle','baad','ko','par','liye','kab','kaise','kaisa','kaisi','woh','us','usko','uski','uska','unka','unki','unko','thora','bohat','zyada','kam','sath','wala','wali','wale','lag','rha','rahe','rhi','kr','kra','karti','karta','hua','hui','huye','gaye','gai','wapis','chala','chal','band','khula','sara','sari','sare'];

    $tokens = array_diff(explode(' ', preg_replace('/[^a-zA-Z0-9\s]/', ' ', strtolower($msg))), $stopwords);
    $tokens = array_filter($tokens, fn($t) => strlen($t) >= 2);
    if (empty($tokens)) return [];

    // Roman Urdu -> English mapping for common auto terms
    $ruMap = [
        'heat' => 'overheat', 'garam' => 'overheat', 'over' => 'overheat',
        'overheat' => 'overheat', 'temperature' => 'overheat',
        'dhak' => 'knock', 'dhakna' => 'knock', 'knocking' => 'knock',
        'awaaz' => 'noise', 'aawaz' => 'noise', 'shor' => 'noise',
        'sound' => 'noise',
        'start' => 'start', 'chalu' => 'start', 'self' => 'start',
        'crank' => 'crank', 'cranking' => 'crank',
        'brake' => 'brake', 'brakes' => 'brake', 'break' => 'brake',
        'grind' => 'grinding', 'grinding' => 'grinding',
        'squeak' => 'squeak', 'squeaking' => 'squeak', 'chir' => 'squeak',
        'vibrat' => 'vibration', 'hilna' => 'vibration',
        'smoke' => 'smoke', 'dhuan' => 'smoke',
        'leak' => 'leak', 'leakage' => 'leak', 'rish' => 'leak',
        'oil' => 'oil', 'tel' => 'oil',
        'battery' => 'battery', 'battary' => 'battery',
        'tyre' => 'tire', 'tyres' => 'tire', 'tire' => 'tire', 'puncture' => 'tire',
        'clutch' => 'clutch', 'gear' => 'gear', 'transmission' => 'transmission',
        'coolant' => 'coolant', 'pani' => 'coolant', 'radiator' => 'radiator',
        'engine' => 'engine', 'motor' => 'engine',
        'filter' => 'filter',
        'ac' => 'ac', 'hawa' => 'ac',
        'pickup' => 'pickup', 'power' => 'power',
        'mileage' => 'mileage', 'average' => 'mileage',
        'shaking' => 'shake', 'shak' => 'shake',
        'pull' => 'pull', 'khinchna' => 'pull',
        'drift' => 'drift',
        'kharab' => 'fault', 'problem' => 'fault', 'masla' => 'fault',
        'issue' => 'fault', 'dikkat' => 'fault',
        'check' => 'check', 'inspect' => 'check',
        'replace' => 'replace', 'badlo' => 'replace', 'change' => 'replace',
        'service' => 'service', 'maintain' => 'maintenance',
    ];

    $expanded = [];
    foreach ($tokens as $t) {
        $expanded[] = $t;
        if (isset($ruMap[$t]) && $ruMap[$t] !== '') {
            $expanded[] = $ruMap[$t];
        }
    }
    $expanded = array_unique(array_filter($expanded, fn($t) => strlen($t) >= 2));
    if (empty($expanded)) $expanded = $tokens;

    // Also add the full raw message for compound phrase matching
    $rawClean = preg_replace('/[^a-zA-Z0-9\s]/', ' ', strtolower($msg));
    $rawClean = preg_replace('/\s+/', ' ', trim($rawClean));
    $expanded[] = $rawClean;

    // Build keyword search (OR across problem, symptoms, causes, solution)
    $likes = [];
    $params = [];
    $types = '';
    foreach ($expanded as $t) {
        $likes[] = '(problem LIKE ? OR symptoms LIKE ? OR causes LIKE ? OR solution LIKE ?)';
        $like = "%{$t}%";
        $params = array_merge($params, [$like, $like, $like, $like]);
        $types .= 'ssss';
    }

    $sql = "SELECT id, system, problem, symptoms, causes, solution FROM kb_problems WHERE (" . implode(' OR ', $likes) . ") ORDER BY problem LIMIT 60";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Score results: problem title match weighted 3x higher
    foreach ($results as &$r) {
        $title = strtolower($r['problem']);
        $text = strtolower($r['problem'] . ' ' . $r['symptoms'] . ' ' . $r['causes'] . ' ' . $r['solution']);
        $titleHits = 0;
        $bodyHits = 0;
        foreach ($expanded as $t) {
            if (stripos($title, $t) !== false) $titleHits++;
            elseif (stripos($text, $t) !== false) $bodyHits++;
        }
        // Title matches are much more relevant
        $totalWeight = count($expanded) * 3; // 3x weight for title
        $r['score'] = ($titleHits * 3 + $bodyHits) / $totalWeight;
    }
    unset($r);

    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_filter($results, fn($r) => $r['score'] >= $minScore);
}

// ============================================================
// GET RELATED PRODUCTS from DB by system name
// ============================================================
function getRelatedProducts($system, $conn) {
    $categoryMap = [
        'Engine' => ['Engine', 'Filters', 'Oil'],
        'Transmission' => ['Transmission', 'Clutch'],
        'Brake' => ['Brake', 'Brakes'],
        'Suspension' => ['Suspension', 'Steering'],
        'Electrical' => ['Electrical', 'Battery', 'Lighting'],
        'Cooling' => ['Cooling', 'Radiator'],
        'AC' => ['AC', 'Air Conditioning'],
        'Fuel' => ['Fuel', 'Fuel System'],
        'Hybrid' => ['Hybrid', 'Electrical'],
        'EV' => ['EV', 'Electric', 'Battery'],
        'Sensors' => ['Sensors', 'Electrical']
    ];
    $cats = $categoryMap[$system] ?? [$system];

    $placeholders = [];
    $params = [];
    $types = '';
    foreach ($cats as $c) {
        $placeholders[] = 'category_name LIKE ?';
        $params[] = "%{$c}%";
        $types .= 's';
    }

    $sql = "SELECT p.product_name, p.price, p.discount_price, p.brand, p.stock
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.status = 'available' AND (" . implode(' OR ', $placeholders) . ")
            ORDER BY RAND() LIMIT 4";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $products;
}

// ============================================================
// FORMAT PRODUCT SECTION — REMOVED (replaced by renderProductSearch)
// ============================================================

// ============================================================
// FORMAT CURRENCY
// ============================================================
function formatCurrency($amount) {
    return 'Rs. ' . number_format((float)$amount, 0);
}

// ============================================================
// GET EMERGENCY / URGENT INSTRUCTIONS based on raw message
// Returns array of steps or null if not emergency
// ============================================================
function getEmergencyInstructions($rawMsg) {
    $msg = strtolower($rawMsg);

    // --- FLAT TIRE / TYRE ---
    if (preg_match('/(flat\s*(tyre|tire)|puncture|tyre.*fail|tire.*fail|tyre.*phat|tire.*phat|ban.*fail|ban.*puncture|wheel.*flat|tyre.*leak|tire.*leak)/i', $msg)) {
        return [
            'title'  => '&#128736; Flat Tyre Emergency — Kya Karein',
            'color'  => '#fd7e14',
            'steps'  => [
                '<strong>Immediately slow down</strong> aur side par le jaein — brake aahista press karein, steering mazbooti se pakrein',
                '<strong>Hazard lights ON</strong> karein — digger (triangle) road par 50m peeche rakhein',
                '<strong>Jack & wrench</strong> se wheel kholein — jack car frame ke niche rakhein (manual dekhein)',
                '<strong>Spare tyre</strong> lagaein — spare tyre ka air pressure check karein (30-35 PSI)',
                '<strong>80 km/h se zyada na chalaein</strong> — spare tyre temporary hai, repair shop jaein',
                '<strong>Nearby workshop</strong> par jaein — tyre puncture repair Rs.200-500, naya tyre Rs.3,000-15,000'
            ]
        ];
    }

    // --- BATTERY DEAD ---
    if (preg_match('/(battery.*dead|battery.*khatam|battery.*down|battery.*flat|gaadi.*start.*nahi|car.*start.*nahi|self.*nahi|starter.*nahi|ignition.*nahi|battery.*low|battery.*weak)/i', $msg)) {
        return [
            'title'  => '&#128267; Battery Dead Emergency — Kya Karein',
            'color'  => '#dc3545',
            'steps'  => [
                '<strong>Jump start</strong> karein — doosri gaadi se jumper cables lagaein (+ to +, - to -)',
                '<strong>Jumper cables nahi?</strong> — kisi se maang lein ya mechanic bulaein',
                '<strong>Headlights ON</strong> na rakhein — battery aur drain hoga',
                '<strong>AC, charger, music system</strong> band karein — sirf ignition ON karein',
                '<strong>Start karein</strong> — 5-10 second crank karein, agar start ho jae to 20 min chalaein',
                '<strong>Agar start na ho</strong> — mechanic bulaein ya towing service lein (Rs.500-2,000)',
                '<strong>Battery replace</strong> karein agar 3+ saal purana hai — Rs.4,000-12,000 depending on brand'
            ]
        ];
    }

    // --- LIGHTS NOT WORKING ---
    if (preg_match('/(light.*nahi|lights.*nahi|headlight.*nahi|tail.*light.*nahi|indicator.*nahi|dipper.*nahi|beam.*nahi|light.*fail|light.*band|light.*off|andhere.*chala)/i', $msg)) {
        return [
            'title'  => '&#128161; Lights Not Working — Kya Karein',
            'color'  => '#ffc107',
            'steps'  => [
                '<strong>Fuse check</strong> karein — fuse box dashboard ke neeche ya engine bay mein hai',
                '<strong>Bulb check</strong> karein — ek bulb dead ho sakta hai, doosra lagaein',
                '<strong>Battery voltage</strong> check karein — multimeter se 12.6V hona chahiye',
                '<strong>Agar headlight</strong> band hai to hazard lights ON karein aur side par jaein',
                '<strong>Night mein na chalaein</strong> bina headlight ke — police bhi rok sakti hai',
                '<strong>Workshop mein</strong> check karwaein — Rs.200-2,000 depending on issue'
            ]
        ];
    }

    // --- SMOKE / FIRE ---
    if (preg_match('/(smoke|dhuan|dhuan.*nikal|fire|aag|jal|burning.*smell|smell.*burn|overheat.*smoke|engine.*smoke|smoke.*nikal)/i', $msg)) {
        return [
            'title'  => '&#128293; Smoke / Fire Emergency — turant karein',
            'color'  => '#dc3545',
            'steps'  => [
                '<strong>Turant gaadi rokein</strong> — side par le jaein, engine BAND karein',
                '<strong>Hood mat kholein</strong> — agar dhuan aa raha hai to 5 min wait karein',
                '<strong>Fire extinguisher</strong> agar hai to use karein — engine bay mein spray karein',
                '<strong>Gaadi se door jaein</strong> — agar aag lagi hai to 50m door khade ho jaein',
                '<strong>115 (Fire Brigade)</strong> call karein agar aag badi hai',
                '<strong>Coolant leak</strong> check karein — agar coolant kam hai to overheating ho rahi hai',
                '<strong>Workshop tow</strong> karwaein — gaadi start mat karein jab tak check na ho'
            ]
        ];
    }

    // --- ACCIDENT ---
    if (preg_match('/(accident|hadsa|crash|takkar|hit.*car|car.*hit|gaadi.*lagi|collision)/i', $msg)) {
        return [
            'title'  => '&#128680; Accident Emergency — Kya Karein',
            'color'  => '#dc3545',
            'steps'  => [
                '<strong>Gaadi rokein</strong> — hazard lights ON karein, engine BAND karein',
                '<strong>Check karein</strong> — koi injured to nahi hai? Agar hai to 112 (ambulance) call karein',
                '<strong>Police ko call</strong> karein — 15 or nearest police station',
                '<strong>Photos lein</strong> — damage ki photos lein insurance ke liye',
                '<strong>Insurance company</strong> ko inform karein — 24 hours ke andar report karein',
                '<strong>Towing service</strong> lein — gaadi drive mat karein agar damage hai',
                '<strong>Workshop mein</strong> inspection karwaein — Rs.500-2,000 inspection fee'
            ]
        ];
    }

    // --- BRAKE FAILURE ---
    if (preg_match('/(brake.*fail|brake.*nahi|brake.*kaam.*nahi|brake.*soft|brake.*loose|brake.*gone|brake.*problem|brake.*noise|brake.*squeal|brake.*grind)/i', $msg)) {
        return [
            'title'  => '&#128694; Brake Emergency — turant karein',
            'color'  => '#dc3545',
            'steps'  => [
                '<strong>Handbrake use</strong> karein — agar brake pedal kaam na kare to handbrake gradually press karein',
                '<strong>Gear down</strong> karein — engine braking se gaadi slow hogi',
                '<strong>Hazard lights ON</strong> karein — peeche waali gaadiyon ko signal dein',
                '<strong>Side par le jaein</strong> — steering mazbooti se pakrein, aahista aahista slow karein',
                '<strong>Brake fluid check</strong> karein — agar kam hai to leak ho sakta hai',
                '<strong>Turant workshop</strong> jaein — brake repair Rs.2,000-15,000 depending on issue',
                '<strong>Agar bilkul brake nahi</strong> — towing service bulaein, gaadi mat chalaein'
            ]
        ];
    }

    // --- OVERHEATING ---
    if (preg_match('/(overheat|overheat.*hai|temperature.*high|temp.*gauge|pani.*kam|coolant.*low|engine.*hot|garam.*hai|gaadi.*garam)/i', $msg)) {
        return [
            'title'  => '&#127777; Overheating Emergency — Kya Karein',
            'color'  => '#dc3545',
            'steps'  => [
                '<strong>Turant AC band</strong> karein aur heater ON karein — engine se heat nikalega',
                '<strong>Gaadi slow</strong> karein aur side par le jaein — engine BAND karein',
                '<strong>Hood mat kholein</strong> — 15-20 min wait karein, steam bahut garam hoti hai',
                '<strong>Coolant level check</strong> karein — radiator mein pani ya coolant daalein (jab thanda ho)',
                '<strong>Water pump, thermostat, radiator</strong> check karwaein — leak ho sakta hai',
                '<strong>Workshop tow</strong> karwaein — engine damage ho sakta hai agar zyada der chalaya'
            ]
        ];
    }

    // --- CAR NOT STARTING / SELF ---
    if (preg_match('/(start.*nahi|self.*nahi|ignition.*nahi|crank.*nahi|dead.*car|gaadi.*band|engine.*start.*nahi|self.*problem|starter.*problem)/i', $msg)) {
        return [
            'title'  => '&#128260; Gaadi Start Nahi Ho Rahi — Kya Karein',
            'color'  => '#fd7e14',
            'steps'  => [
                '<strong>Battery check</strong> karein — headlights ON karein, agar dim hai to battery dead hai',
                '<strong>Jump start</strong> karein — doosri gaadi se ya booster pack se',
                '<strong>Fuel check</strong> karein — tank mein petrol/diesel hai?',
                '<strong>Key fob battery</strong> check karein — agar keyless start hai to remote ki battery change karein',
                '<strong>Neutral mein</strong> rakhein — automatic mein N ya P mein rakhein, manual mein clutch dabaein',
                '<strong>Agar koi awaaz</strong> aa rahi hai (click-click) to starter motor ka issue hai',
                '<strong>Mechanic bulaein</strong> — Rs.500-1,000 tow + diagnosis'
            ]
        ];
    }

    return null;
}

// ============================================================
// GET SYSTEM-SPECIFIC INSTRUCTIONS based on vehicle system + problem
// Returns HTML string for instruction section
// ============================================================
function getSystemInstructions($system, $rawMsg) {
    $msg = strtolower($rawMsg);
    $instructions = [];

    switch ($system) {
        case 'Engine':
            $instructions = [
                'title'  => '&#128161; What To Do Right Now',
                'color'  => '#dc3545',
                'steps'  => [
                    'Check engine light ON hai to OBD2 scanner se code scan karein (Rs.200-500 shops mein)',
                    'Engine oil level dipstick se check karein — kam hai to 1L daalein (Rs.600-1,200)',
                    'Coolant level radiator mein check karein — overflow tank mein mark dekhein',
                    'Unusual awaaz aa rahi hai to engine BAND karein aur mechanic bulaein',
                    'Smoke aa raha hai to gaadi side par le jaein — white (coolant), blue (oil), black (fuel)',
                    'Self nahi lag raha to battery check karein — headlights ON karein, dim hai to battery dead'
                ]
            ];
            if (preg_match('/(awaz|noise|knock|dak|raat|sound)/i', $msg)) {
                $instructions['steps'][] = 'Knocking sound hai to fuel grade check karein — low octane se knocking hoti hai';
                $instructions['steps'][] = 'Timing chain/belt issue ho sakta hai — belt change 80,000-100,000 km par';
            }
            break;

        case 'Brake':
            $instructions = [
                'title'  => '&#128694; Brake Safety Steps',
                'color'  => '#fd7e14',
                'steps'  => [
                    'Brake pedal soft hai to brake fluid check karein — reservoir dashboard ke neeche',
                    'Brake fluid kam hai to leak ho sakta hai — immediately workshop jaein',
                    'Squeal/grinding awaaz aa rahi hai to brake pads khatam ho sakti hain — Rs.2,000-8,000 change',
                    'Handbrake test karein — gaadi 20% slope par hold karna chahiye',
                    'ABS light ON hai to scanner se code check karein — ABS sensor issue ho sakta hai',
                    'Night mein agar brake soft lage to 100% emergency hai — gaadi mat chalaein'
                ]
            ];
            break;

        case 'Battery':
        case 'Electrical':
            $instructions = [
                'title'  => '&#9889; Electrical / Battery Steps',
                'color'  => '#0d6efd',
                'steps'  => [
                    'Battery terminals saaf karein — corrosion (white powder) hai to baking soda + paani se dho lein',
                    'Battery voltage check karein — multimeter se 12.6V+ hona chahiye, 12.2V se neeche = weak',
                    'Jump start karein agar battery dead hai — + to +, - to - (jumper cables)',
                    'Alternator check karein — gaadi chalte waqt voltage 13.5-14.5V hona chahiye',
                    'Fuses check karein — fuse box dashboard ya engine bay mein, visual check karein',
                    'Lights band karein battery bachane ke liye — headlights, AC, music system'
                ]
            ];
            if (preg_match('/(light|beam|dipper|indicator|flash)/i', $msg)) {
                $instructions['steps'][] = 'Headlight bulb check karein — cover kholein, bulb dekhein, jal gaya hai to change karein (Rs.200-800)';
                $instructions['steps'][] = 'Indicator flasher relay check karein — relay box mein se nikal kar check karein';
            }
            break;

        case 'Cooling':
            $instructions = [
                'title'  => '&#127777; Cooling System Steps',
                'color'  => '#0dcaf0',
                'steps'  => [
                    'Radiator mein coolant level check karein — overflow tank mein min/max mark dekhein',
                    'Paani daal sakta hai emergency mein — lekin baad mein coolant mix karein',
                    'Thermostat check karein — stuck thermostat se engine overheat hota hai',
                    'Radiator fan kaam kar raha hai check karein — AC ON karein, fan spin hona chahiye',
                    'Water pump leak check karein — neeche paani gir raha hai to pump kharab hai',
                    'Temperature gauge high ho to AC OFF + heater ON karein — heat door nikaalega'
                ]
            ];
            break;

        case 'Suspension':
            $instructions = [
                'title'  => '&#128694; Suspension Steps',
                'color'  => '#6f42c1',
                'steps'  => [
                    'Car push karke test karein — 2-3 baar bounce hona chahiye, zyada = shocks kharab',
                    'Unusual noise check karein — bump par clunk/knock = bushings ya links',
                    'Tyre wear check karein — uneven wear = alignment ya suspension issue',
                    'Steering wheel vibration check karein —高速 par vibration = wheel balancing',
                    'Ride height check karein — gaadi ek taraf jhuki hai to spring kharab hai',
                    'Workshop mein alignment + balancing karwaein — Rs.1,000-2,000'
                ]
            ];
            break;

        case 'AC':
            $instructions = [
                'title'  => '&#9729; AC System Steps',
                'color'  => '#20c997',
                'steps'  => [
                    'AC compressor ON hai check karein — click sound aani chahiye jab AC ON karein',
                    'Cabin filter check karein — ganda hai to change karein (Rs.500-1,500)',
                    'Coolant level (refrigerant) check karein — low hai to gas refill karwaein (Rs.2,000-5,000)',
                    'Condenser saaf karein — bugs/debris se block ho sakta hai',
                    'AC smell aa rahi hai to mold hai — anti-bacterial spray use karein',
                    'Compressor belt check karein — loose ya torn hai to change karein'
                ]
            ];
            break;

        case 'Fuel':
            $instructions = [
                'title'  => '&#9981; Fuel System Steps',
                'color'  => '#fd7e14',
                'steps'  => [
                    'Fuel filter check karein — clogged filter se power loss hota hai — change 20,000-30,000 km',
                    'Fuel pump sound check karein — key ON karein, fuel pump ka hum sound aana chahiye',
                    'Fuel injectors clean karwaein — carbon buildup se rough idle hota hai',
                    'Petrol quality check karein — kisi aur pump se fuel daalein',
                    'Fuel tank half se kam na rakhein — fuel pump ko cooling milti hai fuel se',
                    'Carburetor (purani gaadi) clean karwaein — Rs.1,000-3,000'
                ]
            ];
            break;

        case 'Transmission':
            $instructions = [
                'title'  => '&#9881; Transmission Steps',
                'color'  => '#6f42c1',
                'steps'  => [
                    'Transmission fluid level check karein — dipstick se, engine warm + running mein check karein',
                    'Fluid color dekhein — dark brown/black = change zaroori, pink/red = theek hai',
                    'Gear shifting smooth hai check karein — rough shifting = fluid ya clutch issue',
                    'Unusual noise check karein — whining/grinding = serious issue',
                    'Clutch pedal check karein (manual) — slipping ya high pedal = clutch wear',
                    'Transmission flush karwaein — 60,000-80,000 km par, Rs.3,000-6,000'
                ]
            ];
            break;

        case 'Hybrid':
        case 'EV':
            $instructions = [
                'title'  => '&#9889; Hybrid / EV Steps',
                'color'  => '#198754',
                'steps'  => [
                    'High voltage battery check karein — dashboard par SOC meter dekhein',
                    'Hybrid battery health check karwaein — diagnostic tool se capacity test',
                    'EV charging port check karein — damage ya loose connection na ho',
                    'Regenerative braking system check karein — pedal feel change hua hai to check karwaein',
                    '12V auxiliary battery check karein — hybrid mein bhi 12V battery hoti hai',
                    'Agar high voltage warning aaye to GAADI BAND karein aur authorized workshop jaein'
                ]
            ];
            break;

        case 'Sensors':
            $instructions = [
                'title'  => '&#128225; Sensor / OBD Steps',
                'color'  => '#0dcaf0',
                'steps'  => [
                    'OBD2 scanner se codes scan karein — Rs.200-500 shops mein ya khareid lein (Rs.1,500-3,000)',
                    'Code clear karein check karne ke liye — agar wapas aaye to permanent issue hai',
                    'MAF sensor saaf karein — MAF cleaner spray se (Rs.300-600)',
                    'O2 sensor check karein — fuel economy kharab hai to O2 sensor issue ho sakta hai',
                    'Throttle body clean karwaein — carbon buildup se rough idle hota hai',
                    'Wiring check karein — rodent damage ya loose connection na ho'
                ]
            ];
            break;
    }

    if (empty($instructions)) return '';

    $html = '<div class="mbot-section mbot-instructions" style="margin-top:14px;border-left:3px solid ' . $instructions['color'] . ';background:' . $instructions['color'] . '08;padding:12px 14px;border-radius:0 10px 10px 0;">';
    $html .= '<div class="mbot-section-title" style="color:' . $instructions['color'] . ';font-size:0.88rem;margin-bottom:8px;">' . $instructions['title'] . '</div>';
    $html .= '<ol style="margin:0;padding-left:20px;font-size:0.82rem;color:#444;line-height:2;">';
    foreach ($instructions['steps'] as $step) {
        $html .= '<li>' . $step . '</li>';
    }
    $html .= '</ol></div>';
    return $html;
}

// ============================================================
// RENDER INSTRUCTIONS + SUGGESTIONS CARD (standalone section)
// ============================================================
// ============================================================
// RENDER QUICK TIPS (short suggestions based on keywords)
// ============================================================
function renderQuickTips($rawMsg) {
    $msg = strtolower($rawMsg);
    $tips = [];

    if (preg_match('/(rain|barish|pani|wet|slip)/i', $msg)) {
        $tips[] = '&#127783;&#65039; Barish mein: Headlights ON, speed kam, braking distance double ho jati hai';
    }
    if (preg_match('/(night|raat|andhera|dark)/i', $msg)) {
        $tips[] = '&#127769; Raat mein: High beam mat chalaein city mein, fog lights use karein agar fog hai';
    }
    if (preg_match('/(highway|fast|speed|tez)/i', $msg)) {
        $tips[] = '&#128666; Highway par: Tyre pressure check karein (32-35 PSI), seatbelt lagaein, phone use na karein';
    }
    if (preg_match('/(winter|sardi|thand|cold|ice|frost)/i', $msg)) {
        $tips[] = '&#10052;&#65039; Sardi mein: Engine ko 2-3 min warm karein, coolant antifreeze mix hona chahiye';
    }
    if (preg_match('/(summer|garmi|tapish|hot|heat)/i', $msg)) {
        $tips[] = '&#127777;&#65039; Garmi mein: Coolant level check karein, tyre pressure kam ho jata hai heat mein';
    }

    if (empty($tips)) return '';
    $html = '<div style="margin-top:10px;padding:8px 12px;background:#f8f9fa;border-radius:10px;border:1px solid #e9ecef;">';
    $html .= '<div style="font-size:0.78rem;font-weight:600;color:#666;margin-bottom:4px;">&#128161; Quick Tips:</div>';
    foreach ($tips as $tip) {
        $html .= '<div style="font-size:0.78rem;color:#555;line-height:1.5;margin-bottom:2px;">' . $tip . '</div>';
    }
    $html .= '</div>';
    return $html;
}

// ============================================================
// ESTIMATE REPAIR COST from KB system
// ============================================================
function estimateRepairCostFromKB($system, $conn) {
    $costMap = [
        'Engine' => 'Rs. 5,000 - 50,000+',
        'Transmission' => 'Rs. 10,000 - 80,000+',
        'Brake' => 'Rs. 3,000 - 25,000',
        'Suspension' => 'Rs. 5,000 - 30,000',
        'Electrical' => 'Rs. 2,000 - 20,000',
        'Cooling' => 'Rs. 3,000 - 25,000',
        'AC' => 'Rs. 5,000 - 30,000',
        'Fuel' => 'Rs. 3,000 - 20,000',
        'Hybrid' => 'Rs. 10,000 - 50,000',
        'EV' => 'Rs. 15,000 - 100,000+',
        'Sensors' => 'Rs. 2,000 - 15,000'
    ];
    return $costMap[$system] ?? null;
}

// ============================================================
// FORMAT BULLET LIST from text
// ============================================================
function formatBulletList($text) {
    if (empty($text)) return "<li>No information available</li>";
    $lines = preg_split('/[\n;,]+/', trim($text));
    $resp = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strlen($line) > 2) {
            $line = preg_replace('/^[-*•]\s*/', '', $line);
            $resp .= '<li>' . htmlspecialchars($line) . '</li>';
        }
    }
    return $resp ?: '<li>' . htmlspecialchars($text) . '</li>';
}

// ============================================================
// BUILD STYLED DIAGNOSIS CARD — REMOVED (replaced by renderDiagnosisReport)
// ============================================================
?>
