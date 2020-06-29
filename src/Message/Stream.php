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
        return (string)$result;
    }

    public function close()
    {
        if ($this->stream) {
            fclose($this->stream);
        }
        $this->detach();
    }

    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;
        return $stream;
    }

    public function getSize()
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

    public function eof()
    {
        return $this->stream ? feof($this->stream) : true;
    }

    public function isSeekable()
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

    public function isWritable()
    {
        return is_resource($this->stream);
    }

    public function write($string)
    {
        $result = $this->stream ? fwrite($this->stream, $string) : false;
        if ($result !== false) {
            return $result;
        }
        throw new RuntimeException('Could not write to stream.');
    }

    public function isReadable()
    {
        return is_resource($this->stream);
    }

    public function read($length)
    {
        if (!$this->stream) {
            return false;
        }
        $data = $data = fread($this->stream, $length);

        if (is_string($data)) {
            return $data;
        }

        throw new RuntimeException('Could not read from stream.');
    }

    public function getContents()
    {
        if (!$this->stream) {
            return '';
        }
        return stream_get_contents($this->stream);
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
