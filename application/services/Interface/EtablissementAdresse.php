<?php

interface Service_Interface_EtablissementAdresse
{
    public function get($id_etablissement);

    public function save($adresse, $etablissementID);
}
