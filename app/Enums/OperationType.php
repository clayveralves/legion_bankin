<?php

namespace App\Enums;

enum OperationType: string
{
    case Deposit = 'deposit';
    case Transfer = 'transfer';
    case Reversal = 'reversal';
}