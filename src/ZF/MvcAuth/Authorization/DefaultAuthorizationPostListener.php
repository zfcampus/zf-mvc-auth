<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use ZF\MvcAuth\MvcAuthEvent;

class DefaultAuthorizationPostListener
{
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        // if there is no identity, return with 401 unauthorized
        if ($mvcAuthEvent->getIdentity() == null) {
            /** @var \Zend\Http\Response $response */
            $response = $mvcAuthEvent->getMvcEvent()->getResponse();
            $response->setStatusCode(401);
            $response->setReasonPhrase('Unauthorized');
            return $response;
        }
    }
}
