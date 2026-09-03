<?php
$page_title = 'Management Team';
require_once __DIR__ . '/../includes/config.php';
requireRole('admin');

$members = $conn->query("SELECT * FROM users WHERE role = 'management' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="admin-main">
        <a href="dashboard.php" class="admin-back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="admin-header">
            <div>
                <h2 class="admin-page-title"><i class="fas fa-users-cog"></i> Management Team</h2>
                <p class="admin-page-subtitle">Manage platform management members</p>
            </div>
            <div class="admin-header-actions">
                <a href="users.php?role=management" class="btn-admin-red"><i class="fas fa-plus"></i> Add Member</a>
                <span class="admin-count-badge"><i class="fas fa-users-cog"></i> <?php echo count($members); ?> members</span>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-body p-0">
                <div class="admin-table-responsive">
                    <?php if (empty($members)): ?>
                        <div class="admin-empty-state">
                            <i class="fas fa-users-cog"></i>
                            <h4>No management members</h4>
                            <p>No users with management role found</p>
                        </div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $i => $member): ?>
                                    <tr>
                                        <td>
                                            <div class="cell-user">
                                                <div class="cell-avatar avatar-c<?php echo ($i % 5) + 1; ?>"><?php echo strtoupper(substr($member['name'], 0, 1)); ?></div>
                                                <div>
                                                    <div class="cell-info-name"><?php echo htmlspecialchars($member['name']); ?></div>
                                                    <div class="cell-info-sub">#<?php echo $member['user_id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo htmlspecialchars($member['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-pill status-<?php echo $member['status'] ?? 'active'; ?>">
                                                <?php echo ucfirst($member['status'] ?? 'Active'); ?>
                                            </span>
                                        </td>
                                        <td><small style="color:#999;"><?php echo date('M d, Y', strtotime($member['created_at'])); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
