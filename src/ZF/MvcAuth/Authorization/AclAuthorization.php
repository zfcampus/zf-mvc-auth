<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authorization;

use Zend\Permissions\Acl\Acl;
use ZF\MvcAuth\Identity\IdentityInterface;

/**
 * Authorization implementation that uses the ACL component
 */
class AclAuthorization extends Acl implements AuthorizationInterface
{
    /**
     * Is the provided identity authorized for the given privilege on the given resource?
     *
     * If the resource does not exist, adds it, the proxies to isAllowed().
     *
     * @param IdentityInterface $identity
     * @param mixed $resource
     * @param mixed $privilege
     * @return bool
     */
    public function isAuthorized(IdentityInterface $identity, $resource, $privilege)
    {
        if (null !== $resource && (! $this->hasResource($resource))) {
            $this->addResource($resource);
        }

        if (!$this->hasRole($identity)) {
            $this->addRole($identity);
        }

        return $this->isAllowed($identity, $resource, $privilege);
    }
}
