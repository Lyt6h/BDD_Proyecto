select 
	p."ID",
	p."Nombres" || ' ' || p."Apellidos" as "nombre",
	count(distinct a."Diagnostico") as "Cantidad_Diagnosticos_diff"
from "Persona" as p 
join "Atencion" as a on p."ID" = a."IDPaciente" 
group by p."Nombres", p."Apellidos", p."ID"
order by "Cantidad_Diagnosticos_diff" desc limit 5
