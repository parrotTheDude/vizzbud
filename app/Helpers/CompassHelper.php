<?php

namespace App\Helpers;

class CompassHelper
{
    public static function fromDegrees($degrees): string
    {
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        $index = (int) round($degrees / 45) % 8;
        return $directions[$index];
    }
}