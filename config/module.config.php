<?php // @codingStandardsIgnoreFile
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

return array(
    'controller_plugins' => array(
        'invokables' => array(
            'getidentity' => 'ZF\MvcAuth\Identity\IdentityPlugin',
        ),
    ),
    'service_manager' => array(
        'aliases' => array(
            'authentication' => 'ZF\MvcAuth\Authentication',
            'authorization' => 'ZF\MvcAuth\Authorization\AuthorizationInterface',
            'ZF\MvcAuth\Authorization\AuthorizationInterface' => 'ZF\MvcAuth\Authorization\AclAuthorization',
        ),
        'delegators' => array(
            'ZF\MvcAuth\Authentication\DefaultAuthenticationListener' => array(
                'ZF\MvcAuth\Factory\AuthenticationAdapterDelegatorFactory',
            ),
        ),
        'factories' => array(
            'ZF\MvcAuth\Authentication' => 'ZF\MvcAuth\Factory\AuthenticationServiceFactory',
            'ZF\MvcAuth\ApacheResolver' => 'ZF\MvcAuth\Factory\ApacheResolverFactory',
            'ZF\MvcAuth\FileResolver' => 'ZF\MvcAuth\Factory\FileResolverFactory',
            'ZF\MvcAuth\Authentication\DefaultAuthenticationListener' => 'ZF\MvcAuth\Factory\DefaultAuthenticationListenerFactory',
            'ZF\MvcAuth\Authentication\AuthHttpAdapter' => 'ZF\MvcAuth\Factory\DefaultAuthHttpAdapterFactory',
            'ZF\MvcAuth\Authorization\AclAuthorization' => 'ZF\MvcAuth\Factory\AclAuthorizationFactory',
            'ZF\MvcAuth\Authorization\DefaultAuthorizationListener' => 'ZF\MvcAuth\Factory\DefaultAuthorizationListenerFactory',
            'ZF\MvcAuth\Authorization\DefaultResourceResolverListener' => 'ZF\MvcAuth\Factory\DefaultResourceResolverListenerFactory',
            'ZF\OAuth2\Service\OAuth2Server' => 'ZF\MvcAuth\Factory\NamedOAuth2ServerFactory',
        ),
        'invokables' => array(
            'ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener' => 'ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener',
            'ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener' => 'ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener',
        ),
    ),
    'zf-mvc-auth' => array(
        'authentication' => array(
            /* First, we define authentication configuration types. These have
             * the keys:
             * - http
             * - oauth2
             *
             * Note: as of 1.1, these are deprecated.
             *
            'http' => array(
                'accept_schemes' => array('basic', 'digest'),
                'realm' => 'My Web Site',
                'digest_domains' => '/',
                'nonce_timeout' => 3600,
                'htpasswd' => APPLICATION_PATH . '/data/htpasswd' // htpasswd tool generated
                'htdigest' => APPLICATION_PATH . '/data/htdigest' // @see http://www.askapache.com/online-tools/htpasswd-generator/
                'basic_resolver_factory' => 'ServiceManagerKeyToAsk', // if this is set, the htpasswd key is ignored - see below
                'digest_resolver_factory' => 'ServiceManagerKeyToAsk', // if this is set, the htdigest key is ignored - see below
            ),
             *
             * Starting in 1.1, we have an "adapters" key, which is a key/value
             * pair of adapter name -> adapter configuration information. Each
             * adapter should name the ZF\MvcAuth\Authentication\AdapterInterface
             * type in the 'adapter' key.
             *
             * For HttpAdapter cases, specify an 'options' key with the options
             * to use to create the Zend\Authentication\Adapter\Http instance.
             *
             * Starting in 1.2, you can specify a resolver implementing the
             * Zend\Authentication\Adapter\Http\ResolverInterface that is passed
             * into the Zend\Authentication\Adapter\Http as either basic or digest
             * resolver. This allows you to implement your own method of authentication
             * instead of having to rely on the two default methods (ApacheResolver
             * for basic authentication and FileResolver for digest authentication,
             * both based on files).
             *
             * When you want to use this feature, use the "basic_resolver_factory"
             * key to get your custom resolver instance from the Zend service manager.
             * If this key is set and pointing to a valid entry in the service manager,
             * the entry "htpasswd" is ignored (unless you use it in your custom
             * factory to build the resolver).
             *
             * Using the "digest_resolver_factory" ignores the "htdigest" key in
             * the same way.
             *
             * For OAuth2Adapter instances, specify a 'storage' key, with options
             * to use for matching the adapter and creating an OAuth2 storage
             * instance. The array MUST contain a `route' key, with the route
             * at which the specific adapter will match authentication requests.
             * To specify the storage instance, you may use one of two approaches:
             *
             * - Specify a "storage" subkey pointing to a named service or an array
             *   of named services to use.
             * - Specify an "adapter" subkey with the value "pdo" or "mongo", and
             *   include additional subkeys for configuring a ZF\OAuth2\Adapter\PdoAdapter
             *   or ZF\OAuth2\Adapter\MongoAdapter, accordingly. See the zf-oauth2
             *   documentation for details.
             *
             * This looks like the following for the HTTP basic/digest and OAuth2
             * adapters:
            'adapters' => array
                // HTTP adapter
                'api' => array(
                    'adapter' => 'ZF\MvcAuth\Authentication\HttpAdapter',
                    'options' => array(
                        'accept_schemes' => array('basic', 'digest'),
                        'realm' => 'api',
                        'digest_domains' => 'https://example.com',
                        'nonce_timeout' => 3600,
                        'htpasswd' => 'data/htpasswd',
                        'htdigest' => 'data/htdigest',
                        'basic_resolver_factory' => 'ServiceManagerKeyToAsk', // if this is set, the htpasswd key is ignored
                        'digest_resolver_factory' => 'ServiceManagerKeyToAsk', // if this is set, the htdigest key is ignored
                    ),
                ),
                // OAuth2 adapter, using an "adapter" type of "pdo"
                'user' => array(
                    'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => array(
                        'adapter' => 'pdo',
                        'route' => '/user',
                        'dsn' => 'mysql:host=localhost;dbname=oauth2',
                        'username' => 'username',
                        'password' => 'password',
                        'options' => aray(
                            1002 => 'SET NAMES utf8', // PDO::MYSQL_ATTR_INIT_COMMAND
                        ),
                    ),
                ),
                // OAuth2 adapter, using an "adapter" type of "mongo"
                'client' => array(
                    'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => array(
                        'adapter' => 'mongo',
                        'route' => '/client',
                        'locator_name' => 'SomeServiceName', // If provided, pulls the given service
                        'dsn' => 'mongodb://localhost',
                        'database' => 'oauth2',
                        'options' => array(
                            'username' => 'username',
                            'password' => 'password',
                            'connectTimeoutMS' => 500,
                        ),
                    ),
                ),
                // OAuth2 adapter, using a named "storage" service
                'named-storage' => array(
                    'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => array(
                        'storage' => 'Name\Of\An\OAuth2\Storage\Service',
                        'route' => '/named-storage',
                    ),
                ),
            ),
             *
             * Next, we also have a "map", which maps an API module (with
             * optional version) to a given authentication type (one of basic,
             * digest, or oauth2):
            'map' => array(
                'ApiModuleName' => 'oauth2',
                'OtherApi\V2' => 'basic',
                'AnotherApi\V1' => 'digest',
            ),
             *
             * We also allow you to specify custom authentication types that you
             * support via listeners; by adding them to the configuration, you
             * ensure that they will be available for mapping modules to
             * authentication types in the Admin.
            'types' => array(
                'token',
                'key',
                'etc',
            )
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
