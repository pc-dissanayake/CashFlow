<?php
declare(strict_types=1);

namespace Controllers;

use Models\Invoice;
use Models\InvoiceItem;
use Models\CompanyProfile;
use Models\ClientProfile;
use Models\Programme;

class InvoiceController extends Controller {
    private Invoice $invoiceModel;
    private InvoiceItem $itemModel;
    private CompanyProfile $companyModel;
    private ClientProfile $clientModel;
    private Programme $programmeModel;

    public function __construct() {
        $this->invoiceModel = new Invoice();
        $this->itemModel = new InvoiceItem();
        $this->companyModel = new CompanyProfile();
        $this->clientModel = new ClientProfile();
        $this->programmeModel = new Programme();
    }

    // ── LIST ──────────────────────────────────────────────
    public function index(): void {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $type = $_GET['type'] ?? null; // 'invoice' or 'quotation'

        $invoices = $this->invoiceModel->findByUser($userId, $type);

        $this->render('invoices/index', [
            'invoices' => $invoices,
            'type' => $type,
            'csrf_token' => $this->csrf()
        ]);
    }

    // ── CREATE FORM ───────────────────────────────────────
    public function create(): void {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $docType = $_GET['type'] ?? 'invoice';

        $companies = $this->companyModel->findByUser($userId);
        $clients = $this->clientModel->findByUser($userId);
        $programmes = $this->programmeModel->findByUser($userId);

        if (empty($companies)) {
            $_SESSION['error'] = 'Please create a company profile first.';
            $this->redirect('/company-profiles/create');
            return;
        }

        if (empty($programmes)) {
            $_SESSION['error'] = 'Please create a programme first to generate invoice numbers.';
            $this->redirect('/programmes/create');
            return;
        }

        // Default: use first programme prefix for initial number
        $defaultProgramme = $programmes[0];
        $nextNumber = $this->invoiceModel->getNextDocumentNumber(
            $userId, $docType, $defaultProgramme['prefix'], (int)$defaultProgramme['id']
        );

        $this->render('invoices/create', [
            'companies' => $companies,
            'clients' => $clients,
            'programmes' => $programmes,
            'docType' => $docType,
            'nextNumber' => $nextNumber,
            'csrf_token' => $this->csrf()
        ]);
    }

