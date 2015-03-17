<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use Zend\Http\Request;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Authorization token types this adapter can fulfill.
     * 
     * @var array
     */
    protected $authorizationTokenTypes = array();

    /**
     * Determine if the incoming request provides either basic or digest
     * credentials
     *
     * @param Request $request
     * @return false|string
     */
    public function getTypeFromRequest(Request $request)
    {
        $headers = $request->getHeaders();
        $authorization = $request->getHeader('Authorization');
        if (! $authorization) {
            return false;
        }

        $authorization = trim($authorization->getFieldValue());
        $type = $this->getTypeFromAuthorizationHeader($authorization);

        if (! in_array($type, $this->authorizationTokenTypes)) {
            return false;
        }

        return $type;
    }

    /**
     * Determine the authentication type from the authorization header contents
     *
     * @param string $header
     * @return false|string
     */
    private function getTypeFromAuthorizationHeader($header)
    {
        // we only support headers in the format: Authorization: xxx yyyyy
        if (strpos($header, ' ') === false) {
            return false;
        }

        list($type, $credential) = preg_split('# #', $header, 2);

        return strtolower($type);
    }
}
