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

namespace HireVoice\Neo4j\Proxy;

use Doctrine\Common\Collections\ArrayCollection;
use HireVoice\Neo4j\Extension\ArrayCollection as ExtendedArrayCollection;
use HireVoice\Neo4j\Exception;
use HireVoice\Neo4j\Meta\Entity as Meta;

class Factory
{
    private $proxyDir;
    private $debug;

    function __construct($proxyDir = '/tmp', $debug = false)
    {
        $this->proxyDir = rtrim($proxyDir, '/');
        $this->debug = (bool) $debug;
    }

    function fromNode($node, $repository, \Closure $loadCallback)
    {
        $class = $node->getProperty('class');
        $meta = $repository->fromClass($class);
        $proxyClass = $meta->getProxyClass();

        $proxy = $this->createProxy($meta);
        $proxy->__setMeta($meta);
        $proxy->__setNode($node);
        $proxy->__setLoadCallback($loadCallback);

        $pk = $meta->getPrimaryKey();
        $pk->setValue($proxy, $node->getId());
        $proxy->__addHydrated($pk->getName());

        foreach ($meta->getProperties() as $property) {
            $name = $property->getName();

            if ($value = $node->getProperty($name)) {
                $property->setValue($proxy, $value);
                $proxy->__addHydrated($name);
            }
        }

        foreach ($meta->getManyToManyRelations() as $property) {
            if ($property->isWriteOnly()) {
                $proxy->__addHydrated($property->getName());
            }
        }

        return $proxy;
    }

    private function initializeProperties($object, Meta $meta)
    {
        foreach ($meta->getManyToManyRelations() as $metaProperty) {
            $metaProperty->setValue($object, new ExtendedArrayCollection);
        }

        return $object;
    }

