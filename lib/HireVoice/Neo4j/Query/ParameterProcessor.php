<?php

namespace HireVoice\Neo4j\Query;

class ParameterProcessor
{
    private $query;
    private $parameters = array();

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
        $parameters = $this->parameters;
        $string = $this->query;

        $string = str_replace('[:', '[;;', $string);
        $parameters = array_filter($parameters, function ($value) use (& $parameters, & $string) {
            $key = key($parameters);
            next($parameters);

            if (is_numeric($value)) {
                $string = str_replace(":$key", $value, $string);
                return false;
            } else {
                $string = str_replace(":$key", $key, $string);
                return true;
            }
        });
        $string = str_replace('[;;', '[:', $string);

        $this->query = $string;
        return $parameters;
    }
}

