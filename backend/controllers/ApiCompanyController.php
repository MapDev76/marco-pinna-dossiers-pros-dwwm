<?php
/**
 * API endpoint for company operations used by the dashboard.
 *
 * Only accessible to authenticated Super Admin users. Supports JSON or
 * form-encoded requests with `action` keys such as list, create, update,
 * delete and set_signature_ip. Returns JSON responses.
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/CompanyModel.php';
require_once __DIR__ . '/../models/DepartmentModel.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$pdo = getPDO();
$companyModel = new CompanyModel($pdo);

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?: $_POST;
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

try {
    switch ($action) {
        case 'list':
            $rows = $companyModel->directoryWithAdminsAndDepartments();
            jsonResponse(['ok' => true, 'companies' => $rows]);
            break;

        case 'create':
            $name = trim((string) ($input['name'] ?? ''));
            if ($name === '') {
                jsonResponse(['ok' => false, 'error' => 'Name is required'], 400);
            }

            $data = [
                'name' => $name,
                'type' => $input['type'] ?? 'other',
                'address' => $input['address'] ?? null,
                'city' => $input['city'] ?? null,
                'zip_code' => $input['zip_code'] ?? null,
                'phone' => $input['phone'] ?? null,
                'email' => $input['email'] ?? null,
                'logo_path' => $input['logo_path'] ?? null,
                'signature_ip' => $input['signature_ip'] ?? null,
            ];

            $id = $companyModel->create($data);
            // Ensure a default 'Reception' department exists for this company.
            $departmentModel = new DepartmentModel($pdo);
            $existing = $departmentModel->findByNameAndCompanyId('Reception', (int) $id);
            if (!$existing) {
                $departmentModel->create([
                    'company_id' => $id,
                    'name' => 'Reception',
                    'description' => 'Reception department',
                    'head_user_id' => null,
                ]);
            }
            $company = $companyModel->findById($id);
            jsonResponse(['ok' => true, 'company' => $company]);
            break;

        case 'update':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['ok' => false, 'error' => 'Invalid id'], 400);
            }

            $data = [
                'name' => $input['name'] ?? '',
                'type' => $input['type'] ?? 'other',
                'address' => $input['address'] ?? null,
                'city' => $input['city'] ?? null,
                'zip_code' => $input['zip_code'] ?? null,
                'phone' => $input['phone'] ?? null,
                'email' => $input['email'] ?? null,
                'logo_path' => $input['logo_path'] ?? null,
                'signature_ip' => $input['signature_ip'] ?? null,
            ];

            $companyModel->update($id, $data);
            jsonResponse(['ok' => true]);
            break;

        case 'delete':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                jsonResponse(['ok' => false, 'error' => 'Invalid id'], 400);
            }

            $companyModel->delete($id);
            jsonResponse(['ok' => true]);
            break;

        case 'set_signature_ip':
            $companyId = (int) ($input['company_id'] ?? 0);
            $ip = trim((string) ($input['ip'] ?? ''));
            if ($companyId <= 0) {
                jsonResponse(['ok' => false, 'error' => 'Invalid company_id'], 400);
            }

            // Vérifier que la colonne existe
            $colStmt = $pdo->query("SHOW COLUMNS FROM companies LIKE 'signature_ip'");
            $has = $colStmt->fetch();
            if (!$has) {
                $pdo->exec("ALTER TABLE companies ADD COLUMN signature_ip VARCHAR(45) NULL AFTER email");
            }

            $stmt = $pdo->prepare('UPDATE companies SET signature_ip = :ip WHERE id = :id');
            $stmt->execute(['ip' => $ip ?: null, 'id' => $companyId]);

            jsonResponse(['ok' => true]);
            break;

        default:
            jsonResponse(['ok' => false, 'error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
}
