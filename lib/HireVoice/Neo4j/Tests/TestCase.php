<?php
namespace HireVoice\Neo4j\Tests;

use HireVoice\Neo4j\EntityManager;
use HireVoice\Neo4j\Meta\Repository as MetaRepository;
use HireVoice\Neo4j\Proxy\Factory as ProxyFactory;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function getEntityManager()
    {
    	return new EntityManager(array(
			'host' => $GLOBALS['host'],
			'port' => $GLOBALS['port'],
			'proxy_dir' => '/tmp',
			'debug' => true,
		));
    }
}

