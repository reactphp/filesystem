<?php

namespace React\Filesystem\Node;

class RecursiveInvoker
{
    /**
     * @var DirectoryInterface
     */
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
        return $this->node->ls()->then(function ($list) use ($method, $args) {
            return $this->iterateNode($list, $method, $args);
        });
    }

    /**
     * @param $list
     * @param $method
     * @param $args
     * @return \React\Promise\PromiseInterface
     */
    protected function iterateNode($list, $method, $args)
    {
        $promises = [];

        foreach ($list as $node) {
            if ($node instanceof Directory) {
                $promises[] = call_user_func_array([$node, $method . 'Recursive'], $args);
            } else {
                $promises[] = call_user_func_array([$node, $method], $args);
            }
        }

        return \React\Promise\all($promises)->then(function () use ($method, $args) {
            return call_user_func_array([$this->node, $method], $args);
        });
    }
}
