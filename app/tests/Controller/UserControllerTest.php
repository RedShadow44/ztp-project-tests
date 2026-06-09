<?php

/**
 * User controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class UserControllerTest.
 */
class UserControllerTest extends WebTestCase
{
    /**
     * Base route for user controller tests.
     */
    public const TEST_ROUTE = '/user';

    /**
     * HTTP client used for functional tests.
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
     * Test admin can access user index.
     */
    public function testIndexAdmin(): void
    {
        // given
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);

        // then
        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );
        $this->assertSelectorExists('html');
    }

    /**
     * Test index access forbidden for normal user.
     */
    public function testIndexForbiddenForUser(): void
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
     * Test admin can view user details.
     */
    public function testShowUser(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->request(
            'GET',
            '/user/'.$targetUser->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('html');
    }

    /**
     * Test user cannot view another user profile.
     */
    public function testShowUserForbiddenForUser(): void
    {
        $user1 = $this->createUser([UserRole::ROLE_USER->value]);
        $user2 = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user1);

        $this->httpClient->request(
            'GET',
            '/user/'.$user2->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test admin can access edit user form.
     */
    public function testEditUserGet(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->request(
            'GET',
            '/user/'.$targetUser->getId().'/edit'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }

    /**
     * Test admin can submit user edit form.
     */
    public function testEditUserSubmit(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/'.$targetUser->getId().'/edit'
        );

        $form = $crawler->filter('form')->form([
            'user[email]' => 'updated_'.uniqid().'@example.com',
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test user can view own profile.
     */
    public function testProfileOwnUser(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $this->httpClient->request(
            'GET',
            '/profile/'.$user->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );
        $this->assertSelectorExists('html');
    }

    /**
     * Test user cannot view another profile.
     */
    public function testProfileForbidden(): void
    {
        $user1 = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $user2 = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user1);

        $this->httpClient->request(
            'GET',
            '/profile/'.$user2->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test user registration flow.
     */
    public function testRegister(): void
    {
        $crawler = $this->httpClient->request('GET', '/register');

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');

        $form = $crawler->filter('form')->form([
            'user[email]' => 'register'.uniqid().'@example.com',
            'user[password]' => 'password',
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test changing another user's profile is forbidden.
     */
    public function testChangeProfileForbiddenForAnotherUser(): void
    {
        $user1 = $this->createUser([UserRole::ROLE_USER->value]);
        $user2 = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user1);

        $this->httpClient->request(
            'GET',
            '/change/'.$user2->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test user can access own profile change form.
     */
    public function testChangeProfileOwner(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $this->httpClient->request(
            'GET',
            '/change/'.$user->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }

    /**
     * Test profile change submission.
     */
    public function testChangeProfileSubmit(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $crawler = $this->httpClient->request(
            'GET',
            '/change/'.$user->getId()
        );

        $form = $crawler->filter('form')->form([
            'user[email]' => 'updated_'.uniqid().'@example.com',
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test changing another user's password is forbidden.
     */
    public function testChangePasswordForbiddenForAnotherUser(): void
    {
        $user1 = $this->createUser([UserRole::ROLE_USER->value]);
        $user2 = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user1);

        $this->httpClient->request(
            'GET',
            '/change/pass/'.$user2->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test owner can access password change form.
     */
    public function testChangePasswordOwner(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $this->httpClient->request(
            'GET',
            '/change/pass/'.$user->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }

    /**
     * Test password change submission.
     */
    public function testChangePasswordSubmit(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $crawler = $this->httpClient->request(
            'GET',
            '/change/pass/'.$user->getId()
        );

        $form = $crawler->filter('form')->form([
            'user[plain_password][first]' => 'newpassword123',
            'user[plain_password][second]' => 'newpassword123',
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test granting admin role to user.
     */
    public function testSetAdminSuccess(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/'.$user->getId().'/set_admin'
        );

        $form = $crawler->filter('form')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test revoking admin role from user.
     */
    public function testRevokeAdminSuccess(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/'.$user->getId().'/revoke_admin'
        );

        $form = $crawler->filter('form')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test preventing last admin from being revoked.
     */
    public function testRevokeAdminLastAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);

        $this->httpClient->loginUser($admin);

        $this->httpClient->request(
            'GET',
            '/user/'.$admin->getId().'/revoke_admin'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test blocking a user.
     */
    public function testBlockUser(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/'.$user->getId().'/block'
        );

        $form = $crawler->filter('form')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test unblocking a user.
     */
    public function testUnblockUser(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $user->setBlocked(true);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/'.$user->getId().'/unblock'
        );

        $form = $crawler->filter('form')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test editing user password as admin.
     */
    public function testEditPassword(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/'.$user->getId().'/edit/pass'
        );

        $form = $crawler->filter('form')->form([
            'user[plain_password][first]' => 'newpass123',
            'user[plain_password][second]' => 'newpass123',
        ]);

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
            $passwordHasher->hashPassword($user, 'password')
        );

        $userRepository->save($user);

        return $user;
    }
}
