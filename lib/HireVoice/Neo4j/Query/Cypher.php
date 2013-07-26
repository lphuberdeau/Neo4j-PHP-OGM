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
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Path;
use HireVoice\Neo4j\EntityManager;

class Cypher
{
    private $em;
    private $start = array();
    private $match = array();
    private $return = array();
    private $where = array();
    private $order = array();
    private $skip;
    private $limit;
    private $mode;
    private $processor;

    function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->processor = new ParameterProcessor(ParameterProcessor::CYPHER);
    }

    function mode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    function start($string)
    {
        $this->start = array_merge($this->start, func_get_args());
        return $this;
    }

    function startWithNode($name, $nodes)
    {
        if (! is_array($nodes)) {
            $nodes = array($nodes);
        }

        $parts = array();
        foreach ($nodes as $key => $node) {
            $fullKey = $name . '_' .$key;

            $parts[] = ":$fullKey";
            $this->set($fullKey, $node);
        }

        $parts = implode(', ', $parts);
        $this->start("$name = node($parts)");
        
        return $this;
    }

    function startWithQuery($name, $index, $query)
    {
        $this->start("$name = node:`$index`('$query')");

        return $this;
    }

    function startWithLookup($name, $index, $key, $value)
    {
        $this->start("$name = node:`$index`($key = :{$name}_{$key})");
        $this->set("{$name}_{$key}", $value);

        return $this;
    }

    function match($string)
    {
        $this->match = array_merge($this->match, func_get_args());
        return $this;
    }

    function end($string)
    {
        $this->return = array_merge($this->return, func_get_args());
        return $this;
    }

    function where($string)
    {
        $this->where = array_merge($this->where, func_get_args());
        return $this;
    }

    function order($string)
    {
        $this->order = array_merge($this->order, func_get_args());
        return $this;
    }

    function skip($skip)
    {
        $this->skip = (int) $skip;
        return $this;
    }

    function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    function set($name, $value)
    {
        $this->processor->setParameter($name, $value);

        return $this;
    }

    function getOne()
    {
        $result = $this->execute();
        if (isset($result[0])) {
            return $this->convertValue($result[0][0]);
        }
    }

    function getList()
    {
        $result = $this->execute();
        $list = new \Doctrine\Common\Collections\ArrayCollection;

        foreach ($result as $row) {
            $list->add($this->convertValue($row[0]));
        }

        return $list;
    }

    function getResult()
    {
        $result = $this->execute();
        $list = new \Doctrine\Common\Collections\ArrayCollection;

        foreach ($result as $row) {
            $entry = array();

            foreach ($row as $key => $value) {
                $entry[$key] = $this->convertValue($value);
            }

            $list->add($entry);
        }

        return $list;
    }

    private function execute()
    {
        $query = '';

        if ($this->mode) {
        $query .= 'CYPHER ' . $this->mode . PHP_EOL;
        }

        $query .= 'start ' . implode(', ', $this->start) . PHP_EOL;

        if (count($this->match)) {
            $query .= 'match ' . implode(', ', $this->match) . PHP_EOL;
        }

        if (count($this->where)) {
            $query .= 'where (' . implode(') AND (', $this->where) . ')' . PHP_EOL;
        }

        $query .= 'return ' . implode(', ', $this->return) . PHP_EOL;

        if (count($this->order)) {
            $query .= 'order by ' . implode(', ', $this->order) . PHP_EOL;
        }

        if ($this->skip) {
            $query .= 'skip ' . $this->skip . PHP_EOL;
        }

        if ($this->limit) {
            $query .= 'limit ' . $this->limit . PHP_EOL;
        }

        $this->processor->setQuery($query);
        $parameters = $this->processor->process();

        return $this->em->cypherQuery($this->processor->getQuery(), $parameters);
    }

    private function convertValue($value)
    {
        if ($value instanceof Node) {
            return $this->em->load($value);
        } elseif ($value instanceof Path) {
            return new \HireVoice\Neo4j\PathFinder\Path($value, $this->em);
        } else {
            return $value;
        }
    }
}
