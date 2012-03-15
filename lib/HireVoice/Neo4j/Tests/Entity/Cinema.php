<?php

namespace HireVoice\Neo4j\Tests\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use HireVoice\Neo4j\Annotation as OGM;

/**
 * @OGM\Entity
 */
class Cinema
{
    /**
     * @OGM\Auto
     */
    private $id;

    /**
     * @OGM\Property
     */
    private $name;

    /**
     * @OGM\ManyToMany(relation="presentedMovie")
     */
    private $presentedMovies;

    /**
     * @OGM\ManyToMany(writeOnly=true)
     */
    private $rejectedMovies;

    function __construct()
    {
        $this->presentedMovies = new ArrayCollection;
        $this->rejectedMovies = new ArrayCollection;
    }

    function getId()
    {
        return $this->id;
    }

    function setId($id)
    {
        $this->id = $id;
    }

    function getName()
    {
        return $this->name;
    }

    function setName($name)
    {
        $this->name = $name;
    }

    function getPresentedMovies()
    {
        return $this->presentedMovies;
    }

    function addPresentedMovie($movie)
    {
        $this->presentedMovies->add($movie);
    }

    function setPresentedMovies(ArrayCollection $movies)
    {
        $this->presentedMovies = $movies;
    }

    function getRejectedMovies()
    {
        return $this->rejectedMovies;
    }
}

