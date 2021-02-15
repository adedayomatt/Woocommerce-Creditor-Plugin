<?php
require __DIR__.'/../vendor/autoload.php';

class Http{

    public $client;

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }

    public function get($url, $query = [], $headers = [], $options = [])
    {
        return $this->client->get($url, array_merge([
            'http_errors' => false,
            'headers' => $headers,
            'query' => $query
        ]), $options);
    }

    public function post($url, $query = [], $headers = [], $options = [])
    {
        return $this->client->post($url, array_merge([
            'http_errors' => true,
            'headers' => $headers,
            'query' => $query
        ]), $options);
    }

    public function request($method, $url, $query = [], $data = [], $headers = [], $options = [])
    {
        return $this->client->request($method, $url, array_merge([
            'http_errors' => false,
            'headers' => $headers,
            'query' => $query
        ]), $options);
    }


}