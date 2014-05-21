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
use Doctrine\Common\Collections\ArrayCollection;
use HireVoice\Neo4j\Query\LuceneQueryProcessor;
use Doctrine\Common\Persistence\ObjectRepository;

class Repository implements ObjectRepository
{
    /**
     * @var \HireVoice\Neo4j\Meta\Entity
     */
    private $meta;

    /**
     * @var \Everyman\Neo4j\Index\NodeIndex
     */
    private $index;

    /**
     * @var \HireVoice\Neo4j\EntityManager
     */
    private $entityManager;

    /**
     * @var string
     */
    private $class;

    function __construct(EntityManager $entityManager, Meta\Entity $meta)
    {
        $this->entityManager = $entityManager;
        $this->class = $meta->getName();
        $this->meta = $meta;
    }

    function find($id)
    {
        if (! $entity = $this->entityManager->findAny($id)) {
            return false;
        }

        if (! $entity->getEntity() instanceof $this->class) {
            return false;
        }

        return $entity;
    }

    function findAll()
    {
        $collection = new ArrayCollection();
        foreach($this->getIndex()->query('id:*') as $node){
            $collection->add($this->entityManager->load($node));
        }

        return $collection;
    }
    
    protected function createGremlinQuery($string = null)
    {
        return $this->entityManager->createGremlinQuery($string);
    }

    protected function createCypherQuery()
    {
        return $this->entityManager->createCypherQuery();
    }

    function getIndex()
    {
        return $this->entityManager->createIndex($this->class);
    }

    /**
     * Finds one node by a set of criteria
     *
     * @param array $criteria An array of search criteria
     * @return null|object
     */
    public function findOneBy(array $criteria)
    {
        $query = $this->createQuery($criteria);  
   
        if ($node = $this->getIndex()->queryOne($query)) {
            return $this->entityManager->load($node);
        }
        return null;
    }

    /**
     * Finds all node matching the search criteria
     *
     * @param array $criteria An array of search criteria
     * @param array $orderBy
     * @param null $limit
     * @param null $offset
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        if ($orderBy !== null || $limit !== null || $offset !== null) {
            throw new \InvalidArgumentException('$orderBy, $limit and $offset are currently not supported');
        }

        $query = $this->createQuery($criteria);
        $collection = new ArrayCollection();

        foreach($this->getIndex()->query($query) as $node) {
            $collection->add($this->entityManager->load($node));
        }
        return $collection;
    }

    /**
     * Calls the Lucene Query Processor to build the query
     *
     * @param array $criteria An array of search criterias
     */
    public function createQuery(array $criteria = array())
    {
        if(!empty($criteria)) {
            $queryProcessor = new LuceneQueryProcessor();
            foreach($criteria as $key => $value) {
                $queryProcessor->addQueryTerm($key, $value);
            }
            return $queryProcessor->getQuery();
        }
        throw new \InvalidArgumentException('The criteria passed to the find** method can not be empty');
    }

    function __call($name, $arguments)
    {
        if (strpos($name, 'findOneBy') === 0) {
            $property = substr($name, 9);
            $property = Meta\Reflection::singularizeProperty($property);

            if ($node = $this->getIndex()->findOne($property, $arguments[0])) {
                return $this->entityManager->load($node);
            }
        } elseif (strpos($name, 'findBy') === 0) {
            $property = $this->getSearchableProperty(substr($name, 6));

            $collection = new ArrayCollection;
            foreach ($this->getIndex()->find($property, $arguments[0]) as $node) {
                $collection->add($this->entityManager->load($node));
            }
            return $collection;
        }

        
    }

    private function getSearchableProperty($property)
    {
        $property = Meta\Reflection::singularizeProperty($property);

        foreach ($this->meta->getIndexedProperties() as $p) {
            if (Meta\Reflection::singularizeProperty($p->getName()) == $property) {
                return $property;
            }
        }

        throw new Exception("Property $property is not indexed.");
    }

    protected function getRepository($class)
    {
        return $this->entityManager->getRepository($class);
    }

    /**
     * Retrieves entity manager.
     *
     * @return \HireVoice\Neo4j\EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Retrieves meta info.
     *
     * @return \HireVoice\Neo4j\Meta\Entity
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Returns the class name of the object managed by the repository.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->class;
    }    
}
