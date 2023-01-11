<?php
// ---
// slug: handle-a-pagination-on-a-custom-collection
// name: Handle a Pagination on a Custom Collection
// executable: true
// ---

// In case you're using a custom collection (through a Provider), make sure you return the `Paginator` object to get the
// full hydra response with `hydra:view` (which contains information about first, last, next and previous page).
//
// The following example shows how to handle it using a custom Provider. You will need to use the Doctrine Paginator and
// pass it to the API Platform Paginator.

namespace App\Entity {
    use ApiPlatform\Metadata\GetCollection;
    use App\Repository\BookRepository;
    use App\State\BooksListProvider;
    use Doctrine\ORM\Mapping as ORM;

    // Use custom Provider on operation to retrieve the custom collection
    #[GetCollection(provider: BooksListProvider::class)]
    #[ORM\Entity(repositoryClass: BookRepository::class)]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        public ?int $id = null;

        #[ORM\Column]
        public ?string $title = null;

        #[ORM\Column(type: 'boolean')]
        public ?bool $published = null;
    }
}

namespace App\Repository {
    use App\Entity\Book;
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Common\Collections\Criteria;
    use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
    use Doctrine\Persistence\ManagerRegistry;

    class BookRepository extends ServiceEntityRepository
    {
        public function __construct(ManagerRegistry $registry)
        {
            parent::__construct($registry, Book::class);
        }

        public function getPublishedBooks(int $page = 1, int $itemsPerPage = 30): DoctrinePaginator
        {
            // Retrieve the custom collection and inject it into a Doctrine Paginator object
            return new DoctrinePaginator(
                $this->createQueryBuilder('b')
                     ->where('b.published IS NOT NULL')
                     ->addCriteria(
                         Criteria::create()
                             ->setFirstResult(($page - 1) * $itemsPerPage)
                             ->setMaxResults($itemsPerPage)
                     )
            );
        }
    }
}

namespace App\State {
    use ApiPlatform\Doctrine\Orm\Paginator;
    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\Pagination\Pagination;
    use ApiPlatform\State\ProviderInterface;
    use App\Repository\BookRepository;

    class BooksListProvider implements ProviderInterface
    {
        public function __construct(private readonly BookRepository $bookRepository, private readonly Pagination $pagination)
        {
        }

        public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator
        {
            // Retrieve the pagination parameters from the context thanks to the Pagination object
            [$page, , $limit] = $this->pagination->getPagination($operation, $context);

            // Decorates the Doctrine Paginator object to the API Platform Paginator one
            return new Paginator($this->bookRepository->getPublishedBooks($page, $limit));
        }
    }
}
