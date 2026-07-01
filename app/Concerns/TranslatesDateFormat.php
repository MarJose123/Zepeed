<?php

namespace App\Concerns;

use Carbon\CarbonInterface;

trait TranslatesDateFormat
{
    /**
     * Translate a MySQL/MariaDB DATE_FORMAT() token string into the
     * equivalent PHP/Carbon format string, then format the given date.
     *
     * Only the tokens actually used across the dashboard/metrics
     * controllers are mapped (%Y %m %d %H %i %s). Any other character
     * in the pattern (slashes, colons, spaces, literal zeros) passes
     * through unchanged, matching DATE_FORMAT()'s own behaviour.
     */
    protected function formatDate(CarbonInterface $date, string $mysqlFormat): string
    {
        return $date->format(strtr($mysqlFormat, [
            '%Y' => 'Y',
            '%m' => 'm',
            '%d' => 'd',
            '%H' => 'H',
            '%i' => 'i',
            '%s' => 's',
        ]));
    }
}
