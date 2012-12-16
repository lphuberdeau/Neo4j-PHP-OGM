<?php
/**
 * Copyright (C) 2012 Louis-Philippe Huberdeau
 *
 * Permission is hereby granted, free of charge, to any person obtaining a 
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */


namespace HireVoice\Neo4j\Tests;

use HireVoice\Neo4j\Configuration;
use HireVoice\Neo4j\Proxy\Factory;
use HireVoice\Neo4j\Meta\Repository;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Transport;
use Everyman\Neo4j\PathFinder;

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

    function testSpecifyCurl()
    {
        $configuration = new Configuration(array(
            'host' => 'example.com',
            'transport' => 'curl',
        ));;

        $this->assertEquals(new Client(new Transport\Curl('example.com', 7474)), $configuration->getClient());
    }

    function testSpecifyStream()
    {
        $configuration = new Configuration(array(
            'host' => 'example.com',
            'transport' => 'stream',
        ));;

        $this->assertEquals(new Client(new Transport\Stream('example.com', 7474)), $configuration->getClient());
    }

    function testSpecifyCredentials()
    {
        $configuration = new Configuration(array(
            'username' => 'foobar',
            'password' => 'baz',
        ));;

        $transport = new Transport\Curl;
        $transport->setAuth('foobar', 'baz');
        $this->assertEquals(new Client($transport), $configuration->getClient());
    }

    function testPathFinderMaxDepthAndAlgorithm()
    {
        $configuration = new Configuration(array(
            'pathfinder_maxdepth' => 5,
            'pathfinder_algorithm' => PathFinder::AlgoAllSimple,
        ));

        $this->assertEquals(PathFinder::AlgoAllSimple, $configuration->getPathFinderAlgorithm());
        $this->assertEquals(5, $configuration->getPathFinderMaxDepth());
    }
}

