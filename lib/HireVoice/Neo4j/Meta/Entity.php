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
use ReflectionProperty;

class Entity
{
    /**
     * @var string
     */
    private $repositoryClass = 'HireVoice\\Neo4j\\Repository';

    /**
     * @var array
     */
    private $labels = array();

    /**
     * @var string
     */
    private $className;

    /**
     * @var int
     */
    private $primaryKey;

    /**
     * @var \HireVoice\Neo4j\Meta\Property[]
     */
    private $properties = array();

    /**
     * @var \HireVoice\Neo4j\Meta\Property[]
     */
    private $indexedProperties = array();

    /**
     * @var \HireVoice\Neo4j\Meta\Property[]
     */
    private $manyToManyRelations = array();

    /**
     * @var \HireVoice\Neo4j\Meta\Property[]
     */
    private $manyToOneRelations = array();

    /**
     * @param string $className
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * @param AnnotationReader $reader
     * @param string $className
     * @return Entity
     * @throws \HireVoice\Neo4j\Exception
     */
    public static function fromClass(AnnotationReader $reader, $className)
    {
        $class = new \ReflectionClass($className);

        if ($class->implementsInterface('HireVoice\\Neo4j\\Proxy\\Entity')) {
            $class = $class->getParentClass();
            $className = $class->getName();
        }

        if (!$entity = $reader->getClassAnnotation($class, 'HireVoice\\Neo4j\\Annotation\\Entity')) {
            throw new Exception("Class $className is not declared as an entity.");
        }

        $object = new self($class->getName());
        if ($entity->repositoryClass) {
            $object->repositoryClass = $entity->repositoryClass;
        }
        if ($entity->labels) {
            $object->labels = explode(",", $entity->labels);
        }

        foreach (self::getClassProperties($class->getName()) as $prop) {
            $prop = new Property($reader, $prop);
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

    /**
    * Recursive function to get an associative array of class properties by property name => ReflectionProperty() object 
    * including inherited ones from extended classes 
    * @param string $className Class name 
    * @return array 
    */
    private static function getClassProperties($className){
        $ref = new \ReflectionClass($className);
        $props = $ref->getProperties();
        $props_arr = array();
        foreach($props as $prop){
            $f = $prop->getName();
            $props_arr[$f] = $prop;
        }
        if($parentClass = $ref->getParentClass()){
            $parent_props_arr = self::getClassProperties($parentClass->getName());
            if(count($parent_props_arr) > 0){
                $props_arr = array_merge($parent_props_arr, $props_arr);
            }
        } 
        return $props_arr;
    }

	/*
     * @return string
     */
    public function getRepositoryClass()
    {
        return $this->repositoryClass;
    }

    /**
     * @return array
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @return string
     */
    public function getProxyClass()
    {
        return 'neo4jProxy' . str_replace('\\', '_', $this->className);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->className;
    }

    /**
     * @return array
     */
    public function getIndexedProperties()
    {
        return $this->indexedProperties;
    }

    /**
     * @return \HireVoice\Neo4j\Meta\Property[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return \HireVoice\Neo4j\Meta\Property
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @return Property[]
     */
    public function getManyToManyRelations()
    {
        return $this->manyToManyRelations;
    }

    /**
     * @return Property[]
     */
    public function getManyToOneRelations()
    {
        return $this->manyToOneRelations;
    }

    function getManyToOneRelation($labelName)
    {
        foreach($this->manyToOneRelations as $rel){
            if($rel->getName() == $labelName){
                return $rel;
                break;
            }
        }
        return NULL;
    }
    
    function getManyToManyRelation($labelName)
    {
        foreach($this->manyToManyRelations as $rel){
            if($rel->getName() == $labelName){
                return $rel;
                break;
            }
        }
        return NULL;
    }
    
    function getRelation($labelName)
    {
        foreach($this->manyToManyRelations as $rel){
            if($rel->getName() == $labelName){
                return $rel;
                break;
            }
        }
        foreach($this->manyToOneRelations as $rel){
            if($rel->getName() == $labelName){
                return $rel;
                break;
            }
        }
        return NULL;
    }
    /**
     * Finds property by $name.
     *
     * @param string $name
     * @return \HireVoice\Neo4j\Meta\Property|null
     */
    public function findProperty($name)
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

        return null;
    }

    /**
     * @param Property $property
     * @throws \HireVoice\Neo4j\Exception
     */
    private function setPrimaryKey(Property $property)
    {
        if ($this->primaryKey) {
            throw new Exception("Class {$this->className} contains multiple targets for @Auto");
        }

        $this->primaryKey = $property;
    }

    /**
     * @throws \HireVoice\Neo4j\Exception
     */
    private function validate()
    {
        if (!$this->primaryKey) {
            throw new Exception("Class {$this->className} contains no @Auto property");
        }
    }
}
