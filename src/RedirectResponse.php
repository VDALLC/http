<?php
namespace Vda\Http;

class RedirectResponse extends Response
{
    public function __construct($url, $permanent = false)
    {
        $status = $permanent ? 301 : 302;
        $headers = array('Location' => $url);
        parent::__construct('', $status, $headers);
    }
}
