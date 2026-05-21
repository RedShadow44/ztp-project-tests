<?php

/**
 * Avatar controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Avatar;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\AvatarRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class AvatarControllerTest.
 */
class AvatarControllerTest extends WebTestCase
{
    /**
     * HTTP client.
     */
    private KernelBrowser $httpClient;

    /**
     * Entity manager.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Avatar repository.
     */
    private ?AvatarRepository $avatarRepository;

    /**
     * Set up.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();

        $container = static::getContainer();

        $this->entityManager = $container->get(
            'doctrine.orm.entity_manager'
        );

        $this->avatarRepository = $container->get(
            AvatarRepository::class
        );
    }

    /**
     * Test create avatar page.
     */
    public function testCreateAvatarPage(): void
    {
        // given
        $user = $this->createUser();

        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request(
            'GET',
            '/avatar/create'
        );

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }

    /**
     * Test redirect to edit when avatar exists.
     */
    public function testRedirectToEditWhenAvatarExists(): void
    {
        // given
        $user = $this->createUser();

        $avatar = new Avatar();

        $avatar->setFilename('avatar.jpg');
        $avatar->setUser($user);

        $this->entityManager->persist($avatar);

        $user->setAvatar($avatar);

        $this->entityManager->flush();

        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request(
            'GET',
            '/avatar/create'
        );

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test edit avatar page.
     */
    public function testEditAvatarPage(): void
    {
        // given
        $user = $this->createUser();

        $avatar = new Avatar();

        $avatar->setFilename('avatar.jpg');
        $avatar->setUser($user);

        $this->avatarRepository->save($avatar);

        $user->setAvatar($avatar);

        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request(
            'GET',
            '/avatar/'.$avatar->getId().'/edit'
        );

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }

    /**
     * Test edit redirect to create when avatar missing.
     */
    public function testEditRedirectWhenAvatarMissing(): void
    {
        // given
        $user = $this->createUser();

        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request(
            'GET',
            '/avatar/'.$user->getId().'/edit'
        );

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test create avatar requires login.
     */
    public function testCreateAvatarRequiresLogin(): void
    {
        // when
        $this->httpClient->request(
            'GET',
            '/avatar/create'
        );

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test edit avatar requires login.
     */
    public function testEditAvatarRequiresLogin(): void
    {
        // when
        $this->httpClient->request(
            'GET',
            '/avatar/1/edit'
        );

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Create user helper.
     */
    private function createUser(): User
    {
        // given
        $container = static::getContainer();

        $passwordHasher = $container->get(
            'security.password_hasher'
        );

        $userRepository = $container->get(
            UserRepository::class
        );

        $user = new User();

        $user->setEmail(
            'avatar'.uniqid().'@example.com'
        );

        $user->setRoles([
            UserRole::ROLE_USER->value,
        ]);

        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                'password'
            )
        );

        // when
        $userRepository->save($user);

        // then
        return $user;
    }
}
