<?php
//Funcion para escribir logs
function write_log($filename, $message) {
    file_put_contents($filename, $message . PHP_EOL, FILE_APPEND);
}

//Funcion para cambiar acentos o caracteres especiales de tipo √[x]
function text_replace($text){
    $mapa_errores = [
        '√°' => 'á',
        '√©' => 'é',
        '√≠' => 'í',
        '√≥' => 'ó',
        '√∫' => 'ú',
        '√±' => 'ñ',
        '√ç'=> 'Í',
        '√Å'=> 'Á',
        '√ì'=> 'Ó',
    ];
    $corrected_text = str_replace(array_keys($mapa_errores), array_values($mapa_errores), $text);

    return $corrected_text;
}

function end_handle($is_ok, $data, $ok_handle, $log_message, $log_file, $original_data_str, $err_handle, $basename) {
    if ($is_ok) {
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
        $output_row = $original_data_str . "\n";
        fputs($err_handle, $output_row);
        write_log($log_file, $log_message . "Acción: DESCARTADO y CARGADO en {$basename}ERR.csv");
    }
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
        if (!isset($data[1]) || trim($data[1]) === '') {
            $log_message .= "RUN Paciente faltante: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
        } elseif (isset($data[1]) && !preg_match('/^[1-9][0-9]{5}-[0-9Kk]$/', $data[1])) {
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
            $log_message .= "Nombre faltante: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
        } else {
            $direccion_value = trim($data[2]);
            $length = strlen($direccion_value);
            
            if ($length > 30) {
                $log_message .= "Nombre excede 30 caracteres ({$length}): Se cambia a NULL. ";
                $data[2] = '';
            } else {
                $data[2] = $direccion_value;
            }
        }

        //Validar Apellido
        if (!isset($data[3]) || trim($data[3]) === '') {
            $log_message .= "Apellido faltante: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
        } else {
            $direccion_value = trim($data[3]);
            $length = strlen($direccion_value);
            
            if ($length > 30) {
                $log_message .= "Nombre excede 30 caracteres ({$length}): Se cambia a NULL. ";
                $data[3] = '';
            } else {
                $data[3] = $direccion_value;
            }
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
        end_handle($is_ok, $data, $ok_handle, $log_message, $log_file, $original_data_str, $err_handle, $basename);
    }
    fclose($ok_handle);
    fclose($err_handle);
    fclose($handle);
}

function orden_handle($handle, $basename, $log_file, $err_file, $ok_file) {
    
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');

    $header = fgetcsv($handle,0,';','"', '\\');
    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);

    $line_count = 1;

    //procesar linea por linea
    while (($data = fgetcsv($handle,0,';','"', '\\')) !== FALSE) {
        $line_count++;
        $original_data_str = implode(';', $data);
        $is_ok = true;
        $log_message = "Línea $line_count, IDAtencion: " . ($data[0] ?? 'N/A') . " - ";

        //Validar ID atencion
        if (!isset($data[0]) || trim($data[0]) === '') {
            $log_message .= "ID faltante: Se registra como ERROR. "; // al ser PK no puede ser NULL, a pesar que el
            $is_ok = false;                                                // enunciado no lo especifica
        } else {
            $id_value = trim($data[0]);

            if (!ctype_digit($id_value) ||$id_value < 1) {
                $log_message .= "ID inválido ('{$id_value}'): No es un entero positivo. Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[0] = $id_value;
            }
        }

        //Validar ID arancel
        if (!isset($data[1]) || trim($data[1]) === '') {
            $log_message .= "ID faltante: Se registra como ERROR. "; // al ser PK no puede ser NULL, a pesar que el
            $is_ok = false;                                                // enunciado no lo especifica
        } else {
            $id_value = trim($data[1]);

            if (!ctype_digit($id_value) ||$id_value < 1) {
                $log_message .= "ID inválido ('{$id_value}'): No es un entero positivo. Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[1] = $id_value;
            }
        }

        //Validar Consulta
        if (isset($data[2]) && trim($data[2]) !== '') {
            $consulta_value = trim($data[2]);

            $corrected_value = text_replace($consulta_value); // limpiamos los caracteres

            if ($corrected_value !== $consulta_value) {
                $log_message .= "Consulta corregida: Se corrigió acentos/caracteres especiales. ";
                $consulta_value = $corrected_value;
            }

            $length = strlen($consulta_value); 

            if ($length > 100) {
                $log_message .= "Consulta excede 100 caracteres ({$length}): Se cambia a NULL. ";
                $data[2] = '';

            } else {
                $data[2] = $consulta_value; 
            }
        } 

        end_handle($is_ok, $data, $ok_handle, $log_message, $log_file, $original_data_str, $err_handle, $basename);
    }
    fclose($ok_handle);
    fclose($err_handle);
    fclose($handle);
}

