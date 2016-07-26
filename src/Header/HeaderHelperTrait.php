<?php
declare(strict_types = 1);

namespace ZipStream\Header;

use ZipStream\File\DeflatedFileFactsInterface;
use ZipStream\File\FileFactsInterface;

/**
 * Class HeaderHelperTrait
 * @package ZipStream\Header
 */
trait HeaderHelperTrait
{
    /**
     * Convert a UNIX timestamp to a DOS timestamp.
     *
     * @param int|null $when
     * @return int DOS Timestamp
     */
    protected final function dosTime(int $when = null): int
    {
        $when = $when ?? time();

        // get date array for timestamp
        $d = getdate($when);

        // set lower-bound on dates
        if ($d['year'] < 1980) {
            $d = [
                'year'    => 1980,
                'mon'     => 1,
                'mday'    => 1,
                'hours'   => 0,
                'minutes' => 0,
                'seconds' => 0
            ];
        }

        // remove extra years from 1980
        $d['year'] -= 1980;

        // return date string
        return (
            ($d['year'] << 25) |
            ($d['mon'] << 21) |
            ($d['mday'] << 16) |
            ($d['hours'] << 11) |
            ($d['minutes'] << 5) |
            ($d['seconds'] >> 1)
        );
    }

    /**
     * Create a format string and argument list for pack(), then call
     * pack() and return the result.
     *
     * @param array $fields
     * @return string
     */
    protected function packFields(array $fields): string
    {
        $fmt = '';
        $args = [];

        // populate format string and argument list
        foreach ($fields as $field) {
            $fmt .= $field[0];
            $args[] = $field[1];
        }

        // prepend format string to argument list
        array_unshift($args, $fmt);

        // build output string from header and compressed data
        return call_user_func_array('pack', $args);
    }

    /**
     * @param string $name
     * @return string
     */
    protected function sanitiseName(string $name): string
    {
        // strip leading slashes from file name
        // (fixes bug in windows archive viewer)
        return preg_replace('/^\\/+/', '', $name);
    }

    /**
     * @param bool $deflationEnabled
     * @return int
     */
    protected function getMeth(bool $deflationEnabled)
    {
        return $deflationEnabled ? 0x08 : 0x00;
    }

    /**
     * @param FileFactsInterface $facts
     * @return bool
     */
    protected function deflationEnabled(FileFactsInterface $facts): bool
    {
        return $facts instanceof DeflatedFileFactsInterface;
    }

    /**
     * @param string $name
     * @return int
     */
    protected function getGeneralPurposeFlag(string $name): int
    {
        $generalPurposeFlag = 0x00;

        if (mb_check_encoding($name, 'UTF-8') &&
            !mb_check_encoding($name, 'ASCII')
        ) {
            // Sets Bit 11: Language encoding flag (EFS).  If this bit is set,
            // the filename and comment fields for this file
            // MUST be encoded using UTF-8. (see APPENDIX D)
            $generalPurposeFlag |= 0x0800;
        }

        return $generalPurposeFlag;
    }
}
