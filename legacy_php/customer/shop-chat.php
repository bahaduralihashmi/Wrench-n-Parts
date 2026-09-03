<?php
$page_title = 'Chat with Shop';
require_once __DIR__ . '/../includes/config.php';
requireRole('customer');

$user_id = $_SESSION['user_id'];
$shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : 0;
if (!$shop_id) { redirect(SITE_URL . '/customer/dashboard.php'); }

$shop = $conn->prepare("SELECT s.*, u.name AS owner_name FROM shops s LEFT JOIN users u ON s.user_id = u.user_id WHERE s.shop_id = ?");
$shop->bind_param("i", $shop_id);
$shop->execute();
$shop_data = $shop->get_result()->fetch_assoc();
$shop->close();
if (!$shop_data) { setFlash('danger', 'Shop not found.'); redirect(SITE_URL . '/customer/dashboard.php'); }

$shopkeeper_id = $shop_data['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $msg = trim($_POST['message']);
    if ($msg) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $shopkeeper_id, $msg);
        $stmt->execute();
        $stmt->close();
    }
    redirect(SITE_URL . '/customer/shop-chat.php?shop_id=' . $shop_id);
}

$read_stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
$read_stmt->bind_param("ii", $shopkeeper_id, $user_id);
$read_stmt->execute();
$read_stmt->close();