function medicamento_handle($handle, $basename, $log_file, $err_file, $ok_file) {
        
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');
    $log_handle = fopen($log_file, 'a');

    $header = fgetcsv($handle,0,';','"', '\\');
    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);

    $line_count = 1;

    //procesar linea por linea
    while (($data = fgetcsv($handle,0,';','"', '\\')) !== FALSE) {
        $line_count++;
        $original_data_str = implode(';', $data);
        $is_ok = true;
        $log_message = "Línea $line_count, IDAtencion: " . ($data[0] ?? 'N/A') . " - ";

        //Validar nombre_medicamento
        if (isset($data[1]) && trim($data[1]) !== '') {
            $direccion_value = trim($data[1]);
            $length = strlen($direccion_value); 

            if ($length > 100) {
                $log_message .= "Medicamento excede 100 caracteres ({$length}): Se cambia a NULL. ";
                $data[1] = '';
            } else {
                $data[1] = $direccion_value; 
            }
        }

        //Validar posologia
        if (isset($data[2]) && trim($data[2]) !== '') {
            $direccion_value = trim($data[2]);
            $length = strlen($direccion_value); 

            if ($length > 100) {
                $log_message .= "Posología excede 100 caracteres ({$length}): Se cambia a NULL. ";
                $data[2] = '';
            } else {
                $data[2] = $direccion_value; 
            }
        }
        
        //validar Psicotropico
        $booleans = ['true','false'];
        if (isset($data[3]) && trim($data[3]) !== '') {                                         // cuando no es bool lo tira a err, ya que no me quiero
            $value = strtolower(trim($data[3]));                                        // arriesgar a recetar un medicamento sin toda su informacion

            if (!in_array($value, $booleans)) {
                $log_message .= "Psicotrópico inválido ('{$data[3]}'): Debe ser booleano. Se registra como ERROR. ";
                $is_ok = false;
            }
        }

        end_handle($is_ok, $data, $ok_handle, $log_message, $log_file, $original_data_str, $err_handle, $basename);
    }
    fclose($ok_handle);
    fclose($err_handle);
    fclose($handle);
    fclose($log_handle);
}

function plan_handle($handle, $basename, $log_file, $err_file, $ok_file) {
        
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');

    $header = fgetcsv($handle,0,';','"', '\\');
    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);

    $line_count = 1;

    //Procesar linea por linea
    while (($data = fgetcsv($handle,0,';','"', '\\')) !== FALSE) { 
        $line_count++;  
        $original_data_str = implode(';', $data);           
        $is_ok = true;                               
        $log_message = "Línea $line_count, ID: " . ($data[0] ?? 'N/A') . " - ";

        //Validar Bonificacion
        if (!isset($data[0]) || trim($data[0]) === '') {
            $log_message .= "Bonificación faltante: Se asume valor 0. "; 
            $data[0] = 0; // NULL se trata como 0
        } else {
            $bonificacion_value = trim($data[0]);
            if (!ctype_digit($bonificacion_value)) {
                $log_message .= "Bonificación inválida ('{$bonificacion_value}'): No es un entero. Se cambia a 0. ";
                $data[0] = 0;
            } else {
                $int_bonificacion = (int)$bonificacion_value;
                if ($int_bonificacion < 0 || $int_bonificacion > 100) {
                    $log_message .= "Bonificación fuera de rango ('{$int_bonificacion}'): Debe ser entre 0 y 100. Se cambia a 0. ";
                    $is_ok = false;
                } else {
                    $data[0] = $int_bonificacion; 
                }
            }
        }

        //Validar Grupo
        if (isset($data[1]) && trim($data[1]) !== '') {
            $grupo_value = trim($data[1]);
            $length = strlen($grupo_value); 

            if ($length > 100) {
                $log_message .= "Grupo excede 100 caracteres ({$length}): Se cambia a NULL. ";
                $data[1] = '';
            } else {
                $data[1] = $grupo_value; 
            }
        } 

        //Resultado del procesamiento de la tupla
        end_handle($is_ok, $data, $ok_handle, $log_message, $log_file, $original_data_str, $err_handle, $basename);
    }
    fclose($ok_handle);
    fclose($err_handle);
    fclose($handle);
}

