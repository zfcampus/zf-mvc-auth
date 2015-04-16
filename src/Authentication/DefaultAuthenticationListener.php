<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use OAuth2\Server as OAuth2Server;
use RuntimeException;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Mvc\Router\RouteMatch;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use ZF\MvcAuth\Identity;
use ZF\MvcAuth\MvcAuthEvent;

class DefaultAuthenticationListener
{
    /**
     * Attached authentication adapters
     *
     * @var AdapterInterface[]
     */
    private $adapters = array();

    /**
     * Supported authentication types
     *
     * @var array
     */
    private $authenticationTypes = array();

    /**
     * Map of API/version to authentication type pairs
     *
     * @var array
     */
    private $authMap = array();

    /**
     * Legacy reasons only: HttpAuth instance
     *
     * On invocation, this will be munged to an HttpAdapter instance, and this
     * property nullified.
     *
     * @deprecated
     * @var null|HttpAuth
     */
    protected $httpAdapter;

    /**
     * Attach an authentication adapter
     *
     * Adds the authentication adapter, and updates the list of supported
     * authentication types based on what the adapter provides.
     *
     * @param AdapterInterface $adapter
     */
    public function attach(AdapterInterface $adapter)
    {
        $this->adapters[] = $adapter;
        $this->authenticationTypes = array_unique(array_merge($this->authenticationTypes, $adapter->provides()));
    }

    /**
     * Add custom authentication types.
     *
     * This method allows specifiying additional authentication types, outside
     * of adapters, that your application supports. The values provided are
     * merged with any types already discovered.
     *
     * @param array $types
     */
    public function addAuthenticationTypes(array $types)
    {
        $this->authenticationTypes = array_unique(
            array_merge(
                $this->authenticationTypes,
                $types
            )
        );
    }

    /**
     * Retrieve the supported authentication types
     *
     * @return array
     */
    public function getAuthenticationTypes()
    {
        if ($this->httpAdapter instanceof HttpAuth) {
            // Legacy purposes only. We cannot munge the actual HttpAdapter instance
            // until we have the MvcAuthEvent (and thus the AuthenticationService),
            // so if an HttpAuth instance was directly attached, and this method is
            // queried before invocation, we report both basic and digest as being
            // available.
            return array_unique(array_merge(
                $this->authenticationTypes,
                array('basic', 'digest')
            ));
        }
        return $this->authenticationTypes;
    }

    /**
     * Set the HTTP authentication adapter
     *
     * This method is deprecated; create and attach an HttpAdapter instead.
     *
     * @deprecated
     * @param HttpAuth $httpAdapter
     * @return self
     */
    public function setHttpAdapter(HttpAuth $httpAdapter)
    {
        $this->httpAdapter = $httpAdapter;
        return $this;
    }

    /**
     * Set the OAuth2 server
     *
     * This method is deprecated; create and attach an OAuth2Adapter instead.
     *
     * @deprecated
     * @param  OAuth2Server $oauth2Server
     * @return self
     */
    public function setOauth2Server(OAuth2Server $oauth2Server)
    {
        $this->attach(new OAuth2Adapter($oauth2Server));
        return $this;
    }

    /**
     * Set the API/version to authentication type map.
     *
     * @param array $map
     */
    public function setAuthMap(array $map)
    {
        $this->authMap = $map;
    }

    /**
     * Listen to the authentication event
     *
     * @param MvcAuthEvent $mvcAuthEvent
     * @return null|Identity\IdentityInterface
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $this->attachHttpAdapter($mvcAuthEvent);

        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $request  = $mvcEvent->getRequest();
        $response = $mvcEvent->getResponse();

        if (!$request instanceof HttpRequest
            || $request->isOptions()
        ) {
            return;
        }

        $type = $this->getTypeFromMap($mvcEvent->getRouteMatch());
        if (false === $type && count($this->adapters) > 1) {
            // Ambiguous situation; no matching type in map, but multiple
            // authentication adapters; return a guest identity.
            $identity = new Identity\GuestIdentity();
            $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
            return $identity;
        }

        $type = $type ?: $this->getTypeFromRequest($request);
        if (false === $type) {
            // No authentication type known; trigger any pre-flight actions,
            // and return a guest identity.
            $this->triggerAdapterPreAuth($request, $response);
            $identity = new Identity\GuestIdentity();
            $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
            return $identity;
        }

        // Authenticate against first matching adapter
        $identity = $this->authenticate($type, $request, $response, $mvcAuthEvent);

        // If no identity returned, create a guest identity
        if (! $identity instanceof Identity\IdentityInterface) {
            $identity = new Identity\GuestIdentity();
        }

        $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
        return $identity;
    }

    /**
     * Match the controller to an authentication type, based on the API to
     * which the controller belongs.
     *
     * @param null|RouteMatch $routeMatch
     * @return string|false
     */
    private function getTypeFromMap(RouteMatch $routeMatch = null)
    {
        if (! $routeMatch) {
            return false;
        }

        $controller = $routeMatch->getParam('controller', false);
        if (false === $controller) {
            return false;
        }

        foreach ($this->authMap as $api => $type) {
            $api = rtrim($api, '\\') . '\\';
            if (strlen($api > $controller)) {
                continue;
            }

            if (0 === strpos($controller, $api)) {
                return $type;
            }
        }

        return false;
    }

    /**
     * Determine the authentication type based on request information
     *
     * @param HttpRequest $request
     * @return false|string
     */
    private function getTypeFromRequest(HttpRequest $request)
    {
        foreach ($this->adapters as $adapter) {
            $type = $adapter->getTypeFromRequest($request);
            if (false !== $type) {
                return $type;
            }
        }
        return false;
    }

    /**
     * Trigger the preAuth routine of each adapter
     *
     * This method is triggered if no authentication type was discovered in the
     * request.
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     */
    private function triggerAdapterPreAuth(HttpRequest $request, HttpResponse $response)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->preAuth($request, $response);
        }
    }

    /**
     * Invoke the adapter matching the given $type in order to peform authentication
     *
     * @param string $type
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @param MvcAuthEvent $mvcAuthEvent
     * @return false|Identity\IdentityInterface
     */
    private function authenticate($type, HttpRequest $request, HttpResponse $response, MvcAuthEvent $mvcAuthEvent)
    {
        foreach ($this->adapters as $adapter) {
            if (! $adapter->matches($type)) {
                continue;
            }

            return $adapter->authenticate($request, $response, $mvcAuthEvent);
        }

        return false;
    }

    /**
     * Attach the $httpAdapter as a proper adapter
     *
     * This is to allow using the setHttpAdapter() method along with the
     * AdapterInterface system, and will be removed in a future version.
     *
     * @deprecated
     * @param MvcAuthEvent $mvcAuthEvent
     */
    private function attachHttpAdapter(MvcAuthEvent $mvcAuthEvent)
    {
        if (! $this->httpAdapter instanceof HttpAuth) {
            return;
        }

        $this->attach(new HttpAdapter($this->httpAdapter, $mvcAuthEvent->getAuthenticationService()));
        $this->httpAdapter = null;
    }
}
