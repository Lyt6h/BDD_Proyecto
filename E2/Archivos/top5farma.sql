SELECT a."IDPaciente", COUNT(*)
from medicamentos as m
join "Atencion" as a on m."IDAtencion" = a."ID"
group by a."IDPaciente"
order by count(*) desc limit 5