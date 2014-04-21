<?php
namespace Vda\Http;

interface IHttpClient
{
    /**
     * @param Request $request
     * @return Response
     */
    public function send(Request $request);
}
