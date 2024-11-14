<?php

use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VTimeZone;

class Api_Service_Calendar
{
    public const LF = "\r\n";

    public const ID_DOSSIERTYPE_GRPVISITE = 3;

    public const ID_GENRE_CELLULE = 3;

    public const ID_AVIS_DEFAVORABLE = 2;

    public function sync($userid, $commission = null): void
    {
        $cache = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('cache');
        $isAllowedToViewAll = unserialize($cache->load('acl'))->isAllowed(
            Zend_Auth::getInstance()->getIdentity()['group']['LIBELLE_GROUPE'],
            'commission',
            'calendar_view_all'
        );
        $dossierEvent = $this->createRequestForWebcalEvent(
            $userid,
            $commission,
            $isAllowedToViewAll
        );
        $calendrierNom = 'Prévarisc';
        if ($commission) {
            $dbCommission = new Model_DbTable_Commission();
            $resultLibelle = $dbCommission->getLibelleCommissions($commission);
            if ([] !== $resultLibelle) {
                $calendrierNom .= ' '.$resultLibelle[0]['LIBELLE_COMMISSION'];
            }
        }

        // Le refresh est par défaut à 5 minutes
        $refreshTime = (getenv('PREVARISC_CALENDAR_REFRESH_TIME')
                        && '' !== getenv('PREVARISC_CALENDAR_REFRESH_TIME')) ?
                        getenv('PREVARISC_CALENDAR_REFRESH_TIME') : 'PT5M';

        $calendar = new VCalendar([
            'NAME' => $calendrierNom,
            'X-WR-CALNAME' => $calendrierNom,
            'REFRESH-INTERVAL;VALUE=DURATION' => $refreshTime,
            'X-PUBLISHED-TTL' => $refreshTime,
        ]);

        $vtimezone = $this->getVTimezoneComponent($calendar);
        $calendar->add($vtimezone);

        foreach ($dossierEvent as $commissionEvent) {
            $event = $this->createICSEvent($commissionEvent);
            if ($event) {
                $calendar->add('VEVENT', $event);
            }
        }

        echo $calendar->serialize();
    }

    private function getVTimezoneComponent(VCalendar $calendar): VTimeZone
    {
        $vtimezone = new VTimeZone($calendar, 'VTIMEZONE');
        $daylight = new Component($calendar, 'DAYLIGHT', [
            'DTSTART' => new DateTime('16010325T020000'),
            'RRULE' => 'FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3',
            'TZOFFSETFROM' => '+0100',
            'TZOFFSETTO' => '+0200',
        ]);
        $standard = new Component($calendar, 'STANDARD', [
            'DTSTART' => new DateTime('16011028T030000'),
            'RRULE' => 'FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10',
            'TZOFFSETFROM' => '+0200',
            'TZOFFSETTO' => '+0100',
        ]);
        $vtimezone->TZID = date_default_timezone_get();
        $vtimezone->add($standard);
        $vtimezone->add($daylight);

        return $vtimezone;
    }

    /**
     * [createRequestForWebcalEvent description].
     *
     * @param mixed $userid
     * @param mixed $commission
     * @param mixed $isAllowedToViewAll
     *
     * @return array La requête générée
     */
    private function createRequestForWebcalEvent($userid, $commission, $isAllowedToViewAll)
    {
        $today = new DateTime();
        $yearBefore = $today->modify('-1 year')->format('Y');

        $dbDateCommission = new Model_DbTable_DateCommission();

        if ($isAllowedToViewAll) {
            $userid = null;
            $commission = null;
        }

        return $dbDateCommission->getEventInCommission(
            $userid,
            $commission,
            $yearBefore,
            null,
            $isAllowedToViewAll
        );
    }

