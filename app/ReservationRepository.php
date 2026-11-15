<?php

declare(strict_types=1);

/** Zgłaszany, gdy sala jest już zajęta w wybranym przedziale. */
final class ReservationConflictException extends RuntimeException
{
}

final class ReservationRepository
{
    private const SELECT_BASE = 'SELECT r.*,
            s.name AS room_name, s.location AS room_location, s.image AS room_image,
            s.price_per_hour, s.capacity AS room_capacity,
            u.name AS user_name, u.email AS user_email, u.phone AS user_phone
        FROM reservations r
        JOIN rooms s ON s.id = r.room_id
        JOIN users u ON u.id = r.user_id';

    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::SELECT_BASE . ' WHERE r.id = ? LIMIT 1');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Rezerwacje użytkownika.
     */
    public function forUser(int $userId, string $status = '', string $scope = 'upcoming'): array
    {
        $where = ['r.user_id = :user'];
        $params = ['user' => $userId];

        if (in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            $where[] = 'r.status = :status';
            $params['status'] = $status;
        }

        $upcoming = '(r.reservation_date > CURDATE() OR (r.reservation_date = CURDATE() AND r.end_time >= CURTIME()))';

        $order = 'r.reservation_date DESC, r.start_time DESC';
        if ($scope === 'upcoming') {
            $where[] = $upcoming;
            $order = 'r.reservation_date ASC, r.start_time ASC';
        } elseif ($scope === 'past') {
            $where[] = 'NOT ' . $upcoming;
        }

        $sql = self::SELECT_BASE . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $order;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Rezerwacje wszystkich użytkowników (panel administratora).
     *
     * @param array{q?: string, status?: string, room_id?: int|string} $filters
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int, perPage: int}
     */
    public function adminPaginate(array $filters, int $page = 1, int $perPage = 12): array
    {
        $where = [];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(r.title LIKE :q1 OR u.name LIKE :q2 OR u.email LIKE :q3 OR s.name LIKE :q4)';
            $like = '%' . $q . '%';
            foreach (['q1', 'q2', 'q3', 'q4'] as $key) {
                $params[$key] = $like;
            }
        }

        $status = (string) ($filters['status'] ?? '');
        if (in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            $where[] = 'r.status = :status';
            $params['status'] = $status;
        }

        $roomId = (int) ($filters['room_id'] ?? 0);
        if ($roomId > 0) {
            $where[] = 'r.room_id = :room_id';
            $params['room_id'] = $roomId;
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $countSql = 'SELECT COUNT(*) FROM reservations r
                     JOIN rooms s ON s.id = r.room_id
                     JOIN users u ON u.id = r.user_id' . $whereSql;
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $sql = self::SELECT_BASE . $whereSql
            . ' ORDER BY r.reservation_date DESC, r.start_time DESC LIMIT :limit OFFSET :offset';
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
     * Tworzy rezerwację w transakcji i blokadzie wiersza sali,
     * dzięki czemu dwie osoby nie zajmą tego samego terminu.
     *
     * @param array<string, mixed> $data
     * @throws ReservationConflictException
     */
    public function create(array $data): int
    {
        $this->pdo->beginTransaction();

        try {
            $this->lockRoom((int) $data['room_id']);

            if ($this->conflict(
                (int) $data['room_id'],
                (string) $data['reservation_date'],
                (string) $data['start_time'],
                (string) $data['end_time']
            ) !== null) {
                throw new ReservationConflictException('Wybrany termin jest już zajęty.');
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO reservations
                    (user_id, room_id, title, reservation_date, start_time, end_time, guests, status, notes, total_price)
                 VALUES
                    (:user_id, :room_id, :title, :date, :start, :end, :guests, :status, :notes, :total_price)'
            );
            $stmt->execute([
                'user_id'     => (int) $data['user_id'],
                'room_id'     => (int) $data['room_id'],
                'title'       => trim((string) $data['title']),
                'date'        => $data['reservation_date'],
                'start'       => $data['start_time'],
                'end'         => $data['end_time'],
                'guests'      => (int) $data['guests'],
                'status'      => $data['status'] ?? 'pending',
                'notes'       => trim((string) ($data['notes'] ?? '')) ?: null,
                'total_price' => (float) $data['total_price'],
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();

            return $id;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws ReservationConflictException
     */
    public function update(int $id, array $data): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->lockRoom((int) $data['room_id']);

            if ($this->conflict(
                (int) $data['room_id'],
                (string) $data['reservation_date'],
                (string) $data['start_time'],
                (string) $data['end_time'],
                $id
            ) !== null) {
                throw new ReservationConflictException('Wybrany termin jest już zajęty.');
            }

            $stmt = $this->pdo->prepare(
                'UPDATE reservations
                 SET room_id = :room_id, title = :title, reservation_date = :date,
                     start_time = :start, end_time = :end, guests = :guests,
                     notes = :notes, total_price = :total_price
                 WHERE id = :id'
            );
            $stmt->execute([
                'id'          => $id,
                'room_id'     => (int) $data['room_id'],
                'title'       => trim((string) $data['title']),
                'date'        => $data['reservation_date'],
                'start'       => $data['start_time'],
                'end'         => $data['end_time'],
                'guests'      => (int) $data['guests'],
                'notes'       => trim((string) ($data['notes'] ?? '')) ?: null,
                'total_price' => (float) $data['total_price'],
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM reservations WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            throw new InvalidArgumentException('Nieprawidłowy status rezerwacji.');
        }

        $stmt = $this->pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    /** Znajduje kolidującą rezerwację (bez anulowanych). */
    public function conflict(
        int $roomId,
        string $date,
        string $start,
        string $end,
        ?int $excludeId = null
    ): ?array {
        $sql = "SELECT r.id, r.title, r.start_time, r.end_time
                FROM reservations r
                WHERE r.room_id = :room_id
                  AND r.reservation_date = :date
                  AND r.status <> 'cancelled'
                  AND r.start_time < :end
                  AND r.end_time > :start";

        $params = [
            'room_id' => $roomId,
            'date'    => $date,
            'start'   => $start,
            'end'     => $end,
        ];

        if ($excludeId !== null) {
            $sql .= ' AND r.id <> :exclude';
            $params['exclude'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() ?: null;
    }

    /** @return array<string, int|float> */
    public function stats(): array
    {
        $row = $this->pdo->query(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(status = 'pending'), 0) AS pending,
                COALESCE(SUM(status = 'confirmed'), 0) AS confirmed,
                COALESCE(SUM(status = 'cancelled'), 0) AS cancelled,
                COALESCE(SUM(CASE WHEN reservation_date = CURDATE() AND status <> 'cancelled' THEN 1 ELSE 0 END), 0) AS today,
                COALESCE(SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END), 0) AS revenue
             FROM reservations"
        )->fetch();

        return [
            'total'     => (int) ($row['total'] ?? 0),
            'pending'   => (int) ($row['pending'] ?? 0),
            'confirmed' => (int) ($row['confirmed'] ?? 0),
            'cancelled' => (int) ($row['cancelled'] ?? 0),
            'today'     => (int) ($row['today'] ?? 0),
            'revenue'   => (float) ($row['revenue'] ?? 0),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(self::SELECT_BASE . ' ORDER BY r.created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function today(): array
    {
        $stmt = $this->pdo->query(
            self::SELECT_BASE . " WHERE r.reservation_date = CURDATE() AND r.status <> 'cancelled'
              ORDER BY r.start_time ASC"
        );

        return $stmt->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function topRooms(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.name, COUNT(r.id) AS reservations_count,
                    COALESCE(SUM(CASE WHEN r.status <> \'cancelled\' THEN r.total_price ELSE 0 END), 0) AS revenue
             FROM rooms s
             LEFT JOIN reservations r ON r.room_id = s.id
             GROUP BY s.id, s.name
             ORDER BY reservations_count DESC, revenue DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countUpcomingForUser(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM reservations
             WHERE user_id = ?
               AND status <> 'cancelled'
               AND (reservation_date > CURDATE() OR (reservation_date = CURDATE() AND end_time >= CURTIME()))"
        );
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array{total: int, pending: int, confirmed: int, cancelled: int} */
    public function countsForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'pending'), 0) AS pending,
                    COALESCE(SUM(status = 'confirmed'), 0) AS confirmed,
                    COALESCE(SUM(status = 'cancelled'), 0) AS cancelled
             FROM reservations WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch() ?: [];

        return [
            'total'     => (int) ($row['total'] ?? 0),
            'pending'   => (int) ($row['pending'] ?? 0),
            'confirmed' => (int) ($row['confirmed'] ?? 0),
            'cancelled' => (int) ($row['cancelled'] ?? 0),
        ];
    }

    private function lockRoom(int $roomId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM rooms WHERE id = ? FOR UPDATE');
        $stmt->execute([$roomId]);

        if ($stmt->fetch() === false) {
            throw new RuntimeException('Wybrana sala nie istnieje.');
        }
    }
}
