<?php
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/Security.php';

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

            // Fetch User Objects
            $lender   = $this->userModel->findUserById($lenderId);
            $borrower = $this->userModel->findUserById($borrowerId);
            $creator  = ($currentUserId === $lenderId) ? $lender : $borrower;
            
            // Get Time in GMT+6
            $dateObj = new DateTime('now', new DateTimeZone('UTC'));
            $dateObj->setTimezone(new DateTimeZone('Asia/Dhaka'));
            $formattedTime = $dateObj->format('d M Y, h:i A');

            $mailer = new Mailer();
            $notificationTitle = 'New Transaction Added';

            // Iterate to send personalized emails
            foreach ([$lender, $borrower] as $userObject) {
                if ($userObject && property_exists($userObject, 'email') && !empty($userObject->email)) {
                    $isLender = ($userObject->id === $lenderId);
                    $otherPartyName = $isLender ? $borrower->name : $lender->name;
                    
                    // Smart Context Message
                    if ($isLender) {
                        $mainMessage = "<strong>You lent</strong> money to <strong>{$otherPartyName}</strong>.";
                        $subMessage = "You are currently owed this amount.";
                        $color = "#28a745"; // Green for money coming in eventually
                    } else {
                        $mainMessage = "<strong>You borrowed</strong> money from <strong>{$otherPartyName}</strong>.";
                        $subMessage = "You owe this amount to {$otherPartyName}.";
                        $color = "#dc3545"; // Red for debt
                    }

                    $details = [
                        'Description' => htmlspecialchars($description),
                        'Amount' => '৳' . number_format($amount, 2),
                        'Role' => $isLender ? 'Lender (You)' : 'Borrower (You)',
                        'Other Party' => $otherPartyName,
                        'Recorded By' => $creator->name,
                        'Time' => $formattedTime . ' (GMT+6)'
                    ];

                    $html = $this->generateEmailTemplate(
                        'New Transaction Recorded', 
                        $userObject->name, 
                        $mainMessage, 
                        $subMessage, 
                        $details, 
                        $color
                    );

                    $emailSubject = 'Transaction: ' . $description;
                    
                    // Send Email
                    $mailer->send($userObject->email, $userObject->name, $emailSubject, $html);
                    $this->userModel->logEmail($userObject->id, $userObject->email, $emailSubject);

                    // Send App Notification
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

        // Get Time in GMT+6
        $dateObj = new DateTime('now', new DateTimeZone('UTC'));
        $dateObj->setTimezone(new DateTimeZone('Asia/Dhaka'));
        $formattedTime = $dateObj->format('d M Y, h:i A');

        foreach($txnIds as $tid){
            $tid = (int)$tid;
            // Mark as paid
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
                        $otherPartyName = $isLender ? $borrower->name : $lender->name;

                        // Smart Context
                        if ($isLender) {
                            $mainMessage = "Payment received from <strong>{$otherPartyName}</strong>.";
                            $subMessage = "This debt has been marked as settled.";
                            $color = "#28a745";
                        } else {
                            $mainMessage = "You paid <strong>{$otherPartyName}</strong>.";
                            $subMessage = "You have settled this debt.";
                            $color = "#007bff"; // Blue for action taken
                        }

                        $details = [
                            'Transaction ID' => '#' . $tid,
                            'Original Description' => htmlspecialchars($txn->description),
                            'Amount Settled' => '৳' . number_format($txn->amount, 2),
                            'Settled By' => $settler->name,
                            'Settled At' => $formattedTime . ' (GMT+6)'
                        ];

                        $html = $this->generateEmailTemplate(
                            'Transaction Settled',
                            $userObject->name,
                            $mainMessage,
                            $subMessage,
                            $details,
                            $color
                        );

                        // Send Email
                        $mailer->send($userObject->email, $userObject->name, $emailSubject, $html);
                        $this->userModel->logEmail($userObject->id, $userObject->email, $emailSubject);
                        
                        // Send Notification
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

    /**
     * Helper to generate beautiful inline CSS emails
     */
    private function generateEmailTemplate($title, $userName, $mainMsg, $subMsg, $details, $themeColor = '#4A90E2') {
        // Prepare details list
        $detailsHtml = '';
        foreach ($details as $label => $value) {
            $detailsHtml .= "
            <tr>
                <td style='padding: 8px 0; color: #666; font-size: 14px; width: 40%;'>{$label}</td>
                <td style='padding: 8px 0; color: #333; font-size: 14px; font-weight: 600; text-align: right;'>{$value}</td>
            </tr>
            <tr style='border-bottom: 1px solid #eee;'><td></td><td></td></tr>";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; color: #333;'>
            <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #f4f6f8; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <!-- Main Card -->
                        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='600' style='background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; max-width: 90%;'>
                            
                            <!-- Header Bar -->
                            <tr>
                                <td style='background-color: {$themeColor}; padding: 24px; text-align: center;'>
                                    <h1 style='margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;'>{$title}</h1>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style='padding: 32px 24px;'>
                                    <p style='margin: 0 0 16px 0; font-size: 16px; color: #333;'>Hello <strong>{$userName}</strong>,</p>
                                    
                                    <p style='margin: 0 0 8px 0; font-size: 16px; line-height: 1.5; color: #333;'>{$mainMsg}</p>
                                    <p style='margin: 0 0 24px 0; font-size: 14px; color: #666;'>{$subMsg}</p>

                                    <!-- Details Box -->
                                    <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 24px;'>
                                        <tr>
                                            <td style='padding: 16px;'>
                                                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                                                    {$detailsHtml}
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <p style='margin: 0; font-size: 14px; color: #999; text-align: center;'>
                                        This is an automated notification from your expense tracker.
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style='background-color: #f1f3f5; padding: 16px; text-align: center; border-top: 1px solid #e9ecef;'>
                                    <p style='margin: 0; font-size: 12px; color: #868e96;'>&copy; " . date('Y') . " Logbook. All rights reserved.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }
}
?>