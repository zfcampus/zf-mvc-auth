<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authorization;

use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Router\RouteMatch;
use Zend\Permissions\Acl\Acl;
use ZF\MvcAuth\MvcAuthEvent;
use ZF\MvcAuth\Identity\IdentityInterface;

class DefaultAuthorizationListener
{
    /**
     * @var AuthorizationInterface
     */
    protected $authorization;

    /**
     * Array of controller_service_name/identifier_name pairs
     *
     * @var array
     */
    protected $restControllers;

    /**
     * @param Acl $acl
     * @param array $restControllers
     */
    public function __construct(AuthorizationInterface $authorization, array $restControllers = array())
    {
        $this->authorization   = $authorization;
        $this->restControllers = $restControllers;
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
        return $this->authorization->isAuthorized($identity, $resource, $request->getMethod());
    }
}
