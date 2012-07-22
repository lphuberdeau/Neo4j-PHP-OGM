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
        $client = new \Everyman\Neo4j\Client(new \Everyman\Neo4j\Transport('localhost', 7474));
        $this->client = $client;
        $this->metaRepository = new MetaRepository();
    }

    private function getEntityManager()
    {
        return new EntityManager($this->client, $this->metaRepository);
    }

    public function testCreateLuceneQueryWithWordWithoutSpaces()
    {
        $em = $this->getEntityManager();
        $entityMeta = new EntityMeta('HireVoice\Neo4j\Tests\Entity\Person');

        $repo = new Repository($em, $entityMeta);
        $criteria = array('fullname' => 'chris', 'lastname' => 'lord');
        $query = $repo->createQuery($criteria);
        $expectedQuery = 'fullname:chris AND lastname:lord';
        $this->assertEquals($expectedQuery, $query);
    }

        public function testCreateLuceneQueryWithWordWithSpaces()
    {
        $em = $this->getEntityManager();
        $entityMeta = new EntityMeta('HireVoice\Neo4j\Tests\Entity\Person');

        $repo = new Repository($em, $entityMeta);
        $criteria = array('fullname' => 'angus young', 'lastname' => 'lord nelson');
        $query = $repo->createQuery($criteria);
        $expectedQuery = 'fullname:"angus young" AND lastname:"lord nelson"';
        $this->assertEquals($expectedQuery, $query);
    }
}