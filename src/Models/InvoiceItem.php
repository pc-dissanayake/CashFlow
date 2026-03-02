<?php
declare(strict_types=1);

namespace Models;

class InvoiceItem extends Model {
    protected string $table = 'invoice_items';
    protected array $fillable = [
        'invoice_id', 'item_number', 'description',
        'quantity', 'unit_price', 'amount'
    ];

    public function findByInvoice(int $invoiceId): array {
        $stmt = $this->prepare("SELECT * FROM {$this->table} WHERE invoice_id = ? ORDER BY item_number ASC");
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }

    public function deleteByInvoice(int $invoiceId): bool {
        $stmt = $this->prepare("DELETE FROM {$this->table} WHERE invoice_id = ?");
        return $stmt->execute([$invoiceId]);
    }

    public function createBulk(int $invoiceId, array $items): void {
        foreach ($items as $index => $item) {
            $this->create([
                'invoice_id' => $invoiceId,
                'item_number' => $index + 1,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => (float)$item['quantity'] * (float)$item['unit_price']
            ]);
        }
    }
}
