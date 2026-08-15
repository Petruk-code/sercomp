<?php

namespace App\Exceptions;

use RuntimeException;

class ServiceAlreadyCheckedOutException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Servis ini sudah memiliki transaksi.');
    }
}
