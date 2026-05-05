<?php

namespace App\Enums;

enum JuryRole: string
{
    case President    = 'president';
    case Examinateur  = 'examinateur';
    case Rapporteur   = 'rapporteur';
}