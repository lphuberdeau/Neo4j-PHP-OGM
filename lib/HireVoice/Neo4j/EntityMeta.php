<?php

namespace HireVoice\Neo4j;
use Doctrine\Common\Annotations\Reader as AnnotationReader;

class EntityMeta
{
    private $repositoryClass = 'HireVoice\\Neo4j\\Repository';
    private $className;
    private $primaryKey;
    private $properties = array();
    private $indexedProperties = array();
    private $manyToManyRelations = array();
    private $manyToOneRelations = array();

    public static function fromClass(AnnotationReader $reader, $className)
    {
        $class = new \ReflectionClass($className);

        if ($class->implementsInterface('HireVoice\\Neo4j\\EntityProxy')) {
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

        foreach ($class->getProperties() as $property) {
            $prop = new PropertyMeta($reader, $property);
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

    private function setPrimaryKey(PropertyMeta $property)
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