function atencion_handle($handle, $basename, $log_file, $err_file, $ok_file) {
        
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');

    $header = fgetcsv($handle,0,';','"', '\\');

    unset($header[1]);
    $header = array_values($header); // Reindexar el aray

    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);

    $line_count = 1;

    //procesar linea por linea
    while (($data = fgetcsv($handle,0,';','"', '\\')) !== FALSE) {
        $line_count++;
        $original_data_str = implode(';', $data);
        $is_ok = true;
        $log_message = "Línea $line_count, IDAtencion: " . ($data[0] ?? 'N/A') . " - ";

        //validar ID
        if (!isset($data[0]) || trim($data[0]) === '') {
            $log_message .= "ID faltante: Se registra como ERROR. "; // al ser PK no puede ser NULL, a pesar que el
            $is_ok = false;                                                // enunciado no lo especifica
        } else {
            $id_value = trim($data[0]);

            if (!ctype_digit($id_value) ||$id_value < 1) {
                $log_message .= "ID inválido ('{$id_value}'): No es un entero positivo. Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[0] = $id_value;
            }
        }

        //Validar Run Paciente
        if (!isset($data[2]) || trim($data[2]) === '') {
            $log_message .= "RUN Paciente faltante: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
        } elseif (isset($data[2]) && !preg_match('/^[1-9][0-9]{5}-[0-9Kk]$/', $data[2])) {
            $log_message .= "RUN mal formado ('{$data[2]}'): Se registra como ERROR.";
            $is_ok = false;
        }
        
        //Validar Run Medico
        if (!isset($data[3]) || trim($data[3]) === '') {
            $log_message .= "RUN Paciente faltante: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
        } elseif (isset($data[3]) && !preg_match('/^[1-9][0-9]{5}-[0-9Kk]$/', $data[3])) {
            $log_message .= "RUN mal formado ('{$data[3]}'): Se registra como ERROR.";
            $is_ok = false;
        }

        //Validar consulta
        if (isset($data[4]) && trim($data[4]) !== '') {
            $consulta_value = trim($data[4]);

            $corrected_value = text_replace($consulta_value); // limpiamos los caracteres

            if ($corrected_value !== $consulta_value) {
                $log_message .= "Consulta corregida: Se corrigió acentos/caracteres especiales. ";
                $consulta_value = $corrected_value;
            }

            $length = strlen($consulta_value); 

            if ($length > 100) {
                $log_message .= "Consulta excede 100 caracteres ({$length}): Se cambia a NULL. ";
                $data[4] = '';

            } else {
                $data[4] = $consulta_value; 
            }
        }

        //Validar Efectuada
        $booleans = ['true','false'];
        if (isset($data[5]) && trim($data[5]) !== '') {        // cuando no es bool lo tira a err, ya que no me quiero
            $value = strtolower(trim($data[5]));       // arriesgar a recetar un medicamento sin toda su informacion

            if (!in_array($value, $booleans)) {
                $log_message .= "Psicotrópico inválido ('{$data[5]}'): Debe ser booleano. Se registra como ERROR. ";
                $is_ok = false;
            }
        }

        // no uso la funcion end_handle() ya que este es diferente
        if ($is_ok) {
            unset($data[1]);
            $data = array_values($data); // Reindexar el aray
            $output_row = implode(';', $data) . "\n";
            fputs($ok_handle, $output_row);
            if ((strpos($log_message, 'corregido:') !== false || 
            strpos($log_message, 'reemplaza por') !== false || 
            strpos($log_message, 'cambia a NULL') !== false ||
            strpos($log_message, 'normaliza a') !== false || 
            strpos($log_message, 'corregida:') !== false
            )) {
                    write_log($log_file, $log_message . "Acción: CORREGIDO y CARGADO en {$basename}OK.csv");
            }
        } else {
            unset($data[1]);
            $data = array_values($data);
            $output_row = implode(';', $data) . "\n";
            fputs($err_handle, $output_row);
            write_log($log_file, $log_message . "Acción: DESCARTADO y CARGADO en {$basename}ERR.csv");
        }                                        
    }
    fclose($ok_handle);
    fclose($err_handle);
    fclose($handle);
}

