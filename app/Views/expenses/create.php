<?php
$title = 'Create Group Expense';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../Lib/Security.php';

$current_user_id = $_SESSION['user_id'];
$current_user_name = htmlspecialchars($_SESSION['user_name']);
?>

<div class="page-header">
    <h1><i class="fa-solid fa-users-viewfinder"></i> Create Group Expense</h1>
    <p class="subtitle">Complete the details below to split a new expense with your friends.</p>
</div>

<form id="expense-form" action="/expenses/create" method="post" class="expense-grid">
    <?php echo Security::csrfField(); ?>

    <div class="main-column">
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fa-solid fa-receipt"></i> Expense Details</h2>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-pencil"></i>
                    <input type="text" id="description" name="description" placeholder="e.g., Team Dinner" required>
                </div>
            </div>
            <div class="form-group">
                <label for="total_amount">Total Amount</label>
                <div class="input-with-icon">
                    <i>৳</i>
                    <input type="number" id="total_amount" name="total_amount" step="0.01" placeholder="2500.00" required>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fa-solid fa-chart-pie"></i> Split Method</h2>
            </div>
            <div class="form-group">
                <div class="segmented-control">
                    <button type="button" class="sg-btn active" data-value="equal">Split Equally</button>
                    <button type="button" class="sg-btn" data-value="custom">By Custom Amounts</button>
                </div>
                <input type="hidden" name="split_mode" id="split_mode_input" value="equal">
            </div>
            <div id="custom-shares-container" style="display: none;">
                <div id="custom-shares-list"></div>
                <div id="shares-summary" class="shares-summary"></div>
            </div>
        </div>
    </div>

    <div class="sidebar-column">
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fa-solid fa-users"></i> Participants &amp; Payments</h2>
            </div>
            <p class="subtitle" style="margin-top:-16px; margin-bottom:12px;">Select participants and enter how much each person paid.</p>
            <div id="payment-summary-bar" class="payment-summary-bar" style="display:none;"></div>
            <div id="participant-payer-list" class="participant-payer-list">
                <div class="participant-item" data-pid="<?php echo $current_user_id; ?>">
                    <label class="participant-label">
                        <input class="participant-checkbox" type="checkbox" name="participants[]" value="<?php echo $current_user_id; ?>" checked>
                        <?php echo $current_user_name; ?> (You)
                    </label>
                    <div class="paid-amount-wrapper">
                        <span class="paid-label">৳</span>
                        <input type="number" class="paid-input" name="paid_<?php echo $current_user_id; ?>" value="0" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <?php foreach ($data['friends'] as $friend) : ?>
                    <div class="participant-item" data-pid="<?php echo $friend->id; ?>">
                        <label class="participant-label">
                            <input class="participant-checkbox" type="checkbox" name="participants[]" value="<?php echo $friend->id; ?>">
                            <?php echo htmlspecialchars($friend->name); ?>
                        </label>
                        <div class="paid-amount-wrapper">
                            <span class="paid-label">৳</span>
                            <input type="number" class="paid-input" name="paid_<?php echo $friend->id; ?>" value="0" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="form-footer">
        <button id="submit-button" class="btn btn-primary disable-on-click" type="submit">
            <i class="fa-solid fa-check-circle"></i> Create Expense
        </button>
    </div>
</form>

