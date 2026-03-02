<?php
declare(strict_types=1);

namespace Models;

class Programme extends Model {
    protected string $table = 'programmes';
    protected array $fillable = [
        'name', 'prefix', 'description', 'user_id'
    ];

    public function findByUser(int $userId): array {
        $stmt = $this->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY name ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findByPrefix(string $prefix, int $userId): ?array {
        $stmt = $this->prepare("SELECT * FROM {$this->table} WHERE prefix = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$prefix, $userId]);
        return $stmt->fetch() ?: null;
    }
}
