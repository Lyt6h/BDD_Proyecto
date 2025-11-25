# Informe Entrega 3 - Bases de datos IIC2413

### Datos del Alumno
| **Apellidos**       | **Nombres**          | **Número de Alumno** |
|---------------------|----------------------|----------------------|
|  Palomera Coccio    | Matias Felipe        |24664235              |

### 1. Descripción de la solución 
<!-- El análisis de la solución, describe aquí qué y como usaste  HTML, CSS, PHP, SQL, PSQL. 
Cómo rescatas los datos de la base para desplegarlo en los formularios hy viceversa-->
Archivo `config.sql`
- __Indices__: Se crearon los índices secundarios (`Persona_RUN`, `InstituciondeSalud_RUT`) y el índice primario compuesto (`PK_agenda`).
- __Nueva Tabla e Integración de Datos__: Se creó la tabla `Profecion_especialidad` para integrar los datos del archivo `profesionconespecialidad.csv` mediante el comando `COPY FROM stdin` dentro del sql para evitar problemas con paths.
- __Procedimiento Almacenado (SP)__: La función `generar_archivos()` implementa la lógica de generación de texto para Recetas (Normal y Psicotrópica), Órdenes de Examen y el Bono de `Atención` (INCOMPLETO). No llega a implementar la generacion de los archivos en el servidor.
- __Trigger__: Se implementó el Trigger `arch_trgg` en la tabla `Atencion` que se dispara `AFTER UPDATE` para llamar a una función intermedia (``inter_trgg``). Esta función ejecuta el SP ``generar_archivos(ID)`` cuando el estado "Efectuada" cambia de ``FALSE`` a ``TRUE``.
- __Vista (ficha)__: La vista fue creada y actualizada para utilizar la nueva tabla `Profecion_especialidad`.
- __Transacciones y Validación__: Las secciones Transacciones (1.c) y Validación (1.g) en config.sql están incompletas en la entrega (se usan transacciones durante el proyecto pero se implementan en PHP).

Seccion Web (HTML/PHP/PDO):
- __Manejo de Usuarios__: El flujo se inicia en index.php (login), que separa si quien inicia sesion es medico, admin o un usuario inválido.
- __Cancelar Atención Médica__ (cancelar_atencion.php): Implementación Completa. Se busca por RUN, se listan las citas pendientes, y la cancelación se ejecuta usando PDO::beginTransaction() y DELETE (DELETE FROM Orden/Medicamentos antes de DELETE FROM Atencion) para cumplir con la integridad de la DB.
- __Agendamiento de Hora Médica__ (agendar_hora.php): El proceso permite buscar y encontrar médicos por nombre o especialidad, pero la tercera parte (selección de fecha/hora e inserción en Agenda) está incompleta
- __Registrar Atención Médica__ (menu_formulario_medico.php): Se llega hasta la parte de ingresar el diagnostico y medicamento, luego lo siguiente esta incompleto, lo que significa que no se generan ni archivos ni ccambios en la DB.

### 2. Referencias a documentación externa válida
- https://www.youtube.com/watch?v=IZHBMwGIAoI "Sitio WEB con php y mysql"  
- https://www.geeksforgeeks.org/plsql/how-to-insert-a-line-break-in-a-string-plsql/ Uso de CHR(10) para insertar un salto de linea. Usado en la creacion de Recetas y ordenes.  
- https://www.postgresql.org/docs/current/plpgsql-statements.html - https://www.postgresql.org/docs/16/adminpack.html Escribir/generar/crear los archivos para las recetas/ordenes/bonos.  
- https://www.todopostgresql.com/postgresql-create-trigger-disparador-postgresql/ Usado para crear Trigger
- https://www.php.net/manual/es/function.pg-query.php Para evitar SQL_injection.  
- https://developer.mozilla.org/es/docs/Web/HTML/Reference/Elements/input elementos de HTML como input, label, textarea.  
- https://www.w3schools.com/php/php_sessions.asp Variables globales yeyy
- https://www.w3schools.com/tags/tag_table.asp Usado en el Agendamiento de Hora del menu admin al momento de mostrar la informacion del paciente.

### 3. Instrucciones de ejecución de Entrega
<!-- Indica las instrucciones para ejecutar la aplicación web adicionales al URL -->
1. Ejecutar el archivo config.sql para la creacion de funciones, vistas, tablas requeridas en el enunciado. `..\E4> psql -U postgres -d E4 -f .\config.sql`.
2. iniciar con el archivo `index.php`.

### 4. Observaciones adicionales
