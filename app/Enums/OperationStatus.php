<?php

namespace App\Enums;

enum OperationStatus: string
{
    case Completed = 'completed';
    case Reversed = 'reversed';
}