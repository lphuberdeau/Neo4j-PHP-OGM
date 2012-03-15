<?php

namespace HireVoice\Neo4j\Tests\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use HireVoice\Neo4j\Annotation as OGM;

/**
 * @OGM\Entity
 */
class Movie
{
    /**
     * @OGM\Auto
     */
    private $id;

    /**
     * @OGM\Property
     */
    private $title;

    /**
     * @OGM\Property(format="date")
     */
    private $releaseDate;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    private $movieRegistryCode;

    /**
     * @OGM\ManyToMany
     */
    private $actors;

    /**
     * @OGM\ManyToOne
     */
    private $mainActor;

    /**
     * @OGM\ManyToMany(readOnly=true, relation="presentedMovie")
     */
    private $cinemas;

    function __construct()
    {
        $this->actors = new ArrayCollection;
        $this->cinemas = new ArrayCollection;
        $this->movieRegistryCode = uniqid();
    }

    function getId()
    {
        return $this->id;
    }

    function setId($id)
    {
        $this->id = $id;
    }

    function getTitle()
    {
        return $this->title;
    }

    function setTitle($title)
    {
        $this->title = $title;
    }

    function getReleaseDate()
    {
        return $this->releaseDate;
    }

    function setReleaseDate($date)
    {
        $this->releaseDate = $date;
    }

    function getMovieRegistryCode()
    {
        return $this->movieRegistryCode;
    }

    function setMovieRegistryCode($code)
    {
        $this->movieRegistryCode = $code;
    }

    function getActors()
    {
        return $this->actors;
    }

    function addActor($actor)
    {
        $this->actors->add($actor);
    }

    function setActors(ArrayCollection $actors)
    {
        $this->actors = $actors;
    }

    function getCinemas()
    {
        return $this->cinemas;
    }

    function addCinema($cinema)
    {
        $this->cinemas->add($cinema);
    }

    function setCinemas(ArrayCollection $cinemas)
    {
        $this->cinemas = $cinemas;
    }

    function setMainActor($actor)
    {
        $this->mainActor = $actor;
    }

    function getMainActor()
    {
        return $this->mainActor;
    }
}

