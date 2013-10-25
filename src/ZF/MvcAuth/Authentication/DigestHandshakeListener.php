<?php

namespace ZF\MvcAuth\Authentication;

use Zend\Mvc\MvcEvent;

class DigestHandshakeListener
{
    public function __invoke(MvcEvent $e)
    {
        // @todo Send the WWW-Authenticate header using Zend\Authentication\Adapter\Http
        $response = $e->getResponse();
        return $response;

    }
}
