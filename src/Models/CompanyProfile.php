<?php
declare(strict_types=1);

namespace Models;

class CompanyProfile extends Model {
    protected string $table = 'company_profiles';
    protected array $fillable = [
        'name', 'tagline', 'address', 'phone', 'email', 'website',
        'logo_path', 'tax_id', 'registration_no',
        'bank_name', 'bank_account', 'bank_branch',
        'signature_path', 'is_default', 'user_id'
    ];

    public function findByUser(int $userId): array {
        $stmt = $this->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY is_default DESC, name ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getDefault(int $userId): ?array {
        $stmt = $this->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND is_default = true LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function clearDefault(int $userId): void {
        $stmt = $this->prepare("UPDATE {$this->table} SET is_default = false WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
}
