select date_trunc('month', "fecha") as "fecha_por_mes", count(*) AS "Total_inasistencias"
from "Atencion"
group by date_trunc('month', "fecha"), "Efectuada" = FALSE
having "Efectuada" = FALSE
order by "fecha_por_mes";



select a."IDMedico", p."Nombres", count(*) as "Total_inasistencias"
from "Atencion" as a
join "Persona" as p on a."IDMedico" = p."ID"
where a."Efectuada" = False
group by a."IDMedico", p."Nombres"
order by "Total_inasistencias" desc limit 5;



select a."IDPaciente", p."Nombres", count(*) as "Total_inasistencias"
from "Atencion" as a
join "Persona" as p on a."IDPaciente" = p."ID"
where a."Efectuada" = False
group by a."IDPaciente", p."Nombres"
order by "Total_inasistencias" desc limit 5;