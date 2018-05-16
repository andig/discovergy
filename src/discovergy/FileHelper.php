<?php
/**
 * @copyright Copyright (c) 2018, Andreas Goetz
 * @author Andreas Goetz <cpuidle@gmx.de>
 * @license https://opensource.org/licenses/MIT
 */

namespace Discovergy;

class FileHelper
{
    public static function loadFile($file, $mustExist = true)
    {
        if (false === ($contents = @file_get_contents($file))) {
            if ($mustExist) {
                throw new \Exception('Could not load ' . $file);
            }
            return null;
        }

        return $contents;
    }

    public static function loadJsonFile($file, $mustExist = true)
    {
        if (null === ($contents = self::loadFile($file, $mustExist))) {
            return null;
        }

        if (null === ($json = json_decode($contents, true))) {
            throw new \Exception('Could not decode json file ' . $file);
        }

        return $json;
    }
}
