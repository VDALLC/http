<?php
namespace Vda\Http;

class JsonResponse extends Response
{
    public function __construct($body, $status = 200, array $headers = array())
    {
        $header = array('Content-Type' => 'application/json');
        parent::__construct(json_encode($body), $status, array_merge($header, $headers));
    }
}
