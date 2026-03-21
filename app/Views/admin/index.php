<?php
$title = 'Admin Administration';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../Lib/Security.php';

// Helper for URL generation
function url($tab, $params = []) {
    $query = array_merge(['tab' => $tab], $params);
    return '/admin?' . http_build_query($query);
}

$activeTab = $data['tab'] ?? 'dashboard';
?>

<div class="page-header">
    <h1><i class="fa-solid fa-shield-halved"></i> Administration</h1>
</div>

<div class="admin-tabs">
    <a href="<?php echo url('dashboard'); ?>" class="tab-link <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-line"></i> Dashboard
    </a>
    <a href="<?php echo url('users'); ?>" class="tab-link <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
        <i class="fa-solid fa-users-gear"></i> Users
    </a>
    <a href="<?php echo url('logs'); ?>" class="tab-link <?php echo $activeTab === 'logs' ? 'active' : ''; ?>">
        <i class="fa-solid fa-list-check"></i> Audit Logs
    </a>
    <a href="<?php echo url('settings'); ?>" class="tab-link <?php echo $activeTab === 'settings' ? 'active' : ''; ?>">
        <i class="fa-solid fa-sliders"></i> Settings
    </a>
</div>

<div class="admin-content">

    <?php if ($activeTab === 'dashboard'): ?>
        <div class="dashboard-grid">
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="fa-solid fa-money-bill-transfer"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($data['stats']['total_tx'] ?? 0); ?></h3>
                        <span>Total Txns</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="fa-solid fa-wallet"></i></div>
                    <div class="stat-info">
                        <h3>৳<?php echo number_format($data['stats']['total_sum'] ?? 0); ?></h3>
                        <span>Volume</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="fa-solid fa-envelope"></i></div>
                    <div class="stat-info">
                        <h3><?php echo number_format($data['stats']['monthly_emails'] ?? 0); ?></h3>
                        <span>Emails (30d)</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-card full-width">
                <div class="card-header">
                    <h2><i class="fa-solid fa-user-clock"></i> Pending User Approvals</h2>
                    <?php if (!empty($data['pending'])) : ?>
                        <span class="count-badge"><?php echo count($data['pending']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="responsive-table-container">
                    <?php if (!empty($data['pending'])) : ?>
                        <table class="admin-table mobile-stack">
                            <thead>
                                <tr>
                                    <th>User Details</th>
                                    <th>Registered</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['pending'] as $user) : ?>
                                    <tr>
                                        <td data-label="User">
                                            <strong><?php echo htmlspecialchars($user->name); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user->email); ?></small>
                                        </td>
                                        <td data-label="Registered">
                                            <?php echo date("M j, g:i a", strtotime($user->created_at)); ?>
                                        </td>
                                        <td data-label="Actions" class="text-right">
                                            <div class="action-buttons">
                                                <form action="/admin/approve/<?php echo $user->id; ?>" method="post">
                                                    <?php echo Security::csrfField(); ?>
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form action="/admin/reject/<?php echo $user->id; ?>" method="post">
                                                    <?php echo Security::csrfField(); ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">
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
                            <i class="fa-solid fa-check-double"></i>
                            <p>All caught up!</p>
                            <span>No pending approvals required.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-card full-width">
                <h3><i class="fa-solid fa-chart-area"></i> 30-Day Activity</h3>
                <canvas id="txCountChart" height="80"></canvas>
            </div>
        </div>

    <?php elseif ($activeTab === 'users'): ?>
        <div class="split-grid">
            <div class="main-column">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>User Management</h2>
                    </div>

                    <div class="responsive-table-container">
                        <table class="admin-table mobile-stack">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($data['users'])) : foreach ($data['users'] as $u) : ?>
                                    <tr>
                                        <td data-label="User">
                                            <div class="user-cell">
                                                <div class="avatar"><?php echo strtoupper(substr($u->name, 0, 1)); ?></div>
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
                                        <td data-label="Actions" class="text-right">
                                            <div class="action-buttons justify-end">
                                                <?php if ($u->id != $_SESSION['user_id']): ?>
                                                    <form action="/admin/impersonate/<?php echo $u->id; ?>" method="post">
                                                        <?php echo Security::csrfField(); ?>
                                                        <button class="btn-icon" title="Impersonate"><i class="fa-solid fa-mask"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($u->banned_at): ?>
                                                    <form action="/admin/unban/<?php echo $u->id; ?>" method="post">
                                                        <?php echo Security::csrfField(); ?>
                                                        <button class="btn-icon text-success" title="Unban"><i class="fa-solid fa-lock-open"></i></button>
                                                    </form>
                                                <?php elseif ($u->id != $_SESSION['user_id']): ?>
                                                    <form action="/admin/ban/<?php echo $u->id; ?>" method="post" onsubmit="return confirm('Ban this user?');">
                                                        <?php echo Security::csrfField(); ?>
                                                        <button class="btn-icon text-danger" title="Ban"><i class="fa-solid fa-ban"></i></button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($u->role === 'user'): ?>
                                                    <form action="/admin/promote/<?php echo $u->id; ?>" method="post" onsubmit="return confirm('Promote to Admin?');">
                                                        <?php echo Security::csrfField(); ?>
                                                        <button class="btn-icon text-primary" title="Make Admin"><i class="fa-solid fa-shield-halved"></i></button>
                                                    </form>
                                                <?php elseif ($u->role === 'admin' && $u->id != $_SESSION['user_id']): ?>
                                                    <form action="/admin/demote/<?php echo $u->id; ?>" method="post" onsubmit="return confirm('Remove Admin rights?');">
                                                        <?php echo Security::csrfField(); ?>
                                                        <button class="btn-icon text-warning" title="Remove Admin"><i class="fa-solid fa-user-shield"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="4"><div class="empty-state">No users found.</div></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($data['totalPages'] > 1): ?>
                        <div class="pagination">
                            <?php for($i=1; $i<=$data['totalPages']; $i++): ?>
                                <a href="<?php echo url('users', array_merge($data['filters'], ['page' => $i])); ?>" 
                                   class="page-link <?php echo $i == $data['page'] ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-column">
                <div class="dashboard-card">
                    <h3>Filter Users</h3>
                    <form method="get" action="/admin" class="sidebar-form">
                        <input type="hidden" name="tab" value="users">
                        <input type="text" name="search" placeholder="Name or email..." value="<?php echo htmlspecialchars($data['filters']['search']); ?>">
                        <select name="role">
                            <option value="">All Roles</option>
                            <option value="user" <?php if($data['filters']['role']=='user') echo 'selected'; ?>>User</option>
                            <option value="admin" <?php if($data['filters']['role']=='admin') echo 'selected'; ?>>Admin</option>
                        </select>
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="active" <?php if($data['filters']['status']=='active') echo 'selected'; ?>>Active</option>
                            <option value="pending_approval" <?php if($data['filters']['status']=='pending_approval') echo 'selected'; ?>>Pending</option>
                            <option value="suspended" <?php if($data['filters']['status']=='suspended') echo 'selected'; ?>>Banned</option>
                        </select>
                        <button class="btn btn-primary full-width">Filter</button>
                        <a href="<?php echo url('users'); ?>" class="btn secondary full-width text-center">Reset</a>
                    </form>
                </div>

                <div class="dashboard-card">
                    <h3>Exports</h3>
                    <a href="/admin/export-users-csv" class="btn secondary full-width mb-2"><i class="fa-solid fa-file-csv"></i> Export Users</a>
                    <a href="/admin/export-transactions-csv" class="btn secondary full-width"><i class="fa-solid fa-file-invoice-dollar"></i> Export Transactions</a>
                </div>

                <?php if (!empty($_SESSION['impersonator_admin_id'])): ?>
                    <div class="dashboard-card alert-card">
                        <h3>Impersonating</h3>
                        <form action="/admin/stop-impersonation" method="post">
                            <?php echo Security::csrfField(); ?>
                            <button class="btn btn-danger full-width">End Session</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($activeTab === 'logs'): ?>
        <div class="dashboard-card full-width">
            <div class="card-header">
                <h2>System Audit Logs (<?php echo $data['totalActions']; ?>)</h2>
            </div>
            
            <div class="responsive-table-container">
                <table class="admin-table mobile-stack">
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['actions'] as $a): ?>
                        <tr>
                            <td data-label="Admin"><strong><?php echo htmlspecialchars($a->admin_name ?? 'ID:'.$a->admin_id); ?></strong></td>
                            <td data-label="Action"><span class="badge-log"><?php echo htmlspecialchars($a->action); ?></span></td>
                            <td data-label="Details" class="meta-cell">
                                <?php 
                                    if ($a->meta) echo '<div class="json-box">'.htmlspecialchars($a->meta).'</div>'; 
                                    else echo '-'; 
                                ?>
                            </td>
                            <td data-label="Time" class="text-nowrap"><?php echo date('M j, H:i', strtotime($a->created_at)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination mt-4">
                <?php if($data['page'] > 1): ?>
                    <a href="<?php echo url('logs', ['page' => $data['page']-1]); ?>" class="btn secondary">Previous</a>
                <?php endif; ?>
                <?php if($data['page'] < $data['totalPages']): ?>
                    <a href="<?php echo url('logs', ['page' => $data['page']+1]); ?>" class="btn secondary">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($activeTab === 'settings'): ?>
    <div class="dashboard-card" style="max-width:600px;">
        <div class="card-header">
            <h2><i class="fa-solid fa-envelope-open-text"></i> Mail / SMTP Settings</h2>
        </div>
        <p style="color:var(--text-secondary);margin-bottom:20px;font-size:0.9rem;">
            These settings control how Logbook sends email notifications.
            Leave the password field blank to keep the existing password.
        </p>
        <form action="/admin/settings/save" method="post">
            <?php echo Security::csrfField(); ?>
            <div class="settings-form">
                <div class="form-row">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($data['mailConfig']['host']); ?>" required placeholder="mail.example.com">
                </div>
                <div class="form-row">
                    <label>SMTP Port</label>
                    <input type="number" name="smtp_port" value="<?php echo (int)$data['mailConfig']['port']; ?>" required placeholder="465">
                </div>
                <div class="form-row">
                    <label>Encryption</label>
                    <select name="smtp_secure">
                        <option value="ssl" <?php echo ($data['mailConfig']['secure'] === 'ssl') ? 'selected' : ''; ?>>SSL (Port 465)</option>
                        <option value="tls" <?php echo ($data['mailConfig']['secure'] === 'tls') ? 'selected' : ''; ?>>TLS (Port 587)</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>SMTP Username</label>
                    <input type="email" name="smtp_user" value="<?php echo htmlspecialchars($data['mailConfig']['user']); ?>" required placeholder="user@example.com">
                </div>
                <div class="form-row">
                    <label>SMTP Password</label>
                    <input type="password" name="smtp_pass" placeholder="Leave blank to keep existing password" autocomplete="new-password">
                </div>
                <div class="form-row">
                    <label>From Email</label>
                    <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($data['mailConfig']['from_email']); ?>" required placeholder="noreply@example.com">
                </div>
                <div class="form-row">
                    <label>From Name</label>
                    <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($data['mailConfig']['from_name']); ?>" placeholder="LogBook">
                </div>
                <div class="form-row">
                    <button type="submit" class="btn btn-primary disable-on-click">
                        <i class="fa-solid fa-floppy-disk"></i> Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
    <style>
        .settings-form { display: flex; flex-direction: column; gap: 16px; }
        .form-row { display: grid; grid-template-columns: 160px 1fr; align-items: center; gap: 16px; }
        .form-row label { font-weight: 500; color: var(--text-secondary); font-size: 0.9rem; }
        .form-row input, .form-row select {
            padding: 10px 14px; border: 1px solid var(--card-border);
            border-radius: 8px; background: var(--input-bg); color: var(--text-primary);
            font-size: 0.95rem; width: 100%;
        }
        .form-row input:focus, .form-row select:focus {
            outline: none; border-color: var(--brand-color);
            box-shadow: 0 0 0 3px rgba(201,162,39,0.15);
        }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; gap: 6px; }
        }
    </style>
    <?php endif; ?>

