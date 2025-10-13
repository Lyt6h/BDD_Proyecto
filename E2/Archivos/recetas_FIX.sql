select 
    'Receta Médica electrónica' as "Titulo",
	'Paciente: ' || p."Nombres" || ' ' || p."Apellidos" as "nombre_paciente",
    'RUN ' || p."RUN" as "RUT_paciente",
	'Diagnóstico: ' || a."Diagnostico" as "diagnostico",
	m."Medicamento" as "medicamento",
	m."Posologia" as "posologia",
	'Fecha: ' || a."fecha" as "fecha",
	'Dr/a: ' || pm."Nombres" || ' ' || pm."Apellidos" as "nombre_doctor",
	'RUN ' || pm."RUN" as "Rut_doctor"
from "Atencion" as a
join "medicamentos" as m ON a."ID" = m."IDAtencion"
join "Persona" as p on p."ID" = a."IDPaciente"
join "Persona" as pm on a."IDMedico" = pm."ID"
join "profesion" as prof on prof."ID" = pm."ID"
where a."ID" = 1 and m."Psicotropico" = FALSE and a."Efectuada" = True
group by a."fecha", a."Diagnostico", p."Nombres", p."Apellidos", p."RUN",m."Medicamento", m."Posologia", pm."Apellidos", pm."RUN", pm."Nombres"