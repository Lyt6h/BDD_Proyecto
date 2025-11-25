<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

<?php
//  este bloque PHP es paralogica para validar el login
include("conexion.php");
if(isset($_POST['id_usuario']) && isset($_POST['password'])) {
    $id_usuario = $_POST['id_usuario'];
    $pass = $_POST['password'];
    $run_completo = $pass . '-%';
    
    $dbconect = conectarBD();
    
    $consulta_sql = 'SELECT p."ID", p."medico", r."Rol", p."Nombres", p."Apellidos"
        FROM "Persona" as p
        LEFT JOIN "Rol" as r ON r."IDPersona" = p."ID"
        WHERE p."ID" = :id_usuario AND p."RUN" LIKE :run_completo';
    $stmt = $dbconect->prepare($consulta_sql);
    $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $stmt->bindParam(':run_completo', $run_completo, PDO::PARAM_STR);
    $stmt->execute();
    
    $datos_login = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($datos_login) {
        $rol = $datos_login['Rol'];
        $es_medico = (bool)$datos_login['medico'];
        $_SESSION['user_id'] = $datos_login['ID'];
        $_SESSION['user_rol'] = $rol;
        $_SESSION['is_medico'] = ($es_medico);
        $_SESSION['med_name'] = $datos_login['Nombres'] . ' ' . $datos_login['Apellidos'];
        if ($es_medico) {
            header('Location: menu_med/menu_formulario_medico.php');
            exit;
        } elseif ($rol === 'administrativo') {
            header('Location: menu_admin/menu_administrativo.php');
            exit;
        } else {
            // rol malo
            $error_message = "Usuario valido, pero sin permisos";
            $_SESSION['login_error'] = $error_message;
            header("Location: index.php");
            exit;
        }
    } else {
        // Run o Id incorrecto.
        $error_message = "Credenciales inválidas";
        $_SESSION['login_error'] = $error_message;
        header("Location: index.php");
        exit;
        
    }
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

    <form action="index.php" method="POST">
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