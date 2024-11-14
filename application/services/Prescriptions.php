<?php

class Service_Prescriptions
{
    // GESTION PRESCRIPTION TYPE

    /**
     * @psalm-return array<int, mixed>
     *
     * @param mixed $categorie
     * @param mixed $texte
     * @param mixed $article
     */
    public function showPrescriptionType($categorie, $texte, $article): array
    {
        $dbPrescType = new Model_DbTable_PrescriptionType();
        $listePrescType = $dbPrescType->getPrescriptionType($categorie, $texte, $article);

        $dbPrescAssoc = new Model_DbTable_PrescriptionTypeAssoc();
        $prescriptionArray = [];

        foreach ($listePrescType as $ue) {
            $assoc = $dbPrescAssoc->getPrescriptionAssoc($ue['ID_PRESCRIPTIONTYPE']);

            if ([] !== $assoc) {
                $prescriptionArray[] = $assoc;
            }
        }

        return $prescriptionArray;
    }

    public function getCategories()
    {
        $dbPrescriptionCat = new Model_DbTable_PrescriptionCat();

        return $dbPrescriptionCat->recupPrescriptionCat();
    }

    public function getPrescriptionTypeDetail($idPrescType)
    {
        $dbPrescTypeAssoc = new Model_DbTable_PrescriptionTypeAssoc();

        return $dbPrescTypeAssoc->getPrescriptionAssoc($idPrescType);
    }

    public function savePrescriptionType(array $post, $idPrescriptionType = null)
    {
        $dbPrescType = new Model_DbTable_PrescriptionType();
        $dbPresTypeAssoc = new Model_DbTable_PrescriptionTypeAssoc();

        if (null == $idPrescriptionType) {
            $prescType = $dbPrescType->createRow();
        } else {
            $prescType = $dbPrescType->find($idPrescriptionType)->current();
        }

        $prescType->PRESCRIPTIONTYPE_LIBELLE = $post['PRESCRIPTIONTYPE_LIBELLE'];

        $prescType->PRESCRIPTIONTYPE_CATEGORIE = (int) $post['PRESCRIPTIONTYPE_CATEGORIE'];
        $prescType->PRESCRIPTIONTYPE_TEXTE = (int) $post['PRESCRIPTIONTYPE_TEXTE'];
        $prescType->PRESCRIPTIONTYPE_ARTICLE = (int) $post['PRESCRIPTIONTYPE_ARTICLE'];

        $prescType->save();

        if (null != $idPrescriptionType) {
            $prescTypeAssocDelete = $dbPresTypeAssoc->getAdapter()->quoteInto('ID_PRESCRIPTIONTYPE = ?', $post['ID_PRESCRIPTIONTYPE']);
            $dbPresTypeAssoc->delete($prescTypeAssocDelete);
        }

        $nombreAssoc = count($post['texte']);
        for ($i = 0; $i < $nombreAssoc; ++$i) {
            $newAssoc = $dbPresTypeAssoc->createRow();
            $newAssoc->ID_PRESCRIPTIONTYPE = $prescType->ID_PRESCRIPTIONTYPE;
            $newAssoc->NUM_PRESCRIPTIONASSOC = $i + 1;

            $texe = 0 == $post['texte'][$i] || '' == $post['texte'][$i] ? 1 : $post['texte'][$i];

            $newAssoc->ID_TEXTE = $texe;
            $article = 0 == $post['article'][$i] || '' == $post['article'][$i] ? 1 : $post['article'][$i];

            $newAssoc->ID_ARTICLE = $article;
            $newAssoc->save();
        }

        return $prescType->ID_PRESCRIPTIONTYPE;
    }

    // GESTION DES TEXTES
    public function getTextesListe()
    {
        $dbPrescTextes = new Model_DbTable_PrescriptionTexteListe();

        return $dbPrescTextes->getAllTextes();
    }

    // FIN getTextesListe

    public function getTexte($id_texte)
    {
        $dbPrescTextes = new Model_DbTable_PrescriptionTexteListe();

        return $dbPrescTextes->getTexte($id_texte);
    }

    // FIN getTexte

    public function saveTexte(array $post, $idTexte = null): void
    {
        $dbPrescTextes = new Model_DbTable_PrescriptionTexteListe();
        if (null == $idTexte) {
            $texte = $dbPrescTextes->createRow();
            $texte->LIBELLE_TEXTE = $post['LIBELLE_TEXTE'];
            $texte->VISIBLE_TEXTE = $post['VISIBLE_TEXTE'];
            $texte->save();
        } else {
            $texte = $dbPrescTextes->find($idTexte)->current();
            $texte->LIBELLE_TEXTE = $post['LIBELLE_TEXTE'];
            $texte->VISIBLE_TEXTE = $post['VISIBLE_TEXTE'];
            $texte->save();
        }
    }

    // FIN saveTexte

    public function replaceTexte($newId, $oldId): void
    {
        $dbPrescTextes = new Model_DbTable_PrescriptionTexteListe();
        if ('' == $newId) {
            return;
        }

        if ('' == $oldId) {
            return;
        }

        $dbPrescTextes->replace($newId, $oldId);
    }

    // GESTION DES ARTICLES
    public function getArticlesListe()
    {
        $dbPrescArticles = new Model_DbTable_PrescriptionArticleListe();

        return $dbPrescArticles->getAllArticles();
    }

    // FIN getArticlesListe

    public function getArticle($id_article)
    {
        $dbPrescArticles = new Model_DbTable_PrescriptionArticleListe();

        return $dbPrescArticles->getArticle($id_article);
    }

    // FIN getArticle

