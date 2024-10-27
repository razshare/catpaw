<?php
namespace CatPaw\Web;

use Amp\Cancellation;
use Amp\Http\Server\FormParser\StreamingFormParser;
use Amp\Http\Server\Request;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use function json_decode;
use stdClass;
use Throwable;

class BodyParser {
    private function __construct() {
    }

    /**
     * @return Result<mixed>
     */
    public static function parseAsObject(
        Request $request,
        int $sizeLimit,
        null|Cancellation $cancellation = null
    ): Result {
        $contentType = $request->getHeader("Content-Type") ?? '';

        if ('' === $contentType) {
            return error("No Content-Type specified. Could not parse body.");
        }

        try {
            if (str_starts_with($contentType, "application/json")) {
                $result = json_decode($request->getBody()->buffer(cancellation: null, limit: $sizeLimit), false);
                return ok($result);
            } else {
                $result = new stdClass;
                $parser = new StreamingFormParser();
                $fields = $parser->parseForm(request:$request, bodySizeLimit: $sizeLimit);
                foreach ($fields as $field) {
                    $name = $field->getName();
                    if ($field->isFile()) {
                        $result->{$name} = new FormFile(
                            fileName: $field->getFilename(),
                            fileContents: $field->buffer()
                        );
                    } else {
                        $result->{$name} = $field->buffer($cancellation);
                    }
                }
                return ok($result);
            }
        } catch (Throwable $error) {
            return error($error);
        }
    }
}
