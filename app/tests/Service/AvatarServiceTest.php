<?php

/**
 * Avatar service tests.
 */

namespace App\Tests\Service;

use App\Entity\Avatar;
use App\Entity\User;
use App\Repository\AvatarRepository;
use App\Service\AvatarService;
use App\Service\FileUploadServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class AvatarServiceTest.
 */
class AvatarServiceTest extends KernelTestCase
{
    /**
     * Entity manager instance.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Avatar repository.
     */
    private ?AvatarRepository $avatarRepository;

    /**
     * Avatar service under test.
     */
    private ?AvatarService $avatarService;

    /**
     * Set up test environment.
     *
     * Initializes entity manager, repository and a mocked file upload service.
     */
    protected function setUp(): void
    {
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        $this->avatarRepository = $container->get(
            AvatarRepository::class
        );

        // fake upload service for testing
        $fileUploadService = $this->createMock(
            FileUploadServiceInterface::class
        );

        // if $fileUploadService->upload(...) is called it returns 'avatar.jpg'
        $fileUploadService->method('upload')
            ->willReturn('avatar.jpg');

        // manual creation of AvatarService to inject mocked upload service
        $this->avatarService = new AvatarService(
            sys_get_temp_dir(), // temp folder
            $this->avatarRepository, // real repo
            $fileUploadService, // mock fileUploadService
            new Filesystem()
        );
    }

    /**
     * Test avatar creation process.
     *
     * Ensures uploaded file is stored and avatar entity is persisted.
     */
    public function testCreate(): void
    {
        // given
        $user = new User();

        $user->setEmail('avatar'.uniqid().'@example.com');
        $user->setPassword('password');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $avatar = new Avatar();

        // create real empty temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'avatar');

        // put content into temp file
        file_put_contents($tempFile, 'avatar');

        // simulate browser file upload
        $uploadedFile = new UploadedFile(
            $tempFile,
            'avatar.jpg',
            'image/jpeg',
            null,
            true
        );

        // when
        $this->avatarService->create(
            $uploadedFile,
            $avatar,
            $user
        );

        // then
        $savedAvatar = $this->avatarRepository->find(
            $avatar->getId()
        );

        $this->assertNotNull($savedAvatar);

        $this->assertEquals(
            'avatar.jpg',
            $savedAvatar->getFilename()
        );
    }

    /**
     * Test avatar update process.
     *
     * Ensures old avatar is replaced with a new uploaded file.
     */
    public function testUpdate(): void
    {
        // given
        $user = new User();

        $user->setEmail('update'.uniqid().'@example.com');
        $user->setPassword('password');

        $this->entityManager->persist($user);

        $avatar = new Avatar();

        // set old avatar to user
        $avatar->setFilename('old.jpg');
        $avatar->setUser($user);

        $this->avatarRepository->save($avatar);

        // simulate old avatar file (physical file)
        file_put_contents(
            sys_get_temp_dir().'/old.jpg',
            'old'
        );

        // create new avatar file
        $tempFile = tempnam(sys_get_temp_dir(), 'avatar');

        file_put_contents($tempFile, 'new');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'new.jpg',
            'image/jpeg',
            null,
            true
        );

        // when
        $this->avatarService->update(
            $uploadedFile,
            $avatar,
            $user
        );

        // then
        $updatedAvatar = $this->avatarRepository->find(
            $avatar->getId()
        );

        $this->assertEquals(
            'avatar.jpg',
            $updatedAvatar->getFilename()
        );
    }
}
