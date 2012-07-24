<?php

namespace HireVoice\Neo4j\Tests;

use HireVoice\Neo4j\Configuration;
use HireVoice\Neo4j\Proxy\Factory;
use HireVoice\Neo4j\Meta\Repository;

use Everyman\Neo4j\Client;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
	function testObtainDefaultClient()
	{
		$configuration = new Configuration;

		$this->assertEquals(new Client('localhost', 7474), $configuration->getClient());
	}

	function testSpecifyHost()
	{
		$configuration = new Configuration(array(
			'host' => 'example.com',
		));;

		$this->assertEquals(new Client('example.com', 7474), $configuration->getClient());
	}

	function testSpecifyPort()
	{
		$configuration = new Configuration(array(
			'port' => 7575,
		));;

		$this->assertEquals(new Client('localhost', 7575), $configuration->getClient());
	}

	function testObtainDefaultProxyFactory()
	{
		$configuration = new Configuration;

		$this->assertEquals(new Factory, $configuration->getProxyFactory());
	}

	function testObtainDebugProxy()
	{
		$configuration = new Configuration(array(
			'debug' => true,
		));

		$this->assertEquals(new Factory('/tmp', true), $configuration->getProxyFactory());
	}

	function testOntainDifferentDir()
	{
		$configuration = new Configuration(array(
			'proxy_dir' => '/tmp/foo',
		));

		$this->assertEquals(new Factory('/tmp/foo', false), $configuration->getProxyFactory());
	}

	function testObtainDefaultMetaRepository()
	{
		$configuration = new Configuration;

		$this->assertEquals(new Repository, $configuration->getMetaRepository());
	}

	function testSpecifyAnnotationReader()
	{
		$reader = new \Doctrine\Common\Annotations\CachedReader(new \Doctrine\Common\Annotations\AnnotationReader, new \Doctrine\Common\Cache\ArrayCache);
		$configuration = new Configuration(array(
			'annotation_reader' => $reader,
		));

		$this->assertEquals(new Repository($reader), $configuration->getMetaRepository());
	}
}

