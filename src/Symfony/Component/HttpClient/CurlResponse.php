<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient;

use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Exception\JsonException;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Exception\ClientException;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Exception\RedirectionException;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Exception\ServerException;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * A factory to instantiate the best possible HTTP client for the runtime.
 */
class CurlResponse implements ResponseInterface
{
    /**
     * @var bool
     */
    private $is_exec = false;

    /**
     * @var array
     */
    private $headers = [];
    /**
     * @var array
     */
    private $info = [
        'response_headers'  => [],
        'http_code'         => 0,
        'error'             => null,
        'errno'             => 0,
        'debug'             => '',
    ];

    /** @var object|resource */
    private $handle;
    private $id;
    private $timeout = 0;

    /**
     * @var array
     */
    private $curlInfo = [
    ];

    /**
     * @var string
     */
    private $content = '';
    /**
     * @var array
     */
    private $jsonData = null;


    /**
     * @internal
     */
    public function __construct($ch, array $options = [], string $method = 'GET',  string $originalUrl = null)
    {
        $this->id = (int) $ch;
        $this->info['http_method']  = $method;
        $this->timeout = $options['timeout'] ?? null;
        
        $this->info['user_data']    = $options['user_data'] ?? null;
        $this->info['max_duration'] = $options['max_duration'] ?? null;
        $this->info['start_time']   = $this->info['start_time'] ?? microtime(true);
        if(!empty($originalUrl)){
            $this->info['original_url'] = $originalUrl;
        }
        else{
            $this->info['original_url'] = isset($this->info['url']) ? $this->info['url'] : curl_getinfo($ch, \CURLINFO_EFFECTIVE_URL);
        }
        // if (empty($this->info['response_headers'])) {
        //     // Used to keep track of what we're waiting for
        //     curl_setopt($ch, \CURLOPT_PRIVATE, \in_array($method, ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true) && 1.0 < (float) ($options['http_version'] ?? 1.1) ? 'H2' : 'H0'); // H = headers + retry counter
        // }
        // $info = &$this->info;
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'headerFn']);
        $this->handle = $ch;

    }


    /**
     * @return void
     */
    private function curlExec($force = false)
    {
        if(!$force ){
            if($this->is_exec){
                return true;
            }
        }
        $content = curl_exec($this->handle);
        $this->is_exec = true;
        if($content === false){
            $this->info['errno'] = curl_errno($this->handle);
            $this->info['error'] = curl_error($this->handle);
        }
        if(!empty($content)){
            $this->content = $content;
        }
        $this->getInfo();
    }


    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        if(!$this->is_exec){
            $this->curlExec();
        }
        return $this->info['http_code'];
    }


    /**
     * @param bool $throw
     * @return array
     */
    public function getHeaders(bool $throw = true): array
    {
        if(!$this->is_exec){
            $this->curlExec();
        }
        if(!empty($this->headers)){
            return $this->headers;
        }
        return $this->headers;
    }



    /**
     * @param bool $throw
     * @return string
     */
    public function getContent(bool $throw = true): string
    {
        if(!$this->is_exec){
            $this->curlExec();
        }

        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->content;
    }



    /**
     * {@inheritdoc}
     */
    public function toArray(bool $throw = true): array
    {
        if(!$this->is_exec){
            $this->curlExec();
        }
        if (empty($content = $this->getContent($throw))) {
            $errorMsg = 'Response body is empty. ' . $this->getErrorMsg();
            throw new JsonException($errorMsg);
        }

        if (null !== $this->jsonData) {
            return $this->jsonData;
        }
        try {
            $content = json_decode($content, true, 512, \JSON_BIGINT_AS_STRING);
        } catch (\JsonException $e) {
            throw new JsonException($e->getMessage().sprintf(' for "%s".', $this->getInfo('url')), $e->getCode());
        }

        if (!\is_array($content)) {
            throw new JsonException(sprintf('JSON content was expected to decode to an array, "%s" returned for "%s".', get_debug_type($content), $this->getInfo('url')));
        }

        if (null !== $this->content) {
            // Option "buffer" is true
            return $this->jsonData = $content;
        }

        return $content;
    }




    /**
     * {@inheritdoc}
     */
    public function cancel(): void
    {

    }



    /**
     * @param string|null $type
     * @return mixed
     */
    public function getInfo(string $type = null)
    {   
        if(!$this->is_exec){
            $this->curlExec();
        }
        if (empty($this->curlInfo)) {
            $this->curlInfo = curl_getinfo($this->handle);
            $info = array_merge($this->info, $this->curlInfo);

            // workaround curl not subtracting the time offset for pushed responses
            // if (isset($info['url']) && $info['start_time'] / 1000 < $info['total_time']) {
            //     $info['total_time'] -= $info['starttransfer_time'] ?: $info['total_time'];
            //     $info['starttransfer_time'] = 0.0;
            // }
            if(empty($info['end_time'])){
                $info['end_time'] = microtime(true);
            }
            if(empty($info['total_time']) && !empty($info['start_time']) && !empty($info['end_time'])){
                $info['total_time'] =  bcsub($info['end_time']. '', $info['start_time'] . '', 6); //毫秒
            }
            // $waitFor = curl_getinfo($this->handle, \CURLINFO_PRIVATE);
            // if ('H' !== $waitFor[0] && 'C' !== $waitFor[0]) {
            //     curl_setopt($this->handle, \CURLOPT_VERBOSE, false);
            //     $this->finalInfo = $info;
            // }
            $this->info = $info;
        }
        return null !== $type ? $this->info[$type] ?? null : $this->info;
    }



    private function checkStatusCode()
    {
        $code = $this->getInfo('http_code');

        if (500 <= $code) {
            throw new ServerException($this);
        }

        if (400 <= $code) {
            throw new ClientException($this);
        }

        if (300 <= $code) {
            throw new RedirectionException($this);
        }
    }



    /**
     * @param object|resource $ch
     * @param string $headerStr
     * @return int
     */
    private function headerFn($ch, $headerStr)
    {
        $responseHeaders    = explode("\r\n", substr($headerStr, 0, -2));
        $headers = [];
        $response_headers = [];
        $debug = '';
        foreach ($responseHeaders as $h) {
            $response_headers[] = $h;
            if (11 <= \strlen($h) && '/' === $h[4] && preg_match('#^HTTP/\d+(?:\.\d+)? (\d\d\d)(?: |$)#', $h, $m)) {
                if (empty($headers)) {
                    $debug .= "< \r\n";
                    $headers = [];
                }
                //$this->info['http_code'] = (int) $m[1];
            } elseif (2 === \count($m = explode(':', $h, 2))) {
                $headers[strtolower($m[0])][] = ltrim($m[1]);
            }
            $debug .= "< {$h}\r\n";
        }
        
        $debug .= "< \r\n";
        $this->info['debug'] .= $debug;
        $this->headers = array_merge($this->headers, $headers);
        $this->info['response_headers'] = array_merge($this->info['response_headers'], $response_headers);
        return strlen($headerStr);
    }


    /**
     * @return string
     */
    public function getErrorMsg(): string
    {
        $errorMsg = '';
        if(!empty($this->info['errno'])){
            $errorMsg = $this->info['errno'] . ':';
        }
        if(!empty($this->info['error'])){
            $errorMsg .= $this->info['error'];
        }
        return $errorMsg;
    }


    public function __destruct()
    {
        try {
            if (null === $this->timeout) {
                return; // Unused pushed response
            }
        } finally {
            curl_setopt($this->handle, \CURLOPT_VERBOSE, false);
            curl_close($this->handle);
        }
    }

}