<?php

/**
 * Handles department persistence and read queries used by the dashboard and APIs.
 * Provides methods to create, update, delete and list departments, and adapts
 * to optional schema columns (e.g. `head_user_id`).
 */
class DepartmentModel
{
    private array $departmentColumns = [];

    public function __construct(private PDO $pdo)
    {
        $this->departmentColumns = $this->detectDepartmentColumns();
    }

    /**
     * Detects the columns currently available in the departments table.
     * Returns an associative map of column => true for quick checks.
     */

    private function detectDepartmentColumns(): array
    {
        try {
            $statement = $this->pdo->query('SHOW COLUMNS FROM departments');
            $columns = $statement->fetchAll(PDO::FETCH_COLUMN);

            return array_fill_keys($columns, true);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Checks whether a column exists in the current departments schema.
     */

    private function hasDepartmentColumn(string $column): bool
    {
        return isset($this->departmentColumns[$column]);
    }

    /**
     * allWithCompany
     * Return departments joined with their company name and optional head user
     * name when `head_user_id` exists in the schema.
     */

    public function allWithCompany(): array
    {
        $selectParts = ['d.*', 'c.name AS company_name'];
        $joins = ['LEFT JOIN companies c ON c.id = d.company_id'];

        if ($this->hasDepartmentColumn('head_user_id')) {
            $selectParts[] = 'CONCAT(hu.first_name, " ", hu.last_name) AS head_user_name';
            $joins[] = 'LEFT JOIN users hu ON hu.id = d.head_user_id';
        } else {
            $selectParts[] = 'NULL AS head_user_name';
        }

        $statement = $this->pdo->query(
            'SELECT ' . implode(', ', $selectParts) . '
             FROM departments d
             ' . implode("\n             ", $joins) . '
             ORDER BY d.created_at DESC, d.id DESC'
        );

        return $statement->fetchAll();
    }

    /**
     * allForSelect
     * Return an array of id/company_id/name suitable for select inputs.
     */

    public function allForSelect(): array
    {
        $select = ['id', 'company_id', 'name'];
        if ($this->hasDepartmentColumn('icon')) {
            $select[] = 'icon';
        }
        if ($this->hasDepartmentColumn('color')) {
            $select[] = 'color';
        }

        $statement = $this->pdo->query('SELECT ' . implode(', ', $select) . ' FROM departments ORDER BY name ASC');

        return $statement->fetchAll();
    }

    /**
     * findById
     * Return a department row by id or null when missing.
     */

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM departments WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $department = $statement->fetch();

        return $department ?: null;
    }

    /**
     * findByNameAndCompanyId
     * Find the first department matching `name` and optional `company_id`.
     */

    public function findByNameAndCompanyId(string $name, ?int $companyId = null): ?array
    {
        if ($companyId !== null) {
            $statement = $this->pdo->prepare(
                'SELECT * FROM departments WHERE name = :name AND company_id = :company_id ORDER BY id ASC LIMIT 1'
            );
            $statement->execute([
                'name' => $name,
                'company_id' => $companyId,
            ]);
        } else {
            $statement = $this->pdo->prepare('SELECT * FROM departments WHERE name = :name ORDER BY id ASC LIMIT 1');
            $statement->execute(['name' => $name]);
        }

        $department = $statement->fetch();

        return $department ?: null;
    }

    /**
     * create
     * Insert a department and return the new id. Includes `head_user_id` if
     * the column is available in the schema.
     */

    public function create(array $data): int
    {
        $columns = ['company_id', 'name', 'description'];

        if ($this->hasDepartmentColumn('icon')) {
            $columns[] = 'icon';
        }

        if ($this->hasDepartmentColumn('color')) {
            $columns[] = 'color';
        }

        if ($this->hasDepartmentColumn('head_user_id')) {
            $columns[] = 'head_user_id';
        }

        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $statement = $this->pdo->prepare(
            'INSERT INTO departments (' . implode(', ', $columns) . ')
             VALUES (' . implode(', ', $placeholders) . ')'
        );

        $payload = array_intersect_key($data, array_fill_keys($columns, true));
        foreach ($columns as $column) {
            $payload[$column] = $payload[$column] ?? null;
        }

        $statement->execute($payload);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * update
     * Update department fields; includes `head_user_id` when supported.
     */

    public function update(int $id, array $data): void
    {
        $fields = ['company_id', 'name', 'description'];

        if ($this->hasDepartmentColumn('icon')) {
            $fields[] = 'icon';
        }

        if ($this->hasDepartmentColumn('color')) {
            $fields[] = 'color';
        }

        if ($this->hasDepartmentColumn('head_user_id')) {
            $fields[] = 'head_user_id';
        }

        $assignments = array_map(static fn (string $column): string => $column . ' = :' . $column, $fields);
        $statement = $this->pdo->prepare(
            'UPDATE departments
             SET ' . implode(",\n                 ", $assignments) . '
             WHERE id = :id'
        );
        $payload = array_intersect_key($data, array_fill_keys($fields, true));
        foreach ($fields as $field) {
            $payload[$field] = $payload[$field] ?? null;
        }
        $payload['id'] = $id;
        $statement->execute($payload);
    }

    /**
     * delete
     * Delete a department by id.
     */

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM departments WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * count
     * Return total departments count.
     */

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();
    }

    /**
     * countByCompanyId
     * Return the number of departments for a given company id.
     */

    public function countByCompanyId(int $companyId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM departments WHERE company_id = :company_id');
        $statement->execute(['company_id' => $companyId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * byCompanyId
     * Return departments for a specific company id, including optional head
     * user name when the schema supports it.
     */

    public function byCompanyId(int $companyId): array
    {
        $selectParts = ['d.id', 'd.company_id', 'd.name', 'd.description'];
        $joins = [];

        if ($this->hasDepartmentColumn('head_user_id')) {
            $selectParts[] = 'd.head_user_id';
            $selectParts[] = 'CONCAT(u.first_name, " ", u.last_name) AS head_user_name';
            $joins[] = 'LEFT JOIN users u ON u.id = d.head_user_id';
        } else {
            $selectParts[] = 'NULL AS head_user_id';
            $selectParts[] = 'NULL AS head_user_name';
        }

        $statement = $this->pdo->prepare(
            'SELECT ' . implode(', ', $selectParts) . '
             FROM departments d
             ' . implode("\n             ", $joins) . '
             WHERE d.company_id = :company_id
             ORDER BY d.name ASC, d.id DESC'
        );
        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchAll();
    }
}
