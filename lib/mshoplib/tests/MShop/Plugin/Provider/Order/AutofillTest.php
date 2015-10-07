<?php

namespace Aimeos\MShop\Plugin\Provider\Order;


/**
 * @copyright Copyright (c) Metaways Infosystems GmbH, 2014
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 */
class AutofillTest extends \PHPUnit_Framework_TestCase
{
	private $plugin;
	private $orderManager;
	private $order;


	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @access protected
	 */
	protected function setUp()
	{
		$context = \TestHelper::getContext();

		$pluginManager = \Aimeos\MShop\Plugin\Manager\Factory::createManager( $context );
		$this->plugin = $pluginManager->createItem();
		$this->plugin->setProvider( 'Autofill' );
		$this->plugin->setStatus( 1 );

		$this->orderManager = \Aimeos\MShop\Order\Manager\Factory::createManager( $context );
		$orderBaseManager = $this->orderManager->getSubManager( 'base' );

		$this->order = $orderBaseManager->createItem();
	}


	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 *
	 * @access protected
	 */
	protected function tearDown()
	{
		unset( $this->orderManager );
		unset( $this->plugin );
		unset( $this->order );
	}


	public function testRegister()
	{
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( \TestHelper::getContext(), $this->plugin );
		$object->register( $this->order );
	}


