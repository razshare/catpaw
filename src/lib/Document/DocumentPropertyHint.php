<?php
namespace CatPaw\Document;

use Stringable;

readonly class DocumentPropertyHint implements Stringable {
    /**
     * Create a document property.
     * @param  string               $className Class name of the property.
     * @return DocumentPropertyHint
     */
    public static function create(string $className):self {
        return new self($className);
    }

    private function __construct(
        public string $type,
    ) {
    }

    public function __toString(): string {
        return '';
    }
}