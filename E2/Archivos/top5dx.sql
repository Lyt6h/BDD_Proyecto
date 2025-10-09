SELECT p."Nombres", p."Apellidos", COUNT(DISTINCT a."Diagnostico") AS "Cantidad_Diagnosticos"
FROM "Persona" AS p, "Atencion" AS a
WHERE p."ID" = a."ID" 
GROUP BY p."Nombres", p."Apellidos"
HAVING COUNT(DISTINCT a."Diagnostico") > 0
ORDER BY COUNT(DISTINCT a."Diagnostico") DESC LIMIT 5
