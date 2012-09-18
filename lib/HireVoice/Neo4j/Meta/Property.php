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

class Property
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
        if ($this->isProperty() || $this->isRelation()) {
            $this->name = $property->getName();
        } else {
            // as far as we know only relation list are collections with names we can 'normalize'
            $this->name = Reflection::singularizeProperty($property->getName());
        }
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

    function isPrivate()
    {
        return $this->property->isPrivate();
    }

    function getValue($entity)
    {
        $raw = $this->property->getValue($entity);

        switch ($this->format) {
        case 'scalar':
        case 'relation':
            return $raw;
        case 'array':
            return serialize($raw);
        case 'json':
            return json_encode($raw);
        case 'date':
            if ($raw) {
                $value = clone $raw;
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
        case 'array':
            $this->property->setValue($entity, unserialize($value));
            break;
        case 'json':
            $this->property->setValue($entity, json_decode($value, true));
            break;
        case 'date':
            $date = null;
            if ($value) {
                $date = new \DateTime($value . ' UTC');
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }

            $this->property->setValue($entity, $date);
            break;
        }
    }

    function getName()
    {
        return $this->name;
    }

    function getOriginalName()
    {
        return $this->property->getName();
    }

    function matches($names)
    {
        foreach (func_get_args() as $name) {
            if (0 === strcasecmp($name, $this->name)
                || 0 === strcasecmp($name, $this->property->getName())
                || 0 === strcasecmp($name, Reflection::singularizeProperty($this->property->getName()))
            ) {
                return true;
            }
        }

        return false;
    }
}

