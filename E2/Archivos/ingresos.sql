select 
	extract(year from a."fecha") as "año",
	extract(month from a."fecha") as "mes",
	i."Nombre" as "Institucion_Salud",
	count(a."ID") as "Numero_Atenciones",
	sum(ar."ValorFonasa") as "Ingresos"
from "Atencion" as a
join "Persona" as p on a."IDPaciente" = p."ID"
join "InstituciondeSalud" as i on p."InstSalud" = i."ID"
join "Arancel" as ar on ar."ConsAtMedica" like '%consulta%'
group by "año", "mes", i."Nombre"
order by "año", "mes", i."Nombre"