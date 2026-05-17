<?php

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
     * Test route.
     */
    public const TEST_ROUTE = '/user';

    /**
     * HTTP client.
     */
    private KernelBrowser $httpClient;

    /**
     * Set up.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test index admin.
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
            200,
            $this->httpClient->getResponse()->getStatusCode()
        );
        $this->assertSelectorExists('html');
    }

    /**
     * Test index forbidden for user.
     */
    public function testIndexForbiddenForUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);

        // then
        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }
    /**
     * Test show user.
     */
    public function testShowUser(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        // when
        $this->httpClient->request(
            'GET',
            '/user/' . $targetUser->getId()
        );

        // then
        $this->assertEquals(
            200,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('html');
    }
    /**
     * Test show user forbidden for non-admin user.
     */
    public function testShowUserForbiddenForUser(): void
    {
        // given
        $user1 = $this->createUser([UserRole::ROLE_USER->value]);
        $user2 = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user1);

        // when
        $this->httpClient->request(
            'GET',
            '/user/' . $user2->getId()
        );

        // then
        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test edit user (GET form).
     */
    public function testEditUserGet(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        // when
        $crawler = $this->httpClient->request(
            'GET',
            '/user/' . $targetUser->getId() . '/edit'
        );

        // then
        $this->assertEquals(
            200,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }
    /**
     * Test edit user submit.
     */
    public function testEditUserSubmit(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($admin);

        $targetUser = $this->createUser([UserRole::ROLE_USER->value]);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/' . $targetUser->getId() . '/edit'
        );

        $form = $crawler->filter('form')->form([
            'user[email]' => 'updated_' . uniqid() . '@example.com',
        ]);

        // when
        $this->httpClient->submit($form);

        // then
        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }
    /**
     * Test show own profile.
     */
    public function testProfileOwnUser(): void
    {
        // given
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request(
            'GET',
            '/profile/'.$user->getId()
        );

        // then
        $this->assertEquals(
            200,
            $this->httpClient->getResponse()->getStatusCode()
        );
        $this->assertSelectorExists('html');
    }

    /**
     * Test profile forbidden for different user.
     */
    public function testProfileForbidden(): void
    {
        // given
        $user1 = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $user2 = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user1);

        // when
        $this->httpClient->request(
            'GET',
            '/profile/'.$user2->getId()
        );

        // then
        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test register.
     */
    public function testRegister(): void
    {
        // when
        $crawler = $this->httpClient->request(
            'GET',
            '/register'
        );

        // then
        $this->assertEquals(
            200,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');

        $form = $crawler->filter('form')->form([
            'user[email]' => 'register'.uniqid().'@example.com',
            'user[password]' => 'password',
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }
    /**
     * Test change profile forbidden for another user.
     */
    public function testChangeProfileForbiddenForAnotherUser(): void
    {
        // given
        $user1 = $this->createUser([UserRole::ROLE_USER->value]);
        $user2 = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user1);

        // when
        $this->httpClient->request(
            'GET',
            '/change/'.$user2->getId()
        );

        // then
        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test change profile for owner.
     */
    public function testChangeProfileOwner(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        // when
        $crawler = $this->httpClient->request(
            'GET',
            '/change/'.$user->getId()
        );

        // then
        $this->assertEquals(
            200,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }

    /**
     * Test change profile submit.
     */
    public function testChangeProfileSubmit(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $crawler = $this->httpClient->request(
            'GET',
            '/change/'.$user->getId()
        );

        // when
        $form = $crawler->filter('form')->form([
            'user[email]' => 'updated_'.uniqid().'@example.com',
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test change password forbidden for another user.
     */
    public function testChangePasswordForbiddenForAnotherUser(): void
    {
        // given
        $user1 = $this->createUser([UserRole::ROLE_USER->value]);
        $user2 = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user1);

        // when
        $this->httpClient->request(
            'GET',
            '/change/pass/'.$user2->getId()
        );

        // then
        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test change password for owner.
     */
    public function testChangePasswordOwner(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        // when
        $crawler = $this->httpClient->request(
            'GET',
            '/change/pass/'.$user->getId()
        );

        // then
        $this->assertEquals(
            200,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('form');
    }

    /**
     * Test change password submit.
     */
    public function testChangePasswordSubmit(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $crawler = $this->httpClient->request(
            'GET',
            '/change/pass/'.$user->getId()
        );

        // when
        $form = $crawler->filter('form')->form([
            'user[plain_password][first]' => 'newpassword123',
            'user[plain_password][second]' => 'newpassword123',
        ]);

        $this->httpClient->submit($form);

        // then
        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }
    /**
     * Test set admin success.
     */
    public function testSetAdminSuccess(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/'.$user->getId().'/set_admin'
        );

        $form = $crawler->filter('form')->form();

        // when
        $this->httpClient->submit($form);

        // then
        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }

    /**
     * Test revoke admin success.
     */
    public function testRevokeAdminSuccess(): void
    {
        // given
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

        // when
        $this->httpClient->submit($form);

        // then
        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }
    /**
     * Test revoke admin blocked when last admin.
     */
    public function testRevokeAdminLastAdmin(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);

        $this->httpClient->loginUser($admin);

        // when
        $this->httpClient->request(
            'GET',
            '/user/'.$admin->getId().'/revoke_admin'
        );

        // then
        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }
    /**
     * Test block user.
     */
    public function testBlockUser(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/'.$user->getId().'/block'
        );

        $form = $crawler->filter('form')->form();

        // when
        $this->httpClient->submit($form);

        // then
        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }
    /**
     * Test unblock user.
     */
    public function testUnblockUser(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $user->setBlocked(true);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            '/user/'.$user->getId().'/unblock'
        );

        $form = $crawler->filter('form')->form();

        // when
        $this->httpClient->submit($form);

        // then
        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }

    /**
     * Test edit password.
     */
    public function testEditPassword(): void
    {
        // given
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

        // when
        $this->httpClient->submit($form);

        // then
        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }

    /**
     * Create user helper.
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
