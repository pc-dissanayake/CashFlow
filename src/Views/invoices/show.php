<?php
$typeLabel = $invoice['document_type'] === 'quotation' ? 'QUOTATION' : 'INVOICE';
$isPaid = $invoice['is_paid'] ?? false;
?>

<!-- Action Bar -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <a href="/invoices" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
    <div class="d-flex gap-2 action-buttons">
        <a href="/invoices/<?= $invoice['id'] ?>/pdf" class="btn btn-danger" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </a>
        <a href="/invoices/<?= $invoice['id'] ?>/download-pdf" class="btn btn-dark">
            <i class="bi bi-download"></i> Download Signed PDF
        </a>
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer"></i> Print
        </button>
        <form method="POST" action="/invoices/<?= $invoice['id'] ?>/toggle-paid" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <button type="submit" class="btn <?= $isPaid ? 'btn-warning' : 'btn-success' ?>">
                <i class="bi bi-<?= $isPaid ? 'x-circle' : 'check-circle' ?>"></i>
                <?= $isPaid ? 'Remove PAID Stamp' : 'Stamp as PAID' ?>
            </button>
        </form>
        <a href="/invoices/<?= $invoice['id'] ?>/edit" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <?php if ($invoice['document_type'] === 'quotation'): ?>
            <form method="POST" action="/invoices/<?= $invoice['id'] ?>/generate-invoice" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <button type="submit" class="btn btn-outline-success">
                    <i class="bi bi-file-earmark-arrow-up"></i> Generate Invoice
                </button>
            </form>
        <?php endif; ?>
        <form method="POST" action="/invoices/<?= $invoice['id'] ?>/duplicate" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <button type="submit" class="btn btn-outline-info">
                <i class="bi bi-copy"></i> Duplicate
            </button>
        </form>
        <form method="POST" action="/invoices/<?= $invoice['id'] ?>/delete" class="d-inline"
              onsubmit="return confirm('Are you sure you want to delete this document?')">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-trash"></i> Delete
            </button>
        </form>
    </div>
</div>

<!-- Invoice Preview -->
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
$bgUrlCss = '';
if ($bgUrl) {
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? realpath(__DIR__ . '/../../..');
    $filePath = rtrim($docRoot, '/') . $bgUrl;
    if (file_exists($filePath)) {
        $bgUrlCss = 'file://' . $filePath;
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $bgUrlCss = $scheme . '://' . $host . $bgUrl;
    }
}
$hasBackground = !empty($bgUrlCss);

// header background only for basic template
$headerStyle = '';
if ($bgTemplate === 'basic') {
    $headerStyle = 'background: linear-gradient(135deg, #0067a6, #0d9488, #0d9488, #0067a6, #0d9488); ';
}

