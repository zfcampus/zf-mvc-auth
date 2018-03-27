<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Identity;

use Zend\Permissions\Rbac\Role;

class GuestIdentity extends Role implements IdentityInterface
{
    protected static $identity = 'guest';

    public function __construct()
    {
        parent::__construct(static::$identity);
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
