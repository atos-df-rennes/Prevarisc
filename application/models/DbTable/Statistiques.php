<?php

class Model_DbTable_Statistiques extends Zend_Db_Table_Abstract
{
    public $etablissements;

    protected $_name = 'etablissement';

    protected $_primary = 'ID_ETABLISSEMENT';

    private $ets_date;

    // Début : liste des ERP
    public function listeDesERP($date): self
    {
        if (null == $date) {
            $date = date('d/m/Y', time());
        }

        $this->ets_date = $date;

        $this->etablissements = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'etablissement'], ['ID_ETABLISSEMENT'])
            ->columns([
                'DATEVISITE_DOSSIER' => new Zend_Db_Expr("( SELECT MAX( dossier.DATEVISITE_DOSSIER ) FROM etablissementdossier, dossier, dossiernature, etablissement
                WHERE dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
                AND dossiernature.ID_DOSSIER = dossier.ID_DOSSIER
                AND etablissementdossier.ID_ETABLISSEMENT = etablissement.ID_ETABLISSEMENT
                AND etablissement.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                AND dossiernature.ID_NATURE = '21'
                AND ( dossier.TYPE_DOSSIER = '2' || dossier.TYPE_DOSSIER = '3')
                GROUP BY etablissement.ID_ETABLISSEMENT)"),
                'ARRONDISSEMENT' => new Zend_Db_Expr('(SELECT `groupement`.LIBELLE_GROUPEMENT FROM `groupement` INNER JOIN `groupementcommune` ON groupementcommune.ID_GROUPEMENT = groupement.ID_GROUPEMENT INNER JOIN `groupementtype` ON groupementtype.ID_GROUPEMENTTYPE = groupement.ID_GROUPEMENTTYPE WHERE (groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE AND groupementtype.ID_GROUPEMENTTYPE = 2) LIMIT 1)'),
            ])
            ->join('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT', [
                'LIBELLE_ETABLISSEMENTINFORMATIONS',
                'ID_CATEGORIE',
                'ID_TYPE',
                'ID_COMMISSION',
                'ID_STATUT',
                'DATE_ETABLISSEMENTINFORMATIONS',
            ])
            ->joinLeft('type', 'etablissementinformations.ID_TYPE= type.ID_TYPE ', 'LIBELLE_TYPE')
            ->joinLeft('dossier', 'e.ID_DOSSIER_DONNANT_AVIS = dossier.ID_DOSSIER', ['ID_AVIS' => 'AVIS_DOSSIER_COMMISSION'])
            ->joinLeft('avis', 'dossier.AVIS_DOSSIER = avis.ID_AVIS', ['LIBELLE_AVIS' => 'LIBELLE_AVIS'])
            ->joinLeft('commission', 'commission.ID_COMMISSION = etablissementinformations.ID_COMMISSION', 'LIBELLE_COMMISSION')
            ->joinLeft('categorie', 'etablissementinformations.ID_CATEGORIE = categorie.ID_CATEGORIE', 'LIBELLE_CATEGORIE')
            ->joinLeft('etablissementadresse', 'e.ID_ETABLISSEMENT = etablissementadresse.ID_ETABLISSEMENT', ['NUMERO_ADRESSE', 'COMPLEMENT_ADRESSE'])
            ->joinLeft('adressecommune', 'etablissementadresse.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE', ['LIBELLE_COMMUNE', 'CODEPOSTAL_COMMUNE'])
            ->joinLeft('adresserue', 'etablissementadresse.NUMINSEE_COMMUNE = adresserue.NUMINSEE_COMMUNE AND etablissementadresse.ID_RUE = adresserue.ID_RUE')
            ->where('ID_GENRE = 2') // Pas de site - IGH
            ->where("etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = (
                SELECT MAX(DATE_ETABLISSEMENTINFORMATIONS)
                FROM etablissementinformations
                WHERE
                    etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT AND
                    UNIX_TIMESTAMP(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) <= UNIX_TIMESTAMP('".$this->getDate($date)."') OR
                    etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS IS NULL
                )
            ")
            ->group('ID_ETABLISSEMENT')
        ;

        return $this;
    }

    public function listeDesERPVisitePeriodique($dateDebut, $dateFin): self
    {
        if (null == $dateDebut) {
            $dateDebut = date('01/01/'.date('Y'), time());
        }

        if (null == $dateFin) {
            $dateFin = date('31/12/'.date('Y'), time());
        }

        $this->etablissements = $this->select()
            ->setIntegrityCheck(false)
            ->from(['e' => 'etablissement'], ['ID_ETABLISSEMENT'])
            ->columns([
                'DATEVISITE_DOSSIER' => new Zend_Db_Expr('( SELECT MAX( dossier.DATEVISITE_DOSSIER ) FROM etablissementdossier, dossier, dossiernature, etablissement
                WHERE dossier.ID_DOSSIER = etablissementdossier.ID_DOSSIER
                AND DATEDIFF(dossier.DATEVISITE_DOSSIER,CURDATE()) > 0
                AND dossiernature.ID_DOSSIER = dossier.ID_DOSSIER
                AND etablissementdossier.ID_ETABLISSEMENT = etablissement.ID_ETABLISSEMENT
                AND etablissement.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT
                AND dossiernature.ID_NATURE in (21,26) 
                AND dossier.TYPE_DOSSIER in (2,3)
                GROUP BY etablissement.ID_ETABLISSEMENT)'),
                'ARRONDISSEMENT' => new Zend_Db_Expr('(SELECT `groupement`.LIBELLE_GROUPEMENT FROM `groupement` INNER JOIN `groupementcommune` ON groupementcommune.ID_GROUPEMENT = groupement.ID_GROUPEMENT INNER JOIN `groupementtype` ON groupementtype.ID_GROUPEMENTTYPE = groupement.ID_GROUPEMENTTYPE WHERE (groupementcommune.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE AND groupementtype.ID_GROUPEMENTTYPE = 2) LIMIT 1)'),
            ])
            ->join('etablissementinformations', 'e.ID_ETABLISSEMENT = etablissementinformations.ID_ETABLISSEMENT', [
                'LIBELLE_ETABLISSEMENTINFORMATIONS',
                'ID_CATEGORIE',
                'ID_TYPE',
                'ID_COMMISSION',
                'ID_STATUT',
                'DATE_ETABLISSEMENTINFORMATIONS',
                'PERIODICITE_ETABLISSEMENTINFORMATIONS',
                'e.ID_ETABLISSEMENT',
            ])
            ->joinLeft('type', 'etablissementinformations.ID_TYPE= type.ID_TYPE ', 'LIBELLE_TYPE')
            ->joinLeft('etablissementinformationspreventionniste', 'etablissementinformations.ID_ETABLISSEMENTINFORMATIONS  = etablissementinformationspreventionniste.ID_ETABLISSEMENTINFORMATIONS')
            ->joinLeft('utilisateur', 'utilisateur.ID_UTILISATEUR = etablissementinformationspreventionniste.ID_UTILISATEUR')
            ->joinLeft('utilisateurinformations', 'utilisateur.ID_UTILISATEURINFORMATIONS = utilisateurinformations.ID_UTILISATEURINFORMATIONS', ['NOM_UTILISATEURINFORMATIONS', 'PRENOM_UTILISATEURINFORMATIONS'])
            ->joinLeft('dossier', 'e.ID_DOSSIER_DONNANT_AVIS = dossier.ID_DOSSIER', ['ID_AVIS' => 'AVIS_DOSSIER_COMMISSION'])
            ->joinLeft('avis', 'dossier.AVIS_DOSSIER = avis.ID_AVIS', ['LIBELLE_AVIS' => 'LIBELLE_AVIS'])
            ->joinLeft('commission', 'commission.ID_COMMISSION = etablissementinformations.ID_COMMISSION', 'LIBELLE_COMMISSION')
            ->joinLeft('categorie', 'etablissementinformations.ID_CATEGORIE = categorie.ID_CATEGORIE', 'LIBELLE_CATEGORIE')
            ->joinLeft('etablissementadresse', 'e.ID_ETABLISSEMENT = etablissementadresse.ID_ETABLISSEMENT', ['NUMERO_ADRESSE', 'COMPLEMENT_ADRESSE'])
            ->joinLeft('adressecommune', 'etablissementadresse.NUMINSEE_COMMUNE = adressecommune.NUMINSEE_COMMUNE', ['LIBELLE_COMMUNE', 'CODEPOSTAL_COMMUNE'])
            ->joinLeft('adresserue', 'etablissementadresse.NUMINSEE_COMMUNE = adresserue.NUMINSEE_COMMUNE AND etablissementadresse.ID_RUE = adresserue.ID_RUE')
            ->where('ID_GENRE = 2') // Pas de site - IGH
            ->where("etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS = (
                SELECT MAX(DATE_ETABLISSEMENTINFORMATIONS)
                FROM etablissementinformations
                WHERE
                    etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT AND
                    UNIX_TIMESTAMP(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) >= UNIX_TIMESTAMP('".$this->getDate($dateDebut)."')
                    AND UNIX_TIMESTAMP(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) <= UNIX_TIMESTAMP('".$this->getDate($dateFin)."')
                    OR  etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS IS NULL
                )
            ")
            ->group('ID_ETABLISSEMENT')
        ;

        return $this;
    }

    // CHAMPS SUPPLEMENTAIRES

    public function enExploitation(): ?self
    {
        if (null != $this->etablissements) {
            $this->etablissements->where('ID_STATUT = 2');

            return $this;
        }

        return null;
    }

    public function sousmisAControle(): ?self
    {
        if (null != $this->etablissements) {
            $this->etablissements->where('etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS > 0 AND etablissementinformations.PERIODICITE_ETABLISSEMENTINFORMATIONS IS NOT NULL');

            return $this;
        }

        return null;
    }

    public function sousAvisDefavorable(): ?self
    {
        if (null != $this->etablissements) {
            $this->etablissements->where('dossier.AVIS_DOSSIER_COMMISSION = 2'); // AND SCHEMAMISESECURITE_ETABLISSEMENTINFORMATIONS != 1

            $this->etablissements->columns([
                'NBJOURS_DEFAVORABLE' => new Zend_Db_Expr("(
                SELECT DATEDIFF('".$this->getDate($this->ets_date)."', MAX(DATE_ETABLISSEMENTINFORMATIONS))
                FROM etablissementinformations
                WHERE
                    etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT AND
                    UNIX_TIMESTAMP(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) <= UNIX_TIMESTAMP('".$this->getDate($this->ets_date)."') OR
                    etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS IS NULL
                GROUP BY ID_ETABLISSEMENT
                )
                "),
            ]);

            return $this;
        }

        return null;
    }

    /**
     * @param mixed $commune
     */
    public function surLaCommune($commune): ?self
    {
        if (null != $this->etablissements) {
            $this->etablissements->where('adressecommune.NUMINSEE_COMMUNE = ?', $commune);

            return $this;
        }

        return null;
    }

    // Fonctions
    public function trierPar($col): self
    {
        $this->etablissements->order($col);

        return $this;
    }

    /**
     * @return null|array
     */
    public function go()
    {
        if (null != $this->etablissements) {
            return $this->fetchAll($this->etablissements)->toArray();
        }

        return null;
    }

    private function getDate($input): string
    {
        $array_date = explode('/', $input);
        $expectedNumberOfDateParameters = 3;

        if ($expectedNumberOfDateParameters != count($array_date)) {
            throw new Exception('Erreur dans la date', 500);
        }

        return $array_date[2].'-'.$array_date[1].'-'.$array_date[0].' 00:00:00';
    }
}
