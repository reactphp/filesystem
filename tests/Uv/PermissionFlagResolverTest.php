<?php

namespace React\Tests\Filesystem\Uv;

use UV;
use React\Filesystem\Uv\PermissionFlagResolver;
use React\Tests\Filesystem\TestCase;

/**
 * @requires ext uv
 */
class PermissionFlagResolverTest extends TestCase
{
    public function testResolveProvider()
    {
        return [
            [
                'r--------',
                UV::S_IROTH,
            ],
            [
                '-w-------',
                UV::S_IWOTH,
            ],
            [
                '--x------',
                UV::S_IXOTH,
            ],
            [
                '---r-----',
                UV::S_IRGRP,
            ],
            [
                '----w----',
                UV::S_IWGRP,
            ],
            [
                '-----x---',
                UV::S_IXGRP,
            ],
            [
                '------r--',
                UV::S_IRUSR,
            ],
            [
                '-------w-',
                UV::S_IWUSR,
            ],
            [
                '--------x',
                UV::S_IXUSR,
            ],
            [
                'rwxrwxrwx',
                (UV::S_IRWXU | UV::S_IRWXG | UV::S_IRWXO),
            ],
            [
                '-wxrwxrwx',
                (UV::S_IRWXU | UV::S_IRWXG | UV::S_IWOTH | UV::S_IXOTH),
            ],
            [
                'r-xrwxrwx',
                (UV::S_IRWXU | UV::S_IRWXG | UV::S_IROTH | UV::S_IXOTH),
            ],
            [
                'rw-rwxrwx',
                (UV::S_IRWXU | UV::S_IRWXG | UV::S_IROTH | UV::S_IWOTH),
            ],
            [
                'rwx-wxrwx',
                (UV::S_IRWXU | UV::S_IWGRP | UV::S_IXGRP | UV::S_IRWXO),
            ],
            [
                'rwxr-xrwx',
                (UV::S_IRWXU | UV::S_IRGRP | UV::S_IXGRP | UV::S_IRWXO),
            ],
            [
                'rwxrw-rwx',
                (UV::S_IRWXU | UV::S_IRGRP | UV::S_IWGRP | UV::S_IRWXO),
            ],
            [
                'rwxrwx-wx',
                (UV::S_IWUSR | UV::S_IXUSR | UV::S_IRWXG | UV::S_IRWXO),
            ],
            [
                'rwxrwxr-x',
                (UV::S_IRUSR | UV::S_IXUSR | UV::S_IRWXG | UV::S_IRWXO),
            ],
            [
                'rwxrwxrw-',
                (UV::S_IRUSR | UV::S_IWUSR | UV::S_IRWXG | UV::S_IRWXO),
            ],
            [
                'rw-rw-rw-',
                (UV::S_IRUSR | UV::S_IWUSR | UV::S_IRGRP | UV::S_IWGRP | UV::S_IROTH | UV::S_IWOTH),
            ],
            [
                'rwxrwx---',
                (UV::S_IRWXU | UV::S_IRWXG),
            ],
            [
                'rw-rw-r--',
                (UV::S_IRUSR | UV::S_IWUSR | UV::S_IRGRP | UV::S_IWGRP | UV::S_IROTH),
            ],
        ];
    }

    /**
     * @dataProvider testResolveProvider
     */
    public function testResolve($flags, $result)
    {
        $resolver = new PermissionFlagResolver();
        $this->assertSame($result, $resolver->resolve($flags));
    }
}
