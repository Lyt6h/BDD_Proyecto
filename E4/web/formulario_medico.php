<?php
include("conexion.php");
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_medico']) {
    header("Location: index.php");
    exit;
}

$medico_id = $_SESSION['user_id'];

$paciente = null;
$ficha = [];
$medicamentos = [];
$examenes = [];

if (isset($_GET["run"])) {

    $run = $_GET["run"];

    $q = "SELECT * FROM persona WHERE run = $1";
    $res = pg_query_params($conn, $q, array($run));
    $paciente = pg_fetch_assoc($res);

    $q = "SELECT * FROM ficha WHERE run = $1 ORDER BY fecha DESC";
    $res = pg_query_params($conn, $q, array($run));
    if ($res) {
        $ficha = pg_fetch_all($res);
    }

    $q = "SELECT nombre, tipo FROM medicamento ORDER BY nombre";
    $res = pg_query($conn, $q);
    if ($res) {
        $medicamentos = pg_fetch_all($res);
    }

    $q = "SELECT nombre FROM examen ORDER BY nombre";
    $res = pg_query($conn, $q);
    if ($res) {
        $examenes = pg_fetch_all($res);
    }
}
?>

<!DOCTYPE html>
<html>
<body>

<h2>Formulario Médico</h2>

<form method="GET" action="formulario_medico.php">
    RUN Paciente: <input type="text" name="run">
    <input type="submit" value="Buscar">
</form>

<?php if ($paciente): ?>

<h3>Datos del paciente</h3>
<p>Nombre: <?php echo $paciente["nombre"]; ?></p>

<h3>Ficha</h3>
<?php
if (!$ficha) {
    echo "<p>Sin atenciones previas</p>";
} else {
    foreach ($ficha as $fila) {
        echo $fila["fecha"]." - ".$fila["medico"]." - ".$fila["diagnostico"]."<br>";
    }
}
?>

<h3>Registrar nueva atención</h3>

<form method="POST" action="atencion_medica.php">

    <input type="hidden" name="run" value="<?php echo $run; ?>">

    Diagnóstico:<br>
    <textarea name="diagnostico" rows="4" cols="40"></textarea><br><br>

    Medicamentos:<br>
    <?php
    foreach ($medicamentos as $m) {
        echo '<input type="checkbox" name="meds[]" value="'.$m["nombre"].'">'.$m["nombre"].' ('.$m["tipo"].')<br>';
    }
    ?>
    <br>

    Exámenes:<br>
    <?php
    foreach ($examenes as $e) {
        echo '<input type="checkbox" name="exams[]" value="'.$e["nombre"].'">'.$e["nombre"].'<br>';
    }
    ?>

    <br>
    <input type="submit" value="Guardar Atención">

</form>

<?php endif; ?>

</body>
</html>