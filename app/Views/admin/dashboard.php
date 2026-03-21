<?php
$title = 'Admin Dashboard';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../Lib/Security.php';
?>

<div class="page-header">
    <h1><i class="fa-solid fa-gauge-high"></i> Admin Dashboard</h1>
    <p class="subtitle">Overview of system alerts and pending actions.</p>
</div>

<div class="dashboard-grid">
    
    <div class="dashboard-card full-width">
        <div class="card-header">
            <h2>
                <i class="fa-solid fa-user-clock"></i> Pending Approvals
                <?php if (!empty($pendingUsers)) : ?>
                    <span class="count-badge"><?php echo count($pendingUsers); ?></span>
                <?php endif; ?>
            </h2>
        </div>
        
        <div class="responsive-table-container">
            <?php if (!empty($pendingUsers)) : ?>
                <table class="admin-table mobile-stack">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingUsers as $user) : ?>
                            <tr>
                                <td data-label="Name">
                                    <strong><?php echo htmlspecialchars($user->name); ?></strong>
                                </td>
                                <td data-label="Email">
                                    <a href="mailto:<?php echo htmlspecialchars($user->email); ?>" class="text-link">
                                        <?php echo htmlspecialchars($user->email); ?>
                                    </a>
                                </td>
                                <td data-label="Registered">
                                    <?php echo date("M j, g:i a", strtotime($user->created_at)); ?>
                                </td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <form action="/admin/approve/<?php echo $user->id; ?>" method="post">
                                            <?php echo Security::csrfField(); ?>
                                            <button type="submit" class="btn btn-sm btn-success disable-on-click">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form action="/admin/reject/<?php echo $user->id; ?>" method="post">
                                            <?php echo Security::csrfField(); ?>
                                            <button type="submit" class="btn btn-sm btn-danger disable-on-click">
                                                <i class="fa-solid fa-xmark"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="empty-state">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <p>All caught up!</p>
                    <span>No pending user approvals.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    
    .responsive-table-container { width: 100%; overflow-x: auto; }
    
    @media (max-width: 768px) {
        .admin-table.mobile-stack thead { display: none; }
        .admin-table.mobile-stack tbody tr {
            display: block; margin-bottom: 16px; border: 1px solid var(--card-border);
            border-radius: 8px; padding: 12px; background: var(--input-bg);
        }
        .admin-table.mobile-stack td {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; border: none; border-bottom: 1px solid var(--card-border);
        }
        .admin-table.mobile-stack td:last-child { border-bottom: none; }
        .admin-table.mobile-stack td::before {
            content: attr(data-label); font-weight: 600; color: var(--text-secondary); margin-right: 12px;
        }
    }

    .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
    .text-link { color: var(--brand-color); text-decoration: none; }
    .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
    
    .empty-state { text-align: center; padding: 40px; }
    .empty-state i { font-size: 3rem; color: var(--success-color); margin-bottom: 16px; opacity: 0.8; }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>