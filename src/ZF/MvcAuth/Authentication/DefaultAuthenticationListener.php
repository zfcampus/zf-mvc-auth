<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Config\Config;
use Zend\Http\Request as HttpRequest;
use ZF\MvcAuth\Identity;
use ZF\MvcAuth\MvcAuthEvent;

class DefaultAuthenticationListener
{
    /**
     * @var HttpAuth
     */
    protected $httpAdapter;

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

        if (!$request instanceof HttpRequest) {
            return;
        }

        $authHeader = $request->getHeader('Authorization');
        if ($this->httpAdapter instanceof HttpAuth) {
            $this->httpAdapter->setRequest($request);
            $this->httpAdapter->setResponse($response);
        }

        if ($authHeader === false) {
            if ($this->httpAdapter instanceof HttpAuth) {
                $this->httpAdapter->challengeClient();
            }
            return;
        }

        $headerContent = trim($authHeader->getFieldValue());

        // we only support headers in the format: Authorization: xxx yyyyy
        if (strpos($headerContent, ' ') === false) {
            return;
        }

        list($type, $credential) = preg_split('# #', $headerContent, 2);

        switch (strtolower($type)) {
            case 'basic':
            case 'digest':

                if (!$this->httpAdapter instanceof HttpAuth) {
                    return;
                }

                $auth   = $mvcAuthEvent->getAuthenticationService();
                $result = $auth->authenticate($this->httpAdapter);
                $mvcAuthEvent->setAuthenticationResult($result);

                if ($result->isValid()) {
                    $identity = new Identity\AuthenticatedIdentity($result->getIdentity());
                    $identity->setName($result->getIdentity());
                    return $identity;
                }

                $identity = new Identity\GuestIdentity();
                return $identity;

            case 'token':
                throw new \Exception('zf-mvc-auth has not yet implemented a "token" authentication adapter');
        }
    }
}
