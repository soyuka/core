<?php
// ---
// slug: custom-controller-action
// name: Custom Controller Action
// position: 10
// executable: false
// ---

// In case you're using a custom controller action, make sure you return the `Paginator` object to get the full hydra response with `hydra:view` (which contains information about first, last, next and previous page). The following example show how to handle it within a repository method.
// The controller needs to pass through the page number. You will need to use the Doctrine Paginator and pass it to the API Platform Paginator.

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use App\Repository\BookRepository;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
    use Symfony\Component\Security\Core\User\UserInterface;
    use Symfony\Component\Validator\Constraints as Assert;

    #[ORM\Entity]
    class Author
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        private ?int $id = null;

        #[ORM\Column]
        #[Assert\NotBlank]
        private ?string $name = null;

        public function getId(): ?int
        {
            return $this->id;
        }

        public function getName(): ?string
        {
            return $this->name;
        }

        public function setName(string $name): self
        {
            $this->name = $name;

            return $this;
        }
    }

    #[ORM\Entity(repositoryClass: BookRepository::class)]
    #[ApiResource]
    class Book
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        private ?int $id = null;

        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false)]
        #[Assert\NotNull]
        private ?User $author = null;

        #[ORM\Column(type: 'date', nullable: true)]
        #[Assert\Date]
        private ?\DateTimeInterface $publishedOn = null;

        public function getId(): ?int
        {
            return $this->id;
        }

        public function getAuthor(): ?User
        {
            return $this->author;
        }

        public function setAuthor(User $author): self
        {
            $this->author = $author;

            return $this;
        }

        public function getPublishedOn(): ?\DateTimeInterface
        {
            return $this->publishedOn;
        }

        public function setPublishedOn(\DateTimeInterface $publishedOn): self
        {
            $this->publishedOn = $publishedOn;

            return $this;
        }
    }

    #[ORM\Entity]
    #[ORM\Table(name: '`user`')]
    #[UniqueEntity('username')]
    class User implements UserInterface
    {
        #[ORM\Id, ORM\Column, ORM\GeneratedValue]
        private ?int $id = null;

        #[Assert\NotBlank]
        #[ORM\Column(length: 180, unique: true)]
        private ?string $username = null;

        #[ORM\Column(type: 'json')]
        private array $roles = [];

        #[ORM\ManyToOne(targetEntity: Author::class)]
        #[ORM\JoinColumn(nullable: false)]
        #[Assert\NotNull]
        private ?Author $favoriteAuthor = null;

        public function getId(): ?int
        {
            return $this->id;
        }

        public function getUsername(): ?string
        {
            return $this->username;
        }

        public function setUsername(string $username): self
        {
            $this->username = $username;

            return $this;
        }

        /**
         * @see UserInterface
         */
        public function getRoles(): array
        {
            $roles = $this->roles;

            $roles[] = 'ROLE_USER';

            return array_unique($roles);
        }

        public function setRoles(array $roles): self
        {
            $this->roles = $roles;

            return $this;
        }

        /**
         * A visual identifier that represents this user.
         *
         * @see UserInterface
         */
        public function getUserIdentifier(): string
        {
            return (string) $this->username;
        }

        /**
         * @see UserInterface
         */
        public function eraseCredentials(): void
        {
        }

        public function getFavoriteAuthor(): ?Author
        {
            return $this->favoriteAuthor;
        }

        public function setFavoriteAuthor(Author $favoriteAuthor): self
        {
            $this->favoriteAuthor = $favoriteAuthor;

            return $this;
        }
    }
}

namespace App\Repository {
    use ApiPlatform\Doctrine\Orm\Paginator;
    use App\Entity\Book;
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Common\Collections\Criteria;
    use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
    use Doctrine\Persistence\ManagerRegistry;
    use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

    /**
     * @extends ServiceEntityRepository<Book>
     *
     * @method Book|null find($id, $lockMode = null, $lockVersion = null)
     * @method Book|null findOneBy(array $criteria, array $orderBy = null)
     * @method Book[]    findAll()
     * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
     */
    class BookRepository extends ServiceEntityRepository
    {
        private const ITEMS_PER_PAGE = 20;

        private TokenStorageInterface $tokenStorage;

        public function __construct(ManagerRegistry $registry, TokenStorageInterface $tokenStorage)
        {
            parent::__construct($registry, Book::class);

            $this->tokenStorage = $tokenStorage;
        }

        public function getBooksByFavoriteAuthor(int $page = 1): Paginator
        {
            return new Paginator(new DoctrinePaginator($this->createQueryBuilder('b')
                ->select('b')
                ->from(Book::class, 'b')
                ->where('b.author = :author')
                ->setParameter('author', $this->tokenStorage->getToken()->getUser()->getFavoriteAuthor()->getId())
                ->andWhere('b.publishedOn IS NOT NULL')
                ->addCriteria(
                    Criteria::create()
                        ->setFirstResult(($page - 1) * self::ITEMS_PER_PAGE)
                        ->setMaxResults(self::ITEMS_PER_PAGE)
                )));
        }
    }
}

namespace App\Controller\Book {
    use ApiPlatform\Doctrine\Orm\Paginator;
    use App\Repository\BookRepository;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpKernel\Attribute\AsController;

    #[AsController]
    class GetBooksByFavoriteAuthorAction extends AbstractController
    {
        public function __invoke(Request $request, BookRepository $bookRepository): Paginator
        {
            return $bookRepository->getBooksByFavoriteAuthor((int) $request->query->get('page', 1));
        }
    }
}

namespace App\Configurator {
    use App\Entity\User;
    use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

    function configure(ContainerConfigurator $configurator) {
        $configurator->extension('security', [
            'providers' => [
                'users' => [
                    'entity' => [
                        'class' => User::class,
                        'property' => 'username',
                    ],
                ],
            ],
        ]);
    };
}
