<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Factory;

use Zend\Http\Request;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZF\MvcAuth\AclFactory;
use ZF\MvcAuth\DefaultAuthorizationListener;

/**
 * Factory for creating the DefaultAuthorizationListener from configuration
 */
class DefaultAuthorizationListenerFactory implements FactoryInterface
{
    protected $httpMethods = array(
        Request::METHOD_DELETE => true,
        Request::METHOD_GET    => true,
        Request::METHOD_PATCH  => true,
        Request::METHOD_POST   => true,
        Request::METHOD_PUT    => true,
    );

    /**
     * Create the DefaultAuthorizationListener
     * 
     * @param ServiceLocatorInterface $services 
     * @return DefaultAuthorizationListener
     */
    public function createService(ServiceLocatorInterface $services)
    {
        if (!$services->has('config')) {
            throw new ServiceNotCreatedException(
                'Cannot create DefaultAuthorizationListener service; no configuration available!'
            );
        }

        $config = $services->get('config');

        return new DefaultAuthorizationListener(
            $this->createAclFromConfig($config),
            $this->getRestServicesFromConfig($config)
        );
    }

    /**
     * Generate the ACL instance based on the zf-mc-auth "rules" configuration
     *
     * Consumes the AclFactory in order to create the ACL instance.
     * 
     * @param array $config 
     * @return \Zend\Permissions\Acl\Acl
     */
    protected function createAclFromConfig(array $config)
    {
        $aclConfig = array();
        if (isset($config['zf-mvc-auth'])
            && isset($config['zf-mvc-auth']['rules'])
        ) {
            $rulesConfig = $config['zf-mvc-auth']['rules'];
            foreach ($rulesConfig as $controllerService => $rules) {
                $this->createAclConfigFromRules($controllerService, $rules, $aclConfig);
            }
        }

        return AclFactory::factory($aclConfig);
    }

    /**
     * Generate the list of REST services for the listener
     *
     * Looks for zf-rest configuration, and creates a list of controller
     * service / identifier name pairs to pass to the listener.
     * 
     * @param array $config 
     * @return array
     */
    protected function getRestServicesFromConfig(array $config)
    {
        $restServices = array();
        if (!isset($config['zf-rest'])) {
            return $restServices;
        }

        foreach ($config['zf-rest'] as $controllerService => $restConfig) {
            if (!isset($restConfig['identifier_name'])) {
                continue;
            }
            $restServices[$controllerService] = $restConfig['identifier_name'];
        }

        return $restServices;
    }

    /**
     * Creates ACL configuration based on the rules configured
     *
     * - Extracts a rule per action
     * - Extracts rules for each of "collection" and "resource" configured
     * 
     * @param string $controllerService 
     * @param array $rules 
     * @param array $aclConfig 
     */
    protected function createAclConfigFromRules($controllerService, array $rules, &$aclConfig)
    {
        if (isset($rules['actions'])) {
            foreach ($rules['actions'] as $action => $methods) {
                $aclConfig[] = array(
                    'resource' => sprintf('%s::%s', $controllerService, $action),
                    'rights'   => $this->createRightsFromMethods($methods),
                );
            }
        }

        if (isset($rules['collection'])) {
            $aclConfig[] = array(
                'resource' => sprintf('%s::collection', $controllerService),
                'rights'   => $this->createRightsFromMethods($rules['collection']),
            );
        }

        if (isset($rules['resource'])) {
            $aclConfig[] = array(
                'resource' => sprintf('%s::resource', $controllerService),
                'rights'   => $this->createRightsFromMethods($rules['resource']),
            );
        }
    }

    /**
     * Create the list of HTTP methods defining rights
     * 
     * @param array $methods 
     * @return array|null
     */
    protected function createRightsFromMethods(array $methods)
    {
        $rights = array();

        if (isset($methods['all_methods']) && $methods['all_methods']) {
            $rights = $this->httpMethods;
        }

        foreach ($methods as $method => $flag) {
            if (!$flag) {
                if (isset($rights[$method])) {
                    unset($rights[$method]);
                }
                continue;
            }
            $rights[$method] = true;
        }

        if (empty($rights)) {
            return null;
        }

        return array_keys($rights);
    }
}
