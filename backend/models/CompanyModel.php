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
            'INSERT INTO companies (name, type, address, city, province, zip_code, phone, email)
             VALUES (:name, :type, :address, :city, :province, :zip_code, :phone, :email)'
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
                 province = :province,
                 zip_code = :zip_code,
                 phone = :phone,
                 email = :email
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
            'SELECT c.id, c.name, c.city,
                    GROUP_CONCAT(DISTINCT CONCAT(u.first_name, " ", u.last_name) ORDER BY u.last_name SEPARATOR "||") AS admins,
                    GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR "||") AS departments
             FROM companies c
             LEFT JOIN departments d ON d.company_id = c.id
             LEFT JOIN users u ON u.department_id = d.id AND u.role = "admin"
             GROUP BY c.id, c.name, c.city
             ORDER BY c.name ASC'
        );

        $rows = $statement->fetchAll();

        foreach ($rows as &$row) {
            $row['admins'] = empty($row['admins']) ? [] : array_values(array_filter(explode('||', (string) $row['admins'])));
            $row['departments'] = empty($row['departments']) ? [] : array_values(array_filter(explode('||', (string) $row['departments'])));
        }
        unset($row);

        return $rows;
    }
}
