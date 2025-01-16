SET NAMES 'utf8';

UPDATE `genre` SET `LIBELLE_GENRE` = 'BUP' WHERE `LIBELLE_GENRE` = 'EIC';

UPDATE `resources` 
SET `name` = 'etablissement_bup_0_0' 
WHERE `name` = 'etablissement_eic_0_0';

UPDATE `resources`
SET `text` = 'BUP (Ignorer les groupements - Ignorer la commune)'
WHERE `name` = 'etablissement_bup_0_0';