<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\Authentication\Adapter\Http as HttpAuth;
use Zend\Authentication\Adapter\Http\FileResolver;
use ZF\MvcAuth\Exception;

class ConfigurableHttpAdapter implements AdapterInterface
{
    protected $authAllowed = array('basic', 'digest');

    public function __construct(array $config, Request $request, Response $response)
    {
        if (!isset($config['type'])) {
            throw new Exception\InvalidArgumentExeption(
                'You must specify the type of authentication'
            );
        }
        $type = strtolower($config['type']);
        if (!in_array($type, $this->authAllowed)) {
            throw new Exception\InvalidArgumentException(
                'The authentication specified is not supported'
            );
        }
        if (in_array($type, array('basic', 'digest')) && empty($config['file'])) {
            throw new Exception\InvalidArgumentException(
                'You must specify the password\'s file for basic and digest authentication'
            );
        }

        switch ($type) {
            case 'basic':
                $this->authAdapter = new HttpAuth($config);
                $basicResolver = new FileResolver();
                $basicResolver->setFile($config['file']);
                $this->authAdapter->setBasicResolver($basicResolver);
                $this->authAdapter->setRequest($request);
                $this->authAdapter->setResponse($response);
                break;

            case 'digest':
                $this->authAdapter = new HttpAuth($config);
                $digestResolver = new FileResolver();
                $digestResolver->setFile('files/digestPasswd.txt');
                $this->authAdapter->setDigestResolver($digestResolver);
                $this->authAdapter->setRequest($request);
                $this->authAdapter->setResponse($response);
                break;
        }
    }

    public function authenticate()
    {
        return $this->authAdapter->authenticate();
    }

}