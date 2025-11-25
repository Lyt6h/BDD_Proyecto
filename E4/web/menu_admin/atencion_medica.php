<?php
require(__DIR__ . '/../conexion.php');
session_start();
$db = conectarBD();

$datos_paciente = null;
$cita_mas_cercana = null;
$mensaje = null;
$run_ingresado = null;

if (isset($_POST['run']) && !empty($_POST['run'])) { 
    $run_ingresado = trim($_POST['run']);   

    $sql_persona = 'SELECT "ID", "Nombres", "Apellidos", "RUN", "telefono", "email", "Direccion" FROM "Persona" WHERE "RUN" = :run_param';
    $stmt_persona = $db->prepare($sql_persona);
    $stmt_persona->bindParam(':run_param', $run_ingresado);
    $stmt_persona->execute();
    $datos_paciente = $stmt_persona->fetch(PDO::FETCH_ASSOC);   
    if ($datos_paciente) {
        $id_paciente = $datos_paciente['ID'];

        $sql_cita_cercana = 'SELECT 
            a."ID" AS "id_atencion", a."fecha", a."hora", 
            m."Nombres" || \' \' || m."Apellidos" AS nombre_medico,
            pe."especialidad"
            FROM "Atencion" AS a
            JOIN "Persona" AS m ON a."IDMedico" = m."ID"
            LEFT JOIN "Profecion_especialidad" AS pe ON m."ID" = pe."ID"
            WHERE a."IDPaciente" = :id_paciente AND a."Efectuada" = FALSE 
            ORDER BY a."fecha" ASC, a."hora" ASC
            LIMIT 1';

        $stmt_cita = $db->prepare($sql_cita_cercana);
        $stmt_cita->bindParam(':id_paciente', $id_paciente);
        $stmt_cita->execute();
        $cita_mas_cercana = $stmt_cita->fetch(PDO::FETCH_ASSOC);  
            if (!$cita_mas_cercana) {
                $mensaje = "El paciente ha sido encontrado, pero no tiene horas agendadas pendientes.";
            } 
    } else {
    $mensaje = "RUN no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DCColita | Recepción de Pacientes</title>
</head>
<body>
<div class="container">
    <h1>Recepción de Pacientes</h1>

    <?php if ($mensaje): ?>
        <p><?php echo $mensaje; ?></p>
    <?php endif; ?> 
        <h3>Búsqueda de Paciente</h3>
    <form method="POST" action="atencion_medica.php">
        <label for="run">Ingrese el RUN del paciente:</label>
        <input type="text" id="run" name="run" value="<?php echo htmlspecialchars($run_ingresado ?? ''); ?>" required pattern="[0-9]{6}-[0-9Kk]">
        <button type="submit">Buscar Atención</button>
    </form>
        <hr>
    
    <?php if ($datos_paciente): ?>
        <h2>Datos Personales del Paciente</h2>
    
            <p><strong>RUN:</strong> <?php echo htmlspecialchars($datos_paciente['RUN']); ?></p>
        <p><strong>Nombre Completo:</strong> <?php echo htmlspecialchars($datos_paciente['Nombres'] . ' ' . $datos_paciente['Apellidos']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($datos_paciente['telefono']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($datos_paciente['email']); ?></p>
            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($datos_paciente['Direccion']); ?></p>  
            <hr>    
            <?php if ($cita_mas_cercana): ?>
                <h2>Hora Agendada más Cercana (ID: <?php echo htmlspecialchars($cita_mas_cercana['id_atencion']); ?>)</h2>   
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cita_mas_cercana['fecha'])); ?></p>
                <p><strong>Médico:</strong> Dr/a. <?php echo htmlspecialchars($cita_mas_cercana['nombre_medico']); ?></p>
                <p><strong>Especialidad:</strong> <?php echo htmlspecialchars($cita_mas_cercana['especialidad'] ?? 'General'); ?></p>   
            <?php else: ?>
                <p>No se encontró ninguna hora agendada pendiente para este paciente.</p>
            <?php endif; ?>
        
    <?php endif; ?>


    <p><a href="atencion_medica.php">Reiniciar Búsqueda</a></p>
    <p><a href="menu_administrativo.php">Volver al Menú de Acciones</a></p>
    <a href="/logout.php">CERRAR SESIÓN</a></p>
</div>
</body>
</html>