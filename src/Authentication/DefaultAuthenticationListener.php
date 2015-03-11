<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use OAuth2\Request as OAuth2Request;
use OAuth2\Server as OAuth2Server;
use RuntimeException;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Mvc\Router\RouteMatch;
use Zend\Http\Request as HttpRequest;
use ZF\MvcAuth\Identity;
use ZF\MvcAuth\MvcAuthEvent;

class DefaultAuthenticationListener
{
    /**
     * Map of API/version to authentication type pairs
     * 
     * @var array
     */
    private $authMap = array();

    /**
     * @var HttpAuth
     */
    protected $httpAdapter;

    /**
     * @var OAuth2Server
     */
    protected $oauth2Server;

    /**
     * Request methods that will not have request bodies
     *
     * @var array
     */
    protected $requestsWithoutBodies = array(
        'GET',
        'HEAD',
        'OPTIONS',
    );

    /**
     * Set the HTTP authentication adapter
     *
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
     * @param  OAuth2Server $oauth2Server
     * @return self
     */
    public function setOauth2Server(OAuth2Server $oauth2Server)
    {
        $this->oauth2Server = $oauth2Server;
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
     * @return mixed
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $request  = $mvcEvent->getRequest();
        $response = $mvcEvent->getResponse();

        if (!$request instanceof HttpRequest
            || $request->isOptions()
        ) {
            return;
        }

        $type = $this->getTypeFromMap($mvcEvent->getRouteMatch());
        if (false === $type
            && $this->httpAdapter instanceof HttpAuth
            && $this->oauth2Server instanceof OAuth2Server
        ) {
            // Ambiguous situation; no matching type in map, but multiple
            // authentication methods; do nothing.
            $identity = new Identity\GuestIdentity();
            $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
            return $identity;
        }

        $type = $type ?: $this->getTypeFromRequest($request);
        if (false === $type) {
            if ($this->httpAdapter instanceof HttpAuth) {
                $this->httpAdapter->setRequest($request);
                $this->httpAdapter->setResponse($response);
                $this->httpAdapter->challengeClient();
            }
            $identity = new Identity\GuestIdentity();
            $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
            return $identity;
        }

        switch ($type) {
            case 'basic':
            case 'digest':

                if (! $this->httpAdapter instanceof HttpAuth) {
                    $identity = new Identity\GuestIdentity();
                    $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
                    return $identity;
                }

                $this->httpAdapter->setRequest($request);
                $this->httpAdapter->setResponse($response);

                $auth   = $mvcAuthEvent->getAuthenticationService();
                $result = $auth->authenticate($this->httpAdapter);
                $mvcAuthEvent->setAuthenticationResult($result);

                if (! $result->isValid()) {
                    $identity = new Identity\GuestIdentity();
                    $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
                    return $identity;
                }

                $resultIdentity = $result->getIdentity();

                // Pass full discovered identity to AuthenticatedIdentity object
                $identity = new Identity\AuthenticatedIdentity($resultIdentity);

                // But determine name separately
                $name = $resultIdentity;
                if (is_array($resultIdentity)) {
                    $name = isset($resultIdentity['username'])
                        ? $resultIdentity['username']
                        : (string) $resultIdentity;
                }
                $identity->setName($name);

                // Set in MvcEvent
                $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
                return $identity;

            case 'oauth2':
            case 'bearer':

                if (!$this->oauth2Server instanceof OAuth2Server) {
                    $identity = new Identity\GuestIdentity();
                    $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
                    return $identity;
                }

                $content       = $request->getContent();
                $oauth2request = new OAuth2Request($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER, $content);

                if ($this->oauth2Server->verifyResourceRequest($oauth2request)) {
                    $token    = $this->oauth2Server->getAccessTokenData($oauth2request);
                    $identity = new Identity\AuthenticatedIdentity($token);
                    $identity->setName($token['user_id']);
                    $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
                    return $identity;
                }

                $identity = new Identity\GuestIdentity();
                $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
                return $identity;

            case 'token':
                throw new RuntimeException('zf-mvc-auth has not yet implemented a "token" authentication adapter');
        }
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
        $type       = false;
        $authHeader = $request->getHeader('Authorization');

        if ($authHeader) {
            $type = $this->getTypeFromAuthorizationHeader(trim($authHeader->getFieldValue()));
        }

        if (! $type
            && ! in_array($request->getMethod(), $this->requestsWithoutBodies)
            && $request->getHeaders()->has('Content-Type')
            && $request->getHeaders()->get('Content-Type')->match('application/x-www-form-urlencoded')
            && $request->getPost('access_token')
        ) {
            return 'oauth2';
        }

        if (! $type && null !== $request->getQuery('access_token')) {
            return 'oauth2';
        }

        return $type;
    }

    /**
     * Determine the authentication type from the authorization header contents
     * 
     * @param string $header 
     * @return false|string
     */
    private function getTypeFromAuthorizationHeader($header)
    {
        // we only support headers in the format: Authorization: xxx yyyyy
        if (strpos($header, ' ') === false) {
            return false;
        }

        list($type, $credential) = preg_split('# #', $header, 2);

        switch (strtolower($type)) {
            case 'basic':
                return 'basic';
            case 'digest':
                return 'digest';
            case 'bearer':
                return 'oauth2';
            default:
                return false;
        }
    }
}
