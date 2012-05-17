<?php

namespace HireVoice\Neo4j;

interface EntityProxy
{
    function getEntity();

    function __addHydrated($name);

    function __setMeta($meta);

    function __setNode($node);

    function __setRepository($repository);

    function __setProxyFactory($repository);
}

