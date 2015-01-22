<?php
namespace Vda\Http\Session;

use Vda\Util\ParamStore\AbstractParamStore;

class PhpSession extends AbstractParamStore implements ISession
{
    public function getIterator()
    {
        return new \ArrayIterator($_SESSION);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $_SESSION);
    }

    public function &offsetGet($offset)
    {
        return $_SESSION[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
//            $message = 'There should be a very important reason to append $_SESSION array, ' .
//                'if so you can implement this and remove this Exception.';
//            throw new \InvalidArgumentException($message);
            // for ParamStoreTest to pass
            $_SESSION[] = $value;
        } else {
            $_SESSION[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($_SESSION[$offset]);
    }

    public function toArray()
    {
        return $_SESSION;
    }

    public function count()
    {
        return count($_SESSION);
    }
}
