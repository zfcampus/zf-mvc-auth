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
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $request = $mvcEvent->getRequest();
        $response = $mvcEvent->getResponse();
        $configuration = $mvcEvent->getApplication()->getServiceManager()->get('Configuration');

        if (!$request instanceof HttpRequest) {
            return;
        }

        if (!isset($configuration['zf-mvc-auth']['authentication'])) {
            return;
        }
        $authConfig = $configuration['zf-mvc-auth']['authentication'];
        if ($authConfig instanceof Config) {
            $authConfig = $authConfig->toArray();
        }

        // if we have http or digest configured, create adapter as they might need to send challenge
        if (isset($authConfig['http'])) {

            $httpConfig = $authConfig['http'];

            if (!isset($httpConfig['accept_schemes']) || !is_array($httpConfig['accept_schemes'])) {
                throw new \Exception('accept_schemes is required');
            }

            $httpAdapter = new HttpAuth(array_merge($httpConfig, array('accept_schemes' => implode(' ', $httpConfig['accept_schemes']))));
            $httpAdapter->setRequest($request);
            $httpAdapter->setResponse($response);

            $hasFileResolver = false;

            // basic && htpasswd
            if (in_array('basic', $httpConfig['accept_schemes']) && isset($httpConfig['htpasswd'])) {
                $httpAdapter->setBasicResolver(new HttpAuth\ApacheResolver($httpConfig['htpasswd']));
                $hasFileResolver = true;
            }
            if (in_array('digest', $httpConfig['accept_schemes']) && isset($httpConfig['htdigest'])) {
                $httpAdapter->setDigestResolver(new HttpAuth\FileResolver($httpConfig['htdigest']));
                $hasFileResolver = true;
            }

            if ($hasFileResolver === false) {
                unset($httpAdapter);
            }

        }

        if (($authHeader = $request->getHeader('Authorization')) === false) {
            if (isset($httpAdapter)) {
                $httpAdapter->challengeClient();
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

                if (!isset($httpAdapter)) {
                    return;
                    // throw new \Exception('an http adapter is not configured');
                }

                $auth = $mvcAuthEvent->getAuthenticationService();
                $result = $auth->authenticate($httpAdapter);

                if ($result->isValid()) {
                    $identity = new Identity\AuthenticatedIdentity($result->getIdentity());
                    $identity->setName($result->getIdentity());
                    $mvcAuthEvent->setIdentity($identity);
                }

                $mvcAuthEvent->setAuthenticationResult($result);
                return;

            case 'token':
                throw new \Exception('@todo');
        }



    }
}
