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
class Book
{
    /**
     * @OGM\Auto
     */
    protected $id;
    
    /**
     * @OGM\Property
     */
    protected $name;

    /**
     * @OGM\ManyToMany(relation="BasedOn", direction="from")
     */
    protected $sagas;

    function __construct()
    {
        $this->sagas = new ArrayCollection;
    }

    function getId()
    {
        return $this->id;
    }

    function setId($id)
    {
        $this->id = $id;
    }
    
    /**
     * @param mixed $name
     *
     * @return Book 
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed 
     */
    public function getName()
    {
        return $this->name;
    }

    function getSagas()
    {
        return $this->sagas;
    }

    function addSaga($saga)
    {
        $this->sagas->add($saga);
    }

    function setSagas(ArrayCollection $sagas)
    {
        $this->sagas = $sagas;
    }

}

