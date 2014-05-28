<?php

namespace HireVoice\Neo4j\Event;

use Everyman\Neo4j\Relationship;
use HireVoice\Neo4j\Tests\Entity\Cinema;
use HireVoice\Neo4j\Tests\Entity\Movie;

class StmtEventTest extends \PHPUnit_Framework_TestCase
{

    public function eventNameProvider()
    {
        return array(
            array('preStmtExecute'),
            array('postStmtExecute')
        );
    }

    /**
     * @dataProvider eventNameProvider
     * @param $eventName
     */
    public function testGetSet($eventName)
    {
        $className = 'HireVoice\\Neo4j\\Event\\' . ucfirst($eventName);

        $query = $this->getMockBuilder('Everyman\Neo4j\Query')
            ->disableAutoload()
            ->disableOriginalConstructor()
            ->getMock();

        $parameters = array('foo', 'bar');

        $time = new \DateTime();

        $event = new $className($query, $parameters, $time);

        $this->assertSame($event->getQuery(), $query);
        $this->assertSame($event->getParameters(), $parameters);
        $this->assertSame($event->getTime(), $time);

        $parameters = array('bar', 'baz');
        $time = new \DateTime();

        $event->setQuery($query);
        $event->setParameters($parameters);
        $event->setTime($time);

        $this->assertSame($event->getQuery(), $query);
        $this->assertSame($event->getParameters(), $parameters);
        $this->assertSame($event->getTime(), $time);

        $this->assertSame($event->getEventName(), $eventName);
    }
}
 