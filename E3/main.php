<?php
//Funcion para escribir logs
function write_log($filename, $message) {
    file_put_contents($filename, $message . PHP_EOL, FILE_APPEND);
}

function persona_handle($handle, $basename, $log_file, $err_file, $ok_file){
    
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');

    $header = fgetcsv($handle,0,';','"', '\\');
    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);


    $line_count = 1;

    //Procesar linea por linea
    while (($data = fgetcsv($handle,0,';','"', '\\')) !== FALSE) { // En esta linea y en la 12 se me presentaba un 
        $line_count++;  $line_csv = $line_count .'';                                                           // aviso en la terminal, faltaba ponerle
        $original_data_str = implode(';', $data);                                            // otros argumentos, pero no endiendo del todo
        $is_ok = true;                                                                                         // para que sirven.
        $log_message = "Línea $line_count, ID: " . ($data[0] ?? 'N/A') . " - ";

        //Validar y corregir RUN
        if (isset($data[1]) && !preg_match('/^[1-9][0-9]{5}-[0-9Kk]$/', $data[1])) {
            $log_message .= "RUN mal formado ('{$data[1]}'): Se registra como ERROR.";
            $is_ok = false;
        }

        //Validar y corregir correo
        if (isset($data[5]) && $data[5] !== '') {
            $email = $data[5];

            if (preg_match('/[^\x20-\x7E]/', $email)) {                                        
                $log_message .= "Correo con acentos/c.especiales ('{$email}'): Se cambia a NULL. ";// Anulacion de correos
                $data[5] = '';                                                                                   

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $corrected_email = str_replace('..', '.', $email);// caso de dobles puntos
                
                if (filter_var($corrected_email, FILTER_VALIDATE_EMAIL)) {
                    $log_message .= "Correo corregido (doble punto): '{$email}' -> '{$corrected_email}'. ";
                    $data[5] = $corrected_email;
                } else {
                    $log_message .= "Correo mal formado e irreparable ('{$email}'): Se registra como ERROR.";// caso de correo irreparable
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
        if (isset($data[9]) && $data[9] !== '') {
            $roles_validos = [
                'Staff médico',
                'administrativo', 
                'paciente', 
                'Staff médico,paciente', 
                'administrativo,paciente',
                'paciente,Staff médico', 
                'paciente,administrativo'
            ];
            $current_roles = array_map('trim', explode(',', $data[9]));
            $rol_valido = true;

            foreach($current_roles as $rol){
                if (!in_array($rol, ['Staff médico', 'administrativo', 'paciente'])){
                    $rol_valido = false;
                    break;
                }
            }
            if (!$rol_valido && !in_array($data[9], $roles_validos)) {
                $log_message .= "Rol inválido ('{$data[9]}'): Se cambia a NULL.";
                $data[9] = '';
            }
        }
        
        //Validar tipo
        if (isset($data[7]) && $data[7] !== '') {
            $tipo_value = trim($data[7]);
            $lower_tipo_value = strtolower($tipo_value);
            $valid_tipos = ['beneficiario', 'titular'];
            
            if (!in_array($lower_tipo_value, $valid_tipos)) {
                $log_message .= "Tipo inválido ('{$tipo_value}'): Se cambia a NULL. ";
                $data[7] = '';
            } elseif ($tipo_value !== $lower_tipo_value) {
                $log_message .= "Tipo mal escrito ('{$tipo_value}'): Se normaliza a '{$lower_tipo_value}'. ";
                $data[7] = $lower_tipo_value;
            }
        }

        //Validar titular
        if (isset($data[8]) && $data[8] !== '') {
            $titular_run = trim($data[8]);

            if (!preg_match('/^[1-9][0-9]{5}-[0-9Kk]$/i', $titular_run)) { 
                $log_message .= "Titular RUN mal escrito ('{$titular_run}'): Se cambia a NULL. ";
                $data[8] = ''; 
            }
        }         

        //Validar Nombre
        if (!isset($data[2]) || trim($data[2]) === '') {
            $log_message .= "Nombre faltante/vacío: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
            
        }

        //Validar Apellido
        if (!isset($data[3]) || trim($data[3]) === '') {
            $log_message .= "Apellido faltante/vacío: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false; 
            
        } 

        //Validar dirccion
        if (isset($data[4]) && trim($data[4]) !== '') {
            $direccion_value = trim($data[4]);
            $length = strlen($direccion_value); 

            if ($length > 100) {
                $log_message .= "Dirección excede 100 caracteres ({$length}): Se cambia a NULL. ";
                $data[4] = '';
                
            } else {

                $data[4] = $direccion_value; 
            }
        } 

        //Validar Profesion
        if (isset($data[10]) && trim($data[10]) !== '') {
            $valid_profesiones = [
            'tens', 
            'enfermero/a', 
            'kinesiólogo/a', 
            'médico/a'
            ];
            $profesion_value = trim($data[10]);
            $lower_profesion_value = strtolower($profesion_value);
            
            if (!in_array($lower_profesion_value, $valid_profesiones)) {
                $log_message .= "Profesión inválida ('{$profesion_value}'): Se cambia a NULL. ";
                $data[10] = ''; 
            } else {
                if ($profesion_value !== $lower_profesion_value) {
                    $log_message .= "Profesión corregida: '{$profesion_value}' -> '{$lower_profesion_value}'. ";// Normaliza a minusculas
                }
                $data[10] = $lower_profesion_value; 
            }
        }  

        //validar Especialidad
        $profesion_value = $data[10]; 

        if (isset($data[11]) && trim($data[11]) !== '') {
            $especialidad_value = trim($data[11]);
            $length = strlen($especialidad_value); 

            if ($profesion_value === 'medico') {
                if ($length > 30) {
                    $log_message .= "Especialidad excede 30 caracteres ({$length}): Se cambia a NULL. ";
                    $data[11] = '';
                } else {
                    $data[11] = $especialidad_value; 
                }
            } else {
                $log_message .= "Especialidad ('{$especialidad_value}') inválida para '{$profesion_value}': Se cambia a NULL. ";
                $data[11] = '';
            }
        } 

        //Validar fimra
        if (isset($data[12]) && trim($data[12]) !== '') {
            $firma_value = trim($data[12]);
            $length = strlen($firma_value);                                         // asumire que el path esta bien
                                                                                            // escrito en el csv, y que el archivo 
            if ($length > 30) {                                                             // existe en /firmas
                $log_message .= "Firma excede 30 caracteres ({$length}): Se cambia a NULL. ";
                $data[12] = ''; 
            }
        } 

        //Validar InsSalPrev
        if (isset($data[13]) && trim($data[13]) !== '') {
            $inssalprev_value = trim($data[13]);
            $length = strlen($inssalprev_value);

            if ($length > 30) {
                $log_message .= "InsSalPrev excede 30 caracteres ({$length}): Se cambia a NULL. ";
                $data[13] = '';
            } else {
                $data[13] = $inssalprev_value; 
            }
        } 

        //Resultado del procesamiento de la tupla
        if ($is_ok) {
            //Escribir en el archivo OK
            $output_row = implode(';', $data) . "\n";
            fputs($ok_handle, $output_row);
            
            //Registrar en el LOG si se corrigio 
            if (strpos($log_message, 'corregido:') !== false || 
            strpos($log_message, 'reemplaza por') !== false || 
            strpos($log_message, 'cambia a NULL') !== false ||
            strpos($log_message, 'normaliza a') !== false || 
            strpos($log_message, 'corregida:') !== false
            ) {
                write_log($log_file, $log_message . "Acción: CORREGIDO y CARGADO en {$basename}OK.csv");
            }
        } else {
            //Escribir en el archivo ERR
            $output_row = $original_data_str . "\n"; // Escribimos la fila original con error
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