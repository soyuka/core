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
        $recipe2->description = 'A traditional Provençal fish stew.';
        $manager->persist($recipe2);

        $recipe3 = new Recipe();
        $recipe3->name = 'Cassoulet';
        $recipe3->description = 'A rich, slow-cooked casserole.';
        $manager->persist($recipe3);

        $manager->flush();
    }

    public function testMcpEndpoint(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('MongoDB not tested.');
        }

        $client = self::createClient();

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('initialize', [
                'protocolVersion' => '2024-11-05',
                'clientInfo' => [
                    'name' => 'ApiPlatform Test Suite',
                    'version' => '1.0',
                ],
                'capabilities' => new \stdClass(),
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHeader('mcp-session-id');
        $this->mcpHeaders['mcp-session-id'] = $response->getHeaders()['mcp-session-id'][0];

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/list'),
        ]);
        $this->assertResponseIsSuccessful();

        $tools = $response->toArray()['result']['tools'];
        $this->assertContains(['name' => 'recipe_create', 'inputSchema' => ['type' => 'object', 'properties' => ['id' => ['readOnly' => true, 'type' => 'integer'], 'name' => ['type' => 'string'], 'description' => ['type' => 'string'], 'cookTime' => ['type' => ['string', 'null']], 'prepTime' => ['type' => ['string', 'null']]]]], $tools);
        $this->assertContains(['name' => 'recipe_upsert_by_id', 'inputSchema' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string'], 'name' => ['type' => 'string'], 'description' => ['type' => 'string'], 'cookTime' => ['type' => ['string', 'null']], 'prepTime' => ['type' => ['string', 'null']]], 'required' => ['id']]], $tools);
        $this->assertContains(['name' => 'recipe_update_by_id', 'inputSchema' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string'], 'name' => ['type' => 'string'], 'description' => ['type' => 'string'], 'cookTime' => ['type' => ['string', 'null']], 'prepTime' => ['type' => ['string', 'null']]], 'required' => ['id']]], $tools);
        $this->assertContains(['name' => 'recipe_delete_by_id', 'inputSchema' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string']], 'required' => ['id']]], $tools);
        $this->assertContains(['name' => 'recipe_retrieve_by_id', 'inputSchema' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string']], 'required' => ['id']]], $tools);
        $recipeListTool = array_values(array_filter($tools, fn ($t) => 'recipe_retrieve_list' === $t['name']))[0] ?? null;
        $this->assertNotNull($recipeListTool);
        $this->assertArrayHasKey('pageToken', $recipeListTool['inputSchema']['properties']);

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('resources/list'),
        ]);

        $this->assertResponseIsSuccessful();
        $resourceNames = array_map(fn ($r) => $r['name'], $response->toArray()['result']['resources']);
        $this->assertNotContains('recipe_retrieve_list', $resourceNames);
        $this->assertJsonContains([
            'result' => [
                'resources' => [
                    ['uri' => 'http://localhost/docs.jsonopenapi', 'name' => 'openapi_spec', 'description' => 'The OpenAPI specification for this API.', 'mimeType' => 'application/vnd.openapi+json'],
                    ['uri' => 'http://localhost/docs.jsonld', 'name' => 'hydra_docs', 'description' => 'The Hydra documentation for this API.', 'mimeType' => 'application/ld+json'],
                    ['uri' => 'http://localhost/entrypoint', 'name' => 'api_entrypoint', 'description' => 'The main entrypoint for the API.', 'mimeType' => 'application/ld+json'],
                ],
            ],
        ]);

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('resources/templates/list'),
        ]);

        $templateNames = array_map(fn ($r) => $r['name'], $response->toArray()['result']['resourceTemplates']);
        $this->assertNotContains('recipe_retrieve_by_id', $templateNames);
        $this->assertJsonContains([
            'result' => [
                'resourceTemplates' => [
                    [
                        'uriTemplate' => 'http://localhost/contexts/{shortName}',
                        'name' => 'jsonld_context',
                        'description' => 'The JSON-LD context for a given resource short name.',
                        'mimeType' => 'application/ld+json',
                    ],
                ],
            ],
        ]);

        $arguments = [
            'name' => 'Ratatouille',
            'description' => 'A traditional French Provençal stewed vegetable dish.',
        ];
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_create',
                'arguments' => $arguments,
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $createdRecipe = $response->toArray()['result'];
        $this->assertStringContainsString('Ratatouille', $createdRecipe['content'][0]['text']);
        $this->assertArraySubset($arguments, $createdRecipe['structuredContent']);
        $createdRecipeId = $createdRecipe['structuredContent']['id'];

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_retrieve_by_id',
                'arguments' => ['id' => (string) $createdRecipeId],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $readRecipe = $response->toArray()['result'];
        $this->assertStringContainsString('Ratatouille', $readRecipe['content'][0]['text']);
        $this->assertArraySubset($arguments, $readRecipe['structuredContent']);

        // Test collection tool with pagination
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_retrieve_list',
                'arguments' => ['itemsPerPage' => 2],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $list = $response->toArray()['result'];
        dd($list);
        $this->assertCount(2, $list['structuredContent']['items']);
        $this->assertArrayHasKey('nextPageToken', $list['structuredContent']);
        $nextPageToken = $list['structuredContent']['nextPageToken'];

        // Test next page
        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_retrieve_list',
                'arguments' => ['pageToken' => $nextPageToken],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $list = $response->toArray()['result'];
        $this->assertCount(2, $list['structuredContent']['items']);
        $this->assertArrayNotHasKey('nextPageToken', $list['structuredContent']);

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_upsert_by_id',
                'arguments' => [
                    'id' => $createdRecipeId,
                    'name' => 'Ratatouille Updated',
                    'description' => 'An updated description.',
                ],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Ratatouille Updated', $response->getContent());

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_update_by_id',
                'arguments' => [
                    'id' => $createdRecipeId,
                    'name' => 'Ratatouille Updated again',
                ],
            ]),
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Ratatouille Updated again', $response->getContent());

        $response = $client->request('POST', '/mcp', [
            'headers' => $this->mcpHeaders,
            'json' => $this->createJsonRpcRequest('tools/call', [
                'name' => 'recipe_delete_by_id',
                'arguments' => ['id' => (string) $createdRecipeId],
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
