select o."IDArancel", a."ConsAtMedica" as "descripcion", count(*) as "cantidad_solicitudes"
from "Orden" as o
join "Arancel" as a on o."IDArancel" = a."ID"
where a."ConsAtMedica" like '%examen%'
group by o."IDArancel", a."ConsAtMedica"
order by "cantidad_solicitudes" desc limit 5