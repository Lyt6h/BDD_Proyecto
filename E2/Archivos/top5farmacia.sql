select 
	"Medicamento",
	count(*) as "veces_recetado"
from "medicamentos"
group by "Medicamento"
order by "veces_recetado" desc limit 5