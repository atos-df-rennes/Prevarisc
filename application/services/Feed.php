<?php

class Service_Feed
{
    /**
     * Récupération d'un flux de message pour un groupe d'utilisateurs.
     *
     * int $id_groupe
     *
     * @param null|int $count
     * @param mixed    $id_group
     *
     * @return array
     */
    public function get($id_group, $count = 5)
    {
        $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));

        $select->from('news')
            ->join('newsgroupe', 'news.ID_NEWS = newsgroupe.ID_NEWS', null)
            ->join('utilisateur', 'news.ID_UTILISATEUR = utilisateur.ID_UTILISATEUR')
            ->join('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS')
            ->where('newsgroupe.ID_GROUPE = ?', $id_group)
            ->group('ID_NEWS')
            ->order('ID_NEWS DESC')
            ->limit($count)
        ;

        return $select->query()->fetchAll();
    }

    /**
     * @param null|int $count
     * @param mixed    $user
     *
     * @return array
     */
    public function getFeeds($user, $count = 5, $getCount = false)
    {
        $select = new Zend_Db_Select(Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('db'));

        if($getCount){
            $select->from('news', ['COUNT(*)']);
        }else{
            $select->from('news');
        }
        $select->join('newsgroupe', 'news.ID_NEWS = newsgroupe.ID_NEWS', null)
            ->join('utilisateur', 'news.ID_UTILISATEUR = utilisateur.ID_UTILISATEUR')
            ->join('utilisateurinformations', 'utilisateurinformations.ID_UTILISATEURINFORMATIONS = utilisateur.ID_UTILISATEURINFORMATIONS')
            ->where('newsgroupe.ID_GROUPE = ?', $user['group']['ID_GROUPE'])
            ->group('news.ID_NEWS')
            ->order('news.ID_NEWS DESC')
            ->limit($count)
        ;

        return $select->query()->fetchAll();
    }

    /**
     * Ajout d'un message.
     *
     * @param int    $type
     * @param string $message
     * @param int    $author
     */
    public function addMessage($type, $message, $author, array $confidentialite)
    {
        $model = new Model_DbTable_News();
        $model_groupe = new Model_DbTable_NewsGroupe();

        $id_news = $model->createRow([
            'ID_NEWS' => time(),
            'TYPE_NEWS' => $type,
            'TEXTE_NEWS' => $message,
            'ID_UTILISATEUR' => $author,
        ])->save();

        // Ajout des destinataires du message
        foreach ($confidentialite as $value) {
            $model_groupe->createRow([
                'ID_NEWS' => $id_news,
                'ID_GROUPE' => $value,
            ])->save();
        }

        return $id_news;
    }

    /**
     * Suppression d'un message.
     *
     * @param int $id_message
     */
    public function deleteMessage($id_message)
    {
        $model = new Model_DbTable_News();
        $model->find($id_message)->current()->delete();
    }
}
