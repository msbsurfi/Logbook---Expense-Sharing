<?php
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/Security.php';
require_once __DIR__ . '/../Lib/EmailTemplate.php';

class TransactionController {
    private Transaction $transactionModel;
    private User $userModel;
    private Notification $notificationModel;

    public function __construct(){
        Security::ensureSession();
        if (!isset($_SESSION['user_id'])){ header('Location:/login'); exit(); }
        $this->transactionModel = new Transaction();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
    }

    public function create(){
        if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/dashboard'); return; }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error']='Invalid security token.'; header('Location:/dashboard'); return;
        }

        $currentUserId = (int)$_SESSION['user_id'];
        $friendId      = (int)($_POST['friend_id'] ?? 0);
        $amount        = (float)($_POST['amount'] ?? 0);
        $description   = trim($_POST['description'] ?? '');
        $iOweFlag      = ($_POST['i_owe_them'] ?? '1') === '1';

        if (!$friendId || $amount <= 0 || $description === ''){
            $_SESSION['flash_error']="All fields required and amount must be positive."; header('Location:/dashboard'); return;
        }

        $lenderId   = $iOweFlag ? $friendId : $currentUserId;
        $borrowerId = $iOweFlag ? $currentUserId : $friendId;

        if ($this->transactionModel->createTransaction($lenderId, $borrowerId, $amount, $description, null, $currentUserId)){
            $_SESSION['flash_success']="Transaction recorded.";

            $lender   = $this->userModel->findUserById($lenderId);
            $borrower = $this->userModel->findUserById($borrowerId);
            $creator  = ($currentUserId === $lenderId) ? $lender : $borrower;

            $dateObj = new DateTime('now', new DateTimeZone('UTC'));
            $dateObj->setTimezone(new DateTimeZone('Asia/Dhaka'));
            $formattedTime = $dateObj->format('d M Y, h:i A');

            $mailer = new Mailer();
            $notificationTitle = 'New Transaction Added';

            foreach ([$lender, $borrower] as $userObject) {
                if ($userObject && property_exists($userObject, 'email') && !empty($userObject->email)) {
                    $isLender = ($userObject->id === $lenderId);
                    $otherPartyName = $isLender ? $borrower->name : $lender->name;

                    $safeName = htmlspecialchars($otherPartyName);
                    if ($isLender) {
                        $mainMessage = "<strong>You lent</strong> money to <strong>{$safeName}</strong>.";
                        $subMessage = "You are currently owed this amount.";
                        $color = "#28a745";
                    } else {
                        $mainMessage = "<strong>You borrowed</strong> money from <strong>{$safeName}</strong>.";
                        $subMessage = "You owe this amount to {$safeName}.";
                        $color = "#dc3545";
                    }

                    $details = [
                        'Description' => htmlspecialchars($description),
                        'Amount' => '৳' . number_format($amount, 2),
                        'Role' => $isLender ? 'Lender (You)' : 'Borrower (You)',
                        'Other Party' => $safeName,
                        'Recorded By' => htmlspecialchars($creator->name),
                        'Time' => $formattedTime . ' (GMT+6)'
                    ];

                    $html = EmailTemplate::generate(
                        'New Transaction Recorded',
                        $userObject->name,
                        $mainMessage,
                        $subMessage,
                        $details,
                        $color
                    );

                    $emailSubject = 'Transaction: ' . $description;

                    $mailer->send($userObject->email, $userObject->name, $emailSubject, $html);
                    $this->userModel->logEmail($userObject->id, $userObject->email, $emailSubject);

                    $notifMsg = $isLender
                        ? "You lent ৳" . number_format($amount) . " to {$otherPartyName} for '{$description}'."
                        : "You borrowed ৳" . number_format($amount) . " from {$otherPartyName} for '{$description}'.";

                    $this->notificationModel->send($userObject->id, $notificationTitle, $notifMsg);
                }
            }
        } else {
            $_SESSION['flash_error']="Failed to create transaction.";
        }
        header('Location:/dashboard');
    }

    public function showSettlePage($friendId){
        $userId = (int)$_SESSION['user_id'];
        $friendId = (int)$friendId;
        $friend = $this->userModel->findUserById($friendId);
        if (!$friend){ $_SESSION['flash_error']="User not found."; header('Location:/dashboard'); return; }
        $data = [
            'friend' => $friend,
            'transactions' => $this->transactionModel->getUnpaidTransactionsWithFriend($userId, $friendId),
        ];
        require_once __DIR__ . '/../Views/transactions/settle.php';
    }

    public function settleUp(){
        if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/dashboard'); return; }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error']='Invalid security token.'; header('Location:/dashboard'); return;
        }

        $txnIds   = $_POST['txn_ids'] ?? [];
        $friendId = (int)($_POST['friend_id'] ?? 0);
        $actorId  = (int)$_SESSION['user_id'];

        if (empty($txnIds)){
            $_SESSION['flash_error']="No transactions selected.";
            if ($friendId > 0) header('Location:/transactions/settle/'.$friendId); else header('Location:/dashboard');
            return;
        }

        $successCount = 0;
        $mailer = new Mailer();

        $dateObj = new DateTime('now', new DateTimeZone('UTC'));
        $dateObj->setTimezone(new DateTimeZone('Asia/Dhaka'));
        $formattedTime = $dateObj->format('d M Y, h:i A');

        foreach($txnIds as $tid){
            $tid = (int)$tid;
            if ($this->transactionModel->markTransactionAsPaid($tid, $actorId)){
                $successCount++;
                $txn = $this->transactionModel->getTransactionById($tid);
                if (!$txn) continue;

                $lender   = $this->userModel->findUserById((int)$txn->lender_id);
                $borrower = $this->userModel->findUserById((int)$txn->borrower_id);
                $settler  = ($actorId == $lender->id) ? $lender : $borrower;

                $emailSubject = 'Settled: ' . $txn->description;
                $notificationTitle = 'Transaction Settled';

                foreach([$lender, $borrower] as $userObject){
                    if ($userObject && property_exists($userObject, 'email') && !empty($userObject->email)) {
                        $isLender = ($userObject->id === $lender->id);
                        $safeName = htmlspecialchars($isLender ? $borrower->name : $lender->name);

                        if ($isLender) {
                            $mainMessage = "Payment received from <strong>{$safeName}</strong>.";
                            $subMessage = "This debt has been marked as settled.";
                            $color = "#28a745";
                        } else {
                            $mainMessage = "You paid <strong>{$safeName}</strong>.";
                            $subMessage = "You have settled this debt.";
                            $color = "#007bff";
                        }

                        $details = [
                            'Transaction ID' => '#' . $tid,
                            'Original Description' => htmlspecialchars($txn->description),
                            'Amount Settled' => '৳' . number_format($txn->amount, 2),
                            'Settled By' => htmlspecialchars($settler->name),
                            'Settled At' => $formattedTime . ' (GMT+6)'
                        ];

                        $html = EmailTemplate::generate(
                            'Transaction Settled',
                            $userObject->name,
                            $mainMessage,
                            $subMessage,
                            $details,
                            $color
                        );

                        $mailer->send($userObject->email, $userObject->name, $emailSubject, $html);
                        $this->userModel->logEmail($userObject->id, $userObject->email, $emailSubject);

                        $notifMsg = "Transaction '{$txn->description}' (৳" . number_format($txn->amount) . ") was settled by " . $settler->name . ".";
                        $this->notificationModel->send($userObject->id, $notificationTitle, $notifMsg);
                    }
                }
            }
        }

        if ($successCount > 0){
            $_SESSION['flash_success'] = "$successCount transaction(s) settled.";
        } else {
            $_SESSION['flash_error'] = "Settlement failed or no transactions were updated.";
        }

        if ($friendId > 0) header('Location:/transactions/settle/'.$friendId); else header('Location:/dashboard');
    }

    public function showHistory(){
        $userId = (int)$_SESSION['user_id'];
        $history = $this->transactionModel->getTransactionHistoryForUser($userId);
        $data = ['history'=>$history];
        require_once __DIR__ . '/../Views/transactions/history.php';
    }
}
?>