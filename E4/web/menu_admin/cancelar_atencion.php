<?php
require(__DIR__ . '/../conexion.php');
session_start();
$db = conectarBD();

$mensaje = null;
$atenciones_pendientes = [];
$datos_persona = null;
$run_ingresado = null;

// buscar info del paciente
if (isset($_POST['run']) && !empty($_POST['run'])) {
    $run_ingresado = trim($_POST['run']);

    // 1. Buscar ID de la Persona
    $sql_persona = 'SELECT "ID", "Nombres", "Apellidos" FROM "Persona" WHERE "RUN" = :run_ingresado';
    $stmt_persona = $db->prepare($sql_persona);
    $stmt_persona->bindParam(':run_ingresado', $run_ingresado);
    $stmt_persona->execute();
    $datos_persona = $stmt_persona->fetch(PDO::FETCH_ASSOC);
    
    if ($datos_persona) {
        $id_paciente = $datos_persona['ID'];

        $sql_atenciones = 'SELECT 
                a."ID", a."fecha", a."hora", a."Diagnostico",
                m."Nombres" || \' \' || m."Apellidos" AS nombre_medico,
                pe."especialidad"
            FROM "Atencion" AS a
            JOIN "Persona" AS m ON a."IDMedico" = m."ID"
            LEFT JOIN "Profecion_especialidad" AS pe ON m."ID" = pe."ID"
            WHERE a."IDPaciente" = :id_paciente AND a."Efectuada" = FALSE
            ORDER BY a."fecha" DESC, a."hora" DESC';
        
        $stmt_atenciones = $db->prepare($sql_atenciones);
        $stmt_atenciones->bindParam(':id_paciente', $id_paciente);
        $stmt_atenciones->execute();
        $atenciones_pendientes = $stmt_atenciones->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $mensaje = "RUN no encontrado. No se puede cancelar la cita.";
    }

}

// cancelar la cita/hora
if (isset($_POST['id_atencion_a_cancelar']) && !empty($_POST['id_atencion_a_cancelar'])) {
    $id_atencion = $_POST['id_atencion_a_cancelar'];
    
    // usar Try/Catch para no rompero todo
    try {
        // existen dependencias con Orden, asi que eliminare en orden primero lo relacionado a esta atencion
        $db->beginTransaction();

        // eliminar en Orden
        $sql_ordenes = 'DELETE FROM "Orden" WHERE "IDAtencion" = :id_atencion';
        $stmt_ordenes = $db->prepare($sql_ordenes);
        $stmt_ordenes->bindParam(':id_atencion', $id_atencion);
        $stmt_ordenes->execute();

        // eliminar en atencion
        $sql_cancelar_atencion = 'DELETE FROM "Atencion" WHERE "ID" = :id_atencion AND "Efectuada" = FALSE';
        $stmt_cancelar_atencion = $db->prepare($sql_cancelar_atencion);
        $stmt_cancelar_atencion->bindParam(':id_atencion', $id_atencion);
        $stmt_cancelar_atencion->execute();

        $db->commit();

        if ($stmt_cancelar_atencion->rowCount() > 0) {
            $mensaje = "Cita ID {$id_atencion} cancelada.";
        } else {
            $mensaje = " La cita ID {$id_atencion} no pudo ser cancelada.";
        }

        // restablecer el run para estar listo para una nueva cancelacion.
        $run_ingresado = null;
        //hacer rollback si existio un error
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $mensaje = "Error al cancelar la cita: " . $e->getMessage();
    }   
}

if (isset($mensaje) && isset($_POST['id_atencion_a_cancelar'])) {
    $datos_persona = null;
    $atenciones_pendientes = [];
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DCColita | Cancelar Atención Médica</title>
</head>
<body>
<div class="container">
    <h2>Cancelar Atención Médica</h2>
    
    <?php if ($mensaje): ?>
        <p><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <?php if (!$datos_persona && !$run_ingresado): ?>
        <h3>Buscar Paciente</h3>
        <form method="POST" action="cancelar_atencion.php">
            <label for="run">Ingrese el RUN del paciente:</label>
            <input type="text" id="run" name="run" required pattern="[0-9]{6}-[0-9Kk]">
            <button type="submit">Buscar Citas</button>
        </form>

    <?php elseif ($datos_persona): ?>
        <h3>Citas Pendientes para: <?php echo htmlspecialchars($datos_persona['Nombres'] . ' ' . $datos_persona['Apellidos']); ?></h3>

        <?php if (count($atenciones_pendientes) > 0): ?>
            <p>Seleccione la cita que desea cancelar (Hay <?php echo count($atenciones_pendientes); ?> pendientes):</p>
            
            <form method="POST" action="cancelar_atencion.php">
                <input type="hidden" name="run" value="<?php echo htmlspecialchars($run_ingresado); ?>">

                <table class="data-table">
                    <tr>
                        <th>ID Cita</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Médico</th>
                        <th>Especialidad</th>
                        <th>Acción</th>
                    </tr>
                    <?php foreach ($atenciones_pendientes as $cita): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cita['ID']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars(substr($cita['hora'], 0, 5)); ?></td>
                            <td><?php echo htmlspecialchars($cita['nombre_medico']); ?></td>
                            <td><?php echo htmlspecialchars($cita['especialidad'] ?? 'N/A'); ?></td>
                            <td><button type="submit" name="id_atencion_a_cancelar" value="<?php echo htmlspecialchars($cita['ID']); ?>">Cancelar Cita</button></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </form>

        <?php else: ?>
            <p>Este paciente no tiene citas pendientes.</p>
        <?php endif; ?>
    <?php endif; ?>

    <hr>
    <p><a href="cancelar_atencion.php">Reiniciar Búsqueda</a></p>
    <p><a href="menu_administrativo.php">Volver al Menú de Acciones</a></p>
    <a href="../logout.php">CERRAR SESIÓN</a>
</div>
</body>
</html>