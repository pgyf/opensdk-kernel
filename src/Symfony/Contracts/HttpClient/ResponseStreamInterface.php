<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient;

/**
 * Yields response chunks, returned by HttpClientInterface::stream().
 *
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @extends \Iterator<ResponseInterface, ChunkInterface>
 */
interface ResponseStreamInterface extends \Iterator
{
    /**
     * @return ResponseInterface
     */
    public function key();

    /**
     * @return ChunkInterface
     */
    public function current();
}
