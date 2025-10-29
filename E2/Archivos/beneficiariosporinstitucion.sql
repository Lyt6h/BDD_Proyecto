select
	i."Nombre" as institucion_salud,
	count(distinct b."IDtitular") as "Cantidad_beneficiarios_titulares",
	count(distinct b."IDpersona") - count(distinct b."IDtitular") as "Cantidad_beneficiarios_no_titulares"
from "InstituciondeSalud" as i
join "Persona" as p on p."InstSalud" = i."ID"
join "beneficiario" as b on b."IDpersona" = p."ID"
join "Atencion" as a on a."IDPaciente" = p."ID"
where b."Beneficiario" = True
group by i."Nombre"
order by i."Nombre"