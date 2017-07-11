<?php
namespace Vda\Http;


class RefererRedirectResponse extends RedirectResponse
{
    public function __construct($defaultUrl = '/')
    {
        parent::__construct(
            isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $defaultUrl,
            false
        );
    }
}
