<?php
$page_title = 'Chatbot Feedback Review';
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!in_array($current_user['role'], ['admin', 'management'])) {
    setFlash('danger', 'Access denied.');
    redirect(SITE_URL . '/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'approve' && $id) {
        $conn->query("UPDATE chatbot_feedback SET admin_action = 'approved', admin_reviewed = 1 WHERE id = $id");
        // Optionally add to knowledge base
        $row = $conn->query("SELECT * FROM chatbot_feedback WHERE id = $id")->fetch_assoc();
        if ($row) {
            $conn->query("INSERT INTO kb_pending_review (source_type, source_id, problem, solution, status) VALUES ('user_feedback', $id, '" . $conn->real_escape_string($row['message_sent']) . "', '" . $conn->real_escape_string($row['response_given']) . "', 'pending')");
        }
        setFlash('success', 'Feedback approved and queued for KB review.');
    } elseif ($action === 'reject' && $id) {
        $conn->query("UPDATE chatbot_feedback SET admin_action = 'rejected', admin_reviewed = 1 WHERE id = $id");
        setFlash('success', 'Feedback rejected.');
    } elseif ($action === 'delete' && $id) {
        $conn->query("DELETE FROM chatbot_feedback WHERE id = $id");
        setFlash('success', 'Feedback deleted.');
    }
    redirect(SITE_URL . '/admin/feedback-review.php');
}

$pending = $conn->query("SELECT * FROM chatbot_feedback WHERE admin_reviewed = 0 ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$reviewed = $conn->query("SELECT * FROM chatbot_feedback WHERE admin_reviewed = 1 ORDER BY created_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);

$stats = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(feedback = 1) as helpful,
    SUM(feedback = 0) as not_helpful,
    SUM(admin_action = 'approved') as approved,
    SUM(admin_action = 'rejected') as rejected,
    SUM(admin_action = 'pending') as pending_count
FROM chatbot_feedback")->fetch_assoc();

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.fb-wrap{max-width:1000px;margin:0 auto;padding:28px 16px 50px}
.fb-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.fb-stat{background:#fff;border-radius:14px;padding:18px 20px;box-shadow:0 2px 8px rgba(0,0,0,.05);text-align:center}
.fb-stat h4{margin:0;font-size:1.8rem;font-weight:800;background:linear-gradient(135deg,#667eea,#764ba2);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.fb-stat p{margin:4px 0 0;font-size:.78rem;color:#888}
.fb-card{background:#fff;border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid rgba(0,0,0,.04);margin-bottom:16px;overflow:hidden}
.fb-card-header{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
.fb-card-body{padding:16px 20px}
.fb-msg{font-size:.85rem;color:#444;margin-bottom:8px;line-height:1.5}
.fb-meta{font-size:.75rem;color:#999;margin-top:6px}
.fb-btn{padding:8px 16px;border-radius:10px;border:none;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s}
.fb-approve{background:#dcfce7;color:#166534}.fb-approve:hover{background:#bbf7d0}
.fb-reject{background:#fef2f2;color:#dc2626}.fb-reject:hover{background:#fecaca}
.fb-delete{background:#f5f5f5;color:#888}.fb-delete:hover{background:#e5e5e5}
.fb-section-title{font-size:1rem;font-weight:700;color:#1a1a2e;margin:20px 0 12px;display:flex;align-items:center;gap:8px}
@media(max-width:768px){.fb-stats{grid-template-columns:repeat(2,1fr)}}
</style>

<div class="admin-layout">
    <?php require_once __DIR__ . '/../includes/management-sidebar.php'; ?>
    <div class="admin-main">
        <div class="fb-wrap">
            <div class="admin-header" style="margin-bottom:24px;">
                <div>
                    <h3 class="admin-page-title"><i class="fas fa-comments me-2"></i>Chatbot Feedback Review</h3>
                    <p class="admin-page-subtitle">Review user feedback, approve useful responses for knowledge base</p>
                </div>
            </div>

            <div class="fb-stats">
                <div class="fb-stat"><h4><?php echo $stats['total'] ?? 0; ?></h4><p>Total Feedback</p></div>
                <div class="fb-stat"><h4 style="background:linear-gradient(135deg,#22c55e,#16a34a);-webkit-background-clip:text"><?php echo $stats['helpful'] ?? 0; ?></h4><p>Helpful</p></div>
                <div class="fb-stat"><h4 style="background:linear-gradient(135deg,#ef4444,#dc2626);-webkit-background-clip:text"><?php echo $stats['not_helpful'] ?? 0; ?></h4><p>Not Helpful</p></div>
                <div class="fb-stat"><h4 style="background:linear-gradient(135deg,#f59e0b,#d97706);-webkit-background-clip:text"><?php echo $stats['pending_count'] ?? 0; ?></h4><p>Pending Review</p></div>
            </div>

            <div class="fb-section-title"><i class="fas fa-clock"></i> Pending Review (<?php echo count($pending); ?>)</div>
            <?php if (empty($pending)): ?>
                <div class="fb-card"><div class="fb-card-body" style="text-align:center;color:#999;padding:30px;">No pending feedback to review</div></div>
            <?php endif; ?>
            <?php foreach ($pending as $f): ?>
                <div class="fb-card">
                    <div class="fb-card-header">
                        <span style="font-size:.8rem;color:#666;">
                            <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;background:<?php echo $f['feedback'] ? '#dcfce7;color:#166534' : '#fef2f2;color:#dc2626'; ?>">
                                <?php echo $f['feedback'] ? '&#128077; Helpful' : '&#128078; Not Helpful'; ?>
                            </span>
                            &nbsp;<?php echo date('M d, H:i', strtotime($f['created_at'])); ?>
                        </span>
                        <div style="display:flex;gap:6px;">
                            <form method="POST" style="display:inline;"><?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                <button type="submit" class="fb-btn fb-approve" title="Approve"><i class="fas fa-check"></i> Approve</button>
                            </form>
                            <form method="POST" style="display:inline;"><?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                <button type="submit" class="fb-btn fb-reject" title="Reject"><i class="fas fa-times"></i> Reject</button>
                            </form>
                            <form method="POST" style="display:inline;"><?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                <button type="submit" class="fb-btn fb-delete" title="Delete" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <div class="fb-card-body">
                        <div class="fb-msg"><strong>User asked:</strong> <?php echo htmlspecialchars($f['message_sent']); ?></div>
                        <div class="fb-msg"><strong>AI responded:</strong> <?php echo htmlspecialchars(mb_substr($f['response_given'], 0, 400)); ?>...</div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($reviewed)): ?>
                <div class="fb-section-title"><i class="fas fa-history"></i> Recently Reviewed</div>
                <?php foreach (array_slice($reviewed, 0, 10) as $f): ?>
                    <div class="fb-card" style="opacity:.7;">
                        <div class="fb-card-header">
                            <span style="font-size:.8rem;color:#666;">
                                <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;background:<?php
                                    $bg = '#f5f5f5;color:#888';
                                    if ($f['admin_action'] === 'approved') $bg = '#dcfce7;color:#166534';
                                    elseif ($f['admin_action'] === 'rejected') $bg = '#fef2f2;color:#dc2626';
                                    echo $bg;
                                ?>">
                                    <?php echo ucfirst($f['admin_action']); ?>
                                </span>
                                &nbsp;<?php echo date('M d, H:i', strtotime($f['created_at'])); ?>
                            </span>
                        </div>
                        <div class="fb-card-body">
                            <div class="fb-msg" style="font-size:.8rem;"><strong>Q:</strong> <?php echo htmlspecialchars(mb_substr($f['message_sent'], 0, 100)); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>