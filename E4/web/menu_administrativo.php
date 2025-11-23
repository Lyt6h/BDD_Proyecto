<?php
session_start();
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'administrativo') {
    header('Location: index.php?error=' . urlencode('Acceso denegado.'));
    exit;
}
echo "<h1>Bienvenido, Administrativo!</h1>";
echo "<p>Tu ID es: " . $_SESSION['user_id'] . "</p>";
?>