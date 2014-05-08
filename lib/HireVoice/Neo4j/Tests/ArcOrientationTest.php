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

class ArcOrientationTest extends TestCase
{
    public function testToDirection()
    {
        $em = $this->getEntityManager();
        $saga = new Entity\Saga();
        $saga->setTitle('The Hobbit');
        $em->persist($saga);
        $book = new Entity\Book();
        $book->setName('The Hobbit');
        $saga->setBook($book);
        $em->persist($book);
        $em->flush();
        $result = $em->createCypherQuery()
					 ->start('saga = node(:saga)')
					 ->match('saga<--(book)')
					 ->end('book')
					 ->set('saga', $saga)
					 ->getList();
					
        $this->assertInstanceOf('\HireVoice\Neo4j\Tests\Entity\Book', $result->get(0)); 
    }
    
    public function testFromDirection()
    {
        $em = $this->getEntityManager();

        $saga = new Entity\Saga();
        $saga->setTitle('The Hobbit');
        $em->persist($saga);
        $aragorn = new Entity\Person;
        $aragorn->setFirstName('Viggo');
        $aragorn->setLastName('Mortensen');
        $saga->setMainActor($aragorn);
        $em->persist($aragorn);
        $em->flush();

        $result = $em->createCypherQuery()
					 ->start('saga = node(:saga)')
					 ->match('saga-[:mainActor]->(mainActor)')
					 ->where('mainActor.firstName = :name')
					 ->end('mainActor')
					 ->set('saga', $saga)
					 ->set('name', 'Viggo')
					 ->getList();
		
        $this->assertInstanceOf('\HireVoice\Neo4j\Tests\Entity\Person', $result->get(0)); 

    }
}
