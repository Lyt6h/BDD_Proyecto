<?php
function conectarBD() {
    $host = "localhost";
    $dbname = "E4";
    $usuario = "postgres";
    $clave = "24664235";

    try {
        $db = new PDO("pgsql:host=$host; dbname=$dbname", $usuario, $clave);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        echo "Error de conexion: " . $e->getMessage();
        exit();
    }
}
?>