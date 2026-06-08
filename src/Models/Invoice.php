<?php
declare(strict_types=1);

namespace Models;

class Invoice extends Model {
    protected string $table = 'invoices';

    /** Hours after generation when a PAID invoice locks editing/deletion. */
    public const EDIT_LOCK_HOURS = 24;
    /** Hours after generation when the PAID stamp itself can no longer be removed. */
    public const PAID_LOCK_HOURS = 36;

    protected array $fillable = [
        'uuid', 'document_type', 'document_number',
        'company_profile_id', 'client_profile_id', 'programme_id',
        'project_title', 'location', 'requested_by', 'client_email',
        'currency_code', 'subtotal', 'tax_label', 'tax_rate', 'tax_amount', 'total',
        'status', 'is_paid', 'paid_date', 'notes', 'background_template', 'session_number',
        'issue_date', 'due_date', 'user_id'
    ];

    /**
     * Fetch invoices/quotations belonging to a user, optionally filtering by
     * document type, programme ("category"), and an issue date range.
     *
     * @param int $userId
     * @param string|null $documentType 'invoice' or 'quotation'
     * @param int|null $programmeId
     * @param string|null $startDate YYYY-MM-DD
     * @param string|null $endDate YYYY-MM-DD
     * @return array
     */
    public function findByUser(int $userId, ?string $documentType = null, ?int $programmeId = null, ?string $startDate = null, ?string $endDate = null): array {
        $sql = "SELECT i.*, cp.name as company_name, cl.company_name as client_name,
                    pr.name as programme_name, pr.prefix as programme_prefix,
                    EXTRACT(EPOCH FROM (NOW() - i.created_at)) / 3600 AS age_hours
                FROM {$this->table} i
                JOIN company_profiles cp ON i.company_profile_id = cp.id
                JOIN client_profiles cl ON i.client_profile_id = cl.id
                LEFT JOIN programmes pr ON i.programme_id = pr.id
                WHERE i.user_id = ?";
        $params = [$userId];

        if ($documentType) {
            $sql .= " AND i.document_type = ?";
            $params[] = $documentType;
        }

        if ($programmeId) {
            $sql .= " AND i.programme_id = ?";
            $params[] = $programmeId;
        }

        if ($startDate && $endDate) {
            $sql .= " AND i.issue_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        } elseif ($startDate) {
            $sql .= " AND i.issue_date >= ?";
            $params[] = $startDate;
        } elseif ($endDate) {
            $sql .= " AND i.issue_date <= ?";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY i.created_at DESC";

        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findWithDetails(int $id): ?array {
        $sql = "SELECT i.*,
                    cp.name as company_name, cp.tagline as company_tagline,
                    cp.address as company_address, cp.phone as company_phone,
                    cp.email as company_email, cp.website as company_website,
                    cp.logo_path, cp.signature_path, cp.tax_id as company_tax_id,
                    cp.registration_no as company_registration_no,
                    cp.bank_name, cp.bank_account, cp.bank_branch,
                    cl.company_name as client_name, cl.contact_person as client_contact,
                    cl.address as client_address, cl.phone as client_phone,
                    cl.email as client_email_profile, cl.tax_id as client_tax_id,
                    pr.name as programme_name, pr.prefix as programme_prefix
                FROM {$this->table} i
                JOIN company_profiles cp ON i.company_profile_id = cp.id
                JOIN client_profiles cl ON i.client_profile_id = cl.id
                LEFT JOIN programmes pr ON i.programme_id = pr.id
                WHERE i.id = ?";

        $stmt = $this->prepare($sql);
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();

        if ($invoice) {
            $itemModel = new InvoiceItem();
            $invoice['items'] = $itemModel->findByInvoice($id);
        }

        return $invoice ?: null;
    }

    public function findByUuid(string $uuid): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE uuid = ?";
        $stmt = $this->prepare($sql);
        $stmt->execute([$uuid]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Generate next document number in format: PREFIX-YY-XXX
     * Sequence is per programme per year.
     */
    public function getNextDocumentNumber(int $userId, string $type, string $prefix, ?int $programmeId = null): string {
        $year2 = date('y'); // e.g. "26"
        $pattern = $prefix . '-' . $year2 . '-%';

        $sql = "SELECT document_number FROM {$this->table}
                WHERE user_id = ? AND document_type = ? AND document_number LIKE ?
                ORDER BY document_number DESC LIMIT 1";
        $stmt = $this->prepare($sql);
        $stmt->execute([$userId, $type, $pattern]);
        $last = $stmt->fetch();

        if ($last && preg_match('/(\d+)$/', $last['document_number'], $matches)) {
            $nextNum = (int)$matches[1] + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . '-' . $year2 . '-' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
    }

    public function generateUuid(): string {
        return sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );
    }

    /**
     * Compute time-based lock flags for an invoice.
     *
     * A PAID invoice locks editing and deletion EDIT_LOCK_HOURS after it was
     * generated, and its PAID stamp becomes permanent PAID_LOCK_HOURS after
     * generation. Unpaid invoices are never locked. Age is measured DB-side
     * (NOW() vs created_at) to avoid PHP/Postgres timezone skew.
     *
     * @return array{edit_locked: bool, paid_locked: bool, age_hours: float}
     */
    public function lockFlags(int $id): array {
        $stmt = $this->prepare(
            "SELECT is_paid, EXTRACT(EPOCH FROM (NOW() - created_at)) / 3600 AS age_hours
             FROM {$this->table} WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $this->lockFlagsFor($stmt->fetch() ?: []);
    }

    /**
     * Derive lock flags from a row that already carries `is_paid` and `age_hours`
     * (e.g. one returned by findByUser). Avoids a per-row query when locking a list.
     *
     * @return array{edit_locked: bool, paid_locked: bool, age_hours: float}
     */
    public function lockFlagsFor(array $row): array {
        $paid  = (bool)($row['is_paid'] ?? false);
        $hours = (float)($row['age_hours'] ?? 0);

        return [
            'edit_locked' => $paid && $hours >= self::EDIT_LOCK_HOURS,
            'paid_locked' => $paid && $hours >= self::PAID_LOCK_HOURS,
            'age_hours'   => $hours,
        ];
    }

    public function markPaid(int $id): bool {
        $stmt = $this->prepare("UPDATE {$this->table} SET is_paid = true, status = 'paid', paid_date = CURRENT_DATE, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function markUnpaid(int $id): bool {
        $stmt = $this->prepare("UPDATE {$this->table} SET is_paid = false, status = 'sent', paid_date = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function updateTotals(int $id): void {
        $itemModel = new InvoiceItem();
        $items = $itemModel->findByInvoice($id);
        $subtotal = array_sum(array_column($items, 'amount'));

        $invoice = $this->find($id);
        $taxRate = (float)($invoice['tax_rate'] ?? 0);
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        $stmt = $this->prepare("UPDATE {$this->table} SET subtotal = ?, tax_amount = ?, total = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$subtotal, $taxAmount, $total, $id]);
    }
}
