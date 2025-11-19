-- 1.a) Indices secundarios
CREATE INDEX RUN ON Persona(RUN);
CREATE INDEX RUT ON InstituciondeSalud(RUT);        -- Verificar si esto es correcto

-- 1.b) INdice primario
ALTER TABLE "Agenda" ADD CONSTRAINT PK_agenda PRYMARY KEY ("ID", "Fecha", "Hora");

-- 1.c) Transacciones
BEGIN;



COMMIT;

-- 1.d) SP generador



-- 1.e) Trigger de SP


-- 1.f) Vista "Ficha"


-- 1.g) Validacion
