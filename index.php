<?php
// Point d'entrée frontal : lit la route, charge le routeur et affiche la mise en page commune.
$route = $_GET['route'] ?? 'home';
$pageFile = require __DIR__ . '/app/router.php';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$basePath = $scriptDir === '/' ? '' : rtrim($scriptDir, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
        <!-- Métadonnées de base et feuille de style principale -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>StaffEase Pro</title>
        <link rel="icon" href="<?php echo $basePath; ?>/assets/images/faviconStaffeasePro.jpg" type="image/jpeg">
        <link rel="stylesheet" href="<?php echo $basePath; ?>/assets/css/style.css">
</head>
<body>

<?php
// En-tête partagé utilisé par toutes les pages.
require __DIR__ . '/app/layout/header.php';
?>

<main class="content">
<?php
// Vue finale déterminée par le routeur.
require $pageFile;
?>
</main>

</body>
</html>