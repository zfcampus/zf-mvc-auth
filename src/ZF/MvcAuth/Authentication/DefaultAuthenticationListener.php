<?php

namespace ZF\MvcAuth\Authentication;

use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Http\Request as HttpRequest;
use ZF\MvcAuth\MvcAuthEvent;
use ZF\MvcAuth\Identity;
use Zend\Mvc\MvcEvent;

class DefaultAuthenticationListener
{
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $request  = $mvcEvent->getRequest();
        $response = $mvcEvent->getResponse();

        if (!$request instanceof HttpRequest) {
            return;
        }
        
        $configuration = $mvcEvent->getApplication()->getServiceManager()->get('Configuration');

        if (($authHeader = $request->getHeader('Authorization')) === false) {
            if (isset($configuration['zf-mvc-auth']['authentication']['digest'])) {
                $em = $mvcEvent->getApplication()->getEventManager();
                $em->attach(MvcEvent::EVENT_ROUTE, new DigestHandshakeListener($mvcEvent), -100);
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
                if (!isset($configuration['zf-mvc-auth']['authentication']['basic'])) {
                    // @todo this probably needs to be a 401 or 500 somehow, not sure
                    throw new \Exception('Not a valid authentication scheme.');
                }
                $basicAdapter = new HttpAuth($configuration['zf-mvc-auth']['authentication']['basic']);
                $basicResolver = new HttpAuth\ApacheResolver();
                if (!isset($configuration['zf-mvc-auth']['authentication']['basic']['file'])
                    || $configuration['zf-mvc-auth']['authentication']['basic']['file'] == '') {
                    // @todo this probably needs to be a 401 or 500 somehow, not sure
                    throw new \Exception('Bad configuration, file not provided.');
                }
                $basicResolver->setFile($configuration['zf-mvc-auth']['authentication']['basic']['file']);
                $basicAdapter->setBasicResolver($basicResolver);
                $basicAdapter->setRequest($request);
                $basicAdapter->setResponse($response);

        
                $auth = $mvcAuthEvent->getAuthenticationService();
                $result = $auth->authenticate($basicAdapter);
                break;

            case 'digest':
                if (!isset($configuration['zf-mvc-auth']['authentication']['digest'])) {
                    // @todo this probably needs to be a 401 or 500 somehow, not sure
                    throw new \Exception('Not a valid authentication scheme.');
                }
                $basicAdapter = new HttpAuth($configuration['zf-mvc-auth']['authentication']['digest']);
                $digestResolver = new HttpAuth\ApacheResolver();
                if (!isset($configuration['zf-mvc-auth']['authentication']['digest']['file'])
                    || $configuration['zf-mvc-auth']['authentication']['digest']['file'] == '') {
                    // @todo this probably needs to be a 401 or 500 somehow, not sure
                    throw new \Exception('Bad configuration, file not provided.');
                }
                $digestResolver->setFile($configuration['zf-mvc-auth']['authentication']['digest']['file']);
                $basicAdapter->setDigestResolver($digestResolver);
                $basicAdapter->setRequest($request);
                $basicAdapter->setResponse($response);

                $auth = $mvcAuthEvent->getAuthenticationService();
                $result = $auth->authenticate($basicAdapter);
                break;

            case 'token':
                throw new \Exception('@todo');
        }

        if (isset($result)) {
            if ($result->isValid()) {
                $identity = new Identity\AuthenticatedIdentity($result->getIdentity());
                $identity->setName($result->getIdentity());
                $mvcAuthEvent->setIdentity($identity);
            }

            $mvcAuthEvent->setAuthenticationResult($result);
            return;
        }
    }
}
