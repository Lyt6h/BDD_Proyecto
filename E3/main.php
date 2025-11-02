<?php
//Funcion para escribir logs
function write_log($filename, $message) {
    file_put_contents($filename, $message . PHP_EOL, FILE_APPEND);
}

function persona_handle($handle, $basename, $log_file, $err_file, $ok_file){
    
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');

    $header = fgetcsv($handle,0,';');
    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);


    $line_count = 1;

    //Procesar linea por linea
    while (($data = fgetcsv($handle,0,';')) !== FALSE) {
        $line_count++;
        $original_data_str = implode(';', $data);
        $is_ok = true;
        $log_message = "Línea $line_count, ID: " . ($data[0] ?? 'N/A') . " - ";

        //Validar y corregir RUN
        if (isset($data[1]) && !preg_match('/^[1-9][0-9]{5,}-[0-9Kk]$/', $data[1])) {
            $log_message .= "RUN mal formado ('{$data[1]}'): Se registra como ERROR.";
            $is_ok = false;
        }

        //Validar y corregir correo
        if (isset($data[5]) && $data[5] !== '') {
            $email = $data[5];

            if (preg_match('/[^\x20-\x7E]/', $email)) {                                        
                $log_message .= "Correo con acentos/c.especiales ('{$email}'): Se cambia a NULL para la carga. ";//Anulacion de correos
                $data[5] = '';                                                                                   

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $corrected_email = str_replace('..', '.', $email);//caso de dobles puntos
                
                if (filter_var($corrected_email, FILTER_VALIDATE_EMAIL)) {
                    $log_message .= "Correo corregido (doble punto): '{$email}' -> '{$corrected_email}'. ";
                    $data[5] = $corrected_email;
                } else {
                    $log_message .= "Correo mal formado e irreparable ('{$email}'): Se registra como ERROR.";//caso de correo irreparable
                    $is_ok = false;
                }
            }
        }
        
        //Validar teléfono
        if (isset($data[6]) && $data[6] !== '') {
            if (!preg_match('/^[1-9][0-9]{8}$/', $data[6])) {
                 $log_message .= "Teléfono inválido ('{$data[6]}'): Se reemplaza por '100000000'. ";
                 $data[6] = '100000000'; 
            }
        }


        //Validar rol
        //Formatos esperados: {Staff médico, administrativo, paciente, NULL}
        if (isset($data[9]) && $data[9] !== '') {
            $roles_validos = ['Staff médico', 'administrativo', 'paciente', 'Staff médico,paciente', 'administrativo,paciente'];
            // Permitiendo combinaciones válidas como Staff médico,paciente
            $current_roles = array_map('trim', explode(',', $data[9]));
            $rol_valido = true;

            foreach($current_roles as $rol){
                if (!in_array($rol, ['Staff médico', 'administrativo', 'paciente'])){
                    $rol_valido = false;
                    break;
                }
            }
            //Si el rol es una combinación no válida o un valor simple incorrecto, lo marcamos como error
            if (!$rol_valido && !in_array($data[9], $roles_validos)) {
                $log_message .= "Rol inválido ('{$data[9]}'): Se registra como ERROR.";
                $is_ok = false;
            }
        }
        
        //Otras validaciones y correcciones (tipo, profesión, especialidad, InsSalPrev, etc.) irían aquí

        //Resultado del procesamiento de la tupla
        if ($is_ok) {
            //Escribir en el archivo OK
            $output_row = implode(';', $data) . "\n";
            fputs($ok_handle, $output_row);
            
            //Registrar en el LOG si se corrigio 
            if (strpos($log_message, 'corregido:') !== false || strpos($log_message, 'reemplaza por') !== false) {
                 write_log($log_file, $log_message . "Acción: CORREGIDO y CARGADO en {$basename}OK.csv");
            }

        } else {
            //Escribir en el archivo ERR
            $output_row = $original_data_str . "\n"; //Escribimos la fila original con error
            fputs($err_handle, $output_row);
            write_log($log_file, $log_message . "Acción: DESCARTADO y CARGADO en {$basename}ERR.csv");
        }
    }

    fclose($ok_handle);
    fclose($err_handle);
    fclose($handle);
}




$input_dir = __DIR__;
$output_dir = __DIR__ . '/outputs';

//Crear carpeta outputs
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

    if ($basename == 'Arancel DCColita de rana') {
        echo "Advertencia: Limpiador para '{$basename}' no implementado aún.\n";
    } elseif ($basename == 'Arancel fonasa') {
        echo "Advertencia: Limpiador para '{$basename}' no implementado aún.\n";
    } elseif ($basename == 'Atencion') {
        echo "Advertencia: Limpiador para '{$basename}' no implementado aún.\n";
    } elseif ($basename == 'Farmacia') {
        echo "Advertencia: Limpiador para '{$basename}' no implementado aún.\n";
    } elseif ($basename == 'Instituciones previsionales de salud') {
        echo "Advertencia: Limpiador para '{$basename}' no implementado aún.\n";
    } elseif ($basename == 'Medicamento') {
        echo "Advertencia: Limpiador para '{$basename}' no implementado aún.\n";
    } elseif ($basename == 'Orden') {
        echo "Advertencia: Limpiador para '{$basename}' no implementado aún.\n";
    } elseif ($basename == 'Persona') {
        persona_handle(
            $handle,
            $basename, 
            $log_file,
            $err_file, 
            $ok_file
        );
    }
}

echo "\nProceso completado. Resultados en /outputs.\n";
?>