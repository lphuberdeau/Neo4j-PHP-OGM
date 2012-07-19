<?php

namespace HireVoice\Neo4j\Tests;

class SerializationTest extends TestCase
{
	function testSerializeProxy()
	{
		$em = $this->getEntityManager();

		$movie = new Entity\Movie;
		$em->persist($movie);
		$em->flush();

		$baseClass = get_class($movie);

		$movie = $em->reload($movie);

		$this->assertInstanceOf($baseClass, unserialize(serialize($movie)));
	}

	/**
	 * @expectedException HireVoice\Neo4j\Exception
	 */
	function testUnserializedNoLongerLoads()
	{
		$em = $this->getEntityManager();

		$movie = new Entity\Movie;
		$em->persist($movie);
		$em->flush();

		$movie = $em->reload($movie);
		$movie = unserialize(serialize($movie));

		$movie->getActors();
	}

	function testUnserializeLeavesPropertiesAvailable()
	{
		$em = $this->getEntityManager();

		$movie = new Entity\Movie;
		$movie->setTitle('Test');
		$em->persist($movie);
		$em->flush();

		$movie = $em->reload($movie);
		$movie = unserialize(serialize($movie));

		$this->assertEquals("Test", $movie->getTitle());
	}
}

