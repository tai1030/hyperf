<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Cache\Driver;

use Hyperf\Cache\Collector\FileStorage;
use Hyperf\Cache\Exception\CacheException;
use Hyperf\Cache\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;

class FileSystemDriver extends Driver
{
    /**
     * @var string
     */
    protected $storePath = BASE_PATH . '/runtime/caches';

    public function __construct(ContainerInterface $container, array $config)
    {
        parent::__construct($container, $config);
        if (! file_exists($this->storePath)) {
            $results = mkdir($this->storePath, 0777, true);
            if (! $results) {
                throw new CacheException('Has no permission to create cache directory!');
            }
        }
    }

    public function getCacheKey(string $key)
    {
        return $this->getPrefix() . $key . '.cache';
    }

    public function get($key, $default = null)
    {
        $file = $this->getCacheKey($key);
        if (! file_exists($file)) {
            return $default;
        }

        /** @var FileStorage $obj */
        $obj = $this->packer->unpack(file_get_contents($file));
        if ($obj->isExpired()) {
            return $default;
        }

        return $obj->getData();
    }

    public function fetch(string $key, $default = null): array
    {
        $file = $this->getCacheKey($key);
        if (! file_exists($file)) {
            return [false, $default];
        }

        /** @var FileStorage $obj */
        $obj = $this->packer->unpack(file_get_contents($file));
        if ($obj->isExpired()) {
            return [false, $default];
        }

        return [true, $obj->getData()];
    }

    public function set($key, $value, $ttl = null)
    {
        $file = $this->getCacheKey($key);
        $content = $this->packer->pack(new FileStorage($value, $ttl));

        $result = file_put_contents($file, $content, FILE_BINARY);

        return (bool) $result;
    }

    public function delete($key)
    {
        $file = $this->getCacheKey($key);
        if (file_exists($file)) {
            if (! is_writable($file)) {
                return false;
            }
            unlink($file);
        }

        return true;
    }

    public function clear()
    {
        return $this->clearPrefix('');
    }

    public function getMultiple($keys, $default = null)
    {
        if (! is_array($keys)) {
            throw new InvalidArgumentException('The keys is invalid!');
        }

        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        if (! is_array($values)) {
            throw new InvalidArgumentException('The values is invalid!');
        }

        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple($keys)
    {
        if (! is_array($keys)) {
            throw new InvalidArgumentException('The keys is invalid!');
        }

        foreach ($keys as $index => $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key)
    {
        $file = $this->getCacheKey($key);

        return file_exists($file);
    }

    public function clearPrefix(string $prefix): bool
    {
        $files = glob($this->getPrefix() . $prefix . '*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                continue;
            }
            unlink($file);
        }

        return true;
    }

    protected function getPrefix()
    {
        return $this->storePath . DIRECTORY_SEPARATOR . $this->prefix;
    }
}
