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
        $issueDate = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Kolkata')))->format('d M Y');

        $rows = '';
        $srNo = 1;
        foreach ($items as $item) {
            $rows .= '
            <tr>
                <td style="padding: 10px 12px; border-bottom: 1px solid #ccc;">' . $srNo++ . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #ccc;">' . htmlspecialchars((string)$item['description']) . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #ccc; text-align: right; font-weight: bold;">₹ ' . number_format((float)$item['amount'], 2) . '</td>
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

    <!-- Header: School branding (neutral tones + strong border for B&amp;W prints) -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px; border-bottom: 3px solid #333; padding-bottom: 20px;">
      <tr>
        <td>
          <p style="margin: 0 0 2px 0; font-size: 22px; font-weight: bold; color: #111;">Prabudha School</p>
          <p style="margin: 0; font-size: 11px; color: #444; letter-spacing: 1px; text-transform: uppercase;">Fee Invoice</p>
        </td>
        <td align="right">
          <p style="margin: 0 0 2px 0; font-size: 20px; font-weight: bold; color: #111;">INVOICE</p>
          <p style="margin: 0; font-size: 11px; color: #333;"># ' . $billNo . '</p>
        </td>
      </tr>
    </table>

    <!-- Invoice meta: Billed to + Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
      <tr>
        <td width="55%" style="vertical-align: top;">
          <p style="margin: 0 0 4px 0; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #444;">Billed To</p>
          <p style="margin: 0 0 4px 0; font-size: 15px; font-weight: bold; color: #111;">' . $billedTo . '</p>
          ' . ($parentName ? '<p style="margin: 0 0 4px 0; font-size: 11px; color: #333;">Student: <strong>' . $studentName . '</strong></p>' : '') . '
          <p style="margin: 0; font-size: 11px; color: #444;">Prabudha School KR Pete, Karnataka</p>
        </td>
        <td width="45%" align="right" style="vertical-align: top;">
          <table cellpadding="0" cellspacing="0" align="right">
            <tr>
              <td style="font-size: 11px; color: #444; padding-bottom: 4px; padding-right: 12px;">Issue Date</td>
              <td style="font-size: 11px; color: #111; font-weight: bold; padding-bottom: 4px;">' . $issueDate . '</td>
            </tr>
            <tr>
              <td style="font-size: 11px; color: #444; padding-right: 12px;">Due Date</td>
              <td style="font-size: 11px; color: #111; font-weight: bold;">' . $dueDate . '</td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <!-- Fee Items Table -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin-bottom: 0; border: 1px solid #333;">
      <thead>
        <tr style="background-color: #e8e8e8;">
          <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #111; letter-spacing: 0.8px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #333; width: 40px;">#</th>
          <th style="padding: 10px 12px; text-align: left; font-size: 11px; color: #111; letter-spacing: 0.8px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #333;">Description</th>
          <th style="padding: 10px 12px; text-align: right; font-size: 11px; color: #111; letter-spacing: 0.8px; font-weight: bold; text-transform: uppercase; border-bottom: 2px solid #333;">Amount</th>
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
        <td style="padding: 14px 12px; background: #eee; border: 2px solid #333; border-top: 0; font-size: 13px; color: #111; font-weight: bold;">Total Amount Due</td>
        <td style="padding: 14px 12px; background: #eee; border: 2px solid #333; border-top: 0; border-left: 0; text-align: right; font-size: 15px; color: #111; font-weight: bold;">₹ ' . $total . '</td>
      </tr>
    </table>

    <!-- Payment guidance -->
    <div style="background: #f5f5f5; border: 1px solid #555; border-left: 4px solid #333; padding: 12px 16px; margin-bottom: 40px;">
      <p style="margin: 0; font-size: 11px; color: #111; font-weight: bold;">Payment Information</p>
      <p style="margin: 6px 0 0 0; font-size: 11px; color: #333;">Please include the invoice number in all payment communications.</p>
      <p style="margin: 4px 0 0 0; font-size: 11px; color: #333;">Payment is due on or before the due date mentioned above.</p>
      <p style="margin: 4px 0 0 0; font-size: 11px; color: #333;">For fee clarification, contact the accounts office during working hours.</p>
      <p style="margin: 4px 0 0 0; font-size: 11px; color: #333;">This is a system-generated invoice and does not require a signature.</p>
    </div>

    <!-- Footer -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #333; padding-top: 16px;">
      <tr>
        <td style="font-size: 10px; color: #444;">
          <strong style="color: #111;">Prabudha School KR Pete</strong><br />
          Karnataka, India
        </td>
        <td align="right" style="font-size: 10px; color: #444;">Generated on ' . $issueDate . '</td>
      </tr>
    </table>

  </div>

</body>
</html>';
    }
}
