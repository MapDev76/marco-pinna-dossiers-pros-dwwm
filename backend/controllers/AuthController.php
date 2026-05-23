<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/UserModel.php';

$userModel = new UserModel(getPDO());
$pageTitle = 'Connexion StaffEase Pro';
$viewFile = __DIR__ . '/../../public/views/auth/login.php';
$loginEmail = trim($_POST['email'] ?? '');
$loginError = null;

if (($_GET['route'] ?? 'login') === 'logout') {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
    session_start();
    setFlash('success', 'Vous êtes déconnecté.');
    redirectTo('login');
}

if (isLoggedIn()) {
    redirectTo('dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');

    if ($loginEmail === '' || $password === '') {
        $loginError = 'Veuillez remplir l’email et le mot de passe.';
    } else {
        $user = $userModel->findByEmail($loginEmail);

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
            $loginError = 'Identifiants invalides ou accès refusé.';
        } else {
            $_SESSION['auth_user'] = [
                'id' => (int) $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'department_id' => $user['department_id'],
            ];

            $welcomeLabel = match ($user['role']) {
                'super_admin' => 'Super Admin',
                'admin' => 'Admin',
                'department_manager' => 'Chef de département',
                'employee' => 'Employé',
                default => 'utilisateur',
            };

            setFlash('success', 'Connexion réussie. Bienvenue ' . $welcomeLabel . '.');
            redirectTo('dashboard');
        }
    }
}
