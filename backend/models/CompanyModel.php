<?php

class CompanyModel
{
    public function __construct(private PDO $pdo)
    {
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
        $statement = $this->pdo->prepare(
            'INSERT INTO companies (name, type, address, city, zip_code, phone, email, logo_path, signature_ip)
             VALUES (:name, :type, :address, :city, :zip_code, :phone, :email, :logo_path, :signature_ip)'
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * update
     * Met à jour les informations d'une société.
     * Rôle: action CRUD (Super Admin via API).
     */

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE companies
             SET name = :name,
                 type = :type,
                 address = :address,
                 city = :city,
                 zip_code = :zip_code,
                 phone = :phone,
                 email = :email,
                 logo_path = :logo_path,
                 signature_ip = :signature_ip
             WHERE id = :id'
        );
        $data['id'] = $id;
        $statement->execute($data);
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
        $statement = $this->pdo->query(
            'SELECT c.id, c.name, c.city, c.logo_path, c.signature_ip,
                    COUNT(DISTINCT d.id) AS departments_count,
                    COUNT(DISTINCT u_all.id) AS users_count,
                    GROUP_CONCAT(DISTINCT CONCAT(u_admin.first_name, " ", u_admin.last_name) ORDER BY u_admin.last_name SEPARATOR "||") AS admins,
                    GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR "||") AS departments,
                    GROUP_CONCAT(DISTINCT CONCAT(u_head.first_name, " ", u_head.last_name) ORDER BY u_head.last_name SEPARATOR "||") AS heads
             FROM companies c
             LEFT JOIN departments d ON d.company_id = c.id
             LEFT JOIN users u_all ON COALESCE(u_all.company_id, d.company_id) = c.id
             LEFT JOIN users u_admin ON u_admin.department_id = d.id AND u_admin.role IN ("super_admin", "admin")
             LEFT JOIN users u_head ON u_head.id = d.head_user_id
               GROUP BY c.id, c.name, c.city, c.logo_path, c.signature_ip
             ORDER BY c.name ASC'
        );

        $rows = $statement->fetchAll();

        foreach ($rows as &$row) {
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
