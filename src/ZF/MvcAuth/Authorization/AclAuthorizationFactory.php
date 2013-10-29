<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authorization;

abstract class AclAuthorizationFactory
{
    public static function factory(array $config)
    {
        // By default, create an open ACL
        $acl = new AclAuthorization;
        $acl->addRole('guest');
        $acl->allow();

        foreach ($config as $set) {
            if (!isset($set['resource'])) {
                continue;
            }

            // Add new resource to ACL
            $resource = $set['resource'];
            $acl->addResource($set['resource']);

            // Deny guest specified privileges to resource
            $rights   = isset($set['rights']) ? $set['rights'] : null;
            $acl->deny('guest', $resource, $rights);
        }

        return $acl;
    }
}
