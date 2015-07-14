<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\MvcAuth\Authentication;

use InvalidArgumentException;
use Zend\Http\Request;
use Zend\Http\Response;
use ZF\MvcAuth\MvcAuthEvent;

class CompositeAdapter implements AdapterInterface
{
    /**
     * @var AdapterInterface[]
     */
    protected $adapters = array();

    /**
     * @var string
     */
    protected $name;

    /**
     * @param AdapterInterface[] $adapters
     * @param string             $name
     */
    public function __construct(array $adapters = array(), $name = null)
    {
        foreach ($adapters as $adapter) {
            $this->addAdapter($adapter);
        }

        $this->name = (string) $name;
    }

    /**
     * Adds an adapter
     *
     * Subsequently added adapters will take over on previous ones, if they provide the same authentication types
     *
     * @param AdapterInterface $adapter
     */
    public function addAdapter(AdapterInterface $adapter)
    {
        if (in_array($adapter, $this->adapters, true)) {
            return;
        }

        $types = $adapter->provides();

        foreach ($types as $type) {
            $this->adapters[$type] = $adapter;
        }
    }

    /**
     * Removes an adapter
     *
     * If an AdapterInterface instance is passed and that adapter superseded a previously added one,
     * the remaining ones will be re-added to take control back on their provided type
     *
     * @param AdapterInterface|string
     */
    public function removeAdapter($adapterOrType)
    {
        if ($adapterOrType instanceof AdapterInterface) {
            $adapters = array_filter($this->adapters, function ($adapter) use ($adapterOrType) {
                return $adapter !== $adapterOrType;
            });

            $this->adapters = array();
            foreach ($adapters as $adapter) {
                $this->addAdapter($adapter);
            }

            return;
        }

        if (! is_string($adapterOrType)) {
            throw new InvalidArgumentException('Expected AdapterInterface or string');
        }

        unset($this->adapters[$adapterOrType]);
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        $result = array_keys($this->adapters);

        if ($this->name) {
            $result[] = $this->name;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function matches($type)
    {
        return isset($this->adapters[$type]) || ($type && $type === $this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromRequest(Request $request)
    {
        foreach ($this->adapters as $adapter) {
            $type = $adapter->getTypeFromRequest($request);
            if (false !== $type && $adapter === $this->adapters[$type]) {
                return $type;
            }
        }
        return false;
    }

    /**
     * If one of the adapters returns a Response,
     * the cycle will be interrupted and all remaining pre flight checks will not be executed
     *
     * {@inheritdoc}
     */
    public function preAuth(Request $request, Response $response)
    {
        foreach ($this->adapters as $adapter) {
            $result = $adapter->preAuth($request, $response);
            if ($result instanceof Response) {
                return $result;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request, Response $response, MvcAuthEvent $mvcAuthEvent)
    {
        $type = $this->getTypeFromRequest($request);

        if (! $type) {
            return false;
        }

        $adapter = $this->adapters[$type];

        return $adapter->authenticate($request, $response, $mvcAuthEvent);
    }
}
