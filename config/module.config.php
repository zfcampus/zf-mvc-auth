<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'service_manager' => array(
        'aliases' => array(
            'authentication' => 'ZF\MvcAuth\Authentication',
            'authorization' => 'ZF\MvcAuth\Authorization\AuthorizationInterface',
            'ZF\MvcAuth\Authorization\AuthorizationInterface' => 'ZF\MvcAuth\Authorization\AclAuthorization',
        ),
        'factories' => array(
            'ZF\MvcAuth\Authentication' => 'ZF\MvcAuth\Factory\AuthenticationServiceFactory',
            'ZF\MvcAuth\Authentication\DefaultAuthenticationListener' => 'ZF\MvcAuth\Factory\DefaultAuthenticationListenerFactory',
            'ZF\MvcAuth\Authentication\AuthHttpAdapter' => 'ZF\MvcAuth\Factory\DefaultAuthHttpAdapterFactory',
            'ZF\MvcAuth\Authorization\AclAuthorization' => 'ZF\MvcAuth\Factory\AclAuthorizationFactory',
            'ZF\MvcAuth\Authorization\DefaultAuthorizationListener' => 'ZF\MvcAuth\Factory\DefaultAuthorizationListenerFactory',
            'ZF\MvcAuth\Authorization\DefaultResourceResolverListener' => 'ZF\MvcAuth\Factory\DefaultResourceResolverListenerFactory',
        ),
        'invokables' => array(
            'ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener' => 'ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener',
            'ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener' => 'ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener',
        ),
    ),
    'zf-mvc-auth' => array(
        'authentication' => array(
            /**
             *
            'http' => array(
                'accept_schemes' => array('basic', 'digest'),
                'realm' => 'My Web Site',
                'digest_domains' => '/',
                'nonce_timeout' => 3600,
                'htpasswd' => APPLICATION_PATH . '/data/htpasswd' // htpasswd tool generated
                'htdigest' => APPLICATION_PATH . '/data/htdigest' // @see http://www.askapache.com/online-tools/htpasswd-generator/
            ),
             */
        ),
        'authorization' => array(
            // Toggle the following to true to change the ACL creation to
            // require an authenticated user by default, and thus selectively
            // allow unauthenticated users based on the rules.
            'deny_by_default' => false,

            /*
             * Rules indicating what controllers are behind authentication.
             *
             * Keys are controller service names.
             *
             * Values are arrays with either the key "actions" and/or one or
             * more of the keys "collection" and "entity".
             *
             * The "actions" key will be a set of action name/method pairs.
             * The "collection" and "entity" keys will have method values.
             *
             * Method values are arrays of HTTP method/boolean pairs. By
             * default, if an HTTP method is not present in the list, it is
             * assumed to be open (i.e., not require authentication). The
             * special key "default" can be used to set the default flag for
             * all HTTP methods.
             *
            'Controller\Service\Name' => array(
                'actions' => array(
                    'action' => array(
                        'default' => boolean,
                        'GET' => boolean,
                        'POST' => boolean,
                        // etc.
                    ),
                ),
                'collection' => array(
                    'default' => boolean,
                    'GET' => boolean,
                    'POST' => boolean,
                    // etc.
                ),
                'entity' => array(
                    'default' => boolean,
                    'GET' => boolean,
                    'POST' => boolean,
                    // etc.
                ),
            ),
             */
        ),
    ),
);
