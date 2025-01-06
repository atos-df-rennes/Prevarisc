<?php

class View_Helper_Dates
{
    /**
     * A sweet interval formatting, will use the two biggest interval parts.
     * On small intervals, you get minutes and seconds.
     * On big intervals, you get months and days.
     * Only the two biggest parts are used.
     */
    public function formatDateDiff(DateTimeInterface $start, ?DateTimeInterface $end = null): string
    {
        if (!$end instanceof DateTimeInterface) {
            $end = new DateTime();
        }

        $interval = $end->diff($start);
        $doPlural = function ($nb, string $str): string {
            return $nb > 1 ? $str.'s' : $str;
        }; // adds plurals

        $format = [];
        if (0 !== $interval->y) {
            $format[] = '%y '.$doPlural($interval->y, 'année');
        }

        if (0 !== $interval->m) {
            $format[] = '%m mois';
        }

        if (0 !== $interval->d) {
            $format[] = '%d '.$doPlural($interval->d, 'jour');
        }

        if (0 !== $interval->h) {
            $format[] = '%h '.$doPlural($interval->h, 'heure');
        }

        if (0 !== $interval->i) {
            $format[] = '%i '.$doPlural($interval->i, 'minute');
        }

        if (0 !== $interval->s) {
            if ([] === $format) {
                return '<= 1 min';
            }

            $format[] = '%s '.$doPlural($interval->s, 'seconde');
        }

        // We use the two biggest parts
        $format = array_shift($format);

        // Prepend 'since ' or whatever you like
        return $interval->format($format);
    }
}
