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
    protected $id;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $title;

    /**
     * @OGM\Property(format="array")
     */
    protected $alternateTitles;

    /**
     * @OGM\Property
     */
    protected $category;

    /**
     * @OGM\Property(format="date")
     */
    protected $releaseDate;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $movieRegistryCode;

    /**
     * @OGM\ManyToMany
     */
    protected $actors;

    /**
     * @OGM\ManyToOne
     */
    protected $mainActor;

    /**
     * @OGM\ManyToMany(readOnly=true, relation="presentedMovie")
     */
    protected $cinemas;

    /**
     * @OGM\Property(format="json")
     */
    protected $blob;

    function __construct()
    {
        $this->alternateTitles = array();
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

    function addTitle($part)
    {
        $this->title .= $part;
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

    function removeActor($actor)
    {
        $this->actors->removeElement($actor);
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

    function getBlob()
    {
        return $this->blob;
    }

    function setBlob($blob)
    {
        $this->blob = $blob;
    }

    function setCategory($category)
    {
        $this->category = $category;
    }

    function getCategory()
    {
        return $this->category;
    }

    public function setAlternateTitles(array $alternateTitles)
    {
        $this->alternateTitles = array();
        foreach ($alternateTitles as $alternateTitle) {
            $this->addAlternateTitle($alternateTitle);
        }
    }

    public function addAlternateTitle($alternateTitle)
    {
        $this->alternateTitles[] = $alternateTitle;
    }

    public function getAlternateTitles()
    {
        return $this->alternateTitles;
    }
}

