<?php

namespace ZFTest\MvcAuth\TestAsset;

class AuthenticationService
{
    protected $identity;

    public function setIdentity($identity)
    {
        $this->identity = $identity;
    }

    public function getIdentity()
    {
        return $this->identity;
    }

    public function getStorage()
    {
        return $this;
    }

    public function write($identity)
    {
        return $this->setIdentity($identity);
    }
}
