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
use Doctrine\Common\Annotations\Reader as AnnotationReader;
use HireVoice\Neo4j\Exception;

class Entity
{
    private $repositoryClass = 'HireVoice\\Neo4j\\Repository';
    private $labels = array();
    private $className;
    private $primaryKey;
    private $properties = array();
    private $indexedProperties = array();
    private $manyToManyRelations = array();
    private $manyToOneRelations = array();

    public static function fromClass(AnnotationReader $reader, $className)
    {
        $class = new \ReflectionClass($className);

        if ($class->implementsInterface('HireVoice\\Neo4j\\Proxy\\Entity')) {
            $class = $class->getParentClass();
            $className = $class->getName();
        }

        if (! $entity = $reader->getClassAnnotation($class, 'HireVoice\\Neo4j\\Annotation\\Entity')) {
            throw new Exception("Class $className is not declared as an entity.");
        }

        $object = new self($class->getName());

        if ($entity->repositoryClass) {
            $object->repositoryClass = $entity->repositoryClass;
        }
        if ($entity->labels) {
            $object->labels = explode(",", $entity->labels);
        }

        foreach ($class->getProperties() as $property) {
            $prop = new Property($reader, $property);
            if ($prop->isPrimaryKey()) {
                $object->setPrimaryKey($prop);
            } elseif ($prop->isProperty($prop)) {
                $object->properties[] = $prop;

                if ($prop->isIndexed()) {
                    $object->indexedProperties[] = $prop;
                }
            } elseif ($prop->isRelationList()) {
                $object->manyToManyRelations[] = $prop;
            } elseif ($prop->isRelation()) {
                $object->manyToOneRelations[] = $prop;
            }
        }

        $object->validate();

        return $object;
    }

    public function __construct($className)
    {
        $this->className = $className;
    }

    function getRepositoryClass()
    {
        return $this->repositoryClass;
    }

    function getLabels()
    {
        return $this->labels;
    }

    function getProxyClass()
    {
        return 'neo4jProxy' . str_replace('\\', '_', $this->className);
    }

    function getName()
    {
        return $this->className;
    }

    function getIndexedProperties()
    {
        return $this->indexedProperties;
    }

    /**
     * @return \HireVoice\Neo4j\Meta\Property[]
     */
    function getProperties()
    {
        return $this->properties;
    }

    function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    function getManyToManyRelations()
    {
        return $this->manyToManyRelations;
    }

    function getManyToOneRelations()
    {
        return $this->manyToOneRelations;
    }

    /**
     * Finds property by $name.
     *
     * @param string $name
     * @return \HireVoice\Neo4j\Meta\Property|null
     */
    function findProperty($name)
    {
        $property = Reflection::getProperty($name);

        foreach ($this->properties as $p) {
            if ($p->matches(substr($name, 3), $property)) {
                return $p;
            }
        }

        foreach ($this->manyToManyRelations as $p) {
            if ($p->matches(substr($name, 3), $property)) {
                return $p;
            }
        }

        foreach ($this->manyToOneRelations as $p) {
            if ($p->matches(substr($name, 3), $property)) {
                return $p;
            }
        }
    }

    private function setPrimaryKey(Property $property)
    {
        if ($this->primaryKey) {
             throw new Exception("Class {$this->className} contains multiple targets for @Auto");
        }

        $this->primaryKey = $property;
    }

    private function validate()
    {
        if (! $this->primaryKey) {
             throw new Exception("Class {$this->className} contains no @Auto property");
        }
    }
}