	public function testUpdateNone()
	{
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( \TestHelper::getContext(), $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
		$this->assertEquals( array(), $this->order->getServices() );

	}


	public function testUpdateOrderNoItem()
	{
		$context = \TestHelper::getContext();
		$context->setUserId( '' );
		$this->plugin->setConfig( array( 'autofill.useorder' => '1' ) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( $context, $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
		$this->assertEquals( array(), $this->order->getServices() );
	}


	public function testUpdateOrderNone()
	{
		$context = \TestHelper::getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'customer.code', 'UTC001' ) );
		$result = $manager->searchItems( $search );

		if( ( $customer = reset( $result ) ) === false ) {
			throw new \Exception( 'No customer item for code UTC001" found' );
		}

		$context->setUserId( $customer->getId() );
		$this->plugin->setConfig( array(
			'autofill.useorder' => '1',
			'autofill.orderaddress' => '0',
			'autofill.orderservice' => '0'
		) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( $context, $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
		$this->assertEquals( array(), $this->order->getServices() );
	}


	public function testUpdateOrderAddress()
	{
		$context = \TestHelper::getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'customer.code', 'UTC001' ) );
		$result = $manager->searchItems( $search );

		if( ( $customer = reset( $result ) ) === false ) {
			throw new \Exception( 'No customer item for code UTC001" found' );
		}


		$orderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( array( $context ) )->setMethods( array( 'getSubManager' ) )->getMock();

		$orderBaseStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Base\\Standard' )
			->setConstructorArgs( array( $context ) )->setMethods( array( 'getSubManager' ) )->getMock();

		$orderBaseAddressStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Base\\Address\\Standard' )
			->setConstructorArgs( array( $context ) )->setMethods( array( 'searchItems' ) )->getMock();

		$item1 = $orderBaseAddressStub->createItem();
		$item1->setType( \Aimeos\MShop\Order\Item\Base\Address\Base::TYPE_DELIVERY );
		$item2 = $orderBaseAddressStub->createItem();
		$item2->setType( \Aimeos\MShop\Order\Item\Base\Address\Base::TYPE_PAYMENT );

		$orderStub->expects( $this->any() )->method( 'getSubManager' )->will( $this->returnValue( $orderBaseStub ) );
		$orderBaseStub->expects( $this->any() )->method( 'getSubManager' )->will( $this->returnValue( $orderBaseAddressStub ) );
		$orderBaseAddressStub->expects( $this->once() )->method( 'searchItems' )->will( $this->returnValue( array( $item1, $item2 ) ) );

		\Aimeos\MShop\Order\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Order\\Manager\\PluginAutofill', $orderStub );
		$context->getConfig()->set( 'classes/order/manager/name', 'PluginAutofill' );


		$context->setUserId( $customer->getId() );
		$this->plugin->setConfig( array(
			'autofill.useorder' => '1',
			'autofill.orderaddress' => '1',
			'autofill.orderservice' => '0'
		) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( $context, $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( 2, count( $this->order->getAddresses() ) );
		$this->assertEquals( array(), $this->order->getServices() );
	}


	public function testUpdateOrderService()
	{
		$context = \TestHelper::getContext();

		$manager = \Aimeos\MShop\Factory::createManager( $context, 'customer' );
		$search = $manager->createSearch();
		$search->setConditions( $search->compare( '==', 'customer.code', 'UTC001' ) );
		$result = $manager->searchItems( $search );

		if( ( $customer = reset( $result ) ) === false ) {
			throw new \Exception( 'No customer item for code UTC001" found' );
		}


		$orderStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Standard' )
			->setConstructorArgs( array( $context ) )->setMethods( array( 'getSubManager' ) )->getMock();

		$orderBaseStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Base\\Standard' )
			->setConstructorArgs( array( $context ) )->setMethods( array( 'getSubManager' ) )->getMock();

		$orderBaseServiceStub = $this->getMockBuilder( '\\Aimeos\\MShop\\Order\\Manager\\Base\\Service\\Standard' )
			->setConstructorArgs( array( $context ) )->setMethods( array( 'searchItems' ) )->getMock();

		$item1 = $orderBaseServiceStub->createItem();
		$item1->setType( \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_DELIVERY );
		$item1->setCode( 'unitcode' );

		$item2 = $orderBaseServiceStub->createItem();
		$item2->setType( \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT );
		$item2->setCode( 'unitpaymentcode' );

		$orderStub->expects( $this->any() )->method( 'getSubManager' )->will( $this->returnValue( $orderBaseStub ) );
		$orderBaseStub->expects( $this->any() )->method( 'getSubManager' )->will( $this->returnValue( $orderBaseServiceStub ) );
		$orderBaseServiceStub->expects( $this->once() )->method( 'searchItems' )->will( $this->returnValue( array( $item1, $item2 ) ) );

		\Aimeos\MShop\Order\Manager\Factory::injectManager( '\\Aimeos\\MShop\\Order\\Manager\\PluginAutofill', $orderStub );
		$context->getConfig()->set( 'classes/order/manager/name', 'PluginAutofill' );


		$context->setUserId( $customer->getId() );
		$this->plugin->setConfig( array(
			'autofill.useorder' => '1',
			'autofill.orderaddress' => '0',
			'autofill.orderservice' => '1'
		) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( $context, $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( 2, count( $this->order->getServices() ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
	}


	public function testUpdateDelivery()
	{
		$type = \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_DELIVERY;
		$this->plugin->setConfig( array( 'autofill.delivery' => '1' ) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( \TestHelper::getContext(), $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
		$this->assertEquals( 1, count( $this->order->getServices() ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Order\\Item\\Base\\Service\\Iface', $this->order->getService( $type ) );
	}


	public function testUpdateDeliveryCode()
	{
		$type = \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_DELIVERY;
		$this->plugin->setConfig( array( 'autofill.delivery' => '1', 'autofill.deliverycode' => 'unitcode' ) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( \TestHelper::getContext(), $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
		$this->assertEquals( 1, count( $this->order->getServices() ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Order\\Item\\Base\\Service\\Iface', $this->order->getService( $type ) );
		$this->assertEquals( 'unitcode', $this->order->getService( $type )->getCode() );
	}


	public function testUpdateDeliveryCodeNotExists()
	{
		$type = \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_DELIVERY;
		$this->plugin->setConfig( array( 'autofill.delivery' => '1', 'autofill.deliverycode' => 'xyz' ) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( \TestHelper::getContext(), $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
		$this->assertEquals( 1, count( $this->order->getServices() ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Order\\Item\\Base\\Service\\Iface', $this->order->getService( $type ) );
	}


	public function testUpdatePayment()
	{
		$type = \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT;
		$this->plugin->setConfig( array( 'autofill.payment' => '1' ) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( \TestHelper::getContext(), $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
		$this->assertEquals( 1, count( $this->order->getServices() ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Order\\Item\\Base\\Service\\Iface', $this->order->getService( $type ) );
	}


	public function testUpdatePaymentCode()
	{
		$type = \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT;
		$this->plugin->setConfig( array( 'autofill.payment' => '1', 'autofill.paymentcode' => 'unitpaymentcode' ) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( \TestHelper::getContext(), $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
		$this->assertEquals( 1, count( $this->order->getServices() ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Order\\Item\\Base\\Service\\Iface', $this->order->getService( $type ) );
		$this->assertEquals( 'unitpaymentcode', $this->order->getService( $type )->getCode() );
	}


	public function testUpdatePaymentCodeNotExists()
	{
		$type = \Aimeos\MShop\Order\Item\Base\Service\Base::TYPE_PAYMENT;
		$this->plugin->setConfig( array( 'autofill.payment' => '1', 'autofill.paymentcode' => 'xyz' ) );
		$object = new \Aimeos\MShop\Plugin\Provider\Order\Autofill( \TestHelper::getContext(), $this->plugin );

		$this->assertTrue( $object->update( $this->order, 'addProduct.after' ) );
		$this->assertEquals( array(), $this->order->getAddresses() );
		$this->assertEquals( 1, count( $this->order->getServices() ) );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Order\\Item\\Base\\Service\\Iface', $this->order->getService( $type ) );
	}
}