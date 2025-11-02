<?php
//Funcion para escribir logs
function write_log($filename, $message) {
    file_put_contents($filename, $message . PHP_EOL, FILE_APPEND);
}


$input_dir = __DIR__;
$output_dir = __DIR__ . '/outputs';


//Crear carpeta outputs si no existe
if (!file_exists($output_dir)) {
    mkdir($output_dir);
    echo "Carpeta 'outputs' creada.\n";
}


//Buscar todos los archivos CSV en la carpeta base
$csv_files = glob("$input_dir/*.csv");


//Procesar cada archivo
foreach ($csv_files as $filepath) {
    $basename = basename($filepath, '.csv');
    echo "\nProcesando archivo: $basename.csv...\n";

    //Crea una carpeta para cada csv
    mkdir("$output_dir/$basename");

    $ok_file = "$output_dir/{$basename}/{$basename}OK.csv";
    $err_file = "$output_dir/{$basename}/{$basename}ERR.csv";
    $log_file = "$output_dir/{$basename}/{$basename}LOG.txt";

    //Limpiar logs previos
    @unlink($ok_file);
    @unlink($err_file);
    @unlink($log_file);

    $handle = fopen($filepath, 'r');

    ///// TEMP en caso de error/////////////////////
    if (!$handle) {/////////////////////////////////
        echo "No se pudo abrir $basename.csv\n";////
        continue;///////////////////////////////////
    }///////////////////////////////////////////////
    ////

    //Leer encabezado
    $header = fgetcsv($handle);
    $line_csv = implode(',', $header) . "\n";
    fputs(fopen($ok_file, 'w'), $line_csv);
    fputs(fopen($err_file, 'w'), $line_csv);

    
    //Procesar línea por línea
    while (($row = fgetcsv($handle)) !== false) {
        $original_row = $row;
        $valid = true;
        $messages = [];

        //Ejemplo de validaciones genéricas (ajustaremos según archivo):
        foreach ($row as $i => $value) {
            $row[$i] = trim($value);
        }

        // Detección del tipo de archivo para aplicar reglas específicas
        if (stripos($basename, 'persona') !== false) {
            // Validar RUN
            if (!preg_match('/^[1-9]\d{4,7}-[0-9Kk]$/', $row[1])) {
                $messages[] = "RUN inválido ({$row[1]}) → reemplazado por '10000000-0'";
                $row[1] = '10000000-0';
            }
            // Validar correo
            if (!filter_var($row[5], FILTER_VALIDATE_EMAIL)) {
                $messages[] = "Correo malformado ({$row[5]}) → corregido a 'no_valido@correo.cl'";
                $row[5] = 'no_valido@correo.cl';
            }
            // Validar teléfono
            if (!preg_match('/^[1-9]\d{8}$/', $row[6])) {
                $messages[] = "Teléfono inválido ({$row[6]}) → corregido a 100000000";
                $row[6] = '100000000';
            }
        }

        // Si hubo cambios
        if (!empty($messages)) {
            write_log($log_file, implode("; ", $messages));
        }

        // Validar campos críticos vacíos
    //    if (in_array("", $row)) {
    //        fputs(fopen($err_file, 'a'), $original_row);
    //        $err_count++;
    //    } else {
    //        fputs(fopen($ok_file, 'a'), $row);
    //        $ok_count++;
    //    }
    }

    fclose($handle);
}

echo "\nProceso completado. Resultados en /outputs.\n";
?>