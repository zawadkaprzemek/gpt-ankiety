<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    var_dump(date_default_timezone_get());die;
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
