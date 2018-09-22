<?php

namespace React\Tests\Filesystem;

use Throwable;
use Clue\React\Block;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;
use React\Filesystem\InstantInvoker;
use React\Promise\RejectedPromise;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    const TIMEOUT = 30;

    /** @var LoopInterface */
    public $loop;

    protected $tmpDir;
    protected $startTime;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'react-filesystem-tests' . DIRECTORY_SEPARATOR . uniqid('', true);
        mkdir($this->tmpDir, 0777, true);
        $this->tmpDir = realpath($this->tmpDir) . DIRECTORY_SEPARATOR; // Fixing a windows "feature"

        $this->startTime = time();
    }

    public function setUp()
    {
        $this->loop = Factory::create();
    }

    protected function setLoopTimeout(LoopInterface $loop, $maxExecutionTime = self::TIMEOUT)
    {
        return $loop->addTimer($maxExecutionTime, function () use ($loop) {
            $loop->stop();
            $this->fail('Event loop timeout');
        });
    }

    protected function await(PromiseInterface $promise, LoopInterface $loop, $timeout = self::TIMEOUT)
    {
        $result = Block\await($promise, $loop, $timeout);
        return $result;
    }

    public function mockPromise($value = null)
    {
        $mock = $this->getMockBuilder(FulfilledPromise::class)
            ->setMethods([
                'then',
            ])
            ->getMock();

        $mock
            ->expects($this->any())
            ->method('then')->with($this->isType('callable'))
            ->will($this->returnCallback(function ($cb) use (&$value) {
                $val = $cb($value);
                if ($val instanceof PromiseInterface) {
                    return $val;
                }
                
                return $this->mockPromise($val);
            }));

        return $mock;
    }

    public function mockRejectedPromise(Throwable $value)
    {
        $mock = $this->getMockBuilder(RejectedPromise::class)
            ->setMethods([
                'then',
            ])
            ->getMock();

        $mock
            ->expects($this->any())
            ->method('then')
            ->with($this->isType('callable'), $this->isType('callable'))
            ->will($this->returnCallback(function ($_cb, $cb) use (&$value) {
                $val = $cb($value);
                if ($val instanceof PromiseInterface) {
                    return $val;
                }
                
                return $this->mockRejectedPromise($val);
            }));

        return $mock;
    }

    protected function mockAdapter()
    {
        $mock = $this->getMockBuilder('React\Filesystem\AdapterInterface')
            ->setMethods([
                '__construct',
                'getLoop',
                'getFilesystem',
                'setFilesystem',
                'getInvoker',
                'setInvoker',
                'callFilesystem',
                'isSupported',
                'mkdir',
                'rmdir',
                'unlink',
                'chmod',
                'chown',
                'stat',
                'ls',
                'touch',
                'open',
                'read',
                'write',
                'close',
                'rename',
                'readlink',
                'symlink',
                'detectType',
            ])
            ->setConstructorArgs([
                $this->loop,
            ])
            ->getMock();

        $fs = Filesystem::createFromAdapter($mock);
        
        $mock->expects($this->any())
            ->method('getLoop')
            ->with()
            ->will($this->returnValue($this->loop));
        
        $mock->expects($this->any())
            ->method('getFilesystem')
            ->with()
            ->will($this->returnValue($fs));
        
        $invoker = new InstantInvoker($mock);
        $mock->expects($this->any())
            ->method('getInvoker')
            ->with()
            ->will($this->returnCallback(function () use (&$invoker) {
                return $invoker;
            }));
            
        $mock->expects($this->any())
            ->method('setInvoker')
            ->will($this->returnCallback(function ($newInvoker) use (&$invoker) {
                $invoker = $newInvoker;
            }));
        
        $mock->expects($this->any())
            ->method('callFilesystem')
            ->will($this->returnValue($this->mockPromise()));
        
        $mock->expects($this->any())
            ->method('isSupported')
            ->will($this->returnValue(true));
            
        return $mock;
    }
}
