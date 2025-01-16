<?php

namespace App\Formatter;

use Monolog\Formatter\LineFormatter;

/**
 * Class MonologTimeFormatter
 *
 * Custom time formatter for monolog output
 *
 * @package App\Formatter
 */
class MonologTimeFormatter extends LineFormatter
{
    public function __construct()
    {
        parent::__construct(null, 'Y-m-d H:i:s', false, false, false);
    }
}
