<?php
/**
 * @author Stefano Torresi (http://stefanotorresi.it)
 * @license See the file LICENSE.txt for copying permission.
 * ************************************************
 */

namespace ZFTest\MvcAuth\Authentication;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request;
use Zend\Http\Response;
use ZF\MvcAuth\Authentication\CompositeAdapter;

class CompositeAdapterTest extends TestCase
{
    /**
     * @var CompositeAdapter
     */
    protected $adapter;

    public function setUp()
    {
        $this->adapter = new CompositeAdapter();
    }

    public function testCanAddAdapter()
    {
        $adapterMock = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mockProvides = array('foo', 'bar');
        $adapterMock
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mockProvides))
        ;
        $this->adapter->addAdapter($adapterMock);

        $this->assertEquals($mockProvides, $this->adapter->provides());
        $this->assertTrue($this->adapter->matches('foo'));
        $this->assertTrue($this->adapter->matches('bar'));
    }

    public function testAddAdapterIsIdemPotent()
    {
        $adapterMock = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mockProvides = array('foo', 'bar');
        $adapterMock
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mockProvides))
        ;
        $this->adapter->addAdapter($adapterMock);

        $oldAdapter = serialize($this->adapter);

        $this->adapter->addAdapter($adapterMock);

        $newAdapter = serialize($this->adapter);

        $this->assertSame($oldAdapter, $newAdapter);
    }

    public function testCanAddMultipleAdapters()
    {
        $adapterMock1 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $adapterMock2 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mock1Provides = array('foo', 'bar');
        $mock2Provides = array('bar', 'baz');
        $adapterMock1
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock1Provides))
        ;
        $adapterMock2
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock2Provides))
        ;

        $this->adapter->addAdapter($adapterMock1);
        $this->adapter->addAdapter($adapterMock2);

        $this->assertEquals(
            array_values(array_unique(array_merge($mock1Provides, $mock2Provides))),
            $this->adapter->provides()
        );
    }

    public function testCanRemoveAdapter()
    {
        $adapterMock = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mockProvides = array('foo', 'bar');
        $adapterMock
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mockProvides))
        ;
        $this->adapter->addAdapter($adapterMock);
        $this->adapter->removeAdapter($adapterMock);

        $this->assertEmpty($this->adapter->provides());
    }

    public function testCanRemoveAdapterByType()
    {
        $adapterMock1 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $adapterMock2 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mock1Provides = array('foo', 'bar');
        $mock2Provides = array('bar', 'baz');
        $adapterMock1
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock1Provides))
        ;
        $adapterMock2
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock2Provides))
        ;

        $this->adapter->addAdapter($adapterMock1);
        $this->adapter->addAdapter($adapterMock2);

        $this->adapter->removeAdapter('baz');

        $this->assertEquals($mock1Provides, $this->adapter->provides());
    }

    /**
     * @param mixed $invalidValue
     *
     * @dataProvider invalidRemoveValues
     */
    public function testRemoveAdapterWithInvalidValuesThrows($invalidValue)
    {
        $this->setExpectedException('InvalidArgumentException');

        $this->adapter->removeAdapter($invalidValue);
    }

    public function invalidRemoveValues()
    {
        return array(
            array( 0 ),
            array( new \stdClass() ),
            array( true ),
            array( array() ),
        );
    }

    public function testPreviouslyAddedAdaptersCanHandleTypesSupersededByRemovedOnes()
    {
        $adapterMock1 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $adapterMock2 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mock1Provides = array('foo', 'bar');
        $mock2Provides = array('bar', 'baz');
        $adapterMock1
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock1Provides))
        ;
        $adapterMock2
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock2Provides))
        ;

        $this->adapter->addAdapter($adapterMock1);
        $this->adapter->addAdapter($adapterMock2);

        $this->adapter->removeAdapter($adapterMock2);

        $this->assertEquals($mock1Provides, $this->adapter->provides());
    }

    public function testCanGetTypeFromRequest()
    {
        $request      = new Request();
        $adapterMock  = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mockProvides = array('foo', 'bar');
        $adapterMock
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mockProvides))
        ;
        $adapterMock
            ->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($request)
            ->will($this->returnValue('foo'))
        ;

        $this->adapter->addAdapter($adapterMock);

        $this->assertEquals('foo', $this->adapter->getTypeFromRequest($request));
    }

    public function testGetTypeFromRequestCanReturnFalse()
    {
        $request      = new Request();
        $adapterMock  = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mockProvides = array('foo', 'bar');
        $adapterMock
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mockProvides))
        ;
        $adapterMock
            ->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($request)
            ->will($this->returnValue(false))
        ;

        $this->adapter->addAdapter($adapterMock);

        $this->assertFalse($this->adapter->getTypeFromRequest($request));
    }

    public function testDelegatesAuthentication()
    {
        $request      = new Request();
        $response     = new Response();
        $event        = $this->getMockBuilder('\ZF\MvcAuth\MvcAuthEvent')->disableOriginalConstructor()->getMock();
        $adapterMock1 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $adapterMock2 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mock1Provides = array('foo', 'bar');
        $mock2Provides = array('bar', 'baz');
        $adapterMock1
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock1Provides))
        ;
        $adapterMock1
            ->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($request)
            ->will($this->returnValue('bar'))
        ;
        $adapterMock1
            ->expects($this->never()) // we assert that the first adapter is superseded by the second one
            ->method('authenticate')
        ;
        $adapterMock2
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock2Provides))
        ;
        $adapterMock2
            ->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($request)
            ->will($this->returnValue('bar'))
        ;
        $adapterMock2
            ->expects($this->atLeastOnce())
            ->method('authenticate')
            ->with($request, $response, $event)
            ->will($this->returnValue(true))
        ;

        $this->adapter->addAdapter($adapterMock1);
        $this->adapter->addAdapter($adapterMock2);

        $this->assertTrue($this->adapter->authenticate($request, $response, $event));
    }

    public function testAuthenticationCanReturnFalse()
    {
        $request      = new Request();
        $response     = new Response();
        $event        = $this->getMockBuilder('\ZF\MvcAuth\MvcAuthEvent')->disableOriginalConstructor()->getMock();
        $adapterMock  = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mockProvides = array('foo', 'bar');
        $adapterMock
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mockProvides))
        ;
        $adapterMock
            ->expects($this->any())
            ->method('getTypeFromRequest')
            ->with($request)
            ->will($this->returnValue(false))
        ;

        $this->adapter->addAdapter($adapterMock);

        $this->assertFalse($this->adapter->authenticate($request, $response, $event));
    }

    public function testDelegatesPreAuth()
    {
        $request      = new Request();
        $response     = new Response();
        $adapterMock1 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $adapterMock2 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mock1Provides = array('foo', 'bar');
        $mock2Provides = array('bar', 'baz');
        $adapterMock1
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock1Provides))
        ;
        $adapterMock1
            ->expects($this->atLeastOnce())
            ->method('preAuth')
            ->with($request, $response)
        ;
        $adapterMock2
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock2Provides))
        ;
        $adapterMock2
            ->expects($this->atLeastOnce())
            ->method('preAuth')
            ->with($request, $response)
        ;

        $this->adapter->addAdapter($adapterMock1);
        $this->adapter->addAdapter($adapterMock2);
        $this->adapter->preAuth($request, $response);
    }

    public function testPreAuthShortCircuitsWhenAnAdapterReturnsAResponse()
    {
        $request      = new Request();
        $response     = new Response();
        $adapterMock1 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $adapterMock2 = $this->getMock('ZF\MvcAuth\Authentication\AdapterInterface');
        $mock1Provides = array('foo', 'bar');
        $mock2Provides = array('bar', 'baz');
        $adapterMock1
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock1Provides))
        ;
        $adapterMock1
            ->expects($this->atLeastOnce())
            ->method('preAuth')
            ->with($request, $response)
            ->will($this->returnValue($response))
        ;
        $adapterMock2
            ->expects($this->any())
            ->method('provides')
            ->will($this->returnValue($mock2Provides))
        ;
        $adapterMock2
            ->expects($this->never())
            ->method('preAuth')
        ;

        $this->adapter->addAdapter($adapterMock1);
        $this->adapter->addAdapter($adapterMock2);
        $this->assertSame($response, $this->adapter->preAuth($request, $response));
    }
}
