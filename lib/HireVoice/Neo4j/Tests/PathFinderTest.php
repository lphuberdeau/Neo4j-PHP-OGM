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

        $this->assertCount(1, $paths);

        foreach ($paths as $path){
            $entities = $path->getEntities();
        }

        $this->assertCount(3, $entities);

        $this->assertEquals($user1->getFirstName(), $entities[0]->getFirstName());
        $this->assertEquals($user2->getFirstName(), $entities[1]->getFirstName());
        $this->assertEquals($user3->getFirstName(), $entities[2]->getFirstName());
    }

    public function testFindMissingPath()
    {
        $user1 = new Entity\User;
        $user2 = new Entity\User;
        $user3 = new Entity\User;

        $user1->setFirstName('Alex');
        $user2->setFirstName('Sergey');
        $user3->setFirstName('Anatoly');

        $user1->addFriend($user2);

        $em = $this->getEntityManager();

        $em->persist($user1);
        $em->persist($user2);
        $em->persist($user3);

        $em->flush();

        $pathFinder = $em->getPathFinder();
        $paths = $pathFinder->findPaths($user1, $user3);

        $this->assertCount(0, $paths);
    }

    public function testFindSinglePath()
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
        $path = $pathFinder->findSinglePath($user1, $user3);

        $firstNames = array_map(function ($entity) {
            return $entity->getFirstName();
        }, $path->getEntities()->toArray());

        $this->assertEquals(array('Alex', 'Sergey', 'Anatoly'), $firstNames);
    }

    public function testFindSinglePathWithNoSolution()
    {
        $user1 = new Entity\User;
        $user2 = new Entity\User;
        $user3 = new Entity\User;

        $user1->setFirstName('Alex');
        $user2->setFirstName('Sergey');
        $user3->setFirstName('Anatoly');

        $user1->addFriend($user2);

        $em = $this->getEntityManager();

        $em->persist($user1);
        $em->persist($user2);
        $em->persist($user3);

        $em->flush();

        $pathFinder = $em->getPathFinder();
        $path = $pathFinder->findSinglePath($user1, $user3);

        $this->assertNull($path);
    }

    /**
     * @dataProvider algorithms
     */
    public function testFindMultiplePaths($algorithm, $count)
    {
        $user1 = new Entity\User;
        $user2 = new Entity\User;
        $user3 = new Entity\User;
        $user4 = new Entity\User;

        $user1->setFirstName('Alex');
        $user2->setFirstName('Sergey');
        $user3->setFirstName('Anatoly');
        $user4->setFirstName('Vladimir');

        /* 1 -- 2
         *  \  /  \
         *    3 -- 4
         */
        $user1->addFriend($user2);
        $user1->addFriend($user3);
        $user4->addFriend($user2);
        $user4->addFriend($user3);
        $user2->addFriend($user3);

        $em = $this->getEntityManager();

        $em->persist($user1);
        $em->persist($user2);
        $em->persist($user3);
        $em->persist($user4);

        $em->flush();

        $pathFinder = $em->getPathFinder();
        $pathFinder->setAlgorithm($algorithm);
        $paths = $pathFinder->findPaths($user1, $user4);

        $this->assertCount($count, $paths);

        foreach ($paths as $path){
            $firstNames = array_map(function ($entity) {
                return $entity->getFirstName();
            }, $path->getEntities()->toArray());

            $this->assertContains($firstNames, array(
                array('Alex', 'Sergey', 'Anatoly', 'Vladimir'),
                array('Alex', 'Anatoly', 'Sergey', 'Vladimir'),
                array('Alex', 'Sergey', 'Vladimir'),
                array('Alex', 'Anatoly', 'Vladimir'),
            ));
        }
    }

    public function testPathObtainedFromCypher()
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

        $paths = $em->createCypherQuery()
            ->startWithNode('a', $user1)
            ->startWithNode('b', $user3)
            ->match('path = a -[*0..3]- b')
            ->end('path')
            ->getList();

        $entities = $paths[0]->getEntities();
        $this->assertEquals($user1->getFirstName(), $entities[0]->getFirstName());
        $this->assertEquals($user2->getFirstName(), $entities[1]->getFirstName());
        $this->assertEquals($user3->getFirstName(), $entities[2]->getFirstName());
    }

    function algorithms()
    {
        return array(
            array('shortestPath', 2),
            array('allPaths', 4),
            array('allSimplePaths', 4),
        );
    }
}
