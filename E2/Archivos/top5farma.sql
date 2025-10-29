SELECT 
	p."ID" as "ID_persona",
	p."Nombres" || ' ' || p."Apellidos" as "Nombre",
	m."Medicamento",
	COUNT(*) as "cantidad_recetas"
from "Persona" as p
join "Atencion" as a on p."ID" = a."IDPaciente"
join "medicamentos" as m on a."ID" = m."IDAtencion"
group by p."ID", p."Nombres", p."Apellidos", m."Medicamento"
order by count(*) desc limit 5