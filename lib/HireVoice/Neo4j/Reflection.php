<?php

namespace HireVoice\Neo4j;

class Reflection
{
    public static function getProperty($methodName)
    {
        $property = substr($methodName, 3);
        return self::cleanProperty($property);
    }

    public static function cleanProperty($property)
    {
        $property = lcfirst($property);

        if ('ies' == substr($property, -3)) {
            $property = substr($property, 0, -3) . 'y';
        }

        if ('s' == substr($property, -1)) {
            $property = substr($property, 0, -1);
        }

        return $property;
    }
}

