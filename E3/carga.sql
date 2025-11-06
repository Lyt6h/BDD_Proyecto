DROP TABLE IF EXISTS institucion_previsional_salud CASCADE;
DROP TABLE IF EXISTS persona CASCADE;
DROP TABLE IF EXISTS arancel_fonasa CASCADE;
DROP TABLE IF EXISTS plan CASCADE;
DROP TABLE IF EXISTS farmacia CASCADE;
DROP TABLE IF EXISTS arancel_dccolita CASCADE;
DROP TABLE IF EXISTS atencion CASCADE;
DROP TABLE IF EXISTS medicamento CASCADE;
DROP TABLE IF EXISTS orden CASCADE;



CREATE TABLE institucion_previsional_salud (
    codigo INTEGER PRIMARY KEY NOT NULL,
    nombre VARCHAR(30) NOT NULL,
    tipo TEXT CHECK (tipo IN ('abierta', 'cerrada')),
    rut TEXT NOT NULL CHECK (rut ~ '^[6-9][0-9]\.[0-9]{3}\.[0-9]{3}-[0-9Kk]$'),
    enlace TEXT CHECK (enlace IS NULL OR enlace ~ '^https?://[A-Za-z0-9.-]+\.[A-Za-z]{2,}$')
);

CREATE TABLE persona (
    id INTEGER PRIMARY KEY,
    run VARCHAR(10) NOT NULL CHECK (run ~ '^[1-9][0-9]{5,}-[0-9Kk]$'),
    nombre VARCHAR(30) NOT NULL,
    apellido VARCHAR(30) NOT NULL,
    direccion VARCHAR(100),
    correo TEXT CHECK (correo IS NULL OR correo ~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    telefono TEXT CHECK (telefono IS NULL OR telefono ~ '^[1-9][0-9]{8}$'),
    tipo TEXT CHECK (tipo IS NULL OR tipo IN ('beneficiario', 'titular')),
    titular TEXT CHECK (titular IS NULL OR titular ~ '^[1-9][0-9]{5,}-[0-9Kk]$'),
    rol TEXT CHECK (rol IS NULL OR rol IN ('Staff médico', 'administrativo', 'paciente')),
    profesion TEXT CHECK (profesion IS NULL OR profesion IN ('tens', 'enfermero/a', 'kinesiólogo/a', 'medico(a)')),
    especialidad VARCHAR(30),
    firma VARCHAR(30),
    inssalprev VARCHAR(30) REFERENCES institucion_previsional_salud(nombre)
);

CREATE TABLE arancel_fonasa (
    codf INTEGER PRIMARY KEY,
    coda INTEGER,
    atencion VARCHAR(100),
    valor INTEGER,
    grupo VARCHAR(30),
    tipo VARCHAR(30)
);

CREATE TABLE plan (
    id INTEGER PRIMARY KEY,--SK
    bonificacion INTEGER CHECK (bonificacion BETWEEN 0 AND 100 ),
    grupo VARCHAR(100)
);

CREATE TABLE farmacia (
    cod INTEGER PRIMARY KEY NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(256) NOT NULL,
    tipo TEXT CHECK (tipo IN ('Fármacos', 'Insumos', 'Refrigerados', 'Psicotrópicos', 'Alimentos', 'Equipamiento', 'Sueros')),
    codonu INTEGER,
    clasonu VARCHAR(30),
    clasificacion VARCHAR(50) NOT NULL,
    estado TEXT CHECK (estado IN ('Activo', 'Inactivo')),
    esencial INTEGER CHECK (esencial IN (0, 1)),
    precio INTEGER
);

CREATE TABLE arancel_dccolita (
    codigo INTEGER PRIMARY KEY NOT NULL,
    codfonasa TEXT,
    atencion VARCHAR(100),
    valor INTEGER
);

CREATE TABLE atencion (
    id INTEGER PRIMARY KEY,
    runpaciente TEXT NOT NULL REFERENCES persona(run),
    runmedico TEXT NOT NULL REFERENCES persona(run),
    diagnostico VARCHAR(100),
    efectuada BOOLEAN DEFAULT FALSE
);

CREATE TABLE medicamento (
    id INTEGER PRIMARY KEY,
    idatencion INTEGER REFERENCES atencion(id),
    nombre VARCHAR(100) REFERENCES farmacia(cod),
    posologia VARCHAR(100),
    psicotropico BOOLEAN DEFAULT FALSE
);

CREATE TABLE orden (
    id INTEGER PRIMARY KEY NOT NULL, -- poner id como SK
    idatencion INTEGER REFERENCES atencion(id),
    idarancel INTEGER REFERENCES arancel_dccolita(codigo),
    consulta VARCHAR(100)
);

BEGIN;

\COPY institucion_previsional_salud FROM "./outputs/Instituciones previsionales de salud/Instituciones previsionales de saludOK.csv" DELIMITER ';' CSV HEADER;
\COPY persona FROM "./outputs/Persona/PersonaOK.csv" DELIMITER ';' CSV HEADER;
\COPY arancel_fonasa FROM "./outputs/Arancel fonasa/Arancel fonasaOK.csv" DELIMITER ';' CSV HEADER;

\COPY plan FROM "./outputs/planes/Colmena de avispas S.A/Colmena de avispas S.AOK.csv" DELIMITER ';' CSV HEADER;
\COPY plan FROM "./outputs/planes/Cruz de Malta S.A/Cruz de Malta S.AOK.csv" DELIMITER ';' CSV HEADER;
\COPY plan FROM "./outputs/planes/Cruz pal cielo Ltda/Cruz pal cielo LtdaOK.csv" DELIMITER ';' CSV HEADER;
\COPY plan FROM "./outputs/planes/Fundación e imperio/Fundación e imperioOK.csv" DELIMITER ';' CSV HEADER;
\COPY plan FROM "./outputs/planes/medibanc/medibancOK.csv" DELIMITER ';' CSV HEADER;
\COPY plan FROM "./outputs/planes/Menos vida S.A/Menos vida S.AOK.csv" DELIMITER ';' CSV HEADER;
\COPY plan FROM "./outputs/planes/salud/saludOK.csv" DELIMITER ';' CSV HEADER;
\COPY plan FROM "./outputs/planes/Vida uno S.A/Vida uno S.AOK.csv" DELIMITER ';' CSV HEADER;

\COPY farmacia FROM "./outputs/Farmacia/FarmaciaOK.csv" DELIMITER ';' CSV HEADER;
\COPY arancel_dccolita FROM "./outputs/Arancel DCColita de rana/Arancel DCColita de ranaOK.csv" DELIMITER ';' CSV HEADER;
\COPY atencion FROM "./outputs/Atencion/AtencionOK.csv" DELIMITER ';' CSV HEADER;
\COPY medicamento FROM "./outputs/Medicamento/MedicamentoOK.csv" DELIMITER ';' CSV HEADER;
\COPY orden FROM "./outputs/Orden/OrdenOK.csv" DELIMITER ';' CSV HEADER;

COMMIT;
