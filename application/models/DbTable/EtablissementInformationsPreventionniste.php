<?php

class Model_DbTable_EtablissementInformationsPreventionniste extends Zend_Db_Table_Abstract
{
    // Nom de la base
    protected $_name = 'etablissementinformationspreventionniste';

    // Clé primaire
    protected $_primary = ['ID_ETABLISSEMENTINFORMATIONS', 'ID_UTILISATEUR'];

    public function getEtablissementsPreventioniste(string $groupement): array
    {
        $select = $this->select()->setIntegrityCheck(false)
            ->from(['e' => 'etablissement'], [])
            ->join(['ea' => 'etablissementadresse'], 'ea.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT', [])
            ->join(['ei' => 'etablissementinformations'], 'e.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT AND ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )', 'ID_ETABLISSEMENTINFORMATIONS')
            ->join(['gc' => 'groupementcommune'], 'gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE', [])
            ->join(['g' => 'groupement'], 'g.ID_GROUPEMENT = gc.ID_GROUPEMENT', [])
            ->join(['gt' => 'groupementtype'], 'g.ID_GROUPEMENTTYPE = gt.ID_GROUPEMENTTYPE', [])
            ->join(['gp' => 'groupementpreventionniste'], 'gp.ID_GROUPEMENT = g.ID_GROUPEMENT', [])
            ->join(['u' => 'utilisateur'], 'u.ID_UTILISATEUR = gp.ID_UTILISATEUR', 'ID_UTILISATEUR')
            ->where('ei.ID_GENRE = 2')
            ->where('g.LIBELLE_GROUPEMENT = ?', $groupement)
            ->group(['ei.ID_ETABLISSEMENTINFORMATIONS', 'u.ID_UTILISATEUR'])
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    public function getCellulesPreventioniste(string $groupement): array
    {
        $select = $this->select()->setIntegrityCheck(false)
            ->from(['e' => 'etablissement'], [])
            ->join(['el' => 'etablissementlie'], 'el.ID_FILS_ETABLISSEMENT = e.ID_ETABLISSEMENT', [])
            ->join(['pere' => 'etablissement'], 'pere.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT', [])
            ->join(['ea' => 'etablissementadresse'], 'ea.ID_ETABLISSEMENT = pere.ID_ETABLISSEMENT', [])
            ->join(['ei' => 'etablissementinformations'], 'ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT AND ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )', 'ID_ETABLISSEMENTINFORMATIONS')
            ->join(['gc' => 'groupementcommune'], 'gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE', [])
            ->join(['g' => 'groupement'], 'g.ID_GROUPEMENT = gc.ID_GROUPEMENT', [])
            ->join(['gt' => 'groupementtype'], 'gt.ID_GROUPEMENTTYPE = g.ID_GROUPEMENTTYPE', [])
            ->join(['gp' => 'groupementpreventionniste'], 'gp.ID_GROUPEMENT = g.ID_GROUPEMENT', [])
            ->join(['u' => 'utilisateur'], 'u.ID_UTILISATEUR = gp.ID_UTILISATEUR', 'ID_UTILISATEUR')
            ->where('ei.ID_GENRE = 3')
            ->where('g.LIBELLE_GROUPEMENT = ?', $groupement)
            ->group(['ei.ID_ETABLISSEMENTINFORMATIONS', 'u.ID_UTILISATEUR'])
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    public function getSitesPreventioniste(string $groupement): array
    {
        $select = $this->select()->setIntegrityCheck(false)
            ->from(['e' => 'etablissement'], [])
            ->join(['el' => 'etablissementlie'], 'el.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT', [])
            ->join(['fils' => 'etablissement'], 'fils.ID_ETABLISSEMENT = el.ID_FILS_ETABLISSEMENT', [])
            ->join(['ea' => 'etablissementadresse'], 'ea.ID_ETABLISSEMENT = fils.ID_ETABLISSEMENT', [])
            ->join(['ei' => 'etablissementinformations'], 'ei.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT AND ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = e.ID_ETABLISSEMENT )', 'ID_ETABLISSEMENTINFORMATIONS')
            ->join(['gc' => 'groupementcommune'], 'gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE', [])
            ->join(['g' => 'groupement'], 'g.ID_GROUPEMENT = gc.ID_GROUPEMENT', [])
            ->join(['gt' => 'groupementtype'], 'gt.ID_GROUPEMENTTYPE = g.ID_GROUPEMENTTYPE', [])
            ->join(['gp' => 'groupementpreventionniste'], 'gp.ID_GROUPEMENT = g.ID_GROUPEMENT', [])
            ->join(['u' => 'utilisateur'], 'u.ID_UTILISATEUR = gp.ID_UTILISATEUR', 'ID_UTILISATEUR')
            ->where('ei.ID_GENRE = 1')
            ->where('g.LIBELLE_GROUPEMENT = ?', $groupement)
            ->group(['ei.ID_ETABLISSEMENTINFORMATIONS', 'u.ID_UTILISATEUR'])
        ;

        return $this->getAdapter()->fetchAll($select);
    }

    public function deleteEtablissementsPreventioniste(string $groupement): void
    {
        $delete = "DELETE ep.* FROM etablissementinformationspreventionniste ep
        INNER JOIN etablissementinformations ei ON ep.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS AND ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT)
        INNER JOIN `etablissementadresse` AS `ea` ON ea.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
        INNER JOIN `groupementcommune` AS `gc` ON gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
        INNER JOIN `groupement` AS `g` ON g.ID_GROUPEMENT = gc.ID_GROUPEMENT
        WHERE g.LIBELLE_GROUPEMENT = '".$groupement."';";

        $this->getAdapter()->query($delete);
    }

    public function deleteCellulesPreventioniste(string $groupement): void
    {
        $delete = "DELETE ep.* FROM etablissementinformationspreventionniste ep
        INNER JOIN etablissementinformations ei ON ep.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS AND ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT)
        INNER JOIN `etablissementlie` AS `el` ON el.ID_FILS_ETABLISSEMENT = ei.ID_ETABLISSEMENT
        INNER JOIN `etablissement` AS `pere` ON pere.ID_ETABLISSEMENT = el.ID_ETABLISSEMENT
        INNER JOIN `etablissementadresse` AS `ea` ON ea.ID_ETABLISSEMENT = pere.ID_ETABLISSEMENT
        INNER JOIN `groupementcommune` AS `gc` ON gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
        INNER JOIN `groupement` AS `g` ON g.ID_GROUPEMENT = gc.ID_GROUPEMENT
        WHERE g.LIBELLE_GROUPEMENT = '".$groupement."';";

        $this->getAdapter()->query($delete);
    }

