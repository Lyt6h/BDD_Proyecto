<?php
require(__DIR__ . "/../conexion.php");
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_medico']) {
    header("Location: index.php");
    exit;
}

$db = conectarBD();
$datos_persona = null;
$atenciones_pendientes = [];
$mensaje = null;
$run_ingresado = null; 
$id_atencion_seleccionada = null;
$cita_a_registrar = null;
$documentos_generados = []; // SP del config.slq


$id_atencion_seleccionada = isset($_POST['id_atencion_a_registrar']) ? $_POST['id_atencion_a_registrar'] : null;

if ($id_atencion_seleccionada) {
    // 1. Obtener datos completos de la cita
    $sql_cita = 'SELECT f.*, a."ID" as "id_atencion", a."hora"
        FROM ficha as f
        JOIN "Atencion" as a ON f."id_paciente" = a."IDPaciente" AND f."fecha" = a."fecha"
        WHERE a."ID" = :id_atencion';
    
    $stmt_cita = $db->prepare($sql_cita);
    $stmt_cita->bindParam(':id_atencion', $id_atencion_seleccionada);
    $stmt_cita->execute();
    $cita_a_registrar = $stmt_cita->fetch(PDO::FETCH_ASSOC);


    if ($cita_a_registrar && !$datos_persona) {
        $id_paciente = $cita_a_registrar['id_paciente'];
        $sql_nombres = 'SELECT "Nombres", "Apellidos" FROM "Persona" WHERE "ID" = :id';
        $stmt_nombres = $db->prepare($sql_nombres);
        $stmt_nombres->bindParam(':id', $id_paciente);
        $stmt_nombres->execute();
        $datos_persona = $stmt_nombres->fetch(PDO::FETCH_ASSOC);
    }
}


$run_ingresado = isset($_POST['run']) ? trim($_POST['run']) : null;

