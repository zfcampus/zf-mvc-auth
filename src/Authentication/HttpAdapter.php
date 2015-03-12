<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Authentication\AuthenticationService;
use Zend\Http\Request;
use Zend\Http\Response;
use ZF\MvcAuth\Identity;
use ZF\MvcAuth\MvcAuthEvent;

class HttpAdapter extends AbstractAdapter
{
    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var HttpAuth
     */
    private $httpAuth;

    /**
     * @param HttpAuth $httpAuth
     * @param AuthenticationService $authenticationService
     */
    public function __construct(HttpAuth $httpAuth, AuthenticationService $authenticationService)
    {
        $this->httpAuth = $httpAuth;
        $this->authenticationService = $authenticationService;
    }

    /**
     * @return array Array of types this adapter can handle.
     */
    public function provides()
    {
        $provides = array();

        if ($this->httpAuth->getBasicResolver()) {
            $provides[] = 'basic';
        }

        if ($this->httpAuth->getDigestResolver()) {
            $provides[] = 'digest';
        }

        return $provides;
    }

    /**
     * Perform pre-flight authentication operations.
     *
     * If invoked, issues a client challenge.
     *
     * @param Request $request
     * @param Response $response
     */
    public function preAuth(Request $request, Response $response)
    {
        $this->httpAuth->setRequest($request);
        $this->httpAuth->setResponse($response);
        $this->httpAuth->challengeClient();
    }

    /**
     * Attempt to authenticate the current request.
     *
     * @param Request $request
     * @param Response $response
     * @param MvcAuthEvent $mvcAuthEvent
     * @return false|IdentityInterface False on failure, IdentityInterface
     *     otherwise
     */
    public function authenticate(Request $request, Response $response, MvcAuthEvent $mvcAuthEvent)
    {
        $this->httpAuth->setRequest($request);
        $this->httpAuth->setResponse($response);

        $result = $this->authenticationService->authenticate($this->httpAuth);
        $mvcAuthEvent->setAuthenticationResult($result);

        if (! $result->isValid()) {
            return false;
        }

        $resultIdentity = $result->getIdentity();

        // Pass fully discovered identity to AuthenticatedIdentity instance
        $identity = new Identity\AuthenticatedIdentity($resultIdentity);

        // But determine the name separately
        $name = $resultIdentity;
        if (is_array($resultIdentity)) {
            $name = isset($resultIdentity['username'])
                ? $resultIdentity['username']
                : (string) array_shift($resultIdentity);
        }
        $identity->setName($name);

        return $identity;
    }
}
