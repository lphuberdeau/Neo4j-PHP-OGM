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
use HireVoice\Neo4j;

class QueryTest extends TestCase
{
    static $root;
    static $aragorn;
    static $legolas;

    function setUp()
    {
        if (! self::$root) {
            $aragorn = new Entity\Person;
            $aragorn->setFirstName('Viggo');
            $aragorn->setLastName('Mortensen');

            $legolas = new Entity\Person;
            $legolas->setFirstName('Orlando');
            $legolas->setLastName('Bloom');
            $legolas->addFriend($aragorn);

            $root = new Entity\Movie;
            $root->setTitle('Return of the king');
            $root->addActor($aragorn);
            $root->addActor($legolas);

            self::$root = $root;
            self::$aragorn = $aragorn;
            self::$legolas = $legolas;

            $em = $this->getEntityManager();
            $em->persist($root);
            $em->flush();
        }
    }

    function testSimpleQuery()
    {
        $em = $this->getEntityManager();
        $result = $em->createGremlinQuery("g.v(:movie).out('actor')")
            ->set('movie', self::$root)
            ->getList();

        $this->assertCount(2, $result);
        $this->assertTrue($result->map(function ($actor) {
            return $actor->getFirstName() . ' ' . $actor->getLastName();
        })->contains('Orlando Bloom'), "Orlando Bloom in the actor list");
    }

    function testSearchWithArray()
    {
        $em = $this->getEntityManager();
        $result = $em->createGremlinQuery("g.v(:movie).out('actor').filter{:actor.contains(it.lastName)}")
            ->set('movie', self::$root)
            ->set('actor', array('Bloom', 'Tyler'))
            ->getList();

        $this->assertCount(1, $result);
        $this->assertTrue($result->map(function ($actor) {
            return $actor->getFirstName() . ' ' . $actor->getLastName();
        })->contains('Orlando Bloom'), "Orlando Bloom in the actor list");
    }

    /**
     * @expectedException HireVoice\Neo4j\Exception
     */
    function testDetectErrors()
    {
        $em = $this->getEntityManager();
        $result = $em->createGremlinQuery("g.v(:movie)")
            ->getList();
    }

    function testExceptionContainsCypherQuery()
    {
        $em = $this->getEntityManager();
        
        $query = null;
        
        try {
            $result = $em->createCypherQuery()
                         ->start('movie = node(:movie)')
                         ->end('movie')
                         ->getOne();
        } catch(\HireVoice\Neo4j\Exception $e) {
            $query = $e->getQuery();
        }
        
        $this->assertInstanceOf('Everyman\Neo4j\Cypher\Query', $query);
    }
    
    function testExceptionContainsGremlinQuery()
    {
        $em = $this->getEntityManager();
        
        $query = null;
        
        try {
            $result = $em->createGremlinQuery("g.v(:movie)")
                         ->getList();
        } catch(\HireVoice\Neo4j\Exception $e) {
            $query = $e->getQuery();
        }
        
        $this->assertInstanceOf('Everyman\Neo4j\Gremlin\Query', $query);
    }
    
    function testGroupCount()
    {
        $em = $this->getEntityManager();
        $result = $em->createGremlinQuery("m = [:]; g.v(:movie).out('actor').lastName.groupCount(m).iterate();m")
            ->set('movie', self::$root)
            ->getMap();
        
        $this->assertEquals(array(
            'Bloom' => 1,
            'Mortensen' => 1,
        ), $result);
    }

    function testGroupCountWithNode()
    {
        $em = $this->getEntityManager();
        $result = $em->createGremlinQuery("m = [:]; g.v(:movie).out('actor').groupCount(m).iterate();m")
            ->set('movie', self::$root)
            ->getEntityMap();
        
        $this->assertContains('Bloom', array($result[0]['key']->getLastName(), $result[1]['key']->getLastName()));
        $this->assertEquals(1, $result[0]['value']);
        $this->assertCount(2, $result);
    }

