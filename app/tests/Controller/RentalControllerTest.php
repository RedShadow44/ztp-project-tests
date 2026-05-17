<?php

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

class RentalControllerTest extends WebTestCase
{
    private KernelBrowser $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /*
     * RENT
     */

    public function testRentAnonymousRedirect(): void
    {
        $book = $this->createBook();

        $this->httpClient->request(
            'GET',
            '/'.$book->getId().'/rent'
        );

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

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
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /*
     * RENT INDEX
     */

    public function testIndexForbiddenForUser(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $this->httpClient->request('GET', '/rent_index');

        $this->assertEquals(
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    public function testIndexAdmin(): void
    {
        $admin = $this->createUser([UserRole::ROLE_ADMIN->value]);

        $this->httpClient->loginUser($admin);

        $this->httpClient->request('GET', '/rent_index');

        $this->assertEquals(
            200,
            $this->httpClient->getResponse()->getStatusCode()
        );

        $this->assertSelectorExists('html');
    }

    /*
     * APPROVE
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
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

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
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /*
     * DENY
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
            403,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }
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
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

    /*
     * RETURN
     */

    public function testReturnBook(): void
    {
        $user = $this->createUser([UserRole::ROLE_USER->value]);

        $this->httpClient->loginUser($user);

        $rental = $this->createRental();

        $crawler = $this->httpClient->request(
            'GET',
            '/'.$rental->getId().'/return'
        );

        $form = $crawler->filter('form')->form();

        $this->httpClient->submit($form);

        $this->assertEquals(
            302,
            $this->httpClient->getResponse()->getStatusCode()
        );
    }

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

    private function createCategory(): Category
    {
        $repo = static::getContainer()->get(CategoryRepository::class);

        $category = new Category();
        $category->setTitle('Category '.uniqid());

        $repo->save($category);

        return $category;
    }

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