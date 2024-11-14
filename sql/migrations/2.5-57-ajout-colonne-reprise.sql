SET NAMES 'utf8';

ALTER TABLE `prescriptiondossier`
ADD COLUMN `ID_DOSSIER_REPRISE` bigint(20),
ADD KEY `fk_prescriptiondossier_dossierreprise_idx` (`ID_DOSSIER_REPRISE`),
ADD CONSTRAINT `fk_prescriptiondossier_dossierreprise_idx` FOREIGN KEY (`ID_DOSSIER_REPRISE`) REFERENCES `dossier` (`ID_DOSSIER`) ON DELETE CASCADE ON UPDATE NO ACTION;
