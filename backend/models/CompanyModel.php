<?php

class CompanyModel
{
    private array $companyColumns = [];
    private array $departmentColumns = [];

    public function __construct(private PDO $pdo)
    {
        $this->companyColumns = $this->detectCompanyColumns();
        $this->departmentColumns = $this->detectDepartmentColumns();
    }

    /**
     * detectCompanyColumns
     * Rileva le colonne effettivamente disponibili nella tabella companies.
     */

    private function detectCompanyColumns(): array
    {
        try {
            $statement = $this->pdo->query('SHOW COLUMNS FROM companies');
            $columns = $statement->fetchAll(PDO::FETCH_COLUMN);

            return array_fill_keys($columns, true);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * hasCompanyColumn
     * Verifica se una colonna è presente nello schema corrente.
     */

    private function hasCompanyColumn(string $column): bool
    {
        return isset($this->companyColumns[$column]);
    }

    /**
     * detectDepartmentColumns
     * Rileva le colonne effettivamente disponibili nella tabella departments.
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
     * hasDepartmentColumn
     * Vérifie si une colonne est présente dans le schéma courant.
     */

    private function hasDepartmentColumn(string $column): bool
    {
        return isset($this->departmentColumns[$column]);
    }

    /**
     * all
     * Retourne toutes les sociétés (companies) ordinatees par date.
     * Utilisé par l'admin et les API pour lister les entreprises.
     * Rôle: lecture publique pour Super Admin.
     */

    public function all(): array
    {
        $statement = $this->pdo->query('SELECT * FROM companies ORDER BY created_at DESC, id DESC');

        return $statement->fetchAll();
    }

    /**
     * findById
     * Retourne une société par son identifiant.
     * Utilisé par les APIs et contrôleurs pour afficher/modifier une company.
     * Rôle: lecture (Super Admin lors de l'édition).
     */

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM companies WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $company = $statement->fetch();

        return $company ?: null;
    }

    /**
     * create
     * Crée une nouvelle société dans la base de données.
     * Paramètres: tableau associatif avec keys: name,type,address,city,zip_code,phone,email
     * Rôle: action CRUD (utilisée par Super Admin via API).
     */

    public function create(array $data): int
    {
        $columns = ['name', 'type', 'address', 'city', 'zip_code', 'phone', 'email'];

        if ($this->hasCompanyColumn('logo_path')) {
            $columns[] = 'logo_path';
        }

        if ($this->hasCompanyColumn('signature_ip')) {
            $columns[] = 'signature_ip';
        }

        $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
        $statement = $this->pdo->prepare(
            'INSERT INTO companies (' . implode(', ', $columns) . ')
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
     * Met à jour les informations d'une société.
     * Rôle: action CRUD (Super Admin via API).
     */

    public function update(int $id, array $data): void
    {
        $fields = ['name', 'type', 'address', 'city', 'zip_code', 'phone', 'email'];

        if ($this->hasCompanyColumn('logo_path')) {
            $fields[] = 'logo_path';
        }

        if ($this->hasCompanyColumn('signature_ip')) {
            $fields[] = 'signature_ip';
        }

        $assignments = array_map(static fn (string $column): string => $column . ' = :' . $column, $fields);
        $statement = $this->pdo->prepare(
            'UPDATE companies
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
     * Supprime une société.
     * Rôle: action destructive, accessible uniquement au Super Admin.
     */

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM companies WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * count
     * Retourne le nombre total d'entreprises (utilisé pour stats tableau de bord).
     */

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
    }

    /**
     * directoryWithAdminsAndDepartments
     * Retourne les entreprises avec une liste d'administrateurs et de départements.
     * Utilisé par le dashboard pour afficher la company directory.
     */

    public function directoryWithAdminsAndDepartments(): array
    {
        $selectParts = [
            'c.id',
            'c.name',
            'c.city',
            'COUNT(DISTINCT d.id) AS departments_count',
                        '(SELECT COUNT(DISTINCT u_count.id)
                            FROM users u_count
                            INNER JOIN departments d_count ON d_count.id = u_count.department_id
                            WHERE d_count.company_id = c.id) AS users_count',
            'GROUP_CONCAT(DISTINCT CONCAT(u_admin.first_name, " ", u_admin.last_name) ORDER BY u_admin.last_name SEPARATOR "||") AS admins',
            'GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR "||") AS departments',
        ];

        if ($this->hasCompanyColumn('logo_path')) {
            $selectParts[] = 'c.logo_path';
        }

        if ($this->hasCompanyColumn('signature_ip')) {
            $selectParts[] = 'c.signature_ip';
        }

        $groupByParts = ['c.id', 'c.name', 'c.city'];

        if ($this->hasCompanyColumn('logo_path')) {
            $groupByParts[] = 'c.logo_path';
        }

        if ($this->hasCompanyColumn('signature_ip')) {
            $groupByParts[] = 'c.signature_ip';
        }

        $joins = [
            'LEFT JOIN departments d ON d.company_id = c.id',
            'LEFT JOIN users u_admin ON u_admin.department_id = d.id AND u_admin.role IN ("super_admin", "admin")',
        ];

        if ($this->hasDepartmentColumn('head_user_id')) {
            $selectParts[] = 'GROUP_CONCAT(DISTINCT CONCAT(u_head.first_name, " ", u_head.last_name) ORDER BY u_head.last_name SEPARATOR "||") AS heads';
            $joins[] = 'LEFT JOIN users u_head ON u_head.id = d.head_user_id';
        } else {
            $selectParts[] = 'NULL AS heads';
        }

        $statement = $this->pdo->query(
            'SELECT ' . implode(', ', $selectParts) . '
             FROM companies c
             ' . implode("\n             ", $joins) . '
               GROUP BY ' . implode(', ', $groupByParts) . '
             ORDER BY c.name ASC'
        );

        $rows = $statement->fetchAll();

        foreach ($rows as &$row) {
            $row['logo_path'] = $row['logo_path'] ?? null;
            $row['signature_ip'] = $row['signature_ip'] ?? null;
            $row['admins'] = empty($row['admins']) ? [] : array_values(array_filter(explode('||', (string) $row['admins'])));
            $row['departments'] = empty($row['departments']) ? [] : array_values(array_filter(explode('||', (string) $row['departments'])));
            $row['heads'] = empty($row['heads']) ? [] : array_values(array_filter(explode('||', (string) $row['heads'])));
            $row['departments_count'] = (int) ($row['departments_count'] ?? 0);
            $row['users_count'] = (int) ($row['users_count'] ?? 0);
        }
        unset($row);

        return $rows;
    }
}
