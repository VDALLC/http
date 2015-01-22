<?php
namespace Vda\Http;

use Vda\Util\VarUtil as V;

class Response implements IResponse
{
    public static $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC-reschke-http-status-308-07
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    );

    protected $status = 200, $headers = array(), $body = '';

    public function __construct($body, $status = 200, array $headers = array())
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    /**
     * @param string $str
     * @return Response
     */
    public static function createFromString($str, $raw = false)
    {
        list($head, $body) = preg_split('~(\r\n|\n\r|\n){2}~', $str, 2);

        $headersStr = preg_split('~[\n\r]+~', $head);
        $statusLine = explode(' ', array_shift($headersStr), 3); // [0] - version, [1] - code, [2] -phrase
        $headers = array();
        foreach ($headersStr as $header) {
            list($name, $value) = preg_split('~:\s+~', $header, 2);
            $headers[$name] = $value;
        }

        if (!$raw && isset($headers['Transfer-Encoding']) && $headers['Transfer-Encoding'] == 'chunked') {
            $body = self::decodeChunked($body);
            unset($headers['Transfer-Encoding']);
        }

        return new self($body, $statusLine[1], $headers);
    }

    public static function decodeChunked($str)
    {
        for ($res = ''; !empty($str); $str = trim($str)) {
            $pos = strpos($str, "\r\n");
            $len = hexdec(substr($str, 0, $pos));
            $res .= substr($str, $pos + 2, $len);
            $str = substr($str, $pos + 2 + $len);
        }

        return $res;
    }

    /**
     * Check where given code is valid HTTP status
     *
     * @param int $code
     */
    public static function isValidStatus($code)
    {
        return isset(self::$statusTexts[$code]);
    }

    public static function getStatusLine($code)
    {
        if (self::isValidStatus($code)) {
            return 'HTTP/1.1 ' . $code . ' ' . self::$statusTexts[$code];
        } else {
            throw new \InvalidArgumentException('Undefined status code: ' . $code);
        }
    }

    public function setStatus($code)
    {
        $this->status = $code;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function getHeader($name, $ifNull = null)
    {
        return V::ifNull($this->headers[$name], $ifNull);
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function send($fp = null)
    {
        if (is_null($fp)) {
            header(self::getStatusLine($this->status));
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
            echo $this->body;
        } else {
            // TODO
        }
    }
}
