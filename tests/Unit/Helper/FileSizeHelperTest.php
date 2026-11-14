<?php

declare(strict_types=1);

namespace Wexample\PhpFile\Tests\Unit\Helper;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Wexample\PhpFile\Helper\FileSizeHelper;

class FileSizeHelperTest extends TestCase
{
    public static function writtenSizes(): array
    {
        return [
            'bare number' => ['512', 512],
            'int' => [2048, 2048],
            'bytes' => ['512 B', 512],
            'short unit' => ['1K', 1024],
            'english' => ['10MB', 10 * 1024 ** 2],
            'french' => ['2 Go', 2 * 1024 ** 3],
            'lowercase' => ['3 kb', 3 * 1024],
            'decimal' => ['1.5 KB', 1536],
            'spaces around' => ['  4 TB ', 4 * 1024 ** 4],
            'peta' => ['1PB', 1024 ** 5],
        ];
    }

    #[DataProvider('writtenSizes')]
    public function testAWrittenSizeIsReadInBytes(string|int $written, int $bytes): void
    {
        $this->assertSame($bytes, FileSizeHelper::parse($written));
    }

    public function testATextSayingNothingUsableIsNull(): void
    {
        $this->assertNull(FileSizeHelper::parse(''));
        $this->assertNull(FileSizeHelper::parse('abc'));
        $this->assertNull(FileSizeHelper::parse('10 XB'));
        $this->assertNull(FileSizeHelper::parse('-5MB'));
    }

    public function testASizeIsFormattedForDisplay(): void
    {
        $this->assertSame('0 B', FileSizeHelper::format(0));
        $this->assertSame('1023 B', FileSizeHelper::format(1023));
        $this->assertSame('1 KB', FileSizeHelper::format(1024));
        $this->assertSame('1.5 KB', FileSizeHelper::format(1536));
        $this->assertSame('10 MB', FileSizeHelper::format(10 * 1024 ** 2));
        // The ladder stops at its last step.
        $this->assertSame('1024 PB', FileSizeHelper::format(1024 ** 6));
    }

    public function testWhatIsFormattedReadsBackAsTheSameSize(): void
    {
        foreach ([0, 1, 512, 1024, 1536, 10 * 1024 ** 2, 3 * 1024 ** 3, 1024 ** 5] as $bytes) {
            $this->assertSame($bytes, FileSizeHelper::parse(FileSizeHelper::format($bytes)), (string) $bytes);
        }
    }
}
