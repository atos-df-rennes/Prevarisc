
SET NAMES 'utf8';

UPDATE `resources` 
SET `name` = 'etablissement_bup_0_0' 
WHERE `name` = 'etablissement_eic_0_0';

UPDATE `resources`
SET `text` = 'BUP (Ignorer les groupements - Ignorer la commune)'
WHERE `name` = 'etablissement_bup_0_0';
