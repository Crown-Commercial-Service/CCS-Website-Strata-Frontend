<?php

declare(strict_types=1);

namespace Strata\Frontend\Utils;

/**
 * Class FileInfoFormatter
 * @package Strata\Frontend\Utils
 */
class FileInfoFormatter
{

    /**
     * Converts a filesize (in bytes) to a formatted string (e.g. '23 MB')
     *
     * @param int $sizeInByte
     * @return string
     */
    public static function formatFileSize($sizeInByte = 0): string
    {
        if (empty($sizeInByte)) {
            $size = '0 B';
            return $size;
        }

        $sizeInBytes = (int) $sizeInByte; 

        $size = match (true) {
            (int) $sizeInByte < 1024 => $sizeInByte . ' B',
            (int) $sizeInByte < 1048576 => round($sizeInByte / 1024, 2) . ' KB',
            (int) $sizeInByte < 1073741824 => round($sizeInByte / 1048576, 2) . ' MB',
            (int) $sizeInByte < 1099511627776 => round($sizeInByte / 1073741824, 2) . ' GB',
            (int) $sizeInByte >= 1099511627776 => '1 TB+',
            default => '0 B'
        };

        return $size;
    }
}
