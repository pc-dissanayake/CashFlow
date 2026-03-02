<?php
declare(strict_types=1);

namespace Models;

class ClientProfile extends Model {
    protected string $table = 'client_profiles';
    protected array $fillable = [
        'company_name', 'contact_person', 'address',
        'phone', 'email', 'tax_id', 'user_id'
    ];

    public function findByUser(int $userId): array {
        $stmt = $this->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY company_name ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findByName(string $name, int $userId): ?array {
        $stmt = $this->prepare("SELECT * FROM {$this->table} WHERE company_name = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$name, $userId]);
        return $stmt->fetch() ?: null;
    }
}
