<?php
namespace Vda\Http;

use Vda\Util\VarUtil as V;

class RefererRedirectResponse extends RedirectResponse
{
    public function __construct($defaultUrl = '/')
    {
        $url = V::ifNull($_SERVER['HTTP_REFERER'], $defaultUrl);
        parent::__construct($url, false);
    }
}
