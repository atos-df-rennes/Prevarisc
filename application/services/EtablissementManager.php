<?php

class Service_EtablissementManager
{
    private $etablissementAdresse;

    public function getEtablissementAdresse(): Service_Interface_EtablissementAdresse
    {
        if (getenv('PREVARISC_API_ADRESSE_MODAL')) {
            $this->etablissementAdresse = new Model_DbTable_EtablissementAdresseApi();
        } else {
            $this->etablissementAdresse = new Model_DbTable_EtablissementAdresse();
        }

        return $this->etablissementAdresse;
    }
}
