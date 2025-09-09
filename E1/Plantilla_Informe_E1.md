# Informe Entrega 1 - Bases de datos IIC2413

### Datos del Alumno
| **Apellidos**       | **Nombres**          | **Número de Alumno** |
|---------------------|----------------------|----------------------|
| Palomera Coccio     | Matias Felipe        |24664235              |

### 1. Modelo Entidad-Relación (E/R)
<!-- Inserta aquí tu diagrama ER. Usa el formato svg para evitar la perdida de calidad. Reemplaza "diagrama.svg" por la ruta a tu archivo -->

![Diagrama E/R](tu_diagrama.svg)

### 2. Entidades Débiles
<!-- Justifica CADA entidad débil identificada  -->
#### 2.1 Receta
Se identifico **Receta** como entidad débil porque se nesecita que primero exista una consulta que genere una receta para el paciente.
#### 2.2 Ordenes
Se identifico **Ordenes** como entidad débil porque, al igual que una receta, se necesita que se lleve a cabo una consulta  primero.

### 3. Llaves Primarias  y Compuestas
<!-- Justifica TODAS las llaves: primaria simple y primaria compuesta -->
#### 3.1 Persona
La llave primaria de __Persona__ es  __Rut__ porque existe un rut unico por cada persona.
#### 3.2 Instituciones previsionales de salud
La llave primaria de __Instituciones previsionales de salud__ es  __Codigo__ porque tienen un codigo ministeriasl unico. Tambien considere el Rut, pero en __Personas__ se usa el codigo para enlazar entidades, asi que creo que es mas completo.
#### 3.3 Maestro farmacia
La llave primaria de __Maestro farmacia__ es  __Codigo generico__ porque es el codigo unico que se puede rescatar de la entidad. 
#### 3.4 Arancel FONASA
La llave primaria de __Arancel FONASA__ es  __Codigo__ porque es el codigo FONASA unico en la entidad.
#### 3.5 Receta
La llave compuesta de __Receta__ es (____, ____) porque depende de consulta con numero unico por consulta.
#### 3.6 Ordenes
La llave compuesta de __Ordenes__ es (____, ____) porque al igual que __Receta__ depende de consulta con numero unico por consulta.
#### 3.7 Plan Isapre
La llave compuesta de __Plan Isapre__ es (____, ____) porque depende de ISAPRE con grupo de arancel
#### 3.8 Afiliacion
La llave compuesta de __Afiliacion__ es (____, ____) porque depende de __Persona__ con las __Instituciones previcionales de salud__.


### 4. Relaciones
<!-- Justifica TODAS las relaciones de tu modelo -->
#### 4.1 Relacion_A
Relaciona la __Entidad_A__ y la __Entidad_B__, porque ...

### 5. Cardinalidades
<!-- Explica la cardinalidad en CADA relación del modelo -->
#### 5.1 Entidad_A - Entidad_B (1 -- 0 a n)
- Una instancia de **Entidad_A** puede estar asociada con cero o mas instancias de **Entidad_B**.
- Cada instancia de **Entidad_B** se relaciona con exactamente una instancia de **Entidad_A**.

### 6. Jerarquías
<!-- Identifica y justifica TODAS las jerarquías -->
#### 6.1 Entidad_Padre - Entidad_Hija1 - Entidad_Hija2
Se modelo una jerarquía donde **Entidad_Padre** es la entidad padre y **Entidad_Hija1** y **Entidad_Hija2** heredan de ella, porque...

### 7. Esquema Relacional
<!-- Construye el esquema relacional a partir de tu Modelo E/R -->

**Entidad_A**( <u>atributo_1</u>: INT, atributo_2: VARCHAR, ... )  
**Entidad_B**( <u>atributo_1</u>: INT, <u>atributo_2</u>: INT, atributo_3: DATE, … )
...

### 8. Consistencia y Normalización en BCNF
<!-- Justifica la consistencia del esquema y su cumplimiento de BCNF -->

- **Consistencia:** ...
    
- **Normalización en BCNF**: ...