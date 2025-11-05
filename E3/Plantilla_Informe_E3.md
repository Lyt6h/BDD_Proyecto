# Informe Entrega 3 - Bases de datos IIC2413

### Datos del Alumno
| **Apellidos**       | **Nombres**          | **Número de Alumno** |
|---------------------|----------------------|----------------------|
| Palomera Coccio | Matias Felipe    |24664235              |

### 1. Esquema Base de Datos 
<!-- Inserta aquí la imagen del esquema Usa el formato svg para evitar la perdida de calidad. Reemplaza "diagrama.svg" por la ruta a tu archivo -->

![Esquema BD](tu_diagrama.svg)

### 2. Data cleaning en PHP
<!-- Justifica CADA acción tomada sobre registros erroneos -->
| Error.                 | Archivo   | Acción.      | Solución                                  |
|:-----------------------|:----------|:-------------|:------------------------------------------|
| correo con tildes.     | Persona   | corrección   | cambio de letra con tilde por sin tilde   |
|                        |           |              |                                           |

Indicar los registros con correos con tildes

### 2. Data cleaning en DBMS
<!-- Justifica CADA acción tomada sobre registros erroneos -->
| Error.                 | Tabla     | Acción.      | Solución                                  |
|:-----------------------|:----------|:-------------|:------------------------------------------|
| atributo PK duplicado. | Persona   | Elimina.     |                                           |
|                        |           |              |                                           |
Indicar los registros con atributo PK duplicado

### 3. Instrucciones de ejecución de Entrega
Para ejecutar el main.php y carga.sql, se espera que los archivos estén ubicados en una estructura como esta:
```
.
│   
└── E3/
    ├── firmas/
    │   └── *files
    ├── planes/
    │   └── *.csv
    ├── *.csv
    ├── carga.sql
    ├── main.php
    └── README.md
```
Al ejecutar `php main.php` se creará la carpeta /outputs, en el caso de que se quiera VOLVER A EJECUTAR main.php, se tiene que borrar manuelmente la carpeta /outputs.  
Despues de la ejecución quedaría asi:
```
.
│   
└── E3/
    ├── firmas/
    │   └── *files
    ├── outputs/
    │   ├── dircsv/
    │   │   ├── fileERR.csv
    │   │   ├── fileLOG.txt
    │   │   └── fileOK.csv
    │   ├──...
    │   ...
    ├── planes/
    │   └── *.csv
    ├── *.csv
    ├── carga.sql
    ├── main.php
    └── README.md
```
Al eje


### 4. Observaciones adicionales