<?php
$page_title = 'Knowledge Base';
require_once __DIR__ . '/../includes/config.php';
requireRole('management');

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'articles';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_article':
            $title = sanitize($_POST['title']);
            $category = sanitize($_POST['category']);
            $keywords = sanitize($_POST['keywords']);
            $content = trim($_POST['content']);
            if (!empty($title) && !empty($content)) {
                $stmt = $conn->prepare("INSERT INTO kb_articles (title, category, keywords, content) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $title, $category, $keywords, $content);
                $stmt->execute();
                $stmt->close();
                setFlash('success', 'Article added to knowledge base.');
            } else {
                setFlash('danger', 'Title and content are required.');
            }
            redirect(SITE_URL . '/management/knowledge-base.php?tab=articles');
            break;

        case 'delete_article':
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM kb_articles WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Article deleted.');
            redirect(SITE_URL . '/management/knowledge-base.php?tab=articles');
            break;

        case 'add_dtc':
            $code = strtoupper(sanitize($_POST['code']));
            $system = sanitize($_POST['system']);
            $description = sanitize($_POST['description']);
            $causes = trim($_POST['causes']);
            $fixes = trim($_POST['fixes']);
            if (!empty($code) && !empty($description)) {
                $stmt = $conn->prepare("INSERT INTO kb_dtc_codes (code, system, description, causes, fixes) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $code, $system, $description, $causes, $fixes);
                $stmt->execute();
                $stmt->close();
                setFlash('success', "DTC code $code added.");
            } else {
                setFlash('danger', 'Code and description are required.');
            }
            redirect(SITE_URL . '/management/knowledge-base.php?tab=dtc');
            break;

        case 'delete_dtc':
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM kb_dtc_codes WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'DTC code deleted.');
            redirect(SITE_URL . '/management/knowledge-base.php?tab=dtc');
            break;

        case 'add_faq':
            $question = sanitize($_POST['question']);
            $answer = trim($_POST['answer']);
            $category = sanitize($_POST['category']);
            if (!empty($question) && !empty($answer)) {
                $stmt = $conn->prepare("INSERT INTO kb_faqs (question, answer, category) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $question, $answer, $category);
                $stmt->execute();
                $stmt->close();
                setFlash('success', 'FAQ added.');
            } else {
                setFlash('danger', 'Question and answer are required.');
            }
            redirect(SITE_URL . '/management/knowledge-base.php?tab=faqs');
            break;

        case 'delete_faq':
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM kb_faqs WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'FAQ deleted.');
            redirect(SITE_URL . '/management/knowledge-base.php?tab=faqs');
            break;

        case 'add_problem':
            $system = sanitize($_POST['system']);
            $problem = sanitize($_POST['problem']);
            $symptoms = trim($_POST['symptoms']);
            $causes = trim($_POST['causes']);
            $solution = trim($_POST['solution']);
            if (!empty($system) && !empty($problem)) {
                $stmt = $conn->prepare("INSERT INTO kb_problems (system, problem, symptoms, causes, solution) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $system, $problem, $symptoms, $causes, $solution);
                $stmt->execute();
                $stmt->close();
                setFlash('success', 'Diagnostic problem added.');
            } else {
                setFlash('danger', 'System and problem are required.');
            }
            redirect(SITE_URL . '/management/knowledge-base.php?tab=problems');
            break;

        case 'delete_problem':
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM kb_problems WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            setFlash('success', 'Diagnostic problem deleted.');
            redirect(SITE_URL . '/management/knowledge-base.php?tab=problems');
            break;
    }
}

$articles = $conn->query("SELECT * FROM kb_articles ORDER BY category, title")->fetch_all(MYSQLI_ASSOC);
$dtcCodes = $conn->query("SELECT * FROM kb_dtc_codes ORDER BY code")->fetch_all(MYSQLI_ASSOC);
$faqs = $conn->query("SELECT * FROM kb_faqs ORDER BY category, id DESC")->fetch_all(MYSQLI_ASSOC);
$problems = $conn->query("SELECT * FROM kb_problems ORDER BY system, problem")->fetch_all(MYSQLI_ASSOC);

