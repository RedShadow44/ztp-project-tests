<?php

/**
 * Rental controller tests.
 */

namespace App\Tests\Controller;

use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Rental;
use App\Entity\User;
use App\Entity\Enum\UserRole;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Repository\RentalRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class RentalControllerTest.
 */
class RentalControllerTest extends WebTestCase
{
    /**
     * HTTP client used to simulate browser requests.
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
     * Test renting a book as anonymous user.
     *
     * Anonymous users should be redirected when trying to rent a book.
     */
    public function testRentAnonymousRedirect(): void
    {
        $book = $this->createBook();

        $this->httpClient->request(
            'GET',
            '/'.$book->getId().'/rent'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test renting a book as authenticated user.
     *
     * Ensures logged-in users can initiate rent action (redirect flow).
     */
    public function testRentUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $book = $this->createBook();

        $this->httpClient->request(
            'GET',
            '/'.$book->getId().'/rent'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test rental index access for normal user.
     *
     * Users without admin role should be forbidden.
     */
    public function testIndexForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $this->httpClient->request('GET', '/rent_index');

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test rental index access for admin.
     *
     * Admin should be able to view rental index page.
     */
    public function testIndexAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);

        $this->httpClient->loginUser($admin);

        $this->httpClient->request('GET', '/rent_index');

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('html');
    }

    /**
     * Test approve rental forbidden for normal user.
     */
    public function testApproveForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $rental = $this->createRental();

        $this->httpClient->request(
            'GET',
            '/'.$rental->getId().'/rent_approve'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test approve rental as admin.
     *
     * Admin can approve rental requests via form submission.
     */
    public function testApproveAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);

        $this->httpClient->loginUser($admin);

        $rental = $this->createRental();

        $crawler = $this->httpClient->request(
            'GET',
            '/'.$rental->getId().'/rent_approve'
        );

        $form = $crawler->filter('form')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test deny rental forbidden for normal user.
     */
    public function testDenyForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $rental = $this->createRental();

        $this->httpClient->request(
            'GET',
            '/'.$rental->getId().'/rent_deny'
        );

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test deny rental as admin.
     *
     * Admin can deny rental requests via form submission.
     */
    public function testDenyAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);

        $this->httpClient->loginUser($admin);

        $rental = $this->createRental();

        $crawler = $this->httpClient->request(
            'GET',
            '/'.$rental->getId().'/rent_deny'
        );

        $form = $crawler->filter('form')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /**
     * Test returning a rented book.
     *
     * User can return a book via form submission.
     *
     * @return void
     */
    //    public function testReturnBook(): void {
    //
    //        $user = $this->createUser([UserRole::ROLE_USER->value]);
    //
    //        $this->httpClient->loginUser($user);
    //        $rental = $this->createRental();
    //
    //        $crawler = $this->httpClient->request( 'GET', '/'.$rental->getId().'/return' );
    //        $form = $crawler->filter('form')->form(); $this->httpClient->submit($form);
    //
    //        $this->assertEquals( \Symfony\Component\HttpFoundation\Response::HTTP_FOUND,
    //            $this->httpClient->getResponse()->getStatusCode() ); }

    /**
     * Create test user.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     */
    private function createUser(array $roles): User
    {
        $container = static::getContainer();

        $passwordHasher = $container->get('security.password_hasher');
        $repo = $container->get(UserRepository::class);

        $user = new User();
        $user->setEmail('user'.uniqid().'@example.com');
        $user->setRoles($roles);
        $user->setPassword(
            $passwordHasher->hashPassword($user, 'password')
        );

        $repo->save($user);

        return $user;
    }

    /**
     * Create test category.
     *
     * @return Category Category entity
     */
    private function createCategory(): Category
    {
        $repo = static::getContainer()->get(CategoryRepository::class);

        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $repo->save($category);

        return $category;
    }

    /**
     * Create test book.
     *
     * @return Book Book entity
     */
    private function createBook(): Book
    {
        $repo = static::getContainer()->get(BookRepository::class);

        $book = new Book();
        $book->setTitle('Book '.uniqid());
        $book->setAuthor('Author');
        $book->setDescription('Description');
        $book->setAvailable(true);
        $book->setCategory($this->createCategory());

        $repo->save($book);

        return $book;
    }

    /**
     * Create test rental.
     *
     * @return Rental Rental entity
     */
    private function createRental(): Rental
    {
        $repo = static::getContainer()->get(RentalRepository::class);

        $rental = new Rental();
        $rental->setOwner(
            $this->createUser([UserRole::ROLE_USER->value])
        );
        $rental->setBook($this->createBook());
        $rental->setStatus(false);

        $repo->save($rental);

        return $rental;
    }
}
