<?php

namespace CatPaw\Web;

use function Amp\File\isDirectory;
use CatPaw\Directory;
use function CatPaw\error;
use CatPaw\File;
use function CatPaw\ok;
use CatPaw\Unsafe;
use CatPaw\Web\Attributes\Session;
use function hash;
use function rand;
use Throwable;

class FileSystemSessionOperations implements SessionOperationsInterface {
    /** @inheritDoc */
    public static function create(
        int $ttl,
        string $directoryName,
        bool $keepAlive,
    ):self {
        return new self(
            $ttl,
            $directoryName,
            $keepAlive,
        );
    }

    private function __construct(
        private readonly int $ttl,
        private string $directoryName,
        private readonly bool $keepAlive,
    ) {
        $this->directoryName = preg_replace('/[\\/\\\]+(?=$)/', '', $this->directoryName);
    }

    private array $sessions = [];

    /** @inheritDoc */
    public function startSession(string $id): Unsafe {
        $session  = Session::create();
        $filename = "$this->directoryName/$id";
        if ((File::exists($filename))) {
            $file = File::open($filename);

            if ($file->error) {
                return error($file->error);
            }

            $readingAttempt = $file->value->readAll()->await();

            if ($readingAttempt->error) {
                return error($readingAttempt->error);
            }

            $data = json_decode($readingAttempt->value, true) ?? false;

            if ($data) {
                $storage = $data["STORAGE"] ?? [];
                $session->setStorage($storage);
                $session->setTime($data["TIME"] ?? time());
            } else {
                $storage = [];
                $session->setStorage($storage);
                $session->setTime(time());
            }
        } else {
            $id      = $this->makeSessionId();
            $storage = [];
            $session->setStorage($storage);
            $session->setTime(time());
        }

        $session->setId($id);
        $this->setSession($session);
        return ok($session);
    }

    /** @inheritDoc */
    public function validateSession(string $id): Unsafe {
        try {
            $time = time();
            if ($id) {
                if (!isset($this->sessions[$id])) {
                    $sessionAttempt = $this->startSession($id);
                    if ($sessionAttempt->error) {
                        return error($sessionAttempt->error);
                    }
                    $session = $sessionAttempt->value;
                    if (!$session) {
                        return ok(false);
                    }
                    $id = $session->getId();
                }

                /** @var Session $session */
                $session = $this->sessions[$id];
                if ($time > $session->getTime() + $this->ttl) {
                    $this->removeSession($id);
                } else {
                    if ($this->keepAlive) {
                        $session->setTime($time);
                    }
                    return ok($session);
                }
            }

            $session = Session::create();
            $session->setTime($time);
            $session->setId($this->makeSessionID());
            $this->setSession($session);
            return ok($session);
        } catch (Throwable) {
            return ok(false);
        }
    }

    /** @inheritDoc */
    public function issetSession(string $id): bool {
        return isset($this->sessions[$id]) || (File::exists("$this->directoryName/$id"));
    }

    /** @inheritDoc */
    public function setSession(Session $session): bool {
        $this->sessions[$session->getId()] = $session;
        return true;
    }

    /** @inheritDoc */
    public function persistSession(string $id): Unsafe {
        if (!isDirectory($this->directoryName)) {
            if ($error = Directory::create($this->directoryName)->error) {
                return error($error);
            }
        }

        $filename = "$this->directoryName/$id";

        $session = $this->validateSession($id);
        if ($session->error) {
            return error($session->error);
        }
            
        $file = File::open($filename, 'w+');
        if ($file->error) {
            return error($file->error);
        }

        $writingAttempt = $file->value->write(json_encode([
            "STORAGE" => $session->value->getStorage(),
            "TIME"    => $session->value->getTime(),
        ]))->await();
        if ($writingAttempt->error) {
            return error($writingAttempt->error);
        }

        $file->value->close();
        return ok();
    }

    /** @inheritDoc */
    public function removeSession(string $id): Unsafe {
        $filename = "$this->directoryName/$id";
        if (File::exists($filename)) {
            if ($error = File::delete($filename)->error) {
                return error($error);
            }
        }
        unset($this->sessions[$id]);
        return ok();
    }

    /** @inheritDoc */
    public function makeSessionId(): string {
        $id = hash('sha3-224', (string)rand());
        $id = $id?:'';
        while ($this->issetSession($id)) {
            $id = hash('sha3-224', (string)rand());
        }
        return $id;
    }
}
