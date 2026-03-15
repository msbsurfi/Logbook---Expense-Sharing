<?php
$title = 'Admin Panel';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../Lib/Security.php';

// Helper to keep query params when switching pages
function build_url($params = []) {
    return '/admin?' . http_build_query(array_merge($_GET, $params));
}
?>

<div class="page-header">
    <h1><i class="fa-solid fa-users-gear"></i> User Management</h1>
    <p class="subtitle">Manage users, roles, and system exports.</p>
</div>

<div class="admin-grid">
    <!-- Main Content: User List -->
    <div class="main-column">
        <div class="dashboard-card">
            <!-- Tabs -->
            <div class="nav-tabs">
                <a href="<?php echo build_url(['status' => '', 'page' => 1]); ?>" 
                   class="nav-link <?php echo empty($data['filters']['status']) ? 'active' : ''; ?>">All</a>
                <a href="<?php echo build_url(['status' => 'pending_approval', 'page' => 1]); ?>" 
                   class="nav-link <?php echo ($data['filters']['status'] ?? '') === 'pending_approval' ? 'active' : ''; ?>">
                   Pending
                </a>
                <a href="<?php echo build_url(['status' => 'suspended', 'page' => 1]); ?>" 
                   class="nav-link <?php echo ($data['filters']['status'] ?? '') === 'suspended' ? 'active' : ''; ?>">
                   Banned
                </a>
                <a href="<?php echo build_url(['role' => 'admin', 'status' => '', 'page' => 1]); ?>" 
                   class="nav-link <?php echo ($data['filters']['role'] ?? '') === 'admin' ? 'active' : ''; ?>">
                   Admins
                </a>
            </div>

            <!-- Mobile-Friendly User Table -->
            <div class="responsive-table-container">
                <table class="admin-table mobile-stack">
                    <thead>
                        <tr>
                            <th>User Info</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data['users'])) : foreach ($data['users'] as $u) : ?>
                            <tr>
                                <td data-label="User">
                                    <div class="user-cell">
                                        <div class="user-avatar-small"><?php echo strtoupper(substr($u->name, 0, 1)); ?></div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($u->name); ?></strong>
                                            <small><?php echo htmlspecialchars($u->email); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Role">
                                    <span class="badge role-<?php echo $u->role; ?>"><?php echo ucfirst($u->role); ?></span>
                                </td>
                                <td data-label="Status">
                                    <?php if ($u->banned_at): ?>
                                        <span class="badge status-suspended">Banned</span>
                                    <?php elseif ($u->status === 'pending_approval'): ?>
                                        <span class="badge status-pending">Pending</span>
                                    <?php else: ?>
                                        <span class="badge status-active">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <!-- Impersonate -->
                                        <?php if ($u->id != $_SESSION['user_id']): ?>
                                        <form action="/admin/impersonate/<?php echo $u->id; ?>" method="post">
                                            <?php echo Security::csrfField(); ?>
                                            <button class="btn-icon" title="Impersonate"><i class="fa-solid fa-mask"></i></button>
                                        </form>
                                        <?php endif; ?>

                                        <!-- Approve (if pending) -->
                                        <?php if ($u->status === 'pending_approval'): ?>
                                            <form action="/admin/approve/<?php echo $u->id; ?>" method="post">
                                                <?php echo Security::csrfField(); ?>
                                                <button class="btn-icon text-success" title="Approve"><i class="fa-solid fa-check"></i></button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Ban / Unban -->
                                        <?php if ($u->banned_at): ?>
                                            <form action="/admin/unban/<?php echo $u->id; ?>" method="post">
                                                <?php echo Security::csrfField(); ?>
                                                <button class="btn-icon text-success" title="Unban"><i class="fa-solid fa-lock-open"></i></button>
                                            </form>
                                        <?php elseif ($u->id != $_SESSION['user_id']): ?>
                                            <form action="/admin/ban/<?php echo $u->id; ?>" method="post">
                                                <?php echo Security::csrfField(); ?>
                                                <button class="btn-icon text-danger" title="Ban"><i class="fa-solid fa-ban"></i></button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Make Admin/User -->
                                        <?php if ($u->role === 'user'): ?>
                                            <form action="/admin/promote/<?php echo $u->id; ?>" method="post" onsubmit="return confirm('Make this user an Admin?');">
                                                <?php echo Security::csrfField(); ?>
                                                <button class="btn-icon text-primary" title="Promote to Admin"><i class="fa-solid fa-shield-halved"></i></button>
                                            </form>
                                        <?php elseif ($u->role === 'admin' && $u->id != $_SESSION['user_id']): ?>
                                            <form action="/admin/demote/<?php echo $u->id; ?>" method="post" onsubmit="return confirm('Remove Admin rights?');">
                                                <?php echo Security::csrfField(); ?>
                                                <button class="btn-icon text-warning" title="Demote to User"><i class="fa-solid fa-user-shield"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center p-4">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($data['totalPages'] > 1): ?>
            <div class="pagination">
                <?php for($i=1; $i<=$data['totalPages']; $i++): ?>
                    <a href="<?php echo build_url(['page' => $i]); ?>" 
                       class="page-link <?php echo $i == $data['page'] ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar: Filters & Stats -->
    <div class="sidebar-column">
        <!-- Search & Filter -->
        <div class="dashboard-card">
            <h3><i class="fa-solid fa-filter"></i> Search</h3>
            <form method="get" action="/admin" class="sidebar-form">
                <input type="text" name="search" placeholder="Name or Email..." value="<?php echo htmlspecialchars($data['filters']['search'] ?? ''); ?>">
                <div class="form-row">
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="user" <?php if(($data['filters']['role']??'')==='user') echo 'selected'; ?>>User</option>
                        <option value="admin" <?php if(($data['filters']['role']??'')==='admin') echo 'selected'; ?>>Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary full-width">Apply Filters</button>
                <a href="/admin" class="btn secondary full-width text-center">Reset</a>
            </form>
        </div>

        <!-- Data Exports -->
        <div class="dashboard-card">
            <h3><i class="fa-solid fa-download"></i> Exports</h3>
            <div class="sidebar-links">
                <a href="/admin/export-users-csv?<?php echo http_build_query($_GET); ?>" class="sidebar-btn">
                    <i class="fa-solid fa-file-csv"></i> Users (CSV)
                </a>
                <a href="/admin/export-transactions-csv" class="sidebar-btn">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Transactions (All)
                </a>
                <a href="/admin/logs" class="sidebar-btn">
                    <i class="fa-solid fa-list-ol"></i> View Audit Logs
                </a>
            </div>
        </div>

        <!-- Stop Impersonation Alert -->
        <?php if (!empty($_SESSION['impersonator_admin_id'])): ?>
        <div class="dashboard-card alert-card">
            <h3>Impersonating</h3>
            <p>You are viewing as a user.</p>
            <form action="/admin/stop-impersonation" method="post">
                <?php echo Security::csrfField(); ?>
                <button class="btn btn-danger full-width">End Session</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* CSS Grid Layout */
    .admin-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
    @media (min-width: 1024px) { .admin-grid { grid-template-columns: 3fr 1fr; } }

    /* Tabs */
    .nav-tabs { display: flex; gap: 4px; overflow-x: auto; margin-bottom: 16px; border-bottom: 2px solid var(--card-border); }
    .nav-link { padding: 10px 16px; color: var(--text-secondary); text-decoration: none; font-weight: 500; white-space: nowrap; border-bottom: 2px solid transparent; margin-bottom: -2px; }
    .nav-link.active { color: var(--brand-color); border-bottom-color: var(--brand-color); }

    /* User Cell */
    .user-cell { display: flex; align-items: center; gap: 12px; }
    .user-avatar-small { width: 32px; height: 32px; background: var(--brand-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.9rem; }
    .user-cell small { display: block; color: var(--text-secondary); font-size: 0.8rem; }

    /* Buttons */
    .btn-icon { background: none; border: none; cursor: pointer; padding: 6px; font-size: 1rem; color: var(--text-secondary); transition: transform 0.2s; }
    .btn-icon:hover { transform: scale(1.1); }
    .text-success { color: var(--success-color); }
    .text-danger { color: var(--danger-color); }
    .text-primary { color: var(--brand-color); }
    .text-warning { color: #f59e0b; }
    
    /* Sidebar */
    .sidebar-form { display: flex; flex-direction: column; gap: 12px; }
    .sidebar-form input, .sidebar-form select { padding: 10px; border-radius: 6px; border: 1px solid var(--card-border); background: var(--input-bg); color: var(--text-primary); width: 100%; }
    .sidebar-links { display: flex; flex-direction: column; gap: 8px; }
    .sidebar-btn { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 6px; background: var(--input-bg); color: var(--text-primary); text-decoration: none; font-size: 0.9rem; transition: background 0.2s; }
    .sidebar-btn:hover { background: var(--card-border); }
    
    /* Mobile Stack Table (Repeated for safety) */
    @media (max-width: 768px) {
        .admin-table.mobile-stack thead { display: none; }
        .admin-table.mobile-stack tbody tr { display: block; padding: 16px; border-bottom: 1px solid var(--card-border); }
        .admin-table.mobile-stack td { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border: none; }
        .admin-table.mobile-stack td::before { content: attr(data-label); font-weight: 600; color: var(--text-secondary); margin-right: auto; }
        .admin-table.mobile-stack .action-buttons { justify-content: flex-end; }
    }
    
    /* Badges */
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
    .role-admin { background: #e0f2fe; color: #0369a1; }
    .role-user { background: #f1f5f9; color: #64748b; }
    .status-active { background: #dcfce7; color: #15803d; }
    .status-suspended { background: #fee2e2; color: #b91c1c; }
    .status-pending { background: #fef3c7; color: #b45309; }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>