<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Authentication\AuthenticationServiceInterface;
use Zend\Http\Request;
use Zend\Http\Response;
use ZF\MvcAuth\Identity;
use ZF\MvcAuth\MvcAuthEvent;

class HttpAdapter extends AbstractAdapter
{
    /**
     * @var AuthenticationServiceInterface
     */
    private $authenticationService;

    /**
     * Authorization header token types this adapter can fulfill.
     *
     * @var array
     */
    protected $authorizationTokenTypes = array('basic', 'digest');

    /**
     * @var HttpAuth
     */
    private $httpAuth;

    /**
     * Base to use when prefixing "provides" strings
     *
     * @var null|string
     */
    private $providesBase;

    /**
     * @param HttpAuth $httpAuth
     * @param AuthenticationServiceInterface $authenticationService
     * @param null|string $providesBase
     */
    public function __construct(
        HttpAuth $httpAuth,
        AuthenticationServiceInterface $authenticationService,
        $providesBase = null
    ) {
        $this->httpAuth = $httpAuth;
        $this->authenticationService = $authenticationService;

        if (is_string($providesBase) && ! empty($providesBase)) {
            $this->providesBase = $providesBase;
        }
    }

    /**
     * Returns the "types" this adapter can handle.
     *
     * If no $providesBase is present, returns "basic" and/or "digest" in the
     * array, based on what resolvers are present in the adapter; if
     * $providesBase is present, the same strings are returned, only with the
     * $providesBase prefixed, along with a "-" separator.
     *
     * @return array Array of types this adapter can handle.
     */
    public function provides()
    {
        $providesBase = $this->providesBase ? $this->providesBase . '-' : '';
        $provides     = array();

        if ($this->httpAuth->getBasicResolver()) {
            $provides[] = $providesBase . 'basic';
        }

        if ($this->httpAuth->getDigestResolver()) {
            $provides[] = $providesBase . 'digest';
        }

        return $provides;
    }

    /**
     * Match the requested authentication type against what we provide.
     *
     * @param string $type
     * @return bool
     */
    public function matches($type)
    {
        return ($this->providesBase === $type || in_array($type, $this->provides(), true));
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