if ($run_ingresado && !$cita_a_registrar) { 
    $sql_persona = 'SELECT "ID" FROM "Persona" WHERE "RUN" = :run_param';
    $stmt_persona = $db->prepare($sql_persona);
    $stmt_persona->bindParam(':run_param', $run_ingresado);
    $stmt_persona->execute();
    $id_paciente_data = $stmt_persona->fetch(PDO::FETCH_ASSOC);

    if ($id_paciente_data) {
        $id_paciente = $id_paciente_data['ID'];
        
        // usar la vista ficha para obtener los datos
        $sql_atenciones = 'SELECT 
                f."id_paciente", f."fecha", f."nombre_medico", f."especialidad", f."diagnostico",
                a."ID" as "id_atencion", 
                a."hora"
            FROM ficha as f
            JOIN "Atencion" as a ON f."id_paciente" = a."IDPaciente" AND f."fecha" = a."fecha"
            WHERE f."id_paciente" = :id_paciente AND a."Efectuada" = FALSE AND a."IDMedico" = :id_medico_logueado
            ORDER BY f."fecha" DESC';
        
        $stmt_atenciones = $db->prepare($sql_atenciones);
        $stmt_atenciones->bindParam(':id_paciente', $id_paciente);
        $stmt_atenciones->bindParam(':id_medico_logueado', $_SESSION['user_id']);
        $stmt_atenciones->execute();
        $atenciones_pendientes = $stmt_atenciones->fetchAll(PDO::FETCH_ASSOC);
        
        $sql_nombres = 'SELECT "Nombres", "Apellidos" FROM "Persona" WHERE "ID" = :id';
        $stmt_nombres = $db->prepare($sql_nombres);
        $stmt_nombres->bindParam(':id', $id_paciente);
        $stmt_nombres->execute();
        $datos_persona = $stmt_nombres->fetch(PDO::FETCH_ASSOC);

    } else {
        $mensaje = "RUN no encontrado.";
    }

}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DCColita | Formulario Médico</title>
</head>
<body>
<div class="container">
    <h1>Bienvenido/a Dr/a. <?php echo $_SESSION['med_name'] ?></h1>
    
    <?php if ($mensaje): ?>
        <p><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <?php if (!$datos_persona && !$cita_a_registrar && !isset($_POST['run'])): ?>
                <h3>Buscar Paciente Agendado</h3>
        <form method="POST" action="menu_formulario_medico.php">
            <label for="run">Ingrese el RUN del paciente:</label>
            <input type="text" id="run" name="run" required pattern="[0-9]{6}-[0-9Kk]">
            <button type="submit">Buscar Citas Pendientes</button>
        </form>

    <?php elseif ($cita_a_registrar): ?>
        <h3>Registrar Atención Médica ID <?php echo htmlspecialchars($cita_a_registrar['id_atencion']); ?></h3>
        <p>Paciente: <?php echo htmlspecialchars($datos_persona['Nombres'] . ' ' . $datos_persona['Apellidos']); ?></p>
        <p>Médico: <?php echo htmlspecialchars($cita_a_registrar['nombre_medico']); ?> (<?php echo htmlspecialchars($cita_a_registrar['especialidad']); ?>)</p>
        <p>Fecha: <?php echo date('d/m/Y', strtotime($cita_a_registrar['fecha'])); ?></p>
        
        <hr>

        <form method="POST" action="menu_formulario_medico.php">
            <input type="hidden" name="id_atencion_final" value="<?php echo htmlspecialchars($cita_a_registrar['id_atencion']); ?>">
            <input type="hidden" name="run" value="<?php echo htmlspecialchars($run_ingresado); ?>">

            <h4>Diagnóstico</h4>
            <label for="diagnostico">Diagnóstico:</label><br>
            <textarea id="diagnostico" name="diagnostico" rows="4" cols="50"></textarea>

            <h4>Medicamentos</h4>
            <p>Ingrese el medicamento y su posología.</p>
            <label for="medicamento_nombre">Nombre:</label>
            <input type="text" id="medicamento_nombre" name="medicamento_nombre"><br>
            <label for="medicamento_posologia">Posología:</label>
            <input type="text" id="medicamento_posologia" name="medicamento_posologia"><br>
            <label for="psicotropico">Psicotrópico (Si/No):</label>
            <input type="checkbox" id="psicotropico" name="psicotropico" value="TRUE">

            <br><br>
            <button type="submit" name="registrar_atencion">Guardar Atención y Finalizar</button>
        </form>

    <?php elseif ($datos_persona): ?>
                <h3>Citas Pendientes para: <?php echo htmlspecialchars($datos_persona['Nombres'] . ' ' . $datos_persona['Apellidos']); ?></h3>

        <?php if (!empty($atenciones_pendientes)): ?>
            <p>Seleccione la cita actual:</p>
            
            <form method="POST" action="menu_formulario_medico.php">
                <input type="hidden" name="run" value="<?php echo htmlspecialchars($run_ingresado); ?>">
            
                <table class="data-table" >
                    <tr>
                        <th> </th>
                        <th>ID Cita</th>
                        <th>Fecha y Hora</th>
                        <th>Médico</th>
                    </tr>
                    <?php foreach ($atenciones_pendientes as $cita): ?>
                        <tr>
                            <td><button type="submit" name="id_atencion_a_registrar" value="<?php echo $cita['id_atencion']; ?>">Seleccionar</button></td>                                  <td><?php echo htmlspecialchars($cita['id_atencion']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cita['fecha'])) . ' ' . htmlspecialchars(substr($cita['hora'], 0, 5)); ?></td>
                            <td><?php echo htmlspecialchars($cita['nombre_medico']); ?></td>
                            <td><?php echo htmlspecialchars($cita['especialidad'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

            </form>
        <?php else: ?>
            <p>Este paciente no tiene citas pendientes para registrar.</p>
        <?php endif; ?>
    <?php endif; ?>
    
    <hr>
    <p><a href="menu_formulario_medico.php">Reiniciar Búsqueda</a></p>
    <p><a href="/logout.php">CERRAR SESIÓN</a></p>
</div>
</body>
</html>

<!-- 201979-5 run para ejemplo -->
