<?php
namespace HireVoice\Neo4j\Extension;

class ArrayCollection extends \Doctrine\Common\Collections\ArrayCollection
{
    private $tracking = array();

    public function removeElement($element)
    {
        if (parent::removeElement($element)) {
            $this->tracking[] = $element;
            return true;
        }

        return false;
    }

    public function getRemovedElements()
    {
        $list = $this;
        return array_filter($this->tracking, function ($target) use ($list) {
            return ! $list->contains($target);
        });
    }
}

