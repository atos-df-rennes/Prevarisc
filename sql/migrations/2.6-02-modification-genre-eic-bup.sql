SET NAMES 'utf8';

UPDATE `genre` SET `LIBELLE_GENRE` = 'BUP' WHERE `LIBELLE_GENRE` = 'EIC';

UPDATE `resources` 
SET `name` = REPLACE(name, 'eic', 'bup'), `text` = REPLACE(text, 'EIC', 'BUP')
WHERE `name` LIKE 'etablissement_eic%';
