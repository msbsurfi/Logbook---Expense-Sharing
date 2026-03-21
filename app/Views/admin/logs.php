<?php
$title='Admin Logs';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <h1><i class="fa-solid fa-list-check"></i> Audit Logs</h1>
    <p class="subtitle">History of administrative actions and impersonations.</p>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h2>Action Logs (<?php echo $data['total_actions']; ?>)</h2>
        <small class="text-secondary">Page <?php echo $data['page']; ?></small>
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
                    <td data-label="Admin">
                        <strong><?php echo htmlspecialchars($a->admin_name ?? 'ID:'.$a->admin_id); ?></strong>
                    </td>
                    <td data-label="Action">
                        <span class="badge-log"><?php echo htmlspecialchars($a->action); ?></span>
                    </td>
                    <td data-label="Details" class="meta-cell">
                        <?php
                          if ($a->meta) {
                              $decoded = json_decode($a->meta, true);
                              
                              echo '<div class="json-box">'.htmlspecialchars(json_encode($decoded)).'</div>';
                          } else {
                              echo '<span class="text-muted">-</span>';
                          }
                        ?>
                    </td>
                    <td data-label="Time" class="text-nowrap">
                        <?php echo date('M j, H:i', strtotime($a->created_at)); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    
    <div class="pagination mt-4">
        <?php if($data['page'] > 1): ?>
            <a href="/admin/logs?page=<?php echo $data['page']-1; ?>" class="btn secondary">Previous</a>
        <?php endif; ?>
        <?php if($data['page'] < $data['total_pages']): ?>
            <a href="/admin/logs?page=<?php echo $data['page']+1; ?>" class="btn secondary">Next</a>
        <?php endif; ?>
    </div>
</div>

<style>
    .badge-log { background: var(--input-bg); border: 1px solid var(--card-border); padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-family: monospace; }
    .json-box { font-family: monospace; font-size: 0.75rem; color: var(--text-secondary); max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .json-box:hover { white-space: normal; overflow: visible; background: var(--input-bg); position: absolute; padding: 8px; border: 1px solid var(--card-border); z-index: 10; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .meta-cell { position: relative; }
    
    @media(max-width: 768px) {
        .json-box { max-width: 100%; white-space: normal; }
        .meta-cell { position: static; }
    }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>