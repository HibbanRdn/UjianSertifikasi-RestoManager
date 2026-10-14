<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestFileTest extends TestCase
{
    public function testReturnsOnlyArrayFilePayloads(): void
    {
        $file = ['name' => 'menu.png', 'error' => UPLOAD_ERR_OK];
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST'], ['image' => $file, 'invalid' => 'string']);

        self::assertSame($file, $request->file('image'));
        self::assertNull($request->file('invalid'));
        self::assertNull($request->file('missing'));
    }
}
