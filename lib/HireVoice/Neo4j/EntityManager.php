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

namespace HireVoice\Neo4j;

use Everyman\Neo4j\Client,
    Everyman\Neo4j\Node,
    Everyman\Neo4j\Relationship,
    Everyman\Neo4j\Index\NodeIndex,
    Everyman\Neo4j\Gremlin\Query as InternalGremlinQuery,
    Everyman\Neo4j\Cypher\Query as InternalCypherQuery;

/**
 * The entity manager handles the communication with the database server and
 * keeps track of the various entities in the system.
 */
class EntityManager
{
    const ENTITY_CREATE = 'entity.create';
    const RELATION_CREATE = 'relation.create';
	const QUERY_RUN = 'query.run';
	
    private $client;
    private $metaRepository;
    private $proxyFactory;
    private $batch;

    private $entities = array();

    private $nodes = array();
    private $indexes = array();
    private $repositories = array();

    private $loadedNodes = array();

    private $dateGenerator;

    private $eventHandlers = array();

    /**
     * Initialize the entity manager using the provided configuration.
     * Configuration options are detailed in the Configuration class.
     * 
     * @param Configuration|array $configuration Various information about how the entity manager should behave.
     */
    function __construct($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = new Configuration;
        } elseif (is_array($configuration)) {
            $configuration = new Configuration($configuration);
        } elseif (! $configuration instanceof Configuration) {
            throw new Exception('Provided argument must be a Configuration object or an array.');
        }

        $this->proxyFactory = $configuration->getProxyFactory();
        $this->client = $configuration->getClient();
        $this->metaRepository = $configuration->getMetaRepository();

