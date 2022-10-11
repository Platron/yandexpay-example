<?php

class Client
{
    private $curl;

    public function __construct()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $this->curl = $curl;
    }

    public function get($url, $parameters)
    {
        $url .= '?' . http_build_query($parameters);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, 0);

        return curl_exec($this->curl);
    }

    public function post($url, $parameters)
    {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($parameters));

        return curl_exec($this->curl);
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }
}
