<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
;

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'no_superfluous_phpdoc_tags' => true,
        'ordered_imports' => true,
        'phpdoc_summary' => false,
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_order' => true,
        'psr_autoloading' => false,
        'single_line_throw' => false,
        'simplified_null_return' => false,
        'yoda_style' => ['equal' => null, 'identical' => null, 'less_and_greater' => null],
        'trailing_comma_in_multiline' => ['after_heredoc' => true, 'elements' => ['arrays', 'arguments', 'parameters']],
    ])
    ->setFinder($finder)
;
