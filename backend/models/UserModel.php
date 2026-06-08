<?php

/**
 * Handles user persistence and role-aware queries for the dashboard and APIs.
 * Provides methods to find, list, create, update and delete users, plus helper
 * queries for profile, team membership, requests and notifications.
 */
class UserModel
{
    private array $userColumns = [];

    public function __construct(private PDO $pdo)
    {
        $this->userColumns = $this->detectUserColumns();
    }

    private function detectUserColumns(): array
    {
        try {
            $statement = $this->pdo->query('SHOW COLUMNS FROM users');
            $columns = $statement->fetchAll(PDO::FETCH_COLUMN);

            return array_fill_keys($columns, true);
        } catch (Throwable $e) {
            return [];
        }
    }

    private function hasUserColumn(string $column): bool
    {
        return isset($this->userColumns[$column]);
    }

    private function attachDepartmentAssignments(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $userIds = [];
        foreach ($rows as $row) {
            $uid = (int) ($row['id'] ?? 0);
            if ($uid > 0) {
                $userIds[] = $uid;
            }
        }
        $userIds = array_values(array_unique($userIds));
        if (empty($userIds)) {
            return $rows;
        }

        $placeholders = implode(', ', array_fill(0, count($userIds), '?'));
        $links = [];
        try {
            $statement = $this->pdo->prepare(
                'SELECT l.user_id, l.department_id, d.name AS department_name
                 FROM user_department_links l
                 INNER JOIN departments d ON d.id = l.department_id
                 WHERE l.user_id IN (' . $placeholders . ')'
            );
            $statement->execute($userIds);
            $links = $statement->fetchAll();
        } catch (Throwable $e) {
            $links = [];
        }

        $byUser = [];
        foreach ($links as $link) {
            $uid = (int) ($link['user_id'] ?? 0);
            $did = (int) ($link['department_id'] ?? 0);
            $name = trim((string) ($link['department_name'] ?? ''));
            if ($uid <= 0 || $did <= 0) {
                continue;
            }
            if (!isset($byUser[$uid])) {
                $byUser[$uid] = [
                    'ids' => [],
                    'names' => [],
                ];
            }
            $byUser[$uid]['ids'][$did] = $did;
            if ($name !== '') {
                $byUser[$uid]['names'][$name] = $name;
            }
        }

        foreach ($rows as &$row) {
            $uid = (int) ($row['id'] ?? 0);
            $primaryDepartmentId = (int) ($row['department_id'] ?? 0);
            $primaryDepartmentName = trim((string) ($row['department_name'] ?? ''));

            $ids = isset($byUser[$uid]['ids']) ? $byUser[$uid]['ids'] : [];
            $names = isset($byUser[$uid]['names']) ? $byUser[$uid]['names'] : [];

            if ($primaryDepartmentId > 0) {
                $ids[$primaryDepartmentId] = $primaryDepartmentId;
            }
            if ($primaryDepartmentName !== '') {
                $names[$primaryDepartmentName] = $primaryDepartmentName;
            }

            $row['department_ids'] = array_values(array_map('intval', array_keys($ids)));
            $row['department_names'] = array_values(array_map('strval', array_keys($names)));
        }
        unset($row);

        return $rows;
    }

    public function setDepartmentLinks(int $userId, array $departmentIds): void
    {
        if ($userId <= 0) {
            return;
        }

        $normalized = [];
        foreach ($departmentIds as $departmentId) {
            $id = (int) $departmentId;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }
        $normalized = array_values($normalized);

        $delete = $this->pdo->prepare('DELETE FROM user_department_links WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);

        if (empty($normalized)) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO user_department_links (user_id, department_id)
             VALUES (:user_id, :department_id)'
        );
        foreach ($normalized as $departmentId) {
            $insert->execute([
                'user_id' => $userId,
                'department_id' => $departmentId,
            ]);
        }
    }

    /**
     * findByEmail
     * Return a user row matching the provided email or null. Used for login.
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
     * Return a user row by id or null when not found.
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
     * Return all users with joined department and company information.
     */

    public function allWithRelations(): array
    {
        $companySelect = $this->hasUserColumn('company_id')
            ? 'COALESCE(u.company_id, d.company_id) AS company_id'
            : 'd.company_id AS company_id';

        $statement = $this->pdo->query(
            'SELECT u.*, ' . $companySelect . ', d.name AS department_name, c.name AS company_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN companies c ON c.id = ' . ($this->hasUserColumn('company_id') ? 'COALESCE(u.company_id, d.company_id)' : 'd.company_id') . '
             ORDER BY u.created_at DESC, u.id DESC'
        );

        return $this->attachDepartmentAssignments($statement->fetchAll());
    }

    /**
     * allForSelect
     * Minimal user list suitable for select inputs (id, name, email, role).
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
     * Return the number of users belonging to a company (via departments).
     */

