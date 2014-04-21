<?php
namespace Vda\Http;

class HttpClient implements IHttpClient
{
    public function send(Request $request)
    {
        $request->uri();
        //TODO case insensitive header
        $host = $request->headers()->get('Host');
        $request->headers()->set('Connection', 'Close');
        $socket = fsockopen($host, 80, $errorNumber, $errorString, 30);
        if (!$socket) {
            throw new \Exception("{$errorString} ({$errorNumber})");
        } else {
            fwrite($socket, $request->__toString());
            $res = '';
            while (!feof($socket)) {
                $res .= fgets($socket, 1024);
            }
            fclose($socket);
            return Response::createFromString($res);
        }
    }
}