</div>

<style>
    /* Tabs */
    .admin-tabs { display: flex; gap: 10px; margin-bottom: 24px; overflow-x: auto; padding-bottom: 4px; border-bottom: 1px solid var(--card-border); }
    .tab-link { padding: 10px 20px; border-radius: 8px 8px 0 0; background: var(--input-bg); color: var(--text-secondary); text-decoration: none; font-weight: 500; transition: all 0.2s; white-space: nowrap; }
    .tab-link:hover { color: var(--text-primary); background: var(--card-bg); }
    .tab-link.active { background: var(--brand-color); color: white; }

    /* Layouts */
    .dashboard-grid { display: flex; flex-direction: column; gap: 24px; }
    .split-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
    @media (min-width: 1024px) { .split-grid { grid-template-columns: 3fr 1fr; } }

    /* Stats */
    .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
    .stat-card { background: var(--card-bg); padding: 20px; border-radius: 12px; border: 1px solid var(--card-border); display: flex; align-items: center; gap: 16px; }
    .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; }
    .bg-blue { background: #3b82f6; } .bg-green { background: #10b981; } .bg-purple { background: #8b5cf6; }
    .stat-info h3 { margin: 0; font-size: 1.5rem; color: var(--text-primary); }
    .stat-info span { color: var(--text-secondary); font-size: 0.9rem; }

    /* Tables Mobile First */
    .responsive-table-container { overflow-x: auto; }
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { text-align: left; padding: 12px; border-bottom: 2px solid var(--card-border); color: var(--text-secondary); }
    .admin-table td { padding: 12px; border-bottom: 1px solid var(--card-border); color: var(--text-primary); vertical-align: middle; }
    
    @media (max-width: 768px) {
        .admin-table.mobile-stack thead { display: none; }
        .admin-table.mobile-stack tr { display: block; border: 1px solid var(--card-border); margin-bottom: 12px; border-radius: 8px; background: var(--card-bg); padding: 12px; }
        .admin-table.mobile-stack td { display: flex; justify-content: space-between; align-items: center; border: none; padding: 6px 0; }
        .admin-table.mobile-stack td::before { content: attr(data-label); font-weight: 600; color: var(--text-secondary); margin-right: 10px; }
        .admin-table.mobile-stack td.text-right { text-align: right; justify-content: flex-end; }
    }

    /* Actions & Avatars */
    .user-cell { display: flex; align-items: center; gap: 10px; }
    .avatar { width: 32px; height: 32px; background: var(--brand-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; }
    .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
    .justify-end { justify-content: flex-end; }
    
    .btn-icon { background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--text-secondary); padding: 4px; transition: transform 0.2s; }
    .btn-icon:hover { transform: scale(1.1); }
    .text-success { color: var(--success-color); } .text-danger { color: var(--danger-color); } 
    .text-primary { color: var(--brand-color); } .text-warning { color: #f59e0b; }

    /* Badges & Misc */
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
    .role-admin { background: #dbeafe; color: #1e40af; } .role-user { background: #f3f4f6; color: #374151; }
    .status-active { background: #d1fae5; color: #065f46; } .status-pending { background: #fef3c7; color: #92400e; } .status-suspended { background: #fee2e2; color: #b91c1c; }
    .badge-log { font-family: monospace; background: var(--input-bg); padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; }
    .json-box { font-family: monospace; font-size: 0.75rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
    
    /* Sidebar Forms */
    .sidebar-form input, .sidebar-form select { width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 6px; border: 1px solid var(--card-border); background: var(--input-bg); color: var(--text-primary); }
    .full-width { width: 100%; margin-bottom: 8px; }
    .mb-2 { margin-bottom: 8px; }
    
    .dashboard-card { background: var(--card-bg); padding: 24px; border-radius: 12px; border: 1px solid var(--card-border); margin-bottom: 24px; }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .count-badge { background: var(--brand-color); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; }
</style>

<?php if ($activeTab === 'dashboard'): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    fetch('/admin/analytics-data')
        .then(r => r.json())
        .then(data => {
            const canvas = document.getElementById('txCountChart');
            if (!canvas || !window.Chart || !data || !data.labels) return;
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Transactions',
                        data: data.counts,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { display: false } }
                }
            });
        })
        .catch(() => {});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
