<?php
$title = 'Create Group Expense';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../Lib/Security.php';

$current_user_id = $_SESSION['user_id'];
$current_user_name_text = $_SESSION['user_name'];
$current_user_name = htmlspecialchars($current_user_name_text);
?>

<div class="page-header">
    <h1><i class="fa-solid fa-users-viewfinder"></i> Create Group Expense</h1>
    <p class="subtitle">Complete the details below to split a new expense with your friends.</p>
    <div class="expense-tips">
        <span class="tip-chip"><i class="fa-solid fa-wallet"></i> Add the total amount</span>
        <span class="tip-chip"><i class="fa-solid fa-user-check"></i> Choose who joined</span>
        <span class="tip-chip"><i class="fa-solid fa-scale-balanced"></i> Confirm the split</span>
    </div>
</div>

<form id="expense-form" action="/expenses/create" method="post" class="expense-grid">
    <?php echo Security::csrfField(); ?>

    <section class="details-section">
        <div class="dashboard-card expense-card">
            <div class="card-header">
                <div>
                    <h2><i class="fa-solid fa-receipt"></i> Expense Details</h2>
                    <p class="card-subtitle">Start with a clear description and the full amount paid for the expense.</p>
                </div>
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
    </section>

    <aside class="participants-section">
        <div class="dashboard-card expense-card participants-card">
            <div class="card-header">
                <div>
                    <h2><i class="fa-solid fa-users"></i> Participants &amp; Payments</h2>
                    <p class="card-subtitle">Select the people involved and record how much each person actually paid.</p>
                </div>
                <span class="card-badge" id="selected-count-badge">1 participant</span>
            </div>
            <div id="payment-summary-bar" class="payment-summary-bar" style="display:none;"></div>
            <div id="participant-payer-list" class="participant-payer-list">
                <div class="participant-item is-selected" data-pid="<?php echo $current_user_id; ?>" data-participant-name="<?php echo htmlspecialchars($current_user_name_text, ENT_QUOTES); ?>">
                    <label class="participant-label">
                        <input class="participant-checkbox" type="checkbox" name="participants[]" value="<?php echo $current_user_id; ?>" checked>
                        <span class="participant-copy">
                            <span class="participant-name"><?php echo $current_user_name; ?> <span class="participant-tag">You</span></span>
                            <span class="participant-hint">Default payer and participant.</span>
                        </span>
                    </label>
                    <div class="paid-amount-wrapper">
                        <span class="paid-label">৳</span>
                        <input type="number" class="paid-input" name="paid_<?php echo $current_user_id; ?>" value="0" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <?php foreach ($data['friends'] as $friend) : ?>
                    <div class="participant-item is-unselected" data-pid="<?php echo $friend->id; ?>" data-participant-name="<?php echo htmlspecialchars($friend->name, ENT_QUOTES); ?>">
                        <label class="participant-label">
                            <input class="participant-checkbox" type="checkbox" name="participants[]" value="<?php echo $friend->id; ?>">
                            <span class="participant-copy">
                                <span class="participant-name"><?php echo htmlspecialchars($friend->name); ?></span>
                                <span class="participant-hint">Include only if they shared this expense.</span>
                            </span>
                        </label>
                        <div class="paid-amount-wrapper">
                            <span class="paid-label">৳</span>
                            <input type="number" class="paid-input" name="paid_<?php echo $friend->id; ?>" value="0" step="0.01" min="0" placeholder="0.00" disabled>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>

    <section class="split-section">
        <div class="dashboard-card expense-card">
            <div class="card-header">
                <div>
                    <h2><i class="fa-solid fa-chart-pie"></i> Split Method</h2>
                    <p class="card-subtitle">Choose an equal split for speed or switch to custom amounts when each share is different.</p>
                </div>
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
    </section>

    <div class="form-footer">
        <button id="submit-button" class="btn btn-primary disable-on-click" type="submit">
            <i class="fa-solid fa-check-circle"></i> Create Expense
        </button>
    </div>
</form>

