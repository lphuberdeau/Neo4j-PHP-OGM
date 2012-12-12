<?php
namespace HireVoice\Neo4j;

use Everyman\Neo4j\Relationship;

class PathFinder
{
	protected $entityManager;

	protected $client;

	protected $startNode;

	protected $endNode;

	protected $maxDepth = null;

	protected $algorithm = null;

	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
		$this->client = $entityManager->getClient();
	}

	public function setStartEntity($entity)
	{
		$this->startNode = $this->client->getNode($entity->getId());

		return $this;
	}

	public function setEndEntity($entity)
	{
		$this->endNode = $this->client->getNode($entity->getId());

		return $this;
	}

	public function setMaxDepth($depth)
	{
		$this->maxDepth = $depth;

		return $this;
	}

	public function setAlgorithm($algorithm)
	{
		$this->algorithm = $algorithm;

		return $this;
	}

	public function findPaths()
	{
		$paths = $this->preparePaths()->getPaths();

		$pathObjects = array();
		foreach ($paths as $path){
			$pathObjects[] = new Path($path, $this->entityManager);
		}

		return $pathObjects;
	}

	public function findSinglePath()
	{
		$path = $this->preparePaths()->getSinglePath();

		return new Path($path, $this->entityManager);
	}

	protected function preparePaths()
	{
		if (null === $this->relationship){
			$paths = $this->startNode->findPathsTo($this->endNode);
		} else {
			$paths = $this->startNode->findPathsTo($this->endNode, $this->relationship);
		}

		if ($this->maxDepth !== null) $paths->setMaxDepth($this->maxDepth);
		if ($this->algorithm !== null) $paths->setAlgorithm($this->algorithm);

		return $paths;
	}

}