function arancel_fonasa_handle($handle, $basename, $log_file, $err_file, $ok_file) {
    
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');

    $header = fgetcsv($handle,0,';','"', '\\');
    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);

    $line_count = 1;

    //Procesar linea por linea
    while (($data = fgetcsv($handle,0,';','"', '\\')) !== FALSE) { 
        $line_count++;  
        $original_data_str = implode(';', $data);           
        $is_ok = true;                               
        $log_message = "Línea $line_count, ID: " . ($data[0] ?? 'N/A') . " - ";

        //Validar CodF
        if (!isset($data[0]) || trim($data[0]) === '') {
            $log_message .= "Código Fonasa faltante: Se registra como ERROR. "; 
            $is_ok = false;                                                
        } else {
            $id_value = trim($data[0]);

            if (!ctype_digit($id_value)) {
                $log_message .= "Código Fonasa ('{$id_value}'): No es un entero. Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[0] = $id_value;
            }
        }

        //Validar CodA
        if (!isset($data[1]) || trim($data[1]) === '') {
            $id_value = trim($data[1]);

            if (!ctype_digit($id_value)) {
                if (!$id_value == '') {
                    $log_message .= "Código Adicional ('{$id_value}'): No es un entero. Se registra como ERROR. ";
                    $is_ok = false;
                } else {
                    $data[1] = $id_value;
                }   
            }
        }

        //Validar atencion
        if (isset($data[2]) && trim($data[2]) !== '') {
            $consulta_value = trim($data[2]);
            $length = strlen($consulta_value); 

            if ($length > 100) {
                $log_message .= "Consulta/Atencion excede 100 caracteres ({$length}): Se cambia a NULL. ";
                $data[2] = '';

            } else {
                $data[2] = $consulta_value; 
            }
        }

        //Validar valor
        if (isset($data[3]) && trim($data[3]) !== '') {
            $valor_value = trim($data[3]);
            $cleaned_valor = str_replace('.', '', $valor_value);
            if (!ctype_digit($cleaned_valor) || $cleaned_valor <= 0) {
                $log_message .= "Valor inválido ('{$cleaned_valor}'): Debe ser un entero positivo. Se registra como NULL. ";
                $data[3] = ''; 
            } else {
                $data[3] = $valor_value;
            }
        } 

        //Validar grupo
        if (isset($data[4]) && trim($data[4]) !== '') {
            $grupo_value = trim($data[4]);
            $length = strlen($grupo_value); 

            if ($length > 30) {
                $log_message .= "Grupo excede 30 caracteres ({$length}): Se cambia a NULL. ";
                $data[4] = '';
                
            } else {
                $data[4] = $grupo_value; 
            }
        } 
        else {
            $data[4] = '';
        }

        //Validar tipo
        if (isset($data[5]) && trim($data[5]) !== '') {
            $grupo_value = trim($data[5]);
            $length = strlen($grupo_value); 

            if ($length > 30) {
                $log_message .= "Grupo excede 30 caracteres ({$length}): Se cambia a NULL. ";
                $data[5] = '';
                
            } else {
                $data[5] = $grupo_value; 
            }
        } 
        else {
            $data[5] = '';
        }

        //Resultado del procesamiento de la tupla
        end_handle($is_ok, $data, $ok_handle, $log_message, $log_file, $original_data_str, $err_handle, $basename);
    }
    fclose($ok_handle);
    fclose($err_handle);
    fclose($handle);
}


