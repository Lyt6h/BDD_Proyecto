-- 1.a) Indices secundarios
CREATE INDEX Persona_RUN ON "Persona"("RUN");
CREATE INDEX InstituciondeSalud_RUT ON "InstituciondeSalud"("RUT");        -- Verificar si esto es correcto

-- 1.b) INdice primario
ALTER TABLE "Agenda" ADD CONSTRAINT PK_agenda PRIMARY KEY ("ID", "Fecha", "Hora");

-- 1.c) Transacciones


-- 1.d) SP generador
CREATE OR REPLACE FUNCTION generar_archivos(atencion_id INT)
RETURNS void as 
$$
DECLARE
    datos RECORD;       -- almacenara los datos como nombres de paci. y med. y diagnostico
    med RECORD;     -- usados para 
    orden RECORD;   -- iterar recopilando info

    receta_text TEXT;      -- := Asignacion
    receta_psico_text TEXT;
    orden_text TEXT;
    bono_text TEXT;
BEGIN
	receta_text := '';      -- := Asignacion
    receta_psico_text := '';
    orden_text := '';
    bono_text := '';
	
    -- datos paciente, datos medico, diagnostico (info general al menos para las recetas)
    SELECT p."Nombres" || ' ' || p."Apellidos" as nombre_paciente, 
        p."RUN" as run_paciente,
        a."Diagnostico" as diagnostico,
        a."fecha" as fecha,
        'Dr/a. ' || m."Nombres" || ' ' || m."Apellidos" as nombre_medico, 
        m."RUN" as run_medico,
        prof."firma" as firma

    INTO datos
    FROM "Atencion" as a
    JOIN "Persona" as p ON a."IDPaciente" = p."ID"
    JOIN "Persona" as m ON a."IDMedico" = m."ID"
    JOIN "profesion" as prof ON prof."ID" = m."ID"
    WHERE a."ID" = atencion_id;



    -- Crear receta NO psico
    receta_text := receta_text || 'Receta Médica Electronica' || CHR(10) || CHR(10)     -- CHR(10) para el salto de linea
        || 'Paciente: ' || datos."nombre_paciente" || CHR(10)
        || 'RUN: ' || datos."run_paciente" || CHR(10) 
        || 'Diagnóstico: ' || datos."diagnostico" || CHR(10) || CHR(10);

        --ingresar medicamentos NO psico.
    FOR med IN (
        SELECT * 
        FROM medicamentos
        WHERE "IDAtencion" = atencion_id AND NOT "Psicotropico"
    )
    LOOP
        receta_text := receta_text || med."Medicamento" || ' - ' || med."Posologia" || CHR(10);     -- no sabia como ponerloexactamente com en la receta asi que ' - ' de separador nomas.
    END LOOP;

    -- terminar la receta
    receta_text := receta_text || 'Fecha: ' || datos."fecha" || CHR(10)
        || datos."nombre_medico" || CHR(10)
        || 'RUN: ' || datos."run_medico" || CHR(10)
        || datos."firma" || CHR(10); --(RECORDATORIO) USAR COALESCE() EN CASO DE TENER PROBLEMAS CON LOS NULOOOOOOOOOOOOS

    

    --crear receta psico.
    receta_psico_text := receta_psico_text || 'Receta Médica Electronica Psocptrópicos' || CHR(10) || CHR(10)     -- CHR(10) para el salto de linea
        || 'Paciente: ' || datos."nombre_paciente" || CHR(10)
        || 'RUN: ' || datos."run_paciente" || CHR(10) 
        || 'Diagnóstico: ' || datos."diagnostico" || CHR(10) || CHR(10);

        --ingresar medicamentos psico.
    FOR med IN (
        SELECT * 
        FROM medicamentos
        WHERE "IDAtencion" = atencion_id AND "Psicotropico"
    )
    LOOP
        receta_psico_text := receta_psico_text || med."Medicamento" || ' - ' || med."Posologia" || CHR(10);     -- no sabia como ponerloexactamente com en la receta asi que ' - ' de separador nomas.
    END LOOP;

    -- terminar la receta
    receta_psico_text := receta_psico_text || 'Fecha: ' || datos."fecha" || CHR(10)
        || datos."nombre_medico" || CHR(10)
        || 'RUN: ' || datos."run_medico" || CHR(10)
        || datos."firma" || CHR(10);



    -- Crear Orden 
    orden_text := orden_text || 'Orden de examen' || CHR(10) || CHR(10)
        || 'Paciente: ' || datos."nombre_paciente" || CHR(10) 
        || 'RUN: ' || datos."run_paciente" || CHR(10) 
        || 'Diagnóstico: ' || datos."diagnostico" || CHR(10);

    --ingresar ordenes 
    FOR orden IN (
        SELECT a."ConsAtMedica", a."Codigo", a."Codigo_a"
        FROM "Orden" as o
        JOIN "Arancel" as a ON o."IDArancel" = a."ID"
        WHERE o."IDAtencion" = atencion_id
    )
    LOOP
        orden_text := orden_text || orden."Codigo" || ' ' || orden."Codigo_a"::TEXT || ' ' || orden."ConsAtMedica" || CHR(10);
    END LOOP;

    -- terminar orden 
    orden_text :=  orden_text || 'Fecha: ' || datos."fecha" || CHR(10)
        || datos."nombre_medico" || CHR(10)
        || 'RUN: ' || datos."run_medico" || CHR(10)
        || datos."firma" || CHR(10);
    


    -- Crear bono
    bono_text := bono_text || 'Bono de Atención Médica' || CHR(10) || CHR(10)
        || 'Datos del Beneficiario: ' || datos."nombre_paciente" || CHR(10)
        || 'RUN: ' || datos."run_paciente" || CHR(10)
        || 'Datos del Prestador: ' || datos."nombre_medico" || CHR(10)
        || 'RUN: ' || datos."run_medico" || CHR(10) || CHR(10);

/**
# terminar el crear bonos
# falta la "tabla" qu aparece en el PDF
#
#
#
#
#
#
#
**/

    -- Crear o generar finalmente los archivos
/**
Si

(una opcion podria ser hacerlo con PHP? ( ͡° ͜ʖ ͡°) )
**/	
    
END;
$$ language plpgsql;

-- 1.e) Trigger de SP
    /** 
    Voy a crear una funcion intermedia para comprobar si la Atencion se realizó
    y rescatar el ID de la Atencion, ya que no supe hacerlo todo en un Trigger 
    **/
CREATE OR REPLACE FUNCTION inter_trgg()
RETURNS TRIGGER as $$
BEGIN
    IF NEW."Efectuada" = TRUE AND OLD."Efectuada" = FALSE THEN CALL generar_archivos(NEW."ID");
    END IF;
    RETURN NEW;
END;
$$ language plpgsql;

    -- ahora si el trigger como tal
CREATE TRIGGER arch_trgg
AFTER UPDATE ON "Atencion"
FOR EACH ROW 
EXECUTE FUNCTION inter_trgg();

-- 1.f) Vista "Ficha"
CREATE VIEW Ficha AS
SELECT 
    a."IDPaciente" as ID_paciente,
    a."fecha" as fecha,
    m."Nombres" || ' ' || m."Apellidos" as Nombre_medico,
    p."profesion" as Especialidad,
    a."Diagnostico" as diagnostico
FROM "Atencion" as a 
JOIN "Persona" as m ON m."ID" = a."IDMedico"
LEFT JOIN "profesion" as p ON p."ID" = m."ID"
ORDER BY a."fecha" DESC;

-- 1.g) Validacion
    -- DAMN!

