<?php

namespace HireVoice\Neo4j\Tests;
use HireVoice\Neo4j\MetaRepository;

class EntityMetaTest extends \PHPUnit_Framework_TestCase
{
    function testObtainIndexedProperties()
    {
        $repo = new MetaRepository;
        $meta = $repo->fromClass('HireVoice\\Neo4j\\Tests\\Entity\\Movie');

        $names = array();
        foreach ($meta->getIndexedProperties() as $property) {
            $names[] = $property->getName();
        }

        $this->assertEquals(array('movieRegistryCode'), $names);
    }

    function testGetProperties()
    {
        $repo = new MetaRepository;
        $meta = $repo->fromClass('HireVoice\\Neo4j\\Tests\\Entity\\Movie');

        $names = array();
        foreach ($meta->getProperties() as $property) {
            $names[] = $property->getName();
        }

        $this->assertEquals(array('title', 'releaseDate', 'movieRegistryCode', 'blob'), $names);
    }

    function testManyToMany()
    {
        $repo = new MetaRepository;
        $meta = $repo->fromClass('HireVoice\\Neo4j\\Tests\\Entity\\Movie');

        $names = array();
        foreach ($meta->getManyToManyRelations() as $property) {
            $names[] = $property->getName();
        }

        $this->assertEquals(array('actor', 'presentedMovie'), $names);
    }

    function testManyToOne()
    {
        $repo = new MetaRepository;
        $meta = $repo->fromClass('HireVoice\\Neo4j\\Tests\\Entity\\Movie');

        $names = array();
        foreach ($meta->getManyToOneRelations() as $property) {
            $names[] = $property->getName();
        }

        $this->assertEquals(array('mainActor'), $names);
    }
}

