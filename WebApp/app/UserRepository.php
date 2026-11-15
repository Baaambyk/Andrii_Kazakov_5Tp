<?php

declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([mb_strtolower(trim($email))]);

        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, ?string $phone): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, phone, role)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            trim($name),
            mb_strtolower(trim($email)),
            $passwordHash,
            $phone !== null && trim($phone) !== '' ? trim($phone) : null,
            'user',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateProfile(int $id, string $name, ?string $phone): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?');
        $stmt->execute([
            trim($name),
            $phone !== null && trim($phone) !== '' ? trim($phone) : null,
            $id,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $id]);
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $sql = 'SELECT u.*,
                       (SELECT COUNT(*) FROM reservations r WHERE r.user_id = u.id) AS reservations_count,
                       (SELECT COUNT(*) FROM reservations r WHERE r.user_id = u.id AND r.status = \'confirmed\') AS confirmed_count
                FROM users u
                ORDER BY u.created_at DESC';

        return $this->pdo->query($sql)->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function countAdmins(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    public function setRole(int $id, string $role): void
    {
        if (!in_array($role, ['user', 'admin'], true)) {
            throw new InvalidArgumentException('Nieprawidłowa rola.');
        }

        $stmt = $this->pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}
