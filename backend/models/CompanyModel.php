<?php

class CompanyModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $statement = $this->pdo->query('SELECT * FROM companies ORDER BY created_at DESC, id DESC');

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM companies WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $company = $statement->fetch();

        return $company ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO companies (name, type, address, city, province, zip_code, phone, email)
             VALUES (:name, :type, :address, :city, :province, :zip_code, :phone, :email)'
        );
        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

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

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM companies WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
    }
}
