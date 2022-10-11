<?php

namespace Pgyf\Opensdk\Kernel\HttpClient;

use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Psr\Log\LoggerInterface;

trait RetryableClient
{
    /**
     * @var array
     */
    //protected $retryConfig = [];

    /**
     * @var GenericRetryStrategy
     */
    protected $strategy = null;


    /**
     * @var int
     */
    protected $maxRetries = 1;

    /**
     * Undocumented function
     * @param array|bool $config
     * @return self
     */
    public function retry($config = []): self
    {
        if(empty($config)){
            return $this;
        }
        if($config === true){
            $config = [];
        }
        $config = RequestUtil::mergeDefaultRetryOptions($config);

        //$this->retryConfig = array_merge($this->retryConfig, $config);
        $strategy = new GenericRetryStrategy(
            // @phpstan-ignore-next-line
            (array) $config['status_codes'],
            // @phpstan-ignore-next-line
            (int) $config['delay'],
            // @phpstan-ignore-next-line
            (float) $config['multiplier'],
            // @phpstan-ignore-next-line
            (int) $config['max_delay'],
            // @phpstan-ignore-next-line
            (float) $config['jitter']
        );
        return $this->retryUsing($strategy, (int) $config['max_retries']);
    }


    public function retryUsing(
        RetryStrategyInterface $strategy,
        int $maxRetries = 1,
        LoggerInterface $logger = null
    ): self {
        $this->strategy     = $strategy;
        $this->maxRetries   = $maxRetries;
        return $this;
    }

}
