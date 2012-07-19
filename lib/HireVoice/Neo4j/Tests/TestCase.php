<?php
namespace HireVoice\Neo4j\Tests;

use HireVoice\Neo4j\EntityManager;
use HireVoice\Neo4j\Meta\Repository as MetaRepository;
use HireVoice\Neo4j\Proxy\Factory as ProxyFactory;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function getEntityManager()
    {
        $client = new \Everyman\Neo4j\Client(new \Everyman\Neo4j\Transport($GLOBALS['host'], $GLOBALS['port']));
        $em = new EntityManager($client, new MetaRepository());
        $em->setProxyFactory(new ProxyFactory('/tmp', true));
        return $em;
    }
}

