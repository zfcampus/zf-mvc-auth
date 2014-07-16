<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authorization;

abstract class AclAuthorizationFactory
{
    public static function factory(array $config)
    {
        // Determine whether we are whitelisting or blacklisting
        $denyByDefault = false;
        if (array_key_exists('deny_by_default', $config)) {
            $denyByDefault = (bool) $config['deny_by_default'];
            unset($config['deny_by_default']);
        }

        // By default, create an open ACL
        $acl = new AclAuthorization;
        $acl->addRole('guest');
        $acl->allow();

        $grant = 'deny';
        if ($denyByDefault) {
            $acl->deny('guest', null, null);
            $grant = 'allow';
        }

        foreach ($config as $set) {
            if (! is_array($set) || ! isset($set['resource'])) {
                continue;
            }

            // Add new resource to ACL
            $resource = $set['resource'];
            $acl->addResource($set['resource']);

            // Deny guest specified privileges to resource
            $privileges = isset($set['privileges']) ? $set['privileges'] : null;

            // "null" privileges means no permissions were setup; nothing to do
            if (null === $privileges) {
                continue;
            }

            $acl->$grant('guest', $resource, $privileges);
        }

        return $acl;
    }
}
