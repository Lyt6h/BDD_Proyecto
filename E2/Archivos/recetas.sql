--- ejemplo de receta No Psicotropico
select 
    'Receta Médica electrónica' as "Titulo",
	p."Nombres" || ' ' || p."Apellidos" as "nombre_paciente",
    p."RUN" as "RUT_paciente",
	a."Diagnostico" as "diagnostico",
	m."Medicamento" as "medicamento",
	m."Posologia" as "posologia",
	a."fecha" as "fecha",
	pm."Nombres" || ' ' || pm."Apellidos" as "nombre_doctor",
	pm."RUN" as "Rut_doctor"
from "Atencion" as a
join "medicamentos" as m ON a."ID" = m."IDAtencion"
join "Persona" as p on p."ID" = a."IDPaciente"
join "Persona" as pm on a."IDMedico" = pm."ID"
join "profesion" as prof on prof."ID" = pm."ID"
where m."Psicotropico" = False and a."Efectuada" = True
group by a."fecha", a."Diagnostico", p."Nombres", p."Apellidos", p."RUN",m."Medicamento", m."Posologia", pm."Apellidos", pm."RUN", pm."Nombres", a."ID"
order by a."ID" limit 1;


--- ejemplo de receta con Psicotropicos
select 
    'Receta Médica electrónica Psicotrópicos' as "Titulo",
	p."Nombres" || ' ' || p."Apellidos" as "nombre_paciente",
    p."RUN" as "RUT_paciente",
	a."Diagnostico" as "diagnostico",
	m."Medicamento" as "medicamento",
	m."Posologia" as "posologia",
	a."fecha" as "fecha",
	pm."Nombres" || ' ' || pm."Apellidos" as "nombre_doctor",
	pm."RUN" as "Rut_doctor"
from "Atencion" as a
join "medicamentos" as m ON a."ID" = m."IDAtencion"
join "Persona" as p on p."ID" = a."IDPaciente"
join "Persona" as pm on a."IDMedico" = pm."ID"
join "profesion" as prof on prof."ID" = pm."ID"
where m."Psicotropico" = True and a."Efectuada" = True
group by a."fecha", a."Diagnostico", p."Nombres", p."Apellidos", p."RUN",m."Medicamento", m."Posologia", pm."Apellidos", pm."RUN", pm."Nombres", a."ID"
order by a."ID" limit 1
