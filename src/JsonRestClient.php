<?php
namespace Vda\Http;

use Exception;

class JsonRestClient extends HttpClient implements IRestClient
{
    protected $url;
    protected $defaultData;
    protected $headers = array();

    public function __construct($url, array $defaultData = array(), array $headers = array())
    {
        $this->url = $url;
        $this->defaultData = $defaultData;
        $this->headers = $headers;
    }

    public function request($path, $data)
    {
        $post = array_merge($this->defaultData, $data);
        $request = Request::create($this->url . $path, 'POST', array(), $post, array(), array(), $this->headers);
        $response = $this->send($request);
        $body = json_decode($response->getBody());
        if ($response->getStatus() != 200) {
            throw new Exception(is_object($body) ? $body->message : $response->getBody(), $response->getStatus());
        }
        return $body;
    }
}