    public function deleteSitesPreventioniste(string $groupement): void
    {
        $delete = "DELETE ep.* FROM etablissementinformationspreventionniste ep
        INNER JOIN etablissementinformations ei ON ep.ID_ETABLISSEMENTINFORMATIONS = ei.ID_ETABLISSEMENTINFORMATIONS AND ei.DATE_ETABLISSEMENTINFORMATIONS = (SELECT MAX(etablissementinformations.DATE_ETABLISSEMENTINFORMATIONS) FROM etablissementinformations WHERE etablissementinformations.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT)
        INNER JOIN `etablissementlie` AS `el` ON el.ID_ETABLISSEMENT = ei.ID_ETABLISSEMENT
        INNER JOIN `etablissement` AS `fils` ON fils.ID_ETABLISSEMENT = el.ID_FILS_ETABLISSEMENT
        INNER JOIN `etablissementadresse` AS `ea` ON ea.ID_ETABLISSEMENT = fils.ID_ETABLISSEMENT
        INNER JOIN `groupementcommune` AS `gc` ON gc.NUMINSEE_COMMUNE = ea.NUMINSEE_COMMUNE
        INNER JOIN `groupement` AS `g` ON g.ID_GROUPEMENT = gc.ID_GROUPEMENT
        WHERE g.LIBELLE_GROUPEMENT = '".$groupement."';";

        $this->getAdapter()->query($delete);
    }

    public function addPreventioniste(array $valeur): void
    {
        foreach ($valeur as $val) {
            $this->insert($val);
        }
    }
}
