<?php

declare(strict_types=1);

namespace Webclient\Fake\Message;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class UploadedFile implements UploadedFileInterface
{

    /**
     * @var StreamInterface
     */
    private $body;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $error;

    /**
     * @var bool;
     */
    private $moved = false;

    public function __construct(string $name, string $contents, string $type, int $error)
    {
        $this->name = $name;
        $this->body = new Stream($contents);
        $this->type = $type ? $type : 'application/octet-stream';
        $this->error = $error;
    }

    public function getStream()
    {
        if ($this->moved) {
            throw new RuntimeException('Uploaded file ' . $this->name . ' has already been moved');
        }
        return $this->body;
    }

    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new RuntimeException('Uploaded file ' . $this->name . ' has already been moved');
        }

        if (!is_writable(dirname($targetPath)) || (is_file($targetPath) && !is_writable($targetPath))) {
            throw new InvalidArgumentException('Upload target path is not writable');
        }

        $target = fopen($targetPath, 'w');
        $this->body->rewind();
        while (!$this->body->eof()) {
            $data = $this->body->read(4096);
            fwrite($target, $data);
        }
        fclose($target);
        $this->moved = true;
    }

    public function getSize()
    {
        return $this->body->getSize();
    }

    public function getError()
    {
        return $this->error;
    }

    public function getClientFilename()
    {
        return $this->name;
    }

    public function getClientMediaType()
    {
        return $this->type;
    }
}
