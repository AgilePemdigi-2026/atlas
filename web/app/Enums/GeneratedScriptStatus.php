<?php

namespace App\Enums;

enum GeneratedScriptStatus: string
{
    case Completed = 'completed';
    case Partial = 'partial';
    case Failed = 'failed';
}
