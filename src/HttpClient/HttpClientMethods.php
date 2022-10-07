<?php
declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\HttpClient;

use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Pgyf\Opensdk\Kernel\Symfony\Contracts\HttpClient\ResponseInterface;

trait HttpClientMethods
{
    /**
     * @param  string  $url
     * @param  array<string, mixed>  $options
     *
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->request('GET', $url, RequestUtil::formatOptions($options, 'GET'));
    }

    /**
     * @param  string  $url
     * @param  array<string, mixed>  $options
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->request('POST', $url, RequestUtil::formatOptions($options, 'POST'));
    }

    /**
     * @param  string  $url
     * @param  array<string, mixed>  $options
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function postJson(string $url, array $options = []): ResponseInterface
    {
        $options['headers']['Content-Type'] = 'application/json';

        return $this->request('POST', $url, RequestUtil::formatOptions($options, 'POST'));
    }

    /**
     * @param  string  $url
     * @param  array<string, mixed>  $options
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function postXml(string $url, array $options = []): ResponseInterface
    {
        $options['headers']['Content-Type'] = 'text/xml';

        return $this->request('POST', $url, RequestUtil::formatOptions($options, 'POST'));
    }

    /**
     * @param  string  $url
     * @param  array<string, mixed>  $options
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function patch(string $url, array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $url, RequestUtil::formatOptions($options, 'PATCH'));
    }

    /**
     * @param  string  $url
     * @param  array<string, mixed>  $options
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function patchJson(string $url, array $options = []): ResponseInterface
    {
        $options['headers']['Content-Type'] = 'application/json';

        return $this->request('PATCH', $url, RequestUtil::formatOptions($options, 'PATCH'));
    }

    /**
     * @param  string  $url
     * @param  array<string, mixed>  $options
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function put(string $url, array $options = []): ResponseInterface
    {
        return $this->request('PUT', $url, RequestUtil::formatOptions($options, 'PUT'));
    }

    /**
     * @param  string  $url
     * @param  array<string, mixed>  $options
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function delete(string $url, array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $url, RequestUtil::formatOptions($options, 'DELETE'));
    }
}