$search = isset($_GET['search']) ? strtolower(sanitize($_GET['search'])) : '';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.kb-wrap{max-width:1200px;margin:0 auto;padding:28px 16px 50px}
.kb-tabs{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap}
.kb-tab{padding:11px 22px;border-radius:12px;border:none;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .25s;font-family:'Inter',sans-serif;background:#f1f5f9;color:#666;display:inline-flex;align-items:center;gap:8px}
.kb-tab.active{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;box-shadow:0 4px 12px rgba(102,126,234,.3)}
.kb-tab .cnt{background:rgba(0,0,0,.08);border-radius:20px;padding:2px 10px;font-size:.75rem}
.kb-tab.active .cnt{background:rgba(255,255,255,.25)}
.kb-card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.04);overflow:hidden;margin-bottom:22px}
.kb-card-h{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9;background:linear-gradient(180deg,#fafbfc 0%,#fff 100%)}
.kb-card-h h5{margin:0;font-size:1rem;font-weight:700;color:#1a1a2e;display:flex;align-items:center;gap:10px}
.kb-card-h h5 i{background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.kb-card-b{padding:20px 24px}
.kb-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.kb-item{padding:14px 0;border-bottom:1px solid #f5f5f5}
.kb-item:last-child{border-bottom:none}
.kb-item-title{font-weight:700;font-size:.9rem;color:#1a1a2e;display:flex;align-items:center;gap:8px;justify-content:space-between}
.kb-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.kb-badge-guide{background:#ede9fe;color:#7c3aed}
.kb-badge-interval{background:#d1fae5;color:#059669}
.kb-badge-torque{background:#fef3c7;color:#d97706}
.kb-badge-general{background:#e2e8f0;color:#475569}
.kb-badge-dtc{background:#fee2e2;color:#dc2626;font-family:monospace}
.kb-badge-faq{background:#dbeafe;color:#2563eb}
.kb-item-content{font-size:.82rem;color:#666;margin-top:6px;max-height:64px;overflow:hidden;line-height:1.5}
.kb-item-meta{font-size:.75rem;color:#aaa;margin-top:6px}
.kb-del{border:none;background:rgba(220,38,38,.08);color:#dc2626;width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:.8rem;transition:all .2s}
.kb-del:hover{background:#dc2626;color:#fff}
.kb-form input,.kb-form textarea,.kb-form select{width:100%;padding:11px 14px;border:1.5px solid #e0e0e0;border-radius:12px;font-size:.85rem;font-family:'Inter',sans-serif;transition:all .25s;margin-bottom:10px}
.kb-form input:focus,.kb-form textarea:focus,.kb-form select:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);outline:none}
.kb-form textarea{min-height:90px;resize:vertical}
.kb-btn{padding:11px 22px;border-radius:12px;border:none;font-size:.85rem;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;box-shadow:0 4px 12px rgba(102,126,234,.25);transition:all .25s}
.kb-btn:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(102,126,234,.35)}
.kb-empty{text-align:center;padding:30px 20px;color:#bbb}
@media(max-width:768px){.kb-grid2{grid-template-columns:1fr}}
</style>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/management-sidebar.php'; ?>
    <div class="admin-main">
        <div class="kb-wrap">

            <div class="admin-header" style="margin-bottom:24px;">
                <div>
                    <h3 class="admin-page-title"><i class="fas fa-book me-2"></i>Knowledge Base (RAG)</h3>
                    <p class="admin-page-subtitle">These manuals, DTC codes and FAQs power MechBot's AI answers (Retrieval-Augmented Generation)</p>
                </div>
            </div>

            <div class="kb-tabs">
                <button class="kb-tab <?php echo $tab === 'articles' ? 'active' : ''; ?>" onclick="location.href='knowledge-base.php?tab=articles'"><i class="fas fa-book-open"></i> Guides & Manuals <span class="cnt"><?php echo count($articles); ?></span></button>
                <button class="kb-tab <?php echo $tab === 'dtc' ? 'active' : ''; ?>" onclick="location.href='knowledge-base.php?tab=dtc'"><i class="fas fa-code"></i> DTC Codes <span class="cnt"><?php echo count($dtcCodes); ?></span></button>
                <button class="kb-tab <?php echo $tab === 'problems' ? 'active' : ''; ?>" onclick="location.href='knowledge-base.php?tab=problems'"><i class="fas fa-stethoscope"></i> Problems <span class="cnt"><?php echo count($problems); ?></span></button>
                <button class="kb-tab <?php echo $tab === 'faqs' ? 'active' : ''; ?>" onclick="location.href='knowledge-base.php?tab=faqs'"><i class="fas fa-question-circle"></i> FAQs <span class="cnt"><?php echo count($faqs); ?></span></button>
                <a href="chatbot-config.php" class="kb-tab" style="text-decoration:none;margin-left:auto;"><i class="fas fa-robot"></i> Chatbot Config</a>
            </div>

            <?php if ($tab === 'articles'): ?>
                <div class="kb-card">
                    <div class="kb-card-h"><h5><i class="fas fa-plus-circle"></i> Add Repair Guide / Service Interval / Torque Spec</h5></div>
                    <div class="kb-card-b">
                        <form method="POST" class="kb-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="add_article">
                            <div class="kb-grid2">
                                <input type="text" name="title" placeholder="Title (e.g. How to Change Engine Oil)" required>
                                <div style="display:flex;gap:10px;">
                                    <select name="category" style="margin-bottom:0;">
                                        <option value="repair_guide">Repair Guide</option>
                                        <option value="service_interval">Service Interval</option>
                                        <option value="torque_spec">Torque Spec</option>
                                        <option value="general">General</option>
                                    </select>
                                    <input type="text" name="keywords" placeholder="Keywords (comma separated)" style="margin-bottom:0;">
                                </div>
                            </div>
                            <textarea name="content" placeholder="Full guide content..." required></textarea>
                            <button type="submit" class="kb-btn"><i class="fas fa-save me-1"></i>Add Article</button>
                        </form>
                    </div>
                </div>

                <div class="kb-card">
                    <div class="kb-card-h"><h5><i class="fas fa-book-open"></i> Articles (<?php echo count($articles); ?>)</h5></div>
                    <div class="kb-card-b">
                        <?php if (empty($articles)): ?>
                            <div class="kb-empty"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No articles yet</div>
                        <?php else: ?>
                            <?php foreach ($articles as $a): ?>
                                <div class="kb-item">
                                    <div class="kb-item-title">
                                        <span><?php echo htmlspecialchars($a['title']); ?> <span class="kb-badge kb-badge-<?php echo str_replace('_', '-', $a['category']); ?>"><?php echo str_replace('_', ' ', $a['category']); ?></span></span>
                                        <form method="POST" onsubmit="return confirm('Delete this article?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="delete_article">
                                            <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
                                            <button type="submit" class="kb-del" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                    <div class="kb-item-content"><?php echo htmlspecialchars(mb_substr($a['content'], 0, 300)); ?>...</div>
                                    <div class="kb-item-meta">Keywords: <?php echo htmlspecialchars($a['keywords'] ?: 'none'); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($tab === 'dtc'): ?>
                <div class="kb-card">
                    <div class="kb-card-h"><h5><i class="fas fa-plus-circle"></i> Add DTC / OBD-II Code</h5></div>
                    <div class="kb-card-b">
                        <form method="POST" class="kb-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="add_dtc">
                            <div class="kb-grid2">
                                <input type="text" name="code" placeholder="Code (e.g. P0301)" required style="text-transform:uppercase;font-family:monospace;">
                                <input type="text" name="system" placeholder="System (e.g. Engine, Fuel, ABS)" required>
                            </div>
                            <input type="text" name="description" placeholder="Description (e.g. Cylinder 1 Misfire Detected)" required>
                            <textarea name="causes" placeholder="Common causes..."></textarea>
                            <textarea name="fixes" placeholder="How to fix / diagnose..."></textarea>
                            <button type="submit" class="kb-btn"><i class="fas fa-save me-1"></i>Add DTC Code</button>
                        </form>
                    </div>
                </div>

                <div class="kb-card">
                    <div class="kb-card-h"><h5><i class="fas fa-code"></i> DTC Codes (<?php echo count($dtcCodes); ?>)</h5></div>
                    <div class="kb-card-b">
                        <?php if (empty($dtcCodes)): ?>
                            <div class="kb-empty"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No DTC codes yet</div>
                        <?php else: ?>
                            <?php foreach ($dtcCodes as $d): ?>
                                <div class="kb-item">
                                    <div class="kb-item-title">
                                        <span><span class="kb-badge kb-badge-dtc"><?php echo htmlspecialchars($d['code']); ?></span> <?php echo htmlspecialchars($d['description']); ?> <span class="kb-badge kb-badge-general"><?php echo htmlspecialchars($d['system']); ?></span></span>
                                        <form method="POST" onsubmit="return confirm('Delete this DTC code?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="delete_dtc">
                                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                            <button type="submit" class="kb-del" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                    <div class="kb-item-content"><strong>Causes:</strong> <?php echo htmlspecialchars($d['causes']); ?><br><strong>Fixes:</strong> <?php echo htmlspecialchars($d['fixes']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($tab === 'problems'): ?>
                <div class="kb-card">
                    <div class="kb-card-h"><h5><i class="fas fa-plus-circle"></i> Add Diagnostic Problem</h5></div>
                    <div class="kb-card-b">
                        <form method="POST" class="kb-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="add_problem">
                            <div class="kb-grid2">
                                <input type="text" name="system" placeholder="System (e.g. Engine, Brake, AC, EV)" required list="systemList">
                                <datalist id="systemList">
                                    <option value="Engine"></option><option value="Transmission"></option><option value="Brake"></option><option value="Suspension"></option><option value="Electrical"></option><option value="Cooling"></option><option value="AC"></option><option value="Fuel"></option><option value="Hybrid"></option><option value="EV"></option><option value="Sensors"></option>
                                </datalist>
                                <input type="text" name="problem" placeholder="Problem (e.g. Engine Won't Start)" required>
                            </div>
                            <textarea name="symptoms" placeholder="Symptoms..."></textarea>
                            <textarea name="causes" placeholder="Possible causes..."></textarea>
                            <textarea name="solution" placeholder="Solution / how to fix..."></textarea>
                            <button type="submit" class="kb-btn"><i class="fas fa-save me-1"></i>Add Problem</button>
                        </form>
                    </div>
                </div>

                <div class="kb-card">
                    <div class="kb-card-h"><h5><i class="fas fa-stethoscope"></i> Diagnostic Problems (<?php echo count($problems); ?>) - powers MechBot's expert diagnosis</h5></div>
                    <div class="kb-card-b">
                        <?php if (empty($problems)): ?>
                            <div class="kb-empty"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No problems yet</div>
                        <?php else: ?>
                            <?php foreach ($problems as $pr): ?>
                                <div class="kb-item">
                                    <div class="kb-item-title">
                                        <span><span class="kb-badge kb-badge-dtc"><?php echo htmlspecialchars($pr['system']); ?></span> <?php echo htmlspecialchars($pr['problem']); ?></span>
                                        <form method="POST" onsubmit="return confirm('Delete this diagnostic problem?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="delete_problem">
                                            <input type="hidden" name="id" value="<?php echo $pr['id']; ?>">
                                            <button type="submit" class="kb-del" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                    <div class="kb-item-content"><strong>Symptoms:</strong> <?php echo htmlspecialchars($pr['symptoms']); ?><br><strong>Causes:</strong> <?php echo htmlspecialchars($pr['causes']); ?><br><strong>Solution:</strong> <?php echo htmlspecialchars($pr['solution']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <div class="kb-card">
                    <div class="kb-card-h"><h5><i class="fas fa-plus-circle"></i> Add FAQ</h5></div>
                    <div class="kb-card-b">
                        <form method="POST" class="kb-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="add_faq">
                            <div class="kb-grid2">
                                <input type="text" name="question" placeholder="Question (e.g. What is the return policy?)" required>
                                <select name="category">
                                    <option value="general">General</option>
                                    <option value="policies">Policies</option>
                                    <option value="orders">Orders</option>
                                    <option value="services">Services</option>
                                </select>
                            </div>
                            <textarea name="answer" placeholder="Answer..." required></textarea>
                            <button type="submit" class="kb-btn"><i class="fas fa-save me-1"></i>Add FAQ</button>
                        </form>
                    </div>
                </div>

                <div class="kb-card">
                    <div class="kb-card-h"><h5><i class="fas fa-question-circle"></i> FAQs (<?php echo count($faqs); ?>)</h5></div>
                    <div class="kb-card-b">
                        <?php if (empty($faqs)): ?>
                            <div class="kb-empty"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No FAQs yet</div>
                        <?php else: ?>
                            <?php foreach ($faqs as $f): ?>
                                <div class="kb-item">
                                    <div class="kb-item-title">
                                        <span><span class="kb-badge kb-badge-faq"><?php echo htmlspecialchars($f['category']); ?></span> <?php echo htmlspecialchars($f['question']); ?></span>
                                        <form method="POST" onsubmit="return confirm('Delete this FAQ?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="delete_faq">
                                            <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                            <button type="submit" class="kb-del" title="Delete"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                    <div class="kb-item-content"><?php echo htmlspecialchars(mb_substr($f['answer'], 0, 200)); ?>...</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