<style>
    .page-header h1 { margin: 0 0 4px 0; color: var(--text-primary); }
    .page-header .subtitle { color: var(--text-secondary); margin: 0 0 24px 0; }

    .expense-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
    .main-column { display: flex; flex-direction: column; gap: 24px; }

    @media (min-width: 1024px) {
        .expense-grid { grid-template-columns: 2fr 1fr; }
    }

    .dashboard-card { background-color: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 24px; }
    .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--card-border); }
    .card-header h2 { margin: 0; font-size: 1.25rem; color: var(--text-primary); }
    .form-group { margin-bottom: 20px; }
    .form-group:last-child { margin-bottom: 0; }
    .form-group label { display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-primary); }
    .input-with-icon { position: relative; }
    .input-with-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
    .input-with-icon input {
        width: 100%; padding: 12px 12px 12px 40px; border: 1px solid var(--card-border); border-radius: 8px; font-size: 1rem;
        background-color: var(--input-bg); color: var(--text-primary);
    }
    .input-with-icon input[type="number"] { padding-left: 30px; }
    .segmented-control { display: flex; width: 100%; background-color: var(--input-bg); border-radius: 8px; padding: 4px; }
    .sg-btn { flex: 1; padding: 10px; border: none; background-color: transparent; border-radius: 6px; font-weight: 500; cursor: pointer; transition: background-color 0.2s, color 0.2s; color: var(--text-secondary); }
    .sg-btn.active { background-color: var(--card-bg); color: var(--brand-color); font-weight: 600; box-shadow: var(--shadow-sm); }

    .participant-payer-list { display: flex; flex-direction: column; gap: 8px; }
    .participant-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-radius: 8px; transition: background-color 0.2s; }
    .participant-item:hover { background-color: var(--input-bg); }
    .participant-label { flex-grow: 1; display: flex; align-items: center; gap: 12px; cursor: pointer; color: var(--text-primary); }
    .participant-checkbox { width: 1.2em; height: 1.2em; }

    .paid-amount-wrapper { display: flex; align-items: center; gap: 4px; }
    .paid-label { color: var(--text-secondary); font-size: 0.9rem; }
    .paid-input {
        width: 90px; padding: 6px 8px; border: 1px solid var(--card-border); border-radius: 8px;
        font-size: 0.9rem; background-color: var(--input-bg); color: var(--text-primary); text-align: right;
    }
    .paid-input:focus { outline: none; border-color: var(--brand-color); box-shadow: 0 0 0 2px var(--brand-color-light); }

    .payment-summary-bar {
        margin-bottom: 12px; padding: 10px 14px; border-radius: 8px; font-size: 0.875rem; font-weight: 600;
        transition: all 0.3s; border: 1px solid transparent;
    }
    .payment-summary-bar.valid { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-color); }
    .payment-summary-bar.invalid { background-color: var(--danger-bg); color: var(--danger-text); border-color: var(--danger-color); }
    .payment-summary-bar.partial { background-color: var(--warning-bg); color: var(--warning-text); border-color: var(--warning-color); }

    #custom-shares-container { margin-top: 24px; border-top: 1px solid var(--card-border); padding-top: 24px; }
    #custom-shares-list { display: flex; flex-direction: column; gap: 16px; }
    .share-item { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 16px; }
    .shares-summary { margin-top: 20px; padding: 12px; border-radius: 8px; text-align: center; font-weight: 600; transition: all 0.3s; border: 1px solid transparent; }
    .shares-summary.valid { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-color); }
    .shares-summary.invalid { background-color: var(--danger-bg); color: var(--danger-text); border-color: var(--danger-color); }
    .shares-summary.zero { background-color: var(--input-bg); color: var(--text-secondary); }

    .form-footer { grid-column: 1 / -1; }
    .btn-primary {
        width: 100%; background-color: var(--brand-color); color: white; padding: 14px; font-size: 1.1rem;
        font-weight: 600; border-radius: 8px; border: none; cursor: pointer;
        transition: all 0.2s; box-shadow: var(--shadow-md);
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-primary:hover { background-color: var(--brand-hover); transform: translateY(-2px); }
    .btn-primary:disabled { background-color: var(--disabled-bg); color: var(--disabled-text); cursor: not-allowed; box-shadow: none; transform: none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('expense-form');
    const totalAmountInput = document.getElementById('total_amount');
    const customSharesContainer = document.getElementById('custom-shares-container');
    const customSharesList = document.getElementById('custom-shares-list');
    const sharesSummary = document.getElementById('shares-summary');
    const submitButton = document.getElementById('submit-button');
    const splitModeControl = form.querySelector('.segmented-control');
    const splitModeInput = document.getElementById('split_mode_input');
    const paymentSummaryBar = document.getElementById('payment-summary-bar');

    function getCheckedParticipants() {
        return Array.from(form.querySelectorAll('.participant-checkbox:checked'))
            .map(cb => ({
                id: cb.value,
                name: cb.closest('.participant-label').textContent.trim()
            }));
    }

    function validatePayments() {
        const totalAmount = parseFloat(totalAmountInput.value) || 0;
        if (totalAmount === 0) {
            paymentSummaryBar.style.display = 'none';
            return false;
        }

        let paidSum = 0;
        form.querySelectorAll('.paid-input').forEach(inp => {
            const item = inp.closest('.participant-item');
            const cb = item.querySelector('.participant-checkbox');
            if (cb && cb.checked) {
                paidSum += parseFloat(inp.value) || 0;
            }
        });

        const remaining = totalAmount - paidSum;
        paymentSummaryBar.style.display = 'block';

        if (Math.abs(remaining) < 0.01) {
            paymentSummaryBar.textContent = '✅ Payments match total amount!';
            paymentSummaryBar.className = 'payment-summary-bar valid';
            return true;
        } else if (remaining > 0) {
            paymentSummaryBar.textContent = '৳' + remaining.toFixed(2) + ' still unassigned';
            paymentSummaryBar.className = 'payment-summary-bar partial';
            return false;
        } else {
            paymentSummaryBar.textContent = '❌ Over by ৳' + Math.abs(remaining).toFixed(2);
            paymentSummaryBar.className = 'payment-summary-bar invalid';
            return false;
        }
    }

    function initPayments() {
        const totalAmount = parseFloat(totalAmountInput.value) || 0;
        const checkedItems = form.querySelectorAll('.participant-checkbox:checked');
        if (checkedItems.length === 0 || totalAmount === 0) return;

        const firstChecked = checkedItems[0];
        const firstItem = firstChecked.closest('.participant-item');
        const firstPaidInput = firstItem.querySelector('.paid-input');

        form.querySelectorAll('.participant-item').forEach(item => {
            const paidInput = item.querySelector('.paid-input');
            paidInput.value = '0';
        });

        if (firstPaidInput) firstPaidInput.value = totalAmount.toFixed(2);
        validatePayments();
    }

    function updateView() {
        const isCustomMode = splitModeInput.value === 'custom';
        customSharesContainer.style.display = isCustomMode ? 'block' : 'none';

        if (isCustomMode) {
            rebuildCustomSharesList();
        } else {
            updateSubmitButton();
        }
    }

    function updateSubmitButton() {
        const paymentsOk = validatePayments();
        const isCustomMode = splitModeInput.value === 'custom';
        if (!isCustomMode) {
            submitButton.disabled = !paymentsOk;
        }
    }

    function rebuildCustomSharesList() {
        customSharesList.innerHTML = '';
        const selectedParticipants = getCheckedParticipants();

        selectedParticipants.forEach(p => {
            const row = document.createElement('div');
            row.className = 'share-item';
            row.innerHTML = `
                <label>${p.name}</label>
                <div class="input-with-icon">
                    <i>৳</i>
                    <input type="number" class="share-input" step="0.01" name="share_${p.id}" placeholder="0.00" required>
                </div>`;
            customSharesList.appendChild(row);
        });

        customSharesList.querySelectorAll('.share-input').forEach(input => input.addEventListener('input', validateShares));
        validateShares();
    }

    function validateShares() {
        const totalAmount = parseFloat(totalAmountInput.value) || 0;
        let sharesTotal = 0;
        customSharesList.querySelectorAll('.share-input').forEach(input => { sharesTotal += parseFloat(input.value) || 0; });
        const remaining = totalAmount - sharesTotal;
        const paymentsOk = validatePayments();

        sharesSummary.classList.remove('valid', 'invalid', 'zero');

        if (totalAmount === 0) {
            sharesSummary.textContent = 'Please enter a total amount first.';
            sharesSummary.classList.add('zero');
            submitButton.disabled = true;
            return;
        }

        if (Math.abs(remaining) < 0.001) {
            sharesSummary.textContent = '✅ Shares match the total amount!';
            sharesSummary.classList.add('valid');
            submitButton.disabled = !paymentsOk;
        } else if (remaining < 0) {
            sharesSummary.textContent = `❌ Over by ৳${Math.abs(remaining).toFixed(2)}`;
            sharesSummary.classList.add('invalid');
            submitButton.disabled = true;
        } else {
            sharesSummary.textContent = `৳${remaining.toFixed(2)} remaining to assign`;
            sharesSummary.classList.add('zero');
            submitButton.disabled = true;
        }
    }

    splitModeControl.querySelectorAll('.sg-btn').forEach(button => {
        button.addEventListener('click', () => {
            splitModeControl.querySelector('.active').classList.remove('active');
            button.classList.add('active');
            splitModeInput.value = button.dataset.value;
            updateView();
        });
    });

    form.querySelectorAll('.participant-checkbox').forEach(cb => {
        cb.addEventListener('change', () => {
            if (splitModeInput.value === 'custom') rebuildCustomSharesList();
            validatePayments();
            updateSubmitButton();
        });
    });

    form.querySelectorAll('.paid-input').forEach(inp => {
        inp.addEventListener('input', () => {
            validatePayments();
            updateSubmitButton();
            if (splitModeInput.value === 'custom') validateShares();
        });
    });

    totalAmountInput.addEventListener('input', () => {
        initPayments();
        if (splitModeInput.value === 'custom') {
            rebuildCustomSharesList();
        } else {
            updateSubmitButton();
        }
    });

    initPayments();
    updateView();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
