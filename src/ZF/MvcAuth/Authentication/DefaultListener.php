<?php

namespace ZF\MvcAuth\Authentication;

use Zend\Http\Request as HttpRequest;
use ZF\MvcAuth\MvcAuthEvent;


class DefaultListener
{
    public function authenticate(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $request = $mvcEvent->getRequest();

        if (!$request instanceof HttpRequest) {
            return;
        }

        if ($request->getHeader('Authorization') === false) {
            return;
        }
    }
}