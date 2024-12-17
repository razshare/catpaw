<?php

/**
 * Get the verb of the current document.
 * @return string
 */
function verb():string {
    return $GLOBALS['DOCUMENT_VERB'] ?? 'GET';
}

/**
 * Check if the verb of the current document is `POST`.
 * @return bool
 */
function POST():bool {
    return 'POST' === verb();
}

/**
 * Check if the verb of the current document is `GET`.
 * @return bool
 */
function GET():bool {
    return 'GET' === verb();
}

/**
 * Check if the verb of the current document is `PUT`.
 * @return bool
 */
function PUT():bool {
    return 'PUT' === verb();
}

/**
 * Check if the verb of the current document is `DELETE`.
 * @return bool
 */
function DELETE():bool {
    return 'DELETE' === verb();
}