<?php

namespace HireVoice\Neo4j;

class PropertyMeta
{
    const AUTO = 'HireVoice\\Neo4j\\Annotation\\Auto';
    const PROPERTY = 'HireVoice\\Neo4j\\Annotation\\Property';
    const INDEX = 'HireVoice\\Neo4j\\Annotation\\Index';
    const TO_MANY = 'HireVoice\\Neo4j\\Annotation\\ManyToMany';
    const TO_ONE = 'HireVoice\\Neo4j\\Annotation\\ManyToOne';
    
    private $reader;
    private $property;
    private $name;
    private $format = 'relation';
    private $traversed = true;
    private $writeOnly = false;

    function __construct($reader, $property)
    {
        $this->reader = $reader;
        $this->property = $property;
        $this->name = Reflection::cleanProperty($property->getName());
        $property->setAccessible(true);
    }

    function isPrimaryKey()
    {
        return !! $this->reader->getPropertyAnnotation($this->property, self::AUTO);
    }

    function isProperty()
    {
        if ($annotation = $this->reader->getPropertyAnnotation($this->property, self::PROPERTY)) {
            $this->format = $annotation->format;
            return true;
        } else {
            return false;
        }
    }

    function isIndexed()
    {
        return !! $this->reader->getPropertyAnnotation($this->property, self::INDEX);
    }

    function isTraversed()
    {
        return $this->traversed;
    }

    function isWriteOnly()
    {
        return $this->writeOnly;
    }

    function isRelation()
    {
        if ($annotation = $this->reader->getPropertyAnnotation($this->property, self::TO_ONE)) {
            if ($annotation->relation) {
                $this->name = $annotation->relation;
            }
            $this->traversed = ! $annotation->readOnly;

            return true;
        }

        return false;
    }
    
    function isRelationList()
    {
        if ($annotation = $this->reader->getPropertyAnnotation($this->property, self::TO_MANY)) {
            if ($annotation->relation) {
                $this->name = $annotation->relation;
            }
            $this->traversed = ! $annotation->readOnly;
            $this->writeOnly = $annotation->writeOnly;

            return true;
        }

        return false;
    }

    function getValue($entity)
    {
        switch ($this->format) {
        case 'scalar':
        case 'relation':
            return $this->property->getValue($entity);
        case 'date':
            $value = $this->property->getValue($entity);
            if ($value) {
                $value = clone $value;
                $value->setTimezone(new \DateTimeZone('UTC'));
                return $value->format('Y-m-d H:i:s');
            } else {
                return null;
            }
        }
    }

    function setValue($entity, $value)
    {
        switch ($this->format) {
        case 'scalar':
        case 'relation':
            $this->property->setValue($entity, $value);
            break;
        case 'date':
            $date = new \DateTime($value . ' UTC');
            $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $this->property->setValue($entity, $date);
            break;
        }
    }

    function getName()
    {
        return $this->name;
    }

    function matches($names)
    {
        foreach (func_get_args() as $name) {
            if (0 === strcasecmp($name, $this->name)
                || 0 === strcasecmp($name, $this->property->getName())
                || 0 === strcasecmp($name, Reflection::cleanProperty($this->property->getName()))
            ) {
                return true;
            }
        }

        return false;
    }
}

