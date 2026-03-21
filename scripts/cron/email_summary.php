<?php
// scripts/cron/email_summary.php
// Usage (cron): php /path/to/scripts/cron/email_summary.php
// Sends an admin summary with weekly/monthly email counts and transaction stats.

chdir(__DIR__.'/../..'); // move to project root

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Models/Database.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Lib/Mailer.php';

$db = new Database();

// Weekly and monthly email counts
$db->query("SELECT COUNT(*) AS c FROM email_log WHERE sent_at >= (NOW() - INTERVAL 7 DAY)");
$weeklyEmails = (int)($db->fetchOne()->c ?? 0);

$db->query("SELECT COUNT(*) AS c FROM email_log WHERE sent_at >= (NOW() - INTERVAL 30 DAY)");
$monthlyEmails = (int)($db->fetchOne()->c ?? 0);

// Transaction stats
$db->query("SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS s FROM transactions WHERE created_at >= (NOW() - INTERVAL 7 DAY)");
$wk = $db->fetchOne();
$db->query("SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS s FROM transactions WHERE created_at >= (NOW() - INTERVAL 30 DAY)");
$mo = $db->fetchOne();
$db->query("SELECT COUNT(*) AS c, COALESCE(SUM(amount),0) AS s FROM transactions");
$all = $db->fetchOne();

// Active, verified admins
$db->query("SELECT id,name,email FROM users WHERE role='admin' AND status='active' AND email_verified=1 AND banned_at IS NULL");
$admins = $db->fetchAll();

$mailer = new Mailer();
$subject = "Halkhata Summary Report";

$body = "<h2>Halkhata Summary</h2>
<p><strong>Weekly Emails Sent:</strong> {$weeklyEmails}</p>
<p><strong>Monthly Emails Sent:</strong> {$monthlyEmails}</p>
<hr>
<p><strong>Weekly Transactions:</strong> ".(int)($wk->c ?? 0)." | Amount: ৳".number_format((float)($wk->s ?? 0),2)."</p>
<p><strong>Monthly Transactions:</strong> ".(int)($mo->c ?? 0)." | Amount: ৳".number_format((float)($mo->s ?? 0),2)."</p>
<p><strong>Total Transactions:</strong> ".(int)($all->c ?? 0)." | Amount: ৳".number_format((float)($all->s ?? 0),2)."</p>
<p style='color:#666'>Generated at ".gmdate('Y-m-d H:i:s')." UTC</p>";

$userModel = new User();
$sent = 0;

foreach ($admins as $a) {
    if (!empty($a->email)) {
        $mailer->send($a->email, $a->name, $subject, $body);
        $userModel->logEmail($a->id, $a->email, $subject);
        $sent++;
    }
}

echo "Summary emails sent to {$sent} admin(s).\n";