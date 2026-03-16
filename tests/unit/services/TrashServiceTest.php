<?php

namespace bitpart\assettrash\tests\unit\services;

use bitpart\assettrash\services\TrashService;
use PHPUnit\Framework\TestCase;

class TrashServiceTest extends TestCase
{
    private TrashService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrashService();
    }

    public function testGenerateTrashPathCreatesCorrectPath(): void
    {
        $uid = 'abc-123';
        $result = $this->service->generateTrashPath('.trash', $uid, 'photo.jpg');

        $this->assertSame('.trash/abc-123_photo.jpg', $result);
    }

    public function testGenerateTrashPathHandlesNestedFilename(): void
    {
        $uid = 'abc-123';
        // basename() should strip directory traversal
        $result = $this->service->generateTrashPath('.trash', $uid, '../../../etc/passwd');

        $this->assertSame('.trash/abc-123_passwd', $result);
    }

    public function testGenerateTrashPathHandlesFilenameWithSpaces(): void
    {
        $uid = 'abc-123';
        $result = $this->service->generateTrashPath('.trash', $uid, 'my photo.jpg');

        $this->assertSame('.trash/abc-123_my photo.jpg', $result);
    }

    public function testGenerateTrashPathHandlesCustomTrashDir(): void
    {
        $uid = 'abc-123';
        $result = $this->service->generateTrashPath('_deleted', $uid, 'file.pdf');

        $this->assertSame('_deleted/abc-123_file.pdf', $result);
    }

    public function testGenerateTrashPathHandlesFilenameWithMultipleExtensions(): void
    {
        $uid = 'abc-123';
        $result = $this->service->generateTrashPath('.trash', $uid, 'archive.tar.gz');

        $this->assertSame('.trash/abc-123_archive.tar.gz', $result);
    }

    public function testGenerateTrashPathStripsDirectoryFromFilename(): void
    {
        $uid = 'abc-123';
        $result = $this->service->generateTrashPath('.trash', $uid, 'subdir/nested/file.jpg');

        $this->assertSame('.trash/abc-123_file.jpg', $result);
    }
}
