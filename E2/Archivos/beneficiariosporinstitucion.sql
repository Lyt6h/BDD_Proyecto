SELECT 
    i."Nombre" AS institucion_salud,
    COUNT(DISTINCT CASE WHEN b."Beneficiario" = FALSE THEN p."ID" END) AS titulares,
    COUNT(DISTINCT CASE WHEN b."Beneficiario" = TRUE THEN p."ID" END) AS beneficiarios
FROM "Persona" AS p, "beneficiario" AS b, "InstituciondeSalud" AS i
WHERE p."ID" = b."IDpersona" AND p."InstSalud" = i."ID" AND p."ID" IN (SELECT "IDPaciente" FROM "Atencion")
GROUP BY i."Nombre"
ORDER BY i."Nombre"