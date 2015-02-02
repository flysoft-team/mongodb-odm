<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class ShardKeyTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $schemaManager = $this->dm->getSchemaManager();
        $schemaManager->ensureDocumentSharding(__NAMESPACE__ . '\ShardedOne');
    }

    /**
     * @group sharding
     */
    public function testUpdateAfterSave()
    {
        $queries = array();
        $this->logQueries($queries);

        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();

        /** @var ShardedOne $o */
        $o = $this->dm->find(get_class($o), $o->id);
        $o->title = 'test2';
        $this->dm->flush();

        $lastQuery = end($queries);
        $this->assertTrue($lastQuery['update']);
        $this->assertContains('k', array_keys($lastQuery['query']));
        $this->assertEquals($o->key, $lastQuery['query']['k']);
    }

    /**
     * @group sharding
     */
    public function testUpsert()
    {
        $queries = array();
        $this->logQueries($queries);

        $o = new ShardedOne();
        $o->id = new \MongoId();
        $this->dm->persist($o);
        $this->dm->flush();

        $lastQuery = end($queries);
        $this->assertTrue($lastQuery['update']);
        $this->assertContains('k', array_keys($lastQuery['query']));
        $this->assertEquals($o->key, $lastQuery['query']['k']);
    }

    /**
     * @group sharding
     */
    public function testRemove()
    {
        $queries = array();
        $this->logQueries($queries);

        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->remove($o);
        $this->dm->flush();

        $lastQuery = end($queries);
        $this->assertTrue($lastQuery['remove']);
        $this->assertContains('k', array_keys($lastQuery['query']));
        $this->assertEquals($o->key, $lastQuery['query']['k']);
    }

    /**
     * @group sharding
     */
    public function testRefresh()
    {
        $queries = array();
        $this->logQueries($queries);

        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();
        $this->dm->refresh($o);

        $lastQuery = end($queries);
        $this->assertTrue($lastQuery['findOne']);
        $this->assertContains('k', array_keys($lastQuery['query']));
        $this->assertEquals($o->key, $lastQuery['query']['k']);
    }

    /**
     * @group sharding
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testUpdateWithShardKeyChangeException()
    {
        $o = new ShardedOne();
        $this->dm->persist($o);
        $this->dm->flush();

        $o->key = 'testing2';
        $this->dm->flush();
    }

    /**
     * Replace DM with the one with enabled query logging
     *
     * @param $queries
     */
    private function logQueries(&$queries)
    {
        $this->dm->getConnection()->getConfiguration()->setLoggerCallable(
            function (array $log) use (&$queries) {
                $queries[] = $log;
            }
        );
        $this->dm = DocumentManager::create(
            $this->dm->getConnection(),
            $this->dm->getConfiguration()
        );
    }
}

/**
 * @ODM\Document
 * @ODM\ShardKey(keys={"k"="asc"})
 */
class ShardedOne
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $title = 'test';

    /** @ODM\String(name="k") */
    public $key = 'testing';
}