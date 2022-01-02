<?php

namespace CatPaw\Sessions;

use Amp\File\File;
use Amp\LazyPromise;
use Amp\Promise;
use CatPaw\Attributes\Sessions\Session;
use Throwable;
use function Amp\File\createDirectoryRecursively;
use function Amp\File\deleteFile;
use function Amp\File\exists;
use function Amp\File\isDirectory;
use function Amp\File\openFile;
use function Amp\File\write;

class FileSystemSessionOperations implements SessionOperationsInterface {

	/**
	 * @param int    $ttl session time to live in seconds.
	 * @param string $dirname session directory name.
	 * @param bool $keepAlive if true the session creation time will be
	 * refreshed every time the session is requested, which will not allow the session to die
	 * unless the session has been inactive for <b>$ttl</b> time.
	 */
	public function __construct(
		private int    $ttl,
		private string $dirname,
		private bool   $keepAlive,
	) {
		$this->dirname = preg_replace('/[\\/\\\]+(?=$)/', '', $this->dirname);
	}

	private array $sessions = [];

	/**
	 * @inheritDoc
	 */
	public function startSession(string $id): Promise {
		return new LazyPromise(function() use ($id) {
			$session = Session::create();
			$filename = "$this->dirname/$id";
			if((yield exists($filename))) {
				$contents = '';
				/** @var File $file */
				$file = yield openFile($filename, 'r');
				while(($chunk = yield $file->read()))
					$contents .= $chunk;
				yield $file->close();

				$data = json_decode($contents, true)??false;
				$session->setStorage($data["STORAGE"]);
				$session->setTime($data["TIME"]);
			} else {
				$id = yield $this->makeSessionID();
				$storage = [];
				$session->setStorage($storage);
				$session->setTime(time());
			}

			$session->setId($id);
			yield $this->setSession($session);
			return $session;
		});
	}


	/**
	 * @inheritDoc
	 */
	public function validateSession(string $id): Promise {
		return new LazyPromise(function() use ($id) {
			try {
				$time = time();
				if($id) {
					if(!isset($this->sessions[$id])) {
						$session = yield $this->startSession($id);
					} else {
						$session = $this->sessions[$id];
					}

					if($session) {
						/** @var Session $session */
						$session = $this->sessions[$id];
						if($session->getTime() + $this->ttl < $time) {
							yield $this->removeSession($id);
						} else {
							if($this->keepAlive)
								$session->setTime($time);
							return $session;
						}
					}
				}

				$session = Session::create();
				$session->setTime($time);
				$session->setId(yield $this->makeSessionID());
				yield $this->setSession($session);
				return $session;
			} catch(Throwable) {
				return false;
			}
		});
	}

	/**
	 * @inheritDoc
	 */
	public function issetSession(string $id): Promise {
		return new LazyPromise(function() use ($id) {
			return isset($this->sessions[$id]) || (yield exists("$this->dirname/$id"));
		});
	}

	/**
	 * @inheritDoc
	 */
	public function setSession(Session $session): Promise {
		return new LazyPromise(function() use ($session) {
			$this->sessions[$session->getId()] = $session;
			return true;
		});
	}

	/**
	 * @inheritDoc
	 */
	public function persistSession(string $id): Promise {
		return new LazyPromise(function() use ($id) {
			if(!yield isDirectory($this->dirname))
				yield createDirectoryRecursively($this->dirname);


			$filename = "$this->dirname/$id";

			/** @var File $file */
			$file = yield openFile($filename, 'w+');

			/** @var Session $session */
			$session = yield $this->validateSession($id);

			yield $file->write(json_encode([
											   "STORAGE" => $session->storage(),
											   "TIME"    => $session->getTime(),
										   ]));
			yield $file->close();
			return true;
		});
	}

	/**
	 * @inheritDoc
	 */
	public function removeSession(string $id): Promise {
		return new LazyPromise(function() use ($id) {
			$filename = "$this->dirname/$id";
			if(yield exists($filename))
				yield deleteFile($filename);

			unset($this->sessions[$id]);

			return true;
		});
	}

	/**
	 * @inheritDoc
	 */
	public function makeSessionID(): Promise {
		return new LazyPromise(function() {
			$id = hash('sha3-224', rand());
			while(yield $this->issetSession($id)) {
				$id = yield new LazyPromise(function() {
					return hash('sha3-224', rand());
				});
			}

			return $id;
		});
	}
}