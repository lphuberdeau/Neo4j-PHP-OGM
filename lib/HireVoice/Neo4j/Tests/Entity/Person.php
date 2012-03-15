<?php

namespace HireVoice\Neo4j\Tests\Entity;
use HireVoice\Neo4j\Annotation as OGM;

/**
 * @OGM\Entity
 */
class Person
{
    /**
     * @OGM\Auto
     */
    private $id;

    /**
     * @OGM\Property
     */
    private $firstName;

    /**
     * @OGM\Property
     */
    private $lastName;

    function getId()
    {
        return $this->id;
    }

    function setId($id)
    {
        $this->id = $id;
    }

    function getFirstName()
    {
        return $this->firstName;
    }

    function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    function getLastName()
    {
        return $this->lastName;
    }

    function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }
}

