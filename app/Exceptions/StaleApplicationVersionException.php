<?php

namespace App\Exceptions;

use RuntimeException;

class StaleApplicationVersionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Pengajuan telah diproses oleh pengguna lain. Muat ulang data sebelum melanjutkan.');
    }
}
