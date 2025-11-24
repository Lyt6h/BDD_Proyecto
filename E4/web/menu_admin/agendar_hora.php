<?php
require(__DIR__ . '/../conexion.php');
$db = conectarBD();

$mostrar_formulario = true;
$datos_persona = null;

$medicos_encontrados = [];
$busqueda_activa = false;
$especialidades_lista = [];

//buscar todas las especialidades de los medicos
$sql_especialidades = 'SELECT DISTINCT "especialidad" 
    FROM "Profecion_especialidad" 
    WHERE "especialidad" IS NOT NULL 
    ORDER BY "especialidad"';
$stmt_esp = $db->query($sql_especialidades);
$especialidades_lista = $stmt_esp->fetchAll(PDO::FETCH_COLUMN);



// logica de Obtener datos del paciente
if (isset($_POST['run']) && !empty($_POST['run'])) {
    $run_ingresado = trim($_POST['run']);
        
    $sql = 'SELECT 
            p."ID", p."RUN", p."Nombres", p."Apellidos", p."Direccion", p."email", p."telefono",
            r."Rol",
            b."Beneficiario" AS es_beneficiario,
            b."IDtitular"
        FROM "Persona" AS p
        LEFT JOIN "Rol" AS r ON p."ID" = r."IDPersona"
        LEFT JOIN "beneficiario" AS b ON p."ID" = b."IDpersona"
        WHERE p."RUN" = :run_param';

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':run_param', $run_ingresado);
    $stmt->execute();
    
    $datos_persona = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($datos_persona) {
        $mostrar_formulario = false; // oculta el formulario de run si se encuentra la persona
    } else {
        $mensaje = "RUN no encontrado. Por favor, intente de nuevo.";
    }

}




// logica de obtener medico en caso de que se encontrara persona(paciente)
if (isset($datos_persona) && $datos_persona && (isset($_POST['nombre_buscado']) || isset($_POST['id_especialidad_seleccionada']))) {
    
    $nombre_buscado = trim($_POST['nombre_buscado'] ?? '');
    $especialidad_seleccionada = trim($_POST['id_especialidad_seleccionada'] ?? '');

    // con tal de que un campo tenga contenido
    if (!empty($nombre_buscado) || !empty($especialidad_seleccionada)) {
        $busqueda_activa = true;

            
        // Construcción dinámica de la cláusula WHERE
        $where_clauses = [];
        $params = [];
        
        // busqueda por nombre
        if (!empty($nombre_buscado)) {
            $where_clauses[] = '(m."Nombres" LIKE :nombre_p OR m."Apellidos" LIKE :nombre_p)';
            $params[':nombre_p'] = '%' . $nombre_buscado . '%';
        }
        
        // busqueda por especialidad
        if (!empty($especialidad_seleccionada) && $especialidad_seleccionada !== 'todos') {
            $where_clauses[] = 'pe."especialidad" = :especialidad_p';
            $params[':especialidad_p'] = $especialidad_seleccionada;
        }

        $sql_medicos = 'SELECT
                m."ID", m."Nombres", m."Apellidos", m."RUN",
                pe."profesion", 
                pe."especialidad"
            FROM
                "Persona" AS m
            JOIN 
                "Profecion_especialidad" AS pe ON m."ID" = pe."ID"
            WHERE 
                m.medico = TRUE
                ' . (count($where_clauses) > 0 ? " AND (" . implode(" OR ", $where_clauses) . ")" : "") . '            ORDER BY 
                m."Apellidos"';
        
        $stmt_medicos = $db->prepare($sql_medicos);
        
        // Bind de los parámetros
        foreach ($params as $key => $value) {
            $stmt_medicos->bindValue($key, $value);
        }
        $stmt_medicos->execute();
        $medicos_encontrados = $stmt_medicos->fetchAll(PDO::FETCH_ASSOC);

    }
}
?>







