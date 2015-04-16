ZF MVC Auth
===========

[![Build Status](https://travis-ci.org/zfcampus/zf-mvc-auth.png)](https://travis-ci.org/zfcampus/zf-mvc-auth)

Introduction
------------

`zf-mvc-auth` is a ZF2 module that adds services, events, and configuration that extends the base
ZF2 MVC lifecycle to handle authentication and authorization.

For authentication, 3 primary methods are supported out of the box: HTTP Basic authentication,
HTTP Digest authentication, and OAuth2 (this requires Brent Shaffer's [OAuth2
Server](https://github.com/bshaffer/oauth2-server-php)).

For authorization, this particular module delivers a pre-dispatch time listener that will
identify if the given route match, along with the HTTP method, is authorized to be dispatched.

Requirements
------------
  
Please see the [composer.json](composer.json) file.

Installation
------------

Run the following `composer` command:

```console
$ composer require "zfcampus/zf-mvc-auth"
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

```javascript
"require": {
    "zfcampus/zf-mvc-auth": "~1.0-dev"
}
```

And then run `composer update` to ensure the module is installed.

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:


```php
return array(
    /* ... */
    'modules' => array(
        /* ... */
        'ZF\MvcAuth',
    ),
    /* ... */
);
```

Configuration
-------------

### User Configuration

The top-level configuration key for user configuration of this module is `zf-mvc-auth`.  Under this
key, there are two sub-keys, one for `authentication` and the other for `authorization`.

#### Key: `authentication`

The `authentication` key is used for any configuration that is related to the process of
authentication, or the process of validating an identity.

##### Sub-key: `http`

The `http` sub-key is utilized for configuring an HTTP-based authentication scheme.  These schemes
utilize ZF2's `Zend\Authentication\Adapter\Http` adapter, which implements both HTTP
Basic and HTTP Digest authentication.  To accomplish this, the HTTP adapter uses a file based
"resolver" in order to resolve the file containing credentials.  These implementation nuances can be
explored in the [Authentication portion of the ZF2 manual](http://framework.zend.com/manual/2.0/en/modules/zend.authentication.adapter.http.html).

The `http` sub-key has several fields:

- `accept_schemes`: *required*; an array of configured schemes; one or both of `basic` and `digest`.
- `realm`: *required*; this is typically a string that identifies the HTTP realm; e.g., "My Site".
- `digest_domains`: *required* for HTTP Digest; this is the relative URI for the protected area,
  typically `/`.
- `nonce_timeout`: *required* for HTTP Digest; the number of seconds in which to expire the digest
  nonce, typically `3600`.

Beyond those configuration options, one or both of the following resolver configurations is required:

- `htpasswd`: the path to a file created in the `htpasswd` file format
- `htdigest`: the path to a file created in the `htdigest` file format

An example might look like the following:

```php
'http' => array(
    'accept_schemes' => array('basic', 'digest'),
    'realm' => 'My Web Site',
    'digest_domains' => '/',
    'nonce_timeout' => 3600,
    'htpasswd' => APPLICATION_PATH . '/data/htpasswd', // htpasswd tool generated
    'htdigest' => APPLICATION_PATH . '/data/htdigest', // @see http://www.askapache.com/online-tools/htpasswd-generator/
),
```

##### Sub-key: `map`

- Since 1.1.0.

The `map` subkey is used to map an API module (optionally, with a version
namespace) to a given authentication type (typically, one of `basic`, `digest`, or
`oauth2`). This can be used to enfore different authentication methods for
different APIs, or even versions of the same API.

```php
return array(
    'zf-mvc-auth' => array(
        'authentication' => array(
            'map' => array(
                'Status\V1' => 'basic',  // v1 only!
                'Status\V2' => 'oauth2', // v2 only!
                'Ping'      => 'digest', // all versions!
            ),
        ),
    ),
);
```

In the absence of a `map` subkey, if andy authentication adapter configuration
is defined, that configuration will be used for any API.

**Note for users migrating from 1.0**: In the 1.0 series, authentication was
*per-application*, not per API. The migration to 1.1 should be seamless; if you
do not edit your authentication settings, or provide authentication information
to any APIs, your API will continue to act as it did. The first time you perform
one of these actions, the Admin API will create a map, mapping each version of
each service to the configured authentication scheme, and thus ensuring that
your API continues to work as previously configured, while giving you the
flexibility to define authentication per-API and per-version in the future.

##### Sub-key: `types`

- Since 1.1.0.

Starting in 1.1.0, the concept of authentication adapters was provided. Adapters
"provide" one or more authentication types; these are then used internally to
determine which adapter to use, as well as by the Admin API to allow mapping
APIs to specific authentication types.

In some instances you may be using listeners or other facilities for
authenticating an API. In order to allow mapping these (which is primarily a
documentation feature in such instances), the `types` subkey exists. This key is
an array of string authentication types:

```php
return array(
    'zf-mvc-auth' => array(
        'authentication' => array(
            'types' => array(
                'token',
                'key',
            ),
        ),
    ),
);
```

This key and its contents **must** be created manually.

##### Sub-key: `adapters`

- Since 1.1.0.

Starting in 1.1.0, with the introduction of adapters, you can also configure
named HTTP and OAuth2 adapters. The name provided will be used as the
authentication type for purposes of mapping APIs to an authentication adapter.

The format for the `adapters` key is a key/value pair, with the key acting as
the type, and the value as configuration for providing a
`ZF\MvcAuth\Authentication\HttpAdapter` or
`ZF\MvcAuth\Authentication\OAuth2Adapter` instance, as follows:

```php
return array(
    'zf-mvc-auth' => array(
        'authentication' => array(
            'adapters' => array(
                'api' => array(
                    // This defines an HTTP adapter that can satisfy both
                    // basic and digest.
                    'adapter' => 'ZF\MvcAuth\Authentication\HttpAdapter',
                    'options' => array(
                        'accept_schemes' => array('basic', 'digest'),
                        'realm' => 'api',
                        'digest_domains' => 'https://example.com',
                        'nonce_timeout' => 3600,
                        'htpasswd' => 'data/htpasswd',
                        'htdigest' => 'data/htdigest',
                    ),
                ),
                'user' => array(
                    // This defines an OAuth2 adapter backed by PDO.
                    'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => array(
                        'adapter' => 'pdo',
                        'dsn' => 'mysql:host=localhost;dbname=oauth2',
                        'username' => 'username',
                        'password' => 'password',
                        'options' => aray(
                            1002 => 'SET NAMES utf8', // PDO::MYSQL_ATTR_INIT_COMMAND
                        ),
                    ),
                ),
                'client' => array(
                    // This defines an OAuth2 adapter backed by Mongo.
                    'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => array(
                        'adapter' => 'mongo',
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
            ),
        ),
    ),
);
```

#### Key: `authorization`

#### Sub-Key: `deny_by_default`

`deny_by_default` toggles the default behavior for the `Zend\Permissions\Acl` implementation.  The
default value is `false`, which means that if no authenticated user is present, and no permissions
rule applies for the current resource, then access is allowed. Change this setting to `true` to
require authenticated identities by default.

Example:

```php
'deny_by_default' => false,
```

> ##### deny_by_default with zf-oauth2
>
> When using `deny_by_default => true` with > [zf-oauth2](https://github.com/zfcampus/zf-oauth2),
> you will need to explicitly allow POST on the OAuth2 controller in order for Authentication
> requests to be made.
> 
> As an example:
>
> ```php
> `authorization` => array(
>     'deny_by_default' => true,
>     'ZF\\OAuth2\\Controller\\Auth' => array(
>         'actions' => array(
>             'token' => array(
>                 'GET'    => false,
>                 'POST'   => true,   // <-----
>                 'PATCH'  => false,
>                 'PUT'    => false,
>                 'DELETE' => false,
>             ),
>         ),
>     ),
> ),
> ```

#### Sub-Key: Controller Service Name

Under the `authorization` key is an array of _controller service name_ keyed authorization
configuration settings.  The structure of these arrays depends on the type of the controller
service that you're attempting to grant or restrict access to.

For the typical ZF2 based action controller, this array is keyed with `actions`.  Under this
key, each action name for the given controller service is associated with a *permission array*.

For [zf-rest](https://github.com/zfcampus/zf-rest)-based controllers, a top level key of either
`collection` or `entity` is used.  Under each of these keys will be an associated *permission
array*.

A **permission array** consists of a keyed array of either `default` or an HTTP method.  The
values for each of these will be a boolean value where `true` means _an authenticated user
is required_ and where `false` means _an authenticated user is *not* required_.  If an action
or HTTP method is not idendified, the `default` value will be assumed.  If there is no default,
the behavior of the `deny_by_default` key (discussed above) will be assumed.

Below is an example:

```php
`authorization` => array(
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
),
```

### System Configuration

The following configuration is provided in `config/module.config.php` to enable the module to
function:

```php
'service_manager' => array(
    'aliases' => array(
        'authentication' => 'ZF\MvcAuth\Authentication',
        'authorization' => 'ZF\MvcAuth\Authorization\AuthorizationInterface',
        'ZF\MvcAuth\Authorization\AuthorizationInterface' => 'ZF\MvcAuth\Authorization\AclAuthorization',
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
    ),
    'invokables' => array(
        'ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener' => 'ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener',
        'ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener' => 'ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener',
    ),
),
```

These services will be described in the events and services section.

ZF2 Events
----------

### Events

#### ZF\MvcAuth\MvcAuthEvent::EVENT_AUTHENTICATION (a.k.a "authentication")

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `500` priority.  It is registered
via the `ZF\MvcAuth\MvcRouteListener` event listener aggregate.

#### ZF\MvcAuth\MvcAuthEvent::EVENT_AUTHENTICATION_POST (a.k.a "authentication.post")

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `499` priority.  It is
registered via the `ZF\MvcAuth\MvcRouteListener` event listener aggregate.

#### ZF\MvcAuth\MvcAuthEvent::EVENT_AUTHORIZATION (a.k.a "authorization")

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `-600` priority.  It is
registered via the `ZF\MvcAuth\MvcRouteListener` event listener aggregate.

#### ZF\MvcAuth\MvcAuthEvent::EVENT_AUTHORIZATION_POST (a.k.a "authorization.post")

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `-601` priority.  It is
registered via the `ZF\MvcAuth\MvcRouteListener` event listener aggregate.

#### ZF\MvcAuth\MvcAuthEvent object

The `MvcAuthEvent` object provides contextual information when any authentication
or authorization event is triggered.  It persists the following:

- identity: `setIdentity()` and `getIdentity()`
- authentication service: `setAuthentication()` and `getAuthentication()`
- authorization service: `setAuthorization()` and `getAuthorization()`
- authorization result: `setIsAuthorized` and `isAuthorized()`
- original MVC event: `getMvcEvent()`

### Listeners

#### ZF\MvcAuth\Authentication\DefaultAuthenticationListener

This listener is attached to the `MvcAuth::EVENT_AUTHENTICATION` event.  It is primarily
responsible for preforming any authentication and ensuring that an authenticated
identity is persisted in both the `MvcAuthEvent` and `MvcEvent` objects (the latter under the event
parameter `ZF\MvcAuth\Identity`).

#### ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener

This listener is attached to the `MvcAuth::EVENT_AUTHENTICATION_POST` event.  It is primarily
responsible for determining if an unsuccessful authentication was preformed, and in that case
it will attempt to set a `401 Unauthorized` status on the `MvcEvent`'s response object.

#### ZF\MvcAuth\Authorization\DefaultAuthorizationListener

This listener is attached to the `MvcAuth::EVENT_AUTHORIZATION` event.  It is primarily
responsible for executing the `isAuthorized()` method on the configured authorization service.

#### ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener

This listener is attached to the `MvcAuth::EVENT_AUTHORIZATION_POST` event.  It is primarily
responsible for determining if the current request is authorized.   In the case where the current
request is not authorized, it will attempt to set a `403 Forbidden` status on the `MvcEvent`'s
response object.

#### ZF\MvcAuth\Authorization\DefaultResourceResolverListener

This listener is attached to the `MvcAuth::EVENT_AUTHENTICATION_POST` with a priority of `-1`.
It is primarily responsible for creating and persisting a special name in the current event
for zf-rest-based controllers when used in conjunction with `zf-rest` module.

ZF2 Services
------------

#### Controller Plugins

This module exposes the controller plugin `getIdentity()`, mapping to
`ZF\MvcAuth\Identity\IdentityPlugin`. This plugin will return the identity discovered during
authentication as injected into the `Zend\Mvc\MvcEvent`'s `ZF\MvcAuth\Identity` parameter. If no
identity is present in the `MvcEvent`, or the identity present is not an instance of
`ZF\MvcAuth\Identity\IdentityInterface`, an instance of `ZF\MvcAuth\Identity\GuestIdentity` will be
returned.

#### Event Listener Services

The following services are provided and serve as event listeners:

- `ZF\MvcAuth\Authentication\DefaultAuthenticationListener`
- `ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener`
- `ZF\MvcAuth\Authorization\DefaultAuthorizationListener`
- `ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener`
- `ZF\MvcAuth\Authorization\DefaultResourceResolverListener`

#### ZF\MvcAuth\Authentication (a.k.a "authentication")

This is an instance of `Zend\Authentication\AuthenticationService`.

#### ZF\MvcAuth\Authentication\AuthHttpAdapter

This is an instance of `Zend\Authentication\Adapter\Http`.

#### ZF\MvcAuth\Authorization\AclAuthorization (a.k.a "authorization", "ZF\MvcAuth\Authorization\AuthorizationInterface")

This is an instance of `ZF\MvcAuth\Authorization\AclAuthorization`, which in turn is an extension
of `Zend\Permissions\Acl\Acl`.


#### ZF\MvcAuth\ApacheResolver

This is an instance of `Zend\Authentication\Adapter\Http\ApacheResolver`. 
You can override the ApacheResolver with your own resolver by providing a custom factory. 

#### ZF\MvcAuth\FileResolver

This is an instance of `Zend\Authentication\Adapter\Http\FileResolver`.
You can override the FileResolver with your own resolver by providing a custom factory.

### Authentication Adapters

- Since 1.1.0

Authentication adapters provide the most direct means for adding custom
authentication facilities to your APIs. Adapters implement
`ZF\MvcAuth\Authentication\AdapterInterface`:

```php
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
```

The `provides()` method should return an array of strings, each an
authentication "type" that this adapter provides; as an example, the provided
`ZF\MvcAuth\Authentication\HttpAdapter` can provide `basic` and/or `digest`.

The `matches($type)` should test the given `$type` against what the adapter
provides to determine if it can handle an authentication request. Typically,
this can be done with `return in_array($type, $this->provides(), true);`

The `getTypeFromRequest()` method can be used to match an incoming request to
the authentication type it resolves, if any. Examples might be deconstructing
the `Authorization` header, or a custom header such as `X-Api-Token`.

The `preAuth()` method can be used to provide client challenges; typically,
this will only ever be used by the included `HttpAdapter`.

Finally, the `authenticate()` method is used to attempt to authenticate an
incoming request. I should return either a boolean `false`, indicating
authentictaion failed, or an instance of
`ZF\MvcAuth\Identity\IdentityInterface`; if the latter is returned, that
identity will be used for the duration of the request.

Adapters are attached to the `DefaultAuthenticationListener`. To attach your
custom adapter, you will need to do one of the following:

- Define named HTTP and/or OAuth2 adapters via configuration.
- During an event listener, pull your adapter and the
  `DefaultAuthenticationListener` services, and attach your adapter to the
  latter.
- Create a `DelegatorFactory` for the `DefaultAuthenticationListener` that
  attaches your custom adapter before returning the listener.

#### Defining named HTTP and/or OAuth2 adapters

Since HTTP and OAuth2 support is built-in, `zf-mvc-auth` provides a
configuration-driven approach for creating named adapters of these types. Each
requires a unique key under the `zf-mvc-auth.authentication.adapters`
configuration, and each type has its own format.

```php
return array(
    /* ... */
    'zf-mvc-auth' => array(
        'authentication' => array(
            'adapters' => array(
                'api' => array(
                    // This defines an HTTP adapter that can satisfy both
                    // basic and digest.
                    'adapter' => 'ZF\MvcAuth\Authentication\HttpAdapter',
                    'options' => array(
                        'accept_schemes' => array('basic', 'digest'),
                        'realm' => 'api',
                        'digest_domains' => 'https://example.com',
                        'nonce_timeout' => 3600,
                        'htpasswd' => 'data/htpasswd',
                        'htdigest' => 'data/htdigest',
                    ),
                ),
                'user' => array(
                    // This defines an OAuth2 adapter backed by PDO.
                    'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => array(
                        'adapter' => 'pdo',
                        'dsn' => 'mysql:host=localhost;dbname=oauth2',
                        'username' => 'username',
                        'password' => 'password',
                        'options' => aray(
                            1002 => 'SET NAMES utf8', // PDO::MYSQL_ATTR_INIT_COMMAND
                        ),
                    ),
                ),
                'client' => array(
                    // This defines an OAuth2 adapter backed by Mongo.
                    'adapter' => 'ZF\MvcAuth\Authentication\OAuth2Adapter',
                    'storage' => array(
                        'adapter' => 'mongo',
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
            ),
            /* ... */
        ),
        /* ... */
    ),
    /* ... */
);
```

The above configuration would provide the authentication types
`array('api-basic', 'api-digest', 'user', 'client')` to your application, which
can each them be associated in the authentication type map.

If you use `zf-apigility-admin`'s Admin API and/or the Apigility UI to
configure authentication adapters, the above configuration will be created for
you.

#### Attaching an adapter during an event listener

The best event to attach to in this circumstances is the "authentication" event.
When doing so, you'll want to attach at a priority > 1 to ensure it executes
before the `DefaultAuthenticationListener`.

In the following example, we'll assume you've defined a service named
`MyCustomAuthenticationAdapter` that returns an `AdapterInterface`
implementation, and that the class is the `Module` class of your API or a module
in your application.

```php
class Module
{
    public function onBootstrap($e)
    {
        $app      = $e->getApplication();
        $events   = $app->getEventManager();
        $services = $app->getServiceManager();

        $events->attach(
            'authentication',
            function ($e) use ($services) {
                $listener = $services->get('ZF\MvcAuth\Authentication\DefaultAuthenticationListener')
                $adapter = $services->get('MyCustomAuthenticationAdapter');
                $listener->attach($adapter);
            },
            1000
        );
    }
}
```

By returning nothing, the `DefaultAuthenticationListener` will continue to
execute, but will now also have the new adapter attached.

#### Using a delegator factory

Delegator Factories are a way to "decorate" an instance returned by the Zend
Framework `ServiceManager` in order to provide pre-conditions or alter the
instance normally returned. In our case, we want to attach an adapter after the
instance is created, but before it's returned.

In the following example, we'll assume you've defined a service named
`MyCustomAuthenticationAdapter` that returns an `AdapterInterface`
implementation. The following is a delegator factory for the `DefaultAuthenticationListener` that will inject our adapter.

```php
use Zend\ServiceManager\DelegatorFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class CustomAuthenticationDelegatorFactory implements DelegatorFactoryInterface
{
    public function createDelegatorWithName(
        ServiceLocatorInterface $services,
        $name,
        $requestedName,
        $callback
    ) {
        $listener  = $callback();
        $listener->attach($services->get('MyCustomAuthenticationAdapter');
        return $listener;
    }
}
```

We then need to tell the `ServiceManager` about the delegator factory; we do this in our module's `config/module.config.php`, or one of the `config/autoload/` configuration files:

```php
return array(
    /* ... */
    'service_manager' => array(
        /* ... */
        'delegators' => array(
            'ZF\MvcAuth\Authentication\DefaultAuthenticationListener' => array(
                'CustomAuthenticationDelegatorFactory',
            ),
        ),
    ),
    /* ... */
);
```

Once configured, our adapter will be attached to every instance of the `DefaultAuthenticationListener` that is retrieved.