function i_salud_handle($handle, $basename, $log_file, $err_file, $ok_file) {
    
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');

    $header = fgetcsv($handle,0,';','"', '\\');
    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);

    $line_count = 1;

    //Procesar linea por linea
    while (($data = fgetcsv($handle,0,';','"', '\\')) !== FALSE) { 
        $line_count++;  
        $original_data_str = implode(';', $data);           
        $is_ok = true;                               
        $log_message = "Línea $line_count, ID: " . ($data[0] ?? 'N/A') . " - ";

        //Validar Codigo
        if (!isset($data[0]) || trim($data[0]) === '') {
            $log_message .= "Código faltante: Se registra como ERROR. "; 
            $is_ok = false;                                                
        } else {
            $id_value = trim($data[0]);

            if (!ctype_digit($id_value)) {
                $log_message .= "Código ('{$id_value}'): No es un entero. Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[0] = $id_value;
            }
        }

        //Validar nombre
        if (isset($data[1]) && trim($data[1]) !== '') {
            $direccion_value = trim($data[1]);
            $length = strlen($direccion_value); 

            if ($length > 30) {
                $log_message .= "Nombre excede 30 caracteres ({$length}): Se cambia a NULL. ";
                $data[1] = '';
            } else {
                $data[1] = $direccion_value; 
            }
        }

        //Validar tipo
        $valid_tipos = ['abierta', 'cerrada'];
        if (isset($data[2]) && trim($data[2]) !== '') {
            $tipo_value = trim($data[2]);
            $lower_tipo_value = strtolower($tipo_value);

            if (!in_array($lower_tipo_value, $valid_tipos)) {
                $log_message .= "Tipo de ISAPRE inválido ('{$tipo_value}'): Debe ser 'abierta' o 'cerrada'. Se cambia a NULL. ";
                $data[2] = '';
            } 
        }

        //Validar rut
        if (!isset($data[3]) || trim($data[3]) === '') {
            $log_message .= "RUT faltante: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
        } else {
            $rut_value = trim($data[3]);

            $rut_value = str_replace(
                ["–", "—", "‒", "−", "-", "﹘", "﹣", "－", "‑"], // para quien lea esto:
                "-",                                            // he gastado demasiado tiempo en esto
                $rut_value                                      // me quiero ir a dormir
            );

            if (!preg_match('/^[6-9][0-9]\.[0-9]{3}\.[0-9]{3}-[0-9Kk]$/', $rut_value)) {
                $log_message .= "RUT inválido ('{$rut_value}'): No cumple el formato. Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[3] = $rut_value;
            }
        }

        //Validar enlace
        if (isset($data[4]) && trim($data[4]) !== '') {
            $enlace_value = trim($data[4]);

            if (!preg_match('/^https?:\/\//i', $enlace_value)) {
                $data[4] = 'https://' . $enlace_value;

            } else {
                $data[4] = $enlace_value;
            }
        }

        //Resultado del procesamiento de la tupla
        end_handle($is_ok, $data, $ok_handle, $log_message, $log_file, $original_data_str, $err_handle, $basename);
    }
    fclose($ok_handle);
    fclose($err_handle);
    fclose($handle);
}

