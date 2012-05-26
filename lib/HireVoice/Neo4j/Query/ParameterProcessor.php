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

class ParameterProcessor
{
    const GREMLIN = 'gremlin';
    const CYPHER = 'cypher';

    private $mode;
    private $query;
    private $parameters = array();

    function __construct($mode = 'gremlin')
    {
        $this->mode = $mode;
    }

    function setQuery($string)
    {
        $this->query = $string;
    }

    function getQuery()
    {
        return $this->query;
    }
    
    function setParameter($name, $value)
    {
        if (is_object($value) && method_exists($value, 'getId')) {
            $this->parameters[$name] = $value->getId();
        } else {
            $this->parameters[$name] = $value;
        }
    }

    function process()
    {
        $mode = $this->mode;
        $parameters = $this->parameters;
        $string = $this->query;

        $string = str_replace('[:', '[;;', $string);
        $parameters = array_filter($parameters, function ($value) use (& $parameters, & $string, $mode) {
            $key = key($parameters);
            next($parameters);

            if (is_numeric($value)) {
                $string = str_replace(":$key", $value, $string);
                return false;
            } else {
                if ($mode == 'cypher') {
                    $string = str_replace(":$key", '{' . $key . '}', $string);
                } else {
                    $string = str_replace(":$key", $key, $string);
                }
                return true;
            }
        });
        $string = str_replace('[;;', '[:', $string);

        $this->query = $string;
        return $parameters;
    }
}

