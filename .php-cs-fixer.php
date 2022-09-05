<?php
/*
 * This document has been generated with
 * https://mlocati.github.io/php-cs-fixer-configurator/#version:3.11.0|configurator
 * you can change this configuration by importing this file.
 */
$config = new PhpCsFixer\Config();
return $config
    ->setRules([
        'align_multiline_comment'                 => ['comment_type' => 'all_multiline'],
        'array_indentation'                       => true,
        'array_syntax'                            => ['syntax' => 'short'],
        'binary_operator_spaces'                  => ['default' => 'align_single_space_minimal'],
        'blank_line_after_namespace'              => true,
        'braces'                                  => ['allow_single_line_anonymous_class_with_empty_body' => true,'allow_single_line_closure' => true,'position_after_anonymous_constructs' => 'same','position_after_control_structures' => 'same','position_after_functions_and_oop_constructs' => 'same'],
        'cast_spaces'                             => ['space' => 'none'],
        'class_definition'                        => true,
        'clean_namespace'                         => true,
        'compact_nullable_typehint'               => true,
        'concat_space'                            => ['spacing' => 'none'],
        'constant_case'                           => true,
        'control_structure_continuation_position' => ['position' => 'same_line'],
        'function_declaration'                    => ['closure_function_spacing' => 'none','trailing_comma_single_line' => true],
        'function_typehint_space'                 => true,
        'heredoc_indentation'                     => true,
        'indentation_type'                        => true,
        'list_syntax'                             => true,
        'method_argument_space'                   => ['after_heredoc' => false,'keep_multiple_spaces_after_comma' => false,'on_multiline' => 'ensure_fully_multiline'],
        'method_chaining_indentation'             => true,
        'native_function_type_declaration_casing' => true,
        'no_spaces_after_function_name'           => true,
        'no_spaces_around_offset'                 => true,
        'no_unneeded_curly_braces'                => true,
        'no_unused_imports'                       => true,
        'ordered_imports'                         => true,
        'phpdoc_align'                            => ['align' => 'vertical'],
        'phpdoc_indent'                           => true,
        'phpdoc_order'                            => true,
        'phpdoc_scalar'                           => true,
        'yoda_style'                              => ['always_move_variable' => true,'equal' => true,'identical' => true,'less_and_greater' => false],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('vendor')
            ->in(__DIR__)
    )
;
