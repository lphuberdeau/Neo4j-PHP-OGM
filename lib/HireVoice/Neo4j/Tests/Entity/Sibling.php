<?php
namespace HireVoice\Neo4j\Tests\Entity;
use HireVoice\Neo4j\Annotation as OGM;

/**
 * @OGM\Relation
 */
class Sibling
{
	/**
	 * @OGM\Auto
	 */
	protected $id;

	/**
	 * @OGM\Start
	 */
	protected $from;

	/**
	 * @OGM\End
	 */
	protected $to;

	/**
	 * @OGM\Property
	 */
	protected $type;

	function getFrom()
	{
		return $this->from;
	}

	function setFrom(User $from)
	{
		$this->from = $from;
	}

	function getTo()
	{
		return $this->to;
	}

	function setTo(User $to)
	{
		$this->to = $to;
	}

	function getType()
	{
		return $this->type;
	}

	function setType($type)
	{
		$this->type = $type;
	}
}

