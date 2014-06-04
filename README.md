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
$ composer require "zfcampus/zf-mvc-auth:~1.0-dev"
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

#### `ZF\MvcAuth\MvcAuthEvent::EVENT_AUTHENTICATION` (a.k.a `authentication`)

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `500` priority.  It is registered
via the `ZF\MvcAuth\MvcRouteListener` event listener aggregate.

#### `ZF\MvcAuth\MvcAuthEvent::EVENT_AUTHENTICATION_POST` (a.k.a `authentication.post`)

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `499` priority.  It is
registered via the `ZF\MvcAuth\MvcRouteListener` event listener aggregate.

#### `ZF\MvcAuth\MvcAuthEvent::EVENT_AUTHORIZATION` (a.k.a `authorization`)

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `-600` priority.  It is
registered via the `ZF\MvcAuth\MvcRouteListener` event listener aggregate.

#### `ZF\MvcAuth\MvcAuthEvent::EVENT_AUTHORIZATION_POST` (a.k.a `authorization.post`)

This event is triggered in relation to `MvcEvent::EVENT_ROUTE` at `-601` priority.  It is
registered via the `ZF\MvcAuth\MvcRouteListener` event listener aggregate.

#### `ZF\MvcAuth\MvcAuthEvent` object

The `MvcAuthEvent` object provides contextual information when any authentication
or authorization event is triggered.  It persists the following:

- identity: `setIdentity()` and `getIdentity()`
- authentication service: `setAuthentication()` and `getAuthentication()`
- authorization service: `setAuthorization()` and `getAuthorization()`
- authorization result: `setIsAuthorized` and `isAuthorized()`
- original MVC event: `getMvcEvent()`

### Listeners

#### `ZF\MvcAuth\Authentication\DefaultAuthenticationListener`

This listener is attached to the `MvcAuth::EVENT_AUTHENTICATION` event.  It is primarily
responsible for preforming any authentication and ensuring that an authenticated
identity is persisted in both the `MvcAuthEvent` and `MvcEvent` objects (the latter under the event
parameter `ZF\MvcAuth\Identity`).

#### `ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener`

This listener is attached to the `MvcAuth::EVENT_AUTHENTICATION_POST` event.  It is primarily
responsible for determining if an unsuccessful authentication was preformed, and in that case
it will attempt to set a `401 Unauthorized` status on the `MvcEvent`'s response object.

#### `ZF\MvcAuth\Authorization\DefaultAuthorizationListener`

This listener is attached to the `MvcAuth::EVENT_AUTHORIZATION` event.  It is primarily
responsible for executing the `isAuthorized()` method on the configured authorization service.

#### `ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener`

This listener is attached to the `MvcAuth::EVENT_AUTHORIZATION_POST` event.  It is primarily
responsible for determining if the current request is authorized.   In the case where the current
request is not authorized, it will attempt to set a `403 Forbidden` status on the `MvcEvent`'s
response object.

#### `ZF\MvcAuth\Authorization\DefaultResourceResolverListener`

This listener is attached to the `MvcAuth::EVENT_AUTHENTICATION_POST` with a priority of `-1`.
It is primarily responsible for creating and persisting a special name in the current event
for zf-rest-based controllers when used in conjunction with `zf-rest` module.

ZF2 Services
------------

#### Event Listener Services

The following services are provided and serve as event listeners:

- `ZF\MvcAuth\Authentication\DefaultAuthenticationListener`
- `ZF\MvcAuth\Authentication\DefaultAuthenticationPostListener`
- `ZF\MvcAuth\Authorization\DefaultAuthorizationListener`
- `ZF\MvcAuth\Authorization\DefaultAuthorizationPostListener`
- `ZF\MvcAuth\Authorization\DefaultResourceResolverListener`

#### `ZF\MvcAuth\Authentication` (a.k.a `authentication`)

This is an instance of `Zend\Authentication\AuthenticationService`.

#### `ZF\MvcAuth\Authentication\AuthHttpAdapter`

This is an instance of `Zend\Authentication\Adapter\Http`.

#### `ZF\MvcAuth\Authorization\AclAuthorization` (a.k.a `authorization`, `ZF\MvcAuth\Authorization\AuthorizationInterface`)

This is an instance of `ZF\MvcAuth\Authorization\AclAuthorization`, which in turn is an extension
of `Zend\Permissions\Acl\Acl`.
