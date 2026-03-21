<?php
$title = 'My Friends';
require_once __DIR__ . '/../layouts/header.php'; 
require_once __DIR__ . '/../../Lib/Security.php';

// Note: Ensure your controller populates $data['sent_requests'] 
// by querying friends table where requested_by = current_user AND status = 'pending'
?>

<div class="page-header">
    <h1><i class="fa-solid fa-user-group"></i> My Friends</h1>
    <p class="subtitle">Manage your friend requests and connections.</p>
</div>

<div class="friends-page-grid">
    <!-- Add Friend Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fa-solid fa-user-plus"></i> Add a Friend</h2>
        </div>
        <form action="/friends/send" method="post" class="add-friend-form">
            <?php echo Security::csrfField(); ?>
            <div class="form-group">
                <label for="profile_code">Enter your friend's unique profile code</label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-hashtag"></i>
                    <input type="text" id="profile_code" name="profile_code" placeholder="Friend's profile code" required>
                </div>
            </div>
            <button class="btn btn-primary disable-on-click" type="submit">
                <i class="fa-solid fa-paper-plane"></i> Send Friend Request
            </button>
        </form>
    </div>

    <!-- Incoming Requests Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fa-solid fa-inbox"></i> Incoming Requests</h2>
            <?php if (!empty($data['requests'])) : ?>
                <span class="count-badge"><?php echo count($data['requests']); ?></span>
            <?php endif; ?>
        </div>
        <div class="friends-list">
            <?php if (!empty($data['requests'])) : foreach ($data['requests'] as $r) : ?>
                <div class="friend-item">
                    <div class="friend-info">
                        <strong><?php echo htmlspecialchars($r->name); ?></strong>
                        <span>Code: <?php echo htmlspecialchars($r->profile_code); ?></span>
                    </div>
                    <div class="friend-actions">
                        <form action="/friends/respond/<?php echo $r->id; ?>/accept" method="post">
                            <?php echo Security::csrfField(); ?>
                            <button class="btn btn-success disable-on-click" type="submit"><i class="fa-solid fa-check"></i> Accept</button>
                        </form>
                        <form action="/friends/respond/<?php echo $r->id; ?>/decline" method="post">
                            <?php echo Security::csrfField(); ?>
                            <button class="btn btn-danger disable-on-click" type="submit"><i class="fa-solid fa-xmark"></i> Decline</button>
                        </form>
                    </div>
                </div>
            <?php endforeach;
            else : ?>
                <div class="empty-state">
                    <i class="fa-solid fa-envelope-open-text"></i>
                    <p>No pending requests</p>
                    <span>You're all caught up!</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sent Requests Card (New) -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fa-solid fa-paper-plane"></i> Sent Requests</h2>
            <?php if (!empty($data['sent_requests'])) : ?>
                <span class="count-badge"><?php echo count($data['sent_requests']); ?></span>
            <?php endif; ?>
        </div>
        <div class="friends-list">
            <?php if (!empty($data['sent_requests'])) : foreach ($data['sent_requests'] as $sr) : ?>
                <div class="friend-item">
                    <div class="friend-info">
                        <strong><?php echo htmlspecialchars($sr->name); ?></strong>
                        <span>Code: <?php echo htmlspecialchars($sr->profile_code); ?></span>
                        <span class="status-pill">Pending</span>
                    </div>
                    <div class="friend-actions">
                        <form action="/friends/cancel/<?php echo $sr->id; ?>" method="post">
                            <?php echo Security::csrfField(); ?>
                            <!-- Cancelling deletes the record, effectively same as unfriend/decline logic -->
                            <button class="btn btn-danger disable-on-click" type="submit" title="Cancel Request">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach;
            else : ?>
                <div class="empty-state">
                    <i class="fa-solid fa-paper-plane"></i>
                    <p>No sent requests</p>
                    <span>Requests you send will appear here.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Your Friends Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2><i class="fa-solid fa-users"></i> Your Friends</h2>
        </div>
        <div class="friends-list">
            <?php if (!empty($data['friends'])) : foreach ($data['friends'] as $f) : ?>
                <div class="friend-item">
                    <div class="friend-info">
                        <strong><?php echo htmlspecialchars($f->name); ?></strong>
                        <span>Code: <?php echo htmlspecialchars($f->profile_code); ?></span>
                    </div>
                    <div class="friend-actions">
                        <a class="btn secondary" href="/transactions/settle/<?php echo $f->id; ?>">Settle Up</a>
                        <!-- Changed to trigger modal instead of direct submit -->
                        <button class="btn btn-danger" type="button" 
                                onclick="openUnfriendModal(<?php echo $f->id; ?>, '<?php echo htmlspecialchars($f->name, ENT_QUOTES); ?>')" 
                                title="Unfriend">
                            <i class="fa-solid fa-user-slash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach;
            else : ?>
                <div class="empty-state">
                    <i class="fa-solid fa-users-slash"></i>
                    <p>No friends yet</p>
                    <span>Use the form above to add friends by their profile code.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Unfriend Confirmation Modal -->
