<?php
namespace HireVoice\Neo4j;

use Everyman\Neo4j\Path as RawPath;
use Doctrine\Common\Collections\ArrayCollection;

class Path
{
	protected $entities;

	protected $entityManager;

	public function __construct(RawPath $path, EntityManager $entityManager)
	{
		$this->entities = new ArrayCollection();

		foreach ($path as $node){
			$this->entities->add($entityManager->load($node));
		}
	}

	public function getEntities()
	{
		return $this->entities;
	}
}