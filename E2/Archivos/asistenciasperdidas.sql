select 
	extract(year from "fecha") as "año",
	extract(month from "fecha") as "mes",
	count(*) AS "Total_inasistencias"
from "Atencion"
where "Efectuada" = False
group by "año", "mes"
order by "año", "mes";



select
	a."IDMedico",
	p."Nombres" || ' ' || p."Apellidos" as "Nombre",
	count(*) as "Total_inasistencias"
from "Atencion" as a
join "Persona" as p on a."IDMedico" = p."ID"
where a."Efectuada" = False
group by a."IDMedico", p."Nombres", p."Apellidos"
order by "Total_inasistencias" desc limit 5;



select 
	a."IDPaciente",
	p."Nombres" || ' ' || p."Apellidos" as "Nombre",
	count(*) as "Total_inasistencias"
from "Atencion" as a
join "Persona" as p on a."IDPaciente" = p."ID"
where a."Efectuada" = False
group by a."IDPaciente", p."Nombres", p."Apellidos"
order by "Total_inasistencias" desc limit 5;