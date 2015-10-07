<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 */


namespace Aimeos\MAdmin\Job\Manager;


/**
 * Test class for \Aimeos\MAdmin\Job\Manager\Factory.
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
	public function testCreateManager()
	{
		$manager = \Aimeos\MAdmin\Job\Manager\Factory::createManager( \TestHelper::getContext() );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Manager\\Iface', $manager );
	}


	public function testCreateManagerName()
	{
		$manager = \Aimeos\MAdmin\Job\Manager\Factory::createManager( \TestHelper::getContext(), 'Standard' );
		$this->assertInstanceOf( '\\Aimeos\\MShop\\Common\\Manager\\Iface', $manager );
	}


	public function testCreateManagerInvalidName()
	{
		$this->setExpectedException( '\\Aimeos\\MAdmin\\Job\\Exception' );
		\Aimeos\MAdmin\Job\Manager\Factory::createManager( \TestHelper::getContext(), '%^' );
	}


	public function testCreateManagerNotExisting()
	{
		$this->setExpectedException( '\\Aimeos\\MShop\\Exception' );
		\Aimeos\MAdmin\Job\Manager\Factory::createManager( \TestHelper::getContext(), 'unknown' );
	}

}