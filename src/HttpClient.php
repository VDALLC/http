<?php
namespace Vda\Http;

class HttpClient implements IHttpClient
{
    public function send(Request $request)
    {
        //TODO case insensitive header
        $host = $request->headers()->get('Host');

        $request->headers()->set('Connection', 'Close');
        if ($request->ssl()) {
            $host = 'ssl://' . $host;
        }

        $port = $request->port();
        if (empty($port)) {
            $port = $request->ssl() ? 443 : 80;
        }


        $socket = fsockopen($host, $port, $errorNumber, $errorString, 30);

        if (!$socket) {
            throw new \Exception("{$errorString} ({$errorNumber})");
        } else {
            fwrite($socket, $request->__toString());
            $res = '';
            while (!feof($socket)) {
                $res .= fgets($socket, 4096);
            }
            fclose($socket);

            return Response::createFromString($res);
        }
    }
}
