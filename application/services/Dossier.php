<?php

class Service_Dossier
{
    public const ID_DOSSIERTYPE_ETUDE = 1;

    public const ID_DOSSIERTYPE_VISITE = 2;

    public const ID_DOSSIERTYPE_GRPVISITE = 3;

    /**
     * @var array<string, int>|array<string, mixed>|array<string, mixed[]>|mixed
     */
    public $etablissement;

    /**
     * @var mixed|mixed[]
     */
    public $listeEtablissement;

    /**
     * Récupération de l'ensemble des types.
     *
     * @return array
     */
    public function getAllTypes()
    {
        $DB_type = new Model_DbTable_DossierType();

        return $DB_type->fetchAll()->toArray();
    }

    /**
     * Récupération de l'ensemble des natures.
     *
     * @return array
     */
    public function getAllNatures()
    {
        $db_nature = new Model_DbTable_DossierNatureliste();

        return $db_nature->fetchAll()->toArray();
    }

    /**
     * Récupération des pièces jointes d'un dossier.
     *
     * @param int $id_dossier
     *
     * @return array
     */
    public function getAllPJ($id_dossier)
    {
        $DBused = new Model_DbTable_PieceJointe();

        return $DBused->affichagePieceJointe('dossierpj', 'dossierpj.ID_DOSSIER', $id_dossier);
    }

    /**
     * Similaire à la fonction getAllPJ() mais permet d'avoir l'ID_PLATAU pour connaître le chemin où sont sauvegardées les PJs.
     */
    public function getAllPiecesJointes(int $idDossier): array
    {
        $DBused = new Model_DbTable_PieceJointe();

        $select = $DBused->select()
            ->setIntegrityCheck(false)
            ->from('piecejointe')
            ->join('dossierpj', 'piecejointe.ID_PIECEJOINTE = dossierpj.ID_PIECEJOINTE', [])
            ->join('dossier', 'dossierpj.ID_DOSSIER = dossier.ID_DOSSIER', ['ID_DOSSIER', 'ID_PLATAU'])
            ->where('dossier.ID_DOSSIER = ?', $idDossier)
            ->order('piecejointe.ID_PIECEJOINTE DESC')
        ;

        return $DBused->fetchAll($select)->toArray();
    }

    /**
     * Ajout d'une pièce jointe pour un dossier.
     *
     * @param int    $id_dossier
     * @param string $name
     * @param string $description
     */
    public function addPJ($id_dossier, array $file, $name = '', $description = ''): void
    {
        $extension = strtolower(strrchr($file['name'], '.'));

        $DBpieceJointe = new Model_DbTable_PieceJointe();

        $piece_jointe = [
            'EXTENSION_PIECEJOINTE' => $extension,
            'NOM_PIECEJOINTE' => '' == $name ? substr($file['name'], 0, -4) : $name,
            'DESCRIPTION_PIECEJOINTE' => $description,
            'DATE_PIECEJOINTE' => date('Y-m-d'),
        ];

        $piece_jointe['ID_PIECEJOINTE'] = $DBpieceJointe->createRow($piece_jointe)->save();

        $store = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore');
        $file_path = $store->getFilePath($piece_jointe, 'dossier', $id_dossier, true);

        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            $msg = 'Ne peut pas déplacer le fichier '.$file['tmp_name'].' vers '.$file_path;

            // log some debug information
            error_log($msg);
            error_log('is_dir '.dirname($file_path).': '.is_dir(dirname($file_path)));
            error_log('is_writable '.dirname($file_path).':'.is_writable(dirname($file_path)));
            $cmd = 'ls -all '.dirname($file_path);
            error_log($cmd);
            $rslt = explode("\n", shell_exec($cmd));
            foreach ($rslt as $file) {
                error_log($file);
            }

            throw new Exception($msg);
        }

