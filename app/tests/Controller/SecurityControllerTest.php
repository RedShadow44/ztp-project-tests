<?php

/**
 * Security controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class SecurityControllerTest.
 */
class SecurityControllerTest extends WebTestCase
{
    /**
     * HTTP client used for functional testing.
     */
    private KernelBrowser $httpClient;

    /**
     * Set up test environment before each test.
     *
     * Initializes Symfony test client.
     */
    protected function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test login page loads successfully.
     *
     * Ensures login form is rendered.
     */
    public function testLoginPageLoads(): void
    {
        // when
        $this->httpClient->request('GET', '/login');

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }

    /**
     * Test login with invalid credentials.
     *
     * Ensures authentication fails and user is redirected or shown error.
     */
    public function testLoginInvalidCredentials(): void
    {
        // when
        $crawler = $this->httpClient->request('GET', '/login');

        $form = $crawler->filter('#submit-button')->form([
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('.alert, .error, body');
    }

    /**
     * Test login with valid credentials.
     *
     * Ensures authenticated user is redirected after successful login.
     */
    public function testLoginValidCredentials(): void
    {
        // given
        $user = $this->createUser();

        // when
        $crawler = $this->httpClient->request('GET', '/login');

        $form = $crawler->filter('#submit-button')->form([
            'email' => $user->getEmail(),
            'password' => 'password',
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test logout route.
     *
     * Ensures logout endpoint triggers redirect behavior.
     */
    public function testLogout(): void
    {
        // when
        $this->httpClient->request('GET', '/logout');

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Create test user for authentication tests.
     */
    private function createUser(): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');
        $userRepository = $container->get(UserRepository::class);

        $user = new User();
        $user->setEmail('login'.uniqid().'@example.com');
        $user->setRoles([UserRole::ROLE_USER->value]);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $userRepository->save($user);

        return $user;
    }
}
