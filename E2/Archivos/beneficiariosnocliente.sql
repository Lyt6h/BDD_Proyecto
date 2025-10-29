select
	p."ID",
	p."Nombres" || ' ' || p."Apellidos" as "nombre"
from "Persona" as p
join "beneficiario" as b on b."IDtitular" = p."ID"
join "Atencion" as a on a."IDPaciente" = p."ID"
where b."Beneficiario" = True
group by p."ID", p."Nombres", p."Apellidos"
order by p."ID"
-- hasta aqui estan listadas las personas titulares de una institucion que son pacientes.
-- falta que almenos uno de sus beneficiarios no sea paciente. 