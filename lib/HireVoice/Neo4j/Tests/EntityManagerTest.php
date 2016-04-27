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
use Doctrine\Common\EventManager;
use HireVoice\Neo4j\EntityManager;
use HireVoice\Neo4j\Event\PostPersist;
use HireVoice\Neo4j\Event\QueryAwareEvent;
use HireVoice\Neo4j\Event\RelationAwareEvent;

class EntityManagerTest extends TestCase
{
    function testStoreSimpleEntity()
    {
        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $em = $this->getEntityManager();
        $movie = $em->find(get_class($entity), $entity->getId());

        $this->assertEquals('Return of the king', $movie->getTitle());
    }

    function testStoreRelations()
    {
        $aragorn = new Entity\Person;
        $aragorn->setFirstName('Viggo');
        $aragorn->setLastName('Mortensen');

        $legolas = new Entity\Person;
        $legolas->setFirstName('Orlando');
        $legolas->setLastName('Bloom');

        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');
        $entity->addActor($aragorn);
        $entity->addActor($legolas);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $em = $this->getEntityManager();
        $movie = $em->find(get_class($entity), $entity->getId());

        $actors = array();
        foreach ($movie->getActors() as $actor) {
            $actors[] = $actor->getFirstName();
        }

        $this->assertEquals(array('Viggo', 'Orlando'), $actors);
    }

    function testLookupIndex()
    {
        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $movieKey = $entity->getMovieRegistryCode();

        $em = $this->getEntityManager();
        $repository = $em->getRepository(get_class($entity));
        $movie = $repository->findOneByMovieRegistryCode($movieKey);

        $this->assertEquals('Return of the king', $movie->getTitle());

        $movies = $repository->findByMovieRegistryCode($movieKey);
        $this->assertCount(1, $movies);
        $this->assertEquals($entity, $movies->first()->getEntity());
    }

    function testUpdateLookupIndex()
    {
        $john = new Entity\User;
        $john->setFirstName('John');
        $john->setLastName('Doe');

        $jane = new Entity\User;
        $jane->setFirstName('Jane');
        $jane->setLastName('Doe');

        $em = $this->getEntityManager();
        $em->persist($john);
        $em->persist($jane);
        $em->flush();

        $lastName = $john->getLastName();
        $repository = $em->getRepository(get_class($john));
        $results = $repository->findByLastName($lastName);
        $this->assertCount(2, $results);

        $jane->setLastName('Foo');
        $em->persist($jane);
        $em->flush();

        $updatedResults = $repository->findByLastName($lastName);
        $this->assertCount(1, $updatedResults);
    }

    function testNodeIndexLookup()
    {

        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');
        $entity->setCategory('long');

        $matrix = new Entity\Movie;
        $matrix->setTitle('Matrix');
        $matrix->setCategory('scifi');

        $matrix2 = new Entity\Movie;
        $matrix2->setTitle('Matrix2');
        $matrix2->setCategory('scifi');

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->persist($matrix);
        $em->persist($matrix2);
        $em->flush();

        $movieKey = $entity->getMovieRegistryCode();

        $em = $this->getEntityManager();

        $movies = $em->createCypherQuery()
            ->start('entity = node:`MovieNodeIndex`(movie_title = \'Return of the king\')')
            ->end('entity')
            ->getList();

        foreach ($movies as $movie) {
            $this->assertEquals('Return of the king', $movie->getTitle());
        }

        $movies = $em->createCypherQuery()
            ->start('entity = node:`MovieNodeIndex`(title = \'Return of the king\')')
            ->end('entity')
            ->getList();

        foreach ($movies as $movie) {
            $this->assertEquals('Return of the king', $movie->getTitle());
        }
    }

