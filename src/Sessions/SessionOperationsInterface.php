<?php

namespace CatPaw\Sessions;

use Amp\Promise;
use CatPaw\Attributes\Sessions\Session;

interface SessionOperationsInterface {

	/**
	 * Start a session or retrieve an already existing session by id.<br/>
	 * If the session id already exists, load it, otherwise create a new session.<br/>
	 * @param string $id session id (if blank a new session is created).
	 * @return Promise
	 */
	public function startSession(string $id):Promise;

	/**
	 * @return Promise<string> a new unique session id.
	 */
	public function makeSessionID(): Promise;

	/**
	 * @param string $id session id
	 * @return Promise<bool> true if a session with the given id exists and is alive, false otherwise.
	 */
	public function issetSession(string $id): Promise;

	/**
	 * Save a session in memory.<br/>
	 * The difference "persist" and "set" is that
	 * the "persist" method will save data on a permanent storage
	 * like a database or even directly to the file system.
	 * @param Session $session
	 * @return Promise<bool> true if the session has been saved, false otherwise.
	 */
	public function setSession(Session $session): Promise;

	/**
	 * Validate a session id and get a session back.<br/>
	 * If the session id does not exist (or it expired) a new session is created.<br/>
	 * This means the resulting <b>Session</b> instance could have a different <b>id</b> than
	 * the one given as input.
	 * @param string $id
	 * @return Promise<false|Session> the <b>Session</b> instance or false if something went wrong.
	 */
	public function validateSession(string $id): Promise;

	/**
	 * Save a session permanently.<br/>
	 * The difference "persist" and "set" is that
	 * the "persist" method will save data on a permanent storage
	 * like a database or even directly to the file system.
	 * @param string $id session id
	 * @return Promise<bool> true if the session has been persisted, false otherwise.
	 */
	public function persistSession(string $id): Promise;

	/**
	 * Removes a session from memory and from the permanent storage.
	 * @param string $id session id
	 * @return Promise<bool> true if the session has been removed from both memory and permanent storage, false otherwise.
	 */
	public function removeSession(string $id): Promise;


}