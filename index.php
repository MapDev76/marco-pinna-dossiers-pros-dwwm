<?php
$route = $_GET['route'] ?? 'home';
$pageFile = require __DIR__ . '/app/router.php';
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
require $pageFile;
?>
</main>

</body>
</html>