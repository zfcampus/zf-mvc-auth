<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Router\RouteMatch;
use Zend\Permissions\Acl\Acl;

class DefaultAuthorizationListener
{
    /**
     * @var Acl
     */
    protected $acl;

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
    public function __construct(Acl $acl, array $restControllers = array())
    {
        $this->acl = $acl;
        $this->restControllers = $restControllers;
    }

    /**
     * Attempt to authorize the discovered identity based on the ACLs present
     * 
     * @param MvcAuthEvent $mvcAuthEvent 
     * @return true|ApiProblemResponse
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
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
        if (!$identity instanceof Identity\IdentityInterface) {
            return;
        }

        $resource = $this->buildResourceString($routeMatch, $request);
        if (!$resource) {
            return;
        }

        // If the resource does not exist, add it. Theoretically, though, this
        // means that the current identity is already allowed.
        if (!$this->acl->hasResource($resource)) {
            $this->acl->addResource($resource);
        }

        $identity = $mvcAuthEvent->getIdentity();
        if (!$this->acl->isAllowed($identity, $resource, $request->getMethod())) {
            $response->setStatusCode(403);
            $response->setReasonPhrase('Forbidden');
            return $response;
        }
        return true;
    }

    /**
     * Creates a resource string based on the controller service name and type
     *
     * For REST services (those passed to the constructor), it returns one of:
     *
     * - <controller service name>::resource
     * - <controller service name>::collection
     *
     * For all others, it uses the "action" route match parameter:
     *
     * - <controller service name>::<action>
     *
     * If it cannot resolve a controller service name, boolean false is returned.
     * 
     * @param RouteMatch $routeMatch 
     * @param Request $request 
     * @return false|string
     */
    public function buildResourceString(RouteMatch $routeMatch, Request $request)
    {
        // Considerations:
        // - We want the controller service name
        $controller = $routeMatch->getParam('controller', false);
        if (!$controller) {
            return false;
        }

        // - Is this an RPC or a REST call?
        //   - Basically, if it's not in the zf-rest configuration, we assume REST
        if (!array_key_exists($controller, $this->restControllers)) {
            $action = $routeMatch->getParam('action', 'index');
            return sprintf('%s::%s', $controller, $action);
        }

        //   - If it is a REST controller, we need to know if we have a 
        //     resource or a controller. The way to determine that is if we have 
        //     an identifier. We find that info from the route parameters.
        $identifierName = $this->restControllers[$controller];
        $id = $this->getIdentifier($identifierName, $routeMatch, $request);
        if ($id) {
            return sprintf('%s::resource', $controller);
        }
        return sprintf('%s::collection', $controller);
    }

    /**
     * Attempt to retrieve the identifier for a given request
     *
     * Checks first if the $identifierName is in the route matches, and then
     * as a query string parameter.
     * 
     * @param string $identifierName 
     * @param RouteMatch $routeMatch 
     * @param Request $request 
     * @return false|mixed
     */
    protected function getIdentifier($identifierName, RouteMatch $routeMatch, Request $request)
    {
        $id = $routeMatch->getParam($identifierName, false);
        if ($id) {
            return $id;
        }
        return $request->getQuery($identifierName, false);
    }
}
