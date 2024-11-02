<?php
namespace CatPaw\Web\Implementations\FileServer;

use function Amp\File\isDirectory;
use function Amp\File\isFile;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use CatPaw\Core\File;
use CatPaw\Web\HttpStatus;
use CatPaw\Web\Interfaces\ByteRangeInterface;
use CatPaw\Web\Interfaces\FileServerInterface;
use CatPaw\Web\Interfaces\FileServerOverwriteInterface;
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Mime;
use Psr\Log\LoggerInterface;

/**
 * A file server that serves static files as requested by the
 * client and return 404 responses whenever a static file is not found.
 * @package CatPaw\Web
 */
readonly class SpaFileServer implements FileServerInterface {
    public string $fallback;
    private FileServerOverwriteInterface $overwrite;
    public function __construct(
        private ServerInterface $server,
        private LoggerInterface $logger,
        private ByteRangeInterface $byteRange,
    ) {
        $this->fallback  = "index.html";
        $this->overwrite = new class($server) implements FileServerOverwriteInterface {
            public function __construct(private ServerInterface $server) {
            }
        
            public function overwrite(string $fileName, string $path):string {
                // Required for Spa mode
                if (isDirectory($fileName) || !File::exists($fileName)) {
                    return "{$this->server->staticsLocation()}/index.html";
                }
                return $fileName;
            }
        };
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
     * @param  mixed                $data
     * @param  int                  $status
     * @param  array<string,string> $headers
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
     * @param  false|string         $message
     * @param  int                  $status
     * @param  array<string,string> $headers
     * @return Response
     */
    private function failure(
        false|string $message = false,
        int $status = 500,
        array $headers = []
    ):Response {
        if (false === $message) {
            $message = HttpStatus::reason($status);
        }

        return new Response(
            status : $status,
            headers: $headers,
            body   : $message,
        );
    }

    public function serve(Request $request):Response {
        $path      = urldecode($request->getUri()->getPath());
        $server    = $this->server;
        $fallback  = $this->fallback;
        $overwrite = $this->overwrite;
        $byteRange = $this->byteRange;
        $logger    = $this->logger;

        if (!$server->staticsLocation() || strpos($path, '../')) {
            return $this->notFound();
        }

        // This smells.
        $fileName = $server->staticsLocation().$path;
        $fileName = $overwrite->overwrite($fileName, $path);


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

        $rangedResponse = $byteRange->file(
            fileName  : $fileName,
            rangeQuery: $request->getHeader("Range") ?? '',
        )->unwrap($error);

        if (!$error) {
            return $rangedResponse;
        }

        $file = File::open($fileName, 'r')->unwrap($error);
        if ($error) {
            $logger->error($error);
            return $this->failure();
        }

        $stream = $file->ampFile();

        $fileSize = File::size($fileName)->unwrap($error);
        if ($error) {
            $logger->error($error);
            return $this->failure();
        }

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
