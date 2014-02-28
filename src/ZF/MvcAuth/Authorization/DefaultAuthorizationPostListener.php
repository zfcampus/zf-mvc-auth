<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authorization;

use Zend\Http\Response as HttpResponse;
use ZF\MvcAuth\MvcAuthEvent;

class DefaultAuthorizationPostListener
{
    /**
     * Determine if we have an authorization failure, and, if so, return a 403 response
     *
     * @param MvcAuthEvent $mvcAuthEvent
     * @return null|\Zend\Http\Response
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $response = $mvcEvent->getResponse();

        if ($mvcAuthEvent->isAuthorized()) {
            if ($response instanceof HttpResponse) {
                if ($response->getStatusCode() != 200) {
                    $response->setStatusCode(200);
                }
            }
            return;
        }

        if (!$response instanceof HttpResponse) {
            return $response;
        }

        $response->setStatusCode(403);
        $response->setReasonPhrase('Forbidden');
        return $response;
    }
}
