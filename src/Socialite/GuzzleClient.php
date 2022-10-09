<?php

namespace Pgyf\Opensdk\Kernel\Socialite;

class GuzzleClient extends \Pgyf\Opensdk\Kernel\Symfony\Component\HttpClient\CurlHttpClient
{

    /**
     * @param string $url
     * @param array $options
     * @return GuzzleResponse
     */
    public function post(string $url, array $options = [])
    {
        if(!empty($options['form_params'])){
            $options['body'] = $options['form_params'];
            unset($options['form_params']);
        }
        $response = $this->request('POST', $url, $options);
        return new GuzzleResponse($response);
    }


    /**
     * @param string $url
     * @param array $options
     * @return GuzzleResponse
     */
    public function get(string $url, array $options = [])
    { 
        $response = $this->request('GET', $url, $options);
        return new GuzzleResponse($response);
    }

}
