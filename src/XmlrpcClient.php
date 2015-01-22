<?php
namespace Vda\Http;

use Exception;

class XmlrpcClient extends HttpClient
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

    public function call($method, $params)
    {
        $rpcOptions = array(
            'escaping'  => 'markup',
            'encoding'  => 'UTF-8',
        );
        $body = xmlrpc_encode_request($method, array_merge($this->defaultData, $params), $rpcOptions);
        $request = Request::create($this->url, 'POST', array(), array(), array(), array(), $this->headers, $body);
        $response = $this->send($request);
//        echo($response->getBody());die;
        if ($response->getStatus() != 200) {
            throw new Exception($response->getBody(), $response->getStatus());
        }
        return xmlrpc_decode($response->getBody(), 'UTF-8');
    }
}