    public function saveArticle(array $post, $idArticle = null): void
    {
        $dbPrescArticles = new Model_DbTable_PrescriptionArticleListe();
        if (null == $idArticle) {
            $texte = $dbPrescArticles->createRow();
            $texte->LIBELLE_ARTICLE = $post['LIBELLE_ARTICLE'];
            $texte->VISIBLE_ARTICLE = $post['VISIBLE_ARTICLE'];
            $texte->save();
        } else {
            $texte = $dbPrescArticles->find($idArticle)->current();
            $texte->LIBELLE_ARTICLE = $post['LIBELLE_ARTICLE'];
            $texte->VISIBLE_ARTICLE = $post['VISIBLE_ARTICLE'];
            $texte->save();
        }
    }

    // FIN saveArticle

    public function replaceArticle($newId, $oldId): void
    {
        $dbPrescArticles = new Model_DbTable_PrescriptionArticleListe();
        if ('' == $newId) {
            return;
        }

        if ('' == $oldId) {
            return;
        }

        $dbPrescArticles->replace($newId, $oldId);
    }

    // GESTION DES PRESCRIPTIONS
    public function savePrescription(array $post, $idPrescription = null): void
    {
        $dbPrescRegl = new Model_DbTable_PrescriptionRegl();
        $dbPrescReglAssoc = new Model_DbTable_PrescriptionReglAssoc();

        $prescRegl = null == $idPrescription ? $dbPrescRegl->createRow() : $dbPrescRegl->find($idPrescription)->current();

        $prescRegl->PRESCRIPTIONREGL_LIBELLE = $post['PRESCRIPTION_LIBELLE'];
        $prescRegl->PRESCRIPTIONREGL_TYPE = $post['PRESCRIPTIONREGL_TYPE'];
        $prescRegl->PRESCRIPTIONREGL_VISIBLE = $post['PRESCRIPTIONREGL_VISIBLE'];
        $prescRegl->save();

        if (null != $idPrescription) {
            $prescAssocDelete = $dbPrescReglAssoc->getAdapter()->quoteInto('ID_PRESCRIPTIONREGL = ?', $post['idPrescription']);
            $dbPrescReglAssoc->delete($prescAssocDelete);
        }

        $nombreAssoc = count($post['texte']);
        for ($i = 0; $i < $nombreAssoc; ++$i) {
            $newAssoc = $dbPrescReglAssoc->createRow();
            $newAssoc->ID_PRESCRIPTIONREGL = $prescRegl->ID_PRESCRIPTIONREGL;
            $newAssoc->NUM_PRESCRIPTIONASSOC = $i + 1;
            $texe = 0 == $post['texte'][$i] || '' == $post['texte'][$i] ? 1 : $post['texte'][$i];
            $newAssoc->ID_TEXTE = $texe;
            $article = 0 == $post['article'][$i] || '' == $post['article'][$i] ? 1 : $post['article'][$i];
            $newAssoc->ID_ARTICLE = $article;
            $newAssoc->save();
        }
    }

    // FIN savePrescription

    /**
     * @psalm-return array<int, mixed>
     *
     * @param mixed      $type
     * @param null|mixed $mode
     */
    public function getPrescriptions($type, $mode = null): array
    {
        $dbPrescRegl = new Model_DbTable_PrescriptionRegl();
        $listePrescDossier = $dbPrescRegl->recupPrescRegl($type, $mode);

        $dbPrescReglAssoc = new Model_DbTable_PrescriptionReglAssoc();

        $prescriptionArray = [];
        foreach ($listePrescDossier as $ue) {
            $assoc = $dbPrescReglAssoc->getPrescriptionReglAssoc($ue['ID_PRESCRIPTIONREGL']);
            $prescriptionArray[] = $assoc;
        }

        return $prescriptionArray;
    }

    // FIN getPrescriptions

    /**
     * @param string $type
     * @param mixed  $idPrescription
     */
    public function getPrescriptionInfo($idPrescription, $type)
    {
        if ('rappel-reg' == $type) {
            $dbPrescAssoc = new Model_DbTable_PrescriptionReglAssoc();

            return $dbPrescAssoc->getPrescriptionReglAssoc($idPrescription);
        }

        return null;
    }

    // FIN getPrescriptionInfo

    public function setOrder($data, $type): void
    {
        if ('prescriptionType' == $type) {
            $dbPrescType = new Model_DbTable_PrescriptionType();
            foreach ($data as $num => $presc) {
                $prescType = $dbPrescType->find($presc)->current();
                $prescType->PRESCRIPTIONTYPE_NUM = $num;
                $prescType->save();
            }
        } elseif ('categorie' == $type) {
            $dbPrescCat = new Model_DbTable_PrescriptionCat();
            foreach ($data as $num => $cat) {
                $categorie = $dbPrescCat->find($cat)->current();
                $categorie->NUM_PRESCRIPTION_CAT = $num;
                $categorie->save();
            }
        } elseif ('texte' == $type) {
            $dbPrescTexte = new Model_DbTable_PrescriptionTexte();
            foreach ($data as $num => $texte) {
                $categorie = $dbPrescTexte->find($texte)->current();
                $categorie->NUM_PRESCRIPTIONTEXTE = $num;
                $categorie->save();
            }
        } elseif ('article' == $type) {
            $dbPrescArticle = new Model_DbTable_PrescriptionArticle();
            foreach ($data as $num => $article) {
                $categorie = $dbPrescArticle->find($article)->current();
                $categorie->NUM_PRESCRIPTIONARTICLE = $num;
                $categorie->save();
            }
        }
    }

    // FIN setOrder
} // FIN SERVICE
