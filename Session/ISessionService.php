<?php
namespace Vda\Http\Session;

interface ISessionService
{
    /**
     * Start HTTP-session
     *
     * @param string $sessionId
     */
    public function open($sessionId = null);

    /**
     * Close current HTTP-session
     */
    public function close();


    public function clear();

    /**
     * Get current session parameters
     *
     * @return ISession;
     */
    public function getParams();
}
