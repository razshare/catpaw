<?php
namespace CatPaw\Web;

use function Amp\File\isDirectory;
use function Amp\File\isFile;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use CatPaw\Container;
use function CatPaw\error;
use CatPaw\File;
use function CatPaw\ok;
use CatPaw\Unsafe;
use CatPaw\Web\Interfaces\FileServerInterface;
use CatPaw\Web\Interfaces\FileServerOverwriteInterface;
use CatPaw\Web\Services\ByteRangeService;
use Psr\Log\LoggerInterface;

readonly class FileServer implements FileServerInterface {
    /**
     * Create a file server that serves static files as requested by the 
     * client and return 404 responses whenever a static file is not found.
     * @return Unsafe<self>
     */
    public static function create(Server $server):Unsafe {
        $loggerAttempt = Container::create(LoggerInterface::class);
        if ($loggerAttempt->error) {
            return error($loggerAttempt->error);
        }

        $rangeAttempt = Container::create(ByteRangeService::class);
        if ($rangeAttempt->error) {
            return error($rangeAttempt->error);
        }

        return ok(new self(
            server: $server,
            fallback: 'index.html',
            logger: $loggerAttempt->value,
            byteRangeService: $rangeAttempt->value,
        ));
    }

    /**
     * Similar to `::create`, but instead of returning 404 responses, return the `/index.html` file.
     * @return Unsafe<self>
     */
    public static function createForSpa(Server $server):Unsafe {
        $loggerAttempt = Container::create(LoggerInterface::class);
        if ($loggerAttempt->error) {
            return error($loggerAttempt->error);
        }

        $rangeAttempt = Container::create(ByteRangeService::class);
        if ($rangeAttempt->error) {
            return error($rangeAttempt->error);
        }

        return ok(new self(
            server:           $server,
            fallback:         'index.html',
            logger:           $loggerAttempt->value,
            byteRangeService: $rangeAttempt->value,
            overwrite:        FileServerOverwriteForSpa::create($server),
        ));
    }

    private function __construct(
        private Server $server,
        private string $fallback,
        private LoggerInterface $logger,
        private ByteRangeService $byteRangeService,
        private FileServerOverwriteInterface|false $overwrite = false,
    ) {
    }

    private function notFound():Response {
        return new Response(status:HttpStatus::NOT_FOUND);
    }

    private function redirect(string $to):Response {
        return new Response(
            status: HttpStatus::FOUND,
            headers: [
                'Location' => $to,
            ],
        );
    }


    /**
     * Success response.
     * @param  mixed    $data
     * @param  int      $status
     * @param  array    $headers
     * @return Response
     */
    private function success(
        mixed $data = '',
        int $status = 200,
        array $headers = [],
    ):Response {
        return new Response(
            status : $status,
            headers: $headers,
            body   : $data,
        );
    }

    /**
     * Something is wrong, notify the client with a code and a message.
     * @param  false|string $message
     * @param  int          $status
     * @param  array        $headers
     * @return Response
     */
    private function failure(
        false|string $message = false,
        int $status = 500,
        array $headers = []
    ):Response {
        if (false === $message) {
            $message = HttpStatus::getReason($status);
        }
        
        return new Response(
            status : $status,
            headers: $headers,
            body   : $message,
        );
    }

    public function serve(Request $request):Response {
        $path             = urldecode($request->getUri()->getPath());
        $server           = $this->server;
        $fallback         = $this->fallback;
        $overwrite        = $this->overwrite;
        $byteRangeService = $this->byteRangeService;
        $logger           = $this->logger;

        if (!$server->www || strpos($path, '../')) {
            return $this->notFound();
        }
        
        $fileName = $server->www.$path;

        if ($overwrite) {
            $fileName = $overwrite->overwrite($fileName);
        }


        if (isDirectory($fileName)) {
            if (str_ends_with($path, '/') && File::exists("$fileName$fallback")) {
                return $this->redirect(to:"$path$fallback");
            }

            if (File::exists("$fileName/$fallback")) {
                return $this->redirect(to:"$path/$fallback");
            }
        }
        
        if (!isFile($fileName)) {
            return $this->failure(status:HttpStatus::NOT_FOUND);
        }

        $attachmentHeaders = [];
        if (Mime::isAttachment($fileName)) {
            $slashedName       = sprintf('"%s"', addcslashes(basename($path), '"\\'));
            $attachmentHeaders = [
                'Content-Disposition' => "attachment; filename=$slashedName",
                'Content-Type'        => 'application/octet-stream',
            ];
        }

        $rangedResponseAttempt = $byteRangeService->file(
            fileName  : $fileName,
            rangeQuery: $request->getHeader("Range") ?? '',
        );

        if (!$rangedResponseAttempt->error) {
            return $rangedResponseAttempt->value;
        }

        $fileAttempt = File::open($fileName, 'r');
        if ($fileAttempt->error) {
            $logger->error($fileAttempt->error);
            return $this->failure();
        }

        $file   = $fileAttempt->value;
        $stream = $file->getAmpFile();

        $fileSizeAttempt = File::getSize($fileName);
        if ($fileSizeAttempt->error) {
            $logger->error($fileSizeAttempt->error);
            return $this->failure();
        }
        $fileSize = $fileSizeAttempt->value;

        $mimeType = Mime::findContentType($fileName);
        return $this->success(
            data   : $stream,
            headers: [
                "Accept-Ranges"  => "bytes",
                "Content-Type"   => $mimeType,
                "Content-Length" => $fileSize,
                ...$attachmentHeaders,
            ],
        );
    }
}