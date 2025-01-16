<?php

class Service_Alerte
{
    public const ALERTE_LINK = '<a data-value="%s"%s class="pull-right alerte-link"><span class="glyphicon glyphicon-bell" aria-hidden="true"></span>Alerter</a>';

    public function getLink($idTypeChangement, $idEtablissement = null): string
    {
        $etabData = '';
        if ($idEtablissement) {
            $etabData = sprintf(' data-ets="%s"', $idEtablissement);
        }

        return sprintf(self::ALERTE_LINK, $idTypeChangement, $etabData);
    }
}