<div id="unfriendModal" class="modal-overlay" style="display: none;">
    <div class="modal-content dashboard-card">
        <div class="modal-header">
            <h3>Unfriend Confirmation</h3>
            <button class="close-modal" onclick="closeUnfriendModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to remove <strong id="modalFriendName"></strong> from your friends list?</p>
            <p class="warning-text">This action cannot be undone.</p>
            
            <div class="form-group">
                <label for="confirm_text">Type <strong>CONFIRM</strong> to proceed:</label>
                <input type="text" id="confirm_text" class="form-control" placeholder="CONFIRM" autocomplete="off">
            </div>

            <form id="unfriendForm" action="" method="post">
                <?php echo Security::csrfField(); ?>
                <div class="modal-actions">
                    <button type="button" class="btn secondary" onclick="closeUnfriendModal()">Cancel</button>
                    <button type="submit" id="confirmBtn" class="btn btn-danger" disabled>Unfriend</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Existing Styles */
    .page-header h1 { margin: 0 0 4px 0; color: var(--text-primary); }
    .page-header .subtitle { color: var(--text-secondary); margin: 0 0 24px 0; }
    
    .friends-page-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }
    
    .dashboard-card { background-color: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 24px; }
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--card-border); }
    .card-header h2 { margin: 0; font-size: 1.25rem; color: var(--text-primary); }
    
    .count-badge {
        background-color: var(--brand-color); color: white; font-size: 0.8rem; font-weight: 600; padding: 4px 8px; border-radius: 12px; min-width: 24px; text-align: center;
    }

    .status-pill {
        display: inline-block; font-size: 0.75rem; padding: 2px 8px; border-radius: 10px; background-color: var(--input-bg); border: 1px solid var(--card-border); color: var(--text-secondary); margin-left: 8px;
    }
    
    .add-friend-form { display: flex; flex-direction: column; gap: 16px; }
    .form-group label { display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-primary); }
    .input-with-icon { position: relative; }
    .input-with-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
    .input-with-icon input, .form-control {
        width: 100%; padding: 12px 12px 12px 40px; border: 1px solid var(--card-border); border-radius: 8px; font-size: 1rem;
        background-color: var(--input-bg); color: var(--text-primary);
    }
    .form-control { padding-left: 12px; } /* Reset padding for modal input */
    
    .friends-list { display: flex; flex-direction: column; gap: 12px; }
    .friend-item {
        display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; padding: 12px; border-radius: 8px; background-color: var(--input-bg);
    }
    .friend-info strong { display: block; color: var(--text-primary); font-weight: 600; }
    .friend-info span { font-size: 0.85rem; color: var(--text-secondary); }
    .friend-actions { display: flex; align-items: center; gap: 12px; }
    .friend-actions form { margin: 0; }
    
    .btn { padding: 8px 16px; border-radius: 6px; font-weight: 500; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all 0.2s; }
    .btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-primary { background-color: var(--brand-color); color: white; border-color: var(--brand-color); box-shadow: var(--shadow-sm); }
    .btn-primary:hover { background-color: var(--brand-hover); }
    .btn.secondary { background-color: var(--input-bg); color: var(--text-secondary); border-color: var(--card-border); }
    .btn.secondary:hover { border-color: var(--text-primary); color: var(--text-primary); }
    .btn-success { background-color: var(--success-bg); color: var(--success-text); border-color: var(--success-color); }
    .btn-success:hover { background-color: var(--success-color); color: white; }
    .btn-danger { background-color: var(--danger-bg); color: var(--danger-text); border-color: var(--danger-color); }
    .btn-danger:hover { background-color: var(--danger-color); color: white; }

    .empty-state { text-align: center; padding: 40px 20px; }
    .empty-state i { font-size: 3rem; color: var(--text-secondary); }
    .empty-state p { font-size: 1.2rem; font-weight: 600; margin: 16px 0 4px; color: var(--text-primary); }
    .empty-state span { color: var(--text-secondary); }

    /* Modal Styles */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5); z-index: 1000;
        display: flex; justify-content: center; align-items: center;
        backdrop-filter: blur(2px);
    }
    .modal-content {
        width: 100%; max-width: 500px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: slideUp 0.3s ease-out;
    }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .modal-header h3 { margin: 0; color: var(--text-primary); }
    .close-modal { background: none; border: none; font-size: 1.2rem; color: var(--text-secondary); cursor: pointer; }
    .modal-body p { color: var(--text-primary); margin-bottom: 12px; }
    .warning-text { color: var(--danger-color) !important; font-size: 0.9rem; }
    .modal-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    const modal = document.getElementById('unfriendModal');
    const confirmInput = document.getElementById('confirm_text');
    const confirmBtn = document.getElementById('confirmBtn');
    const unfriendForm = document.getElementById('unfriendForm');
    const modalFriendName = document.getElementById('modalFriendName');

    function openUnfriendModal(friendId, friendName) {
        // Set dynamic data
        unfriendForm.action = '/friends/unfriend/' + friendId;
        modalFriendName.textContent = friendName;
        
        // Reset state
        confirmInput.value = '';
        confirmBtn.disabled = true;
        
        // Show modal
        modal.style.display = 'flex';
        confirmInput.focus();
    }

    function closeUnfriendModal() {
        modal.style.display = 'none';
    }

    // Validation Logic
    confirmInput.addEventListener('input', function() {
        if (this.value === 'CONFIRM') {
            confirmBtn.disabled = false;
        } else {
            confirmBtn.disabled = true;
        }
    });

    document.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeUnfriendModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.style.display === 'flex') {
            closeUnfriendModal();
        }
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
