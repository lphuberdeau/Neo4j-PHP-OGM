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
use HireVoice\Neo4j\Annotation as OGM;

/**
 * @OGM\Entity(labels="Pasta")
 */
class Pasta
{
    /**
     * @OGM\Auto
     */
    protected $strangePrimaryKey;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $name;
    
    /**
     * @OGM\ManyToMany(relation="ingredient")
     */
    protected $olives;
    
    /**
     * @OGM\ManyToOne(relation="base_ingredient")
     */
    protected $tomato;

    /**
    * Constructor that initializes references
    * 
    */
    public function __construct(){
        $this->olives = new \Doctrine\Common\Collections\ArrayCollection();
    }
    /**
     * @param mixed $strangePrimaryKey
     *
     * @return Pasta 
     */
    public function setStrangePrimaryKey($strangePrimaryKey)
    {
        $this->strangePrimaryKey = $strangePrimaryKey;
        return $this;
    }

    /**
     * @return mixed 
     */
    public function getStrangePrimaryKey()
    {
        return $this->strangePrimaryKey;
    }

    /**
     * @param mixed $name
     *
     * @return Pasta 
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $olives
     *
     * @return Pasta 
     */
    public function setOlives($olives)
    {
        $this->olives = $olives;
        return $this;
    }

    /**
     * @return mixed 
     */
    public function getOlives()
    {
        return $this->olives;
    }

    /**
    * 
    * @param 
    */
    public function addOlive($olive)
    {
        $this->olives[] = $olive;
    }
    /**
     * @param mixed $tomato
     *
     * @return Pasta 
     */
    public function setTomato($tomato)
    {
        $this->tomato = $tomato;
        return $this;
    }

    /**
     * @return mixed 
     */
    public function getTomato()
    {
        return $this->tomato;
    }

}

