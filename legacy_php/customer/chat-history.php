<?php
$page_title = 'Chat History';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');

$user_id = $_SESSION['user_id'];

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM chatbot_logs WHERE user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();
$total_pages = ceil($total / $per_page);

$chat_stmt = $conn->prepare("SELECT * FROM chatbot_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$chat_stmt->bind_param("iii", $user_id, $per_page, $offset);
$chat_stmt->execute();
$chat_result = $chat_stmt->get_result();

require_once __DIR__ . '/header.php';
?>

<div class="container-fluid px-4 py-4">
    <a href="<?php echo SITE_URL; ?>/customer/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <div class="cust-welcome-banner">
        <div class="cust-welcome-left">
            <h1 class="cust-welcome-title">Chat History</h1>
                    <p class="cust-welcome-desc">Your conversations with MechBot</p>
                </div>
                <div class="cust-welcome-actions">
                    <button onclick="toggleChatbot()" class="cust-btn-chatbot"><i class="fas fa-robot me-1"></i>Start New Chat</button>
                </div>
            </div>

            <?php if ($chat_result->num_rows > 0): ?>
                <div class="cust-section">
                    <div class="cust-empty-state" style="padding:0;">
                        <?php while ($chat = $chat_result->fetch_assoc()): ?>
                            <div style="display:flex;gap:12px;padding:16px 20px;border-bottom:1px solid #f0f0f0;">
                                <div style="width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#e8f0fe;">
                                    <i class="fas fa-user" style="color:#3498db;"></i>
                                </div>
                                <div style="flex:1;">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                        <strong style="font-size:0.9rem;">You</strong>
                                        <small style="color:#888;font-size:0.75rem;"><i class="fas fa-clock me-1"></i><?php echo timeAgo($chat['created_at']); ?></small>
                                    </div>
                                    <p style="margin:0;font-size:0.88rem;line-height:1.5;"><?php echo nl2br(htmlspecialchars($chat['question'])); ?></p>
                                    <?php if (!empty($chat['response'])): ?>
                                        <div style="margin-top:8px;padding:10px;background:#f8f9fa;border-radius:8px;">
                                            <small style="color:#888;display:block;margin-bottom:4px;"><i class="fas fa-robot me-1"></i>MechBot Response:</small>
                                            <p style="margin:0;font-size:0.85rem;line-height:1.5;"><?php echo nl2br(htmlspecialchars($chat['response'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a></li>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="cust-section">
            <div class="cust-empty-state">
                <div class="cust-empty-icon"><i class="fas fa-robot"></i></div>
                <h3 class="cust-empty-title">No chat history yet</h3>
                <p class="cust-empty-desc">Start a conversation with MechBot using the chat button in the bottom-right corner</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$chat_stmt->close();
require_once __DIR__ . '/footer.php';
?>