<?php

/**
 * File upload service tests.
 */

namespace App\Tests\Service;

use App\Service\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Class FileUploadServiceTest.
 */
class FileUploadServiceTest extends KernelTestCase
{
    /**
     * File upload service instance.
     *
     * @var FileUploadService|null
     */
    private ?FileUploadService $fileUploadService;

    /**
     * Set up test environment.
     *
     * Initializes FileUploadService with real slugger and temp directory.
     *
     * @return void
     */
    public function setUp(): void
    {
        $slugger = static::getContainer()->get(SluggerInterface::class);

        $this->fileUploadService = new FileUploadService(
            sys_get_temp_dir(),
            $slugger
        );
    }

    /**
     * Test successful file upload.
     *
     * Ensures file is moved to target directory and filename is returned.
     *
     * @return void
     */
    public function testUpload(): void
    {
        // given
        // create empty temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');

        // add content to temp file
        file_put_contents($tempFile, 'avatar');

        // simulate browser upload
        $uploadedFile = new UploadedFile(
            $tempFile,
            'avatar.jpg',
            'image/jpeg',
            null,
            true // tells symfony this is fake upload for testing
        );

        // when
        $result = $this->fileUploadService->upload($uploadedFile);

        // then
        $this->assertNotEmpty($result);

        $this->assertFileExists(
            sys_get_temp_dir().'/'.$result
        );
    }

    /**
     * Test retrieving target upload directory.
     *
     * @return void
     */
    public function testGetTargetDirectory(): void
    {
        // when
        $result = $this->fileUploadService
            ->getTargetDirectory();

        // then
        $this->assertEquals(
            sys_get_temp_dir(),
            $result
        );
    }

    /**
     * Test upload failure throws exception.
     *
     * Ensures FileException is propagated when file move fails.
     *
     * @return void
     */
    public function testUploadThrowsException(): void
    {
        // given
        $uploadedFile = $this->createMock(UploadedFile::class);

        $uploadedFile->method('guessExtension')
            ->willReturn('jpg');

        $uploadedFile->method('getClientOriginalName')
            ->willReturn('avatar.jpg');

        $uploadedFile->method('move')
            ->willThrowException(
                new FileException('Upload failed')
            );

        $this->expectException(FileException::class);

        // when
        $this->fileUploadService->upload($uploadedFile);
    }
}