$messages = $conn->prepare("SELECT * FROM chat_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$messages->bind_param("iiii", $user_id, $shopkeeper_id, $shopkeeper_id, $user_id);
$messages->execute();
$messages_result = $messages->get_result();

require_once __DIR__ . '/header.php';
?>

<style>
.chat-page{display:flex;flex-direction:column;height:calc(100vh - 65px);max-height:calc(100vh - 65px);overflow:hidden}
.chat-header-bar{display:flex;align-items:center;gap:14px;padding:14px 24px;background:#fff;border-bottom:1px solid #f0f0f0;flex-shrink:0;transition:all .3s}
[data-theme="dark"] .chat-header-bar{background:#1a1a2e;border-color:#2a2a3e}
.chat-header-bar .shop-avatar{width:44px;height:44px;border-radius:12px;object-fit:cover;background:#f0f0f0}
[data-theme="dark"] .chat-header-bar .shop-avatar{background:#0f172a}
.chat-header-bar .shop-info{flex:1}
.chat-header-bar .shop-info h2{font-size:1rem;font-weight:700;color:#1a1a2e;margin:0}
[data-theme="dark"] .chat-header-bar .shop-info h2{color:#e8e8f0}
.chat-header-bar .shop-info p{font-size:.75rem;color:#888;margin:2px 0 0;display:flex;align-items:center;gap:5px}
[data-theme="dark"] .chat-header-bar .shop-info p{color:#999}
.chat-header-bar .shop-info .online-dot{width:7px;height:7px;background:#22c55e;border-radius:50%;display:inline-block}
.chat-header-bar .back-link{width:38px;height:38px;background:#f8f9fa;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#666;text-decoration:none;font-size:.9rem;transition:all .2s;flex-shrink:0}
[data-theme="dark"] .chat-header-bar .back-link{background:#0f172a;color:#999}
.chat-header-bar .back-link:hover{background:#fee2e2;color:#dc3545}
[data-theme="dark"] .chat-header-bar .back-link:hover{background:rgba(220,53,69,.15);color:#ff6b6b}
.chat-body{flex:1;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:10px;background:#f8f9fa;transition:all .3s}
[data-theme="dark"] .chat-body{background:#0a0a14}
.chat-msg{max-width:70%;padding:12px 16px;border-radius:16px;font-size:.88rem;line-height:1.5;position:relative;word-wrap:break-word}
.chat-msg.sent{align-self:flex-end;background:linear-gradient(135deg,#dc3545,#b71c1c);color:#fff;border-bottom-right-radius:4px}
.chat-msg.received{align-self:flex-start;background:#fff;color:#333;border:1px solid #f0f0f0;border-bottom-left-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
[data-theme="dark"] .chat-msg.received{background:#1a1a2e;border-color:#2a2a3e;color:#e2e8f0}
.chat-msg .msg-time{font-size:.65rem;opacity:.65;margin-top:4px;display:block}
.chat-msg.sent .msg-time{text-align:right}
.chat-input-bar{display:flex;gap:10px;padding:14px 24px;background:#fff;border-top:1px solid #f0f0f0;flex-shrink:0;transition:all .3s}
[data-theme="dark"] .chat-input-bar{background:#1a1a2e;border-color:#2a2a3e}
.chat-input-bar input{flex:1;padding:12px 18px;border:1.5px solid #e8e8e8;border-radius:12px;font-size:.9rem;color:#333;outline:none;transition:border-color .3s}
[data-theme="dark"] .chat-input-bar input{background:#0f172a;border-color:#475569;color:#e2e8f0}
.chat-input-bar input:focus{border-color:#dc3545}
.chat-input-bar button{width:46px;height:46px;background:linear-gradient(135deg,#dc3545,#b71c1c);border:none;border-radius:12px;color:#fff;font-size:1rem;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.chat-input-bar button:hover{transform:scale(1.05);box-shadow:0 4px 16px rgba(220,53,69,.4)}
.chat-empty{text-align:center;padding:60px 20px;color:#aaa;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center}
.chat-empty i{font-size:3rem;margin-bottom:14px;color:#ddd}
.chat-empty p{margin:0;font-size:.9rem}
[data-theme="dark"] .chat-empty p{color:#999}
.chat-date-divider{text-align:center;font-size:.72rem;color:#aaa;padding:8px 0}
@media(max-width:768px){
    .chat-page{height:calc(100vh - 120px);max-height:calc(100vh - 120px)}
    .chat-header-bar,.chat-input-bar{padding:10px 14px}
    .chat-body{padding:14px}
    .chat-msg{max-width:85%}
}
</style>

<div class="chat-page">
    <div class="chat-header-bar">
        <a href="<?php echo SITE_URL; ?>/customer/shop-profile.php?id=<?php echo $shop_id; ?>" class="back-link"><i class="fas fa-arrow-left"></i></a>
        <a href="<?php echo SITE_URL; ?>/customer/shop-profile.php?id=<?php echo $shop_id; ?>" style="text-decoration:none;display:flex;align-items:center;gap:14px;flex:1;">
            <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($shop_data['logo']) ? $shop_data['logo'] : 'no-image.png'; ?>" class="shop-avatar" alt="" style="cursor:pointer;">
            <div class="shop-info">
                <h2 style="cursor:pointer;"><?php echo htmlspecialchars($shop_data['shop_name']); ?></h2>
                <p><span class="online-dot"></span> <?php echo htmlspecialchars($shop_data['owner_name'] ?? 'Shop Owner'); ?></p>
            </div>
        </a>
    </div>

    <div class="chat-body" id="chatBody">
        <?php if ($messages_result->num_rows > 0): ?>
            <?php $last_date = ''; while ($msg = $messages_result->fetch_assoc()): ?>
                <?php $msg_date = date('M d, Y', strtotime($msg['created_at'])); ?>
                <?php if ($msg_date !== $last_date): ?>
                    <div class="chat-date-divider"><?php echo $msg_date; ?></div>
                    <?php $last_date = $msg_date; ?>
                <?php endif; ?>
                <div class="chat-msg <?php echo $msg['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                    <?php echo htmlspecialchars($msg['message']); ?>
                    <span class="msg-time"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="chat-empty">
                <i class="fas fa-comments"></i>
                <p>Start a conversation with <strong><?php echo htmlspecialchars($shop_data['shop_name']); ?></strong></p>
            </div>
        <?php endif; ?>
    </div>

    <form method="POST" class="chat-input-bar">
        <?php echo csrfField(); ?>
        <input type="text" name="message" id="chatMsgInput" placeholder="Type your message..." autocomplete="off" required>
        <button type="submit" name="send_msg"><i class="fas fa-paper-plane"></i></button>
    </form>
</div>

<script>
var chatBody = document.getElementById('chatBody');
chatBody.scrollTop = chatBody.scrollHeight;

document.getElementById('chatMsgInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.form.submit();
    }
});
</script>

<?php
$messages->close();
require_once __DIR__ . '/footer.php';
?>
