<?php

/**
 * Tag controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class TagControllerTest.
 */
class TagControllerTest extends WebTestCase
{
    /**
     * Base route for tag controller.
     */
    public const TEST_ROUTE = '/tag';

    /**
     * HTTP client used for functional testing.
     */
    private KernelBrowser $httpClient;

    /**
     * Set up test environment before each test.
     *
     * Initializes Symfony test client.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test index access for anonymous user.
     *
     * Ensures anonymous users are redirected from tag index.
     */
    public function testIndexAnonymous(): void
    {
        $this->httpClient->request('GET', self::TEST_ROUTE);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test index access forbidden for normal user.
     */
    public function testIndexUserForbidden(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $this->httpClient->request('GET', self::TEST_ROUTE);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test index access for admin user.
     */
    public function testIndexAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);

        $this->httpClient->loginUser($admin);

        $this->httpClient->request('GET', self::TEST_ROUTE);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('html');
    }

    /**
     * Test show tag page for admin.
     */
    public function testShowTagAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $tag = $this->createTag();

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('html');
    }

    /**
     * Test create tag forbidden for normal user.
     */
    public function testCreateTagForbiddenForUser(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test create tag as admin.
     */
    public function testCreateTagAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );

        $form = $crawler->selectButton('submit')->form([
            'tag[title]' => 'New Tag '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test edit tag forbidden for normal user.
     */
    public function testEditTagForbiddenForUser(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $tag = $this->createTag();

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/edit'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test edit tag as admin.
     */
    public function testEditTagAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $tag = $this->createTag();

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/edit'
        );

        $form = $crawler->selectButton('submit')->form([
            'tag[title]' => 'Updated '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test delete tag forbidden for normal user.
     */
    public function testDeleteTagForbiddenForUser(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $tag = $this->createTag();

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/delete'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test delete tag as admin.
     */
    public function testDeleteTagAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $tag = $this->createTag();

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$tag->getId().'/delete'
        );

        $form = $crawler->selectButton('submit')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Create test user.
     */
    private function createUser(array $roles): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');

        $userRepository = $container->get(UserRepository::class);

        $user = new User();

        $user->setEmail('user'.uniqid().'@example.com');

        $user->setRoles($roles);

        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                'password'
            )
        );

        $userRepository->save($user);

        return $user;
    }

    /**
     * Create test tag.
     */
    private function createTag(): Tag
    {
        $container = static::getContainer();

        $repository = $container->get(TagRepository::class);

        $tag = new Tag();

        $tag->setTitle('Tag '.uniqid());

        $repository->save($tag);

        return $tag;
    }
}