    function testFulltextIndexQuery()
    {
        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $movieKey = $entity->getMovieRegistryCode();

        $em = $this->getEntityManager();

        $movies = $em->createCypherQuery()
            ->startWithQuery('entity', 'MovieFulltextIndex', 'movie_fulltext_title:return*')
            ->end('entity')
            ->getList();
        foreach ($movies as $movie) {
            $this->assertRegExp('/^return.+/', strtolower($movie->getTitle()));
        }

        $movies = $em->createCypherQuery()
            ->startWithQuery('entity', 'MovieFulltextIndex', 'title:return*')
            ->end('entity')
            ->getList();
        foreach ($movies as $movie) {
            $this->assertRegExp('/^return.+/', strtolower($movie->getTitle()));
        }
    }

    /**
     * @expectedException \HireVoice\Neo4j\Exception
     */
    function testSearchMissingProperty()
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository('HireVoice\\Neo4j\\Tests\\Entity\\Movie');

        $repository->findByMovieRegistrationCode('Return of the king');
    }

    /**
     * @expectedException \HireVoice\Neo4j\Exception
     */
    function testSearchUnindexedProperty()
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository('HireVoice\\Neo4j\\Tests\\Entity\\Movie');

        $repository->findByCategory('Return of the king');
    }

    function testRelationsDoNotDuplicate()
    {
        $aragorn = new Entity\Person;
        $aragorn->setFirstName('Viggo');
        $aragorn->setLastName('Mortensen');

        $legolas = new Entity\Person;
        $legolas->setFirstName('Orlando');
        $legolas->setLastName('Bloom');

        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');
        $entity->addActor($aragorn);
        $entity->addActor($legolas);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $em->persist($entity);
        $em->flush();

        $em = $this->getEntityManager();
        $movie = $em->find(get_class($entity), $entity->getId());

        $this->assertCount(2, $movie->getActors());
    }

    function testManyToOneRelation()
    {
        $legolas = new Entity\Person;
        $legolas->setFirstName('Orlando');
        $legolas->setLastName('Bloom');

        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');
        $entity->setMainActor($legolas);

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $em = $this->getEntityManager();
        $movie = $em->find(get_class($entity), $entity->getId());

        $this->assertEquals('Orlando', $movie->getMainActor()->getFirstName());
    }

    /**
     * @expectedException \HireVoice\Neo4j\Exception
     */
    function testPersistNonEntity()
    {
        $em = $this->getEntityManager();
        $em->persist($this);
    }

    /**
     * @expectedException \HireVoice\Neo4j\Exception
     */
    function testPersistEntityWithoutPersistableId()
    {
        $em = $this->getEntityManager();
        $em->persist(new FailedEntity);
    }

    function testReadOnlyProperty()
    {
        $movie = new Entity\Movie;
        $movie->setTitle('Return of the king');

        $cinema = new Entity\Cinema;
        $cinema->setName('Paramount');
        $cinema->addPresentedMovie($movie);

        $cinema2 = new Entity\Cinema;
        $cinema2->setName('Fake');
        $movie->addCinema($cinema2);

        $em = $this->getEntityManager();
        $em->persist($cinema);
        $em->flush();

        $em = $this->getEntityManager();
        $movie = $em->find(get_class($movie), $movie->getId());
        $this->assertCount(1, $movie->getCinemas());
        $this->assertEquals('Paramount', $movie->getCinemas()->first()->getName());
    }

    function testWriteOnlyProperty()
    {
        $movie = new Entity\Movie;
        $movie->setTitle('Return of the king');

        $cinema = new Entity\Cinema;
        $cinema->setName('Paramount');
        $cinema->getRejectedMovies()->add($movie);

        $em = $this->getEntityManager();
        $em->persist($cinema);
        $em->flush();

        $em = $this->getEntityManager();
        $cinema = $em->find(get_class($cinema), $cinema->getId());
        $this->assertCount(0, $cinema->getRejectedMovies());
    }

    function testStoreDate()
    {
        $date = new \DateTime('-4 month');

        $movie = new Entity\Movie;
        $movie->setReleaseDate($date);

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $em = $this->getEntityManager();
        $movie = $em->find(get_class($movie), $movie->getId());

        $this->assertEquals($date, $movie->getReleaseDate());
    }

    function testAutostoreDates()
    {
        $date = new \DateTime;

        $aragorn = new Entity\Person;
        $aragorn->setFirstName('Viggo');
        $aragorn->setLastName('Mortensen');

        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');
        $entity->addActor($aragorn);

        $em = $this->getEntityManager();
        $em->setDateGenerator(function () {
            return 'foobar';
        });
        $em->persist($entity);
        $em->flush();

        $result = $em->createGremlinQuery('g.v(:movie).map')
            ->set('movie', $entity)
            ->getMap();

        $this->assertEquals('foobar', $result['creationDate']);
        $this->assertEquals('foobar', $result['updateDate']);

        $result = $em->createGremlinQuery('g.v(:movie).outE.map')
            ->set('movie', $entity)
            ->getMap();

        $this->assertEquals('foobar', $result['creationDate']);

        $em->setDateGenerator(function () {
            return 'baz';
        });

        $em->persist($entity);
        $em->flush();

        $result = $em->createGremlinQuery('g.v(:movie).map')
            ->set('movie', $entity)
            ->getMap();

        $this->assertEquals('foobar', $result['creationDate']);
        $this->assertEquals('baz', $result['updateDate']);
    }

    function testStoreArray()
    {
        $movie = new Entity\Movie;
        $movie->setTitle('The Lord of the Rings: The Fellowship of the Ring');

        $movie->addAlternateTitle('LOTR: The Fellowship of the Ring');
        $movie->addAlternateTitle('The Fellowship of the Ring');

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $movie = $em->findAny($movie->getId());

        $this->assertEquals(array('LOTR: The Fellowship of the Ring', 'The Fellowship of the Ring'), $movie->getAlternateTitles());
    }

    function testStoreStructure()
    {
        $movie = new Entity\Movie;
        $movie->setBlob(array('A' => 'B'));

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $movie = $em->findAny($movie->getId());

        $this->assertEquals(array('A' => 'B'), $movie->getBlob());
    }

    function testEntityProxyHandlesPropertiesCorrectly()
    {
        $movie = new Entity\Movie;

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $movie = $em->findAny($movie->getId());
        $movie->addTitle('Die Hard');

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $movie = $em->findAny($movie->getId());
        $this->assertEquals('Die Hard', $movie->getTitle());

        $movie->addTitle(' with a Vengance');
        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $movie = $em->findAny($movie->getId());
        $this->assertEquals('Die Hard with a Vengance', $movie->getTitle());
    }

    function testEntityRetrievedIsTheSame()
    {
        $movie = new Entity\Movie;

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $a = $em->find(get_class($movie), $movie->getId());
        $b = $em->find(get_class($movie), $movie->getId());

        $this->assertSame($a, $b);
    }

    function testClearingEntitiesFreesRelations()
    {
        $movie = new Entity\Movie;

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $a = $em->find(get_class($movie), $movie->getId());

        $em->clear();

        $b = $em->find(get_class($movie), $movie->getId());

        $this->assertNotSame($a, $b);
    }

    function testEntityRetrievedIsTheSameFromProxyAsWell()
    {
        $movie = new Entity\Movie;
        $actor = new Entity\Person;
        $movie->addActor($actor);

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $movie = $em->find(get_class($movie), $movie->getId());
        $a = $em->find(get_class($actor), $actor->getId());
        $b = $movie->getActors()->first();

        $this->assertSame($a, $b);
    }

    function testRelationRemovalFromList()
    {
        $movie = new Entity\Movie;
        $actor = new Entity\Person;
        $movie->addActor($actor);

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $em = $this->getEntityManager();

        $movie = $em->find(get_class($movie), $movie->getId());
        $actor = $movie->getActors()->first();
        $movie->removeActor($actor);

        $em->persist($movie);
        $em->flush();

        $em = $this->getEntityManager();
        $movie = $em->find(get_class($movie), $movie->getId());

        $this->assertCount(0, $movie->getActors());
    }

    function testRelationRemovalFromSingleElement()
    {
        $movie = new Entity\Movie;
        $actor = new Entity\Person;
        $actor->setFirstName('Bob');
        $movie->setMainActor($actor);

        $em = $this->getEntityManager();
        $em->persist($movie);
        $em->flush();

        $em = $this->getEntityManager();

        $movie = $em->find(get_class($movie), $movie->getId());
        $replacement = new Entity\Person;
        $replacement->setFirstName('Roger');
        $movie->setMainActor($replacement);

        $em->persist($movie);
        $em->flush();

        $em = $this->getEntityManager();
        $movie = $em->find(get_class($movie), $movie->getId());

        $this->assertEquals('Roger', $movie->getMainActor()->getFirstName());
    }

    function testSelfReferencingNodes()
    {
        $em = $this->getEntityManager();

        $a = new Entity\Person;
        $b = new Entity\Person;

        $a->addFriend($b);
        $b->addFriend($a);

        $em->persist($a);
        $em->flush();

        $loaded = $em->find(get_class($a), $a->getId());

        $this->assertCount(1, $loaded->getFriends());
    }

    function testRemove()
    {
        $em = $this->getEntityManager();

        $user1 = new Entity\User;
        $user1->setFirstName('Alex');

        $user2 = new Entity\User;
        $user2->setFirstName('Ivan');

        $em->persist($user1);
        $em->persist($user2);
        $em->flush();

        $id = $user1->getId();

        $em->remove($user1);
        $em->flush();

        $client = $em->getClient();

        $node2 = $client->getNode($user2->getId());

        $this->assertEquals(0, count($node2->getRelationships(array('friends'))));

        $this->assertEquals(null, $em->find(get_class($user1), $id));
    }

    function testRemoveDoesNotLeaveIndexBehind()
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('HireVoice\\Neo4j\\Tests\\Entity\\User');

        $user = new Entity\User;
        $user->setFirstName('Alex');

        $em->persist($user);
        $em->flush();

        $lookupValue = $user->getUniqueId();

        $this->assertCount(1, $repo->findByUniqueId($lookupValue));

        $em->remove($user);
        $em->flush();

        $this->assertCount(0, $repo->findByUniqueId($lookupValue));
    }

    /**
     * @group neo4j-v2
     */
    function testStoreLabeledEntity()
    {
        $entity = new Entity\City;
        $entity->setName('Montpellier');

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $em = $this->getEntityManager();
        $city = $em->createCypherQuery()
                ->startWithNode('a', $entity)
                ->end('labels(a)')
                ->getOne();

        $this->assertEquals(2, $city->count());
        $this->assertEquals("Location", $city->offsetGet(0));
        $this->assertEquals("City", $city->offsetGet(1));
    }

    /**
     * Check the fix of issue #54.
     */
    function testRemoveEntity()
    {
        $entity = new Entity\Movie;
        $entity->setTitle('Jules et Jim');

        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();

        $em->remove($entity);
        $em->flush();

        $entity2 = new Entity\Movie;
        $entity2->setTitle('Rois et reine');
        $em->persist($entity2);
        $em->flush();

        // one only checks that a second flush
        // after a remove does not throw an exception
        // if not the test is ok.

        $this->assertTrue(true);
    }

    function testRelationIntegrityOnMultipleFlush()
    {
        $em = $this->getEntityManager();

        $book = new Entity\Book();
        $saga = new Entity\Saga();
        $saga->setTitle('A tale of two cities');
        $book->setName('A tale of two cities');
        $book->addSaga($saga);
        $saga->setBook($book);
        $em->persist($book);
        $em->persist($saga);
        $em->flush();

        $query = $em->createCypherQuery()
            ->startWithNode("book", array($book))
            ->match("(saga)<-[:BasedOn]-book")
            ->end("saga");

        $testSaga = $query->getOne();
        $this->assertEquals($testSaga->getId(), $saga->getId());

        $em->persist($saga);
        $em->flush();

        $testSaga = $query->getOne();
        $this->assertNotNull($testSaga);
        $this->assertEquals($testSaga->getId(), $saga->getId());
    }
}

/**
 * @HireVoice\Neo4j\Annotation\Entity
 */
class FailedEntity
{
    /**
     * @HireVoice\Neo4j\Annotation\Property
     */
    private $name;
}
