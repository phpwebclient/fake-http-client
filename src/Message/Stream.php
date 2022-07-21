<?php

declare(strict_types=1);

namespace Webclient\Fake\Message;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

final class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $stream;

    public function __construct(string $content)
    {
        $this->stream = fopen('php://temp', 'w+');
        fwrite($this->stream, $content);
        $this->rewind();
    }

    public function __toString()
    {
        try {
            $result = $this->getContents();
        } catch (Throwable $exception) {
            $result = '';
        }
        return $result;
    }

    public function close()
    {
        if ($this->stream) {
            fclose($this->stream);
        }
        $this->detach();
    }

    /**
     * @inheritDoc
     * @return resource
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;
        return $stream;
    }

    public function getSize(): ?int
    {
        if (!$this->stream) {
            return null;
        }
        $stats = fstat($this->stream);
        return array_key_exists('size', $stats) ? (int)$stats['size'] : null;
    }

    public function tell()
    {
        return $this->stream ? ftell($this->stream) : false;
    }

    public function eof(): bool
    {
        return !$this->stream || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return is_resource($this->stream);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if ($this->stream && fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Could not seek in stream.');
        }
    }

    public function rewind()
    {
        if ($this->stream && rewind($this->stream) === false) {
            throw new RuntimeException('Could not rewind stream.');
        }
    }

    public function isWritable(): bool
    {
        return is_resource($this->stream);
    }

    public function write($string): int
    {
        $result = $this->stream ? fwrite($this->stream, $string) : false;
        if ($result !== false) {
            return $result;
        }
        throw new RuntimeException('Could not write to stream.');
    }

    public function isReadable(): bool
    {
        return is_resource($this->stream);
    }

    public function read($length)
    {
        if (!$this->stream) {
            return false;
        }
        $data = fread($this->stream, $length);

        if (is_string($data)) {
            return $data;
        }

        throw new RuntimeException('Could not read from stream.');
    }

    public function getContents(): string
    {
        if (!$this->stream) {
            return '';
        }
        $contents = stream_get_contents($this->stream);
        if (!is_string($contents)) {
            throw new RuntimeException('error get stream contents');
        }
        return $contents;
    }

    public function getMetadata($key = null)
    {
        if (!$this->stream) {
            return null;
        }

        $meta = stream_get_meta_data($this->stream);
        if (!$key) {
            return $meta;
        }

        return array_key_exists($key, $meta) ? $meta[$key] : null;
    }
}
