<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

final class InvoicePdfService
{
    public static function stream(array $bill, array $items): void
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(self::html($bill, $items));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'invoice-' . ($bill['bill_no'] ?? $bill['id']) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $dompdf->output();
        exit;
    }

    private static function html(array $bill, array $items): string
    {
        $total = number_format((float)$bill['total_amount'], 2);
        $billNo = htmlspecialchars((string)$bill['bill_no']);
        $studentName = htmlspecialchars((string)$bill['student_name']);
        $parentName = !empty($bill['parent_name']) ? htmlspecialchars((string)$bill['parent_name']) : null;
        $billedTo = $parentName ?? $studentName;
        $dueDate = htmlspecialchars((string)$bill['due_date']);
        $issueDate = date('d M Y');

        $rows = '';
        $srNo = 1;
        foreach ($items as $item) {
            $rows .= '
            <tr>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e8e0d5; color: #333;">' . $srNo++ . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e8e0d5; color: #333;">' . htmlspecialchars((string)$item['description']) . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e8e0d5; text-align: right; color: #333; font-weight: bold;">₹ ' . number_format((float)$item['amount'], 2) . '</td>
            </tr>';
        }

        return '<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
</head>
<body style="margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; background: #fff; color: #222;">

  <!-- Page wrapper with margin -->
  <div style="padding: 40px 48px; max-width: 680px; margin: 0 auto;">

    <!-- Header: School branding -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px; border-bottom: 3px solid #c8813a; padding-bottom: 20px;">
      <tr>
        <td>
          <p style="margin: 0 0 2px 0; font-size: 22px; font-weight: bold; color: #7a3e00; letter-spacing: 0.5px;">Prabudha School</p>
          <p style="margin: 0; font-size: 11px; color: #999; letter-spacing: 1px; text-transform: uppercase;">Fee Invoice</p>
        </td>
        <td align="right">
          <p style="margin: 0 0 2px 0; font-size: 20px; font-weight: bold; color: #c8813a;">INVOICE</p>
          <p style="margin: 0; font-size: 11px; color: #888;"># ' . $billNo . '</p>
        </td>
      </tr>
    </table>

    <!-- Invoice meta: Billed to + Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
      <tr>
        <td width="55%" style="vertical-align: top;">
          <p style="margin: 0 0 4px 0; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #999;">Billed To</p>
          <p style="margin: 0 0 4px 0; font-size: 15px; font-weight: bold; color: #2c2c2c;">' . $billedTo . '</p>
          ' . ($parentName ? '<p style="margin: 0 0 4px 0; font-size: 11px; color: #555;">Student: <strong>' . $studentName . '</strong></p>' : '') . '
          <p style="margin: 0; font-size: 11px; color: #888;">Prabudha School, Karnataka</p>
        </td>
        <td width="45%" align="right" style="vertical-align: top;">
          <table cellpadding="0" cellspacing="0" align="right">
            <tr>
              <td style="font-size: 11px; color: #888; padding-bottom: 4px; padding-right: 12px;">Issue Date</td>
              <td style="font-size: 11px; color: #333; font-weight: bold; padding-bottom: 4px;">' . $issueDate . '</td>
            </tr>
            <tr>
              <td style="font-size: 11px; color: #888; padding-right: 12px;">Due Date</td>
              <td style="font-size: 11px; color: #c8813a; font-weight: bold;">' . $dueDate . '</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <!-- Fee Items Table -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin-bottom: 0;">
      <thead>
        <tr style="background-color: #7a3e00;">
          <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #fff; letter-spacing: 0.8px; font-weight: normal; text-transform: uppercase; width: 40px;">#</th>
          <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #fff; letter-spacing: 0.8px; font-weight: normal; text-transform: uppercase;">Description</th>
          <th style="padding: 10px 12px; text-align: right; font-size: 11px; color: #fff; letter-spacing: 0.8px; font-weight: normal; text-transform: uppercase;">Amount</th>
        </tr>
      </thead>
      <tbody style="background: #fff;">
        ' . $rows . '
      </tbody>
    </table>

    <!-- Total row -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 40px;">
      <tr>
        <td colspan="2" style="padding: 0;"></td>
      </tr>
      <tr>
        <td style="padding: 14px 12px; background: #fdf5ec; border-bottom: 2px solid #c8813a; font-size: 13px; color: #555; font-weight: bold;">Total Amount Due</td>
        <td style="padding: 14px 12px; background: #fdf5ec; border-bottom: 2px solid #c8813a; text-align: right; font-size: 15px; color: #7a3e00; font-weight: bold;">₹ ' . $total . '</td>
      </tr>
    </table>

    <!-- Note -->
    <div style="background: #fdf5ec; border-left: 4px solid #c8813a; padding: 12px 16px; margin-bottom: 40px; border-radius: 2px;">
      <p style="margin: 0; font-size: 11px; color: #7a3e00; font-weight: bold;">Payment Note</p>
      <p style="margin: 4px 0 0 0; font-size: 11px; color: #666;">Please present this invoice at the school office when making payment. Keep a copy for your records.</p>
    </div>

    <!-- Footer -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e0d5c8; padding-top: 16px;">
      <tr>
        <td style="font-size: 10px; color: #aaa;">Prabudha School &bull; Karnataka, India</td>
        <td align="right" style="font-size: 10px; color: #aaa;">Generated on ' . $issueDate . '</td>
      </tr>
    </table>

  </div>

</body>
</html>';
    }
}
