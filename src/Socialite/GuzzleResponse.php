<?php

namespace Pgyf\Opensdk\Kernel\Socialite;

use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\CurlResponse;

class GuzzleResponse
{

    /**
     * Undocumented variable
     * @var CurlResponse
     * @author lyf <381296986@qq.com>
     * @date   2022-10-09
     */
    protected $response;

    /**
     * @param array $defaultOptions     Default request's options
     * @param int   $maxHostConnections The maximum number of connections to a single host
     * @param int   $maxPendingPushes   The maximum number of pushed responses to accept in the queue
     *
     * @see HttpClientInterface::OPTIONS_DEFAULTS for available options
     */
    public function __construct(CurlResponse $response)
    {
        $this->response = $response;
    }

    /**
     * @param string $url
     * @param array $options
     * @return string
     */
    public function getBody(){
        return $this->response->getContent();
    }

}
