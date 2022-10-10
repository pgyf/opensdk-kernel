<?php

namespace Pgyf\Opensdk\Kernel\Form;

use Pgyf\Opensdk\Kernel\Exceptions\RuntimeException;
use function file_put_contents;
use function md5;
use function pathinfo;
use const PATHINFO_EXTENSION;
use function strtolower;
use Pgyf\Opensdk\Kernel\Symfony\Component\Mime\MimeTypes;
use Pgyf\Opensdk\Kernel\Symfony\Component\Mime\Part\DataPart;
use function sys_get_temp_dir;
use function tempnam;

class File extends DataPart
{
    /**
     * @throws RuntimeException
     */
    public static function withContents(
        string $contents,
        ?string $filename = null,
        ?string $contentType = null,
        ?string $encoding = null
    ): DataPart {
        if (null === $contentType) {
            $mimeTypes = new MimeTypes();

            if ($filename) {
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $contentType = $mimeTypes->getMimeTypes($ext)[0] ?? 'application/octet-stream';
            } else {
                $tmp = tempnam(sys_get_temp_dir(), 'pgyfopensdk');
                if (! $tmp) {
                    throw new RuntimeException('Failed to create temporary file.');
                }

                file_put_contents($tmp, $contents);
                $contentType = $mimeTypes->guessMimeType($tmp) ?? 'application/octet-stream';
                $filename = md5($contents).'.'.($mimeTypes->getExtensions($contentType)[0] ?? null);
            }
        }

        return new self($contents, $filename, $contentType, $encoding);
    }
}
