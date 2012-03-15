<?php

spl_autoload_register(function ($className) {
    $parts = explode('\\', $className);

    if (reset($parts) == 'HireVoice') {
        require __DIR__ . '/lib/' . implode('/', $parts) . '.php';
    } elseif (reset($parts) == 'Entity') {
        require __DIR__ . '/tests/' . implode('/', $parts) . '.php';
    } elseif (reset($parts) == 'Doctrine') {
        require __DIR__ . '/vendor/doctrine-common/lib/' . implode('/', $parts) . '.php';
    } elseif (reset($parts) == 'Everyman') {
        require __DIR__ . '/vendor/neo4jphp/lib/' . implode('/', $parts) . '.php';
    }
});

require __DIR__ . '/lib/HireVoice/Neo4j/Annotation/Entity.php';
require __DIR__ . '/lib/HireVoice/Neo4j/Annotation/Auto.php';
require __DIR__ . '/lib/HireVoice/Neo4j/Annotation/Property.php';
require __DIR__ . '/lib/HireVoice/Neo4j/Annotation/Index.php';
require __DIR__ . '/lib/HireVoice/Neo4j/Annotation/ManyToOne.php';
require __DIR__ . '/lib/HireVoice/Neo4j/Annotation/ManyToMany.php';
