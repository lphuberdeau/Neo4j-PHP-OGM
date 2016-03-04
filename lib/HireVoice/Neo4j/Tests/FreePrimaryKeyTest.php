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
use Doctrine\Common\Collections\ArrayCollection;

class FreePrimaryKeyTest extends TestCase
{
    public $pkVal;
    
    public function setupDb(){
        $em = $this->getEntityManager();

        $pasta= new Entity\Pasta();
        $pasta->setName('Good pasta with tomato and olives');
        
        $olive1 = new Entity\Olive();
        $olive1->setName('black olive');
        $olive2 = new Entity\Olive();
        $olive2->setName('green olive');
        
        $tomato = new Entity\Tomato();
        
        $pasta->addOlive($olive1);
        $pasta->addOlive($olive2);
        
        $pasta->setTomato($tomato);
        
        $em->persist($pasta);
        $em->flush();
        $this->pkVal = $pasta->getStrangePrimaryKey(); 
    }
    
    public function testInsert(){
        $em = $this->getEntityManager();
        $this->setupDb();
        
        $loaded = $em->find('HireVoice\Neo4j\Tests\Entity\Pasta', $this->pkVal);
        
        $this->assertGreaterThanOrEqual(1, count($loaded));
        $this->assertGreaterThanOrEqual(2, count($loaded->getOlives()));
        $this->assertGreaterThanOrEqual(1, count($loaded->getTomato()));
    }
    
    public function testEntityReload(){
        $em = $this->getEntityManager();
        $this->setupDb();
        
        $loaded = $em->find('HireVoice\Neo4j\Tests\Entity\Pasta', $this->pkVal);
        
        $entity = $loaded->getEntity();
        
        $reloadEntity = $em->reload($entity);
        
        $this->assertGreaterThanOrEqual(1, count($loaded));
        $this->assertGreaterThanOrEqual(2, count($loaded->getOlives()));
        $this->assertGreaterThanOrEqual(1, count($loaded->getTomato()));
    }
    
    
    public function testUpdate(){
        $em = $this->getEntityManager();
        $this->setupDb();
        
        $loaded = $em->find('HireVoice\Neo4j\Tests\Entity\Pasta', $this->pkVal);
        
        $loaded->setName('This pasta is terrible');
        
        $olive1 = new Entity\Olive();
        $olive1->setName('terrible olive');
        
        $loaded->addOlive($olive1);
        
        $em->persist($loaded);
        $em->flush();
        
        unset($loaded);
        $em->clear();
        
        $loaded = $em->find('HireVoice\Neo4j\Tests\Entity\Pasta', $this->pkVal);
        
        $this->assertGreaterThanOrEqual(1, count($loaded));
        $this->assertEquals('This pasta is terrible', $loaded->getName());
        $this->assertGreaterThanOrEqual(3, count($loaded->getOlives()));

        $update_found = FALSE;
        foreach($loaded->getOlives() as $olive){
            if($olive->getName() == 'terrible olive'){
                $update_found = TRUE;
            }
        }
        $this->assertTrue( $update_found );
        $this->assertGreaterThanOrEqual(1, count($loaded->getTomato()));
    }
    
    function testRemove()
    {
        $this->setupDb();
        $em = $this->getEntityManager();
        $loaded = $em->find('HireVoice\Neo4j\Tests\Entity\Pasta', $this->pkVal);
        $em->remove($loaded);
        $em->flush();
        $this->assertEquals(null, $em->find('HireVoice\Neo4j\Tests\Entity\Pasta', $this->pkVal));
    }
    
    function testFindAny(){
        $this->setupDb();
        $em = $this->getEntityManager();
        $loaded = $em->findAny($this->pkVal);
        $this->assertEquals('Good pasta with tomato and olives', $loaded->getName());
    }
}
