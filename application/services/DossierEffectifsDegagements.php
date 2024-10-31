<?php

class Service_DossierEffectifsDegagements extends Service_Descriptif
{
    public function __construct()
    {
        parent::__construct(
            'effectifsDegagementsDossier',
            new Model_DbTable_DisplayRubriqueDossier(),
            new Service_RubriqueDossier()
        );
    }

    public function copyValeurs(int $idDossier, array $rubriques): void
    {
        $serviceChamp = new Service_Champ();

        foreach ($rubriques as $rubrique) {
            $champs = $rubrique['CHAMPS'];

            foreach ($champs as $champ) {
                if ('Parent' !== $champ['TYPE']) {
                    $this->saveValeurChamp('champ-'.$champ['ID_CHAMP'], $idDossier, 'Dossier', $champ['VALEUR']);

                    continue;
                }

                if (!$serviceChamp->isTableau($champ)) {
                    foreach ($champ['FILS'] as $enfant) {
                        $this->saveValeurChamp(
                            implode('-', ['champ', $enfant['ID_CHAMP']]),
                            $idDossier,
                            'Dossier',
                            $enfant['VALEUR']
                        );
                    }

                    continue;
                }
                
                foreach ($champ['FILS']['VALEURS'] as $index => $champs) {
                    foreach ($champs as $idChamp => $data) {
                        $this->saveValeurChamp(
                            implode('-', ['champ', $idChamp, $index]),
                            $idDossier,
                            'Dossier',
                            $data['VALEUR']
                        );
                    }
                }
            }
        }
    }
}
