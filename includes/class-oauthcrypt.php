<?php

class OauthCrypt
{ 
    private $key;
    /**
     * __construct function.
     *
     * @access public
     * @param mixed $action (default: null)
     * @return void
     */
    public function __construct()
    {
        $keyContents = file_get_contents('/usr/local/encryptKey.txt');
        $this->key = \Defuse\Crypto\Key::loadFromAsciiSafeString($keyContents);
    }

    public function encrypt($str)
    {
        return \Defuse\Crypto\Crypto::encrypt($str, $this->getKey());
    }

    public function decrypt($str)
    {   
        return \Defuse\Crypto\Crypto::decrypt($str, $this->getKey());
    }

    private function getKey()
    {
        return $this->key;
    }
}
