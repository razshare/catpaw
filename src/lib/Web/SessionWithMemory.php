<?php
namespace CatPaw\Web;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Request;


use function CatPaw\Core\uuid;
use CatPaw\Web\Interfaces\ResponseModifier;


use CatPaw\Web\Interfaces\SessionInterface;

/**
 * In memory session.
 * @package CatPaw\Web
 */
class SessionWithMemory implements SessionInterface {
    /** @var array<SessionWithMemory> */
    private static array $cache = [];

    /**
     * @inheritdoc
     */
    public static function create(Request $request):self {
        $sessionIdCookie = $request->getCookie('session-id') ?? false;
        if ($sessionIdCookie) {
            $id = $sessionIdCookie->getValue();
            if (isset(self::$cache[$id])) {
                $session = self::$cache[$id];
                if ($session->validate()) {
                    return $session;
                }
            }
        }

        do {
            $id = uuid();
        } while (isset(self::$cache[$id]));
        $session = new SessionWithMemory(
            id: $id,
            ttl: 60 * 60 * 24,
            data: [],
        );

        return self::$cache[$id] = $session;
    }



    private false|int $stopped = false;
    private int $started;
    private int $expiration;

    /**
     *
     * @param  array<mixed> $data Session data.
     * @param  int          $ttl  Time to live in seconds.
     * @param  string       $id   Session id.
     * @return void
     */
    private function __construct(
        private array $data,
        // @phpstan-ignore-next-line
        private int $ttl,
        private string $id,
    ) {
        $this->started    = time();
        $this->expiration = $this->started + $ttl;
    }

    /**
     * @param  ResponseModifier $modifier
     * @return void
     */
    public function apply(ResponseModifier $modifier):void {
        $modifier->withCookies(new ResponseCookie('session-id', $this->id));
    }

    /**
     * @inheritdoc
     */
    public function validate():bool {
        if (false !== $this->stopped) {
            return false;
        }
        $now = time();
        return $now < $this->expiration ;
    }

    /**
     * @inheritdoc
     */
    public function has(string $key):bool {
        return isset($this->data[$key]);
    }

    /**
     * @inheritdoc
     */
    public function &ref(string $key, mixed $default = null):mixed {
        if (!$this->has($key)) {
            $this->data[$key] = $default;
        }
        return $this->data[$key];
    }

    /**
     * @inheritdoc
     */
    public function id():string {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function destroy():void {
        $this->stopped = time();
        unset(self::$cache[$this->id]);
    }
}
