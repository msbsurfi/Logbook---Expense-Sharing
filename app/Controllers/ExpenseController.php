<?php
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/Security.php';

class ExpenseController {
    private $expenseModel;
    private $friendModel;
    private $transactionModel;
    private $userModel;
    private $notificationModel;

    public function __construct(){
        Security::ensureSession();
        if (!isset($_SESSION['user_id'])){ header('Location:/login'); exit(); }
        $this->expenseModel      = new Expense();
        $this->friendModel       = new Friend();
        $this->transactionModel  = new Transaction();
        $this->userModel         = new User();
        $this->notificationModel = new Notification();
    }

    public function showCreateForm(){
        $data = [
            'friends'=>$this->friendModel->getAcceptedFriends($_SESSION['user_id'])
        ];
        require_once __DIR__ . '/../Views/expenses/create.php';
    }

    public function create(){
        if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/expenses/create'); return; }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error']="Security token invalid."; header('Location:/expenses/create'); return;
        }

        $creatorId   = $_SESSION['user_id'];
        $description = trim($_POST['description'] ?? '');
        $totalAmount = filter_var($_POST['total_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
        $participants = $_POST['participants'] ?? [];
        $participants = array_map('intval',$participants);
        $participants = array_filter($participants, fn($v)=>$v>0);

        // Always include payer and creator in the working set (payer might be creator or another friend)
        $payerId = (int)($_POST['payer_id'] ?? $creatorId);

        // Build the base unique list of participant IDs first (explicit choices)
        $allIds = array_unique($participants);

        // Ensure creator included (if not selected explicitly)
        if (!in_array($creatorId,$allIds)) {
            $allIds[] = $creatorId;
        }
        // Ensure payer included
        if (!in_array($payerId,$allIds)) {
            $allIds[] = $payerId;
        }

        if (!$description || !$totalAmount || count($allIds) < 2){
            $_SESSION['flash_error']="Provide description, amount > 0, and at least one friend (plus payer).";
            header('Location:/expenses/create');
            return;
        }

        $splitMode = $_POST['split_mode'] ?? 'equal';
        $shares = [];

        if ($splitMode === 'custom') {
            $sum = 0;
            foreach ($allIds as $pid) {
                $key = 'share_'.$pid;
                if (!isset($_POST[$key])) {
                    $_SESSION['flash_error'] = "Missing share for participant ID: $pid";
                    header('Location:/expenses/create');
                    return;
                }
                $val = (float)$_POST[$key];
                if ($val < 0) {
                    $_SESSION['flash_error'] = "Share cannot be negative.";
                    header('Location:/expenses/create');
                    return;
                }
                $shares[$pid] = $val;
                $sum += $val;
            }
            if (abs($sum - $totalAmount) > 0.01) {
                $_SESSION['flash_error'] = "Sum of custom shares ($sum) != total ($totalAmount).";
                header('Location:/expenses/create');
                return;
            }
        } else {
            // Equal split among allIds
            $per = round($totalAmount / count($allIds), 2);
            foreach ($allIds as $pid) {
                $shares[$pid] = $per;
            }
            // Adjust rounding difference
            $diff = $totalAmount - array_sum($shares);
            if (abs($diff) >= 0.01) {
                // Add difference to payer (logical choice) so totals match exactly
                $shares[$payerId] += $diff;
            }
        }

        // Create expense
        $eid = $this->expenseModel->createExpense($description,$totalAmount,$creatorId);
        if (!$eid){
            $_SESSION['flash_error']="Could not create expense."; header('Location:/expenses/create'); return;
        }

        // Insert participants & shares (payer flagged)
        $db = new Database();
        foreach ($shares as $pid=>$amt){
            $db->query("INSERT INTO expense_participants (expense_id,user_id,share,is_payer) VALUES (:e,:u,:s,:p)");
            $db->bind(':e',$eid);
            $db->bind(':u',$pid);
            $db->bind(':s',$amt);
            $db->bind(':p',$pid == $payerId ? 1 : 0);
            $db->execute();
        }

        // Create transactions: everyone OWES the payer EXCEPT the payer
        foreach ($shares as $pid=>$amt){
            if ($pid == $payerId || $amt <= 0) continue;
            $this->transactionModel->createTransaction(
                $payerId,     // lender (paid the whole bill)
                $pid,         // borrower
                $amt,
                $description,
                $eid,
                $creatorId    // recorded by creator (could be payer or not)
            );
        }

        // --- ENHANCED NOTIFICATION LOGIC ---
        
        // 1. Setup Timezone (GMT+6)
        $dateObj = new DateTime('now', new DateTimeZone('UTC'));
        $dateObj->setTimezone(new DateTimeZone('Asia/Dhaka'));
        $formattedTime = $dateObj->format('d M Y, h:i A') . ' (GMT+6)';

        // 2. Fetch Payer Object Info
        $payerObj = $this->userModel->findUserById($payerId);
        $payerName = $payerObj ? $payerObj->name : 'Unknown';

        // 3. Initialize Mailer
        $mailer = new Mailer();

        foreach ($allIds as $pid){
            $u = $this->userModel->findUserById($pid);
            if (!$u || empty($u->email)) continue;

            $myShare = $shares[$pid];
            $isPayer = ($pid == $payerId);
            
            // Define context variables
            if ($isPayer) {
                // You paid the total, you are owed (Total - Your Share)
                $amountOwedToYou = $totalAmount - $myShare;
                
                $title = "Expense Added: You Paid";
                $mainMessage = "You paid <strong>৳" . number_format($totalAmount, 2) . "</strong> for '{$description}'.";
                $subMessage = "Your share is ৳" . number_format($myShare, 2) . ". The group owes you ৳" . number_format($amountOwedToYou, 2) . ".";
                $themeColor = "#28a745"; // Green (Good, money coming back)
                $notifMsg = "You paid ৳" . number_format($totalAmount) . " for '{$description}'. Group owes you ৳" . number_format($amountOwedToYou) . ".";
                
                $details = [
                    'Expense' => htmlspecialchars($description),
                    'Total Bill' => '৳' . number_format($totalAmount, 2),
                    'Your Share' => '৳' . number_format($myShare, 2),
                    'Total Owed To You' => '৳' . number_format($amountOwedToYou, 2),
                    'Date' => $formattedTime
                ];

            } else {
                // You owe money
                $title = "New Expense to Settle";
                $mainMessage = "<strong>{$payerName}</strong> paid for '{$description}'.";
                $subMessage = "You need to pay your share of ৳" . number_format($myShare, 2) . ".";
                $themeColor = "#dc3545"; // Red (Debt)
                $notifMsg = "{$payerName} paid for '{$description}'. You owe ৳" . number_format($myShare) . ".";

                $details = [
                    'Expense' => htmlspecialchars($description),
                    'Paid By' => $payerName,
                    'Total Bill' => '৳' . number_format($totalAmount, 2),
                    'Your Share (Debt)' => '৳' . number_format($myShare, 2),
                    'Date' => $formattedTime
                ];
            }

            // Generate HTML
            $html = $this->generateEmailTemplate($title, $u->name, $mainMessage, $subMessage, $details, $themeColor);
            
            // Send Email
            $subject = 'Expense: ' . $description;
            $mailer->send($u->email, $u->name, $subject, $html);
            
            // Log & Notify
            $this->userModel->logEmail($u->id, $u->email, $subject);
            $this->notificationModel->send($u->id, 'Group Expense', $notifMsg);
        }

        $_SESSION['flash_success']="Expense created. All participants now owe the payer (excluding payer's own share).";
        header('Location:/dashboard');
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
                <td style='padding: 8px 0; color: #666; font-size: 14px; width: 45%; vertical-align: top;'>{$label}</td>
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