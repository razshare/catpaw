<?php
namespace CatPaw\Environment\Attributes;

use Attribute;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Environment\Services\EnvironmentConfigurationService;
use CatPaw\Environment\Services\EnvironmentService;
use Psr\Log\LoggerInterface;

#[Attribute]
class EnvironmentFileName implements AttributeInterface {
    use CoreAttributeDefinition;
    
    private array $eitherFileNames;

    /**
     * @param  string[] $eitherFileName The first valid file name will be used.
     * @return void
     */
    public function __construct(
        string ...$eitherFileNames,
    ) {
        if (count($eitherFileNames) === 0) {
            $eitherFileNames = [ "./resources/.env", "./resources/env.yml" ];
        }
        $this->eitherFileNames = $eitherFileNames;
    }

    /**
     * This will set the given file names to the EnvironmentConfigurationService.
     * @return void
     */
    #[Entry] public function main(
        EnvironmentConfigurationService $environmentConfigurationService,
        EnvironmentService $environmentService,
        LoggerInterface $logger,
    ) {
        $environmentConfigurationService->setFileNames(...$this->eitherFileNames);
        yield $environmentService->load(
            logger: $logger,
            environmentConfigurationService: $environmentConfigurationService,
        );
    }
}