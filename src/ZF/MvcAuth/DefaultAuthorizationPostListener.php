<?php

namespace ZF\MvcAuth;

class DefaultAuthorizationPostListener
{
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        // if there is no identity, return with 401 unauthorized
        if ($mvcAuthEvent->getIdentity() == null) {
            /** @var \Zend\Http\Response $response */
            $response = $mvcAuthEvent->getMvcEvent()->getResponse();
            $response->setStatusCode(401);
            $response->setReasonPhrase('Unauthorized');
            return $response;
        }
    }
}