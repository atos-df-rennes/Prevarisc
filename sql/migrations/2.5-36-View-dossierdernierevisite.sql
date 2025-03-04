SET NAMES 'utf8';

DROP VIEW IF EXISTS `dossierdernierevisite`;

CREATE VIEW `dossierdernierevisite` AS 
SELECT 
    ed.ID_ETABLISSEMENT,
    MAX(d.DATEVISITE_DOSSIER) as DATEVISITE_DOSSIER,
    DATE_ADD(MAX(d.DATEVISITE_DOSSIER), INTERVAL ei.PERIODICITE_ETABLISSEMENTINFORMATIONS MONTH) as DATEPROCHAINEVISITE_DOSSIER
FROM dossier d
    INNER JOIN dossiernature n on d.ID_DOSSIER = n.ID_DOSSIER
    INNER JOIN etablissementdossier ed on ed.ID_DOSSIER = d.ID_DOSSIER
    INNER JOIN etablissementinformationsactuel ei on ei.ID_ETABLISSEMENT = ed.ID_ETABLISSEMENT
WHERE 
    n.ID_NATURE IN(26,21,47,48)
    AND d.AVIS_DOSSIER_COMMISSION IS NOT NULL
    AND d.AVIS_DOSSIER_COMMISSION > 0
    AND d.DATESUPPRESSION_DOSSIER IS NULL
GROUP BY ed.ID_ETABLISSEMENT;