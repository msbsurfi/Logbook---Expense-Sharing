<?php
class EmailTemplate {
    public static function generate(
        string $title,
        string $userName,
        string $mainMsg,
        string $subMsg,
        array $details,
        string $themeColor = '
        array $transactionCards = []
    ): string {
        $detailsHtml = '';
        foreach ($details as $label => $value) {
            $detailsHtml .= "<tr>
                <td style='padding:8px 0;color:
                <td style='padding:8px 0;color:
            </tr>
            <tr><td colspan='2' style='border-bottom:1px solid 
        }

        $cardsHtml = '';
        if (!empty($transactionCards)) {
            $cardsHtml = "<h3 style='margin:24px 0 12px 0;font-size:16px;color:
            foreach ($transactionCards as $card) {
                $cardRows = '';
                foreach ($card as $lbl => $val) {
                    $cardRows .= "<tr>
                        <td style='padding:5px 0;color:
                        <td style='padding:5px 0;color:
                    </tr>";
                }
                $cardsHtml .= "<table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'
                    style='background:
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
<body style='margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;background-color:
<table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%' style='background-color:
<tr><td align='center'>
<table role='presentation' cellspacing='0' cellpadding='0' border='0' width='600'
    style='background-color:
<tr>
    <td style='background-color:{$themeColor};padding:28px 24px;text-align:center;'>
        <h1 style='margin:0;color:
    </td>
</tr>
<tr>
    <td style='padding:32px 28px;'>
        <p style='margin:0 0 16px 0;font-size:16px;color:
        <p style='margin:0 0 8px 0;font-size:16px;line-height:1.6;color:
        <p style='margin:0 0 28px 0;font-size:14px;color:
        <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'
            style='background-color:
            <tr><td style='padding:18px;'>
                <table role='presentation' cellspacing='0' cellpadding='0' border='0' width='100%'>
                    {$detailsHtml}
                </table>
            </td></tr>
        </table>
        {$cardsHtml}
        <p style='margin:16px 0 0 0;font-size:12px;color:
            This is an automated notification from Logbook. Please do not reply to this email.
        </p>
    </td>
</tr>
<tr>
    <td style='background-color:
        <p style='margin:0 0 6px 0;font-size:14px;font-weight:700;color:
        <p style='margin:0 0 8px 0;font-size:12px;color:
        <p style='margin:0;font-size:11px;color:
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
