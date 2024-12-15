<?php

namespace CatPaw\Document;

class DocumentRunConfiguration {
    /**
     * 
     * @param  string                             $fileName
     * @param  bool                               $mounted
     * @param  string                             $documentName
     * @param  array<string,DocumentPropertyHint> $propertyHints
     * @return DocumentRunConfiguration
     */
    public static function create(
        string $fileName,
        bool $mounted = false,
        string $documentName = '',
        array $propertyHints = [],
    ):self {
        return new self(
            fileName: $fileName,
            documentName: $documentName,
            mounted: $mounted,
            propertyHints: $propertyHints,
        );
    }
    
    /**
     * 
     * @param  string                             $fileName
     * @param  string                             $documentName
     * @param  bool                               $mounted
     * @param  array<string,DocumentPropertyHint> $propertyHints
     * @return void
     */
    private function __construct(
        public readonly string $fileName,
        public string $documentName,
        public bool $mounted,
        public array $propertyHints,
    ) {
    }
}