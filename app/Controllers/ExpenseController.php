<?php
require_once __DIR__ . '/../Lib/Mailer.php';
require_once __DIR__ . '/../Lib/Security.php';
require_once __DIR__ . '/../Lib/EmailTemplate.php';

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
            'friends' => $this->friendModel->getAcceptedFriends($_SESSION['user_id'])
        ];
        require_once __DIR__ . '/../Views/expenses/create.php';
    }

    public function create(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST'){ header('Location:/expenses/create'); return; }
        if (!Security::validateCsrf($_POST['csrf_token'] ?? '')){
            $_SESSION['flash_error'] = 'Security token invalid.';
            header('Location:/expenses/create');
            return;
        }

        $creatorId   = (int)$_SESSION['user_id'];
        $description = trim($_POST['description'] ?? '');
        $totalAmount = (float)filter_var($_POST['total_amount'] ?? 0, FILTER_VALIDATE_FLOAT);

        $participants = array_filter(array_map('intval', $_POST['participants'] ?? []), fn($v) => $v > 0);
        $allIds = array_values(array_unique($participants));

        if (!in_array($creatorId, $allIds)) {
            $allIds[] = $creatorId;
        }

        if (!$description || $totalAmount <= 0 || count($allIds) < 2){
            $_SESSION['flash_error'] = 'Provide description, amount > 0, and at least one other participant.';
            header('Location:/expenses/create');
            return;
        }

        $splitMode = $_POST['split_mode'] ?? 'equal';
        $shares = [];

        if ($splitMode === 'custom') {
            $sum = 0;
            foreach ($allIds as $pid) {
                $val = (float)($_POST['share_' . $pid] ?? 0);
                if ($val < 0){
                    $_SESSION['flash_error'] = 'Share cannot be negative.';
                    header('Location:/expenses/create');
                    return;
                }
                $shares[$pid] = $val;
                $sum += $val;
            }
            if (abs($sum - $totalAmount) > 0.01){
                $_SESSION['flash_error'] = "Sum of shares ({$sum}) does not equal total ({$totalAmount}).";
                header('Location:/expenses/create');
                return;
            }
        } else {
            $per = round($totalAmount / count($allIds), 2);
            foreach ($allIds as $pid) {
                $shares[$pid] = $per;
            }
            $diff = $totalAmount - array_sum($shares);
            if (abs($diff) >= 0.01) {
                $shares[$allIds[0]] += $diff;
            }
        }

        $paidAmounts = [];
        $paidSum = 0;
        foreach ($allIds as $pid) {
            $paid = (float)($_POST['paid_' . $pid] ?? 0);
            if ($paid < 0) $paid = 0;
            $paidAmounts[$pid] = $paid;
            $paidSum += $paid;
        }

        if (abs($paidSum - $totalAmount) > 0.01){
            $_SESSION['flash_error'] = "Sum of payments ({$paidSum}) must equal total amount ({$totalAmount}).";
            header('Location:/expenses/create');
            return;
        }

        $netBalances = [];
        foreach ($allIds as $pid) {
            $netBalances[$pid] = $paidAmounts[$pid] - $shares[$pid];
        }

        $minimizedTxns = $this->minimizeDebts($netBalances);

        $eid = $this->expenseModel->createExpense($description, $totalAmount, $creatorId);
        if (!$eid){
            $_SESSION['flash_error'] = 'Could not create expense.';
            header('Location:/expenses/create');
            return;
        }

        $db = new Database();
        foreach ($allIds as $pid) {
            $isPayer = $paidAmounts[$pid] > 0 ? 1 : 0;
            $db->query("INSERT INTO expense_participants (expense_id,user_id,share,is_payer) VALUES (:e,:u,:s,:p)");
            $db->bind(':e', $eid);
            $db->bind(':u', $pid);
            $db->bind(':s', $shares[$pid]);
            $db->bind(':p', $isPayer);
            $db->execute();
        }

        $createdTxnIds = [];
        foreach ($minimizedTxns as $txn) {
            $newId = $this->transactionModel->createTransaction(
                $txn['lender'],
                $txn['borrower'],
                round($txn['amount'], 2),
                $description,
                $eid,
                $creatorId
            );
            if ($newId !== false) {
                $createdTxnIds[] = $newId;
            }
        }

        $this->sendGroupExpenseEmails($eid, $allIds, $description, $totalAmount, $shares, $paidAmounts, $createdTxnIds, $creatorId);

        $_SESSION['flash_success'] = 'Expense created with ' . count($minimizedTxns) . ' optimized transaction(s).';
        header('Location:/dashboard');
    }

    private function minimizeDebts(array $netBalances): array {
        $eps = 0.01;
        $positive = [];
        $negative = [];
        foreach ($netBalances as $userId => $balance) {
            if ($balance > $eps) $positive[(int)$userId] = $balance;
            elseif ($balance < -$eps) $negative[(int)$userId] = abs($balance);
        }

        $transactions = [];
        while (!empty($positive) && !empty($negative)) {
            arsort($positive);
            arsort($negative);
            $creditorId = array_key_first($positive);
            $debtorId   = array_key_first($negative);
            $amount     = min($positive[$creditorId], $negative[$debtorId]);

            $transactions[] = [
                'lender'   => $creditorId,
                'borrower' => $debtorId,
                'amount'   => $amount
            ];

            $positive[$creditorId] -= $amount;
            $negative[$debtorId]   -= $amount;

            if ($positive[$creditorId] < $eps) unset($positive[$creditorId]);
            if ($negative[$debtorId] < $eps)   unset($negative[$debtorId]);
        }
        return $transactions;
    }

    private function sendGroupExpenseEmails(int $eid, array $allIds, string $description, float $totalAmount, array $shares, array $paidAmounts, array $txnIds, int $creatorId): void {
        $dateObj = new DateTime('now', new DateTimeZone('UTC'));
        $dateObj->setTimezone(new DateTimeZone('Asia/Dhaka'));
        $formattedTime = $dateObj->format('d M Y, h:i A') . ' (GMT+6)';

        $userObjects = [];
        foreach ($allIds as $pid) {
            $u = $this->userModel->findUserById($pid);
            if ($u) $userObjects[$pid] = $u;
        }

        $fullTxns = [];
        foreach ($txnIds as $tid) {
            $t = $this->transactionModel->getTransactionById($tid);
            if ($t) $fullTxns[] = $t;
        }

        $mailer = new Mailer();

        foreach ($allIds as $pid) {
            $u = $userObjects[$pid] ?? null;
            if (!$u || empty($u->email)) continue;

            $myShare   = $shares[$pid] ?? 0;
            $myPaid    = $paidAmounts[$pid] ?? 0;
            $myNet     = $myPaid - $myShare;

            if ($myNet > 0) {
                $themeColor = '#C9A227';
                $mainMsg    = "You paid <strong>৳" . number_format($myPaid, 2) . "</strong> for <em>{$description}</em>.";
                $subMsg     = "Others owe you a total of <strong>৳" . number_format($myNet, 2) . "</strong>.";
            } elseif ($myNet < 0) {
                $themeColor = '#dc3545';
                $mainMsg    = "You owe <strong>৳" . number_format(abs($myNet), 2) . "</strong> for <em>{$description}</em>.";
                $subMsg     = "Please settle your share with the payer(s) at your earliest convenience.";
            } else {
                $themeColor = '#6c757d';
                $mainMsg    = "You are settled for <em>{$description}</em>.";
                $subMsg     = "Your contribution exactly covered your share. No further action needed.";
            }

            $paidStr = implode(', ', array_filter(array_map(function($pid2) use ($userObjects, $paidAmounts) {
                if (($paidAmounts[$pid2] ?? 0) > 0) {
                    $n = isset($userObjects[$pid2]) ? htmlspecialchars($userObjects[$pid2]->name) : "ID:{$pid2}";
                    return $n . ' (৳' . number_format($paidAmounts[$pid2], 2) . ')';
                }
                return null;
            }, array_keys($paidAmounts))));

            $details = [
                'Expense'      => htmlspecialchars($description),
                'Total Bill'   => '৳' . number_format($totalAmount, 2),
                'Your Share'   => '৳' . number_format($myShare, 2),
                'You Paid'     => '৳' . number_format($myPaid, 2),
                'Paid By'      => $paidStr ?: 'N/A',
                'Participants' => count($allIds),
                'Date'         => $formattedTime,
            ];

            $txnCards = [];
            foreach ($fullTxns as $t) {
                $lenderObj   = $userObjects[(int)$t->lender_id] ?? null;
                $borrowerObj = $userObjects[(int)$t->borrower_id] ?? null;
                if (!$lenderObj || !$borrowerObj) continue;
                if ((int)$t->lender_id !== $pid && (int)$t->borrower_id !== $pid) continue;

                $role = ((int)$t->lender_id === $pid) ? 'You are owed this' : 'You owe this';
                $txnCards[] = [
                    'From (Borrower)' => htmlspecialchars($borrowerObj->name) . ((int)$t->borrower_id === $pid ? ' (You)' : ''),
                    'To (Lender)'     => htmlspecialchars($lenderObj->name)   . ((int)$t->lender_id   === $pid ? ' (You)' : ''),
                    'Amount'          => '৳' . number_format((float)$t->amount, 2),
                    'Your Role'       => $role,
                    'Status'          => 'Pending Settlement',
                ];
            }

            $subject = 'Group Expense: ' . $description;
            $html    = EmailTemplate::generate(
                'Group Expense Summary',
                $u->name,
                $mainMsg,
                $subMsg,
                $details,
                $themeColor,
                $txnCards
            );

            $mailer->send($u->email, $u->name, $subject, $html);
            $this->userModel->logEmail($u->id, $u->email, $subject);

            $notifMsg = $myNet > 0
                ? "You are owed ৳" . number_format($myNet, 2) . " for '{$description}'."
                : ($myNet < 0 ? "You owe ৳" . number_format(abs($myNet), 2) . " for '{$description}'." : "Settled for '{$description}'.");
            $this->notificationModel->send($u->id, 'Group Expense', $notifMsg);
        }
    }
}