<?php //interfaz  ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Hora Médica</title> 
</head>
<body>
<div class="container">
    <h2>Agendamiento de Hora Médica</h2>
    
    <?php if (isset($mensaje)): ?>
        <p class="error"><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <?php if ($mostrar_formulario): ?>
        <form method="POST" action="agendar_hora.php">
            <label for="run">Ingrese el RUN del paciente:</label>
            <input type="text" id="run" name="run" required pattern="[0-9]{6}-[0-9Kk]">
            <button type="submit">Buscar Persona</button>
        </form>
    <?php else: ?>
        <p class="success">Persona encontrada. Revise la información antes de agendar.</p>
        
        <h3>Datos del Paciente</h3>
        <table class="data-table">
            <tr><th>ID Paciente</th><td><?php echo htmlspecialchars($datos_persona['ID']); ?></td></tr>
            <tr><th>RUN</th><td><?php echo htmlspecialchars($datos_persona['RUN']); ?></td></tr>
            <tr><th>Nombre Completo</th><td><?php echo htmlspecialchars($datos_persona['Nombres'] . ' ' . $datos_persona['Apellidos']); ?></td></tr>
            <tr><th>Dirección</th><td><?php echo htmlspecialchars($datos_persona['Direccion'] . ' ' . $datos_persona['Apellidos']); ?></td></tr>
            <tr><th>Email</th><td><?php echo htmlspecialchars($datos_persona['email'] ?? 'N/A'); ?></td></tr>
            <tr><th>Teléfono</th><td><?php echo htmlspecialchars($datos_persona['telefono'] ?? 'N/A'); ?></td></tr>
            <tr><th>Rol</th><td><?php echo htmlspecialchars($datos_persona['Rol'] ?? 'N/A'); ?></td></tr>
            <tr><th>Beneficiario</th><td><?php echo $datos_persona['es_beneficiario'] ? 'Sí' : 'No'; ?></td></tr>
            
            <?php if ($datos_persona['es_beneficiario'] && $datos_persona['IDtitular']): ?>
                <tr><th>ID Titular</th><td><?php echo htmlspecialchars($datos_persona['IDtitular']); ?></td></tr>
            <?php endif; ?>

        </table>
        
        <hr>  <!-- linea bonita-->

        <h3>Buscar Médico</h3>
        <form method="POST" action="agendar_hora.php">
            <input type="hidden" name="run" value="<?php echo htmlspecialchars($datos_persona['RUN']); ?>">

            <label for="nombre_buscado">Buscar por Nombre o Apellido:</label>
            <input type="text" id="nombre_buscado" name="nombre_buscado">
            
            <label for="especialidad_seleccionada">Seleccionar Especialidad:</label>
            <select id="id_especialidad_seleccionada" name="id_especialidad_seleccionada">
                <option value="todos">-- Seleccione una Especialidad (Opcional) --</option>
                <?php foreach ($especialidades_lista as $especialidad): ?>
                    <option value="<?php echo htmlspecialchars($especialidad); ?>">
                        <?php echo htmlspecialchars($especialidad); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <?php if (isset($mensaje_esp)): ?>
                <p class="error"><?php echo $mensaje_esp; ?></p>
            <?php endif; ?>
            
            <button type="submit">Buscar Médico</button>
        </form>

        <hr>

        <?php if ($busqueda_activa): ?>
            <?php if (count($medicos_encontrados) > 0): ?>
                <h4>Resultados de la Búsqueda (<?php echo count($medicos_encontrados); ?> encontrados)</h4>
                
                <form method="POST" action="agendar_hora.php">
                    <input type="hidden" name="run" value="<?php echo htmlspecialchars($datos_persona['RUN']); ?>">

                    <table class="data-table">
                        <tr>
                            <th>Seleccionar</th>
                            <th>Nombre Médico</th>
                            <th>Especialidad</th>
                        </tr>
                        <?php foreach ($medicos_encontrados as $medico): ?>
                            <tr>
                                <td>
                                    <button type="submit" name="id_medico_seleccionado" value="<?php echo $medico['ID']; ?>">
                                        Seleccionar
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars($medico['Nombres'] . ' ' . $medico['Apellidos']); ?></td>
                                <td><?php echo htmlspecialchars($medico['especialidad'] ?? $medico['profesion']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </form>

            <?php else: ?>
                <p class="error">No se encontraron medicos</p>
            <?php endif; ?>
        <?php endif; ?>
        
    <?php endif; ?>
        
    <a href="agendar_hora.php">Reiniciar Búsqueda</a>
    <p><a href="../menu_administrativo.php">Volver al Menú de Acciones</a></p>
    <a href="../logout.php">CERRAR SESIÓN</a>
</div>
</body>
</html>