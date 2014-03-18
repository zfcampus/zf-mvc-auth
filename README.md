ZF MVC Auth
===========

[![Build Status](https://travis-ci.org/zfcampus/zf-mvc-auth.png)](https://travis-ci.org/zfcampus/zf-mvc-auth)
[![Coverage Status](https://coveralls.io/repos/zfcampus/zf-mvc-auth/badge.png?branch=master)](https://coveralls.io/r/zfcampus/zf-mvc-auth)

Provide events for Authentication and Authorization in the ZF2 MVC lifecycle.


Installation
------------

You can install using:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
```


Configuration
-------------

Services:
    ```authentication``` is provided and is an instance of Zend\Auth\AuthenticationService
    with a NonPersistent storage adapter.