function aranceldcc_handle($handle, $basename, $log_file, $err_file, $ok_file) {
    
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');

    $header = fgetcsv($handle,0,';','"', '\\');
    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);

    $line_count = 1;

    //Procesar linea por linea
    while (($data = fgetcsv($handle,0,';','"', '\\')) !== FALSE) { 
        $line_count++;  
        $original_data_str = implode(';', $data);           
        $is_ok = true;                               
        $log_message = "Línea $line_count, ID: " . ($data[0] ?? 'N/A') . " - ";

        //Validar Codigo
        if (!isset($data[0]) || trim($data[0]) === '') {
            $log_message .= "Código faltante: Se registra como ERROR. "; 
            $is_ok = false;                                                
        } else {
            $cod_value = trim($data[0]);

            if (!ctype_digit($cod_value)) {
                $log_message .= "Código ('{$cod_value}'): No es un entero. Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[0] = $cod_value;
            }
        }

        //Validar codFonasa
        if (!isset($data[1]) || trim($data[1]) === '') {
            $cod_value = trim($data[1]);

            if (!ctype_digit($cod_value)) {
                $log_message .= "Código Fonasa ('{$cod_value}'): No es un entero. Se registra como ERROR. ";
            } else {
                $data[1] = $cod_value;
            }
        }

        //Validar atencion
        if (isset($data[2]) && trim($data[2]) !== '') {
            $consulta_value = trim($data[2]);
            $length = strlen($consulta_value); 

            if ($length > 100) {
                $log_message .= "Consulta/Atencion excede 100 caracteres ({$length}): Se cambia a NULL. ";
                $data[2] = '';

            } else {
                $data[2] = $consulta_value; 
            }
        }

        //Validar valor
        if (isset($data[2]) && trim($data[2]) !== '') {
            $id_value = trim($data[3]);
            if (!ctype_digit($id_value)) {
                $log_message .= "Valor ('{$id_value}'): No es un entero. Se cambia a NULL. ";
                $data[3] = '';
            } else {
                $data[3] = $id_value;
            }
        }

        //Resultado del procesamiento de la tupla
        end_handle($is_ok, $data, $ok_handle, $log_message, $log_file, $original_data_str, $err_handle, $basename);
    }
    fclose($ok_handle);
    fclose($err_handle);
    fclose($handle);
}

