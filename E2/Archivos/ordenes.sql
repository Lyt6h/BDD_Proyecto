select 
    'Orden de examen' as "Titulo",
	p."Nombres" || ' ' || p."Apellidos" as "nombre_paciente",
    p."RUN" as "RUT_paciente",
	pm."Nombres" || ' ' || pm."Apellidos" as "nombre_doctor",
	pm."RUN" as "Rut_doctor",
	a."fecha" as "fecha",
   	ar."ConsAtMedica" as "examen"
from "Atencion" as a
join "Orden" as o on a."ID" = o."IDAtencion"
join "Persona" as p on p."ID" = a."IDPaciente"
join "Persona" as pm on a."IDMedico" = pm."ID"
join "Arancel" as ar on o."IDArancel" = ar."ID"
where a."ID" = 1
