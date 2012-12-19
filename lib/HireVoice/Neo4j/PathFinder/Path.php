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

namespace HireVoice\Neo4j\PathFinder;

use HireVoice\Neo4j\EntityManager;
use Everyman\Neo4j\Path as RawPath;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class for keeping entities along the path
 *
 * @author Alex Belyaev <lex@alexbelyaev.com>
 */
class Path
{
    protected $entities;

    protected $entityManager;

    public function __construct(RawPath $path, EntityManager $entityManager)
    {
        $this->entities = new ArrayCollection();

        foreach ($path as $node){
            $this->entities->add($entityManager->load($node));
        }
    }

    public function getEntities()
    {
        return $this->entities;
    }
}