    function testWithMultipleParts()
    {
        $em = $this->getEntityManager();
        $result = $em->createGremlinQuery()
            ->add("m = [:]")
            ->add("g.v(:movie).out('actor').lastName.groupCount(m).iterate()")
            ->add("m")
            ->set('movie', self::$root)
            ->getMap();
        
        $this->assertEquals(array(
            'Bloom' => 1,
            'Mortensen' => 1,
        ), $result);
    }

    function testGetEmptyMap()
    {
        $em = $this->getEntityManager();
        $result = $em->createGremlinQuery()
            ->add("m = [:]")
            ->getMap();
        
        $this->assertEquals(array(
        ), $result);
    }

    function testObtainPrimitive()
    {
        $em = $this->getEntityManager();
        $result = $em->createGremlinQuery()
            ->add("5")
            ->getOne();
        
        $this->assertEquals(5, $result);
    }

    function testCypherBasicQuery()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->start('movie = node(:movie)')
            ->end('movie')
            ->set('movie', self::$root)
            ->getOne();

        $this->assertEquals('Return of the king', $result->getTitle());
    }

    function testGetBasicCypherMatch()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->start('movie = node(:movie)')
            ->match('(movie) -[:actor]-> (actor)')
            ->end('actor')
            ->set('movie', self::$root)
            ->getList();

        $this->assertTrue($result->map(function ($actor) {
            return $actor->getFirstName() . ' ' . $actor->getLastName();
        })->contains('Orlando Bloom'), "Orlando Bloom in the actor list");
    }

    /**
     * @expectedException HireVoice\Neo4j\Exception
     */
    function testDetectCypherErrors()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->start('movie = node(:movie)')
            ->end('movie')
            ->getOne();
    }

    function testCypherWithMultipleStart()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->start('aragorn = node(:aragorn)', 'legolas = node(:legolas)')
            ->match('(aragorn) <-- (movie) --> (legolas)')
            ->end('movie')
            ->set('aragorn', self::$aragorn)
            ->set('legolas', self::$legolas)
            ->getOne();

        $this->assertEquals('Return of the king', $result->getTitle());
    }

    function testCypherWithMultipleMatch()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->start('aragorn = node(:aragorn)')
            ->start('legolas = node(:legolas)')
            ->match('(movie) --> (aragorn)')
            ->match('(movie) --> (legolas)')
            ->end('movie')
            ->set('aragorn', self::$aragorn)
            ->set('legolas', self::$legolas)
            ->getOne();

        $this->assertEquals('Return of the king', $result->getTitle());
    }

    function testObtainCypherRecordset()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('actor', self::$aragorn)
            ->match('(movie) --> (actor)')
            ->match('(movie) --> (costar)')
            ->where('actor <> costar')
            ->end('actor', 'costar')
            ->getResult();

        $this->assertCount(1, $result);
        $first = $result->first();
        $this->assertEquals('Orlando', $first['costar']->getFirstName());
        $this->assertEquals('Viggo', $first['actor']->getFirstName());
    }

    function testObtainPropertiesFromCypher()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('actor', self::$aragorn)
            ->match('(movie) --> (actor)')
            ->match('(movie) --> (costar)')
            ->where('actor <> costar')
            ->end('costar.firstName', 'costar.lastName as lastName')
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals(array(
            'costar.firstName' => 'Orlando',
            'lastName' => 'Bloom',
        ), $result->first());
    }

    function testCypherOrdering()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('movie', self::$root)
            ->match('(movie) -[:actor]-> (actor)')
            ->end('actor.firstName, count(*)')
            ->order('count(*), actor.firstName')
            ->getList();

        $this->assertEquals(array(
            'Orlando',
            'Viggo',
        ), $result->toArray());
    }

    function testSearchOnProperty()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('movie', self::$root)
            ->match('(movie) -[:actor]-> (actor)')
            ->where('actor.lastName = :name')
            ->end('actor.firstName')
            ->set('name', 'Bloom')
            ->getList();

        $this->assertEquals(array(
            'Orlando',
        ), $result->toArray());
    }

    function testSearchOnPropertyMultipleWhere()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('movie', self::$root)
            ->match('(movie) -[:actor]-> (actor)')
            ->where('actor.firstName = :firstname')
            ->where('actor.lastName = :name')
            ->end('actor.firstName')
            ->set('firstname', 'Orlando')
            ->set('name', 'Bloom')
            ->getList();

        $this->assertEquals(array(
            'Orlando',
        ), $result->toArray());
    }

    function testStartWithLuceneQuery()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithQuery('person', 'HireVoice\\Neo4j\\Tests\\Entity\\Person', 'firstName:O*')
            ->startWithNode('root', self::$root)
            ->match('root -[:actor]-> person')
            ->end('person')
            ->getList();

        $this->assertCount(1, $result);
        $this->assertEquals('Bloom', $result->first()->getLastName());
    }

    function testFilterSingleKeyLookup()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithLookup('person', 'HireVoice\\Neo4j\\Tests\\Entity\\Person', 'firstName', 'Orlando')
            ->startWithNode('root', self::$root)
            ->match('root -[:actor]-> person')
            ->end('person')
            ->getList();

        $this->assertCount(1, $result);
        $this->assertEquals('Bloom', $result->first()->getLastName());
    }

    function testCypherLimiting()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('movie', self::$root)
            ->match('(movie) -[:actor]-> (actor)')
            ->end('actor.firstName, count(*)')
            ->order('count(*), actor.firstName')
            ->limit(1)
            ->getList();

        $this->assertEquals(array(
            'Orlando',
        ), $result->toArray());
    }

    function testCypherSkiping()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('movie', self::$root)
            ->match('(movie) -[:actor]-> (actor)')
            ->end('actor.firstName, count(*)')
            ->order('count(*), actor.firstName')
            ->skip(1)
            ->getList();

        $this->assertEquals(array(
            'Viggo',
        ), $result->toArray());
    }

    /**
     * @group neo4j-v2
     */
    function testCypherOptionalMatchWithFullPath()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('movie', self::$root)
            ->optionalMatch('(movie) -[:actor]-> (actor) -[:friend]-> (x)')
            ->end('actor.firstName as n1,x.firstName as n2')
            ->getResult();

		$this->assertEquals(array(
            array(
                "n1" => "Orlando",
                "n2" => "Viggo"
            )
        ), $result->toArray());
    }

    /**
     * @group neo4j-v2
     */
    function testCypherOptionalMatchConcatened()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('movie', self::$root)
            ->optionalMatch('(movie) -[:actor]-> (actor), (actor) -[:friend]-> (x)')
            ->end('actor.firstName as n1,x.firstName as n2')
            ->getResult();

        $this->assertEquals(array(
            array(
                "n1" => "Orlando",
                "n2" => "Viggo"
            )
        ), $result->toArray());
    }

    /**
     * @group neo4j-v2
     */
    function testCypherMultipleOptionalMatchClauses()
    {
        $em = $this->getEntityManager();
        $result = $em->createCypherQuery()
            ->startWithNode('movie', self::$root)
            ->optionalMatch('(movie) -[:actor]-> (actor)')
            ->optionalMatch('(actor) -[:friend]-> (x)')
            ->end('actor.firstName as n1,x.firstName as n2')
            ->order('n1 DESC')
            ->getResult();

         $this->assertEquals(array(
            //Viggo has no friend but he's returned
            array(
                "n1" => "Viggo",
                "n2" => null,
            ),
            //Orlando with his friend Viggo
            array(
                "n1" => "Orlando",
                "n2" => "Viggo"
            )
        ), $result->toArray());
    }
}