// placeholder replacement helper
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
<div class="invoice-preview card shadow" id="invoicePreview" style="<?= $hasBackground ? 'background-image:url(' . htmlspecialchars($bgUrlCss) . ');background-size:cover;background-position:center;background-repeat:no-repeat;' : '' ?>">
    <div class="card-body p-4" style="<?= $hasBackground ? 'background:#ffffff;' : '' ?>">

        <!-- Header with gradient -->
        <div class="invoice-header" style="<?= $headerStyle ?>border-radius: 8px 8px 0 0; padding: 30px; margin: -1.5rem -1.5rem 0 -1.5rem; color: <?php if ($bgTemplate === 'basic' ) { echo '#fff'; } else { echo '#000'; } ?>;">
            <div class="row align-items-start">
                <div class="col-md-5">
                    <h1 class="mb-0 fw-bold" style="font-size: 2.5rem; letter-spacing: 2px;"><?= $typeLabel ?></h1>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-6">
                                <strong>DATE</strong><br>
                                <?= date('d/m/Y', strtotime($invoice['issue_date'])) ?>
                            </div>
                            <div class="col-6">
                                <strong><?= $typeLabel ?> NO.</strong><br>
                                <?= htmlspecialchars($invoice['document_number']) ?>
                            </div>
                        </div>
                        <?php if (!empty($invoice['programme_name'])): ?>
                            <div class="mt-2">
                                <span style="background: rgba(255,255,255,0.25); padding: 2px 10px; border-radius: 4px; font-size: 0.85rem;">
                                    <i class="bi bi-collection"></i> <?= htmlspecialchars($invoice['programme_name']) ?>
                                        &ndash; Session <?= htmlspecialchars($invoice['session_number']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-7 text-end">
                    <?php if ($invoice['logo_path']): ?>
                        <img src="<?= htmlspecialchars($invoice['logo_path']) ?>" alt="Company Logo"
                             style="max-height: 70px; margin-bottom: 10px;" class="mb-2">
                        <br>
                    <?php endif; ?>
                    <strong class="fs-5"><?= htmlspecialchars($invoice['company_name']) ?></strong><br>
                    <?php if ($invoice['company_tagline']): ?>
                        <small><?= htmlspecialchars($invoice['company_tagline']) ?></small><br>
                    <?php endif; ?>
                    <?php if ($invoice['company_address']): ?>
                        <small>Address: <?= nl2br(htmlspecialchars($invoice['company_address'])) ?></small><br>
                    <?php endif; ?>
                    <?php if (!empty($invoice['company_tax_id'])): ?>
                        <small>VAT No: <?= htmlspecialchars($invoice['company_tax_id']) ?></small><br>
                    <?php endif; ?>
                    <?php if ($invoice['company_phone']): ?>
                        <small>Phone: <?= htmlspecialchars($invoice['company_phone']) ?></small><br>
                    <?php endif; ?>
                    <?php if ($invoice['company_email']): ?>
                        <small>Email: <?= htmlspecialchars($invoice['company_email']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Invoice To Section -->
        <div class="mt-4 mb-4" style="padding: 0 10px;">
            <div class="mb-2">
                <small class="text-muted"><?= $typeLabel ?> TO:</small><br>
                <strong class="fs-5"><?= htmlspecialchars($invoice['client_name']) ?></strong>
            </div>
            <?php if ($invoice['project_title']): ?>
                <div><strong>For</strong>: <?= htmlspecialchars($invoice['project_title']) ?></div>
            <?php endif; ?>
            <?php if ($invoice['location']): ?>
                <div><strong>Location</strong>: <?= htmlspecialchars($invoice['location']) ?></div>
            <?php endif; ?>
            <?php if ($invoice['requested_by']): ?>
                <div><strong>Requested by</strong>: <?= htmlspecialchars($invoice['requested_by']) ?></div>
            <?php endif; ?>
            <?php if ($invoice['client_email']): ?>
                <div><strong>Email address</strong>: <?= htmlspecialchars($invoice['client_email']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Description of Services -->
        <div style="padding: 0 10px;">
            <h6 class="text-uppercase fw-bold" style="color: #0d9488;">DESCRIPTION OF SERVICES</h6>

            <table class="table table-bordered mt-3">
                <thead style="background-color: #f0fdfa;">
                    <tr>
                        <th width="60" class="text-center">ITEM</th>
                        <th>DESCRIPTION</th>
                        <th width="100" class="text-center">QUANTITY</th>
                        <th width="130" class="text-end">UNIT PRICE</th>
                        <th width="130" class="text-end">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoice['items'])): ?>
                        <?php foreach ($invoice['items'] as $item): ?>
                            <tr>
                                <td class="text-center"><?= $item['item_number'] ?></td>
                                <?php $itemDesc = applyPlaceholders($item['description'], $invoice);
                                    // allow basic formatting tags from WYSIWYG editor
                                    $itemDesc = strip_tags($itemDesc, '<p><br><b><strong><i><em><u><s><strike><ul><ol><li><h1><h2><h3>');
                                ?>
                                <td><?= $itemDesc ?></td>
                                <td class="text-center"><?= rtrim(rtrim(number_format((float)$item['quantity'], 2), '0'), '.') ?></td>
                                <td class="text-end"><?= htmlspecialchars($invoice['currency_code']) ?> <?= number_format((float)$item['unit_price'], 2) ?></td>
                                <td class="text-end"><?= htmlspecialchars($invoice['currency_code']) ?> <?= number_format((float)$item['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="row justify-content-end position-relative" style="padding: 0 10px;">
            <!-- PAID Stamp -->
            <?php if ($isPaid): ?>
                <div class="paid-stamp" style="position: absolute; left: 35%; top: -10px; z-index: 100;">
                    <div style="color: #dc3545; font-size: 3rem; font-weight: 900; border: 2px solid #dc3545; border-radius: 15px; padding: 1px 6px; transform: rotate(-15deg); opacity: 0.85; letter-spacing: 2px; display:inline-block; white-space:nowrap;">
                        PAID
                    </div>
                </div>
            <?php endif; ?>

            <div class="col-md-5">
                <table class="table table-sm mb-0">
                    <?php if ((float)$invoice['tax_rate'] > 0): ?>
                        <tr>
                            <td class="text-end border-0"><?= htmlspecialchars($invoice['tax_label'] ?? 'VAT') ?></td>
                            <td class="text-end border-0" style="color: #0d9488; font-weight: bold;">
                                <?= htmlspecialchars($invoice['currency_code']) ?> <?= number_format((float)$invoice['tax_amount'], 2) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td class="text-end border-0"><?= htmlspecialchars($invoice['tax_label'] ?? 'VAT') ?></td>
                            <td class="text-end border-0" style="color: #0d9488; font-weight: bold;">
                                <?= htmlspecialchars($invoice['currency_code']) ?> 0.00
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-end border-0 fw-bold">Total</td>
                        <td class="text-end border-0 fw-bold fs-5" style="color: #0d9488;">
                            <?= htmlspecialchars($invoice['currency_code']) ?> <?= number_format((float)$invoice['total'], 2) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notes -->
        <?php if (!empty($invoice['notes'])): ?>
            <div class="mt-4" style="padding: 0 10px;">
                <div style="border: 1px solid #dee2e6; border-radius: 6px; padding: 15px;">
                    <strong>NOTES</strong>
                    <?php $notesText = applyPlaceholders($invoice['notes'], $invoice);
                        $notesText = strip_tags($notesText, '<p><br><b><strong><i><em><u><s><strike><ul><ol><li><h1><h2><h3>');
                    ?>
                    <p class="mb-0 mt-2" style="color: #0d9488;"><?= $notesText ?></p>                  </div>
              </div>
          <?php endif; ?>        <!-- Signature & Auth Section -->
        <?php if ($invoice['signature_path']): ?>
            <div class="mt-4 text-end" style="padding: 0 10px;">
                <div class="d-inline-block text-center">
                    <img src="<?= htmlspecialchars($invoice['signature_path']) ?>" alt="Signature" style="max-height: 60px;">
                    <hr style="margin: 5px 0;">
                    <small>Authorized Signature</small><br>
                    <small class="fw-bold"><?= htmlspecialchars($invoice['company_name']) ?></small>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer with UUID -->
        <div class="mt-4 pt-3 border-top text-center">
            <small class="text-muted" style="font-family: monospace; font-size: 0.9rem;">Invoce No:
                <?= htmlspecialchars($invoice['uuid']) ?>
            </small>
            <br>
            <small class="text-muted" style="font-size: 0.6rem;">
                This document is digitally generated and signed by <?= htmlspecialchars($invoice['company_name']) ?>.
                Any unauthorized modification is prohibited.
            </small>
        </div>

    </div>
</div>

<style>
@media print {
    .no-print, .navbar, .alert { display: none !important; }
    .invoice-preview { box-shadow: none !important; border: none !important; }
    body { background: #fff !important; }
    .container { max-width: 100% !important; padding: 0 !important; }
}
.invoice-preview {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
}

/* Mobile / small-screen improvements */
.action-buttons { display:flex; gap:0.5rem; }
.no-print .action-buttons { flex-wrap: wrap; }
.no-print .action-buttons form,
.no-print .action-buttons a,
.no-print .action-buttons button { margin-bottom: 0.35rem; }

@media (max-width: 576px) {
    .invoice-preview { margin: 0 8px; }
    .invoice-header { padding: 18px; }
    .invoice-header h1 { font-size: 1.5rem; letter-spacing: 1px; }
    .invoice-header .col-md-5, .invoice-header .col-md-7 { text-align: left; }
    .invoice-header .col-md-7 img { max-height: 50px; }
    .invoice-header .row > div { margin-bottom: 0.5rem; }
    .paid-stamp { left: 5%; top: -5px; transform: rotate(-8deg); font-size: 2rem; }
    .invoice-preview { padding-left: 6px; padding-right: 6px; }
    .table th, .table td { font-size: 0.9rem; }
}

@media (max-width: 420px) {
    .invoice-header h1 { font-size: 1.2rem; }
    .invoice-preview { padding-left: 4px; padding-right: 4px; }
}
</style>
