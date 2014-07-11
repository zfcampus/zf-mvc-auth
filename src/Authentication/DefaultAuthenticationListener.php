<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Http\Request as HttpRequest;
use ZF\MvcAuth\Identity;
use ZF\MvcAuth\MvcAuthEvent;
use OAuth2\Server as OAuth2Server;
use OAuth2\Request as OAuth2Request;

class DefaultAuthenticationListener
{
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

        $type = false;

        if ($this->httpAdapter instanceof HttpAuth) {
            $this->httpAdapter->setRequest($request);
            $this->httpAdapter->setResponse($response);
        }

        $authHeader = $request->getHeader('Authorization');
        if ($authHeader) {
            $headerContent = trim($authHeader->getFieldValue());

            // we only support headers in the format: Authorization: xxx yyyyy
            if (strpos($headerContent, ' ') === false) {
                $identity = new Identity\GuestIdentity();
                $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
                return $identity;
            }

            list($type, $credential) = preg_split('# #', $headerContent, 2);
        }

        if (! $type
            && ! in_array($request->getMethod(), $this->requestsWithoutBodies)
            && $request->getHeaders()->has('Content-Type')
            && $request->getHeaders()->get('Content-Type')->match('application/x-www-form-urlencoded')
            && $request->getPost('access_token')
        ) {
            $type = 'oauth2';
        }

        if (! $type && null !== $request->getQuery('access_token')) {
            $type = 'oauth2';
        }

        if (! $type) {
            if ($this->httpAdapter instanceof HttpAuth) {
                $this->httpAdapter->challengeClient();
            }
            $identity = new Identity\GuestIdentity();
            $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
            return $identity;
        }

        switch (strtolower($type)) {
            case 'basic':
            case 'digest':

                if (!$this->httpAdapter instanceof HttpAuth) {
                    $identity = new Identity\GuestIdentity();
                    $mvcEvent->setParam('ZF\MvcAuth\Identity', $identity);
                    return $identity;
                }

                $auth   = $mvcAuthEvent->getAuthenticationService();
                $result = $auth->authenticate($this->httpAdapter);
                $mvcAuthEvent->setAuthenticationResult($result);

                if ($result->isValid()) {
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
                }

                $identity = new Identity\GuestIdentity();
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
                throw new \Exception('zf-mvc-auth has not yet implemented a "token" authentication adapter');
        }
    }
}
