<?php

class UserModel
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * findByEmail
     * Retourne l'utilisateur correspondant à l'email (utilisé par AuthController).
     * Rôle: login / authentication.
     */

    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * findById
     * Retourne un utilisateur par id (utilisé par plusieurs APIs).
     */

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * allWithRelations
     * Retourne tous les utilisateurs avec leurs relations department/company (utilisé pour l'admin).
     */

    public function allWithRelations(): array
    {
        $statement = $this->pdo->query(
            'SELECT u.*, d.name AS department_name, c.name AS company_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN companies c ON c.id = COALESCE(u.company_id, d.company_id)
             ORDER BY u.created_at DESC, u.id DESC'
        );

        return $statement->fetchAll();
    }

    /**
     * allForSelect
     * Liste minimale d'utilisateurs pour select inputs.
     */

    public function allForSelect(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, first_name, last_name, email, role FROM users ORDER BY first_name, last_name'
        );

        return $statement->fetchAll();
    }

    /**
     * countByCompanyId
     * Nombre d'utilisateurs per company (utilisé pour stats).
     */

    public function countByCompanyId(int $companyId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE d.company_id = :company_id'
        );
        $statement->execute(['company_id' => $companyId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * profileWithRelations
     * Retourne le profil de l'utilisateur avec department et company (utilisé pour dashboard).
     */

    public function profileWithRelations(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.department_id,
                COALESCE(u.company_id, d.company_id) AS company_id,
                d.name AS department_name, c.name AS company_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN companies c ON c.id = COALESCE(u.company_id, d.company_id)
             WHERE u.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $profile = $statement->fetch();

        return $profile ?: null;
    }

    /**
     * teamByDepartmentId
     * Liste des membres d'un département (utilisé par Department Manager / Super Admin).
     */

    public function teamByDepartmentId(int $departmentId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status
             FROM users u
             WHERE u.department_id = :department_id
             ORDER BY u.last_name, u.first_name'
        );
        $statement->execute(['department_id' => $departmentId]);

        return $statement->fetchAll();
    }

    /**
     * companyUsersByCompanyId
     * Retourne les utilisateurs appartenant à une entreprise (via departments).
     */

    public function companyUsersByCompanyId(int $companyId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.status, u.company_id, d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE COALESCE(u.company_id, d.company_id) = :company_id
             ORDER BY u.last_name, u.first_name'
        );
        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchAll();
    }

    /**
     * employeeShifts
     * Retourne i turni assegnati a un dipendente.
     */

    public function employeeShifts(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT us.id, us.work_date, us.status, s.name AS shift_name, s.start_time, s.end_time, d.name AS department_name
             FROM user_shifts us
             INNER JOIN shifts s ON s.id = us.shift_id
             INNER JOIN departments d ON d.id = s.department_id
             WHERE us.user_id = :user_id
             ORDER BY us.work_date DESC, us.id DESC
             LIMIT 10'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    /**
     * employeeRequests / companyRequestsByCompanyId / userNotifications
     * Fonctions per recuperare richieste e notifiche per utente o company.
     */

    public function employeeRequests(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, type, title, status, created_at
             FROM requests
             WHERE user_id = :user_id
               AND type <> :notification_type
             ORDER BY created_at DESC, id DESC
             LIMIT 10'
        );
        $statement->execute([
            'user_id' => $userId,
            'notification_type' => 'notification',
        ]);

        return $statement->fetchAll();
    }

    public function companyRequestsByCompanyId(int $companyId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.id, r.type, r.title, r.status, r.created_at,
                    u.first_name, u.last_name, d.name AS department_name
             FROM requests r
             INNER JOIN users u ON u.id = r.user_id
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE d.company_id = :company_id
               AND r.type <> :notification_type
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT 20'
        );
        $statement->execute([
            'company_id' => $companyId,
            'notification_type' => 'notification',
        ]);

        return $statement->fetchAll();
    }

    public function userNotifications(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, type, title, message, status, created_at
             FROM requests
             WHERE user_id = :user_id
               AND type = :notification_type
             ORDER BY created_at DESC, id DESC
             LIMIT 20'
        );
        $statement->execute([
            'user_id' => $userId,
            'notification_type' => 'notification',
        ]);

        return $statement->fetchAll();
    }

    public function createNotificationForUser(int $userId, string $title, string $message): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO requests (user_id, type, title, message, status)
             VALUES (:user_id, :type, :title, :message, :status)'
        );
        $statement->execute([
            'user_id' => $userId,
            'type' => 'notification',
            'title' => $title,
            'message' => $message,
            'status' => 'pending',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateNotificationForUser(int $notificationId, int $userId, string $title, string $message): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE requests
             SET title = :title,
                 message = :message
             WHERE id = :id
               AND user_id = :user_id
               AND type = :type'
        );
        $statement->execute([
            'title' => $title,
            'message' => $message,
            'id' => $notificationId,
            'user_id' => $userId,
            'type' => 'notification',
        ]);

        return $statement->rowCount() > 0;
    }

    public function createRequestForUser(int $userId, string $type, string $title, string $message): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO requests (user_id, type, title, message, status)
             VALUES (:user_id, :type, :title, :message, :status)'
        );
        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'status' => 'pending',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (department_id, company_id, first_name, last_name, email, phone, password, role, status)
             VALUES (:department_id, :company_id, :first_name, :last_name, :email, :phone, :password, :role, :status)'
        );
        $statement->execute([
            'department_id' => $data['department_id'],
            'company_id' => $data['company_id'],
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
                    company_id = :company_id,
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
            'company_id' => $data['company_id'],
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
