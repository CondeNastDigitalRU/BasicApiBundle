<?php

use Condenast\BasicApiBundle\Tests\Fixtures\App\Kernel;

$_SERVER['APP_RUNTIME_OPTIONS'] = [
    'project_dir' => dirname(__DIR__)
];

require_once dirname(__DIR__, 4).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
