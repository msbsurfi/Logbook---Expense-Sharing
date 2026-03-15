<?php
$title = 'Settle With ' . htmlspecialchars($data['friend']->name);
require_once __DIR__ . '/../layouts/header.php'; // Your modern, theme-aware header
require_once __DIR__ . '/../../Lib/Security.php';

// Pre-calculate totals for the summary display
$total_you_owe = 0;
$total_owed_to_you = 0;
if (!empty($data['transactions'])) {
    foreach ($data['transactions'] as $txn) {
        if ($txn->borrower_id == $_SESSION['user_id']) {
            $total_you_owe += $txn->amount;
        } else {
            $total_owed_to_you += $txn->amount;
        }
    }
}
$net_balance = $total_owed_to_you - $total_you_owe;
?>

<div class="page-header">
    <h1>Settle Up With <?php echo htmlspecialchars($data['friend']->name); ?></h1>
    <p class="subtitle">Select transactions below to mark them as paid.</p>
</div>

<!-- Financial Summary Card -->
<div class="dashboard-card summary-card">
    <div class="summary-item">
        <span class="label">Total You Owe</span>
        <strong class="value text-danger">৳<?php echo number_format($total_you_owe, 2); ?></strong>
    </div>
    <div class="summary-item">
        <span class="label">Total They Owe</span>
        <strong class="value text-success">৳<?php echo number_format($total_owed_to_you, 2); ?></strong>
    </div>
    <div class="summary-item net-balance">
        <span class="label">Net Balance</span>
        <strong class="value <?php echo $net_balance < 0 ? 'text-danger' : 'text-success'; ?>">
            ৳<?php echo number_format(abs($net_balance), 2); ?>
            <small><?php echo $net_balance < 0 ? '(You Owe)' : '(Owed To You)'; ?></small>
        </strong>
    </div>
</div>

<form id="settle-form" action="/transactions/settle" method="post" class="dashboard-card">
    <?php echo Security::csrfField(); ?>
    <input type="hidden" name="friend_id" value="<?php echo $data['friend']->id; ?>">

    <div class="card-header">
        <h2><i class="fa-solid fa-list-check"></i> Unpaid Transactions</h2>
        <?php if (!empty($data['transactions'])) : ?>
            <label class="select-all-label">
                <input type="checkbox" id="select-all-checkbox"> Select All
            </label>
        <?php endif; ?>
    </div>
    
    <div class="transaction-list">
        <?php if (!empty($data['transactions'])) : foreach ($data['transactions'] as $txn) :
            $is_you_owe = $txn->borrower_id == $_SESSION['user_id'];
        ?>
            <div class="transaction-item" data-amount="<?php echo $is_you_owe ? -$txn->amount : $txn->amount; ?>">
                <label class="custom-checkbox">
                    <input type="checkbox" name="txn_ids[]" value="<?php echo $txn->id; ?>">
                    <span class="checkmark"></span>
                </label>
                <div class="transaction-details">
                    <strong><?php echo htmlspecialchars($txn->description); ?></strong>
                    <span><?php echo date("M j, Y", strtotime($txn->created_at)); ?></span>
                </div>
                <div class="transaction-amount <?php echo $is_you_owe ? 'text-danger' : 'text-success'; ?>">
                    ৳<?php echo number_format($txn->amount, 2); ?>
                    <small><?php echo $is_you_owe ? '(You owe)' : '(They owe you)'; ?></small>
                </div>
            </div>
        <?php endforeach; else : ?>
            <div class="empty-state">
                <i class="fa-solid fa-handshake"></i>
                <p>Nothing to settle!</p>
                <span>You have no outstanding transactions with <?php echo htmlspecialchars($data['friend']->name); ?>.</span>
            </div>
        <?php endif; ?>
    </div>
</form>

<!-- Sticky Action Bar -->
<?php if (!empty($data['transactions'])) : ?>
<div class="settle-summary-bar">
    <div class="container summary-content">
        <div class="selection-info">
            <strong id="selected-count">No items selected</strong>
            <span id="selected-total-display"></span>
        </div>
        <button form="settle-form" id="settle-submit-btn" class="btn btn-primary disable-on-click" type="submit" disabled>
            Settle Selected
        </button>
    </div>
</div>
<?php endif; ?>