        $DBsave = new Model_DbTable_DossierPj();
        $DBsave->createRow([
            'ID_DOSSIER' => $id_dossier,
            'ID_PIECEJOINTE' => $piece_jointe['ID_PIECEJOINTE'],
        ])->save();
    }

    /**
     * Suppression d'une pièce jointe d'un dossier.
     *
     * @param int $id_dossier
     * @param int $id_pj
     */
    public function deletePJ($id_dossier, $id_pj): void
    {
        $DBpieceJointe = new Model_DbTable_PieceJointe();
        $DBitem = new Model_DbTable_DossierPj();

        $pj = $DBpieceJointe->find($id_pj)->current();
        if (!$pj) {
            return;
        }

        $store = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('dataStore');
        $file_path = $store->getFilePath($pj, 'dossier', $id_dossier);

        if (null != $DBitem) {
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            $DBitem->delete('ID_PIECEJOINTE = '.(int) $id_pj);
            $pj->delete();
        }
    }

    /**
     * Récupération des contacts d'un dossier.
     *
     * @param int $id_dossier
     *
     * @return array
     */
    public function getAllContacts($id_dossier)
    {
        $DB_contact = new Model_DbTable_UtilisateurInformations();

        return $DB_contact->getContact('dossier', $id_dossier);
    }

    public function getContactInfo(int $idDossier, int $idEtab, int $idFonction): array
    {
        $dbDossierContact = new Model_DbTable_DossierContact();
        $contactInfos = $dbDossierContact->recupInfoContact($idDossier, $idFonction);
        if (1 === count($contactInfos)) {
            return $contactInfos[0];
        }

        $contactInfos = $dbDossierContact->recupContactEtablissement($idEtab, $idFonction);
        if ([] !== $contactInfos) {
            return $contactInfos[0];
        }

        return [];
    }

    /**
     * Récupération des textes applicables d'un dossier.
     *
     * @return array[][]
     */
    public function getAllTextesApplicables(int $id_dossier): array
    {
        $dossierTextesAppl = new Model_DbTable_DossierTextesAppl();

        $textes_applicables_non_organises = $dossierTextesAppl->recupTextes($id_dossier);

        return (new Service_TextesApplicables())->organize($textes_applicables_non_organises);
    }

    /**
     * Sauvegarde des textes applicables d'un dossier.
     *
     * @param int $id_dossier
     */
    public function saveTextesApplicables($id_dossier, array $textes_applicables): void
    {
        $dbDossier = new Model_DbTable_Dossier();
        $dossierTexteApplicable = new Model_DbTable_DossierTextesAppl();
        $etsTexteApplicable = new Model_DbTable_EtsTextesAppl();

        $typeDossier = $dbDossier->getTypeDossier($id_dossier);
        $type = $typeDossier['TYPE_DOSSIER'];

        // On récupère le premier établissements afin de mettre à jour ses textes applicables lorsque l'on est dans une visite
        $id_etablissement = null;
        if (in_array($type, [2, 3])) {
            $tabEtablissement = $dbDossier->getEtablissementDossier($id_dossier);
            $id_etablissement = isset($tabEtablissement[0]) ? $tabEtablissement[0]['ID_ETABLISSEMENT'] : null;
        }

        foreach ($textes_applicables as $id_texte_applicable => $is_active) {
            if (!$is_active) {
                $texte_applicable = $dossierTexteApplicable->find($id_texte_applicable, $id_dossier)->current();
                if ($texte_applicable instanceof Zend_Db_Table_Row_Abstract) {
                    $texte_applicable->delete();
                    if (in_array($type, [2, 3]) && $id_etablissement) {
                        $texte_applicable = $etsTexteApplicable->find($id_texte_applicable, $id_etablissement)->current();
                        if ($texte_applicable instanceof Zend_Db_Table_Row_Abstract) {
                            $texte_applicable->delete();
                        }
                    }
                }
            } elseif (!$dossierTexteApplicable->find($id_texte_applicable, $id_dossier)->current() instanceof Zend_Db_Table_Row_Abstract) {
                $row = $dossierTexteApplicable->createRow();
                $row->ID_TEXTESAPPL = $id_texte_applicable;
                $row->ID_DOSSIER = $id_dossier;
                $row->save();
                if ((self::ID_DOSSIERTYPE_VISITE == $type || self::ID_DOSSIERTYPE_GRPVISITE == $type) && $id_etablissement) {
                    $exist = $etsTexteApplicable->find($id_texte_applicable, $id_etablissement)->current();
                    if (!$exist) {
                        $row = $etsTexteApplicable->createRow();
                        $row->ID_TEXTESAPPL = $id_texte_applicable;
                        $row->ID_ETABLISSEMENT = $id_etablissement;
                        $row->save();
                    }
                }
            }
        }
    }

    /**
     * Ajout d'un contact à un dossier.
     *
     * @param int    $id_dossier
     * @param string $nom
     * @param string $prenom
     * @param int    $id_fonction
     * @param string $societe
     * @param string $fixe
     * @param string $mobile
     * @param string $fax
     * @param string $email
     * @param string $adresse
     * @param string $web
     */
    public function addContact($id_dossier, $nom, $prenom, $id_fonction, $societe, $fixe, $mobile, $fax, $email, $adresse, $web): void
    {
        $DB_informations = new Model_DbTable_UtilisateurInformations();

        $id_contact = $DB_informations->createRow([
            'NOM_UTILISATEURINFORMATIONS' => (string) $nom,
            'PRENOM_UTILISATEURINFORMATIONS' => (string) $prenom,
            'TELFIXE_UTILISATEURINFORMATIONS' => (string) $fixe,
            'TELPORTABLE_UTILISATEURINFORMATIONS' => (string) $mobile,
            'TELFAX_UTILISATEURINFORMATIONS' => (string) $fax,
            'MAIL_UTILISATEURINFORMATIONS' => (string) $email,
            'SOCIETE_UTILISATEURINFORMATIONS' => (string) $societe,
            'WEB_UTILISATEURINFORMATIONS' => (string) $web,
            'OBS_UTILISATEURINFORMATIONS' => (string) $adresse,
            'ID_FONCTION' => (string) $id_fonction,
        ])->save();

        $this->addContactExistant($id_dossier, $id_contact);
    }

    /**
     * Ajout d'un contact existant à un dossier.
     *
     * @param int $id_dossier
     * @param int $id_contact
     */
    public function addContactExistant($id_dossier, $id_contact): void
    {
        $DB_contact = new Model_DbTable_DossierContact();

        $DB_contact->createRow([
            'ID_DOSSIER' => $id_dossier,
            'ID_UTILISATEURINFORMATIONS' => $id_contact,
        ])->save();
    }

    /**
     * Suppression d'un contact.
     *
     * @param int $id_dossier
     * @param int $id_contact
     */
    public function deleteContact($id_dossier, $id_contact): void
    {
        $DB_current = new Model_DbTable_EtablissementContact();
        $DB_informations = new Model_DbTable_UtilisateurInformations();
        $DB_contact = [
            new Model_DbTable_EtablissementContact(),
            new Model_DbTable_DossierContact(),
            new Model_DbTable_GroupementContact(),
            new Model_DbTable_CommissionContact(),
        ];

        // Appartient à d'autre dossier / ets ?
        $exist = false;
        foreach ($DB_contact as $model) {
            if (count($model->fetchAll('ID_UTILISATEURINFORMATIONS = '.$id_contact)->toArray()) > (($model == $DB_current) ? 1 : 0)) {
                $exist = true;
            }
        }

        // Est ce que le contact n'appartient pas à d'autre etablissement ?
        if (!$exist) {
            $DB_current->delete('ID_UTILISATEURINFORMATIONS = '.$id_contact); // Porteuse
            $DB_informations->delete('ID_UTILISATEURINFORMATIONS = '.$id_contact); // Contact
        } else {
            $DB_current->delete('ID_UTILISATEURINFORMATIONS = '.$id_contact.' AND ID_DOSSIER = '.$id_dossier); // Porteuse
        }
    }

    /**
     * Retourne les prescriptions d'un dossier.
     *
     * @param int   $id_dossier
     * @param mixed $type
     *
     * @psalm-return array<int, mixed>
     */
    public function getPrescriptions($id_dossier, $type): array
    {
        /*
            suivant la valeur de $type
            0 = rappel réglementaire
            1 = a l'exploitation
            2 = a l'amélioration
        */
        $dbPrescDossier = new Model_DbTable_PrescriptionDossier();
        $listePrescDossier = $dbPrescDossier->recupPrescDossier($id_dossier, $type);

        $dbPrescDossierAssoc = new Model_DbTable_PrescriptionDossierAssoc();
        $prescriptionArray = [];
        foreach ($listePrescDossier as $ue) {
            $assoc = [];

            if ($ue['ID_PRESCRIPTION_TYPE']) {
                // cas d'une prescription type
                $assoc = $dbPrescDossierAssoc->getPrescriptionTypeAssoc($ue['ID_PRESCRIPTION_TYPE'], $ue['ID_PRESCRIPTION_DOSSIER']);
            } else {
                // cas d'une prescription particulière
                $assoc = $dbPrescDossierAssoc->getPrescriptionDossierAssoc($ue['ID_PRESCRIPTION_DOSSIER']);
            }

            foreach ($assoc as $index => $presc) {
                $assoc[$index]['DATE_LEVEE_FULL'] = Service_Utils_Date::formatDateWithDayName($presc['DATE_LEVEE']);
                $assoc[$index]['ID_DOSSIER_REPRISE'] = $ue['ID_DOSSIER_REPRISE'];
                $assoc[$index]['OBJET_DOSSIER'] = $ue['OBJET_DOSSIER'];
                $assoc[$index]['DATEINSERT_DOSSIER'] = Service_Utils_Date::formatDateWithDayName($ue['DATEINSERT_DOSSIER']);
                $assoc[$index]['LIBELLE_DOSSIERTYPE'] = $ue['LIBELLE_DOSSIERTYPE'];
                $assoc[$index]['LIBELLE_DOSSIERNATURE'] = $ue['LIBELLE_DOSSIERNATURE'];
            }

            if ([] !== $assoc) {
                $prescriptionArray[] = $assoc;
            }
        }

        return $prescriptionArray;
    }

    /**
     * Retourne les détails d'une prescription.
     *
     * @param int $id_prescription
     *
     * @return array
     */
    public function getDetailPrescription($id_prescription)
    {
        // On recherche la ligne correspondante à la prescription
        $db_prescription_dossier = new Model_DbTable_PrescriptionDossier();
        $infos_prescription = $db_prescription_dossier->recupPrescInfos($id_prescription);

        // On va chercher les textes et articles associés à cette prescription
        if (null == $infos_prescription['ID_PRESCRIPTION_TYPE']) {
            $db_prescription_assoc = new Model_DbTable_PrescriptionDossierAssoc();
            $liste_assoc = $db_prescription_assoc->getPrescriptionDossierAssoc($id_prescription);
            $infos_prescription['assoc'] = $liste_assoc;
        } else {
            $dbPrescTypeAssoc = new Model_DbTable_PrescriptionTypeAssoc();

            $liste_assoc = $dbPrescTypeAssoc->getPrescriptionAssoc($infos_prescription['ID_PRESCRIPTION_TYPE']);
            $infos_prescription['assoc'] = $liste_assoc;
            $infos_prescription['LIBELLE_PRESCRIPTION_DOSSIER'] = $liste_assoc[0]['PRESCRIPTIONTYPE_LIBELLE'];
        }

        $infos_prescription['DATE_LEVEE'] = Service_Utils_Date::convertFromMySQL($infos_prescription['DATE_LEVEE']);

        return $infos_prescription;
    }

    /**
     * Sauvegarde une prescription.
     */
    public function savePrescription(array $post): void
    {
        $dbPrescDossier = new Model_DbTable_PrescriptionDossier();
        $dbPrescDossierAssoc = new Model_DbTable_PrescriptionDossierAssoc();

        if ('edit' == $post['action']) {
            $prescEdit = $dbPrescDossier->find($post['id_prescription'])->current();

            if ($prescEdit->TYPE_PRESCRIPTION_DOSSIER != $post['TYPE_PRESCRIPTION_DOSSIER']) {
                $nbPrescription = $dbPrescDossier->recupMaxNumPrescDossier($post['id_dossier'], $post['TYPE_PRESCRIPTION_DOSSIER']);
                $numPrescription = $nbPrescription['maxnum'];
                ++$numPrescription;

                $newCount = true;
            } else {
                $numPrescription = $prescEdit->NUM_PRESCRIPTION_DOSSIER;
                $newCount = false;
            }

            $prescEdit->NUM_PRESCRIPTION_DOSSIER = $numPrescription;
            $prescEdit->LIBELLE_PRESCRIPTION_DOSSIER = $post['PRESCRIPTION_LIBELLE'];
            $prescEdit->TYPE_PRESCRIPTION_DOSSIER = $post['TYPE_PRESCRIPTION_DOSSIER'];
            $prescEdit->DATE_LEVEE = Service_Utils_Date::convertToMySQL($post['DATE_LEVEE']);
            $prescEdit->JUSTIFICATIF_LEVEE = $post['JUSTIFICATIF_LEVEE'];
            $prescEdit->save();

            if ($newCount) {
                // il faut effectuer une nouvelle numérotation des prescriptions du type que l'on abandonne
                $nbPresc = 1;
                $listeExploit = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 0);
                foreach ($listeExploit as $prescDossier) {
                    $prescCount = $dbPrescDossier->find($prescDossier['ID_PRESCRIPTION_DOSSIER'])->current();
                    $prescCount->NUM_PRESCRIPTION_DOSSIER = $nbPresc;
                    $prescCount->save();
                    ++$nbPresc;
                }

                $nbPresc = 1;
                $listeExploit = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 1);
                foreach ($listeExploit as $prescDossier) {
                    $prescCount = $dbPrescDossier->find($prescDossier['ID_PRESCRIPTION_DOSSIER'])->current();
                    $prescCount->NUM_PRESCRIPTION_DOSSIER = $nbPresc;
                    $prescCount->save();
                    ++$nbPresc;
                }

                $listeAmelio = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 2);
                foreach ($listeAmelio as $prescDossier) {
                    $prescCount = $dbPrescDossier->find($prescDossier['ID_PRESCRIPTION_DOSSIER'])->current();
                    $prescCount->NUM_PRESCRIPTION_DOSSIER = $nbPresc;
                    $prescCount->save();
                    ++$nbPresc;
                }
            }

            $prescAssocDelete = $dbPrescDossierAssoc->getAdapter()->quoteInto('ID_PRESCRIPTION_DOSSIER = ?', $prescEdit->ID_PRESCRIPTION_DOSSIER);
            $dbPrescDossierAssoc->delete($prescAssocDelete);

            $nombreAssoc = count($post['texte']);
            for ($i = 0; $i < $nombreAssoc; ++$i) {
                $newAssoc = $dbPrescDossierAssoc->createRow();
                $newAssoc->NUM_PRESCRIPTION_DOSSIERASSOC = $i + 1;
                $newAssoc->ID_PRESCRIPTION_DOSSIER = $post['id_prescription'];
                if (null != $post['texte'][$i] && '' != $post['texte'][$i] && 0 != $post['texte'][$i]) {
                    $newAssoc->ID_TEXTE = $post['texte'][$i];
                } else {
                    $newAssoc->ID_TEXTE = 1;
                }

                if (null != $post['article'][$i] && '' != $post['article'][$i] && 0 != $post['article'][$i]) {
                    $newAssoc->ID_ARTICLE = $post['article'][$i];
                } else {
                    $newAssoc->ID_ARTICLE = 1;
                }

                $newAssoc->save();
            }
        } elseif ('edit-type' == $post['action']) {
            $prescEdit = $dbPrescDossier->find($post['id_prescription'])->current();

            if ($prescEdit->TYPE_PRESCRIPTION_DOSSIER != $post['TYPE_PRESCRIPTION_DOSSIER']) {
                $nbPrescription = $dbPrescDossier->recupMaxNumPrescDossier($post['id_dossier'], $post['TYPE_PRESCRIPTION_DOSSIER']);
                $numPrescription = $nbPrescription['maxnum'];
                ++$numPrescription;
            } else {
                $numPrescription = $prescEdit->NUM_PRESCRIPTION_DOSSIER;
            }

            $prescEdit->LIBELLE_PRESCRIPTION_DOSSIER = $post['PRESCRIPTION_LIBELLE'];
            $prescEdit->ID_PRESCRIPTION_TYPE = null;
            $prescEdit->NUM_PRESCRIPTION_DOSSIER = $numPrescription;
            $prescEdit->TYPE_PRESCRIPTION_DOSSIER = $post['TYPE_PRESCRIPTION_DOSSIER'];
            $prescEdit->DATE_LEVEE = Service_Utils_Date::convertToMySQL($post['DATE_LEVEE']);
            $prescEdit->JUSTIFICATIF_LEVEE = $post['JUSTIFICATIF_LEVEE'];
            $prescEdit->save();

            $nombreAssoc = count($post['texte']);
            for ($i = 0; $i < $nombreAssoc; ++$i) {
                $newAssoc = $dbPrescDossierAssoc->createRow();
                $newAssoc->NUM_PRESCRIPTION_DOSSIERASSOC = $i + 1;
                $newAssoc->ID_PRESCRIPTION_DOSSIER = $post['id_prescription'];
                $newAssoc->ID_TEXTE = $post['texte'][$i];
                $newAssoc->ID_ARTICLE = $post['article'][$i];
                $newAssoc->save();
            }

            $nbPresc = 1;
            $listeExploit = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 1);
            foreach ($listeExploit as $prescDossier) {
                $prescCount = $dbPrescDossier->find($prescDossier['ID_PRESCRIPTION_DOSSIER'])->current();
                $prescCount->NUM_PRESCRIPTION_DOSSIER = $nbPresc;
                $prescCount->save();
                ++$nbPresc;
            }

            $listeAmelio = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 2);
            foreach ($listeAmelio as $prescDossier) {
                $prescCount = $dbPrescDossier->find($prescDossier['ID_PRESCRIPTION_DOSSIER'])->current();
                $prescCount->NUM_PRESCRIPTION_DOSSIER = $nbPresc;
                $prescCount->save();
                ++$nbPresc;
            }
        } elseif ('presc-add' == $post['action']) {
            $nbPrescription = $dbPrescDossier->recupMaxNumPrescDossier($post['id_dossier'], $post['TYPE_PRESCRIPTION_DOSSIER']);
            $numPrescription = $nbPrescription['maxnum'];
            ++$numPrescription;

            $prescEdit = $dbPrescDossier->createRow();
            $prescEdit->ID_DOSSIER = $post['id_dossier'];
            $prescEdit->NUM_PRESCRIPTION_DOSSIER = $numPrescription;
            $prescEdit->LIBELLE_PRESCRIPTION_DOSSIER = $post['PRESCRIPTION_LIBELLE'];
            $prescEdit->TYPE_PRESCRIPTION_DOSSIER = $post['TYPE_PRESCRIPTION_DOSSIER'];
            $prescEdit->DATE_LEVEE = Service_Utils_Date::convertToMySQL($post['DATE_LEVEE']);
            $prescEdit->JUSTIFICATIF_LEVEE = $post['JUSTIFICATIF_LEVEE'];
            $prescEdit->save();

            $nombreAssoc = count($post['texte']);
            for ($i = 0; $i < $nombreAssoc; ++$i) {
                $newAssoc = $dbPrescDossierAssoc->createRow();
                $newAssoc->NUM_PRESCRIPTION_DOSSIERASSOC = $i + 1;
                $newAssoc->ID_PRESCRIPTION_DOSSIER = $prescEdit->ID_PRESCRIPTION_DOSSIER;
                if (null != $post['texte'][$i] && '' != $post['texte'][$i] && 0 != $post['texte'][$i]) {
                    $newAssoc->ID_TEXTE = $post['texte'][$i];
                } else {
                    $newAssoc->ID_TEXTE = 1;
                }

                if (null != $post['article'][$i] && '' != $post['article'][$i] && 0 != $post['article'][$i]) {
                    $newAssoc->ID_ARTICLE = $post['article'][$i];
                } else {
                    $newAssoc->ID_ARTICLE = 1;
                }

                $newAssoc->save();
            }

            $nbPresc = 1;
            $listeExploit = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 1);
            foreach ($listeExploit as $prescDossier) {
                $prescCount = $dbPrescDossier->find($prescDossier['ID_PRESCRIPTION_DOSSIER'])->current();
                $prescCount->NUM_PRESCRIPTION_DOSSIER = $nbPresc;
                $prescCount->save();
                ++$nbPresc;
            }

            $listeAmelio = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 2);
            foreach ($listeAmelio as $prescDossier) {
                $prescCount = $dbPrescDossier->find($prescDossier['ID_PRESCRIPTION_DOSSIER'])->current();
                $prescCount->NUM_PRESCRIPTION_DOSSIER = $nbPresc;
                $prescCount->save();
                ++$nbPresc;
            }
        }
    }

    public function copyPrescriptionDossier($listePrescription, $idDossier, ?int $idDossierInitial = null): void
    {
        $dbPrescDossier = new Model_DbTable_PrescriptionDossier();
        $dbPrescDossierAssoc = new Model_DbTable_PrescriptionDossierAssoc();

        foreach ($listePrescription as $val => $ue) {
            if (null !== $ue[0]['DATE_LEVEE']) {
                continue;
            }

            if (null !== $ue[0]['JUSTIFICATIF_LEVEE'] && '' !== $ue[0]['JUSTIFICATIF_LEVEE']) {
                continue;
            }

            if (isset($ue[0]['ID_PRESCRIPTION_TYPE']) && null != $ue[0]['ID_PRESCRIPTION_TYPE']) {
                // cas d'une prescription type
                $assoc = $dbPrescDossierAssoc->getPrescriptionTypeAssoc($ue[0]['ID_PRESCRIPTION_TYPE'], $ue[0]['ID_PRESCRIPTION_DOSSIER']);
            } else {
                // cas d'une prescription particulière
                $assoc = $dbPrescDossierAssoc->getPrescriptionDossierAssoc($ue[0]['ID_PRESCRIPTION_DOSSIER']);
            }

            $newPresc = $dbPrescDossier->createRow();
            $newPresc->ID_DOSSIER = $idDossier;
            $newPresc->NUM_PRESCRIPTION_DOSSIER = $ue[0]['NUM_PRESCRIPTION_DOSSIER'];
            $newPresc->ID_PRESCRIPTION_TYPE = $ue[0]['ID_PRESCRIPTION_TYPE'];
            $newPresc->LIBELLE_PRESCRIPTION_DOSSIER = $ue[0]['LIBELLE_PRESCRIPTION_DOSSIER'];
            $newPresc->TYPE_PRESCRIPTION_DOSSIER = $ue[0]['TYPE_PRESCRIPTION_DOSSIER'];

            if (null !== $idDossierInitial) {
                $newPresc->ID_DOSSIER_REPRISE = $idDossierInitial;
            }

            $newPresc->save();

            foreach ($assoc as $val) {
                if (null == $val['ID_PRESCRIPTION_TYPE']) {
                    $newAssoc = $dbPrescDossierAssoc->createRow();
                    $newAssoc->NUM_PRESCRIPTION_DOSSIERASSOC = $val['NUM_PRESCRIPTION_DOSSIERASSOC'];
                    $newAssoc->ID_PRESCRIPTION_DOSSIER = $newPresc->ID_PRESCRIPTION_DOSSIER;
                    $newAssoc->ID_TEXTE = $val['ID_TEXTE'];
                    $newAssoc->ID_ARTICLE = $val['ID_ARTICLE'];
                    $newAssoc->save();
                }
            }
        }
    }

    /**
     * Supprime une prescription.
     */
    public function deletePrescription(array $post): void
    {
        $dbPrescDossier = new Model_DbTable_PrescriptionDossier();
        $dbPrescDossierAssoc = new Model_DbTable_PrescriptionDossierAssoc();

        $prescToDelete = $dbPrescDossier->find($post['id_prescription'])->current();

        $prescAssocDelete = $dbPrescDossierAssoc->getAdapter()->quoteInto('ID_PRESCRIPTION_DOSSIER = ?', $prescToDelete->ID_PRESCRIPTION_DOSSIER);
        $dbPrescDossierAssoc->delete($prescAssocDelete);
        $prescToDelete->delete();

        $prescriptionDossier = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 0);
        $num = 1;
        foreach ($prescriptionDossier as $ue) {
            $prescChangePlace = $dbPrescDossier->find($ue['ID_PRESCRIPTION_DOSSIER'])->current();
            $prescChangePlace->NUM_PRESCRIPTION_DOSSIER = $num;
            $prescChangePlace->save();
            ++$num;
        }

        $prescriptionDossier = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 1);
        $num = 1;
        foreach ($prescriptionDossier as $ue) {
            $prescChangePlace = $dbPrescDossier->find($ue['ID_PRESCRIPTION_DOSSIER'])->current();
            $prescChangePlace->NUM_PRESCRIPTION_DOSSIER = $num;
            $prescChangePlace->save();
            ++$num;
        }

        $prescriptionDossier = $dbPrescDossier->recupPrescDossier($post['id_dossier'], 2);
        foreach ($prescriptionDossier as $ue) {
            $prescChangePlace = $dbPrescDossier->find($ue['ID_PRESCRIPTION_DOSSIER'])->current();
            $prescChangePlace->NUM_PRESCRIPTION_DOSSIER = $num;
            $prescChangePlace->save();
            ++$num;
        }
    }

    public function savePrescriptionRegl($idDossier, $prescriptionRegl): void
    {
        $dbPrescDossier = new Model_DbTable_PrescriptionDossier();
        $dbPrescDossierAssoc = new Model_DbTable_PrescriptionDossierAssoc();
        $j = 0;
        foreach ($prescriptionRegl as $ue) {
            $prescEdit = $dbPrescDossier->createRow();
            $prescEdit->ID_DOSSIER = $idDossier;
            if (array_key_exists(0, $ue) && array_key_exists('PRESCRIPTIONREGL_LIBELLE', $ue[0])) {
                $prescEdit->LIBELLE_PRESCRIPTION_DOSSIER = $ue[0]['PRESCRIPTIONREGL_LIBELLE'];
            }

            $prescEdit->TYPE_PRESCRIPTION_DOSSIER = 0;
            $prescEdit->NUM_PRESCRIPTION_DOSSIER = $j++;
            $prescEdit->save();

            $nombreAssoc = count($ue);
            for ($i = 0; $i < $nombreAssoc; ++$i) {
                $newAssoc = $dbPrescDossierAssoc->createRow();
                $newAssoc->NUM_PRESCRIPTION_DOSSIERASSOC = $i + 1;
                $newAssoc->ID_PRESCRIPTION_DOSSIER = $prescEdit->ID_PRESCRIPTION_DOSSIER;
                $newAssoc->ID_TEXTE = $ue[$i]['ID_TEXTE'];
                $newAssoc->ID_ARTICLE = $ue[$i]['ID_ARTICLE'];
                $newAssoc->save();
            }
        }
    }

    public function changePosPrescription($tabId): void
    {
        $DBprescDossier = new Model_DbTable_PrescriptionDossier();

        $numPresc = 1;
        foreach ($tabId as $idPrescDoss) {
            if ($idPrescDoss) {
                $updatePrescDossier = $DBprescDossier->find($idPrescDoss)->current();
                $updatePrescDossier->NUM_PRESCRIPTION_DOSSIER = $numPresc;
                $updatePrescDossier->save();
                ++$numPresc;
            }
        }
    }

    /**
     * @param int        $id_etablissement
     * @param null|mixed $id_dossier
     */
    public function getEtabInfos($id_dossier = null, $id_etablissement = null)
    {
        $DBdossier = new Model_DbTable_Dossier();

        if (null != $id_etablissement) {
            $DBetab = new Model_DbTable_Etablissement();
            $etabTab = $DBetab->getInformations($id_etablissement);

            $this->etablissement = $etabTab->toArray();

            $DbAdresse = new Model_DbTable_EtablissementAdresse();
            $this->etablissement['adresses'] = $DbAdresse->get($id_etablissement);

            $service_etablissement = new Service_Etablissement();
            $etablissementInfos = $service_etablissement->get($id_etablissement);
            if (null != $etablissementInfos['general']['ID_DOSSIER_DONNANT_AVIS']) {
                $etablissementInfos['avisExploitation'] = $DBdossier->getAvisDossier($etablissementInfos['general']['ID_DOSSIER_DONNANT_AVIS']);
            }

            $this->etablissement['etablissementInfos'] = $etablissementInfos;

            if (null != $this->etablissement['etablissementInfos']['general']['ID_DOSSIER_DONNANT_AVIS']) {
                $avisExploitationEtab = $DBdossier->getAvisDossier($this->etablissement['etablissementInfos']['general']['ID_DOSSIER_DONNANT_AVIS']);
                $this->etablissement['avisExploitationEtab'] = $avisExploitationEtab['AVIS_DOSSIER'];
            } else {
                $this->etablissement['avisExploitationEtab'] = 3;
            }

            return $this->etablissement;
        }

        if (null != $id_dossier) {
            $tabEtablissement = $DBdossier->getEtablissementDossier((int) $id_dossier);
            $this->listeEtablissement = $tabEtablissement;

            $service_etablissement = new Service_Etablissement();
            foreach ($this->listeEtablissement as $val => $ue) {
                $etablissementInfos = $service_etablissement->get($ue['ID_ETABLISSEMENT']);
                if (null != $etablissementInfos['general']['ID_DOSSIER_DONNANT_AVIS']) {
                    $this->listeEtablissement[$val]['avisExploitation'] = $DBdossier->getAvisDossier($etablissementInfos['general']['ID_DOSSIER_DONNANT_AVIS']);
                }

                $this->listeEtablissement[$val]['infosEtab'] = $etablissementInfos;
            }

            return $this->listeEtablissement;
        }

        return null;
    }

    public function isDossierDonnantAvis($dossier, $idNature): bool
    {
        return
            // Cas d'une étude uniquement dans le cas d'une levée de reserve
            in_array($idNature, [19, 7, 17, 16]) && $dossier->DATECOMM_DOSSIER
            // Cas d'une viste uniquement dans le cas d'une VP, inopinée, avant ouverture ou controle
            || in_array($idNature, [21, 23, 24, 47]) && $dossier->DATEVISITE_DOSSIER
            // Cas d'un groupe deviste uniquement dans le cas d'une VP, inopinée, avant ouverture ou controle
            || in_array($idNature, [26, 28, 29, 48]) && $dossier->DATECOMM_DOSSIER;
    }

    public function getDateDossier($dossier): Zend_Date
    {
        $date = $dossier->DATEINSERT_DOSSIER;

        if (1 == $dossier->TYPE_DOSSIER || self::ID_DOSSIERTYPE_GRPVISITE == $dossier->TYPE_DOSSIER) {
            if (null != $dossier->DATECOMM_DOSSIER && '' != $dossier->DATECOMM_DOSSIER) {
                $date = $dossier->DATECOMM_DOSSIER;
            }
        } elseif (self::ID_DOSSIERTYPE_VISITE == $dossier->TYPE_DOSSIER) {
            if (null == $dossier->DATEVISITE_DOSSIER) {
                return new Zend_Date($date, Zend_Date::DATES);
            }

            if ('' == $dossier->DATEVISITE_DOSSIER) {
                return new Zend_Date($date, Zend_Date::DATES);
            }

            $date = $dossier->DATEVISITE_DOSSIER;

            return new Zend_Date($date, Zend_Date::DATES);
        }

        return new Zend_Date($date, Zend_Date::DATES);
    }

    // Retrouve l'avis du dernier dossier donnant avis pour l'établissement courant uniquement
    public function saveDossierDonnantAvisCurrentEtab(array $dernierDossierDonnantAvis, $etab, $cache): Zend_Db_Table_Row_Abstract
    {
        $dbEtab = new Model_DbTable_Etablissement();
        $etab = $dbEtab->find($etab['ID_ETABLISSEMENT'])->current();

        $etab->ID_DOSSIER_DONNANT_AVIS = $dernierDossierDonnantAvis['ID_DOSSIER'];
        $etab->save();

        $cache->remove(sprintf('etablissement_id_%d', $etab['ID_ETABLISSEMENT']));

        return $etab;
    }

    /**
     * @psalm-return array<int, Zend_Db_Table_Row_Abstract>
     *
     * @param mixed $nouveauDossier
     * @param mixed $listeEtab
     * @param mixed $cache
     * @param mixed $repercuterAvis
     *
     * @return Zend_Db_Table_Row_Abstract[]
     */
    public function saveDossierDonnantAvis($nouveauDossier, $listeEtab, $cache, $repercuterAvis = false): array
    {
        $dbEtab = new Model_DbTable_Etablissement();
        $DBdossier = new Model_DbTable_Dossier();
        $service_etablissement = new Service_Etablissement();

        $updatedEtab = [];

        foreach ($listeEtab as $ue) {
            $etabToEdit = $dbEtab->find($ue['ID_ETABLISSEMENT'])->current();
            // Avant la mise à jour du champ ID_DOSSIER_DONNANT_AVIS on s'assure que la date de l'avis est plus récente
            if (property_exists($etabToEdit, 'ID_DOSSIER_DONNANT_AVIS') && null !== $etabToEdit->ID_DOSSIER_DONNANT_AVIS) {
                $dossierAncienAvis = $DBdossier->find($etabToEdit->ID_DOSSIER_DONNANT_AVIS)->current();

                $dateAncienAvis = $this->getDateDossier($dossierAncienAvis);
                $dateNewAvis = $this->getDateDossier($nouveauDossier);

                $MAJEtab = $dateNewAvis > $dateAncienAvis || $dateNewAvis == $dateAncienAvis ? 1 : 0;
            } else {
                $MAJEtab = 1;
            }

            if (1 == $MAJEtab) {
                $etabToEdit->ID_DOSSIER_DONNANT_AVIS = $nouveauDossier->ID_DOSSIER;
                $etabToEdit->save();
                $updatedEtab[] = $etabToEdit;

                if ($repercuterAvis) {
                    $etablissementInfos = $service_etablissement->get($ue['ID_ETABLISSEMENT']);
                    foreach ($etablissementInfos['etablissement_lies'] as $etabEnfant) {
                        if ('2' == $etabEnfant['ID_STATUT']) {
                            $etabToEdit = $dbEtab->find($etabEnfant['ID_ETABLISSEMENT'])->current();
                            $etabToEdit->ID_DOSSIER_DONNANT_AVIS = $nouveauDossier->ID_DOSSIER;
                            $etabToEdit->save();
                            $updatedEtab[] = $etabToEdit;
                        }
                    }
                }
            }
        }

        foreach ($updatedEtab as $etablissement) {
            $cache->remove(sprintf('etablissement_id_%d', $etablissement['ID_ETABLISSEMENT']));
            if ($parent = $dbEtab->getParent($etablissement['ID_ETABLISSEMENT'])) {
                $cache->remove(sprintf('etablissement_id_%d', $parent['ID_ETABLISSEMENT']));
            }
        }

        return $updatedEtab;
    }

    public function getCommission($idDossier)
    {
        $dbDossier = new Model_DbTable_Dossier();

        return $dbDossier->getCommissionV2($idDossier);
    }

    public function delete($idDossier, $date = null, $uniqueEtab = false): void
    {
        if (!$date) {
            $date = new DateTime();
        }

        $DB_dossier = new Model_DbTable_Dossier();

        $dossier = $DB_dossier->find($idDossier)->current();
        $deleteDossier = true;
        if ($uniqueEtab) {
            $DB_etsDossier = new Model_DbTable_EtablissementDossier();
            $deleteDossier = count($DB_etsDossier->getEtablissementListe($idDossier)) <= 1;
        }

        if ($deleteDossier) {
            $dossier->DATESUPPRESSION_DOSSIER = $date->format('Y-m-d');
            $idDeleteBy = Zend_Auth::getInstance()->getIdentity()['ID_UTILISATEUR'];
            $dossier->DELETED_BY = $idDeleteBy;
            // suppression de la date de passage en commission
            $dbAffectDossier = new Model_DbTable_DossierAffectation();
            $dbAffectDossier->deleteDateDossierAffect($idDossier);

            $dossier->save();
        }
    }

    public function deleteByEtab($idEtablissement): void
    {
        $date = new DateTime();
        $DB_dossier = new Model_DbTable_Dossier();

        $dossiers = $DB_dossier->getDossiersEtab($idEtablissement);

        foreach ($dossiers as $dossier) {
            $this->delete($dossier['ID_DOSSIER'], $date, true);
        }
    }

    public function getPreventionniste($idDossier)
    {
        $DB_prev = new Model_DbTable_DossierPreventionniste();

        return $DB_prev->getPrevDossier($idDossier);
    }

    public function hasAvisDerogation(int $idDossier): bool
    {
        $modelDossier = new Model_DbTable_Dossier();

        $result = $modelDossier->getListAvisDerogationsFromDossier($idDossier);

        return !empty($result);
    }

    public function retablirDossier($idDossier): void
    {
        $DB_dossier = new Model_DbTable_Dossier();
        $dossier = $DB_dossier->find($idDossier)->current();
        $dossier->DATESUPPRESSION_DOSSIER = null;
        $dossier->DELETED_BY = null;
        $dossier->save();
    }

    public function getNombreNouvellesPiecesJointes(int $idDossier): int
    {
        $modelDossier = new Model_DbTable_Dossier();
        $serviceNotification = new Service_Notification();

        return $modelDossier->getNombreNouvellesPiecesJointes($idDossier, $serviceNotification->getLastPageVisitDate(Service_Notification::DOSSIER_PIECES_SESSION_NAMESPACE));
    }

    /**
     * Vérifie si un dossier Plat'AU à de nouvelles pièces.
     */
    public function hasNewPj(array $dossier): bool
    {
        $serviceNotification = new Service_Notification();
        $modelPj = new Model_DbTable_PieceJointe();
        $pjs = $modelPj->affichagePieceJointe('dossierpj', 'dossierpj.ID_DOSSIER', $dossier['ID_DOSSIER']);

        foreach ($pjs as $pj) {
            if (!$serviceNotification->isNew($pj, Service_Notification::DASHBOARD_DOSSIER_SESSION_NAMESPACE)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
