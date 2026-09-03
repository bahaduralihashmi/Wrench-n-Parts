<?php
$page_title = 'Chatbot Configuration';
require_once __DIR__ . '/../includes/config.php';
requireRole('management');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_chatbot':
                $currentStatus = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'chatbot_enabled'")->fetch_assoc();
                $newStatus = ($currentStatus && $currentStatus['setting_value'] == '1') ? '0' : '1';
                $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'chatbot_enabled'");
                $stmt->bind_param("s", $newStatus);
                $stmt->execute();
                $stmt->close();
                setFlash('success', 'Chatbot ' . ($newStatus == '1' ? 'enabled' : 'disabled') . ' successfully.');
                redirect(SITE_URL . '/management/chatbot-config.php');
                break;

            case 'update_name':
                $botName = sanitize($_POST['chatbot_name']);
                if (!empty($botName)) {
                    $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'chatbot_name'");
                    $stmt->bind_param("s", $botName);
                    $stmt->execute();
                    $stmt->close();
                    setFlash('success', 'Chatbot name updated to "' . htmlspecialchars($botName) . '" successfully.');
                } else {
                    setFlash('danger', 'Chatbot name cannot be empty.');
                }
                redirect(SITE_URL . '/management/chatbot-config.php');
                break;

            case 'update_gemini':
                $geminiKey = trim($_POST['gemini_api_key'] ?? '');
                $geminiModel = sanitize($_POST['gemini_model'] ?? 'gemini-2.5-flash');
                if (empty($geminiKey)) {
                    setFlash('danger', 'Gemini API key cannot be empty. Get a free key from <a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a>.');
                    redirect(SITE_URL . '/management/chatbot-config.php');
                    break;
                }
                foreach (['gemini_api_key' => $geminiKey, 'gemini_model' => $geminiModel] as $key => $value) {
                    $check = $conn->prepare("SELECT COUNT(*) as cnt FROM system_settings WHERE setting_key = ?");
                    $check->bind_param("s", $key);
                    $check->execute();
                    $exists = $check->get_result()->fetch_assoc()['cnt'] > 0;
                    $check->close();
                    if ($exists) {
                        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
                        $stmt->bind_param("ss", $value, $key);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
                        $stmt->bind_param("ss", $key, $value);
                    }
                    $stmt->execute();
                    $stmt->close();
                }
                setFlash('success', 'Gemini AI settings saved. MechBot now uses the AI mechanic model!');
                redirect(SITE_URL . '/management/chatbot-config.php');
                break;

            case 'delete_old_logs':
                $days = isset($_POST['days']) ? (int)$_POST['days'] : 30;
                if ($days < 1) $days = 30;
                $stmt = $conn->prepare("DELETE FROM chatbot_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->bind_param("i", $days);
                $stmt->execute();
                $deletedCount = $stmt->affected_rows;
                $stmt->close();
                setFlash('success', "Deleted $deletedCount chatbot logs older than $days days.");
                redirect(SITE_URL . '/management/chatbot-config.php');
                break;
        }
    }
}

$chatbotEnabled = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'chatbot_enabled'")->fetch_assoc();
$chatbotName = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'chatbot_name'")->fetch_assoc();
$geminiKeyRow = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'gemini_api_key'")->fetch_assoc();
$geminiModelRow = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'gemini_model'")->fetch_assoc();
$isEnabled = $chatbotEnabled && $chatbotEnabled['setting_value'] == '1';
$botName = $chatbotName ? $chatbotName['setting_value'] : 'MechBot';
$geminiKey = $geminiKeyRow ? $geminiKeyRow['setting_value'] : '';
$geminiModel = $geminiModelRow ? $geminiModelRow['setting_value'] : 'gemini-flash-latest';
$aiConfigured = !empty($geminiKey);

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$whereClause = '';
$params = [];
$types = '';
if (!empty($search)) {
    $whereClause = "WHERE question LIKE ? OR response LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types = "ss";
}

