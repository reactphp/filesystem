<?php

namespace React\Filesystem\Node;

use React\Promise\Deferred;

class RecursiveInvoker
{

    protected $node;

    /**
     * @param DirectoryInterface $node
     */
    public function __construct(DirectoryInterface $node)
    {
        $this->node = $node;
    }

    /**
     * @param string $method
     * @param array $args
     * @return \React\Promise\Promise
     */
    public function execute($method, $args)
    {
        $deferred = new Deferred();

        $this->node->ls()->then(function ($list) use ($deferred, $method, $args) {
            $this->node->getFilesystem()->getLoop()->futureTick(function () use ($list, $deferred, $method, $args) {
                $this->iterateNode($list, $deferred, $method, $args);
            });
        });

        return $deferred->promise();
    }

    /**
     * @param array $list
     * @param Deferred $deferred
     * @param string $method
     * @param array $args
     */
    protected function iterateNode($list, $deferred, $method, $args)
    {
        $promises = [];

        foreach ($list as $node) {
            if ($node instanceof Directory) {
                $promises[] = call_user_func_array([$node, $method . 'Recursive'], $args);
            } else {
                $promises[] = call_user_func_array([$node, $method], $args);
            }
        }

        \React\Promise\all($promises)->then(function () use ($deferred, $method, $args) {
            call_user_func_array([$this->node, $method], $args)->then(function () use ($deferred) {
                $deferred->resolve();
            });
        });
    }
}
