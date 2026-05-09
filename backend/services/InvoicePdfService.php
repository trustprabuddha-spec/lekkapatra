<?php

declare(strict_types=1);

use Dompdf\Dompdf;
use Dompdf\Options;

final class InvoicePdfService
{
    public static function stream(array $bill, array $items, array $emis): void
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(self::html($bill, $items, $emis));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'invoice-' . ($bill['bill_no'] ?? $bill['id']) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $dompdf->output();
        exit;
    }

    private static function html(array $bill, array $items, array $emis): string
    {
        $total = number_format((float)$bill['total_amount'], 2);
        $rows = '';
        foreach ($items as $item) {
            $rows .= '<tr><td>' . htmlspecialchars((string)$item['description']) . '</td><td style="text-align:right;">' . number_format((float)$item['amount'], 2) . '</td></tr>';
        }

        $emiRows = '';
        foreach ($emis as $emi) {
            $emiRows .= '<tr><td>' . (int)$emi['installment_no'] . '</td><td>' . htmlspecialchars((string)$emi['due_date']) . '</td><td style="text-align:right;">' . number_format((float)$emi['amount'], 2) . '</td></tr>';
        }

        return '<html><body style="font-family: DejaVu Sans, sans-serif;">
            <h2>School Fee Invoice</h2>
            <p><strong>Bill No:</strong> ' . htmlspecialchars((string)$bill['bill_no']) . '</p>
            <p><strong>Student:</strong> ' . htmlspecialchars((string)$bill['student_name']) . '</p>
            <p><strong>Source:</strong> ' . htmlspecialchars((string)$bill['student_source']) . '</p>
            <p><strong>Due Date:</strong> ' . htmlspecialchars((string)$bill['due_date']) . '</p>
            <table width="100%" border="1" cellspacing="0" cellpadding="6">
              <thead><tr><th align="left">Description</th><th align="right">Amount</th></tr></thead>
              <tbody>' . $rows . '</tbody>
            </table>
            <p style="text-align:right;"><strong>Total: Rs. ' . $total . '</strong></p>
            <h3>EMI Plan</h3>
            <table width="100%" border="1" cellspacing="0" cellpadding="6">
              <thead><tr><th>Installment</th><th>Due Date</th><th align="right">Amount</th></tr></thead>
              <tbody>' . ($emiRows ?: '<tr><td colspan="3">No EMI plan</td></tr>') . '</tbody>
            </table>
        </body></html>';
    }
}
