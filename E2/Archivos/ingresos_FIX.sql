select 
	date_trunc('month', a."fecha"::timestamp without time zone) as "Meses",
	i."Nombre" as "Institucion_Salud",
	count(a."ID") as "Numero_Atenciones",
	sum(ar."ValorFonasa") as "Ingresos_Fonasa",
	sum(ar."ValorColita") as "Ingresos_Colita"
from "Atencion" as a
join "Persona" as p on a."IDPaciente" = p."ID"
join "InstituciondeSalud" as i on p."InstSalud" = i."ID"
join "Arancel" as ar on a."ID" = ar."ID"
where a."Efectuada" = True
group by "Meses", "Institucion_Salud"
order by "Meses" asc