    private function createProxy(Meta $meta)
    {
        $proxyClass = $meta->getProxyClass();
        $className = $meta->getName();

        if (class_exists($proxyClass, false)) {
            return $this->initializeProperties($this->newInstance($proxyClass), $meta);
        }

        $targetFile = "{$this->proxyDir}/$proxyClass.php";
        if ($this->debug || ! file_exists($targetFile)) {
            $functions = '';
            $reflectionClass = new \ReflectionClass($className);
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (! $method->isConstructor() && ! $method->isDestructor() && ! $method->isFinal()) {
                    $functions .= $this->methodProxy($method, $meta);
                }
            }

            $properties = $meta->getProperties();
            $properties[] = $meta->getPrimaryKey();
            $properties = array_filter($properties, function ($property) {
                return ! $property->isPrivate();
            });
            $properties = array_map(function ($property) {
                return $property->getName();
            }, $properties);
            $properties = var_export($properties, true);

            $content = <<<CONTENT
<?php
use HireVoice\\Neo4j\\Extension;
use HireVoice\\Neo4j\\Extension\\ArrayCollection;

class $proxyClass extends $className implements HireVoice\\Neo4j\\Proxy\\Entity
{
    private \$neo4j_hydrated = array();
    private \$neo4j_meta;
    private \$neo4j_node;
    private \$neo4j_loadCallback;
    private \$neo4j_relationships = false;

    function getEntity()
    {
        \$entity = new $className;

        foreach (\$this->neo4j_meta->getProperties() as \$prop) {
            \$prop->setValue(\$entity, \$prop->getValue(\$this));
        }

        \$prop = \$this->neo4j_meta->getPrimaryKey();
        \$prop->setValue(\$entity, \$prop->getValue(\$this));

        return \$entity;
    }

    $functions

    function __addHydrated(\$name)
    {
        \$this->neo4j_hydrated[] = \$name;
    }

    function __setMeta(\$meta)
    {
        \$this->neo4j_meta = \$meta;
    }

    function __setNode(\$node)
    {
        \$this->neo4j_node = \$node;
    }

    function __getNode()
    {
        return \$this->neo4j_node;
    }

    function __setLoadCallback(\Closure \$loadCallback)
    {
        \$this->neo4j_loadCallback = \$loadCallback;
    }

    private function __load(\$name, \$propertyName)
    {
        if (in_array(\$propertyName, \$this->neo4j_hydrated)) {
            return;
        }

        if (! \$this->neo4j_meta) {
            throw new \\HireVoice\\Neo4j\\Exception('Proxy not fully initialized. Relations are not available when loading the object from a session or other forms of serialization.');
        }

        \$property = \$this->neo4j_meta->findProperty(\$name);

        if (strpos(\$name, 'set') === 0) {
            \$this->__addHydrated(\$propertyName);
            return;
        }

        if (false === \$this->neo4j_relationships) {
            \$command = new Extension\GetNodeRelationshipsLight(\$this->neo4j_node->getClient(), \$this->neo4j_node);
            \$this->neo4j_relationships = \$command->execute();
        }

        \$this->__addHydrated(\$propertyName);
        \$collection = new ArrayCollection;
        foreach (\$this->neo4j_relationships as \$relation) {
            if (\$relation['type'] == \$propertyName) {
                // Read-only relations read the start node instead
                if (\$property->isTraversed()) {
                    \$nodeUrl = \$relation['end'];
                    \$root = \$relation['start'];
                } else {
                     \$nodeUrl = \$relation['start'];
                     \$root = \$relation['end'];
                }

                if (basename(\$root) == \$this->getId()) {
                    \$node = \$this->neo4j_node->getClient()->getNode(basename(\$nodeUrl));
                    \$loader = \$this->neo4j_loadCallback;
                    \$collection->add(\$loader(\$node));
                }
            }
        }

        if (\$property->isRelationList()) {
            \$property->setValue(\$this, \$collection);
        } else {
            if (count(\$collection)) {
                \$property->setValue(\$this, \$collection->first());
            }
        }
    }

    function __sleep()
    {
        return $properties;
    }
}


CONTENT;
            if ( ! is_dir($this->proxyDir)) {
                if (false === @mkdir($this->proxyDir, 0775, true)) {
                    throw new Exception('Proxy Dir is not writable');
                }
            } else if ( ! is_writable($this->proxyDir)) {
                throw new Exception('Proxy Dir is not writable');
            }
            file_put_contents($targetFile, $content);
        }

        require $targetFile;
        return $this->initializeProperties($this->newInstance($proxyClass), $meta);
    }

    private function newInstance($proxyClass)
    {
        static $prototypes = array();

        if (!array_key_exists($proxyClass, $prototypes)) {
            $prototypes[$proxyClass] = unserialize(sprintf('O:%d:"%s":0:{}', strlen($proxyClass), $proxyClass));
        }

        return clone $prototypes[$proxyClass];
    }

    private function methodProxy($method, $meta)
    {
        $property = $meta->findProperty($method->getName());

        if (! $property) {
            // No need for a proxy if not related to a property
            return;
        }

        if ($property->isProperty()) {
            // Properties are loaded straight-up, no need for proxies
            return;
        }

        $parts = array();
        $arguments = array();

        foreach ($method->getParameters() as $parameter) {
            $variable = '$' . $parameter->getName();
            $parts[] = $variable;

            $arg = $variable;

            if ($parameter->isOptional()) {
                $arg .= ' = ' . var_export($parameter->getDefaultValue(), true);
            }

            if ($parameter->isPassedByReference()) {
                $arg = "& $arg";
            } elseif ($c = $parameter->getClass()) {
                $arg = $c->getName() . ' ' . $arg;
            }

            if ($parameter->isArray()) {
                $arg = "array $arg";
            }

            $arguments[] = $arg;
        }

        $parts = implode(', ', $parts);
        $arguments = implode(', ', $arguments);

        $name = var_export($method->getName(), true);
        $propertyName = var_export($property->getName(), true);

        return <<<FUNC

    function {$method->getName()}($arguments)
    {
        self::__load($name, $propertyName);
        return parent::{$method->getName()}($parts);
    }

FUNC;
    }
}

