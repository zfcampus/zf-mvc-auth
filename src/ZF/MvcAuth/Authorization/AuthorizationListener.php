<?php

namespace ZF\MvcAuth\Authorization;

use Zend\Permissions\Acl\Acl;
use ZF\MvcAuth\MvcAuthEvent;
use Zend\Http\Request as HttpRequest;
use ZF\MvcAuth\Identity\GuestIdentity;

class AuthorizationListener
{
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $request  = $mvcEvent->getRequest();
        $response = $mvcEvent->getResponse();
        $configuration = $mvcEvent->getApplication()->getServiceManager()->get('Configuration');

        if (!$request instanceof HttpRequest) {
            return;
        }

        if (!isset($configuration['zf-mvc-auth']['authorization']['controller'])) {
            return;
        }


        $authControllers = $configuration['zf-mvc-auth']['authorization']['controller'];

        $params = $mvcEvent->getRouteMatch()->getParams();
        $method = $mvcEvent->getRequest()->getMethod();

        $identity = $mvcAuthEvent->getIdentity();

        if (isset($authControllers[$params['controller']])) {
            $controller = $authControllers[$params['controller']];
            $allAction = isset($controller['all_action']) ? $controller['all_action'] : false;
            $allMethod = isset($controller['all_method']) ? $controller['all_method'] : false;
            if (($allAction || (isset($controller['action']) && (in_array($params['action'], $controller['action'])))) &&
                ($allMethod || (isset($controller['method']) && (in_array($method, $controller['method']))))) {
                    if ($identity instanceof GuestIdentity) {
                        // @todo return 403
                        $response->setStatusCode(403);
                        $response->setReasonPhrase('Forbidden.');
                        return $response;
                    }
                }

        }
        return true;
    }
}
