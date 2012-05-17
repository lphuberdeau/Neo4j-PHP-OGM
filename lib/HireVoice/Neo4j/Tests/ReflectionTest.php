<?php
namespace HireVoice\Neo4j\Tests;
use HireVoice\Neo4j\Reflection;

class ReflectionTest extends \PHPUnit_Framework_TestCase
{
    function testBasicPlural()
    {
        $this->assertEquals('bee', Reflection::cleanProperty('bees'));
    }

    function testWithIes()
    {
        $this->assertEquals('property', Reflection::cleanProperty('properties'));
    }

    function testDoubleS()
    {
        $this->assertEquals('access', Reflection::cleanProperty('access'));
    }
}

