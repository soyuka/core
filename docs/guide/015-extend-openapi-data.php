<?php
// --- 
// slug: secure-a-resource-with-custom-voters
// name: Secure a Resource with Custom Voters
// position: 10
// executable: true
// ---

namespace App\OpenApi {
    use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
    use ApiPlatform\OpenApi\OpenApi;
    use ApiPlatform\OpenApi\Model;

    final class OpenApiFactory implements OpenApiFactoryInterface
    {
        private $decorated;

        public function __construct(OpenApiFactoryInterface $decorated)
        {
            $this->decorated = $decorated;
        }

        public function __invoke(array $context = []): OpenApi
        {
            $openApi = $this->decorated->__invoke($context);
            $pathItem = $openApi->getPaths()->getPath('/api/grumpy_pizzas/{id}');
            $operation = $pathItem->getGet();

            $openApi->getPaths()->addPath('/api/grumpy_pizzas/{id}', $pathItem->withGet(
                $operation->withParameters(array_merge(
                    $operation->getParameters(),
                    [new Model\Parameter('fields', 'query', 'Fields to remove of the output')]
                ))
            ));

            $openApi = $openApi->withInfo((new Model\Info('New Title', 'v2', 'Description of my custom API'))->withExtensionProperty('info-key', 'Info value'));
            $openApi = $openApi->withExtensionProperty('key', 'Custom x-key value');
            $openApi = $openApi->withExtensionProperty('x-value', 'Custom x-value value');

            return $openApi;
        }
    }
}

namespace App\Configurator {
    use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

    function configure(ContainerConfigurator $configurator) {
        $services = $configurator->services();
        $services->set(App\OpenApi\OpenApiFactory::class)->decorate('api_platform.openapi.factory');
    };
}

namespace App\Playground {
    use App\Kernel;
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/api/grumpy_pizzas/1.jsonld');
    }
}

// namespace App\Tests {
//     class ApiTestCase extends ApiTestCase {
//
//     }
// }
