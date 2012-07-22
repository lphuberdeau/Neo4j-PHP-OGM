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
use HireVoice\Neo4j\Meta\Entity as EntityMeta;
use Hirevoice\Neo4j\Repository;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{

    private $client;
    private $metaRepository;
    private $transport;

    public function __construct()
    {
        $client = new \Everyman\Neo4j\Client(new \Everyman\Neo4j\Transport($GLOBALS['host'], $GLOBALS['port']));
        $this->client = $client;
        $this->metaRepository = new MetaRepository();
    }

    private function getEntityManager()
    {
        return new EntityManager($this->client, $this->metaRepository);
    }

    private function getRepository()
    {
        $em = $this->getEntityManager();
        $entityMeta = new EntityMeta('HireVoice\\Neo4j\\Tests\\Entity\\Movie');

        $repo = new Repository($em, $entityMeta);

        return $repo;
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
        $this->createNodes();

        $repo = $this->getRepository();

        $movie = $repo->findOneBy(array('title' => 'Matrix'));

        $this->assertEquals('Matrix', $movie->getTitle());
    }

    public function testQueryWithSpacesInSearchTermAndReturningNode()
    {
        $repo = $this->getRepository();

        $movie = $repo->findOneBy(array('title' => 'The Matrix'));

        $this->assertEquals('The Matrix', $movie->getTitle());
    }

    public function createNodes()
    {
        $em = $this->getEntityManager();

        $entity = new Entity\Movie;
        $entity->setTitle('Return of the king');
        $em->persist($entity);

        $matrix = new Entity\Movie;
        $matrix->setTitle('Matrix');
        $em->persist($matrix);

        $matrix2 = new Entity\Movie;
        $matrix2->setTitle('The Matrix');
        $em->persist($matrix2);

        $em->flush();

    }
}