    // ── STORE ─────────────────────────────────────────────
    public function store(): void {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = $_SESSION['user_id'];

        $data = [
            'uuid' => $this->invoiceModel->generateUuid(),
            'document_type' => $_POST['document_type'] ?? 'invoice',
            'document_number' => trim($_POST['document_number'] ?? ''),
            'company_profile_id' => (int)($_POST['company_profile_id'] ?? 0),
            'client_profile_id' => (int)($_POST['client_profile_id'] ?? 0),
            'programme_id' => !empty($_POST['programme_id']) ? (int)$_POST['programme_id'] : null,
            'project_title' => trim($_POST['project_title'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'requested_by' => trim($_POST['requested_by'] ?? ''),
            'client_email' => trim($_POST['client_email'] ?? ''),
            'currency_code' => trim($_POST['currency_code'] ?? 'LKR'),
            'tax_label' => trim($_POST['tax_label'] ?? 'VAT'),
            'tax_rate' => (float)($_POST['tax_rate'] ?? 0),
            'status' => $_POST['status'] ?? 'draft',
            'is_paid' => isset($_POST['is_paid']),
            'notes' => trim($_POST['notes'] ?? ''),
            'background_template' => $_POST['background_template'] ?? 'none',
            'session_number' => trim($_POST['session_number'] ?? ''),
            'issue_date' => $_POST['issue_date'] ?? date('Y-m-d'),
            'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            'user_id' => $userId,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ];

        if (empty($data['document_number']) || empty($data['company_profile_id']) || empty($data['client_profile_id']) || empty($data['programme_id'])) {
            $_SESSION['error'] = 'Document number, company profile, client, and programme are required.';
            $this->redirect('/invoices/create?type=' . $data['document_type']);
            return;
        }

        if ($data['is_paid']) {
            $data['status'] = 'paid';
            $data['paid_date'] = date('Y-m-d');
        }

        $invoiceId = $this->invoiceModel->create($data);

        // Save line items
        $items = $this->parseItems();
        if (!empty($items)) {
            $this->itemModel->createBulk($invoiceId, $items);
        }

        // Recalculate totals
        $this->invoiceModel->updateTotals($invoiceId);

        $typeName = ucfirst($data['document_type']);
        $_SESSION['success'] = "{$typeName} created successfully.";
        $this->redirect('/invoices/' . $invoiceId);
    }

    // ── SHOW ──────────────────────────────────────────────
    public function show(int $id): void {
        $this->requireAuth();
        $invoice = $this->invoiceModel->findWithDetails($id);

        if (!$invoice || $invoice['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('/invoices');
            return;
        }

        $this->render('invoices/show', [
            'invoice' => $invoice,
            'csrf_token' => $this->csrf()
        ]);
    }

    // ── EDIT FORM ─────────────────────────────────────────
    public function edit(int $id): void {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];

        $invoice = $this->invoiceModel->findWithDetails($id);
        if (!$invoice || $invoice['user_id'] !== $userId) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('/invoices');
            return;
        }

        $companies = $this->companyModel->findByUser($userId);
        $clients = $this->clientModel->findByUser($userId);
        $programmes = $this->programmeModel->findByUser($userId);

        $this->render('invoices/edit', [
            'invoice' => $invoice,
            'companies' => $companies,
            'clients' => $clients,
            'programmes' => $programmes,
            'csrf_token' => $this->csrf()
        ]);
    }

    // ── UPDATE ────────────────────────────────────────────
    public function update(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $invoice = $this->invoiceModel->find($id);
        if (!$invoice || $invoice['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('/invoices');
            return;
        }

        $data = [
            'document_number' => trim($_POST['document_number'] ?? ''),
            'company_profile_id' => (int)($_POST['company_profile_id'] ?? 0),
            'client_profile_id' => (int)($_POST['client_profile_id'] ?? 0),
            'programme_id' => !empty($_POST['programme_id']) ? (int)$_POST['programme_id'] : null,
            'project_title' => trim($_POST['project_title'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'requested_by' => trim($_POST['requested_by'] ?? ''),
            'client_email' => trim($_POST['client_email'] ?? ''),
            'currency_code' => trim($_POST['currency_code'] ?? 'LKR'),
            'tax_label' => trim($_POST['tax_label'] ?? 'VAT'),
            'tax_rate' => (float)($_POST['tax_rate'] ?? 0),
            'status' => $_POST['status'] ?? 'draft',
            'is_paid' => isset($_POST['is_paid']),
            'notes' => trim($_POST['notes'] ?? ''),
            'background_template' => $_POST['background_template'] ?? 'none',
            'session_number' => trim($_POST['session_number'] ?? ''),
            'issue_date' => $_POST['issue_date'] ?? date('Y-m-d'),
            'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
        ];

        if ($data['is_paid']) {
            $data['status'] = 'paid';
            $data['paid_date'] = date('Y-m-d');
        } else {
            $data['paid_date'] = null;
        }

        $this->invoiceModel->update($id, $data);

        // Replace items
        $this->itemModel->deleteByInvoice($id);
        $items = $this->parseItems();
        if (!empty($items)) {
            $this->itemModel->createBulk($id, $items);
        }

        $this->invoiceModel->updateTotals($id);

        $_SESSION['success'] = 'Document updated successfully.';
        $this->redirect('/invoices/' . $id);
    }

    // ── DELETE ─────────────────────────────────────────────
    public function delete(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $invoice = $this->invoiceModel->find($id);
        if (!$invoice || $invoice['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('/invoices');
            return;
        }

        $this->invoiceModel->delete($id);
        $_SESSION['success'] = 'Document deleted.';
        $this->redirect('/invoices');
    }

    // ── TOGGLE PAID STATUS ─────────────────────────────────
    public function togglePaid(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $invoice = $this->invoiceModel->find($id);
        if (!$invoice || $invoice['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('/invoices');
            return;
        }

        if ($invoice['is_paid']) {
            $this->invoiceModel->markUnpaid($id);
            $_SESSION['success'] = 'Marked as unpaid.';
        } else {
            $this->invoiceModel->markPaid($id);
            $_SESSION['success'] = 'Marked as PAID.';
        }

        $this->redirect('/invoices/' . $id);
    }

    // ── PDF VIEW (print-friendly) ─────────────────────────
    public function pdf(int $id): void {
        $this->requireAuth();
        $invoice = $this->invoiceModel->findWithDetails($id);

        if (!$invoice || $invoice['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('/invoices');
            return;
        }

        // Render standalone PDF view (no layout)
        extract(['invoice' => $invoice]);
        require BASE_PATH . '/src/Views/invoices/pdf.php';
        exit;
    }

    // ── DOWNLOAD SIGNED PDF ────────────────────────────────
    public function downloadPdf(int $id): void {
        $this->requireAuth();
        $invoice = $this->invoiceModel->findWithDetails($id);

        if (!$invoice || $invoice['user_id'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('/invoices');
            return;
        }

        $companyName = $invoice['company_name'];
        $companyId = (int)$invoice['company_profile_id'];
        $docNumber = $invoice['document_number'];

        // Build the internal URL for the PDF HTML view
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = "{$scheme}://{$host}";
        $pdfUrl = "{$baseUrl}/invoices/{$id}/pdf";

        // Pass session cookie so Python can authenticate
        $sessionName = session_name();
        $sessionId = session_id();
        $cookie = "{$sessionName}={$sessionId}";

        // Output file path
        $storageDir = BASE_PATH . '/storage/pdfs';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        $safeDocNum = preg_replace('/[^A-Za-z0-9\-_]/', '_', $docNumber);
        $outputFile = "{$storageDir}/{$safeDocNum}.pdf";

        // Build the Python command
        $scriptPath = BASE_PATH . '/scripts/generate_pdf.py';
        $cmd = sprintf(
            'python3 %s --url %s --cookie %s --company %s --company-id %d --base-url %s --output %s 2>&1',
            escapeshellarg($scriptPath),
            escapeshellarg($pdfUrl),
            escapeshellarg($cookie),
            escapeshellarg($companyName),
            $companyId,
            escapeshellarg($baseUrl),
            escapeshellarg($outputFile)
        );

        // Close the session before shelling out so the Python-fetched request
        // can read the same session file (PHP uses exclusive file locks on sessions).
        session_write_close();

        // Execute
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        // Re-open the session for error handling / redirects
        session_start();

        if ($exitCode !== 0 || !file_exists($outputFile)) {
            $_SESSION['error'] = 'Failed to generate PDF. ' . implode("\n", $output);
            $this->redirect('/invoices/' . $id);
            return;
        }

        // Stream the file to the browser
        $typeLabel = $invoice['document_type'] === 'quotation' ? 'Quotation' : 'Invoice';
        $filename = "{$typeLabel}_{$safeDocNum}.pdf";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($outputFile));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        readfile($outputFile);

        // Clean up the temp file
        @unlink($outputFile);
        exit;
    }

    // ── GENERATE INVOICE ────────────────────────────────────
    public function generateInvoice(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = $_SESSION['user_id'];
        $quote = $this->invoiceModel->findWithDetails($id);

        if (!$quote || $quote['user_id'] !== $userId) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('/invoices');
            return;
        }
        if ($quote['document_type'] !== 'quotation') {
            $_SESSION['error'] = 'Only quotations can be converted to invoices.';
            $this->redirect('/invoices/' . $id);
            return;
        }

        $newData = [
            'uuid' => $this->invoiceModel->generateUuid(),
            'document_type' => 'invoice',
            'document_number' => $this->invoiceModel->getNextDocumentNumber(
                $userId,
                'invoice',
                $quote['programme_prefix'] ?? 'DOC',
                $quote['programme_id'] ? (int)$quote['programme_id'] : null
            ),
            'company_profile_id' => $quote['company_profile_id'],
            'client_profile_id' => $quote['client_profile_id'],
            'programme_id' => $quote['programme_id'],
            'project_title' => $quote['project_title'],
            'location' => $quote['location'],
            'requested_by' => $quote['requested_by'],
            'client_email' => $quote['client_email'],
            'currency_code' => $quote['currency_code'],
            'subtotal' => $quote['subtotal'],
            'tax_label' => $quote['tax_label'],
            'tax_rate' => $quote['tax_rate'],
            'tax_amount' => $quote['tax_amount'],
            'total' => $quote['total'],
            'status' => 'draft',
            'is_paid' => false,
            'notes' => $quote['notes'],
            'background_template' => $quote['background_template'] ?? 'none',
            'session_number' => $quote['session_number'] ?? '',
            'issue_date' => date('Y-m-d'),
            'user_id' => $userId,
        ];

        $newId = $this->invoiceModel->create($newData);
        if (!empty($quote['items'])) {
            $this->itemModel->createBulk($newId, $quote['items']);
        }

        $_SESSION['success'] = 'Quotation converted to invoice.';
        $this->redirect('/invoices/' . $newId . '/edit');
    }

    // ── DUPLICATE ──────────────────────────────────────────
    public function duplicate(int $id): void {
        $this->requireAuth();
        $this->validateCsrf();

        $userId = $_SESSION['user_id'];
        $invoice = $this->invoiceModel->findWithDetails($id);

        if (!$invoice || $invoice['user_id'] !== $userId) {
            $_SESSION['error'] = 'Document not found.';
            $this->redirect('/invoices');
            return;
        }

        $newData = [
            'uuid' => $this->invoiceModel->generateUuid(),
            'document_type' => $invoice['document_type'],
            'document_number' => $this->invoiceModel->getNextDocumentNumber(
                $userId,
                $invoice['document_type'],
                $invoice['programme_prefix'] ?? 'DOC',
                $invoice['programme_id'] ? (int)$invoice['programme_id'] : null
            ),
            'company_profile_id' => $invoice['company_profile_id'],
            'client_profile_id' => $invoice['client_profile_id'],
            'programme_id' => $invoice['programme_id'],
            'project_title' => $invoice['project_title'],
            'location' => $invoice['location'],
            'requested_by' => $invoice['requested_by'],
            'client_email' => $invoice['client_email'],
            'currency_code' => $invoice['currency_code'],
            'subtotal' => $invoice['subtotal'],
            'tax_label' => $invoice['tax_label'],
            'tax_rate' => $invoice['tax_rate'],
            'tax_amount' => $invoice['tax_amount'],
            'total' => $invoice['total'],
            'status' => 'draft',
            'is_paid' => false,
            'notes' => $invoice['notes'],
            'background_template' => $invoice['background_template'] ?? 'none',
            'session_number' => trim($_POST['session_number'] ?? ''),
            'session_number' => $invoice['session_number'] ?? '',
            'issue_date' => date('Y-m-d'),
            'user_id' => $userId,
        ];

        $newId = $this->invoiceModel->create($newData);

        // Duplicate items
        if (!empty($invoice['items'])) {
            $this->itemModel->createBulk($newId, $invoice['items']);
        }

        $_SESSION['success'] = 'Document duplicated.';
        $this->redirect('/invoices/' . $newId . '/edit');
    }

    // ── AJAX: Get next document number ───────────────────
    public function nextNumber(): void {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $programmeId = (int)($_GET['programme_id'] ?? 0);
        $docType = $_GET['type'] ?? 'invoice';

        if (!$programmeId) {
            $this->json(['error' => 'Programme required'], 400);
            return;
        }

        $programme = $this->programmeModel->find($programmeId);
        if (!$programme || $programme['user_id'] !== $userId) {
            $this->json(['error' => 'Programme not found'], 404);
            return;
        }

        $nextNumber = $this->invoiceModel->getNextDocumentNumber(
            $userId, $docType, $programme['prefix'], $programmeId
        );

        $this->json(['number' => $nextNumber, 'prefix' => $programme['prefix']]);
    }

    // ── HELPERS ────────────────────────────────────────────
    private function parseItems(): array {
        $items = [];
        $descriptions = $_POST['item_description'] ?? [];
        $quantities = $_POST['item_quantity'] ?? [];
        $unitPrices = $_POST['item_unit_price'] ?? [];

        for ($i = 0; $i < count($descriptions); $i++) {
            $desc = trim($descriptions[$i] ?? '');
            if (empty($desc)) continue;

            $items[] = [
                'description' => $desc,
                'quantity' => (float)($quantities[$i] ?? 1),
                'unit_price' => (float)($unitPrices[$i] ?? 0),
            ];
        }

        return $items;
    }
}