    /**
     * @psalm-return array{SUMMARY:string|false, LOCATION:string, DESCRIPTION:mixed, DTSTART:DateTime, DTEND:DateTime}|null
     *
     * @param mixed $commissionEvent
     *
     * @return null|(DateTime|false|mixed|string)[]
     */
    private function createICSEvent($commissionEvent): ?array
    {
        $event = null;

        $ets = null;
        if (is_array($commissionEvent)) {
            if (isset($commissionEvent['ID_ETABLISSEMENT'])) {
                $etsService = new Service_Etablissement();
                $ets = $etsService->get($commissionEvent['ID_ETABLISSEMENT']);

                $etsLibelleArray = [];
                foreach ($ets['parents'] as $parent) {
                    $etsLibelleArray[] = trim($parent['LIBELLE_ETABLISSEMENTINFORMATIONS']);
                }

                $etsLibelleArray[] = trim($ets['informations']['LIBELLE_ETABLISSEMENTINFORMATIONS']);
                $etsLibelle = implode(' - ', $etsLibelleArray);
            } else {
                $etsLibelle = '';
            }

            $commune = $ets && count($ets['adresses']) > 0 ? $ets['adresses'][0]['LIBELLE_COMMUNE'] : '';
            // Cas d'une commission en salle
            if (1 === $commissionEvent['ID_COMMISSIONTYPEEVENEMENT']) {
                if (self::ID_DOSSIERTYPE_GRPVISITE === $commissionEvent['TYPE_DOSSIER']) {
                    $libelleSum = $commissionEvent['LIBELLE_DATECOMMISSION'];
                } else {
                    $libelleSum = $commissionEvent['OBJET_DOSSIER'];
                }

                $summary = sprintf(
                    '#%s %s (%s) : %s %s - %s',
                    $ets ? $ets['general']['NUMEROID_ETABLISSEMENT'] : '',
                    $etsLibelle,
                    $commune,
                    $commissionEvent['LIBELLE_DOSSIERTYPE'],
                    $commissionEvent['LIBELLE_DOSSIERNATURE'],
                    trim($libelleSum)
                );
                $geo = sprintf('Commission en salle de %s', $commissionEvent['LIBELLE_COMMISSION']);
            // Cas d'une visite d'une commission ou d'un groupe de visite
            } else {
                $summary = sprintf(
                    '#%s %s : %s',
                    $ets ? $ets['general']['NUMEROID_ETABLISSEMENT'] : '',
                    $etsLibelle,
                    $commissionEvent['LIBELLE_DATECOMMISSION']
                );
                $adresse = $ets && count($ets['adresses']) > 0 ? $ets['adresses'][0] : null;
                if ($adresse) {
                    $geo = sprintf(
                        '%s %s %s, %s %s',
                        $adresse['NUMERO_ADRESSE'],
                        $adresse['LIBELLE_RUETYPE'],
                        $adresse['LIBELLE_RUE'],
                        $adresse['CODEPOSTAL_COMMUNE'],
                        $adresse['LIBELLE_COMMUNE']
                    );
                } else {
                    $geo = '';
                }
            }

            $dateStartHour = $commissionEvent['HEURE_DEB_AFFECT'] ?
                                'HEURE_DEB_AFFECT' : 'HEUREDEB_COMMISSION';
            $dateEndHour = $commissionEvent['HEURE_FIN_AFFECT'] ?
                                'HEURE_FIN_AFFECT' : 'HEUREFIN_COMMISSION';
            $dtStart = new DateTime(
                sprintf(
                    '%s %s',
                    $commissionEvent['DATE_COMMISSION'],
                    $commissionEvent[$dateStartHour]
                ),
                new DateTimeZone(date_default_timezone_get())
            );

            $dtEnd = new DateTime(
                sprintf(
                    '%s %s',
                    $commissionEvent['DATE_COMMISSION'],
                    $commissionEvent[$dateEndHour]
                ),
                new DateTimeZone(date_default_timezone_get())
            );

            $event = [
                'SUMMARY' => substr($summary, 0, 255),
                'LOCATION' => $geo,
                'DESCRIPTION' => $this->getEventCorps($commissionEvent, $ets),
                'DTSTART' => $dtStart,
                'DTEND' => $dtEnd,
            ];
        }

        return $event;
    }

