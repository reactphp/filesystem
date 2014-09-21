<?php

namespace React\Filesystem;

abstract class FlagResolver
{

    public function resolve($flagString)
    {
        $flags = static::DEFAULT_FLAG;

        $flagString = str_split($flagString);
        foreach ($flagString as $flag) {
            if (isset($this->flagMapping[$flag])) {
                $flags |= $this->flagMapping[$flag];
            }
        }

        return $flags;
    }
}
