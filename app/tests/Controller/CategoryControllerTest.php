<?php

/**
 * Category controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\CategoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\NoResultException;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\BookRepository;

/**
 * Class CategoryControllerTest.
 */
class CategoryControllerTest extends WebTestCase
{
    /**
     * Base route for category controller.
     */
    public const TEST_ROUTE = '/category';

    /**
     * HTTP client used to simulate browser requests.
     */
    private KernelBrowser $httpClient;

    /**
     * Doctrine entity manager.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Set up test environment before each test.
     *
     * Initializes HTTP client and entity manager.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();

        $this->entityManager = static::getContainer()
            ->get('doctrine.orm.entity_manager');
    }

    /**
     * Anonymous user should be redirected from category index.
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
     * Normal user should be forbidden from category index.
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
     * Admin should access category index.
     */
    public function testIndexAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $this->httpClient->request('GET', self::TEST_ROUTE);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );
        $this->assertSelectorExists('html');
    }

    /**
     * Test admin can view category details.
     */
    public function testShowCategoryAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $category = $this->createCategory();

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId()
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );
        $this->assertSelectorExists('html');
    }

    /**
     * Test user cannot access category creation page.
     */
    public function testCreateCategoryForbiddenForUser(): void
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
     * Test admin can create category.
     */
    public function testCreateCategoryAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/create'
        );

        $form = $crawler->filter('#submit-button')->form([
            'category[title]' => 'Created '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test user cannot edit category.
     */
    public function testEditCategoryForbiddenForUser(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $category = $this->createCategory();

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/edit'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test admin can edit category.
     */
    public function testEditCategoryAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $category = $this->createCategory();

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/edit'
        );

        $form = $crawler->filter('#submit-button')->form([
            'category[title]' => 'Updated '.uniqid(),
        ]);

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test user cannot delete category.
     */
    public function testDeleteTagForbiddenForUser(): void
    {
        $user = $this->createUser([
            UserRole::ROLE_USER->value,
        ]);

        $this->httpClient->loginUser($user);

        $category = $this->createCategory();

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/delete'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test admin can delete category.
     */
    public function testDeleteCategoryAdmin(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $category = $this->createCategory();

        $crawler = $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/delete'
        );

        $form = $crawler->filter('#submit-button')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test deleting category with existing books.
     */
    public function testDeleteCategoryWithBooks(): void
    {
        $admin = $this->createUser([
            UserRole::ROLE_ADMIN->value,
        ]);

        $this->httpClient->loginUser($admin);

        $category = $this->createCategory();

        $book = new Book();
        $book->setTitle('Book '.uniqid());
        $book->setAuthor('Author');
        $book->setDescription('Description');
        $book->setCategory($category);

        $this->entityManager->persist($book);
        $this->entityManager->flush();

        $this->httpClient->request(
            'GET',
            self::TEST_ROUTE.'/'.$category->getId().'/delete'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test CategoryService canBeDeleted exception handling.
     */
    public function testCanBeDeletedException(): void
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);

        $bookRepository = $this->createMock(BookRepository::class);

        $bookRepository
            ->method('countByCategory')
            ->willThrowException(
                new NoResultException()
            );

        $paginator = $this->createMock(PaginatorInterface::class);

        $service = new CategoryService(
            $categoryRepository,
            $bookRepository,
            $paginator
        );

        $category = new Category();

        $result = $service->canBeDeleted($category);

        $this->assertFalse($result);
    }

    /**
     * Create category helper.
     */
    private function createCategory(): Category
    {
        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $categoryRepository = static::getContainer()
            ->get(CategoryRepository::class);

        $categoryRepository->save($category);

        return $category;
    }

    /**
     * Create user helper.
     */
    private function createUser(array $roles): User
    {
        $passwordHasher = static::getContainer()
            ->get('security.password_hasher');

        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setRoles($roles);
        $user->setBlocked(false);

        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                'password'
            )
        );

        $userRepository = static::getContainer()
            ->get(UserRepository::class);

        $userRepository->save($user);

        return $user;
    }
}
