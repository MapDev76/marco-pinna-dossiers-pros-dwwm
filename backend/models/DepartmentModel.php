<?php

class DepartmentModel
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * allWithCompany
     * Retourne la liste des départements avec le nom de l'entreprise associée.
     * Rôle: consultation pour Super Admin et admin.
     */

    public function allWithCompany(): array
    {
        $statement = $this->pdo->query(
            'SELECT d.*, c.name AS company_name, CONCAT(hu.first_name, " ", hu.last_name) AS head_user_name
             FROM departments d
             LEFT JOIN companies c ON c.id = d.company_id
             LEFT JOIN users hu ON hu.id = d.head_user_id
             ORDER BY d.created_at DESC, d.id DESC'
        );

        return $statement->fetchAll();
    }

    /**
     * allForSelect
     * Retourne id, company_id et nom pour utiliser dans des listes déroulantes.
     */

    public function allForSelect(): array
    {
        $statement = $this->pdo->query('SELECT id, company_id, name FROM departments ORDER BY name ASC');

        return $statement->fetchAll();
    }

    /**
     * findById
     * Recherche un département par son id.
     */

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM departments WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $department = $statement->fetch();

        return $department ?: null;
    }

    /**
     * create
     * Crée un département pour une entreprise donnée.
     * Rôle: CRUD accessible par Super Admin et admin.
     */

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO departments (company_id, name, description, head_user_id)
             VALUES (:company_id, :name, :description, :head_user_id)'
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * update
     * Met à jour un département (company_id, name, description).
     */

    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE departments
             SET company_id = :company_id,
                 name = :name,
                 description = :description,
                 head_user_id = :head_user_id
             WHERE id = :id'
        );
        $data['id'] = $id;
        $statement->execute($data);
    }

    /**
     * delete
     * Supprime un département par id.
     */

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM departments WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * count
     * Retourne le nombre total de départements.
     */

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM departments')->fetchColumn();
    }

    /**
     * countByCompanyId
     * Nombre de départements pour une entreprise.
     */

    public function countByCompanyId(int $companyId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM departments WHERE company_id = :company_id');
        $statement->execute(['company_id' => $companyId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * byCompanyId
     * Liste des départements d'une entreprise spécifique.
     */

    public function byCompanyId(int $companyId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT d.id, d.company_id, d.name, d.description, d.head_user_id, CONCAT(u.first_name, " ", u.last_name) AS head_user_name
             FROM departments d
             LEFT JOIN users u ON u.id = d.head_user_id
             WHERE d.company_id = :company_id
             ORDER BY d.name ASC, d.id DESC'
        );
        $statement->execute(['company_id' => $companyId]);

        return $statement->fetchAll();
    }
}
