<?php

namespace Formwork\Files;

use Formwork\Formwork;
use Formwork\Utils\FileSystem;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\MimeType;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;

class File
{
    /**
     * File path
     */
    protected string $path;

    /**
     * File name
     */
    protected string $name;

    /**
     * File extension
     */
    protected string $extension;

    /**
     * File uri
     */
    protected string $uri;

    /**
     * File MIME type
     */
    protected string $mimeType;

    /**
     * File type in a human-readable format
     */
    protected ?string $type;

    /**
     * File size in a human-readable format
     */
    protected string $size;

    /**
     * File hash
     */
    protected string $hash;

    /**
     * Create a new File instance
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->name = basename($path);
        $this->extension = FileSystem::extension($path);
        $this->mimeType = FileSystem::mimeType($path);
        $this->uri = Uri::resolveRelative($this->name, HTTPRequest::root() . ltrim(Formwork::instance()->request(), '/'));
        $this->size = FileSystem::formatSize(FileSystem::fileSize($path));
    }

    /**
     * Get file path
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get file name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get file extension
     */
    public function extension(): string
    {
        return $this->extension;
    }

    /**
     * Get file URI
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Get file MIME type
     */
    public function mimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get file type in a human-readable format
     */
    public function type(): ?string
    {
        if (isset($this->type)) {
            return $this->type;
        }
        if (Str::startsWith($this->mimeType, 'image')) {
            return $this->type = 'image';
        }
        if (Str::startsWith($this->mimeType, 'text')) {
            return $this->type = 'text';
        }
        if (Str::startsWith($this->mimeType, 'audio')) {
            return $this->type = 'audio';
        }
        if (Str::startsWith($this->mimeType, 'video')) {
            return $this->type = 'video';
        }
        if ($this->mimeType === MimeType::fromExtension('pdf')) {
            return $this->type = 'pdf';
        }
        if ($this->matchExtensions(['doc', 'docx', 'odt', 'odm', 'ott'])) {
            return $this->type = 'document';
        }
        if ($this->matchExtensions(['ppt', 'pptx', 'pps', 'odp', 'otp'])) {
            return $this->type = 'presentation';
        }
        if ($this->matchExtensions(['xls', 'xlsx', 'ods', 'ots'])) {
            return $this->type = 'spreadsheet';
        }
        if ($this->matchExtensions(['gz', '7z', 'bz2', 'rar', 'tar', 'zip'])) {
            return $this->type = 'archive';
        }
        return null;
    }

    /**
     * Get file size
     */
    public function size(): string
    {
        return $this->size;
    }

    /**
     * Get file hash
     */
    public function hash(): string
    {
        if (isset($this->hash)) {
            return $this->hash;
        }
        return $this->hash = hash_file('sha256', $this->path);
    }

    /**
     * Match MIME type with an array of extensions
     */
    private function matchExtensions(array $extensions): bool
    {
        $mimeTypes = array_map(
            static fn (string $extension): string => MimeType::fromExtension($extension),
            $extensions
        );
        return in_array($this->mimeType, $mimeTypes, true);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
