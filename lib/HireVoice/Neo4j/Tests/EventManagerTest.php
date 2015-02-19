<?php

namespace HireVoice\Neo4j\Tests;

use Doctrine\Common\EventManager;
use HireVoice\Neo4j\Event\Event;
use HireVoice\Neo4j\Event as Events;

class EventManagerTest extends TestCase
{
    /**
     * @var \HireVoice\Neo4j\EntityManager
     */
    private $em;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
    }

    public function testPersist()
    {
        $movie = new Entity\Movie;
        $movie->setTitle('Terminator');

        $eventManager = $this->getEventManagerWithListenerExpectations(
            array(
                'prePersist' => 1,
                'postPersist' => 1
            )
        );

        $this->em->setEventManager($eventManager);
        $this->em->persist($movie);
        $this->em->flush();
    }

    public function testRelationCreate()
    {
        $eventManager = $this->getEventManagerWithListenerExpectations(
            array(
                'prePersist' => 2,
                'postPersist' => 2,
                'preRelationCreate' => 1,
                'postRelationCreate' => 1
            )
        );

        $movie = new Entity\Movie;
        $movie->setTitle('Terminator');
        $actor = new Entity\Person;
        $actor->setFirstName('Arnold');
        $movie->addActor($actor);

        $this->em->setEventManager($eventManager);
        $this->em->persist($movie);
        $this->em->flush();
    }

    public function testStmtCreateWithCypherQuery()
    {
        $queryObj = null;
        $timeElapsed = null;
        $paramsArray = null;

        $eventManager = $this->getEventManagerWithListenerExpectations(
            array(
                'prePersist' => 2,
                'postPersist' => 2,
                'preRelationCreate' => 1,
                'postRelationCreate' => 1,
                'preStmtExecute' => 1,
                'postStmtExecute' => 1
            )
        );

        $movie = new Entity\Movie;
        $movie->setTitle('Terminator');
        $actor = new Entity\Person;
        $actor->setFirstName('Arnold');
        $movie->addActor($actor);

        $this->em->setEventManager($eventManager);
        $this->em->persist($movie);
        $this->em->flush();

        $this->em->createCypherQuery()
            ->start('movie = node(:movie)')
            ->end('movie')
            ->set('movie', $movie)
            ->getOne();
    }

    public function testStmtCreateWithgremlinQuery()
    {
        $queryObj = null;
        $timeElapsed = null;
        $paramsArray = null;

        $eventManager = $this->getEventManagerWithListenerExpectations(
            array(
                'prePersist' => 2,
                'postPersist' => 2,
                'preRelationCreate' => 1,
                'postRelationCreate' => 1,
                'preStmtExecute' => 1,
                'postStmtExecute' => 1
            )
        );

        $movie = new Entity\Movie;
        $movie->setTitle('Terminator');
        $actor = new Entity\Person;
        $actor->setFirstName('Arnold');
        $movie->addActor($actor);

        $this->em->setEventManager($eventManager);
        $this->em->persist($movie);
        $this->em->flush();

        $this->em->createGremlinQuery("g.v(:movie).out('actor')")
            ->set('movie', $movie)
            ->getList();
    }

    public function testRemove()
    {
        $movie = new Entity\Movie;
        $movie->setTitle('Terminator');

        $eventManager = $this->getEventManagerWithListenerExpectations(
            array(
                'prePersist' => 1,
                'postPersist' => 1,
                'preRemove' => 1,
                'postRemove' => 1
            )
        );

        $this->em->setEventManager($eventManager);
        $this->em->persist($movie);
        $this->em->flush();

        $this->em->remove($movie);
        $this->em->flush();
    }

    public function testRelationRemove()
    {
        $eventManager = $this->getEventManagerWithListenerExpectations(
            array(
                'prePersist' => 3,
                'postPersist' => 3,
                'preRelationCreate' => 1,
                'postRelationCreate' => 1,
                'preRelationRemove' => 1,
                'postRelationRemove' => 1
            )
        );

        $movie = new Entity\Movie;
        $movie->setTitle('Terminator');
        $actor = new Entity\Person;
        $actor->setFirstName('Arnold');
        $movie->addActor($actor);

        $this->em->setEventManager($eventManager);
        $this->em->persist($movie);
        $this->em->flush();

        $movie = $this->em->find(get_class($movie), $movie->getId());
        $actor = $movie->getActors()->first();
        $movie->removeActor($actor);

        $this->em->persist($movie);
        $this->em->flush();
    }

    /**
     * Takes an array of [string:$eventName => int:$times] and assembles mock expectations in the listener
     *
     * @param array $expectedEvents
     * @return EventManager
     */
    private function getEventManagerWithListenerExpectations(array $expectedEvents)
    {
        $eventManager = new EventManager();

        $listener = $this->getMock('HireVoice\Neo4j\Tests\Stubs\EventListenerStub');

        foreach ($expectedEvents as $eventName => $times) {
            $listener->expects($this->exactly($times))
                ->method($eventName)
                ->will($this->returnValue(true));

            $eventManager->addEventListener(array($eventName), $listener);
        }

        return $eventManager;
    }
}
 