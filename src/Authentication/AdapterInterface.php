<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use Zend\Http\Request;
use Zend\Http\Response;
use ZF\MvcAuth\Identity\IdentityInterface;
use ZF\MvcAuth\MvcAuthEvent;

interface AdapterInterface
{
    /**
     * @return array Array of types this adapter can handle.
     */
    public function provides();

    /**
     * Attempt to match a requested authentication type
     * against what the adapter provides.
     *
     * @param string $type
     * @return bool
     */
    public function matches($type);

    /**
     * Attempt to retrieve the authentication type based on the request.
     *
     * Allows an adapter to have custom logic for detecting if a request
     * might be providing credentials it's interested in.
     *
     * @param Request $request
     * @return false|string
     */
    public function getTypeFromRequest(Request $request);

    /**
     * Perform pre-flight authentication operations.
     *
     * Use case would be for providing authentication challenge headers.
     *
     * @param Request $request
     * @param Response $response
     * @return void|Response
     */
    public function preAuth(Request $request, Response $response);

    /**
     * Attempt to authenticate the current request.
     *
     * @param Request $request
     * @param Response $response
     * @param MvcAuthEvent $mvcAuthEvent
     * @return false|IdentityInterface False on failure, IdentityInterface
     *     otherwise
     */
    public function authenticate(Request $request, Response $response, MvcAuthEvent $mvcAuthEvent);
}