function farmacia_handle($handle, $basename, $log_file, $err_file, $ok_file) {
    
    $ok_handle = fopen($ok_file, 'a');
    $err_handle = fopen($err_file, 'a');

    $header = fgetcsv($handle,0,';','"', '\\');
    $row_csv = implode(';', $header) . "\n";
    fputs($ok_handle, $row_csv);
    fputs($err_handle, $row_csv);

    $line_count = 1;

    //Procesar linea por linea
    while (($data = fgetcsv($handle,0,';','"', '\\')) !== FALSE) { 
        $line_count++;  
        $original_data_str = implode(';', $data);           
        $is_ok = true;                               
        $log_message = "Línea $line_count, ID: " . ($data[0] ?? 'N/A') . " - ";

        //Validar codMinsal
        if (!isset($data[0]) || trim($data[0]) === '') {
            $log_message .= "Código Minsal faltante: Se registra como ERROR. "; 
            $is_ok = false;                                                
        } else {
            $cod_value = trim($data[0]);
            if (!ctype_digit($cod_value)) {
                $log_message .= "Código Minsal ('{$cod_value}'): No es un entero. Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[0] = $cod_value;
            }
        }

        //Validar nombre
        if (!isset($data[1]) || trim($data[1]) === '') {
            $log_message .= "Nombre faltante: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
        } else {
            $name_value = trim($data[1]);
            $length = strlen($name_value);
            
            if ($length > 100) {
                $log_message .= "Nombre excede 100 caracteres ({$length}): Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[1] = $name_value;
            }
        }

        //Validar descripcion
        if (!isset($data[2]) || trim($data[2]) === '') {
            $log_message .= "Descripción faltante: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
        } else {
            $desc_value = trim($data[2]);
            $length = strlen($desc_value);
            
            if ($length > 256) {
                $log_message .= "Descripción excede 256 caracteres ({$length}): Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[2] = $desc_value;
            }
        }

        //Validar tipo
        if (isset($data[3]) && $data[3] !== '') {
            $tipo_value = trim($data[3]);
            $lower_tipo_value = strtolower($tipo_value);
            $valid_tipos = ['alimentos', 'equipamiento','fármacos','insumos','psicotrópicos','refrigerados','sueros'];
            
            if (!in_array($lower_tipo_value, $valid_tipos)) {
                $log_message .= "Tipo inválido ('{$tipo_value}'): Se cambia a NULL. ";
                $data[3] = '';
            } else  {
                $data[3] = $tipo_value;
            }
        }

        //Validar CodONU
        if (!isset($data[4]) || trim($data[4]) === '') {
            $log_message .= "Código ONU faltante: Se registra como NULL. ";
            $data[4]    = '';                                          
        } else {
            $cod_value = trim($data[4]);
            if (!ctype_digit($cod_value)) {
                $log_message .= "Código Minsal ('{$cod_value}'): No es un entero. Se registra como NULL. ";
                $data[4]    = '';   
            } else {
                $data[4] = $cod_value;
            }
        }

        //Validar clasONU
        $cod_onu_is_null = (!isset($data[4]) || trim($data[4]) === ''); // Revisa si CodOnu es NULL

        if (isset($data[5]) && trim($data[5]) !== '') {
            $clas_value = trim($data[5]);
        
            if (strlen($clas_value) > 30) {
                $log_message .= "Clasificación ONU ('{$clas_value}'): Excede los 30 caracteres. Se recorta. "; // ya que podria no ser NULL
                $data[5] = substr($clas_value, 0, 30);
            } else {
                $data[5] = $clas_value;
            }
        } elseif (!$cod_onu_is_null) {
            $log_message .= "Clasificación ONU faltante: No puede ser NULL si CodONU no es NULL. Se registra como ERROR. ";
            $is_ok = false;
        } elseif (isset($data[5])) {
            $data[5] = '';
        }

        //Validar clasificacion
        if (!isset($data[6]) || trim($data[6]) === '') {
            $log_message .= "Clasificación faltante: Violación de NOT NULL. Se registra como ERROR. ";
            $is_ok = false;
        } else {
            $name_value = trim($data[6]);
            $length = strlen($name_value);
            
            if ($length > 50) {
                $log_message .= "Clasificación excede 50 caracteres ({$length}): Se registra como ERROR. ";
                $is_ok = false;
            } else {
                $data[6] = $name_value;
            }
        }

        //Validar estado
        if (isset($data[7]) && $data[7] !== '') {
            $status_value = trim($data[7]);
            $lower_status_value = strtolower($status_value);
            $valid_status = ['activo','inactivo'];
            
            if (!in_array($lower_status_value, $valid_status)) {
                $log_message .= "Tipo inválido ('{$status_value}'): Se registra en ERROR. ";
                $is_ok = false;
            } else  {
                $data[7] = $status_value;
            }
        }

        //Validar esencial
        if (isset($data[8]) && $data[8] !== '') {
            $esse_value = trim($data[8]);
            $lower_esse_value = strtolower($esse_value);
            $valid_esse = ['0', '1'];
            
            if (!in_array($lower_esse_value, $valid_esse)) {
                $log_message .= "Tipo inválido ('{$esse_value}'): Se cambia a NULL. ";
                $data[8] = '';
            } else  {
                $data[8] = $esse_value;
            }
        }
        //Validar precio
        if (isset($data[9]) && trim($data[9]) !== '') {
            $valor_value = trim($data[9]);
            $cleaned_valor = str_replace('.', '', $valor_value);
            if (!ctype_digit($cleaned_valor) || $cleaned_valor <= 0) {
                $log_message .= "Valor inválido ('{$cleaned_valor}'): Debe ser un entero positivo. Se registra como NULL. ";
                $data[9] = ''; 
            } else {
                $data[9] = $valor_value;
            }
        } 

        //Resultado del procesamiento de la tupla
        end_handle($is_ok, $data, $ok_handle, $log_message, $log_file, $original_data_str, $err_handle, $basename);
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

    //Limpiar logs previos por si se ejecuta otra vez
    @unlink($ok_file);
    @unlink($err_file);
    @unlink($log_file);

    $handle = fopen($filepath, 'r');

    if ($basename == 'Arancel DCColita de rana') {
        aranceldcc_handle(
            $handle, 
            $basename, 
            $log_file, 
            $err_file, 
            $ok_file
        );
    } elseif ($basename == 'Arancel fonasa') {
        arancel_fonasa_handle(
            $handle, 
            $basename, 
            $log_file, 
            $err_file, 
            $ok_file
        );
    } elseif ($basename == 'Atencion') {
        atencion_handle(
            $handle, 
            $basename, 
            $log_file, 
            $err_file, 
            $ok_file
        ); //Atencion en el pdf no cuenta con fecha, pero en el csv si, le hare caso al pdf
    } elseif ($basename == 'Farmacia') {
        farmacia_handle(
            $handle, 
            $basename, 
            $log_file, 
            $err_file, 
            $ok_file
        );
    } elseif ($basename == 'Instituciones previsionales de salud') {
        i_salud_handle(
            $handle, 
            $basename, 
            $log_file, 
            $err_file, 
            $ok_file);
    } elseif ($basename == 'Medicamento') {
        medicamento_handle(
            $handle,
            $basename, 
            $log_file, 
            $err_file, 
            $ok_file);
    } elseif ($basename == 'Orden') {
        orden_handle(
            $handle,
            $basename,
            $log_file,
            $err_file,
            $ok_file
        );
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
//los CSV de la carpeta base estan listos, pero faltan los planes, que tan en una carpeta aparte

$planes_dir = __DIR__ . '/planes';
$output_planes_dir = $output_dir . '/planes';

//Crear carpeta outputs/planes
if (!file_exists($output_planes_dir)) {
    mkdir($output_planes_dir);
    echo "Carpeta 'outputs' creada.\n";
}

$new_csv_files = glob($planes_dir . '/*.csv');

foreach ($new_csv_files as $filepath) {
    $basename = basename($filepath,'.csv');

    echo "\nProcesando archivo: $basename.csv...\n";

    //Crea una carpeta para cada csv
    $csv_dir = "$output_planes_dir/$basename";
    if(!file_exists("$csv_dir")) {
        mkdir("$csv_dir");
    }

    $ok_plan_file = "$output_planes_dir/{$basename}/{$basename}OK.csv";
    $err_plan_file = "$output_planes_dir/{$basename}/{$basename}ERR.csv";
    $log_plan_file = "$output_planes_dir/{$basename}/{$basename}LOG.txt";

    //Limpiar logs previos por si se ejecuta otra vez
    @unlink($ok_plan_file);
    @unlink($err_plan_file);
    @unlink($log_plan_file);

    $handle = fopen($filepath, 'r');

    if ($basename == 'Colmena de avispas S.A.') {
        plan_handle($handle, $basename, $log_plan_file, $err_plan_file, $ok_plan_file);
    } elseif ($basename == 'Cruz de Malta S.A.') {
        plan_handle($handle, $basename, $log_plan_file, $err_plan_file, $ok_plan_file);
    } elseif ($basename == 'Cruz pal cielo Ltda.') {
        plan_handle($handle, $basename, $log_plan_file, $err_plan_file, $ok_plan_file);
    } elseif ($basename == 'Fundación e imperio') {
        plan_handle($handle, $basename, $log_plan_file, $err_plan_file, $ok_plan_file);
    } elseif ($basename == 'medibanc') {
        plan_handle($handle, $basename, $log_plan_file, $err_plan_file, $ok_plan_file);
    } elseif ($basename == 'Menos vida S.A.') {
        plan_handle($handle, $basename, $log_plan_file, $err_plan_file, $ok_plan_file);
    } elseif ($basename == 'salud') {
        plan_handle($handle, $basename, $log_plan_file, $err_plan_file, $ok_plan_file);
    } elseif ($basename == 'Vida uno S.A.') {
       plan_handle($handle, $basename, $log_plan_file, $err_plan_file, $ok_plan_file);
    }
}

echo "\nProceso completado. Resultados en /outputs.\n";
?>