$countQuery = "SELECT COUNT(*) as cnt FROM chatbot_logs $whereClause";
$countStmt = $conn->prepare($countQuery);
if (!empty($types)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalLogs = $countStmt->get_result()->fetch_assoc()['cnt'];
$countStmt->close();
$totalPages = max(1, ceil($totalLogs / $perPage));

$query = "SELECT cl.*, u.name as user_name, u.email as user_email 
          FROM chatbot_logs cl 
          LEFT JOIN users u ON cl.user_id = u.user_id 
          $whereClause 
          ORDER BY cl.created_at DESC 
          LIMIT ? OFFSET ?";
$logStmt = $conn->prepare($query);
$logTypes = $types . "ii";
$logParams = array_merge($params, [$perPage, $offset]);
$logStmt->bind_param($logTypes, ...$logParams);
$logStmt->execute();
$chatbotLogs = $logStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$logStmt->close();

$topQuestions = $conn->query("SELECT question, COUNT(*) as ask_count FROM chatbot_logs GROUP BY question ORDER BY ask_count DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
$totalQueries = $conn->query("SELECT COUNT(*) as cnt FROM chatbot_logs")->fetch_assoc()['cnt'];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.mgmt-wrap{max-width:1200px;margin:0 auto;padding:28px 16px 50px}
.mg-cfg-top{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin-bottom:28px}
.mg-cfg-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.04);padding:28px;text-align:center;position:relative;overflow:hidden;transition:transform .25s,box-shadow .25s}
.mg-cfg-card:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(0,0,0,.08)}
.mg-cfg-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.mg-cfg-card:nth-child(1)::after{background:linear-gradient(90deg,#059669,#34d399)}
.mg-cfg-card:nth-child(2)::after{background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.mg-cfg-card:nth-child(3)::after{background:linear-gradient(90deg,#667eea,#764ba2)}
.mg-cfg-icon{width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:0 auto 14px}
.mg-cfg-card h5{font-size:.95rem;font-weight:700;color:#1a1a2e;margin:0 0 6px}
.mg-cfg-card p{font-size:.85rem;color:#888;margin:0 0 16px}

.mg-toggle{position:relative;width:60px;height:32px;display:inline-block}
.mg-toggle input{opacity:0;width:0;height:0}
.mg-toggle .slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#e2e8f0;border-radius:32px;transition:.3s}
.mg-toggle .slider::before{content:'';position:absolute;height:26px;width:26px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 2px 4px rgba(0,0,0,.1)}
.mg-toggle input:checked + .slider{background:linear-gradient(135deg,#059669,#34d399)}
.mg-toggle input:checked + .slider::before{transform:translateX(28px)}

.mg-status-on{display:inline-block;padding:5px 16px;border-radius:20px;font-size:.78rem;font-weight:700;background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#059669}
.mg-status-off{display:inline-block;padding:5px 16px;border-radius:20px;font-size:.78rem;font-weight:700;background:linear-gradient(135deg,#fee2e2,#fecaca);color:#dc2626}

.mg-cfg-input-group{display:flex;gap:8px}
.mg-cfg-input{flex:1;padding:11px 16px;border:1.5px solid #e0e0e0;border-radius:12px;font-size:.88rem;font-family:'Inter',sans-serif;transition:all .25s}
.mg-cfg-input:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);outline:none}
.mg-btn-sm{padding:11px 20px;border-radius:12px;border:none;font-size:.85rem;font-weight:700;cursor:pointer;transition:all .3s;display:inline-flex;align-items:center;gap:6px;font-family:'Inter',sans-serif}
.mg-btn-save{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;box-shadow:0 4px 12px rgba(102,126,234,.25)}
.mg-btn-save:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(102,126,234,.35)}
.mg-btn-danger{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;box-shadow:0 4px 12px rgba(220,38,38,.25)}
.mg-btn-danger:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(220,38,38,.35)}

.mg-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.04);overflow:hidden;margin-bottom:22px}
.mg-card-h{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(180deg,#fafbfc 0%,#fff 100%)}
.mg-card-h h5{margin:0;font-size:1rem;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:10px}
.mg-card-h h5 i{background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.mg-card-b{padding:20px 24px}

.mg-search-bar{display:flex;gap:10px;margin-bottom:20px}
.mg-search-bar input{flex:1;padding:12px 16px;border:1.5px solid #e0e0e0;border-radius:12px;font-size:.88rem;font-family:'Inter',sans-serif;transition:all .25s}
.mg-search-bar input:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);outline:none}

.mg-log-item{padding:14px 0;border-bottom:1px solid #f5f5f5;transition:background .15s}
.mg-log-item:last-child{border-bottom:none}
.mg-log-item:hover{background:rgba(102,126,234,.02);margin:0 -24px;padding:14px 24px}
.mg-log-user{font-weight:700;font-size:.88rem;color:#1a1a2e}
.mg-log-email{font-size:.75rem;color:#999}
.mg-log-question{font-size:.88rem;color:#333;margin-top:6px;padding:8px 12px;background:#f8f9fa;border-radius:8px;border-left:3px solid #667eea}
.mg-log-response{font-size:.82rem;color:#666;margin-top:6px;padding:8px 12px;background:#fafbfc;border-radius:8px}
.mg-log-time{font-size:.75rem;color:#aaa;margin-top:4px}

.mg-rank{width:28px;height:28px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#888;flex-shrink:0}

.mg-empty{text-align:center;padding:40px 20px;color:#bbb}
.mg-empty i{font-size:2.5rem;margin-bottom:10px;display:block}

@media(max-width:768px){.mg-cfg-top{grid-template-columns:1fr}.mg-search-bar{flex-direction:column}}
</style>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/management-sidebar.php'; ?>
    <div class="admin-main">
        <div class="mgmt-wrap">

            <div class="admin-header" style="margin-bottom:24px;">
                <div>
                    <h3 class="admin-page-title"><i class="fas fa-robot me-2"></i>Chatbot Configuration</h3>
                    <p class="admin-page-subtitle">Manage your MechBot assistant settings and logs</p>
                </div>
            </div>

            <div class="mg-cfg-top">
                <!-- Status -->
                <div class="mg-cfg-card">
                    <div class="mg-cfg-icon" style="background:<?php echo $isEnabled ? 'linear-gradient(135deg,#d1fae5,#a7f3d0)' : 'linear-gradient(135deg,#fee2e2,#fecaca)'; ?>;color:<?php echo $isEnabled ? '#059669' : '#dc2626'; ?>;"><i class="fas fa-robot"></i></div>
                    <h5>Chatbot Status</h5>
                    <p><?php echo $isEnabled ? '<span class="mg-status-on"><i class="fas fa-check me-1"></i>Enabled</span>' : '<span class="mg-status-off"><i class="fas fa-times me-1"></i>Disabled</span>'; ?></p>
                    <form method="POST" style="display:flex;justify-content:center;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="toggle_chatbot">
                        <button type="submit" class="mg-btn-sm" style="background:<?php echo $isEnabled ? 'linear-gradient(135deg,#dc2626,#ef4444)' : 'linear-gradient(135deg,#059669,#34d399)'; ?>;color:#fff;padding:12px 24px;">
                            <i class="fas fa-<?php echo $isEnabled ? 'power-off' : 'power-off'; ?> me-1"></i><?php echo $isEnabled ? 'Disable' : 'Enable'; ?>
                        </button>
                    </form>
                </div>

                <!-- Bot Name -->
                <div class="mg-cfg-card">
                    <div class="mg-cfg-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#d97706;"><i class="fas fa-tag"></i></div>
                    <h5>Bot Name</h5>
                    <p>Current: <strong style="color:#1a1a2e;"><?php echo htmlspecialchars($botName); ?></strong></p>
                    <form method="POST" style="width:100%;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_name">
                        <div class="mg-cfg-input-group">
                            <input type="text" class="mg-cfg-input" name="chatbot_name" value="<?php echo htmlspecialchars($botName); ?>" required>
                            <button type="submit" class="mg-btn-sm mg-btn-save"><i class="fas fa-save"></i></button>
                        </div>
                    </form>
                </div>

                <!-- Gemini AI -->
                <div class="mg-cfg-card">
                    <div class="mg-cfg-icon" style="background:<?php echo $aiConfigured ? 'linear-gradient(135deg,#d1fae5,#a7f3d0)' : 'linear-gradient(135deg,#fee2e2,#fecaca)'; ?>;color:<?php echo $aiConfigured ? '#059669' : '#dc2626'; ?>;"><i class="fas fa-brain"></i></div>
                    <h5>Gemini AI Mechanic</h5>
                    <p><?php echo $aiConfigured ? '<span class="mg-status-on"><i class="fas fa-check me-1"></i>Connected</span>' : '<span class="mg-status-off"><i class="fas fa-times me-1"></i>Not Configured</span>'; ?></p>
                    <form method="POST" style="width:100%;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="update_gemini">
                        <div class="mg-cfg-input-group" style="margin-bottom:8px;">
                            <input type="password" class="mg-cfg-input" name="gemini_api_key" placeholder="Free API key from aistudio.google.com/apikey" value="<?php echo htmlspecialchars($geminiKey); ?>" required>
                        </div>
                        <div class="mg-cfg-input-group" style="margin-bottom:8px;">
                            <input type="text" class="mg-cfg-input" name="gemini_model" placeholder="Model" value="<?php echo htmlspecialchars($geminiModel); ?>" required>
                        </div>
                        <button type="submit" class="mg-btn-sm mg-btn-save" style="width:100%;justify-content:center;"><i class="fas fa-save"></i>Save AI Settings</button>
                    </form>
                    <small style="display:block;margin-top:10px;color:#aaa;font-size:.72rem;">Default: gemini-3.5-flash (free tier, auto-fallback to Flash-Lite on quota). Get key at <a href="https://aistudio.google.com/apikey" target="_blank" style="color:#667eea;">aistudio.google.com/apikey</a></small>
                </div>

                <!-- Total Queries -->
                <div class="mg-cfg-card">
                    <div class="mg-cfg-icon" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);color:#7c3aed;"><i class="fas fa-comments"></i></div>
                    <h5>Total Queries</h5>
                    <p><strong style="font-size:1.5rem;color:#7c3aed;"><?php echo number_format($totalQueries); ?></strong></p>
                    <small style="color:#aaa;">logged conversations</small>
                </div>
            </div>

            <!-- Frequent Questions -->
            <div class="mg-card">
                <div class="mg-card-h"><h5><i class="fas fa-fire me-2"></i>Most Frequent Questions</h5></div>
                <div class="mg-card-b">
                    <?php if (empty($topQuestions)): ?>
                        <div class="mg-empty"><i class="fas fa-question-circle"></i><p>No questions logged yet.</p></div>
                    <?php else: ?>
                        <?php foreach ($topQuestions as $idx => $tq): ?>
                            <div style="display:flex;align-items:center;gap:12px;padding:12px 0;<?php echo $idx < count($topQuestions)-1 ? 'border-bottom:1px solid #f5f5f5;' : ''; ?>">
                                <span class="mg-rank"><?php echo $idx+1; ?></span>
                                <span style="flex:1;font-size:.9rem;color:#333;"><?php echo htmlspecialchars($tq['question']); ?></span>
                                <span style="font-weight:800;font-size:.9rem;color:#667eea;background:linear-gradient(135deg,#ede9fe,#ddd6fe);padding:4px 14px;border-radius:20px;"><?php echo number_format($tq['ask_count']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Delete Old Logs -->
            <div class="mg-card">
                <div class="mg-card-h"><h5><i class="fas fa-trash-alt me-2"></i>Maintenance</h5></div>
                <div class="mg-card-b">
                    <form method="POST" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="delete_old_logs">
                        <div style="flex:1;min-width:200px;">
                            <label style="display:block;font-size:.82rem;font-weight:700;color:#555;margin-bottom:6px;">Delete logs older than (days)</label>
                            <input type="number" class="mg-cfg-input" name="days" value="30" min="1" required style="width:100%;">
                        </div>
                        <button type="submit" class="mg-btn-sm mg-btn-danger" onclick="return confirm('This will permanently delete old chatbot logs. Continue?');" style="padding:12px 24px;">
                            <i class="fas fa-trash me-1"></i>Delete Old Logs
                        </button>
                    </form>
                </div>
            </div>

            <!-- Logs -->
            <div class="mg-card">
                <div class="mg-card-h"><h5><i class="fas fa-list me-2"></i>Chatbot Logs (<?php echo number_format($totalLogs); ?>)</h5></div>
                <div class="mg-card-b">
                    <form method="GET" class="mg-search-bar">
                        <input type="text" name="search" placeholder="Search questions or responses..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="mg-btn-sm mg-btn-save"><i class="fas fa-search me-1"></i>Search</button>
                        <a href="chatbot-config.php" class="mg-btn-sm" style="background:#f5f5f5;color:#777;border:1.5px solid #e8e8e8;"><i class="fas fa-redo me-1"></i>Reset</a>
                    </form>

                    <?php if (empty($chatbotLogs)): ?>
                        <div class="mg-empty"><i class="fas fa-inbox"></i><p>No chatbot logs found.</p></div>
                    <?php else: ?>
                        <?php foreach ($chatbotLogs as $log): ?>
                            <div class="mg-log-item">
                                <div style="display:flex;align-items:center;justify-content:space-between;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <?php if ($log['user_name']): ?>
                                            <div style="width:32px;height:32px;border-radius:10px;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:700;"><?php echo strtoupper(substr($log['user_name'], 0, 1)); ?></div>
                                            <div>
                                                <div class="mg-log-user"><?php echo htmlspecialchars($log['user_name']); ?></div>
                                                <div class="mg-log-email"><?php echo htmlspecialchars($log['user_email']); ?></div>
                                            </div>
                                        <?php else: ?>
                                            <div style="width:32px;height:32px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:.75rem;font-weight:700;">G</div>
                                            <div class="mg-log-user" style="color:#94a3b8;">Guest</div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="mg-log-time"><?php echo timeAgo($log['created_at']); ?></span>
                                </div>
                                <div class="mg-log-question"><?php echo htmlspecialchars($log['question']); ?></div>
                                <div class="mg-log-response"><?php echo htmlspecialchars(substr($log['response'], 0, 120)) . (strlen($log['response']) > 120 ? '...' : ''); ?></div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($totalPages > 1): ?>
                            <div style="display:flex;justify-content:center;gap:6px;margin-top:20px;flex-wrap:wrap;">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" style="padding:8px 14px;border-radius:10px;background:#fff;border:1.5px solid #e8e8e8;text-decoration:none;color:#666;font-size:.85rem;font-weight:600;">&laquo;</a>
                                <?php endif; ?>
                                <?php
                                $sp = max(1, $page - 2);
                                $ep = min($totalPages, $page + 2);
                                for ($i = $sp; $i <= $ep; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" style="padding:8px 14px;border-radius:10px;text-decoration:none;font-size:.85rem;font-weight:600;<?php echo $i === $page ? 'background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:1px solid transparent;box-shadow:0 4px 12px rgba(102,126,234,.3);' : 'background:#fff;border:1.5px solid #e8e8e8;color:#666;'; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" style="padding:8px 14px;border-radius:10px;background:#fff;border:1.5px solid #e8e8e8;text-decoration:none;color:#666;font-size:.85rem;font-weight:600;">&raquo;</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
