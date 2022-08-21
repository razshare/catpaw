<?php
namespace CatPaw\Environment\Attributes;

use function Amp\call;
use Attribute;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Environment\Services\EnvironmentConfigurationService;
use CatPaw\Environment\Services\EnvironmentService;
use CatPaw\Utilities\Container;
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
     */
    #[Entry] public function main(
        EnvironmentConfigurationService $environmentConfigurationService,
        LoggerInterface $logger,
    ) {
        $environmentConfigurationService->setFileNames(...$this->eitherFileNames);
        return call(function() use (
            $environmentConfigurationService,
            $logger,
        ) {
            $environmentService = yield Container::create(EnvironmentService::class);
            yield $environmentService->load(
                logger: $logger,
                environmentConfigurationService: $environmentConfigurationService,
            );
        });
    }
}