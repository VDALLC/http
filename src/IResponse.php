<?php
namespace Vda\Http;

interface IResponse
{
    public static function getStatusLine($code);
    public function setStatus($code);
    public function getStatus();

    public function setHeader($name, $value);
    public function getHeader($name, $ifNull = null);

    public function setBody($body);
    public function getBody();

    public function send($fp = null);
}
