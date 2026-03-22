<?php

namespace App\Enums;

enum ActivityFactor: string
{
    case Poco = 'poco';
    case Ligero = 'ligero';
    case Moderado = 'moderado';
    case Fuerte = 'fuerte';
    case MuyFuerte = 'muy_fuerte';
}
