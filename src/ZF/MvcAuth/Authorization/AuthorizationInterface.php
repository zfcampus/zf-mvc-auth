<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authorization;

use ZF\MvcAuth\Identity\IdentityInterface;

interface AuthorizationInterface
{
    /**
     * Whether or not the given identity has the given privilege on the given resource.
     *
     * @param IdentityInterface $identity
     * @param mixed $resource
     * @param mixed $privilege
     * @return bool
     */
    public function isAuthorized(IdentityInterface $identity, $resource, $privilege);
}
