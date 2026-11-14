<?php

declare(strict_types=1);

namespace Wexample\PhpFile\Tests\Unit\Class;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Wexample\PhpFile\Class\FileSystemItemScanner;
use Wexample\PhpFile\Enum\FileSystemItemType;

class FileSystemItemScannerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/php-file-scanner-'.uniqid();
        mkdir($this->root.'/docs/empty', 0777, true);
        file_put_contents($this->root.'/docs/a.txt', 'hello');
        file_put_contents($this->root.'/outside.txt', 'out');
    }

    protected function tearDown(): void
    {
        unlink($this->root.'/docs/a.txt');
        unlink($this->root.'/outside.txt');
        rmdir($this->root.'/docs/empty');
        rmdir($this->root.'/docs');
        rmdir($this->root);
    }

    public function testALevelIsDescribedRelativeToTheRoot(): void
    {
        $scanner = new FileSystemItemScanner($this->root);

        $items = $scanner->scanLevel('docs');
        usort($items, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        $this->assertSame(['docs/a.txt', 'docs/empty'], array_column($items, FileSystemItemScanner::KEY_PATH));
        $this->assertSame(FileSystemItemType::FILE, $items[0][FileSystemItemScanner::KEY_TYPE]);
        $this->assertSame(5, $items[0][FileSystemItemScanner::KEY_SIZE]);
        $this->assertSame(FileSystemItemType::DIRECTORY, $items[1][FileSystemItemScanner::KEY_TYPE]);
        $this->assertFalse($items[1][FileSystemItemScanner::KEY_HAS_CHILDREN]);
        $this->assertTrue($scanner->scanOne('docs')[FileSystemItemScanner::KEY_HAS_CHILDREN]);
    }

    public function testNothingOutsideTheRootIsReached(): void
    {
        $scanner = new FileSystemItemScanner($this->root.'/docs');

        $this->assertSame([], $scanner->scanLevel('..'));
        $this->assertNull($scanner->scanOne('../outside.txt'));
        // A traversal landing back inside is inside.
        $this->assertSame('a.txt', $scanner->scanOne('../docs/a.txt')[FileSystemItemScanner::KEY_PATH]);
        $this->assertNull($scanner->scanOne('missing.txt'));
        // The name itself stepping out: the holder is the root, `..` is above it.
        $this->assertNull($scanner->scanOne('..'));
        $this->assertNull($scanner->scanOne('a.txt/..'));
    }

    public function testAMissingRootIsRefused(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new FileSystemItemScanner($this->root.'/missing'))->getAbsoluteRootPath();
    }
}
