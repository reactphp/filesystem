<?php

namespace React\Filesystem;

abstract class FlagResolver
{

    public function resolve($flagString, $flags = null, $mapping = null)
    {
        if ($flags === null) {
            $flags = $this->defaultFlags();
        }

        if ($mapping === null) {
            $mapping = $this->flagMapping();
        }

        $flagString = str_split($flagString);
        foreach ($flagString as $flag) {
            if (isset($mapping[$flag])) {
                $flags |= $mapping[$flag];
            }
        }

        return $flags;
    }
}
