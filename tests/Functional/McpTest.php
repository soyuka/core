<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Recipe;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class McpTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private int $jsonRpcId = 1;
    private array $mcpHeaders;

    public static function getResources(): array
    {
        return [Recipe::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mcpHeaders = [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
        ];

        $this->recreateSchema([Recipe::class]);

        $manager = $this->getManager();
        $recipe = new Recipe();
        $recipe->name = 'French Onion Soup';
        $recipe->description = 'A classic French soup.';
        $recipe->cookTime = 'PT1H';
        $recipe->prepTime = 'PT20M';
        $manager->persist($recipe);

        $recipe2 = new Recipe();
        $recipe2->name = 'Bouillabaisse';
        $recipe2->description = 'A traditional Provençal fish and chicken stew.';
        $manager->persist($recipe2);

        $recipe3 = new Recipe();
        $recipe3->name = 'Cassoulet';
        $recipe3->description = 'A rich, slow-cooked casserole.';
        $manager->persist($recipe3);

        $chickenRecipe = new Recipe();
        $chickenRecipe->name = 'Roast Chicken';
        $chickenRecipe->description = 'A simple and delicious roast chicken.';
        $manager->persist($chickenRecipe);

        $manager->flush();
    }

    public function testMcpEndpoint(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        $client = self::createClient();

        // 1. Initialize
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('initialize', [
                'protocolVersion' => '2024-11-05',
                'clientInfo' => ['name' => 'ApiPlatform Test Suite', 'version' => '1.0'],
                'capabilities' => new \stdClass(),
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHeader('mcp-session-id');
        $this->mcpHeaders['mcp-session-id'] = $response->getHeaders()['mcp-session-id'][0];

        // 2. Initial tools/list should NOT have the search tool
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/list'),
        ]);
        $this->assertResponseIsSuccessful();
        $tools = $response->toArray()['result']['tools'];
        $toolNames = array_column($tools, 'name');
        $this->assertNotContains('search_recipes', $toolNames);
        $this->assertContains('invoke_hydra_operation', $toolNames);

        // 3. Read the recipe collection resource
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('resources/read', ['uri' => 'http://localhost/api/recipes']),
        ]);
        $this->assertResponseIsSuccessful();
        // The test can't easily check for the `tools/list_changed` notification,
        // so we proceed assuming the client received it.

        // 4. Call tools/list again, the new tool should be there.
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/list'),
        ]);
        $this->assertResponseIsSuccessful();
        $tools = $response->toArray()['result']['tools'];
        $searchTool = null;
        foreach ($tools as $tool) {
            if ($tool['name'] === 'search_recipes') {
                $searchTool = $tool;
                break;
            }
        }
        $this->assertNotNull($searchTool, 'search_recipes tool was not dynamically generated.');
        $this->assertArrayHasKey('q', $searchTool['inputSchema']['properties']);

        // 5. Call the new dynamic tool to search for chicken
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'search_recipes',
                'arguments' => ['q' => 'chicken'],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $result = $response->toArray()['result'];
        $searchResultContent = $result['structuredContent'];
        $this->assertCount(2, $searchResultContent['hydra:member']);
        $this->assertStringContainsString('chicken', strtolower(json_encode($searchResultContent['hydra:member'])));

        // 6. Use generic tool to CREATE a recipe
        $recipePayload = [
            'name' => 'Ratatouille',
            'description' => 'A traditional French Provençal stewed vegetable dish.',
        ];
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'invoke_hydra_operation',
                'arguments' => [
                    'method' => 'POST',
                    'uri' => 'http://localhost/api/recipes',
                    'payload' => $recipePayload,
                ],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $createdRecipeResult = $response->toArray()['result'];
        $createdRecipeContent = json_decode($createdRecipeResult['content'][0]['text'], true);
        $this->assertArrayHasKey('@id', $createdRecipeContent);
        $newRecipeUri = 'http://localhost'.$createdRecipeContent['@id'];

        // 7. Use generic tool to DELETE the recipe
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'invoke_hydra_operation',
                'arguments' => [
                    'method' => 'DELETE',
                    'uri' => $newRecipeUri,
                ],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('', $response->toArray()['result']['content'][0]['text']);
    }

    private function createJsonRpcRequest(string $method, array $params = []): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $this->jsonRpcId++,
            'method' => $method,
            'params' => $params,
        ];
    }
}
