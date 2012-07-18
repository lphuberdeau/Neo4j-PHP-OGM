About
=====

The Neo4j PHP Object Graph Mapper is an object management layer built on top of everyman/neo4jphp.
It allows manipulation of data inside the Neo4j graph database through the REST connectors.

The library is also based on Doctrine\Common and borrows significantly from the excellent Doctrine\ORM
design.

Released under the MIT Licence.

Created by Louis-Philippe Huberdeau for HireVoice Inc., the library was extracted from the project's
codebase into its own Open Source project. Feel free to use, comment and participate.

Running tests
=============

* Dependencies must be loaded with Composer
* A running instance of neo4j must be running on localhost:7474
* Use PHPUnit.

Basic Usage
===========

In order to store and retrieve information using the library, you must declare your entities.
If you have used Doctrine2 before, this is a very similar process.

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

Using the library is very simple.

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

Complex queries can also be made.

    $em = $this->get('hirevoice.neo4j.entity_manager');
    $john = $repo->findOneByFullName('John Doe');

    $list = $em->createCypherQuery()
        ->startWithNode('john', $john)
        ->match('john -[:follow]-> followedBy <-[:follow]- similarInterest')
        ->match('similarInterest -[:follow]-> potentialMatch')
        ->end('potentialMatch', 'count(*)')
        ->order('count(*) DESC')
        ->limit(10)
        ->getList();

    // $list is a collection of User objects

Just as in Doctrine, it would be better to move this query to a repository.

    <?php
    namespace Repository;
    use HireVoice\Neo4j\Repository as BaseRepository;

    class UserRepository extends BaseRepository
    {
        function findRecommendations(User $user)
        {
            return $em->createCypherQuery()
                ->startWithNode('user', $user)
                ->match('user -[:follow]-> followedBy <-[:follow]- similarInterest')
                ->match('similarInterest -[:follow]-> potentialMatch')
                ->end('potentialMatch', 'count(*)')
                ->order('count(*) DESC')
                ->limit(10)
                ->getList();
        }
    }

The Entity annotation would need to be modified to point to the custom repository classs:

    /**
     * @OGM\Entity(repositoryClass="Repository\UserRepository")
     */

Initialize the EntityManager
============================

Ideally, this would be done through DependencyInjection in your application. Here is the
procedural creation.

    $client = new Everyman\Neo4j\Client('localhost', 7474);
    $metaRepository = new HireVoice\Neo4j\MetaRepository; // Ideally, a cached Doctrine\Common\Annotations\Reader is provided as an argument
    $em = new HireVoice\Neo4j\EntityManager($client, $metaRepository);

    // for debugging purposes or to change the cache location, you can specify the ProxyFactory
    // $em->setProxyFactory(new HireVoice\Neo4j\ProxyFactory('/tmp', true));