<style>
    :root { text-danger-color: var(--danger-color); text-success-color: var(--success-color); }
    .dark-theme { text-danger-color: var(--danger-text); text-success-color: var(--success-text); }
    .text-danger { color: var(--text-danger-color); }
    .text-success { color: var(--text-success-color); }

    .page-header h1 { margin: 0 0 4px 0; color: var(--text-primary); }
    .page-header .subtitle { color: var(--text-secondary); margin: 0 0 24px 0; }
    
    .dashboard-card { background-color: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 24px; margin-bottom: 24px; }
    .summary-card { display: flex; flex-wrap: wrap; justify-content: space-around; gap: 20px; text-align: center; }
    .summary-item .label { display: block; color: var(--text-secondary); margin-bottom: 8px; }
    .summary-item .value { font-size: 1.75rem; font-weight: 600; }
    .net-balance .value { font-size: 2rem; }
    .net-balance small { display: block; font-size: 0.9rem; font-weight: 500; color: var(--text-secondary); }

    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--card-border); }
    .card-header h2 { margin: 0; font-size: 1.25rem; color: var(--text-primary); }
    .select-all-label { display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-secondary); font-weight: 500; }
    
    .transaction-list { display: flex; flex-direction: column; }
    .transaction-item {
        display: flex; align-items: center; gap: 16px; padding: 12px; border-radius: 8px;
        border: 1px solid var(--card-border); cursor: pointer; transition: background-color 0.2s, border-color 0.2s;
        margin-bottom: 12px;
    }
    .transaction-item:hover { background-color: var(--input-bg); }
    .transaction-item.selected { background-color: var(--brand-color-light); border-color: var(--brand-color); } /* Define --brand-color-light in your header */
    .transaction-details { flex-grow: 1; }
    .transaction-details strong { display: block; color: var(--text-primary); }
    .transaction-details span { color: var(--text-secondary); font-size: 0.9rem; }
    .transaction-amount { text-align: right; font-weight: 600; }
    .transaction-amount small { display: block; font-weight: 400; font-size: 0.8rem; }

    /* Custom Checkbox */
    .custom-checkbox { position: relative; padding-left: 30px; }
    .custom-checkbox input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
    .custom-checkbox .checkmark {
        position: absolute; top: 50%; left: 0; transform: translateY(-50%); height: 20px; width: 20px;
        background-color: var(--input-bg); border: 1px solid var(--card-border); border-radius: 4px;
    }
    .custom-checkbox input:checked ~ .checkmark { background-color: var(--brand-color); border-color: var(--brand-color); }
    .custom-checkbox .checkmark:after { content: ""; position: absolute; display: none; left: 6px; top: 2px; width: 5px; height: 10px; border: solid white; border-width: 0 3px 3px 0; transform: rotate(45deg); }
    .custom-checkbox input:checked ~ .checkmark:after { display: block; }

    /* Sticky Action Bar */
    .settle-summary-bar {
        position: sticky; bottom: 0; left: 0; width: 100%;
        background-color: var(--card-bg); box-shadow: 0 -4px 12px rgba(0,0,0,0.1);
        padding: 16px 0; border-top: 1px solid var(--card-border); z-index: 10;
    }
    .dark-theme .settle-summary-bar { box-shadow: 0 -4px 12px rgba(0,0,0,0.4); }
    .summary-content { display: flex; justify-content: space-between; align-items: center; }
    .selection-info strong { display: block; color: var(--text-primary); font-size: 1.1rem; }
    .selection-info span { color: var(--text-secondary); }
    #settle-submit-btn { padding: 12px 24px; font-size: 1rem; }
    
    .empty-state { text-align: center; padding: 40px 20px; }
    .empty-state i { font-size: 3rem; color: var(--text-secondary); }
    .empty-state p { font-size: 1.2rem; font-weight: 600; margin: 16px 0 4px; color: var(--text-primary); }
    .empty-state span { color: var(--text-secondary); }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('settle-form');
    if (!form) return; // Exit if there are no transactions to settle

    const transactionItems = form.querySelectorAll('.transaction-item');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const summaryBar = document.querySelector('.settle-summary-bar');
    const selectedCountEl = document.getElementById('selected-count');
    const selectedTotalEl = document.getElementById('selected-total-display');
    const submitBtn = document.getElementById('settle-submit-btn');

    function updateSummary() {
        let selectedCount = 0;
        let netSelectedAmount = 0;

        transactionItems.forEach(item => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (checkbox.checked) {
                selectedCount++;
                netSelectedAmount += parseFloat(item.dataset.amount);
                item.style.backgroundColor = 'var(--nav-link-hover-bg)'; // Visual feedback
            } else {
                item.style.backgroundColor = 'transparent';
            }
        });

        if (selectedCount > 0) {
            summaryBar.style.display = 'block';
            selectedCountEl.textContent = `${selectedCount} item${selectedCount > 1 ? 's' : ''} selected`;
            
            const actionText = netSelectedAmount > 0 ? 'Net you receive' : 'Net you pay';
            selectedTotalEl.textContent = `${actionText}: ৳${Math.abs(netSelectedAmount).toFixed(2)}`;
            
            submitBtn.disabled = false;
            submitBtn.textContent = `Settle ${selectedCount} Item${selectedCount > 1 ? 's' : ''}`;
        } else {
            summaryBar.style.display = 'none';
            selectedCountEl.textContent = 'No items selected';
            selectedTotalEl.textContent = '';
            submitBtn.disabled = true;
            submitBtn.textContent = 'Settle Selected';
        }
    }

    // Make the entire item clickable
    transactionItems.forEach(item => {
        item.addEventListener('click', (e) => {
            // Don't interfere with clicks directly on the checkbox
            if (e.target.type !== 'checkbox') {
                const checkbox = item.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
            }
            updateSummary();
        });
    });

    // Handle "Select All" functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            transactionItems.forEach(item => {
                item.querySelector('input[type="checkbox"]').checked = selectAllCheckbox.checked;
            });
            updateSummary();
        });
    }

    // Initial state
    if (summaryBar) {
      summaryBar.style.display = 'none';
    }
    updateSummary();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>