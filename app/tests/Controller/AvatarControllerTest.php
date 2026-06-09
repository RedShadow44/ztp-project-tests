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
     * HTTP client used to simulate browser requests.
     */
    private KernelBrowser $httpClient;

    /**
     * Doctrine entity manager instance.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Avatar repository instance.
     */
    private ?AvatarRepository $avatarRepository;

    /**
     * Set up test environment before each test.
     *
     * Initializes HTTP client and retrieves required services from container.
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
     *
     * Ensures that authenticated user can access the avatar creation page
     * and that the form is displayed.
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
     *
     * Ensures that user is redirected when trying to access create page
     * while already having an avatar assigned.
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
     *
     * Ensures that authenticated user can access avatar edit page
     * when avatar exists.
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
     * Test edit redirect when avatar is missing.
     *
     * Ensures that user is redirected when trying to edit
     * a non-existing avatar.
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
     *
     * Ensures unauthenticated users are redirected when accessing
     * avatar creation page.
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
     *
     * Ensures unauthenticated users are redirected when accessing
     * avatar edit page.
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
     * Create a test user entity.
     *
     * @return User
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
