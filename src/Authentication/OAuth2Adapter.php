<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use OAuth2\Request as OAuth2Request;
use OAuth2\Server as OAuth2Server;
use Zend\Http\Request;
use Zend\Http\Response;
use ZF\MvcAuth\Identity;
use ZF\MvcAuth\MvcAuthEvent;

class OAuth2Adapter extends AbstractAdapter
{
    /**
     * Authorization header token types this adapter can fulfill.
     *
     * @var array
     */
    protected $authorizationTokenTypes = array('bearer');

    /**
     * @var OAuth2Server
     */
    private $oauth2Server;

    /**
     * Authentication types this adapter provides.
     *
     * @var array
     */
    private $providesTypes = array('oauth2');

    /**
     * Request methods that will not have request bodies
     *
     * @var array
     */
    private $requestsWithoutBodies = array(
        'GET',
        'HEAD',
        'OPTIONS',
    );

    /**
     * @param OAuth2Server $oauth2Server
     */
    public function __construct(OAuth2Server $oauth2Server, $types = null)
    {
        $this->oauth2Server = $oauth2Server;

        if (is_string($types) && ! empty($types)) {
            $types = array($types);
        }

        if (is_array($types)) {
            $this->providesTypes = $types;
        }
    }

    /**
     * @return array Array of types this adapter can handle.
     */
    public function provides()
    {
        return $this->providesTypes;
    }

    /**
     * Attempt to match a requested authentication type
     * against what the adapter provides.
     *
     * @param string $type
     * @return bool
     */
    public function matches($type)
    {
        return in_array($type, $this->providesTypes, true);
    }

    /**
     * Determine if the given request is a type (oauth2) that we recognize
     *
     * @param Request $request
     * @return false|string
     */
    public function getTypeFromRequest(Request $request)
    {
        $type = parent::getTypeFromRequest($request);

        if (false !== $type) {
            return 'oauth2';
        }

        if (! in_array($request->getMethod(), $this->requestsWithoutBodies)
            && $request->getHeaders()->has('Content-Type')
            && $request->getHeaders()->get('Content-Type')->match('application/x-www-form-urlencoded')
            && $request->getPost('access_token')
        ) {
            return 'oauth2';
        }

        if (null !== $request->getQuery('access_token')) {
            return 'oauth2';
        }

        return false;
    }

    /**
     * Perform pre-flight authentication operations.
     *
     * Performs a no-op; nothing needs to happen for this adapter.
     *
     * @param Request $request
     * @param Response $response
     */
    public function preAuth(Request $request, Response $response)
    {
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
        $content       = $request->getContent();
        $oauth2request = new OAuth2Request(
            $_GET,
            $_POST,
            array(),
            $_COOKIE,
            $_FILES,
            $_SERVER,
            $content,
            $request->getHeaders()->toArray()
        );

        if (! $this->oauth2Server->verifyResourceRequest($oauth2request)) {
            return false;
        }

        $token    = $this->oauth2Server->getAccessTokenData($oauth2request);
        $identity = new Identity\AuthenticatedIdentity($token);
        $identity->setName($token['user_id']);
        return $identity;
    }
}
