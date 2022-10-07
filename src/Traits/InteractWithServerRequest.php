<?php

declare(strict_types=1);

namespace Pgyf\Opensdk\Kernel\Traits;

use Pgyf\Opensdk\Kernel\HttpClient\RequestUtil;
//use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
//use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
//use Symfony\Component\HttpFoundation\Request;

trait InteractWithServerRequest
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    public function getRequest(): ServerRequestInterface
    {
        if (! $this->request) {
            $this->request = RequestUtil::createDefaultServerRequest();
        }

        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     * @return static
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    // public function setRequestFromSymfonyRequest(Request $symfonyRequest): self
    // {
    //     $psr17Factory = new Psr17Factory();
    //     $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);

    //     $this->request = $psrHttpFactory->createRequest($symfonyRequest);

    //     return $this;
    // }
}
