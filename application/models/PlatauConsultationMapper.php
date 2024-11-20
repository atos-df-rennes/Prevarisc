<?php

class Model_PlatauConsultationMapper
{
    private $dbTable;

    public function setDbTable(string $dbTable): self
    {
        $dbTable = new $dbTable();

        if (!$dbTable instanceof Model_DbTable_PlatauConsultation) {
            throw new Exception(sprintf('Incorrect model dbtable. Expecting %s', Model_DbTable_PlatauConsultation::class));
        }

        $this->dbTable = $dbTable;

        return $this;
    }

    public function getDbTable()
    {
        if (null === $this->dbTable) {
            $this->setDbTable(Model_DbTable_PlatauConsultation::class);
        }

        return $this->dbTable;
    }

    public function save(Model_PlatauConsultation $consultation): void
    {
        $data = [
            'STATUT_AVIS' => $consultation->getStatutAvis(),
            'STATUT_PEC' => $consultation->getStatutPec(),
        ];

        $this->getDbTable()->update($data, ['ID_PLATAU = ?' => $consultation->getId()]);
    }

    public function find(string $id, Model_PlatauConsultation $consultation): ?Model_PlatauConsultation
    {
        $result = $this->getDbTable()->find($id);

        if (0 === count($result)) {
            return null;
        }

        $row = $result->current();

        $datePec = Service_Utils_Date::formatDateWithDayName($row->DATE_PEC);
        $dateAvis = Service_Utils_Date::formatDateWithDayName($row->DATE_AVIS);

        $consultation->setId($row->ID_PLATAU)
            ->setStatutAvis($row->STATUT_AVIS ?? Model_Enum_PlatauStatutAvis::INCONNU)
            ->setDateAvis($dateAvis)
            ->setStatutPec($row->STATUT_PEC ?? Model_Enum_PlatauStatutPec::INCONNU)
            ->setDatePec($datePec)
            ->setDateReponseAttendue($row->DATE_REPONSE_ATTENDUE)
        ;

        return $consultation;
    }
}
