<?php
namespace Vda\Http\Session;

class PhpSessionService implements ISessionService
{
    private $params;

    public function __construct()
    {
        $this->params = new PhpSession;
    }

    public function open($sessionId = null)
    {
        session_start($sessionId);
    }

    public function close()
    {
        session_write_close();
    }

    public function clear()
    {
        session_destroy();
    }

    public function getParams()
    {
        return $this->params;
    }
}
