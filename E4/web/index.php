<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DCColita de Rana</title>
</head>
<body>
    <h2>Iniciar Sesión</h2>

    <?php
    if (isset($_SESSION['login_error'])) {
        // Mostrar el mensaje
        echo '<div class="error-message">';
        echo '<strong>Error</strong> ' . htmlspecialchars($_SESSION['login_error']);
        echo '</div>';

        // Eliminar el mensaje de la sesión para que no se muestre al recargar
        unset($_SESSION['login_error']);
    }
    ?>

    <form action="validar_login.php" method="POST">
        <!-- user -->
        <label for="id_usuario">ID de Usuario</label>
        <input type="text" name="id_usuario" required><br><br>

        <!-- pass -->
        <label for="password">RUN (sin digito verificador):</label>
        <input type="password" name="password" required><br><br>

        <!-- botoncito -->
        <input type="submit" value="Ingresar">
    </form>
</body>
</html>