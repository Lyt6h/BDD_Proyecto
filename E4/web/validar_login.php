<?php
    include("conexion.php");
    session_start();

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
                header('Location: formulario_medico.php');
                exit;
            } elseif ($rol === 'administrativo') {
                header('Location: menu_administrativo.php');
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