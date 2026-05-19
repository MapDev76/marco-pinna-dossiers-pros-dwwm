<?php
$route = $_GET['route'] ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>StaffEase Pro</title>
        <link rel="icon" href="/assets/images/faviconStaffeasePro.jpg" type="image/jpeg">
        <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/layout/header.php'; ?>

<main class="content">
<?php
switch ($route) {
        case 'login':
                require __DIR__ . '/public/views/login.php';
                break;
        case 'home':
        default:
                require __DIR__ . '/public/views/home.php';
                break;
}
?>
</main>

</body>
</html>