<?php

namespace App\Enums;

enum EntryDirection: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}