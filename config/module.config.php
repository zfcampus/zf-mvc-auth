<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'authentication' => 'ZF\MvcAuth\AuthenticationServiceFactory'
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'ZF\MvcAuth\Auth' => 'ZF\MvcAuth\AuthController',
        ),
    ),
    'zf-mvc-auth' => array(
        'controller' => 'ZF\MvcAuth\Auth',
        'authentication' => array(
            'http' => array(
                'accept_schemes' => array('basic', 'digest'),
                'realm' => 'My Web Site',
                'digest_domains' => '/',
                'nonce_timeout' => 3600,
                'htpasswd' => APPLICATION_PATH . '/data/htpasswd', // htpasswd tool generated
                'htdigest' => APPLICATION_PATH . '/data/htdigest' // @see http://www.askapache.com/online-tools/htpasswd-generator/
            ),
        ),
        'authorization' => array(
            'controller' => array(
                // list of controller to be authorized
                'Application\Controller\Index' => array(
                    'all_action' => true,
                    // or list of actions specified by 'action' => array(...),
                    'all_method' => true,
                    // or list of HTTP methods specified by 'method' => array(...)
                )
            ),
        )
    )
);
