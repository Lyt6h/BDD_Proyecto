select
	o."IDArancel",
	a."ConsAtMedica" as "descripcion",
	count(*) as "cantidad_solicitudes"
from "Orden" as o
join "Arancel" as a on o."IDArancel" = a."ID"
where a."ConsAtMedica" like '%examen%' or a."ConsAtMedica" like '%Examen%'  -- recuerdo que en una respuesta de issue decia que era examen
group by o."IDArancel", a."ConsAtMedica"									-- cualquiera que contubiese la palabra "examen" en la descripcion.
order by "cantidad_solicitudes" desc limit 5