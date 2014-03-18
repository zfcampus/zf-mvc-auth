<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Identity;

use Zend\Permissions\Rbac\AbstractRole as AbstractRbacRole;

class GuestIdentity extends AbstractRbacRole implements IdentityInterface
{
    protected static $identity = 'guest';

    public function __construct()
    {
        $this->name = static::$identity;
    }

    public function getRoleId()
    {
        return static::$identity;
    }

    public function getAuthenticationIdentity()
    {
        return null;
    }
}
