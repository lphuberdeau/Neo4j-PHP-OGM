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
use HireVoice\Neo4j\Exception;

abstract class GraphElement
{
    private $className;
    private $primaryKey;
    private $properties = array();
    private $indexedProperties = array();

    public function __construct($className)
    {
        $this->className = $className;
    }

	public function loadProperties($reader, $properties)
	{
        foreach ($properties as $property) {
            $prop = new Property($reader, $property);
            if ($prop->isPrimaryKey()) {
                $this->setPrimaryKey($prop);
            } elseif ($prop->isProperty($prop)) {
                $this->properties[] = $prop;

                if ($prop->isIndexed()) {
                    $this->indexedProperties[] = $prop;
                }
            }
        }
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
    }

    function setPrimaryKey(Property $property)
    {
        if ($this->primaryKey) {
             throw new Exception("Class {$this->className} contains multiple targets for @Auto");
        }

        $this->primaryKey = $property;
    }

    function validate()
    {
        if (! $this->primaryKey) {
             throw new Exception("Class {$this->className} contains no @Auto property");
        }
    }
}

