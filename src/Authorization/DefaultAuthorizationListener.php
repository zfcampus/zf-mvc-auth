<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authorization;

use Zend\Http\Headers;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Router\RouteMatch;
use ZF\MvcAuth\MvcAuthEvent;
use ZF\MvcAuth\Identity\IdentityInterface;

class DefaultAuthorizationListener
{
    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * @param AuthorizationInterface $authorization
     */
    public function __construct(AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * Attempt to authorize the discovered identity based on the ACLs present
     *
     * @param MvcAuthEvent $mvcAuthEvent
     * @return bool
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        if ($mvcAuthEvent->isAuthorized()) {
            return;
        }

        $mvcEvent = $mvcAuthEvent->getMvcEvent();

        $request  = $mvcEvent->getRequest();
        if (!$request instanceof Request) {
            return;
        }

        $response  = $mvcEvent->getResponse();
        if (!$response instanceof Response) {
            return;
        }

        $routeMatch = $mvcEvent->getRouteMatch();
        if (!$routeMatch instanceof RouteMatch) {
            return;
        }

        $identity = $mvcAuthEvent->getIdentity();
        if (!$identity instanceof IdentityInterface) {
            return;
        }

        $resource = $mvcAuthEvent->getResource();
        $identity = $mvcAuthEvent->getIdentity();
        $isAuthorized = $this->authorization->isAuthorized($identity, $resource, $request->getMethod());

        // We need reset MVC response which can have been modified
        // by authentication layer. This avoid challenging client in case a
        // guest identity is allowed to access the resource after all.
        if ($isAuthorized) {
            // Resetting response set on mvc event is not sufficient
            // This denote another problem which
            $app = $mvcEvent->getApplication();
            $response = $app->getResponse();

            $response->setStatusCode(200);
            $response->setHeaders(new Headers());

            $mvcEvent->setResponse($response);
        }

        return $isAuthorized;
    }
}
