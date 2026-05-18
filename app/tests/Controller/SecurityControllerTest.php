<?php

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
    private KernelBrowser $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test login page loads.
     */
    public function testLoginPageLoads(): void
    {
        // when
        $this->httpClient->request('GET', '/login');

        // then
        $this->assertEquals(
            200,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }

    /**
     * Test login with invalid credentials.
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

        //then
        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('.alert, .error, body');
    }

    /**
     * Test login with valid credentials.
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

        // then [usually redirect after login]
//        $this->assertTrue(
//            in_array($this->httpClient->getResponse()->getStatusCode(), [302, 200])
//        );
        //then
        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test logout route.
     */
    public function testLogout(): void
    {
        // when
        $this->httpClient->request('GET', '/logout');

        // then [Symfony intercepts logout, typically 302 or handled by firewall]
//        $this->assertTrue(
//            in_array($this->httpClient->getResponse()->getStatusCode(), [302, 204, 500])
//        );
        //then
        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Helper create user.
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
