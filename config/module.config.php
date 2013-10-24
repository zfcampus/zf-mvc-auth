<?php
return array(
    'service_manager' => array(
        'factories' => array(
            'authentication' => 'ZF\MvcAuth\AuthenticationServiceFactory'
        ),
    ),
    'zf-mvc-auth' => array(
        'authentication' => array(
            'first' => array(
                'type' => 'service',
                'service' => 'password-callback-service'
            )
        ),
        'authorization' => array(
        )
    )
);