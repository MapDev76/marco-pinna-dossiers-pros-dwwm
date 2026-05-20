<?php

$route = $_GET['route'] ?? 'home';

$routes = [
    'home' => __DIR__ . '/../public/views/home.php',
    'login' => __DIR__ . '/../public/views/login.php',
    '404' => __DIR__ . '/../public/views/404.php',
    'dashboard' => __DIR__ . '/../public/views/dashboard.php',
];

return $routes[$route] ?? $routes['404'];