    private function getAvisEtablissement($ets = null): string
    {
        if ($ets) {
            $servEtab = new Service_Etablissement();
            $avisDoss = $servEtab->getAvisEtablissement(
                $ets['general']['ID_ETABLISSEMENT'],
                $ets['general']['ID_DOSSIER_DONNANT_AVIS']
            );
            if ($ets['presence_avis_differe'] && 'avisDiff' === $avisDoss) {
                $avis = 'Dossier avec avis differé';
            } elseif (1 === $ets['avis']) {
                $avis = 'Favorable';

                if (self::ID_GENRE_CELLULE === $ets['informations']['ID_GENRE']) {
                    $avis .= " à l'exploitation";
                }
            } elseif (self::ID_AVIS_DEFAVORABLE === $ets['avis']) {
                $avis = 'Défavorable';
                if (self::ID_GENRE_CELLULE === $ets['informations']['ID_GENRE']) {
                    $avis .= " à l'exploitation";
                }
            } else {
                $avis = "Avis d'exploitation indisponible";
            }
        } else {
            $avis = "Avis d'exploitation indisponible";
        }

        return $avis;
    }

    private function getEventCorps(array $commissionEvent, $ets = null): string
    {
        $corpus = 'Contacts du dossier :'.self::LF;

        if ($ets) {
            $servEtab = new Service_Etablissement();
            $dossierService = new Service_Dossier();
            $contactsDossier = $dossierService->getAllContacts(
                $commissionEvent['ID_DOSSIER']
            );
            $contactsEts = $servEtab->getAllContacts($ets['general']['ID_ETABLISSEMENT']);
            $contacts = array_merge($contactsDossier, $contactsEts);
            if ([] !== $contacts) {
                foreach ($contacts as $contact) {
                    $corpus .= $this->formatUtilisateurInformations($contact);
                }
            } else {
                $corpus .= 'Aucun contact'.self::LF;
            }
        } else {
            $corpus .= 'Aucun contact'.self::LF;
        }

        $corpus .= self::LF.self::LF;

        $adresseService = new Service_Adresse();
        $maire = $adresseService->getMaire($commissionEvent['NUMINSEE_COMMUNE']);
        if ($maire && [] !== $maire) {
            $corpus .= sprintf(
                'Coordonnées de la mairie :%s%s%s',
                self::LF,
                $this->formatUtilisateurInformations($maire),
                self::LF.self::LF
            );
        } else {
            $corpus .= 'Aucune coordonées pour la mairie.'.self::LF.self::LF;
        }

        if (1 === $commissionEvent['ID_DOSSIERTYPE']) {
            if ('servInstCommune' === $commissionEvent['TYPESERVINSTRUC_DOSSIER']) {
                $serviceInstruct = $maire;
            } else {
                $dbGroupement = new Model_DbTable_Groupement();
                $serviceInstruct = $dbGroupement->getByLibelle(
                    $commissionEvent['SERVICEINSTRUC_DOSSIER']
                );
                $serviceInstruct = [] === $serviceInstruct ?
                                    null : $serviceInstruct[0];
            }

            if ($maire && [] !== $maire) {
                $corpus .= sprintf(
                    'Coordonnées du service instructeur :%s%s%s',
                    self::LF,
                    $this->formatUtilisateurInformations($serviceInstruct),
                    self::LF.self::LF
                );
            } else {
                $corpus .= 'Aucunes coordonées pour le service instructeur.'.self::LF.self::LF;
            }
        }

        $lastVisitestr = $ets && $ets['last_visite'] ? $ets['last_visite'] : 'Aucune date.';
        $corpus .= sprintf(
            'Date de la dernière visite périodique : %s%s',
            $lastVisitestr,
            self::LF.self::LF
        );

        $corpus .= sprintf(
            "Avis d'exploitation de l'établissement : %s%s",
            $this->getAvisEtablissement($ets),
            self::LF.self::LF.self::LF
        );

        // Ajout Grade, prenom, nom préventionniste dans calendrier dossier detail
        $dossierService = new Service_Dossier();
        $preventionnistes = $dossierService->getPreventionniste($commissionEvent['ID_DOSSIER']);

        $preventionniste = $this->formatPrevisionniste($preventionnistes);

        $corpus .= 'Préventionniste(s) du dossier : '.self::LF;

        return $corpus.sprintf(
            '%s%s',
            $preventionniste,
            self::LF.self::LF
        );
    }

