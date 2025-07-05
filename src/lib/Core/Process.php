<?php
namespace CatPaw\Core;

use function Amp\ByteStream\buffer;
use function Amp\ByteStream\pipe;
use Amp\ByteStream\WritableStream;
use Amp\Process\Process as AmpProcess;
use Psr\Log\LoggerInterface;
use Throwable;

class Process {
    /**
     * Execute a command.
     * @param  string               $command       Command to run.
     * @param  false|WritableStream $output        Send the output of the process to this stream.
     * @param  false|string         $workDirectory Work directory of the command.
     * @param  false|Signal         $kill          When this signal is triggered the process is killed.
     * @return Result<int>
     */
    public static function execute(
        string $command,
        false|WritableStream $output = false,
        false|string $workDirectory = false,
        false|Signal $kill = false,
    ):Result {
        try {
            $logger = Container::get(LoggerInterface::class)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $process = AmpProcess::start($command, $workDirectory?:null);

            if ($kill) {
                $kill->listen(static function() use ($process, $logger) {
                    if (!$process->isRunning()) {
                        return;
                    }

                    try {
                        $process->signal(9);
                    } catch(Throwable $error) {
                        $logger->error($error);
                    }
                });
            }

            if ($output) {
                pipe($process->getStdout(), $output);
                pipe($process->getStderr(), $output);
            }
            $code = $process->join();
        } catch(Throwable $error) {
            return error($error);
        }

        return ok($code);
    }

    /**
     * Execute a command and return its output.
     * @param  string         $command command to run
     * @return Result<string>
     */
    public static function get(string $command):Result {
        [$reader, $writer] = duplex();
        self::execute($command, $writer)->unwrap($error);
        if ($error) {
            return error($error);
        }
        return ok(buffer($reader));
    }
}