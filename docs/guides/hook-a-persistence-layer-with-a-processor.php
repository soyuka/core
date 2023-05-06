<?php
// ---
// slug: hook-a-persistence-layer-with-a-processor
// name: Hook a Persistence Layer with a Processor
// position: 2
// tags: design, state
// ---

// # Hook a Persistence Layer with a Processor
namespace App\ApiResource {

    use ApiPlatform\Metadata\ApiResource;
    use App\State\BookProcessor;

    // We use a `BookProcessor` as the [ApiResource::processor](http://localhost:3000/reference/Metadata/ApiResource#processor) option.
    #[ApiResource(processor: BookProcessor::class)]
    class Book
    {
        public int $id;
        public string $name;
    }
}

namespace App\State {

    use ApiPlatform\Metadata\Operation;
    use ApiPlatform\State\ProcessorInterface;
    use App\ApiResource\Book;

    // The BookProcessor is where we can handle a persistence layer.
    // In this processor we're storing the JSON representation of the book in a file.
    final class BookProcessor implements ProcessorInterface
    {
        /**
         * @param Book $data
         */
        public function process($data, Operation $operation, array $uriVariables = [], array $context = []): Book
        {
            // Use your identifier strategy
            if (!isset($uriVariables['id'])) {
                $data->id = $uriVariables['id'] = random_int(37, 37 * 37 * 37 * 3);
            }
            // Call your persistence layer to save the resource $data
            file_put_contents(sprintf('book-%s.json', $uriVariables['id']), json_encode($data));

            return $data;
        }
    }
}

namespace App\Playground {

    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/books', 'POST', server: [
            'HTTP_ACCEPT' => 'application/ld+json',
            'CONTENT_TYPE' => 'application/json'
        ], content: '{
    "name": "The book name"
}'
        );
    }
}
