<?php
$page_title = 'Customer Chat';
require_once __DIR__ . '/../includes/config.php';
requireRole('shopkeeper');

$shop = null;
$stmt = $conn->prepare("SELECT * FROM shops WHERE user_id = ?");
$stmt->bind_param("i", $current_user['user_id']);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$shop) {
    setFlash('warning', 'Please set up your shop first.');
    redirect(SITE_URL . '/shopkeeper/profile.php');
}

$shop_id = $shop['shop_id'];
$shopkeeper_id = $current_user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_msg'])) {
    verifyCsrf();
    $msg = trim($_POST['message']);
    $customer_id = (int)$_POST['customer_id'];
    if ($msg && $customer_id) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $shopkeeper_id, $customer_id, $msg);
        $stmt->execute();
        $stmt->close();
    }
    redirect(SITE_URL . '/shopkeeper/chat.php?customer_id=' . $customer_id);
}

$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

if ($customer_id) {
    $read_stmt = $conn->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $read_stmt->bind_param("ii", $customer_id, $shopkeeper_id);
    $read_stmt->execute();
    $read_stmt->close();
}

$conversations = $conn->query("
    SELECT u.user_id, u.name, u.profile_image,
           (SELECT message FROM chat_messages cm
            WHERE (cm.sender_id = u.user_id AND cm.receiver_id = $shopkeeper_id)
               OR (cm.sender_id = $shopkeeper_id AND cm.receiver_id = u.user_id)
            ORDER BY cm.created_at DESC LIMIT 1) as last_msg,
           (SELECT created_at FROM chat_messages cm
            WHERE (cm.sender_id = u.user_id AND cm.receiver_id = $shopkeeper_id)
               OR (cm.sender_id = $shopkeeper_id AND cm.receiver_id = u.user_id)
            ORDER BY cm.created_at DESC LIMIT 1) as last_time,
           (SELECT COUNT(*) FROM chat_messages cm
            WHERE cm.sender_id = u.user_id AND cm.receiver_id = $shopkeeper_id AND cm.is_read = 0) as unread
    FROM users u
    WHERE u.user_id IN (
        SELECT DISTINCT sender_id FROM chat_messages WHERE receiver_id = $shopkeeper_id
        UNION
        SELECT DISTINCT receiver_id FROM chat_messages WHERE sender_id = $shopkeeper_id
    )
    ORDER BY last_time DESC
");

$customer_data = null;
$messages_result = null;
if ($customer_id) {
    $cust = $conn->prepare("SELECT user_id, name, profile_image, email FROM users WHERE user_id = ? AND role = 'customer'");
    $cust->bind_param("i", $customer_id);
    $cust->execute();
    $customer_data = $cust->get_result()->fetch_assoc();
    $cust->close();

    $messages = $conn->prepare("SELECT * FROM chat_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
    $messages->bind_param("iiii", $customer_id, $shopkeeper_id, $shopkeeper_id, $customer_id);
    $messages->execute();
    $messages_result = $messages->get_result();
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.sk-chat-layout { display: flex; gap: 16px; min-height: calc(100vh - 140px); }
.sk-chat-list { width: 300px; flex-shrink: 0; background: #fff; border-radius: 14px; border: 1px solid #f0f0f0; overflow: hidden; display: flex; flex-direction: column; }
.sk-chat-list-header { padding: 16px; border-bottom: 1px solid #f0f0f0; }
.sk-chat-list-header h4 { margin: 0; font-weight: 700; font-size: 1rem; }
.sk-chat-list-body { flex: 1; overflow-y: auto; }
.sk-chat-conv { display: flex; gap: 12px; padding: 12px 16px; text-decoration: none; color: inherit; border-bottom: 1px solid #f5f5f5; transition: background .2s; }
.sk-chat-conv:hover, .sk-chat-conv.active { background: #fff5f5; }
.sk-chat-conv-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; background: #f0f0f0; flex-shrink: 0; }
.sk-chat-conv-info { flex: 1; min-width: 0; }
.sk-chat-conv-name { font-weight: 600; font-size: .88rem; color: #1a1a2e; }
.sk-chat-conv-msg { font-size: .78rem; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sk-chat-conv-time { font-size: .68rem; color: #aaa; white-space: nowrap; }
.sk-chat-unread { width: 18px; height: 18px; background: #dc3545; color: #fff; border-radius: 50%; font-size: .65rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

.sk-chat-main { flex: 1; background: #fff; border-radius: 14px; border: 1px solid #f0f0f0; display: flex; flex-direction: column; overflow: hidden; }
.sk-chat-main-header { padding: 14px 20px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 12px; }
.sk-chat-main-header img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #f0f0f0; }
.sk-chat-main-header h5 { margin: 0; font-weight: 700; }
.sk-chat-main-header small { color: #999; }
.sk-chat-body { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px; background: #f8f9fa; }
.sk-chat-msg { max-width: 70%; padding: 10px 14px; border-radius: 14px; font-size: .88rem; line-height: 1.5; word-wrap: break-word; }
.sk-chat-msg.sent { align-self: flex-end; background: linear-gradient(135deg, #dc3545, #b71c1c); color: #fff; border-bottom-right-radius: 4px; }
.sk-chat-msg.received { align-self: flex-start; background: #fff; color: #333; border: 1px solid #f0f0f0; border-bottom-left-radius: 4px; }
.sk-chat-msg .time { font-size: .65rem; opacity: .65; margin-top: 4px; display: block; }
.sk-chat-input { padding: 14px 20px; border-top: 1px solid #f0f0f0; display: flex; gap: 10px; }
.sk-chat-input input { flex: 1; padding: 11px 16px; border: 1.5px solid #e8e8e8; border-radius: 10px; font-size: .9rem; outline: none; }
.sk-chat-input input:focus { border-color: #dc3545; }
.sk-chat-input button { width: 44px; height: 44px; background: linear-gradient(135deg, #dc3545, #b71c1c); border: none; border-radius: 10px; color: #fff; cursor: pointer; font-size: 1rem; flex-shrink: 0; }
.sk-chat-empty { text-align: center; padding: 40px; color: #aaa; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.sk-chat-empty i { font-size: 2.5rem; margin-bottom: 12px; color: #ddd; }
@media(max-width: 992px) {
    .sk-chat-layout { flex-direction: column; }
    .sk-chat-list { width: 100%; max-height: 240px; }
}
</style>

<button class="admin-sidebar-toggle" id="skSidebarToggle" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.toggle('show');document.getElementById('skOverlay').classList.toggle('active')">
    <i class="fas fa-bars"></i>
</button>
<div class="admin-sidebar-overlay" id="skOverlay" onclick="document.querySelector('.dash-layout .dash-sidebar').classList.remove('show');this.classList.remove('active')"></div>
<div class="dash-layout">
    <div class="dash-sidebar">
        <div class="dash-sidebar-brand">
            <div class="dash-brand-icon">SK</div>
            <div>
                <div class="dash-brand-text">Shopkeeper</div>
                <small style="color:#888;font-size:0.75rem;"><?php echo htmlspecialchars($shop['shop_name']); ?></small>
            </div>
        </div>
        <div class="dash-sidebar-label">Menu</div>
        <nav class="dash-nav">
            <a class="dash-nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a class="dash-nav-link" href="products.php"><i class="fas fa-boxes-stacked"></i>Products</a>
            <a class="dash-nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i>Orders</a>
            <a class="dash-nav-link" href="inventory.php"><i class="fas fa-warehouse"></i>Inventory</a>
            <a class="dash-nav-link" href="hot-deals.php"><i class="fas fa-fire"></i>Hot Deals</a>
            <a class="dash-nav-link" href="returns.php"><i class="fas fa-undo-alt"></i>Returns</a>
            <a class="dash-nav-link active" href="chat.php"><i class="fas fa-comments"></i>Chat</a>
            <a class="dash-nav-link" href="profile.php"><i class="fas fa-user-cog"></i>Profile</a>
        </nav>
        <div class="dash-sidebar-footer">
            <a class="dash-nav-link logout" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </div>
    </div>
    <div class="dash-main">
        <a href="<?php echo SITE_URL; ?>/shopkeeper/dashboard.php" style="color:#555;text-decoration:none;font-size:0.88rem;font-weight:500;display:inline-flex;align-items:center;gap:4px;margin-bottom:12px;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <div class="dash-header">
            <h2 class="fw-bold mb-0"><i class="fas fa-comments me-2"></i>Customer Chat</h2>
        </div>

        <div class="sk-chat-layout">
            <div class="sk-chat-list">
                <div class="sk-chat-list-header">
                    <h4><i class="fas fa-users me-1" style="color:#dc3545;"></i> Conversations</h4>
                </div>
                <div class="sk-chat-list-body">
                    <?php if ($conversations && $conversations->num_rows > 0): ?>
                        <?php while ($conv = $conversations->fetch_assoc()): ?>
                            <a href="?customer_id=<?php echo $conv['user_id']; ?>" class="sk-chat-conv <?php echo $customer_id === (int)$conv['user_id'] ? 'active' : ''; ?>">
                                <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($conv['profile_image']) ? $conv['profile_image'] : 'no-image.png'; ?>" class="sk-chat-conv-avatar" alt="">
                                <div class="sk-chat-conv-info">
                                    <div class="d-flex justify-content-between">
                                        <span class="sk-chat-conv-name"><?php echo htmlspecialchars($conv['name']); ?></span>
                                        <?php if ($conv['last_time']): ?><span class="sk-chat-conv-time"><?php echo date('M d', strtotime($conv['last_time'])); ?></span><?php endif; ?>
                                    </div>
                                    <div class="sk-chat-conv-msg"><?php echo htmlspecialchars($conv['last_msg'] ?? 'No messages yet'); ?></div>
                                </div>
                                <?php if ($conv['unread'] > 0): ?>
                                    <span class="sk-chat-unread"><?php echo $conv['unread']; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="sk-chat-empty">
                            <i class="fas fa-comments"></i>
                            <p>No conversations yet.<br>Customers will appear here when they message you.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sk-chat-main">
                <?php if ($customer_data): ?>
                    <div class="sk-chat-main-header">
                        <img src="<?php echo SITE_URL; ?>/uploads/<?php echo !empty($customer_data['profile_image']) ? $customer_data['profile_image'] : 'no-image.png'; ?>" alt="">
                        <div>
                            <h5><?php echo htmlspecialchars($customer_data['name']); ?></h5>
                            <small><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($customer_data['email'] ?? ''); ?></small>
                        </div>
                    </div>
                    <div class="sk-chat-body" id="skChatBody">
                        <?php if ($messages_result && $messages_result->num_rows > 0): ?>
                            <?php while ($msg = $messages_result->fetch_assoc()): ?>
                                <div class="sk-chat-msg <?php echo $msg['sender_id'] == $shopkeeper_id ? 'sent' : 'received'; ?>">
                                    <?php echo htmlspecialchars($msg['message']); ?>
                                    <span class="time"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="sk-chat-empty">
                                <i class="fas fa-comments"></i>
                                <p>No messages in this conversation yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" class="sk-chat-input">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>">
                        <input type="text" name="message" placeholder="Type your reply..." autocomplete="off" required>
                        <button type="submit" name="send_msg"><i class="fas fa-paper-plane"></i></button>
                    </form>
                <?php else: ?>
                    <div class="sk-chat-empty" style="flex:1;">
                        <i class="fas fa-hand-point-left"></i>
                        <p>Select a conversation to start chatting.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
var skChatBody = document.getElementById('skChatBody');
if (skChatBody) skChatBody.scrollTop = skChatBody.scrollHeight;
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
