<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())->setRules([
    '@PSR2' => true,
    'ordered_imports' => [
        'sort_algorithm' => 'length',
    ],
    'single_blank_line_before_namespace' => true,
    'no_unused_imports' => true,
    'whitespace_after_comma_in_array' => true,
    'class_attributes_separation' => [
        'elements' => [
            'const' => 'one',
            'method' => 'one',
            'property' => 'one',
            'trait_import' => 'one',
        ]
    ],
    'blank_line_after_opening_tag' => true,
    'blank_line_before_statement' => [
        'statements' => [
            'if',
            'declare',
            'do',
            'while',
            'for',
            'foreach',
            'goto',
            'return',
            'switch',
            'throw',
            'try',
        ]
    ],
    'return_type_declaration' => [
        'space_before' => 'none',
    ],
    'no_whitespace_in_blank_line' => true,
    'concat_space' => [
        'spacing' => 'one',
    ],
    'no_useless_return' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'yoda_style' => false,
])->setFinder(
    Finder::create()
        ->exclude('bootstrap')
        ->exclude('storage')
        ->exclude('vendor')
        ->ignoreDotFiles(true)
        ->ignoreVCS(true)
        ->in(__DIR__)
);
