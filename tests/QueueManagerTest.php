<?php

use BostjanOb\QueuePlatform\QueueManager;

require_once 'StorageStub.php';

class DummyWorker implements \BostjanOb\QueuePlatform\Worker
{
    public function run($params = null)
    {
        return $params[0] - $params[1];
    }
}

class QueueManagerTest extends \PHPUnit\Framework\TestCase
{

    public function testQueueTaskWithoutWorkerThrowException()
    {
        $this->expectException(InvalidArgumentException::class);

        $manager = new QueueManager(new StorageStub());
        $manager->queueTask('foo', [1, 2, 3]);
    }

    public function testTaskIsAddedToStorage()
    {
        $storage = new StorageStub();
        $manager = new QueueManager($storage);
        $manager->registerWorker('foo', new DummyWorker());
        $task = $manager->queueTask('foo', [1, 2, 3]);

        $this->assertEquals(0, $task->getId());
        $this->assertEquals('foo', $task->getName());
        $this->assertEquals([1, 2, 3], $task->getParams());

        $storageTask = $storage->get(0);
        $this->assertEquals('foo', $storageTask->getName());
    }

    public function testTaskIsReturned()
    {
        $manager = new QueueManager(new StorageStub());
        $manager->registerWorker('foo', new DummyWorker());
        $t = $manager->queueTask('foo', [1, 2, 3]);

        $task = $manager->getTask($t->getId());

        $this->assertFalse($task->isCompleted());
        $this->assertEquals(0, $task->getId());
    }

    public function testTaskIsCompleted()
    {
        $storage = new StorageStub();
        $manager = new QueueManager($storage);
        $manager->registerWorker('foo', new DummyWorker());
        $task = $manager->queueTask('foo', [1, 2, 3]);

        $manager->completeTask($task->getId(), 3);

        $storageTask = $storage->get($task->getId());
        $this->assertEquals('foo', $storageTask->getName());
        $this->assertTrue($storageTask->isCompleted());
        $this->assertEquals(3, $storageTask->getResult());
    }

}
