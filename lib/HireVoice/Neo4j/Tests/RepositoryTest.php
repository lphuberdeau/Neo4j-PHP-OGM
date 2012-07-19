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

use HireVoice\Neo4j\EntityManager;
use HireVoice\Neo4j\Meta\Repository as MetaRepository;
use HireVoice\Neo4j\Proxy\Factory as ProxyFactory;
use Doctrine\Common\Collections\ArrayCollection;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    private function getEntityManager()
    {
        $client = new \Everyman\Neo4j\Client(new \Everyman\Neo4j\Transport($GLOBALS['host'], $GLOBALS['port']));
        $em = new EntityManager($client, new MetaRepository());
        $em->setProxyFactory(new ProxyFactory('/tmp', true));
        return $em;
    }

    private function getRepository()
    {
        $em = $this->getEntityManager();
        return $em->getRepository('HireVoice\\Neo4j\\Tests\\Entity\\Movie');
    }

    public function testCreateLuceneQueryWithWordWithoutSpaces()
    {
        $repo = $this->getRepository();
        $criteria = array('fullname' => 'chris', 'lastname' => 'lord');
        $query = $repo->createQuery($criteria);
        $expectedQuery = 'fullname:chris AND lastname:lord';
        $this->assertEquals($expectedQuery, $query);
    }

        public function testCreateLuceneQueryWithWordWithSpaces()
    {
        $repo = $this->getRepository();
        $criteria = array('fullname' => 'angus young', 'lastname' => 'lord nelson');
        $query = $repo->createQuery($criteria);
        $expectedQuery = 'fullname:"angus young" AND lastname:"lord nelson"';
        $this->assertEquals($expectedQuery, $query);
    }

    public function testQueryWithOneTermWithoutSpaces()
    {
        $repo = $this->getRepository();
        $criteria = array('fullname' => 'angus');
        $query = $repo->createQuery($criteria);
        $expectedQuery = 'fullname:angus';
        $this->assertEquals($expectedQuery, $query);
    }

    public function testQueryWithOneTermWithSpaces()
    {
        $repo = $this->getRepository();
        $criteria = array('fullname' => 'angus young');
        $query = $repo->createQuery($criteria);
        $expectedQuery = 'fullname:"angus young"';
        $this->assertEquals($expectedQuery, $query);
    }

    public function testQueryAndReturningNode()
    {
        $uid = $this->createNodes();

        $repo = $this->getRepository();

        $movie = $repo->findOneBy(array('title' => $uid));

        $this->assertEquals($uid, $movie->getTitle());
    }

    public function testQueryWithSpacesInSearchTermAndReturningNode()
    {
        $uid = $this->createNodes();

        $repo = $this->getRepository();

        $movie = $repo->findOneBy(array('title' => 'The '.$uid));

        $this->assertEquals('The '.$uid, $movie->getTitle());
    }

    public function testQueryForMultipleReturningNodes()
    {
        $uid = $this->createNodes();

        $repo = $this->getRepository();

        $movies = $repo->findBy(array('title' => $uid));

        $this->assertTrue($movies instanceof ArrayCollection);

        $this->assertTrue(count($movies) == 1);
    }

    public function testQueryWithMatchMultipleAndReturnsMultiple()
    {
        $uid = $this->createNodes();

        $repo = $this->getRepository();

        $movies = $repo->findBy(array('title' => '*'.$uid));

        $this->assertTrue($movies instanceof ArrayCollection);

        $this->assertTrue(count($movies) == 2);
    }

    public function createNodes()
    {
        $em = $this->getEntityManager();

        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');
        $em->persist($entity);

        $uid = uniqid();

        $matrix = new Entity\Movie;
        $matrix->setTitle($uid);
        $em->persist($matrix);

        $matrix2 = new Entity\Movie;
        $matrix2->setTitle('The '.$uid);
        $em->persist($matrix2);

        $em->flush();

        return $uid;
    }
}
