<?php

/**
 * File upload service tests.
 */

namespace App\Tests\Service;

use App\Service\FileUploadService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Class FileUploadServiceTest.
 */
class FileUploadServiceTest extends KernelTestCase
{
    /**
     * File upload service.
     */
    private ?FileUploadService $fileUploadService;

    /**
     * Set up.
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
     * Test upload.
     */
    public function testUpload(): void
    {
        //given
        // create empty temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'test');

        //add content to temp file
        file_put_contents($tempFile, 'avatar');

        //simulate browser upload
        $uploadedFile = new UploadedFile(
            $tempFile,
            'avatar.jpg',
            'image/jpeg',
            null,
            true //tells symfony this is fake upload for testing
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
     * Test get target directory.
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
}