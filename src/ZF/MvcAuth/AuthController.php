<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth;

use Zend\Mvc\Controller\AbstractActionController;

class AuthController extends AbstractActionController
{
    public function authenticationFailureAction()
    {
        /** @var \Zend\Http\Response $response */
        $response = $this->getResponse();
        $response->setStatusCode(401);
        $response->setReasonPhrase('Unauthorized.');
        return $response;
    }

    public function authorizationDeniedAction()
    {
        /** @var \Zend\Http\Response $response */
        $response = $this->getResponse();
        $response->setStatusCode(403);
        $response->setReasonPhrase('Authorization denied.');
        return $response;
    }

}