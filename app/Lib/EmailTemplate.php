<?php
class EmailTemplate {
    public static function generate(
        string $title,
        string $userName,
        string $mainMsg,
        string $subMsg,
        array $details,
        string $themeColor = '#C9A227',
        array $transactionCards = []
    ): string {
        $detailsHtml = '';
        foreach ($details as $label => $value) {
            $detailsHtml .= "<tr>
                <td style='padding:8px 0;color:#666;font-size:14px;width:45%;vertical-align:top;'>" . htmlspecialchars((string)$label) . "</td>
                <td style='padding:8px 0;color:#333;font-size:14px;font-weight:600;text-align:right;'>{$value}</td>
            </tr>
            <tr><td colspan='2' style='border-bottom:1px solid #eee;font-size:1px;line-height:1px;'></td></tr>";
        }

        $cardsHtml = '';
        if (!empty($transactionCards)) {
            $cardsHtml = "<h3 style='margin:24px 0 12px 0;font-size:16px;color:#333;font-weight:600;'>Transaction Breakdown</h3>";
            foreach ($transactionCards as $card) {
                $cardRows = '';
                foreach ($card as $lbl => $val) {
                    $cardRows .= "<tr>
                        <td style='padding:5px 0;color:#666;font-size:13px;width:45%;'>" . htmlspecialchars((string)$lbl) . "</td>
                        <td style='padding:5px 0;color:#333;font-size:13px;font-weight:600;text-align:right;'>{$val}</td>
                    </tr>";
                }
                $cardsHtml .= "<table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'
                    style='background:#ffffff;border-radius:8px;border:1px solid #dee2e6;margin-bottom:10px;'>
                    <tr><td style='padding:14px;'>
                        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>{$cardRows}</table>
                    </td></tr></table>";
            }
        }

        $year = date('Y');
        return "<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width,initial-scale=1.0'>
<title>{$title}</title>
</head>
<body style='margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;background-color:#f4f6f8;color:#333;'>
<table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color:#f4f6f8;padding:40px 0;'>
<tr><td align='center'>
<table role='presentation' cellspacing='0' cellpadding='0' border='0' width='600'
    style='background-color:#ffffff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.08);overflow:hidden;max-width:90%;'>
<tr>
    <td style='background-color:{$themeColor};padding:28px 24px;text-align:center;'>
        <h1 style='margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:0.3px;'>{$title}</h1>
    </td>
</tr>
<tr>
    <td style='padding:32px 28px;'>
        <p style='margin:0 0 16px 0;font-size:16px;color:#333;'>Hello <strong>{$userName}</strong>,</p>
        <p style='margin:0 0 8px 0;font-size:16px;line-height:1.6;color:#333;'>{$mainMsg}</p>
        <p style='margin:0 0 28px 0;font-size:14px;color:#666;line-height:1.5;'>{$subMsg}</p>
        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'
            style='background-color:#f8f9fa;border-radius:8px;border:1px solid #e9ecef;margin-bottom:24px;'>
            <tr><td style='padding:18px;'>
                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                    {$detailsHtml}
                </table>
            </td></tr>
        </table>
        {$cardsHtml}
        <p style='margin:16px 0 0 0;font-size:12px;color:#aaa;text-align:center;line-height:1.4;'>
            This is an automated notification from Logbook. Please do not reply to this email.
        </p>
    </td>
</tr>
<tr>
    <td style='background-color:#f8f4e8;padding:20px 28px;text-align:center;border-top:3px solid {$themeColor};'>
        <p style='margin:0 0 6px 0;font-size:14px;font-weight:700;color:#7a6020;'>Logbook &mdash; Expense Tracker</p>
        <p style='margin:0 0 8px 0;font-size:12px;color:#888;'>&copy; {$year} Logbook. All rights reserved.</p>
        <p style='margin:0;font-size:11px;color:#aaa;line-height:1.6;'>
            Account &amp; Support: <a href='mailto:support@YOURDOMAIN' style='color:{$themeColor};text-decoration:none;'>support@YOURDOMAIN</a>
            &nbsp;&bull;&nbsp;
            Report Abuse: <a href='mailto:abuse@YOURDOMAIN' style='color:{$themeColor};text-decoration:none;'>abuse@YOURDOMAIN</a>
        </p>
    </td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>";
    }
}
