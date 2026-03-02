<?php
$typeLabel = $invoice['document_type'] === 'quotation' ? 'QUOTATION' : 'INVOICE';
$isPaid = $invoice['is_paid'] ?? false;
$currency = htmlspecialchars($invoice['currency_code']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $typeLabel ?> <?= htmlspecialchars($invoice['document_number']) ?> - <?= htmlspecialchars($invoice['company_name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 13px;
            color: #333;
            background: #f5f5f5;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .invoice-page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 2px 20px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }
        @media print {
            body { background: #fff; }
            .invoice-page { margin: 0; box-shadow: none; width: 100%; }
            .no-print { display: none !important; }
            @page { margin: 0; size: A4; }
        }

        /* Header gradient */
        .header {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 40%, #5eead4 100%);
            padding: 30px 40px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .header-left h1 {
            font-size: 2.8rem;
            font-weight: 800;
            letter-spacing: 3px;
            margin-bottom: 15px;
        }
        .header-left .meta-row {
            display: flex;
            gap: 40px;
        }
        .header-left .meta-row div {
            font-size: 0.85rem;
        }
        .header-left .meta-row strong {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            opacity: 0.85;
            margin-bottom: 2px;
        }
        .header-right {
            text-align: right;
            max-width: 280px;
        }
        .header-right img { max-height: 65px; margin-bottom: 8px; }
        .header-right .company-name { font-size: 1.1rem; font-weight: 700; }
        .header-right .company-detail { font-size: 0.8rem; line-height: 1.5; opacity: 0.9; }

        /* Body */
        .body { padding: 25px 40px; }

        /* Invoice To */
        .invoice-to { margin-bottom: 20px; }
        .invoice-to .label { font-size: 0.75rem; color: #999; text-transform: uppercase; margin-bottom: 3px; }
        .invoice-to .client-name { font-size: 1.1rem; font-weight: 700; margin-bottom: 5px; }
        .invoice-to .detail { font-size: 0.85rem; line-height: 1.6; }
        .invoice-to .detail strong { min-width: 100px; display: inline-block; }

        /* Section title */
        .section-title {
            color: #0d9488;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            margin: 20px 0 12px 0;
            letter-spacing: 1px;
        }

        /* Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .items-table th {
            background: #f0fdfa;
            border: 1px solid #e0e0e0;
            padding: 10px 12px;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            color: #555;
        }
        .items-table td {
            border: 1px solid #e0e0e0;
            padding: 10px 12px;
            vertical-align: top;
        }
        .items-table .text-center { text-align: center; }
        .items-table .text-end { text-align: right; }
        .items-table .desc-cell { font-size: 0.85rem; line-height: 1.5; }
        .items-table .desc-cell strong { display: block; margin-bottom: 2px; }

        /* Totals */
        .totals-area {
            display: flex;
            justify-content: flex-end;
            position: relative;
            margin-bottom: 20px;
        }
        .totals-table { width: 250px; }
        .totals-table td { padding: 6px 12px; font-size: 0.9rem; border: none; }
        .totals-table .total-row td { font-size: 1.1rem; font-weight: 700; border-top: 2px solid #0d9488; }
        .totals-table .amount { text-align: right; color: #0d9488; font-weight: 600; }

        /* PAID Stamp */
        .paid-stamp {
            position: absolute;
            left: 10%;
            top: -10px;
            color: #dc3545;
            font-size: 2rem;
            font-weight: 900;
            border: 2px solid #dc3545;
            border-radius: 15px;
            padding: 1px 6px;
            transform: rotate(-15deg);
            opacity: 0.7;
            letter-spacing: 2px;
            z-index: 10;
            display: inline-block;
            white-space: nowrap;
        }

        /* Notes */
        .notes-box {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .notes-box .notes-title { font-weight: 700; font-size: 0.85rem; margin-bottom: 5px; }
        .notes-box .notes-content { color: #0d9488; font-size: 0.85rem; line-height: 1.6; }

        /* Signature */
        .signature-area {
            text-align: right;
            margin: 25px 0;
        }
        .signature-area img { max-height: 55px; }
        .signature-area .sig-line { border-top: 1px solid #999; margin: 5px 0; width: 200px; display: inline-block; }
        .signature-area .sig-label { font-size: 0.8rem; color: #666; }
        .signature-area .sig-name { font-size: 0.85rem; font-weight: 600; }

        /* Footer */
        .doc-footer {
            text-align: center;
            padding: 15px 40px;
            font-size: 0.7rem;
            color: #999;
            border-top: 1px solid #eee;
        }
        .doc-footer .uuid { font-family: 'Courier New', monospace; font-size: 0.7rem; color: #aaa; }
        .doc-footer .legal { margin-top: 5px; font-style: italic; }

        /* Print button */
        .print-bar {
            text-align: center;
            padding: 15px;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .print-bar button, .print-bar a {
            padding: 10px 24px;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 6px;
            border: none;
            margin: 0 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-print { background: #dc3545; color: #fff; }
        .btn-print:hover { background: #c82333; }
        .btn-back { background: #6c757d; color: #fff; }
        .btn-back:hover { background: #5a6268; }

        /* Read-only watermark */
        .watermark {
            position: fixed;
            bottom: 20px;
            right: 20px;
            font-size: 0.6rem;
            color: #ccc;
            transform: rotate(-5deg);
            opacity: 0.5;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="print-bar no-print">
    <button class="btn-print" onclick="window.print()">
        🖨 Print / Save as PDF
    </button>
    <a class="btn-print" href="/invoices/<?= $invoice['id'] ?>/download-pdf" style="background:#212529;">
        ⬇ Download Signed PDF
    </a>
    <a class="btn-back" href="/invoices/<?= $invoice['id'] ?>">
        ← Back to Preview
    </a>
</div>

<?php
$bgTemplate = $invoice['background_template'] ?? 'none';
$bgMap = [
    'blue-cream-minimalist' => '/img/templates/blue-cream-minimalist.png',
    'blue-white-minimalist' => '/img/templates/blue-white-minimalist.png',
    'blue-modern-medical' => '/img/templates/blue-modern-medical.png',
    'blue-white-geometric' => '/img/templates/blue-white-geometric.png',
    'modern-elegant' => '/img/templates/modern-elegant.jpg',
];
$bgUrl = $bgMap[$bgTemplate] ?? '';
$hasBackground = !empty($bgUrl);

$headerStyle = '';
if ($bgTemplate === 'basic') {
    $headerStyle = 'background: linear-gradient(135deg, #0067a6, #0d9488, #0d9488, #0067a6, #0d9488); ';
}

function applyPlaceholders(string $text, array $inv): string {
    $map = [
        '{{programme}}' => $inv['programme_name'] ?? '',
        '{{Session_no}}' => $inv['session_number'] ?? '',
        '{{invoice_no}}' => $inv['document_number'] ?? '',
        '{{issue_date}}' => $inv['issue_date'] ?? '',
        '{{due_date}}' => $inv['due_date'] ?? '',
        '{{from_company}}' => $inv['company_name'] ?? '',
        '{{client}}' => $inv['client_name'] ?? '',
        '{{total_amount}}' => number_format((float)$inv['total'], 2)
    ];
    return strtr($text, $map);
}
?>

<div class="invoice-page" style="<?= $hasBackground ? 'background-image:url(' . htmlspecialchars($bgUrl) . ');background-size:cover;background-position:center;background-repeat:no-repeat;' : '' ?>">
    <?php if ($hasBackground): ?>
    <div style="position:absolute;inset:0;background:rgba(255,255,255,0.6);z-index:0;"></div>
    <?php endif; ?>
    <!-- HEADER -->
    <div class="header" style="position:relative;z-index:1; <?= $headerStyle ?><?php if ($bgTemplate !== 'basic' && $bgTemplate !== 'none') { echo 'background: none; '; } ?> color: <?php if ($bgTemplate === 'basic') { echo '#fff'; } else { echo '#000'; } ?>;">
        <div class="header-left">
            <h1><?= $typeLabel ?></h1>
            <div class="meta-row">
                <div>
                    <strong>DATE</strong>
                    <?= date('d/m/Y', strtotime($invoice['issue_date'])) ?>
                </div>
                <div>
                    <strong><?= $typeLabel ?> NO.</strong>
                    <?= htmlspecialchars($invoice['document_number']) ?>
                </div>
            </div>
            <?php if (!empty($invoice['programme_name'])): ?>
                <div style="margin-top: 6px; font-size: 0.85rem; opacity: 0.9; background: rgba(255,255,255,0.25); padding: 2px 10px; border-radius: 4px; display: inline-block;">
                    <?= htmlspecialchars($invoice['programme_name']) ?>
                    &ndash; Session <?= htmlspecialchars($invoice['session_number']) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="header-right">
            <?php if ($invoice['logo_path']): ?>
                <img src="<?= htmlspecialchars($invoice['logo_path']) ?>" alt="Logo"><br>
            <?php endif; ?>
            <div class="company-name"><?= htmlspecialchars($invoice['company_name']) ?></div>
            <?php if ($invoice['company_tagline']): ?>
                <div class="company-detail"><?= htmlspecialchars($invoice['company_tagline']) ?></div>
            <?php endif; ?>
            <?php if ($invoice['company_address']): ?>
                <div class="company-detail">Address: <?= nl2br(htmlspecialchars($invoice['company_address'])) ?></div>
                <?php if (!empty($invoice['company_tax_id'])): ?>
                <div class="company-detail">VAT No: <?= htmlspecialchars($invoice['company_tax_id']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($invoice['company_phone']): ?>
                <div class="company-detail">Phone: <?= htmlspecialchars($invoice['company_phone']) ?></div>
            <?php endif; ?>
            <?php if ($invoice['company_email']): ?>
                <div class="company-detail">Email: <?= htmlspecialchars($invoice['company_email']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- BODY -->
    <div class="body" style="position:relative;z-index:1;">
        <!-- Invoice To -->
        <div class="invoice-to">
            <div class="label"><?= $typeLabel ?> TO:</div>
            <div class="client-name"><?= htmlspecialchars($invoice['client_name']) ?></div>
            <div class="detail">
                <?php if ($invoice['project_title']): ?>
                    <strong>For</strong>: <?= htmlspecialchars($invoice['project_title']) ?><br>
                <?php endif; ?>
                <?php if ($invoice['location']): ?>
                    <strong>Location</strong>: <?= htmlspecialchars($invoice['location']) ?><br>
                <?php endif; ?>
                <?php if ($invoice['requested_by']): ?>
                    <strong>Requested by</strong>: <?= htmlspecialchars($invoice['requested_by']) ?><br>
                <?php endif; ?>
                <?php if ($invoice['client_email']): ?>
                    <strong>Email address</strong>: <?= htmlspecialchars($invoice['client_email']) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Services Table -->
        <div class="section-title">DESCRIPTION OF SERVICES</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width:50px;">ITEM</th>
                    <th>DESCRIPTION</th>
                    <th class="text-center" style="width:80px;">QUANTITY</th>
                    <th class="text-end" style="width:120px;">UNIT PRICE</th>
                    <th class="text-end" style="width:120px;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoice['items'])): ?>
                    <?php foreach ($invoice['items'] as $item): ?>
                        <tr>
                            <td class="text-center"><?= $item['item_number'] ?></td>
                            <?php $itemDesc = applyPlaceholders($item['description'], $invoice);
                            $itemDesc = strip_tags($itemDesc, '<p><br><b><strong><i><em><u><s><strike><ul><ol><li><h1><h2><h3>');
                        ?>
                            <td class="desc-cell"><?= $itemDesc ?></td>
                            <td class="text-center"><?= rtrim(rtrim(number_format((float)$item['quantity'], 2), '0'), '.') ?></td>
                            <td class="text-end"><?= $currency ?> <?= number_format((float)$item['unit_price'], 2) ?></td>
                            <td class="text-end"><?= $currency ?> <?= number_format((float)$item['amount'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-area">
            <?php if ($isPaid): ?>
                <div class="paid-stamp">PAID</div>
            <?php endif; ?>
            <table class="totals-table">
                <tr>
                    <td class="text-end"><?= htmlspecialchars($invoice['tax_label'] ?? 'VAT') ?></td>
                    <td class="amount"><?= $currency ?> <?= number_format((float)$invoice['tax_amount'], 2) ?></td>
                </tr>
                <tr class="total-row">
                    <td class="text-end">Total</td>
                    <td class="amount"><?= $currency ?> <?= number_format((float)$invoice['total'], 2) ?></td>
                </tr>
            </table>
        </div>

        <!-- Notes -->
        <?php if (!empty($invoice['notes'])): ?>
            <?php $notesText = applyPlaceholders($invoice['notes'], $invoice);
                $notesText = strip_tags($notesText, '<p><br><b><strong><i><em><u><s><strike><ul><ol><li><h1><h2><h3>');
            ?>
            <div class="notes-box">
                <div class="notes-title">NOTES</div>
                <div class="notes-content"><?= $notesText ?></div>
        <?php endif; ?>

        <!-- Signature -->
        <?php if ($invoice['signature_path']): ?>
            <div class="signature-area">
                <img src="<?= htmlspecialchars($invoice['signature_path']) ?>" alt="Authorized Signature"><br>
                <div class="sig-line"></div><br>
                <div class="sig-label">Authorized Signature</div>
                <div class="sig-name"><?= htmlspecialchars($invoice['company_name']) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="doc-footer">
        <div class="uuid">Invoce No:<?= htmlspecialchars($invoice['uuid']) ?></div>
        <div class="legal">
            <span style="font-size: 0.4rem;">This document is digitally generated and signed by <?= htmlspecialchars($invoice['company_name']) ?>.
            This is a read-only document. Any unauthorized modification is prohibited.</span>
        </div>
    </div>
</div>

<div class="watermark no-print">
    Digitally Signed by <?= htmlspecialchars($invoice['company_name']) ?>
</div>

</body>
</html>
