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

namespace HireVoice\Neo4j\Query;

use Doctrine\Common\Collections\ArrayCollection;
use Everyman\Neo4j\Node;
use HireVoice\Neo4j\EntityManager;
use HireVoice\Neo4j\Exception;

class Gremlin extends Query
{
    /**
     * @var array
     */
    private $parts = array();

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, 'gremlin');
    }

    /**
     * @api
     * @param string $query
     * @return Gremlin
     */
    public function add($query)
    {
        $this->parts[] = $query;

        return $this;
    }

    /**
     * @api
     * @return ArrayCollection
     * @throws Exception
     */
    public function getList()
    {
        $result = new ArrayCollection;

        foreach ($this->execute() as $row) {
            if ($row[0] instanceof Node) {
                $result->add($this->em->load($row[0]));
            } else {
                throw new Exception('Expecting node, got: ' . $row[0]);
            }
        }

        return $result;
    }

    /**
     * @api
     * @return ArrayCollection
     */
    public function getKeyList()
    {
        $out = new ArrayCollection;

        foreach ($this->getMap() as $key => $value) {
            $out->add($this->convertValue($key));
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getMap()
    {
        $result = $this->execute();

        $out = array();
        if (isset($result[0])) {
            $result = $result[0][0];

            foreach ($result as $key => $value) {
                $out[$key] = $this->convertValue($value);
            }
        } else {
            $class = new \ReflectionClass($result);
            $dataProperty = $class->getProperty('data');
            $dataProperty->setAccessible(true);

            $result = $dataProperty->getValue($result);

            foreach ($result as $key => $values) {
                $value = reset($values);

                $out[$key] = $this->convertValue($value);
            }
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getEntityMap()
    {
        $out = array();

        foreach ($this->getMap() as $key => $value) {
            $out[] = array(
                'key' => $this->convertValue($key),
                'value' => $value,
            );
        }

        return $out;
    }

    /**
     * @api
     * @return bool|Node
     */
    public function getOne()
    {
        $result = $this->execute();

        return $this->convertValue($result[0][0]);
    }

    /**
     * @api
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    private function execute()
    {
        $string = implode(";", $this->parts);

        $this->processor->setQuery($string);
        $parameters = $this->processor->process();

        $rs = $this->em->gremlinQuery($this->processor->getQuery(), $parameters);

        return $rs;
    }

    /**
     * @param mixed $value
     * @return bool|Node
     */
    private function convertValue($value)
    {
        if (preg_match('/^v\[(\d+)\]$/', $value, $parts)) {
            return $this->em->findAny($parts[1]);
        } else {
            return $value;
        }
    }
}

