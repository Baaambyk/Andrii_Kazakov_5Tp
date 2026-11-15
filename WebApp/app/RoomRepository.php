<?php

declare(strict_types=1);

final class RoomRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rooms WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function all(bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM rooms'
            . ($onlyActive ? ' WHERE is_active = 1' : '')
            . ' ORDER BY name ASC';

        return $this->pdo->query($sql)->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function featured(int $limit = 3): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM rooms WHERE is_active = 1 ORDER BY price_per_hour ASC, capacity ASC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Lista sal z filtrami i paginacją.
     *
     * @param array{q?: string, capacity?: int|string, max_price?: float|string, sort?: string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int, perPage: int}
     */
    public function paginate(array $filters, int $page = 1, int $perPage = 9, bool $onlyActive = true): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $onlyActive, $params);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM rooms {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sortOptions = [
            'newest'        => 'created_at DESC, id DESC',
            'price_asc'     => 'price_per_hour ASC, name ASC',
            'price_desc'    => 'price_per_hour DESC, name ASC',
            'capacity_desc' => 'capacity DESC, name ASC',
            'name'          => 'name ASC',
        ];
        $sort = $sortOptions[$filters['sort'] ?? 'newest'] ?? $sortOptions['newest'];

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT * FROM rooms {$where} ORDER BY {$sort} LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items'   => $stmt->fetchAll(),
            'total'   => $total,
            'pages'   => $pages,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $params
     */
    private function buildWhere(array $filters, bool $onlyActive, array &$params): string
    {
        $where = [];

        if ($onlyActive) {
            $where[] = 'is_active = 1';
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(name LIKE :q1 OR location LIKE :q2 OR description LIKE :q3 OR equipment LIKE :q4)';
            $like = '%' . $q . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        $capacity = (int) ($filters['capacity'] ?? 0);
        if ($capacity > 0) {
            $where[] = 'capacity >= :capacity';
            $params['capacity'] = $capacity;
        }

        $maxPrice = (float) ($filters['max_price'] ?? 0);
        if ($maxPrice > 0) {
            $where[] = 'price_per_hour <= :max_price';
            $params['max_price'] = $maxPrice;
        }

        return $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO rooms (name, description, capacity, price_per_hour, location, equipment, image, is_active)
             VALUES (:name, :description, :capacity, :price, :location, :equipment, :image, :is_active)'
        );
        $stmt->execute([
            'name'        => trim((string) $data['name']),
            'description' => trim((string) $data['description']),
            'capacity'    => (int) $data['capacity'],
            'price'       => (float) $data['price_per_hour'],
            'location'    => trim((string) $data['location']),
            'equipment'   => trim((string) ($data['equipment'] ?? '')),
            'image'       => $data['image'] ?? null,
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE rooms
             SET name = :name, description = :description, capacity = :capacity,
                 price_per_hour = :price, location = :location, equipment = :equipment,
                 image = :image, is_active = :is_active
             WHERE id = :id'
        );
        $stmt->execute([
            'id'          => $id,
            'name'        => trim((string) $data['name']),
            'description' => trim((string) $data['description']),
            'capacity'    => (int) $data['capacity'],
            'price'       => (float) $data['price_per_hour'],
            'location'    => trim((string) $data['location']),
            'equipment'   => trim((string) ($data['equipment'] ?? '')),
            'image'       => $data['image'] ?? null,
            'is_active'   => !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM rooms WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function countActive(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM rooms WHERE is_active = 1')->fetchColumn();
    }

    public function totalCapacity(): int
    {
        return (int) $this->pdo->query('SELECT COALESCE(SUM(capacity), 0) FROM rooms WHERE is_active = 1')->fetchColumn();
    }

    /**
     * Zajęte przedziały czasowe dla sali i dnia.
     *
     * @return list<array{start: string, end: string, status: string}>
     */
    public function busySlots(int $roomId, string $date, ?int $excludeReservationId = null): array
    {
        $sql = "SELECT start_time, end_time, status
                FROM reservations
                WHERE room_id = ? AND reservation_date = ? AND status <> 'cancelled'";
        $params = [$roomId, $date];

        if ($excludeReservationId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeReservationId;
        }

        $sql .= ' ORDER BY start_time ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(static fn (array $row): array => [
            'start'  => short_time((string) $row['start_time']),
            'end'    => short_time((string) $row['end_time']),
            'status' => (string) $row['status'],
        ], $stmt->fetchAll());
    }
}
