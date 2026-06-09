<?php

/**
 * Book controller tests.
 */

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

/**
 * Class BookControllerTest.
 */
class BookControllerTest extends WebTestCase
{
    /**
     * Base route for book controller.
     */
    public const TEST_ROUTE = '/book';

    /**
     * HTTP client used to simulate browser requests.
     */
    private KernelBrowser $httpClient;

    /**
     * Set up test environment before each test.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test index route for anonymous user.
     */
    public function testIndexRouteAnonymous(): void
    {
        $this->httpClient->request('GET', self::TEST_ROUTE);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test index route for authenticated user.
     */
    public function testIndexRouteUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $this->httpClient->request('GET', self::TEST_ROUTE);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );
        $this->assertSelectorExists('html');
    }

    /**
     * Test show book route for anonymous user.
     */
    public function testShowBookAnonymous(): void
    {
        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId());

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test show book route for allowed user.
     */
    public function testShowBookAllowedUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $user->setBlocked(false); // for voter

        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId());

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );
        $this->assertSelectorExists('html');
    }

    /**
     * Test show book route for blocked user.
     */
    public function testShowBookBlockedUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $user->setBlocked(true);

        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId());

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test create book access forbidden for normal user.
     */
    public function testCreateBookForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $this->httpClient->request('GET', self::TEST_ROUTE.'/create');

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test create book page for admin.
     */
    public function testCreateBookAdmin(): void
    {
        $user = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($user);

        $this->httpClient->request('GET', self::TEST_ROUTE.'/create');

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );
        $this->assertSelectorExists('form');
    }

    /**
     * Test book creation submission.
     */
    public function testCreateBookSubmit(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($user);

        $category = $this->createCategory();

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );

        $form = $crawler->filter('#submit-button')->form([
            'book[title]' => 'Test Book',
            'book[author]' => 'Author',
            'book[description]' => 'Description',
            'book[category]' => $category->getId(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test edit book forbidden for normal user.
     */
    public function testEditBookForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId().'/edit');

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test edit book page and submission for admin.
     */
    public function testEditBookAdmin(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($user);

        $book = $this->createBook();
        $category = $this->createCategory();

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$book->getId().'/edit'
        );

        $form = $crawler->selectButton('submit')->form();

        $form['book[title]'] = 'Updated Title';
        $form['book[author]'] = 'Updated Author';
        $form['book[description]'] = 'Updated Description';
        $form['book[category]'] = $category->getId();

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test delete book forbidden for normal user.
     */
    public function testDeleteBookForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId().'/delete');

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test delete book as admin.
     */
    public function testDeleteBookAdmin(): void
    {
        $user = $this->createUser([UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$book->getId().'/delete');

        $this->httpClient->submitForm('submit');

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Create user helper.
     *
     * @param array $roles User roles
     *
     * @return User
     */
    private function createUser(array $roles): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');
        $userRepository = $container->get(UserRepository::class);

        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setRoles($roles);
        $user->setPassword($passwordHasher->hashPassword($user, 'password'));

        $userRepository->save($user);

        return $user;
    }

    /**
     * Create category helper.
     *
     * @return Category
     */
    private function createCategory(): Category
    {
        $container = static::getContainer();
        $repo = $container->get(CategoryRepository::class);

        $category = new Category();
        $category->setTitle('Test Category '.uniqid());

        $repo->save($category);

        return $category;
    }

    /**
     * Create book helper.
     *
     * @return Book
     */
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
