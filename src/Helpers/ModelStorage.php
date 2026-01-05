<?php namespace Mchuluq\Larv\Rbac\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Mchuluq\Larv\Rbac\Exceptions\OptimisticLockException;

class ModelStorage{

    protected int|string $id;
    protected string $name;
    protected int $ttl; // seconds, 0 = forever
    
    protected ?int $expectedVersion = null;

    protected $driver = 'model_storage';

    public function __construct(string $name,$id, int $ttl = 0){
        $this->name   = $name;
        $this->ttl    = $ttl;
        $this->id     = $id;
        if (!$this->id) {
            throw new \RuntimeException('ModelStorage requires authenticated user.');
        }
    }

    protected function key(): string{
        return "model_storage:{$this->id}:{$this->name}";
    }

    /* =====================================================
     |  Session-like public API
     ===================================================== */

    public function get(string $key = null, $default = null){
        $storage = $this->readStorage($default);
        return $key === null ? $storage['payload'] : Arr::get($storage['payload'], $key, $default);
    }

    public function put(string $key, $value): self{
        return $this->set($key, $value);
    }

    public function set(string $key, $value): self{
        $storage = $this->readStorage([]);
        $this->assertVersion($storage);
        Arr::set($storage['payload'], $key, $value);
        $this->writeNext($storage, $storage['payload']);
        return $this;
    }

    public function has(string $key): bool{
        return Arr::has($this->get(), $key);
    }

    public function forget(string $key): self{
        $storage = $this->readStorage([]);
        $this->assertVersion($storage);
        Arr::forget($storage['payload'], $key);
        $this->writeNext($storage, $storage['payload']);
        return $this;
    }

    public function pull(string $key, $default = null){
        $storage = $this->readStorage([]);
        $this->assertVersion($storage);
        $value = Arr::get($storage['payload'], $key, $default);
        Arr::forget($storage['payload'], $key);
        $this->writeNext($storage, $storage['payload']);
        return $value;
    }

    public function all(): array{
        return $this->get() ?? [];
    }

    public function only(array $keys): array{
        return Arr::only($this->get(), $keys);
    }

    public function except(array $keys): array{
        return Arr::except($this->get(), $keys);
    }

    public function increment(string $key, int $by = 1): int{
        $storage = $this->readStorage([]);
        $this->assertVersion($storage);
        $current = (int) Arr::get($storage['payload'], $key, 0);
        $value   = $current + $by;
        Arr::set($storage['payload'], $key, $value);
        $this->writeNext($storage, $storage['payload']);
        return $value;
    }

    public function decrement(string $key, int $by = 1): int{
        return $this->increment($key, -$by);
    }

    public function replace(array $payload): self{
        $storage = $this->readStorage([]);
        $this->assertVersion($storage);
        $this->writeNext($storage, $payload);
        return $this;
    }

    public function clear(): void{
        Cache::store($this->driver)->forget($this->key());
        $this->expectedVersion = null;
    }

    public function reload(): self{
        $this->expectedVersion = null;
        $this->readStorage([]);
        return $this;
    }

    /* =====================================================
     |  Internal mechanics
     ===================================================== */

    protected function readStorage($default): array{
        $storage = Cache::store($this->driver)->get(
            $this->key(),
            function () use ($default) {
                return [
                    'version'    => 1,
                    'updated_at' => now()->toDateTimeString(),
                    'payload'    => (array) (
                        is_callable($default)
                            ? call_user_func($default)
                            : $default
                    ),
                ];
            }
        );
        if ($this->expectedVersion === null) {
            $this->expectedVersion = $storage['version'];
        }
        return $storage;
    }

    protected function assertVersion(array $storage): void{
        if (
            $this->expectedVersion !== null &&
            $storage['version'] !== $this->expectedVersion
        ) {
            throw new OptimisticLockException(
                "Storage [{$this->name}] was modified by another process/device."
            );
        }
    }

    protected function writeNext(array $current, array $payload): void{
        $next = [
            'version'    => $current['version'] + 1,
            'updated_at' => now()->toDateTimeString(),
            'payload'    => $payload,
        ];
        $this->expectedVersion = $next['version'];
        $this->writeStorage($next);
    }

    protected function writeStorage(array $storage): void{
        if ($this->ttl > 0) {
            Cache::store($this->driver)->put($this->key(), $storage, $this->ttl);
        } else {
            Cache::store($this->driver)->forever($this->key(), $storage);
        }
    }
}
