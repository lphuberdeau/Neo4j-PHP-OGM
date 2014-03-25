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

class InheritanceTest extends TestCase
{
    public function testSubclass()
    {
        $em = $this->getEntityManager();

        $movie = new Entity\Movie;
        $movie->setTitle('Army of Darkness');
        
        $cinema = new Entity\Cinema;
        $cinema->setName('Nuovo Cinema paradiso');
        $cinema->addPresentedMovie($movie);

        $multiplex = new Entity\Multiplex();
        $multiplex->setName('Multisala Portanova');
        $multiplex->addPresentedMovie($movie);
        $multiplex->setRooms(5);
        
        $em->persist($multiplex);
        $em->flush();

        $id = $multiplex->getId();
        $mp = $em->getRepository('HireVoice\Neo4j\Tests\Entity\Multiplex')->findOneBy(array('id' => $id));

        $ref = new \ReflectionClass(get_class($mp));
        $aaa = $ref->isSubclassOf(get_class($cinema));
         
        $this->assertTrue($ref->isSubclassOf(get_class($cinema)));
    }
    
    public function testParentAttribExists()
    {
        $em = $this->getEntityManager();

        $movie = new Entity\Movie;
        $movie->setTitle('Army of Darkness');
        
        $cinema = new Entity\Cinema;
        $cinema->setName('Nuovo Cinema paradiso');
        $cinema->addPresentedMovie($movie);

        $multiplex = new Entity\Multiplex();
        $multiplex->setName('Multisala Portanova');
        $multiplex->addPresentedMovie($movie);
        $multiplex->setRooms(5);
        
        $em->persist($multiplex);
        $em->flush();

        $id = $multiplex->getId();
        $mp = $em->getRepository('HireVoice\Neo4j\Tests\Entity\Multiplex')->findOneBy(array('id' => $id));

        $ref = new \ReflectionClass(get_class($mp));         
        $this->assertInstanceOf('ReflectionProperty', $ref->getProperty('name'));
    }
    
    public function testChildAttribValue()
    {
        $em = $this->getEntityManager();

        $movie = new Entity\Movie;
        $movie->setTitle('Army of Darkness');
        
        $cinema = new Entity\Cinema;
        $cinema->setName('Nuovo Cinema paradiso');
        $cinema->addPresentedMovie($movie);

        $multiplex = new Entity\Multiplex();
        $multiplex->setName('Multisala Portanova');
        $multiplex->addPresentedMovie($movie);
        $multiplex->setRooms(5);
        
        $em->persist($multiplex);
        $em->flush();

        $id = $multiplex->getId();
        $mp = $em->getRepository('HireVoice\Neo4j\Tests\Entity\Multiplex')->findOneBy(array('id' => $id));

        $this->assertEquals(5, $mp->getRooms());
    }
}
