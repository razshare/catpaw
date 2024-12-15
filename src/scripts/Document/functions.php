<?php

use CatPaw\Document\DocumentPropertyHint;
use CatPaw\Document\DocumentRunConfiguration;

/**
 * Set the name of the document.
 * @param  string $documentName
 * @return void
 */
function name(string $documentName):void {
    if ($configuration = $GLOBALS[DocumentRunConfiguration::class]) {
        if ($configuration instanceof DocumentRunConfiguration) {
            $configuration->documentName = $documentName;
        }
    }
}

/**
 * Check if the document has been mounted.
 * @return bool
 */
function mounted():bool {
    if ($configuration = $GLOBALS[DocumentRunConfiguration::class]) {
        if ($configuration instanceof DocumentRunConfiguration) {
            return $configuration->mounted;
        }
    }

    return false;
}

/**
 * Create a hint for an input property of type `T`.
 * 
 * Assign this to a global variable using the nullish assignment syntax
 * and the runtime will replace the value with the actual input property.
 * 
 * # Example
 * ```php
 * <?php
 *   $name ??= input();
 * ?>
 * 
 * <span>hello <?= $name ?></span>
 * ```
 * @template T
 * @param  class-string<T>|string $className
 * @return T
 * @phpstan-ignore method.templateTypeNotInParameter
 */
function input(string $className = 'string'):mixed {
    return DocumentPropertyHint::create($className);
}