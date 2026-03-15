<?php
$title = 'Create Group Expense';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../Lib/Security.php';

// Prepare user data for JS
$current_user_id = $_SESSION['user_id'];
$current_user_name = htmlspecialchars($_SESSION['user_name']);
?>

<div class="page-header">
    <h1><i class="fa-solid fa-users-viewfinder"></i> Create Group Expense</h1>
    <p class="subtitle">Complete the details below to split a new expense with your friends.</p>
</div>

<form id="expense-form" action="/expenses/create" method="post" class="expense-grid">
    <?php echo Security::csrfField(); ?>

    <!-- Main Column: "What" and "How" -->
    <div class="main-column">
        <!-- Card 1: What was the expense? -->
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

        <!-- Card 3: How to split? -->
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
            <!-- Custom Shares Section (Appears here when active) -->
            <div id="custom-shares-container" style="display: none;">
                <div id="custom-shares-list"></div>
                <div id="shares-summary" class="shares-summary"></div>
            </div>
        </div>
    </div>

    <!-- Sidebar Column: "Who" -->
    <div class="sidebar-column">
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fa-solid fa-users"></i> Participants & Payer</h2>
            </div>
            <p class="subtitle" style="margin-top:-16px; margin-bottom:16px;">Select who was involved and who paid the bill.</p>
            <div id="participant-payer-list" class="participant-payer-list">
                <!-- Current User -->
                <div class="participant-item">
                    <label class="participant-label">
                        <input class="participant-checkbox" type="checkbox" name="participants[]" value="<?php echo $current_user_id; ?>" checked>
                        <?php echo $current_user_name; ?> (You)
                    </label>
                    <label class="payer-radio">
                        <input type="radio" name="payer_id" value="<?php echo $current_user_id; ?>" checked>
                        <span>Paid</span>
                    </label>
                </div>
                <!-- Friends -->
                <?php foreach ($data['friends'] as $friend) : ?>
                    <div class="participant-item">
                        <label class="participant-label">
                            <input class="participant-checkbox" type="checkbox" name="participants[]" value="<?php echo $friend->id; ?>">
                            <?php echo htmlspecialchars($friend->name); ?>
                        </label>
                        <label class="payer-radio">
                            <input type="radio" name="payer_id" value="<?php echo $friend->id; ?>">
                            <span>Paid</span>
                        </label>
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
    /* Use variables from the global theme */
    .page-header h1 { margin: 0 0 4px 0; color: var(--text-primary); }
    .page-header .subtitle { color: var(--text-secondary); margin: 0 0 24px 0; }

    /* Mobile-First Layout: Single column */
    .expense-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
    .main-column { display: flex; flex-direction: column; gap: 24px; }

    /* Desktop Layout: Two columns */
    @media (min-width: 1024px) {
        .expense-grid { grid-template-columns: 2fr 1fr; }
    }

    /* Consistent Card & Form Styling */
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
    
    /* Interactive Participant/Payer List */
    .participant-payer-list { display: flex; flex-direction: column; gap: 8px; }
    .participant-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-radius: 8px; transition: background-color 0.2s; }
    .participant-item:hover { background-color: var(--input-bg); }
    .participant-label { flex-grow: 1; display: flex; align-items: center; gap: 12px; cursor: pointer; color: var(--text-primary); }
    .participant-checkbox { width: 1.2em; height: 1.2em; }

    /* Custom Radio Button for Payer */
    .payer-radio { position: relative; cursor: pointer; }
    .payer-radio input { position: absolute; opacity: 0; }
    .payer-radio span {
        padding: 6px 12px; border: 1px solid var(--card-border); border-radius: 16px;
        font-size: 0.8rem; font-weight: 500; color: var(--text-secondary);
        transition: all 0.2s;
    }
    .payer-radio input:checked + span {
        background-color: var(--brand-color); color: white; border-color: var(--brand-color); font-weight: 600;
    }

    /* Custom Shares Styling */
    #custom-shares-container { margin-top: 24px; border-top: 1px solid var(--card-border); padding-top: 24px; }
    #custom-shares-list { display: flex; flex-direction: column; gap: 16px; }
    .share-item { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 16px; }
    .shares-summary { margin-top: 20px; padding: 12px; border-radius: 8px; text-align: center; font-weight: 600; transition: all 0.3s; border: 1px solid transparent; }
    .shares-summary.valid { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-color); }
    .shares-summary.invalid { background-color: var(--danger-bg); color: var(--danger-text); border-color: var(--danger-color); }
    .shares-summary.zero { background-color: var(--input-bg); color: var(--text-secondary); }

    .form-footer { grid-column: 1 / -1; } /* Makes footer span all columns in grid */
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
    // --- ELEMENT SELECTORS ---
    const form = document.getElementById('expense-form');
    const totalAmountInput = document.getElementById('total_amount');
    const participantItems = form.querySelectorAll('.participant-item');
    const customSharesContainer = document.getElementById('custom-shares-container');
    const customSharesList = document.getElementById('custom-shares-list');
    const sharesSummary = document.getElementById('shares-summary');
    const submitButton = document.getElementById('submit-button');
    const splitModeControl = form.querySelector('.segmented-control');
    const splitModeInput = document.getElementById('split_mode_input');

    // --- FUNCTIONS ---
    function handlePayerChange() {
        const selectedPayerId = form.querySelector('input[name="payer_id"]:checked').value;
        participantItems.forEach(item => {
            const checkbox = item.querySelector('.participant-checkbox');
            // If this item's user is the payer, check and disable their participant checkbox
            if (checkbox.value === selectedPayerId) {
                checkbox.checked = true;
                checkbox.disabled = true;
            } else {
                checkbox.disabled = false;
            }
        });
        // If in custom mode, we need to rebuild the shares list
        if (splitModeInput.value === 'custom') {
            rebuildCustomSharesList();
        }
    }

    function updateView() {
        const isCustomMode = splitModeInput.value === 'custom';
        customSharesContainer.style.display = isCustomMode ? 'block' : 'none';

        if (isCustomMode) {
            rebuildCustomSharesList();
        } else {
            submitButton.disabled = false;
        }
    }

    function rebuildCustomSharesList() {
        customSharesList.innerHTML = '';
        const selectedParticipants = Array.from(form.querySelectorAll('.participant-checkbox:checked'))
            .map(cb => ({
                id: cb.value,
                name: cb.closest('.participant-label').textContent.trim()
            }));

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
        validateShares(); // Initial validation
    }

    function validateShares() {
        const totalAmount = parseFloat(totalAmountInput.value) || 0;
        let sharesTotal = 0;
        customSharesList.querySelectorAll('.share-input').forEach(input => { sharesTotal += parseFloat(input.value) || 0; });
        const remaining = totalAmount - sharesTotal;

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
            submitButton.disabled = false;
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

    // --- EVENT LISTENERS ---
    splitModeControl.querySelectorAll('.sg-btn').forEach(button => {
        button.addEventListener('click', () => {
            splitModeControl.querySelector('.active').classList.remove('active');
            button.classList.add('active');
            splitModeInput.value = button.dataset.value;
            updateView();
        });
    });

    form.querySelectorAll('.participant-checkbox').forEach(cb => {
        cb.addEventListener('change', () => { if (splitModeInput.value === 'custom') rebuildCustomSharesList(); });
    });

    form.querySelectorAll('input[name="payer_id"]').forEach(radio => {
        radio.addEventListener('change', handlePayerChange);
    });

    totalAmountInput.addEventListener('input', () => { if (splitModeInput.value === 'custom') validateShares(); });

    // --- INITIALIZATION ---
    handlePayerChange(); // Run once on load to set the initial state
    updateView();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>