<?php
// --- 
// slug: declare-a-resource
// name: Declare a Resource
// position: 1
// executable: true
// ---

// # Declare a Resource
// This class represents an API resource
namespace App\ApiResource{
    use ApiPlatform\Metadata\Get;
    use ApiPlatform\Metadata\GetCollection;
    
    #[GetCollection(provider: BlogPostProvider::class)]
    class Book{
        public ?int $id = null;
    }
}

namespace App\Provider{
    use App\ApiResource\Book;
    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\ProviderInterface;

    class Provider implements ProviderInterface{
        /**
         * {@inheritDoc}
         */
        public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
        {
            $book = new Book();
            $book->id = 42;
            return [$book];
        }
    }
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/docs.json');
    }
}
