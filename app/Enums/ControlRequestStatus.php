<?php

namespace App\Enums;

enum ControlRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Refused = 'refused';
}
