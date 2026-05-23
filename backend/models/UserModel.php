<?php

class UserModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function allWithRelations(): array
    {
        $statement = $this->pdo->query(
            'SELECT u.*, d.name AS department_name, c.name AS company_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN companies c ON c.id = d.company_id
             ORDER BY u.created_at DESC, u.id DESC'
        );

        return $statement->fetchAll();
    }

    public function allForSelect(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, first_name, last_name, email, role FROM users ORDER BY first_name, last_name'
        );

        return $statement->fetchAll();
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (department_id, first_name, last_name, email, phone, password, role, status)
             VALUES (:department_id, :first_name, :last_name, :email, :phone, :password, :role, :status)'
        );
        $statement->execute([
            'department_id' => $data['department_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $hasPassword = array_key_exists('password', $data) && $data['password'] !== '';
        $sql = 'UPDATE users
                SET department_id = :department_id,
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    role = :role,
                    status = :status';

        if ($hasPassword) {
            $sql .= ', password = :password';
        }

        $sql .= ' WHERE id = :id';

        $statement = $this->pdo->prepare($sql);
        $payload = [
            'department_id' => $data['department_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => $data['role'],
            'status' => $data['status'],
            'id' => $id,
        ];

        if ($hasPassword) {
            $payload['password'] = $data['password'];
        }

        $statement->execute($payload);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