    public function countByCompanyId(int $companyId): int
    {
        $where = $this->hasUserColumn('company_id')
            ? '(COALESCE(u.company_id, d.company_id) = :company_id)'
            : '(d.company_id = :company_id)';

        $statement = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE ' . $where
        );
        $statement->execute(['company_id' => $companyId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * profileWithRelations
     * Return a user's profile augmented with department and company names.
     */

    public function profileWithRelations(int $id): ?array
    {
        $companyIdExpr = $this->hasUserColumn('company_id') ? 'COALESCE(u.company_id, d.company_id)' : 'd.company_id';

        $statement = $this->pdo->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.department_id,
                ' . $companyIdExpr . ' AS company_id,
                d.name AS department_name, c.name AS company_name, c.type AS company_type
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             LEFT JOIN companies c ON c.id = ' . $companyIdExpr . '
             WHERE u.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);

        $profile = $statement->fetch();

        return $profile ?: null;
    }

    /**
     * teamByDepartmentId
     * Return members of a department for team views (department manager / admin).
     */

    public function teamByDepartmentId(int $departmentId): array
    {
        $companySelect = $this->hasUserColumn('company_id')
            ? 'COALESCE(u.company_id, d.company_id) AS company_id'
            : 'd.company_id AS company_id';

        $statement = $this->pdo->prepare(
            'SELECT u.id, u.department_id, ' . $companySelect . ', u.first_name, u.last_name, u.email, u.role, u.status
             FROM users u
               LEFT JOIN departments d ON d.id = u.department_id
               WHERE u.department_id = :department_id
                 OR EXISTS (
                    SELECT 1
                    FROM user_department_links udl
                    WHERE udl.user_id = u.id
                      AND udl.department_id = :department_id
                 )
             ORDER BY u.last_name, u.first_name'
        );
        $statement->execute(['department_id' => $departmentId]);

           return $this->attachDepartmentAssignments($statement->fetchAll());
    }

    /**
     * companyUsersByCompanyId
     * Return users that belong to a given company, resolved via departments.
     */

    public function companyUsersByCompanyId(int $companyId): array
    {
        if ($this->hasUserColumn('company_id')) {
            $statement = $this->pdo->prepare(
                'SELECT u.id, u.department_id, u.first_name, u.last_name, u.email, u.role, u.status,
                        COALESCE(u.company_id, d.company_id) AS company_id,
                        d.name AS department_name
                 FROM users u
                 LEFT JOIN departments d ON d.id = u.department_id
                 WHERE COALESCE(u.company_id, d.company_id) = :company_id
                 ORDER BY u.last_name, u.first_name'
            );
            $statement->execute(['company_id' => $companyId]);

            return $this->attachDepartmentAssignments($statement->fetchAll());
        }

        $statement = $this->pdo->prepare(
            'SELECT u.id, u.department_id, u.first_name, u.last_name, u.email, u.role, u.status,
                    d.company_id,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             WHERE d.company_id = :company_id
             ORDER BY u.last_name, u.first_name'
        );
        $statement->execute(['company_id' => $companyId]);
        $rows = $statement->fetchAll();

        // Fallback for legacy schemas: only when the company has no departments at all.
        if (!empty($rows)) {
            return $rows;
        }

        $deptCountStatement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM departments WHERE company_id = :company_id'
        );
        $deptCountStatement->execute(['company_id' => $companyId]);
        $departmentCount = (int) $deptCountStatement->fetchColumn();
        if ($departmentCount > 0) {
            return [];
        }

        $orphanStatement = $this->pdo->prepare(
            'SELECT u.id, u.department_id, u.first_name, u.last_name, u.email, u.role, u.status,
                    :company_id AS company_id,
                    NULL AS department_name
             FROM users u
             WHERE u.department_id IS NULL
               AND u.role <> :super_admin_role
             ORDER BY u.last_name, u.first_name'
        );
        $orphanStatement->execute([
            'company_id' => $companyId,
            'super_admin_role' => 'super_admin',
        ]);

        return $this->attachDepartmentAssignments($orphanStatement->fetchAll());
    }

    /**
     * employeeShifts
     * Return the shifts assigned to an employee (limited result set for dashboard).
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
     * Functions to retrieve requests and notifications for a user or company.
     */

    public function employeeRequests(int $userId): array
    {
        $statement = $this->pdo->prepare(
                        'SELECT id, recipient_id, document_id, type, title, message, status, created_at
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
            'SELECT r.id, r.recipient_id, r.document_id, r.type, r.title, r.message, r.status, r.created_at,
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
                        'SELECT id, recipient_id, document_id, type, title, message, status, created_at
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
        $columns = ['department_id', 'first_name', 'last_name', 'email', 'phone', 'password', 'role', 'status'];
        if ($this->hasUserColumn('company_id')) {
            $columns[] = 'company_id';
        }
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        $statement = $this->pdo->prepare(
            'INSERT INTO users (' . implode(', ', $columns) . ')
             VALUES (' . implode(', ', $placeholders) . ')'
        );

        $payload = array_intersect_key($data, array_fill_keys($columns, true));
        foreach ($columns as $column) {
            $payload[$column] = $payload[$column] ?? null;
        }

        $statement->execute($payload);

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

        if ($this->hasUserColumn('company_id')) {
            $sql .= ', company_id = :company_id';
        }

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

        if ($this->hasUserColumn('company_id')) {
            $payload['company_id'] = $data['company_id'] ?? null;
        }

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
