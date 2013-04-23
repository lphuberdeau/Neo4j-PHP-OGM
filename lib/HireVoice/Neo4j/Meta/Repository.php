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

namespace HireVoice\Neo4j\Meta;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationReader;
use HireVoice\Neo4j\Exception;

class Repository
{
    private $reader;
    private $metas = array();

    function __construct($annotationReader = null)
    {
        if ($annotationReader instanceof Reader) {
            $this->reader = $annotationReader;
        } else {
            $this->reader = new AnnotationReader;
        }
    }

    function fromClass($className)
    {
        if (! isset($this->metas[$className])) {
            $this->metas[$className] = $this->findMeta($className, $this);
        }

        return $this->metas[$className];
    }

	private function findMeta($className)
    {
        $class = new \ReflectionClass($className);

        if ($class->implementsInterface('HireVoice\\Neo4j\\Proxy\\Entity')) {
            $class = $class->getParentClass();
        }

        if ($entity = $this->reader->getClassAnnotation($class, 'HireVoice\\Neo4j\\Annotation\\Entity')) {
			return $this->handleEntity($entity, $class);
		} elseif ($entity = $this->reader->getClassAnnotation($class, 'HireVoice\\Neo4j\\Annotation\\Relation')) {
			return $this->handleRelation($entity, $class);
		} else {
            $className = $class->getName();
			throw new Exception("Class $className is not declared as an entity or relation.");
        }
	}

	private function handleEntity($entity, $class)
	{
        $object = new Entity($class->getName());

		$object->setRepositoryClass($entity->repositoryClass);
        $object->loadProperties($this->reader, $class->getProperties());
        $object->loadRelations($this->reader, $class->getProperties());
        $object->validate();

        return $object;
    }

	private function handleRelation($entity, $class)
	{
        $object = new Relation($class->getName());

        $object->loadProperties($this->reader, $class->getProperties());
		$object->loadEndPoints($this->reader, $class->getProperties());
        $object->validate();

        return $object;
    }

}

