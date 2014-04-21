<?php
namespace Vda\Http;

use Exception;
use Vda\Http\HttpClient;
use Vda\Http\IRestClient;
use Vda\Http\Request;

class JsonRestClient extends HttpClient implements IRestClient
{
    protected $url, $defaultData;

    public function __construct($url, array $defaultData = array())
    {
        $this->url = $url;
        $this->defaultData = $defaultData;
    }

    public function request($path, $data)
    {
        $post = array_merge($this->defaultData, $data);
        $request = Request::create($this->url . $path, 'POST', array(), $post);
        $response = $this->send($request);
        $body = json_decode($response->getBody());
        if ($response->getStatus() != 200) {
            throw new Exception(is_object($body) ? $body->message : $response->getBody(), $response->getStatus());
        }
        return $body;
    }
}
