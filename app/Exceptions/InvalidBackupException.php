<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class InvalidBackupException extends Exception implements ShouldntReport
{
    //
}
