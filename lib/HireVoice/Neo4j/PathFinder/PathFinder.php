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

use Everyman\Neo4j\Relationship;
use HireVoice\Neo4j\EntityManager;
use Everyman\Neo4j\PathFinder as PathFinderImpl;

/**
 * Path Finder implements path finding functions
 *
 * @author Alex Belyaev <lex@alexbelyaev.com>
 */
class PathFinder
{
    protected $entityManager;

    protected $startNode;

    protected $endNode;

    protected $relationship;

    protected $maxDepth = null;

    protected $algorithm = null;

    public static function validateAlgorithm($name)
    {
        $algorithms = array(PathFinderImpl::AlgoShortest, PathFinderImpl::AlgoAll, PathFinderImpl::AlgoAllSimple, PathFinderImpl::AlgoDijkstra);

        if (! in_array($name, $algorithms)) {
            throw new Exception(sprintf("Invalid path finding algorithm \"%s\"", $name));

        }
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __clone()
    {
        return array(
            'entityManager',
            'maxDepth',
            'algorithm',
        );
    }

    public function setMaxDepth($depth)
    {
        $this->maxDepth = (int) $depth;

        return $this;
    }

    public function setAlgorithm($algorithm)
    {
        self::validateAlgorithm($algorithm);
        $this->algorithm = $algorithm;

        return $this;
    }

    public function setRelationship($relationship)
    {
        $this->relationship = $relationship;

        return $this;
    }

    public function findPaths($a, $b)
    {
        $this->setStartEntity($a);
        $this->setEndEntity($b);

        $paths = $this->preparePaths()->getPaths();

        $pathObjects = array();
        foreach ($paths as $path){
            $pathObjects[] = new Path($path, $this->entityManager);
        }

        return $pathObjects;
    }

    public function findSinglePath($a, $b)
    {
        $this->setStartEntity($a);
        $this->setEndEntity($b);

        $path = $this->preparePaths()->getSinglePath();

        return new Path($path, $this->entityManager);
    }

    protected function preparePaths()
    {
        if (null === $this->relationship){
            $paths = $this->startNode->findPathsTo($this->endNode);
        } else {
            $paths = $this->startNode->findPathsTo($this->endNode, $this->relationship);
        }

        if ($this->maxDepth !== null) $paths->setMaxDepth($this->maxDepth);
        if ($this->algorithm !== null) $paths->setAlgorithm($this->algorithm);

        return $paths;
    }

    protected function setStartEntity($entity)
    {
        $this->startNode = $this->entityManager->getClient()->getNode($entity->getId());
    }

    protected function setEndEntity($entity)
    {
        $this->endNode = $this->entityManager->getClient()->getNode($entity->getId());
    }
}

