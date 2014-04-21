<?php
namespace Vda\Http;

interface IRestClient
{
    public function request($path, $data);
}
