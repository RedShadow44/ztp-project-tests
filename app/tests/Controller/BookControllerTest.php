<?php

namespace App\Tests\Controller;

use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookControllerTest extends WebTestCase
{
    public const TEST_ROUTE = '/book';

    private KernelBrowser $httpClient;

    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /*
     * INDEX
     **/

    public function testIndexRouteAnonymous(): void
    {
        $this->httpClient->request('GET', self::TEST_ROUTE);

        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
    }

    public function testIndexRouteUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $this->httpClient->request('GET', self::TEST_ROUTE);

        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
        $this->assertSelectorExists('html');
    }

    /*
     * SHOW (VOTER PROTECTED)
     **/

    public function testShowBookAnonymous(): void
    {
        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId());

        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }

    public function testShowBookAllowedUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $user->setBlocked(false); // for voter

        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId());

        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
        $this->assertSelectorExists('html');
    }

    public function testShowBookBlockedUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $user->setBlocked(true);

        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId());

        $this->assertEquals(403, $this->httpClient->getResponse()->getStatusCode());
    }

//    public function testShowBookAdmin(): void
//    {
//        $user = $this->createUser([
//            UserRole::ROLE_USER->value,
//            UserRole::ROLE_ADMIN->value
//        ]);
//        $this->httpClient->loginUser($user);
//
//        $book = $this->createBook();
//
//        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId());
//
//        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
//        $this->assertSelectorExists('html');
//    }

    /*
     * CREATE (ADMIN ONLY)
     **/

    public function testCreateBookForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $this->httpClient->request('GET', self::TEST_ROUTE.'/create');

        $this->assertEquals(403, $this->httpClient->getResponse()->getStatusCode());
    }

    public function testCreateBookAdmin(): void
    {
        $user = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($user);

        $this->httpClient->request('GET', self::TEST_ROUTE.'/create');

        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
    }

    public function testCreateBookSubmit(): void
    {
        $user = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($user);

        $category = $this->createCategory();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/create');

        $this->httpClient->submitForm('submit', [
            'book[title]' => 'Test Book',
            'book[author]' => 'Author',
            'book[description]' => 'Description',
            'book[category]' => $category->getId(),
        ]);

        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }

    /*
     * EDIT (ADMIN ONLY)
     **/

    public function testEditBookForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId().'/edit');

        $this->assertEquals(403, $this->httpClient->getResponse()->getStatusCode());
    }

    public function testEditBookAdmin(): void
    {
        $user = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($user);

        $book = $this->createBook();
        $category = $this->createCategory();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId().'/edit');

        $this->httpClient->submitForm('submit', [
            'book[title]' => 'Updated Title',
            'book[author]' => 'Updated Author',
            'book[description]' => 'Updated Description',
            'book[category]' => $category->getId(),
        ]);

        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }

    /*
     * DELETE (ADMIN ONLY)
     **/

    public function testDeleteBookForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId().'/delete');

        $this->assertEquals(403, $this->httpClient->getResponse()->getStatusCode());
    }

    public function testDeleteBookAdmin(): void
    {
        $user = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId().'/delete');

        $this->httpClient->submitForm('submit');

        $this->assertEquals(302, $this->httpClient->getResponse()->getStatusCode());
    }


    private function createUser(array $roles): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');
        $userRepository = $container->get(UserRepository::class);

        $user = new User();
        $user->setEmail('user'.rand(1, 999999).'@example.com');
        $user->setRoles($roles);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $userRepository->save($user);

        return $user;
    }

    private function createCategory(): Category
    {
        $container = static::getContainer();
        $repo = $container->get(CategoryRepository::class);

        $category = new Category();
//        $category->setTitle('Test Category');
        $category->setTitle('Test Category ' . uniqid());

        $repo->save($category);

        return $category;
    }

    private function createBook(): Book
    {
        $container = static::getContainer();
        $repo = $container->get(BookRepository::class);

        $category = $this->createCategory();

        $book = new Book();
        $book->setTitle('Test Book');
        $book->setAuthor('Author');
        $book->setDescription('Description');
        $book->setCategory($category);

        $repo->save($book);

        return $book;
    }
}