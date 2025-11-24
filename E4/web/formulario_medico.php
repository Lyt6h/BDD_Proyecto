<?php
require("conexion.php");
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_medico']) {
    header("Location: index.php");
    exit;
}

?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCColita | Formulario Medico</title>
</head>
<body>
<div class="menu-container">
    <h2>Dr/a. <?php echo $_SESSION['med_name']?></h2>
    
    <h3>MERA KBRON que trabajo HP<h3>
</div>
<a href="/logout.php">CERRAR SESIÓN</a>
</body>
</html>