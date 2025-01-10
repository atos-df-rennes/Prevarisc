SET NAMES 'utf8';

(SELECT 'ID_DOSSIER', 'ID_PIECEJOINTE')
UNION
(SELECT d.ID_DOSSIER, pj.ID_PIECEJOINTE

INTO OUTFILE '/tmp/export-reinitialisation.csv'
FIELDS TERMINATED BY ';' OPTIONALLY ENCLOSED BY '"'
ESCAPED BY '\\'
LINES TERMINATED BY '\r\n'

FROM (SELECT ID_PIECEJOINTE, ID_PIECEJOINTESTATUT from piecejointe) as pj
INNER JOIN dossierpj dpj ON pj.ID_PIECEJOINTE = dpj.ID_PIECEJOINTE
INNER JOIN dossier d ON dpj.ID_DOSSIER = d.ID_DOSSIER
INNER JOIN platauconsultation pc ON d.ID_PLATAU = pc.ID_PLATAU
INNER JOIN piecejointestatut pjs ON pjs.ID_PIECEJOINTESTATUT = pj.ID_PIECEJOINTESTATUT
WHERE d.ID_PLATAU IS NOT NULL
AND d.ID_DOSSIER IN (SELECT etablissementdossier.ID_DOSSIER from etablissementdossier)
AND pc.STATUT_AVIS = 'treated'
AND pjs.NOM_STATUT IN ("to_be_exported", "awaiting_status")
ORDER BY d.DATEINSERT_DOSSIER
);

UPDATE piecejointe
SET
    piecejointe.ID_PIECEJOINTESTATUT = 4,
    piecejointe.MESSAGE_ERREUR = "Statut réinitialisé car envoi bloqué"
WHERE piecejointe.ID_PIECEJOINTE IN (
    SELECT pj.ID_PIECEJOINTE
    FROM (SELECT ID_PIECEJOINTE, ID_PIECEJOINTESTATUT from piecejointe) as pj
    INNER JOIN dossierpj dpj ON pj.ID_PIECEJOINTE = dpj.ID_PIECEJOINTE
    INNER JOIN dossier d ON dpj.ID_DOSSIER = d.ID_DOSSIER
    INNER JOIN platauconsultation pc ON d.ID_PLATAU = pc.ID_PLATAU
    INNER JOIN piecejointestatut pjs ON pjs.ID_PIECEJOINTESTATUT = pj.ID_PIECEJOINTESTATUT
    WHERE d.ID_PLATAU IS NOT NULL
    AND d.ID_DOSSIER IN (SELECT etablissementdossier.ID_DOSSIER from etablissementdossier)
    AND pc.STATUT_AVIS = 'treated'
    AND pjs.NOM_STATUT IN ("to_be_exported", "awaiting_status")
    ORDER BY d.DATEINSERT_DOSSIER
);