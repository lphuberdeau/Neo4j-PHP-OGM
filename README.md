[![Build Status](https://travis-ci.org/lphuberdeau/Neo4j-PHP-OGM.svg?branch=master)](https://travis-ci.org/lphuberdeau/Neo4j-PHP-OGM)

## About

The Neo4j PHP Object Graph Mapper is an object management layer built on top of everyman/neo4jphp.
It allows manipulation of data inside the Neo4j graph database through the REST connectors.

The library is also based on Doctrine\Common and borrows significantly from the excellent Doctrine\ORM
design.

Released under the MIT Licence.

Created by Louis-Philippe Huberdeau for HireVoice Inc., the library was extracted from the project's
codebase into its own Open Source project. Feel free to use, comment and participate.

## Basic Usage

In order to store and retrieve information using the library, you must declare your entities.
If you have used Doctrine2 before, this is a very similar process.
```php
<?php
namespace Entity;

use HireVoice\Neo4j\Annotation as OGM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * All entity classes must be declared as such.
 *
 * @OGM\Entity
 */
class User
{
    /**
     * The internal node ID from Neo4j must be stored. Thus an Auto field is required
     * @OGM\Auto
     */
    protected $id;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $fullName;

    /**
     * @OGM\ManyToMany
     */
    protected $follows;

    /**
     * @OGM\ManyToOne
     */
    protected $referrer;

    function __construct()
    {
        $this->friends = new ArrayCollection;
    }

    /* Add your accessors here */
}
```

### Storing entities into the graph database

```php
// Let's assume the entity manager is initialized. More on this later.
$em = $this->get('hirevoice.neo4j.entity_manager');
$repo = $em->getRepository('Entity\\User');

// The repository uses magic functions to search in indexed fields
$john = $repo->findOneByFullName('John Doe');

$jane = new User;
$jane->setFullName('Jane Doe');

$jane->addFollow($john);

$em->persist($jane);
$em->flush(); // Stores both Jane and John, along with the new relation

$em->remove($john);
$em->flush(); // Removes John and the relation to Jane
```

### Fetching entities from the database

```php
$em = $this->get('hirevoice.neo4j.entity_manager');
$repository = $em->getRepository('Entity\\User');

// Find a User by a specific field
$user = $repository->findOneByFullName('superman'); // Returns a User object

// Find some users by a specific field
$usersFromFrance = $repository->findByCountry('FR'); // Returns a collection of User object

// Find one User with more than one criteria
$nonActiveWithSuchEmail = $repository->findOneBy(array('status' => 'idle', 'email' => 'superman@chucknorris.com'));

// Find Multiple Users with more than one criteria
$activeUsersFromFrance = $repository->findBy(array('status' => 'active', 'country' => 'FR'));
```

## Initialize the EntityManager

Ideally, this would be done through DependencyInjection in your application. Here is the
procedural creation.
```php
$em = new HireVoice\Neo4j\EntityManager(array(
    // 'transport' => 'curl', // or 'stream'
    // 'host' => 'localhost',
    // 'port' => 7474,
    // 'username' => null,
    // 'password' => null,
    // 'proxy_dir' => '/tmp',
    // 'debug' => true, // Force proxy regeneration on each request
    // 'annotation_reader' => ... // Should be a cached instance of the doctrine annotation reader in production
));
```

## Full Documentation

To get the full documentation, see the [doc directory](doc/index.md)

