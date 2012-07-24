<?php

namespace HireVoice\Neo4j;
use Everyman\Neo4j\Client;

class Configuration
{
	private $host = 'localhost';
	private $port = 7474;
	private $proxyDir = '/tmp';
	private $debug = false;
	private $annotationReader;

	function __construct(array $configs = array())
	{
		if (isset($configs['host'])) {
			$this->host = $configs['host'];
		}

		if (isset($configs['port'])) {
			$this->port = (int) $configs['port'];
		}

		if (isset($configs['debug'])) {
			$this->debug = (bool) $configs['debug'];
		}

		if (isset($configs['proxy_dir'])) {
			$this->proxyDir = $configs['proxy_dir'];
		}

		if (isset($configs['annotation_reader'])) {
			$this->annotationReader = $configs['annotation_reader'];
		}
	}

	function getClient()
	{
		return new Client($this->host, $this->port);
	}

	function getProxyFactory()
	{
		return new Proxy\Factory($this->proxyDir, $this->debug);
	}

	function getMetaRepository()
	{
		return new Meta\Repository($this->annotationReader);
	}
}

