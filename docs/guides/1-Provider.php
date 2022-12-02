<?php

// # Provider
// Our model is the same then in the [ApiResource guide](./0-ApiResource.php). API Platform will declare
// CRUD operations if we don't declare them. 
namespace App\ApiResource {
    use ApiPlatform\Metadata\ApiResource;
    use App\State\BookProvider;

    // We used a `BookProvider` as the [Operation::provider]() option. (reference doc technique)
    #[ApiResource(provider: BookProvider::class)]
    class Book
    {
        public string $id;
    }
}

namespace App\State {
    use ApiPlatform\Metadata\CollectionOperationInterface;
    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\ProviderInterface;
    use App\ApiResource\Book;

    // The BookProvider is where we retrieve the data in our persistence layer. 
    // In this provider we choose to handle the retrieval of a single Book but also a list of Books.
    // As an exercise you can edit the code and add a second book in the collection.
    final class BookProvider implements ProviderInterface
    {
        public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable|object|null
        {
            if ($operation instanceof CollectionOperationInterface) {
                $book = new Book();
                $book->id = '1';
                return [$book];
            }

            $book = new Book();
            // This holds the value of the `{id}` variable of the URI template.
            $book->id = $uriVariables['id'];
            return $book;
        }
    }
}


