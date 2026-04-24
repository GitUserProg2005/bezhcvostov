<?php

namespace App\Enums;

enum TaskSourceType: string
{
    case Text = 'text';
    case Voice = 'voice';
    case Image = 'image';
    case Screenshot = 'screenshot';
}
