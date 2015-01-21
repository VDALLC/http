<?php
namespace Vda\Http;

use \Vda\Util\ParamStore\ParamStore;

class Request
{
    private $method;
    private $path;
    private $protocol;
    private $get;
    private $post;
    private $cookies;
    private $files;
    private $headers;
    private $body;

    /**
     * @var string
     */
    private $rewroteUri;

    private function __construct(
        $method,
        $uri,
        $get = null,
        $post = null,
        $cookies = null,
        $files = null,
        $headers = null,
        $body = false,
        $protocol = 'HTTP/1.1'
    ) {
        $this->method = $method;
        $this->get = $this->envelopParams($get);
        $this->post = $this->envelopParams($post);
        $this->cookies = $this->envelopParams($cookies);
        $this->files = $this->envelopParams($files);
        $this->headers = $this->envelopParams($headers);
        $this->body = $body;
        $this->protocol = $protocol;
        $this->setUri($uri);
    }

    public static function create(
        $uri,
        $method = 'GET',
        array $get = array(),
        array $post = array(),
        array $cookies = array(),
        array $files = array(),
        array $headers = array(),
        $body = null
    ) {
        return new self($method, $uri, $get, $post, $cookies, $files, $headers, $body);
    }

    public static function createFromGlobals()
    {
        $info = explode('?', $_SERVER['REQUEST_URI']);
        return new self($_SERVER['REQUEST_METHOD'], $info[0]);
    }

    public static function restoreHeaders()
    {
        $headers = array();

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }

            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }

    public function path()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function rewroteUri()
    {
        return $this->rewroteUri;
    }

    public function setRewroteUri($uri)
    {
        $this->rewroteUri = $uri;
    }

    public function uri($addons = null)
    {
        $get = $this->get()->toArray();
        if (is_array($addons)) {
            $get = array_merge($get, $addons);
        }

        $query = (count($get) == 0) ? '' : '?' . http_build_query($get, null, '&');

        return $this->path . $query;
    }

    public function setUri($uri)
    {
        $info = parse_url($uri);
        $this->path = isset($info['path']) ? $info['path'] : '/'; // for parse_url('?') path will not be set
        if (!empty($info['host'])) {
            $this->headers()->set('Host', $info['host']);
        }
        if (!empty($info['query'])) {
            parse_str($info['query'], $arr);
            $this->get()->addAll($arr);
        }
    }

    /**
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function protocol()
    {
        return $this->protocol;
    }

    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * @return ParamStore
     */
    public function get()
    {
        if (is_null($this->get)) {
            $this->get = $this->envelopParams($_GET);
        }

        return $this->get;
    }

    /**
     * @return ParamStore
     */
    public function post()
    {
        if (is_null($this->post)) {
            $this->post = $this->envelopParams($_POST);
        }

        return $this->post;
    }

    /**
     * @return ParamStore
     */
    public function cookies()
    {
        if (is_null($this->cookies)) {
            $this->cookies = $this->envelopParams($_COOKIE);
        }

        return $this->cookies;
    }

    /**
     * @return ParamStore
     */
    public function files()
    {
        if (is_null($this->files)) {
            $this->files = $this->envelopParams($_FILES);
        }

        return $this->files;
    }

    /**
     * @return ParamStore
     */
    public function headers()
    {
        if (is_null($this->headers)) {
            $this->headers = $this->envelopParams(self::restoreHeaders());
        }
        return $this->headers;
    }

    /**
     * @return string
     */
    public function body()
    {
        if ($this->body === false) {
            $contentType = $this->headers()->get('Content-Type');
            if (stripos($contentType, 'multipart/form-data') === 0) {
                $this->buildBody();
            } else {
                $this->body = file_get_contents('php://input');
            }
        } elseif (is_null($this->body)) {
            $this->buildBody();
        }

        $len = mb_strlen($this->body, '8bit');
        if ($len > 0) {
            $this->headers()->set('Content-Length', $len);
        }

        return $this->body;
    }

    public function buildBody($contentType = null, $boundary = null)
    {
        $this->body = '';

        if (is_null($contentType)) {
            $contentType = $this->headers()->get('Content-Type');

            if (is_null($contentType)) {
                if (count($this->files()) > 0) {
                    $contentType = 'multipart/form-data';
                } else {
                    $contentType = 'application/x-www-form-urlencoded';
                }
            }
        }

        if (preg_match('!^multipart/form-data(?:; boundary=(.*))$!i', $contentType, $m)) {
            if (is_null($boundary)) {
                $boundary = empty($m[1]) ? uniqid() : $m[1];
            }

            $contentType = 'multipart/form-data; boundary=' . $boundary;
            $this->buildMultipartBody($boundary);
        } elseif (stripos($contentType, 'application/x-www-form-urlencoded') === 0) {
            $this->body = http_build_query($this->post()->toArray(), null, '&');
        } else {
            throw new \RuntimeException('Unsupported content type: ' . $contentType);
        }

        $this->headers()->set('Content-Type', $contentType);
        $this->headers()->set('Content-Length', mb_strlen($this->body, '8bit'));
    }

    public function __toString()
    {
        // before headers because buildBody() set headers
        $body = $this->body();

        $headers = '';
        foreach ($this->headers()->toArray() as $name => $value) {
            $headers .= "{$name}: {$value}\r\n";
        }

        return $this->method() . ' ' . $this->uri() . ' ' . $this->protocol() . "\r\n" .
                $headers . "\r\n" . $body;
    }

    private function buildMultipartBody($boundary)
    {
        foreach ($this->post() as $k => $v) {
            $this->body .= $this->encodeMultipartVariable($boundary, $k, $v);
        }

        foreach ($this->files() as $k => $v) {
            $this->body .= $this->encodeMultipartFile($boundary, $k, $v['name'], $v['tmp_name'], $v['type']);
        }

        $this->body .= "--{$boundary}--\r\n\r\n";
    }

    private function encodeMultipartVariable($boundary, $name, $value, array $path = array())
    {
        $result = '';
        $path[] = $name;

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $result .= $this->encodeMultipartVariable($boundary, $k, $v, $path);
            }
        } else {
            $k = $this->buildVariableName($path);
            $result .= "--{$boundary}\r\n";
            $result .= "Content-Disposition: form-data; name=\"{$k}\"\r\n";
            $result .= "\r\n{$value}\r\n";
        }

        return $result;
    }

    private function encodeMultipartFile(
        $boundary,
        $name,
        $filename,
        $tmpFilename,
        $fileType,
        array $path = array()
    ) {
        $result = '';
        $path[] = $name;

        if (is_array($filename)) {
            foreach (array_keys($filename) as $k) {
                $result .= $this->encodeMultipartFile($boundary, $k, $filename[$k], $tmpFilename[$k], $fileType[$k], $path);
            }
        } else {
            $k = $this->buildVariableName($path);
            $result .= "--{$boundary}\r\n";
            $result .= "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$filename}\"\r\n";
            $result .= "Content-Type: {$fileType}\r\n";
            $result .= "\r\n" . file_get_contents($tmpFilename) . "\r\n";
        }

        return $result;
    }

    private function buildVariableName($path)
    {
        $result = '';

        foreach ($path as $part) {
            if ($result == '') {
                $result .= $part;
            } else {
                $result .= '[' . $part . ']';
            }
        }

        return $result;
    }

    private function envelopParams($params)
    {
        return is_null($params) ? null : new ParamStore($params);
    }
}
