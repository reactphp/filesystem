<?php
namespace React\Filesystem;

use ReflectionClass;

class ChildProcessAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $loop;
    protected $invoker;
    protected $adapter;

    /**
     * Prepares the adapter instance and its mocks. Runs before each test
     * case
     */
    public function setUp()
    {
        // Mocks EventLoop
        $this->loop = $this->getMock(
            'React\EventLoop\LoopInterface'
        );
        // Mocks PooledInvoker
        $this->invoker = $this->getMock(
            'React\Filesystem\ChildProcess\PooledInvoker'
        );

        // Instantiate adapter and set invoker property as the mock
        $this->adapter = new ChildProcessAdapter($this->loop);
        $this->setProtected($this->adapter, 'invoker', $this->invoker);
    }

    public function testShouldGetLoop()
    {
        // Assertion
        $this->assertEquals(
            $this->loop,
            $this->adapter->getLoop()
        );
    }

    public function testShouldSetInvoker()
    {
        // Set
        $newInvoker = $this->getMock(
            'React\Filesystem\CallInvokerInterface'
        );

        $this->adapter->setInvoker($newInvoker);

        // Assertion
        $this->assertAttributeEquals(
            $newInvoker,
            'invoker',
            $this->adapter
        );
    }

    public function testShouldInvokeStat()
    {
        // Set
        $filename = 'john.doe';
        $promise = $this->getMock('React\Promise\PromiseInterface');

        // Expectation
        $this->invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with('stat', [$filename])
            ->willReturn($promise);

        // Assertion
        $this->assertEquals(
            $promise,
            $this->adapter->stat($filename)
        );
    }

    public function testShouldInvokeUnlink()
    {
        // Set
        $filename = 'john.doe';
        $promise = $this->getMock('React\Promise\PromiseInterface');

        // Expectation
        $this->invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with('unlink', [$filename])
            ->willReturn($promise);

        // Assertion
        $this->assertEquals(
            $promise,
            $this->adapter->unlink($filename)
        );
    }

    public function testShouldInvokeRename()
    {
        // Set
        $from = 'john.doe';
        $to = 'foo.bar';
        $promise = $this->getMock('React\Promise\PromiseInterface');

        // Expectation
        $this->invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with('rename', [$from, $to])
            ->willReturn($promise);

        // Assertion
        $this->assertEquals(
            $promise,
            $this->adapter->rename($from, $to)
        );
    }

    public function testShouldInvokeChmod()
    {
        // Set
        $filename = 'john.doe';
        $mode = 777;
        $promise = $this->getMock('React\Promise\PromiseInterface');

        // Expectation
        $this->invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with('chmod', [$filename, $mode])
            ->willReturn($promise);

        // Assertion
        $this->assertEquals(
            $promise,
            $this->adapter->chmod($filename, $mode)
        );
    }

    public function testShouldInvokeChown()
    {
        // Set
        $filename = 'john.doe';
        $uid = 'foo';
        $gid = 'bar';
        $promise = $this->getMock('React\Promise\PromiseInterface');

        // Expectation
        $this->invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with('chown', [$filename, $uid, $gid])
            ->willReturn($promise);

        // Assertion
        $this->assertEquals(
            $promise,
            $this->adapter->chown($filename, $uid, $gid)
        );
    }

    public function testShouldInvokeLs()
    {
        // Set
        $path = '/my/path';
        $flags = 23;
        $promise = $this->getMock('React\Promise\PromiseInterface');

        // Expectation
        $this->invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with('readdir', [$path, $flags])
            ->willReturn($promise);

        // Assertion
        $this->assertEquals(
            $promise,
            $this->adapter->ls($path, $flags)
        );
    }

    public function testShouldInvokeMkdir()
    {
        // Set
        $filename   = 'john.doe';
        $mode       = 'rwxrwxr-x';
        $permission = 775;
        $flagResolver = $this->getMock('React\Filesystem\FlagResolver');
        $promise      = $this->getMock('React\Promise\PromiseInterface');

        $this->setProtected($this->adapter, 'permissionResolver', $flagResolver);

        // Expectation
        $flagResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($mode)
            ->willReturn($permission);

        $this->invoker
            ->expects($this->once())
            ->method('invokeCall')
            ->with('mkdir', [$filename, $permission])
            ->willReturn($promise);

        // Assertion
        $this->assertEquals(
            $promise,
            $this->adapter->mkdir($filename, $mode)
        );
    }

    /**
     * Call a protected property of an object
     *
     * @param  mixed $obj
     * @param  string $attribute Property name
     * @param  mixed  $value Value to be set
     */
    protected function setProtected($obj, $property, $value)
    {
        $class = new ReflectionClass($obj);
        $property = $class->getProperty($property);
        $property->setAccessible(true);

        if (is_string($obj)) { // static
            $property->setValue($value);
            return;
        }

        $property->setValue($obj, $value);
    }
}
