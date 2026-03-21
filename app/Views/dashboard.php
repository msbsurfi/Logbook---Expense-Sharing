<?php
$title = 'Dashboard';
require_once __DIR__ . '/layouts/header.php'; // Your new, modern header
require_once __DIR__ . '/../Lib/Security.php';
?>

<div class="dashboard-header">
    <div>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!</h1>
        <p class="subtitle">Here's a summary of your shared expenses.</p>
    </div>
    <div class="profile-code-box">
        <span>Profile Code</span>
        <strong><?php echo htmlspecialchars($_SESSION['user_profile_code'] ?? 'N/A'); ?></strong>
    </div>
</div>

<!-- Summary Boxes -->
<div class="summary-grid">
    <div class="summary-box">
        <div class="summary-icon danger"><i class="fa-solid fa-arrow-trend-down"></i></div>
        <div>
            <div class="summary-label">You Owe</div>
            <!-- Use the null coalescing operator (??) to provide a default value -->
            <div class="summary-value danger-text">৳<?php echo number_format($data['totalOwedByYou'] ?? 0, 2); ?></div>
        </div>
    </div>
    <div class="summary-box">
        <div class="summary-icon success"><i class="fa-solid fa-arrow-trend-up"></i></div>
        <div>
            <div class="summary-label">Owed To You</div>
            <div class="summary-value success-text">৳<?php echo number_format($data['totalOwedToYou'] ?? 0, 2); ?></div>
        </div>
    </div>
</div>

