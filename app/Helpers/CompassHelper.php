<?php

namespace App\Helpers;

class CompassHelper
{
    public static function degreesToCompass($degrees)
    {
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        return $directions[round($degrees / 45) % 8];
    }
}