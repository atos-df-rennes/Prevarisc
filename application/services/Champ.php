<?php

class Service_Champ
{
    public function isTableau(array $champ): bool
    {
        return filter_var($champ['tableau'], FILTER_VALIDATE_BOOLEAN);
    }

    public function hasValue(array $champ): bool
    {
        if ('Parent' === $champ['TYPE']) {
            if (!self::isTableau($champ)) {
                foreach ($champ['FILS'] as $enfant) {
                    if (self::hasValue($enfant)) {
                        return true;
                    }
                }

                return false;
            }

            return count($champ['FILS']['VALEURS']) > 0;
        }

        return ('Case à cocher' !== $champ['TYPE'] && null !== $champ['VALEUR'])
            || (null !== $champ['VALEUR'] && 0 !== $champ['VALEUR']);
    }
}
