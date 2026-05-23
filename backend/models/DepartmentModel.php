<?php

class DepartmentModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function allWithCompany(): array
    {
        $statement = $this->pdo->query(
            'SELECT d.*, c.name AS company_name
             FROM departments d
             LEFT JOIN companies c ON c.id = d.company_id
             ORDER BY d.created_at DESC, d.id DESC'
        );

        return $statement->fetchAll();
    }

    public function allForSelect(): array
    {
        $statement = $this->pdo->query('SELECT id, company_id, name FROM departments ORDER BY name ASC');

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM departments WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $department = $statement->fetch();

        return $department ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO departments (company_id, name, description)
             VALUES (:company_id, :name, :description)'
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE departments
             SET company_id = :company_id,
                 name = :name,
                 description = :description
             WHERE id = :id'
        );
        $data['id'] = $id;
        $statement->execute($data);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM departments WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();
    }
}
