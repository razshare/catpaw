<?php

namespace CatPaw\Web;

use CatPaw\Unsafe;
use CatPaw\Web\Attributes\Session;

interface SessionOperationsInterface {
    /**
     * Create operations for sessions.
     * @param  int                        $ttl
     * @param  string                     $directoryName
     * @param  bool                       $keepAlive
     * @return SessionOperationsInterface
     */
    public static function create(
        int $ttl,
        string $directoryName,
        bool $keepAlive,
    ):self;

    /**
     * Start a session or retrieve an already existing session by id.\
     * If the session id already exists, load it, otherwise create a new session.
     * @param  string                $id session id (if blank a new session is created).
     * @return Unsafe<Session|false>
     */
    public function startSession(string $id): Unsafe;

    /**
     * @return string a new unique session id.
     */
    public function makeSessionId(): string;

    /**
     * @param  string $id session id
     * @return bool   true if a session with the given id exists and is alive, false otherwise.
     */
    public function issetSession(string $id): bool;

    /**
     * Save a session in memory.\
     * The difference "persist" and "set" is that
     * the "persist" method will save data on a permanent storage
     * like a database or even directly to the file system.
     * @param  Session $session
     * @return bool    true if the session has been saved, false otherwise.
     */
    public function setSession(Session $session): bool;

    /**
     * Validate a session id and get a session back.\
     * The builtin `FileSystemSessionOperations` implementation will create a new session if the session id does not exist or the session expired.\
     * This means the resulting <b>Session</b> instance could have a different <b>id</b> than
     * the one given as input.
     * @param  string                $id
     * @return Unsafe<Session|false> the <b>Session</b> instance or false if something went wrong.
     */
    public function validateSession(string $id): Unsafe;

    /**
     * Save a session permanently.\
     * The difference "persist" and "set" is that
     * the "persist" method will save data on a permanent storage
     * like a database or even directly to the file system.
     * @param  string       $id session id
     * @return Unsafe<void>
     */
    public function persistSession(string $id): Unsafe;

    /**
     * Removes a session from memory and from the permanent storage.
     * @param  string       $id session id
     * @return Unsafe<void>
     */
    public function removeSession(string $id): Unsafe;
}
