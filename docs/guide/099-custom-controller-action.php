<?php
// ---
// slug: custom-controller-action
// name: Custom Controller Action
// position: 10
// executable: true
// ---

// In case you're using a custom controller action, make sure you return the `Paginator` object to get the full hydra response with `hydra:view` (which contains information about first, last, next and previous page). The following examples show how to handle it within a repository method.
// The controller needs to pass through the page number. You will need to use the Doctrine Paginator and pass it to the API Platform Paginator.
//
// First example:

namespace App\Repository {
    use App\Entity\Book;
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Persistence\ManagerRegistry;
    use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
    use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
    use ApiPlatform\Doctrine\Orm\Paginator;
    use Doctrine\Common\Collections\Criteria;

    class BookRepository extends ServiceEntityRepository
    {
        const ITEMS_PER_PAGE = 20;

        private $tokenStorage;

        public function __construct(
            ManagerRegistry       $registry,
            TokenStorageInterface $tokenStorage
        )
        {
            parent::__construct($registry, Book::class);

            $this->tokenStorage = $tokenStorage;
        }

        public function getBooksByFavoriteAuthor(int $page = 1): Paginator
        {
            $firstResult = ($page - 1) * self::ITEMS_PER_PAGE;

            $user = $this->tokenStorage->getToken()->getUser();
            $queryBuilder = $this->createQueryBuilder();
            $queryBuilder->select('b')
                ->from(Book::class, 'b')
                ->where('b.author = :author')
                ->setParameter('author', $user->getFavoriteAuthor()->getId())
                ->andWhere('b.publicatedOn IS NOT NULL');

            $criteria = Criteria::create()
                ->setFirstResult($firstResult)
                ->setMaxResults(self::ITEMS_PER_PAGE);
            $queryBuilder->addCriteria($criteria);

            $doctrinePaginator = new DoctrinePaginator($queryBuilder);
            $paginator = new Paginator($doctrinePaginator);

            return $paginator;
        }
    }
}

// The Controller would look like this:

namespace App\Controller\Book {
    use ApiPlatform\Doctrine\Orm\Paginator;
    use App\Repository\BookRepository;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpKernel\Attribute\AsController;
    use Symfony\Component\HttpFoundation\Request;

    #[AsController]
    class GetBooksByFavoriteAuthorAction extends AbstractController
    {
        public function __invoke(Request $request, BookRepository $bookRepository): Paginator
        {
            $page = (int)$request->query->get('page', 1);

            return $bookRepository->getBooksByFavoriteAuthor($page);
        }
    }
}

// The service needs to use the proper repository method.
// You can also use the Query object inside the repository method and pass it to the Paginator instead of passing the QueryBuilder and using Criteria. Second Example:

namespace App\Repository {
    // use ...

    class BookRepository extends ServiceEntityRepository
    {
        // constant, variables and constructor...

        public function getBooksByFavoriteAuthor(int $page = 1): Paginator
        {
            $firstResult = ($page - 1) * self::ITEMS_PER_PAGE;

            $user = $this->tokenStorage->getToken()->getUser();
            $queryBuilder = $this->createQueryBuilder();
            $queryBuilder->select('b')
                ->from(Book::class, 'b')
                ->where('b.author = :author')
                ->setParameter('author', $user->getFavoriteAuthor()->getId())
                ->andWhere('b.publicatedOn IS NOT NULL');

            $query = $queryBuilder->getQuery()
                ->setFirstResult($firstResult)
                ->setMaxResults(self::ITEMS_PER_PAGE);

            $doctrinePaginator = new DoctrinePaginator($query);
            $paginator = new Paginator($doctrinePaginator);

            return $paginator;
        }
    }
}
