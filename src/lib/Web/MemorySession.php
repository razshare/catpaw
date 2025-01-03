<?php
namespace CatPaw\Web;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Request;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use function CatPaw\Core\uuid;
use CatPaw\Web\Interfaces\ResponseModifierInterface;
use CatPaw\Web\Interfaces\SessionInterface;

/**
 * In memory session.
 * @package CatPaw\Web
 */
class MemorySession implements SessionInterface {
    /** @var array<MemorySession> */
    private static array $cache = [];

    /**
     * @inheritdoc
     */
    public function &set(string $key, mixed $value): mixed {
        $content = &$this->ref($key, $value);
        $content = $value;
        return $content;
    }

    /**
     * @inheritdoc
     */
    public static function create(Request $request):Result {
        $sessionIdCookie = $request->getCookie('session-id') ?? false;
        if ($sessionIdCookie) {
            $id = $sessionIdCookie->getValue();
            if (isset(self::$cache[$id])) {
                $session   = self::$cache[$id];
                $validated = $session->validate()->unwrap($error);
                if ($error) {
                    return error($error);
                }
                if ($validated) {
                    // @phpstan-ignore return.type
                    return ok($session);
                }
            }
        }

        do {
            $id = uuid();
        } while (isset(self::$cache[$id]));
        $session = new MemorySession(
            id: $id,
            ttl: 60 * 60 * 24,
            data: [],
        );

        // @phpstan-ignore return.type
        return ok(self::$cache[$id] = $session);
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
     * @inheritdoc
     */
    public function flush(ResponseModifierInterface $modifier):Result {
        $modifier->withCookies(new ResponseCookie('session-id', $this->id));
        return ok();
    }

    /**
     * @inheritdoc
     */
    public function validate():Result {
        if (false !== $this->stopped) {
            return ok(false);
        }
        $now = time();
        return ok($now < $this->expiration);
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
    public function destroy():Result {
        $this->stopped = time();
        unset(self::$cache[$this->id]);
        return ok();
    }
}
