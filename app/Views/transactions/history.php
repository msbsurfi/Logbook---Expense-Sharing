<?php
$title = 'Transaction History';
require_once __DIR__ . '/../layouts/header.php'; // Your modern, theme-aware header
?>

<div class="page-header">
    <h1><i class="fa-solid fa-clock-rotate-left"></i> Transaction History</h1>
    <p class="subtitle">A complete record of all your transactions, both created and settled.</p>
</div>

<div class="dashboard-card">
    <div class="card-header">
        <h2><i class="fa-solid fa-table-list"></i> All Transactions</h2>
        <div class="search-bar">
            <div class="input-with-icon">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="history-search-input" placeholder="Search history...">
            </div>
        </div>
    </div>
    
    <!-- This wrapper enables horizontal scrolling on mobile -->
    <div class="table-wrapper">
        <table id="history-table" class="history-table" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Date Created</th>
                    <th>Created By</th>
                    <th>Settled By</th>
                    <th>Date Settled</th>
                    <th>Expense Group</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data['history'])) : foreach ($data['history'] as $t) : ?>
                    <tr>
                        <td><?php echo $t->id; ?></td>
                        <td><?php echo htmlspecialchars($t->description); ?></td>
                        <td>৳<?php echo number_format($t->amount, 2); ?></td>
                        <td>
                            <?php if ($t->lender_id == $_SESSION['user_id']) : ?>
                                <span class="badge type-owe-you">Owed To You</span>
                            <?php else : ?>
                                <span class="badge type-you-owe">You Owe</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($t->status === 'paid') : ?>
                                <span class="badge status-paid">Paid</span>
                            <?php else : ?>
                                <span class="badge status-unpaid">Unpaid</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date("M j, Y, g:i a", strtotime($t->created_at)); ?></td>
                        <td><?php echo htmlspecialchars($t->created_by_name ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($t->settled_by_name ?? '-'); ?></td>
                        <td><?php echo $t->settled_at ? date("M j, Y, g:i a", strtotime($t->settled_at)) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($t->expense_description ?? '-'); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php if (empty($data['history'])) : ?>
            <div class="empty-state">
                <i class="fa-solid fa-receipt"></i>
                <p>No transactions found</p>
                <span>Your history will appear here once you add transactions.</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Use variables from the global theme */
    :root {
        --warning-bg: #fef9c3;
        --warning-text: #854d0e;
        --warning-border: #facc15;
    }
    [data-theme="dark"] {
        --warning-bg: #42310b;
        --warning-text: #fef08a;
    }

    .page-header h1 { margin: 0 0 4px 0; color: var(--text-primary); }
    .page-header .subtitle { color: var(--text-secondary); margin: 0 0 24px 0; }

    .dashboard-card { background-color: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 24px; }
    .card-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
    .card-header h2 { margin: 0; font-size: 1.25rem; color: var(--text-primary); }
    .search-bar .input-with-icon { position: relative; }
    .search-bar i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
    #history-search-input {
        width: 100%; min-width: 250px; padding: 10px 10px 10px 40px; border: 1px solid var(--card-border); border-radius: 8px; font-size: 0.95rem;
        background-color: var(--input-bg); color: var(--text-primary);
    }
    
    /* Styles for Horizontal Scrolling Table */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #history-table { border-collapse: collapse !important; }
    #history-table thead th {
        color: var(--text-secondary); text-align: left; white-space: nowrap;
        user-select: none;
    }
    #history-table tbody td {
        color: var(--text-primary); padding: 12px 16px !important; border-top: 1px solid var(--card-border);
        white-space: nowrap;
    }

    /* Badge Styles */
    .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; }
    .badge.status-paid { background-color: var(--success-bg); color: var(--success-text); }
    .badge.status-unpaid { background-color: var(--warning-bg); color: var(--warning-text); }
    .badge.type-owe-you { background-color: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-color); }
    .badge.type-you-owe { background-color: var(--danger-bg); color: var(--danger-text); border: 1px solid var(--danger-color); }

    .empty-state { text-align: center; padding: 40px 20px; }
    .empty-state i { font-size: 3rem; color: var(--text-secondary); }
    .empty-state p { font-size: 1.2rem; font-weight: 600; margin: 16px 0 4px; color: var(--text-primary); }
    .empty-state span { color: var(--text-secondary); }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('history-search-input');
    const rows = Array.from(document.querySelectorAll('#history-table tbody tr'));

    if (!searchInput || rows.length === 0) {
        return;
    }

    searchInput.addEventListener('input', () => {
        const term = searchInput.value.trim().toLowerCase();

        rows.forEach((row) => {
            const visible = term === '' || row.textContent.toLowerCase().includes(term);
            row.style.display = visible ? '' : 'none';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
