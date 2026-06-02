<?php

class ShiftModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM shifts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function byDepartmentId(int $departmentId): array
    {
        $stmt = $this->pdo->prepare('SELECT s.*, d.name AS department_name, d.company_id FROM shifts s LEFT JOIN departments d ON d.id = s.department_id WHERE s.department_id = :department_id ORDER BY s.start_time ASC, s.id ASC');
        $stmt->execute(['department_id' => $departmentId]);

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO shifts (department_id, name, icon, color, description, kind, start_time, end_time)
             VALUES (:department_id, :name, :icon, :color, :description, :kind, :start_time, :end_time)'
        );
        $statement->execute([
            'department_id' => $data['department_id'],
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
            'kind' => $data['kind'] ?? 'work',
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $assignments = [
            'department_id = :department_id',
            'name = :name',
            'icon = :icon',
            'color = :color',
            'description = :description',
            'kind = :kind',
            'start_time = :start_time',
            'end_time = :end_time',
        ];

        $stmt = $this->pdo->prepare('UPDATE shifts SET ' . implode(', ', $assignments) . ' WHERE id = :id');
        $payload = [
            'department_id' => $data['department_id'] ?? null,
            'name' => $data['name'] ?? null,
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? null,
            'description' => $data['description'] ?? null,
            'kind' => $data['kind'] ?? 'work',
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'id' => $id,
        ];

        $stmt->execute($payload);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM shifts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
