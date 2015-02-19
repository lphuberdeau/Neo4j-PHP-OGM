<?php

namespace HireVoice\Neo4j\Event;

use Everyman\Neo4j\Relationship;
use HireVoice\Neo4j\Tests\Entity\Cinema;
use HireVoice\Neo4j\Tests\Entity\Movie;

class RelationEventTest extends \PHPUnit_Framework_TestCase
{

    public function eventNameProvider()
    {
        return array(
            array('preRelationCreate'),
            array('postRelationCreate'),
            array('preRelationRemove'),
            array('postRelationRemove'),
        );
    }

    /**
     * @dataProvider eventNameProvider
     * @param $eventName
     */
    public function testGetSet($eventName)
    {
        $className = 'HireVoice\\Neo4j\\Event\\' . ucfirst($eventName);

        $movie = new Movie();
        $movie->setTitle('Terminator');

        $cinema = new Cinema();
        $cinema->setName('Cinedome');
        $cinema->addPresentedMovie($movie);

        $client = $this->getMockBuilder('Everyman\Neo4j\Client')
            ->disableAutoload()
            ->disableOriginalConstructor()
            ->getMock();

        $relationship = new Relationship($client);

        $event = new $className($cinema, $movie, 'presents', $relationship);

        $this->assertSame($event->getFrom(), $cinema);
        $this->assertSame($event->getTo(), $movie);
        $this->assertSame($event->getName(), 'presents');
        $this->assertSame($event->getRelationship(), $relationship);

        $event->setFrom($movie);
        $event->setTo($cinema);
        $event->setName('presented-by');

        $this->assertSame($event->getFrom(), $movie);
        $this->assertSame($event->getTo(), $cinema);
        $this->assertSame($event->getName(), 'presented-by');
        $this->assertSame($event->getRelationship(), $relationship);

        $this->assertSame($event->getEventName(), $eventName);
    }
}
 