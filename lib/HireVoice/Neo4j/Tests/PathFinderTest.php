<?php
namespace HireVoice\Neo4j\Tests;

use Everyman\Neo4j\PathFinder\PathFinder;

class PathFinderTest extends TestCase
{
    public function testFindPaths()
    {
        $user1 = new Entity\User;
        $user2 = new Entity\User;
        $user3 = new Entity\User;

        $user1->setFirstName('Alex');
        $user2->setFirstName('Sergey');
        $user3->setFirstName('Anatoly');

        $user1->addFriend($user2);
        $user3->addFriend($user2);

        $em = $this->getEntityManager();

        $em->persist($user1);
        $em->persist($user2);
        $em->persist($user3);

        $em->flush();

        $pathFinder = $em->getPathFinder();
        $paths = $pathFinder->findPaths($user1, $user3);

        $this->assertEquals(1, count($paths));

        foreach ($paths as $path){
            $entities = $path->getEntities();
        }

        $this->assertEquals(3, count($entities));

        $this->assertEquals($user1->getFirstName(), $entities[0]->getFirstName());
        $this->assertEquals($user2->getFirstName(), $entities[1]->getFirstName());
        $this->assertEquals($user3->getFirstName(), $entities[2]->getFirstName());
    }
}