<style>
    .page-header h1 { margin: 0 0 4px 0; color: var(--text-primary); }
    .page-header .subtitle { color: var(--text-secondary); margin: 0 0 24px 0; }
    .expense-tips { display: flex; flex-wrap: wrap; gap: 10px; }
    .tip-chip {
        display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 999px;
        background: var(--card-bg); border: 1px solid var(--card-border); color: var(--text-secondary);
        box-shadow: var(--shadow-sm); font-size: 0.92rem; font-weight: 600;
    }
    .tip-chip i { color: var(--brand-color); }

    .expense-grid {
        display: grid;
        grid-template-columns: 1fr;
        grid-template-areas:
            "details"
            "participants"
            "split"
            "footer";
        gap: 24px;
    }
    .details-section { grid-area: details; }
    .participants-section { grid-area: participants; }
    .split-section { grid-area: split; }

    @media (min-width: 1024px) {
        .expense-grid {
            grid-template-columns: minmax(0, 2fr) minmax(320px, 1fr);
            grid-template-areas:
                "details participants"
                "split participants"
                "footer footer";
            align-items: start;
        }
        .participants-section { position: sticky; top: 104px; }
    }

    .expense-card {
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.14), transparent 22%),
            var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 18px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
    }
    .card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--card-border); }
    .card-header h2 { margin: 0; font-size: 1.25rem; color: var(--text-primary); }
    .card-subtitle { margin: 8px 0 0; color: var(--text-secondary); font-size: 0.92rem; line-height: 1.5; }
    .card-badge {
        display: inline-flex; align-items: center; justify-content: center; padding: 8px 12px; min-height: 36px;
        border-radius: 999px; background: var(--brand-color-light); color: var(--brand-color); font-size: 0.85rem; font-weight: 700;
        white-space: nowrap;
    }
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
    .segmented-control { display: flex; width: 100%; background-color: var(--input-bg); border-radius: 12px; padding: 4px; gap: 4px; }
    .sg-btn { flex: 1; padding: 12px 10px; border: none; background-color: transparent; border-radius: 10px; font-weight: 600; cursor: pointer; transition: background-color 0.2s, color 0.2s, transform 0.2s; color: var(--text-secondary); }
    .sg-btn.active { background-color: var(--card-bg); color: var(--brand-color); font-weight: 600; box-shadow: var(--shadow-sm); }

    .participant-payer-list { display: flex; flex-direction: column; gap: 10px; }
    .participant-item {
        display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 14px;
        border-radius: 14px; border: 1px solid var(--card-border); background: rgba(255, 255, 255, 0.16);
        transition: transform 0.2s, background-color 0.2s, border-color 0.2s, box-shadow 0.2s, opacity 0.2s;
    }
    .participant-item:hover { background-color: var(--input-bg); transform: translateY(-1px); }
    .participant-item.is-selected {
        background: var(--brand-color-light);
        border-color: rgba(184, 134, 11, 0.24);
        box-shadow: var(--shadow-sm);
    }
    .participant-item.is-unselected { opacity: 0.82; }
    .participant-label { flex-grow: 1; display: flex; align-items: center; gap: 12px; cursor: pointer; color: var(--text-primary); margin-bottom: 0; }
    .participant-checkbox { width: 1.2em; height: 1.2em; }
    .participant-copy { display: flex; flex-direction: column; gap: 3px; }
    .participant-name { font-weight: 700; line-height: 1.25; }
    .participant-hint { font-size: 0.82rem; color: var(--text-secondary); line-height: 1.35; }
    .participant-tag {
        display: inline-flex; align-items: center; margin-left: 6px; padding: 2px 8px; border-radius: 999px;
        background: rgba(184, 134, 11, 0.14); color: var(--brand-color); font-size: 0.74rem; font-weight: 700;
        vertical-align: middle;
    }

    .paid-amount-wrapper {
        display: flex; align-items: center; gap: 4px; padding: 6px 8px; border-radius: 12px;
        background: var(--card-bg); border: 1px solid var(--card-border);
    }
    .paid-label { color: var(--text-secondary); font-size: 0.9rem; }
    .paid-input {
        width: 96px; padding: 6px 8px; border: 1px solid var(--card-border); border-radius: 8px;
        font-size: 0.95rem; background-color: var(--input-bg); color: var(--text-primary); text-align: right;
    }
    .paid-input:focus { outline: none; border-color: var(--brand-color); box-shadow: 0 0 0 2px var(--brand-color-light); }
    .paid-input:disabled { opacity: 0.55; cursor: not-allowed; }

    .payment-summary-bar {
        margin-bottom: 14px; padding: 12px 14px; border-radius: 14px; font-size: 0.875rem; font-weight: 700;
        transition: all 0.3s; border: 1px solid transparent;
    }
    .payment-summary-bar.valid { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-color); }
    .payment-summary-bar.invalid { background-color: var(--danger-bg); color: var(--danger-text); border-color: var(--danger-color); }
    .payment-summary-bar.partial { background-color: var(--warning-bg); color: var(--warning-text); border-color: var(--warning-color); }

    #custom-shares-container { margin-top: 24px; border-top: 1px solid var(--card-border); padding-top: 24px; }
    #custom-shares-list { display: flex; flex-direction: column; gap: 16px; }
    .share-item {
        display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 16px;
        padding: 14px; border-radius: 14px; background: var(--input-bg); border: 1px solid var(--card-border);
    }
    .share-item label { margin: 0; font-weight: 700; }
    .shares-summary { margin-top: 20px; padding: 14px; border-radius: 14px; text-align: center; font-weight: 700; transition: all 0.3s; border: 1px solid transparent; }
    .shares-summary.valid { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-color); }
    .shares-summary.invalid { background-color: var(--danger-bg); color: var(--danger-text); border-color: var(--danger-color); }
    .shares-summary.zero { background-color: var(--input-bg); color: var(--text-secondary); }

    .form-footer { grid-area: footer; grid-column: 1 / -1; }
    .btn-primary {
        width: 100%; background-color: var(--brand-color); color: white; padding: 14px; font-size: 1.1rem;
        font-weight: 600; border-radius: 8px; border: none; cursor: pointer;
        transition: all 0.2s; box-shadow: var(--shadow-md);
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-primary:hover { background-color: var(--brand-hover); transform: translateY(-2px); }
    .btn-primary:disabled { background-color: var(--disabled-bg); color: var(--disabled-text); cursor: not-allowed; box-shadow: none; transform: none; }

    @media (max-width: 640px) {
        .card-header { flex-direction: column; }
        .card-badge { align-self: flex-start; }
        .participant-item { flex-direction: column; align-items: stretch; }
        .paid-amount-wrapper { width: 100%; }
        .paid-input { width: 100%; }
        .share-item { grid-template-columns: 1fr; gap: 12px; }
        .segmented-control { flex-direction: column; }
    }
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
    const selectedCountBadge = document.getElementById('selected-count-badge');

    function getCheckedParticipants() {
        return Array.from(form.querySelectorAll('.participant-checkbox:checked'))
            .map(cb => {
                const item = cb.closest('.participant-item');
                return {
                    id: cb.value,
                    name: item ? (item.dataset.participantName || '') : ''
                };
            });
    }

    function syncParticipantStates() {
        const checkedParticipants = form.querySelectorAll('.participant-checkbox:checked').length;
        if (selectedCountBadge) {
            selectedCountBadge.textContent = checkedParticipants === 1 ? '1 participant' : `${checkedParticipants} participants`;
        }

        form.querySelectorAll('.participant-item').forEach(item => {
            const checkbox = item.querySelector('.participant-checkbox');
            const paidInput = item.querySelector('.paid-input');
            const isChecked = !!(checkbox && checkbox.checked);

            item.classList.toggle('is-selected', isChecked);
            item.classList.toggle('is-unselected', !isChecked);

            if (paidInput) {
                paidInput.disabled = !isChecked;
                if (!isChecked) {
                    paidInput.value = '0';
                }
            }
        });
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
            syncParticipantStates();
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

    syncParticipantStates();
    initPayments();
    updateView();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
