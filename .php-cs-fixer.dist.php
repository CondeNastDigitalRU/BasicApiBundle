<?php declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2' => true,
        '@PHP71Migration' => true,
        '@PHP71Migration:risky' => true,
        '@DoctrineAnnotation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in([
                __DIR__.'/src',
                __DIR__.'/tests',
            ])
            ->exclude([
                'Fixtures/App/var',
                'Fixtures/App/config'
            ])
            ->notPath([
                'Fixtures/App/src/Kernel.php',
                'Fixtures/App/public/index.php',
                'Fixtures/App/bin/console',
                'Fixtures/App/config/bootstrap.php',
                'Fixtures/App/config/bundles.php',
            ])
    )
;