    private function formatPrevisionniste($preventionnistes): string
    {
        $result = '';
        // Si plusieurs préventionnistes liés au dossier
        if (count($preventionnistes) > 1) {
            foreach ($preventionnistes as $i => $preventionniste) {
                if ($this->isPreventionnisteExist($preventionnistes, $i)) {
                    $result .= sprintf(
                        '- %s%s%s%s',
                        $preventionniste['GRADE_UTILISATEURINFORMATIONS'],
                        $preventionniste['PRENOM_UTILISATEURINFORMATIONS'],
                        $preventionniste['NOM_UTILISATEURINFORMATIONS'],
                        self::LF.self::LF
                    );
                } else {
                    $result .= sprintf(
                        '- %s%s',
                        ' Informations du prévisionniste incomplètes ou absentes',
                        self::LF.self::LF
                    );
                }
            }
        } elseif ($this->isPreventionnisteExist($preventionnistes, 0)) {
            $result = sprintf(
                '- %s%s%s%s',
                $preventionnistes[0]['GRADE_UTILISATEURINFORMATIONS'].' ',
                $preventionnistes[0]['PRENOM_UTILISATEURINFORMATIONS'].' ',
                $preventionnistes[0]['NOM_UTILISATEURINFORMATIONS'],
                self::LF.self::LF.self::LF
            );
        } else {
            $result .= sprintf(
                '- %s%s',
                '- Informations du prévisionniste incomplètes ou absentes',
                self::LF.self::LF
            );
        }

        return $result;
    }

    // Vérifie que toutes les informations liés au préventionnistes, grade / prenom / nom, est non null
    private function isPreventionnisteExist(array $preventionnistes, $index): bool
    {
        if (empty($preventionnistes[$index]['GRADE_UTILISATEURINFORMATIONS'])) {
            return false;
        }

        if (empty($preventionnistes[$index]['PRENOM_UTILISATEURINFORMATIONS'])) {
            return false;
        }

        return !empty($preventionnistes[$index]['NOM_UTILISATEURINFORMATIONS']);
    }

    private function formatUtilisateurInformations($user): string
    {
        $str = '';
        if ($user && is_array($user) && $user['NOM_UTILISATEURINFORMATIONS']) {
            $str .= sprintf(
                '- %s : %s %s',
                $user['LIBELLE_FONCTION'],
                $user['NOM_UTILISATEURINFORMATIONS'],
                $user['PRENOM_UTILISATEURINFORMATIONS']
            );
            if ($user['NUMEROADRESSE_UTILISATEURINFORMATIONS']
                && $user['RUEADRESSE_UTILISATEURINFORMATIONS']
                && $user['CPADRESSE_UTILISATEURINFORMATIONS']
                && $user['VILLEADRESSE_UTILISATEURINFORMATIONS']
            ) {
                $str .= sprintf(
                    ', %s %s, %s %s',
                    $user['NUMEROADRESSE_UTILISATEURINFORMATIONS'],
                    $user['RUEADRESSE_UTILISATEURINFORMATIONS'],
                    $user['CPADRESSE_UTILISATEURINFORMATIONS'],
                    $user['VILLEADRESSE_UTILISATEURINFORMATIONS']
                );
            }

            if ($user['TELFIXE_UTILISATEURINFORMATIONS']) {
                $str .= sprintf(
                    ', %s',
                    $user['TELFIXE_UTILISATEURINFORMATIONS']
                );
            }

            if ($user['TELFAX_UTILISATEURINFORMATIONS']) {
                $str .= sprintf(
                    ', %s',
                    $user['TELFAX_UTILISATEURINFORMATIONS']
                );
            }

            if ($user['MAIL_UTILISATEURINFORMATIONS']) {
                $str .= sprintf(
                    ', %s',
                    $user['MAIL_UTILISATEURINFORMATIONS']
                );
            }

            $str .= "\n";
        }

        return $str;
    }
}
