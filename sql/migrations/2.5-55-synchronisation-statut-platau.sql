SET NAMES 'utf8';

ALTER TABLE `piecejointe` ADD COLUMN `ID_PLATAU` CHAR(11) NULL DEFAULT NULL;
ALTER TABLE `piecejointe` ADD COLUMN `MESSAGE_ERREUR` VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE `piecejointestatut` ADD COLUMN `NOM_LISIBLE` VARCHAR(50) NOT NULL;
INSERT IGNORE INTO `piecejointestatut` VALUES (5, 'awaiting_status', "Vérification du statut");
UPDATE `piecejointestatut` pjs JOIN (
    SELECT 1 as ID_PIECEJOINTESTATUT, "Non envoyé" as new_name
    UNION ALL
    SELECT 2, "En attente d'envoi"
    UNION ALL
    SELECT 3, "Envoyé"
    UNION ALL
    SELECT 4, "Erreur lors de l'envoi"
) vals ON pjs.ID_PIECEJOINTESTATUT = vals.ID_PIECEJOINTESTATUT
SET `NOM_LISIBLE` = new_name;