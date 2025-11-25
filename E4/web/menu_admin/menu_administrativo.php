<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>DCColita | Menú Admin</title>
</head>
<body>
<div class="menu-container">
    <h2>Menú De Acciones</h2>
    
    <form action="agendar_hora.php" method="GET">
        <button type="submit" class="menu-button">Agendamiento de Hora Médica</button>
    </form><br><br>
    
    <form action="atencion_medica.php" method="GET">
        <button type="submit" class="menu-button">Registrar Atención Médica</button>
    </form><br><br>
    
    <form action="cancelar_atencion.php" method="GET">
        <button type="submit" class="menu-button">Cancelar Atención Médica</button>
    </form><br><br>

</div>
<a href="/logout.php">CERRAR SESIÓN</a>
</body>
</html>