<!-- Main Dashboard Layout -->
<div class="dashboard-grid">
    <!-- Left Column: Main Actions -->
    <div class="main-column">
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fa-solid fa-plus-circle"></i> Add a Transaction</h2>
                <a href="/expenses/create" class="badge badge-pill badge-primary btn btn-primary"><i class="fa-solid fa-plus"></i> New Expense</a>
            </div>
            <form action="/transactions/create" method="post" class="transaction-form">
                <?php echo Security::csrfField(); ?>

                <div class="form-group">
                    <label for="friend_id">Who are you settling with?</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-user-group"></i>
                        <select name="friend_id" id="friend_id" required>
                            <option value="">Select a friend...</option>
                            <!-- Check if $data['friends'] exists and is not empty before looping -->
                            <?php if (!empty($data['friends'])) : ?>
                                <?php foreach ($data['friends'] as $f) : ?>
                                    <option value="<?php echo $f->id; ?>"><?php echo htmlspecialchars($f->name); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>What happened?</label>
                    <div class="segmented-control">
                        <button type="button" class="sg-btn active" data-value="0">You Paid to Friend</button>
                        <button type="button" class="sg-btn" data-value="1">Friend Paid to You</button>
                    </div>
                    <input type="hidden" name="i_owe_them" id="i_owe_them_input" value="0">
                </div>

                <div class="form-grid-double">
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <div class="input-with-icon">
                            <i>৳</i>
                            <input type="number" id="amount" name="amount" step="0.01" placeholder="500.00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-pencil"></i>
                            <input type="text" id="description" name="description" placeholder="e.g., Dinner bill" required>
                        </div>
                    </div>
                </div>
                
                <button class="btn btn-primary disable-on-click" type="submit">
                    <i class="fa-solid fa-paper-plane"></i> Add Transaction
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Balances -->
    <div class="sidebar-column">
        <div class="dashboard-card">
            <div class="card-header">
                <h2><i class="fa-solid fa-scale-balanced"></i> Balances</h2>
            </div>
            <div class="balances-list">
                <!-- Check if $data['balances'] is empty or doesn't exist -->
                <?php if (empty($data['balances']) || !array_filter($data['balances'], fn($b) => $b['balance'] != 0)) : ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-check-circle"></i>
                        <p>All settled up!</p>
                        <span>You have no outstanding balances.</span>
                    </div>
                <?php else : ?>
                    <table>
                        <tbody>
                            <?php foreach ($data['balances'] as $fid => $bal) :
                                if ($bal['balance'] == 0) continue; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bal['name']); ?></td>
                                    <td>
                                        <?php if ($bal['balance'] > 0) : ?>
                                            <span class="badge success">Owes you ৳<?php echo number_format($bal['balance'], 2); ?></span>
                                        <?php else : ?>
                                            <span class="badge danger">You owe ৳<?php echo number_format(abs($bal['balance']), 2); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><a class="btn-settle" href="/transactions/settle/<?php echo $fid; ?>">Settle</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* --- DASHBOARD STYLES --- */

    /* --- Page Header --- */
    .dashboard-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .dashboard-header h1 { margin: 0 0 4px 0; color: var(--text-primary); }
    .dashboard-header .subtitle { color: var(--text-secondary); margin: 0; }
    .profile-code-box { background-color: var(--card-bg); border: 1px solid var(--card-border); border-radius: 8px; padding: 8px 16px; text-align: center; }
    .profile-code-box span { font-size: 0.8rem; color: var(--text-secondary); }
    .profile-code-box strong { display: block; font-size: 1.1rem; color: var(--text-primary); }

    /* --- Summary Grid --- */
    .summary-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 32px; }
    @media (min-width: 640px) { .summary-grid { grid-template-columns: repeat(2, 1fr); } }
    .summary-box { background-color: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; }
    .summary-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    .summary-icon.danger { background-color: var(--danger-bg); color: var(--danger-color); }
    .summary-icon.success { background-color: var(--success-bg); color: var(--success-color); }
    .summary-label { color: var(--text-secondary); }
    .summary-value { font-size: 1.75rem; font-weight: 600; }
    .danger-text { color: var(--danger-color); }
    .success-text { color: var(--success-color); }

    /* --- Main Grid Layout --- */
    .dashboard-grid { display: grid; grid-template-columns: 1fr; gap: 24px; }
    @media (min-width: 1024px) { .dashboard-grid { grid-template-columns: 2fr 1fr; } }

    /* --- General Card Styling --- */
    .dashboard-card { background-color: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 24px; }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .card-header h2 { margin: 0; font-size: 1.25rem; color: var(--text-primary); }
    .card-header .btn.secondary { padding: 8px 16px; font-size: 0.9rem; }

    /* --- Transaction Form --- */
    .transaction-form { display: flex; flex-direction: column; gap: 20px; }
    .form-group label { display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-primary); }
    .input-with-icon { position: relative; }
    .input-with-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
    .input-with-icon input, .input-with-icon select {
        width: 100%; padding: 12px 12px 12px 40px; border: 1px solid var(--card-border); border-radius: 8px; font-size: 1rem;
        background-color: var(--input-bg); color: var(--text-primary);
    }
    .input-with-icon input[type="number"] { padding-left: 30px; }
    .form-grid-double { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }

    .segmented-control { display: flex; width: 100%; background-color: var(--input-bg); border-radius: 8px; padding: 4px; }
    .sg-btn { flex: 1; padding: 10px; border: none; background-color: transparent; border-radius: 6px; font-weight: 500; cursor: pointer; transition: background-color 0.2s, color 0.2s; color: var(--text-secondary); }
    .sg-btn.active { background-color: var(--card-bg); color: var(--brand-color); font-weight: 600; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    
    /* --- THE NEW BUTTON STYLE --- */
    .btn-primary {
        background-color: var(--brand-color);
        color: white;
        padding: 14px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s, transform 0.2s;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-primary:hover {
        background-color: var(--brand-hover);
        transform: translateY(-2px);
    }
    .btn-primary:disabled {
        background-color: #ccc;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    /* --- Balances List --- */
    .balances-list table { width: 100%; border-collapse: collapse; }
    .balances-list td { padding: 12px 0; border-bottom: 1px solid var(--card-border); }
    .balances-list tr:last-child td { border-bottom: none; }
    .balances-list td:first-child { font-weight: 500; color: var(--text-primary); }
    .balances-list td:nth-child(2), .balances-list td:last-child { text-align: right; }
    
    .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
    .badge.success { background-color: var(--success-bg); color: var(--success-text); }
    .badge.danger { background-color: var(--danger-bg); color: var(--danger-text); }

    .btn-settle {
        background-color: var(--input-bg); color: var(--text-secondary); border: 1px solid var(--card-border);
        padding: 6px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 500; text-decoration: none;
        transition: background-color 0.2s, color 0.2s, border-color 0.2s;
    }
    .btn-settle:hover { background-color: var(--brand-color); color: white; border-color: var(--brand-color); }
    
    .empty-state { text-align: center; padding: 40px 20px; }
    .empty-state i { font-size: 3rem; color: var(--success-color); }
    .empty-state p { font-size: 1.2rem; font-weight: 600; margin: 16px 0 4px; color: var(--text-primary); }
    .empty-state span { color: var(--text-secondary); }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // --- Segmented Control for Transaction Form ---
    const segmentedControl = document.querySelector('.segmented-control');
    const hiddenInput = document.getElementById('i_owe_them_input');

    if (segmentedControl && hiddenInput) {
        const buttons = segmentedControl.querySelectorAll('.sg-btn');
        
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                buttons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                hiddenInput.value = button.dataset.value;
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; // Your new, modern footer ?>
