<?php
// Routeur simple : associe une valeur de route à une vue située dans public/views.
$route = $_GET['route'] ?? 'home';

$routes = [
    'home' => __DIR__ . '/../public/views/home.php',
    'login' => __DIR__ . '/../backend/controllers/AuthController.php',
    'logout' => __DIR__ . '/../backend/controllers/AuthController.php',
    'dashboard' => __DIR__ . '/../backend/controllers/DashboardController.php',
    'users' => __DIR__ . '/../backend/controllers/UsersController.php',
    'companies' => __DIR__ . '/../backend/controllers/CompaniesController.php',
    'departments' => __DIR__ . '/../backend/controllers/DepartmentsController.php',
    '404' => __DIR__ . '/../public/views/404.php',
];

return $routes[$route] ?? $routes['404'];
