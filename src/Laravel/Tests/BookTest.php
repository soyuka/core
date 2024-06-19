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

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Book;

class BookTest extends TestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    public function testGetCollection(): void
    {
        $response = $this->get('/api/books');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Book',
            '@id' => '/api/books',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 10,
        ]);
        $response->assertJsonCount(5, 'hydra:member');
    }

    public function testGetBook(): void
    {
        $book = Book::find(1);
        $response = $this->get('/api/books/1');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Book',
            '@id' => '/api/books/1',
            '@type' => 'Book',
            'id' => 1,
            'name' => $book->name, // @phpstan-ignore-line
        ]);
    }

    public function testCreateBook(): void
    {
        $response = $this->postJson(
            '/api/books',
            [
                'name' => 'Don Quichotte',
            ],
            [
                'Accept' => 'application/ld+json',
                'CONTENT_TYPE' => 'application/ld+json',
            ]
        );

        $response->assertStatus(201);
        $response->assertHeader('content-type', 'application/ld+json; charset=utf-8');
        $response->assertJsonFragment([
            '@context' => '/api/contexts/Book',
            '@type' => 'Book',
            'name' => 'Don Quichotte',
        ]);
        $this->assertMatchesRegularExpression('~^/api/books/\d+$~', $response->json('@id'));
    }

    public function testUpdateBook(): void
    {
        $iri = '/api/books/1';
        $response = $this->putJson(
            $iri,
            [
                'name' => 'updated title',
            ],
            [
                'Accept' => 'application/ld+json',
                'CONTENT_TYPE' => 'application/ld+json',
            ]
        );
        $response->assertStatus(200);
        $response->assertJsonFragment([
            '@id' => $iri,
            'name' => 'updated title',
        ]);
    }

    public function testPatchBook(): void
    {
        $iri = '/api/books/1';
        $response = $this->patchJson(
            $iri,
            [
                'name' => 'updated title',
            ],
            [
                'Accept' => 'application/ld+json',
                'CONTENT_TYPE' => 'application/merge-patch+json',
            ]
        );
        $response->assertStatus(200);
        $response->assertJsonFragment([
            '@id' => $iri,
            'name' => 'updated title',
        ]);
    }

    public function testDeleteBook(): void
    {
        $response = $this->delete('/api/books/1');
        $response->assertStatus(204);
        $this->assertNull(Book::find(1));
    }
}