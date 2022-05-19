<?php
/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.8.0|configurator
 * you can change this configuration by importing this file.
 */
$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        'align_multiline_comment' => ['comment_type' => 'all_multiline'],
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_after_namespace' => true,
        'braces' => ['allow_single_line_anonymous_class_with_empty_body' => true,'allow_single_line_closure' => true,'position_after_anonymous_constructs' => 'same','position_after_control_structures' => 'same','position_after_functions_and_oop_constructs' => 'same'],
        'cast_spaces' => ['space' => 'none'],
        'class_definition' => true,
        'clean_namespace' => true,
        'compact_nullable_typehint' => true,
        'concat_space' => ['spacing' => 'none'],
        'control_structure_continuation_position' => ['position' => 'same_line'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_indent' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => ['types' => []],
        'yoda_style' => ['always_move_variable' => true,'equal' => true,'identical' => true,'less_and_greater' => false],
    ])
    ->setFinder(PhpCsFixer\Finder::create()
        ->exclude('vendor')
        ->in(__DIR__)
    )
;