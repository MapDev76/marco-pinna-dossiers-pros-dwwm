<?php
// Routeur simple : associe une valeur de route à une vue située dans public/views.
$route = $_GET['route'] ?? 'home';

$routes = [
    'home' => realpath(__DIR__ . '/../public/views/home.php'),
    'login' => realpath(__DIR__ . '/../backend/controllers/AuthController.php'),
    'logout' => realpath(__DIR__ . '/../backend/controllers/AuthController.php'),
    'dashboard' => realpath(__DIR__ . '/../backend/controllers/DashboardController.php'),
    'api-dashboard' => realpath(__DIR__ . '/../backend/controllers/ApiDispatcher.php'),
    'api-companies' => realpath(__DIR__ . '/../backend/controllers/ApiDispatcher.php'),
    'api-departments' => realpath(__DIR__ . '/../backend/controllers/ApiDispatcher.php'),
    'api-users' => realpath(__DIR__ . '/../backend/controllers/ApiDispatcher.php'),
    'document-download' => realpath(__DIR__ . '/../backend/controllers/DocumentDownloadController.php'),
    'my-space' => realpath(__DIR__ . '/../backend/controllers/EmployeeSpaceController.php'),
    'users' => realpath(__DIR__ . '/../backend/controllers/UsersController.php'),
    'companies' => realpath(__DIR__ . '/../backend/controllers/CompaniesController.php'),
    'departments' => realpath(__DIR__ . '/../backend/controllers/DepartmentsController.php'),
    '404' => realpath(__DIR__ . '/../public/views/404.php'),
];

return $routes[$route] ?? $routes['404'];