        $this->dateGenerator = function () {
            $currentDate = new \DateTime;
            return $currentDate->format('Y-m-d H:i:s');
        };
    }

    /**
     * Includes an entity to persist on the next flush. Persisting entities will cause
     * relations to be followed to discover other entities. Relation traversal will happen
     * during the flush.
     *
     * @param object $entity Any object providing the correct Entity annotations.
     */
    function persist($entity)
    {
        $meta = $this->getMeta($entity);

        $hash = $this->getHash($entity);
        $this->entities[$hash] = $entity;
    }

    /**
     * Commit changes in the object model into the database. Relations will be traversed
     * to discover additional entities. To include an object in the unit of work, use the
     * persist() method.
     */
    function flush()
    {
        $this->discoverEntities();
        $this->writeEntities();
        $this->writeRelations();
        $this->writeIndexes();

        $this->entities = array();
        $this->nodes = array();
    }

    /**
     * Searches a single entity by ID for a given class name. The result will be provided
     * as a proxy node to handle lazy loading of relations.
     *
     * @param string $class The fully qualified class name
     * @param int $id The node ID
     * @return object The entity object
     */
    function find($class, $id)
    {
        return $this->getRepository($class)->find($id);
    }

    /**
     * Searches a single entity by ID, regardless of the class used. The result will be
     * provided as a proxy
     *
     * @param int $id The node ID
     */
    function findAny($id)
    {
        if ($node = $this->client->getNode($id)) {
            return $this->load($node);
        }

        return false;
    }

    /**
     * @access private
     */
    function load($node)
    {
        if (! isset($this->loadedNodes[$node->getId()])) {
            $em = $this;

            $entity = $this->proxyFactory->fromNode($node, $this->metaRepository, function ($node) use ($em) {
                return $em->load($node);
            });

            $this->loadedNodes[$node->getId()] = $entity;
            $this->nodes[$this->getHash($entity)] = $node;
        }

        return $this->loadedNodes[$node->getId()];
    }

    /**
     * Reload an entity. Exchanges an raw entity or an invalid proxy with an initialized
     * proxy.
     *
     * @param object $entity Any entity or entity proxy
     */
    function reload($entity)
    {
        if ($entity instanceof Proxy\Entity) {
            return $this->load($this->findAny($entity->getId()));
        } else {
            return $this->find(get_class($entity), $entity->getId());
        }
    }

    /**
     * Clear entity cache.
     */
    function clear()
    {
        $this->loadedNodes = array();
    }

    /**
     * Provide a Gremlin query builder.
     * 
     * @param string $query Initial query fragment.
     * @return Query\Gremlin
     */
    function createGremlinQuery($query = null)
    {
        $q = new Query\Gremlin($this);

        if ($query) {
            $q->add($query);
        }

        return $q;
    }

    /**
     * Raw gremlin query execution. Used by Query\Gremlin.
     *
     * @param string $string The query string.
     * @param array $parameters The arguments to bind with the query.
     * @return Everyman\Neo4j\Query\ResultSet
     */
    function gremlinQuery($string, $parameters)
    {
        try {
            $start = microtime(true);

            $query = new InternalGremlinQuery($this->client, $string, $parameters);
            $rs = $query->getResultSet();

            $time = microtime(true) - $start;
            $this->triggerEvent(self::QUERY_RUN, $query, $parameters, $time);

            if (count($rs) === 1
                && is_string($rs[0][0])
                && strpos($rs[0][0], 'Exception') !== false
            ) {
                throw new Exception("An error was detected: {$rs[0][0]}", 0, null, $query);
            }

            return $rs;
        } catch (\Everyman\Neo4j\Exception $e) {
            throw new Exception("An error was detected: {$e->getMessage()}", 0, null, $query);
        }
    }

    /**
     * Provide a Cypher query builder.
     *
     * @return Query\Cypher
     */
    function createCypherQuery()
    {
        return new Query\Cypher($this);
    }

    /**
     * Raw cypher query execution.
     *
     * @param string $string The query string.
     * @param array $parameters The arguments to bind with the query.
     * @return Everyman\Neo4j\Query\ResultSet
     */
    function cypherQuery($string, $parameters)
    {
        try {
            $start = microtime(true);

            $query = new InternalCypherQuery($this->client, $string, $parameters);
            $rs = $query->getResultSet();

            $time = microtime(true) - $start;
            $this->triggerEvent(self::QUERY_RUN, $query, $parameters, $time);

            return $rs;
        } catch (\Everyman\Neo4j\Exception $e) {
            $message = $e->getMessage();
            preg_match('/\[message\] => (.*)/', $message, $parts);
            throw new Exception('Query execution failed: ' . $parts[1], 0, $e, $query);
        }
    }

    /**
     * Obtain an entity repository for a single class. The repository provides
     * multiple methods to access nodes and can be extended per entity by
     * specifying the correct annotation.
     *
     * @param string $class Fully qualified class name
     */
    function getRepository($class)
    {
        if (! isset($this->repositories[$class])) {
            $meta = $this->metaRepository->fromClass($class);
            $repositoryClass = $meta->getRepositoryClass();
            $repository = new $repositoryClass($this, $meta);

            if (! $repository instanceof Repository) {
                throw new Exception("Requested repository class $repositoryClass does not extend the base repository class.");
            }

            $this->repositories[$class] = $repository;
        }

        return $this->repositories[$class];
    }

    /**
     * Register an event listener for a given event.
     *
     * @param string $eventName The event to listen, available as constants.
     */
    function registerEvent($eventName, $callback)
    {
        $this->eventHandlers[$eventName][] = $callback;
    }

    private function triggerEvent($eventName, $data)
    {
        if (isset($this->eventHandlers[$eventName])) {
            $args = func_get_args();
            array_shift($args);

            foreach ($this->eventHandlers[$eventName] as $callback) {
                $clone = $args;
                call_user_func_array($callback, $clone);
            }
        }
    }

    private function discoverEntities()
    {
        do {
            $entities = $this->entities;
            
            foreach ($entities as $entity) {
                $this->discoverEntitiesOn($entity);
            }

        } while (count($this->entities) != count($entities));
    }

    private function discoverEntitiesOn($entity)
    {
        $em = $this;

        $this->traverseRelations($entity, function ($entry) use ($em) {
            $em->persist($entry);
        });
    }

    private function traverseRelations($entity, $addCallback, $removeCallback = null)
    {
        $meta = $this->getMeta($entity);

        foreach ($meta->getManyToManyRelations() as $property) {
            if ($property->isTraversed()) {
                $list = $property->getValue($entity);

                foreach ($list as $entry) {
                    $addCallback($entry, $property->getName());
                }

                if ($removeCallback && $list instanceof Extension\ArrayCollection) {
                    foreach ($list->getRemovedElements() as $entry) {
                        $removeCallback($entry, $property->getName());
                    }
                }
            }
        }

        foreach ($meta->getManyToOneRelations() as $property) {
            if ($property->isTraversed()) {
                if ($entry = $property->getValue($entity)) {
                    if ($removeCallback) {
                        $this->removePreviousRelations($entity, $property->getName(), $entry);
                    }
                    $addCallback($entry, $property->getName());
                }
            }
        }
    }

    private function writeEntities()
    {
        $this->begin();
        foreach ($this->entities as $entity) {
            $hash = $this->getHash($entity);
            $this->nodes[$hash] = $this->createNode($entity)->save();
        }
        $this->commit();

        // Write the primary key
        foreach ($this->entities as $entity) {
            $hash = $this->getHash($entity);
            $meta = $this->getMeta($entity);
            $pk = $meta->getPrimaryKey();

            $nodeId = $this->nodes[$hash]->getId();
            if ($pk->getValue($entity) != $nodeId) {
                $pk->setValue($entity, $nodeId);
                $this->triggerEvent(self::ENTITY_CREATE, $entity);
            }
        }
    }

    private function createNode($entity)
    {
        $meta = $this->getMeta($entity);

        $pk = $meta->getPrimaryKey();
        $id = $pk->getValue($entity);

        if ($id) {
            $node = $this->client->getNode($id);
        } else {
            $node = $this->client->makeNode()
                ->setProperty('class', $meta->getName());
        }

        foreach ($meta->getProperties() as $property) {
            $result = $property->getValue($entity);

            $node->setProperty($property->getName(), $result);
        }

        $currentDate = $this->getCurrentDate();

        if (! $id) {
            $node->setProperty('creationDate', $currentDate);
        }

        $node->setProperty('updateDate', $currentDate);

        return $node;
    }

    private function writeRelations()
    {
        $this->begin();
        foreach ($this->entities as $entity) {
            $this->writeRelationsFor($entity);
        }
        $this->commit();
    }

    private function begin()
    {
        $this->batch = $this->client->startBatch();
    }

    private function commit()
    {
        if (count($this->batch->getOperations())) {
            $this->client->commitBatch();
        } else {
            $this->client->endBatch();
        }

        $this->batch = null;
    }

    private function writeRelationsFor($entity)
    {
        $em = $this;

        $addCallback = function ($entry, $relation) use ($entity, $em) {
            $em->addRelation($relation, $entity, $entry);
        };
        $removeCallback = function ($entry, $relation) use ($entity, $em) {
            $em->removeRelation($relation, $entity, $entry);
        };

        $this->traverseRelations($entity, $addCallback, $removeCallback);
    }

    /**
     * @access private
     */
    function addRelation($relation, $a, $b)
    {
        $a = $this->getLoadedNode($a);
        $b = $this->getLoadedNode($b);

        $existing = $this->getRelationsFrom($a, $relation);

        foreach ($existing as $r) {
            if (basename($r['end']) == $b->getId()) {
                return;
            }
        }

        $relationship = $a->relateTo($b, $relation)
            ->setProperty('creationDate', $this->getCurrentDate())
            ->save();

        list($relation, $a, $b) = func_get_args();
        $this->triggerEvent(self::RELATION_CREATE, $relation, $a, $b, $relationship);
    }

    /**
     * @access private
     */
    function removeRelation($relation, $a, $b)
    {
        $a = $this->getLoadedNode($a);
        $b = $this->getLoadedNode($b);

        $existing = $this->getRelationsFrom($a, $relation);

        foreach ($existing as $r) {
            if (basename($r['end']) == $b->getId()) {
                $this->deleteRelationship($r);
                return;
            }
        }
    }

    private function removePreviousRelations($from, $relation, $exception)
    {
        $node = $this->getLoadedNode($from);

        foreach ($this->getRelationsFrom($node, $relation) as $r) {
            if (basename($r['end']) != $exception->getId()) {
                $this->deleteRelationship($r);
            }
        }
    }

    private function deleteRelationship($r)
    {
        if ($relationship = $this->client->getRelationship(basename($r['self'])))  {
            $relationship->delete();
        }
    }

    private function getLoadedNode($entity)
    {
        return $this->nodes[$this->getHash($entity)];
    }

    private function getRelationsFrom($node, $relation)
    {
        // Cache sequential calls for the same element
        static $loaded = null, $existing;
        if ($loaded !== $node) {
            $command = new Extension\GetNodeRelationshipsLight($this->client, $node);
            $existing = $command->execute();
            $loaded = $node;
        }

        return array_filter($existing, function ($entry) use ($relation) {
            return $entry['type'] == $relation;
        });
    }

    function createIndex($className)
    {
        return new NodeIndex($this->client, $className);
    }

    private function getHash($object)
    {
        return spl_object_hash($object);
    }

    private function index($entity)
    {
        $meta = $this->getMeta($entity);
        
		$class = $meta->getName();
		$index = $this->getRepository($class)->getIndex();
		$node = $this->getLoadedNode($entity);
		
        foreach ($meta->getIndexedProperties() as $property) {
            $index->add($node, $property->getName(), $property->getValue($entity));
        }
		
		$index->add($node, 'class', $class);
		$index->add($node, 'id', $entity->getId());
    }

    private function writeIndexes()
    {
        $this->begin();
        foreach ($this->entities as $entity) {
            $this->index($entity);
        }

        foreach ($this->repositories as $repository) {
            $repository->writeIndex();
        }
        $this->commit();
    }

    private function getMeta($entity)
    {
        return $this->metaRepository->fromClass(get_class($entity));
    }

    private function getCurrentDate()
    {
        $gen = $this->dateGenerator;
        return $gen();
    }

    /**
     * Alter how dates are generated. Primarily used for test cases.
     */
    function setDateGenerator(\Closure $generator)
    {
        $this->dateGenerator = $generator;
    }

    /**
     * Returns the Client
     *
     * @return Everyman\Neo4j\Client
     */
    public function getClient()
    {
        return $this->client;
    }
}

