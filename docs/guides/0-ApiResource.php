<?php

// # API Resource
// This class represents an API resource
namespace App\ApiResource;

// The `#[ApiResource]` attribute registers this class as an HTTP resource.
use ApiPlatform\Metadata\ApiResource;
// These are the list of HTTP operations we use to declare a "CRUD" (Create, Read, Update, Delete).
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;

// Each resource has its set of Operations.
// Note that the uriTemplate may use the `id` variable which is our unique
// identifier on this `Book`.
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/books/{id}'),
        // The GetCollection operation returns a list of Books.
        new GetCollection(uriTemplate: '/books'),
        new Post(uriTemplate: '/books'),
        new Patch(uriTemplate: '/books/{id}'),
        new Delete(uriTemplate: '/books/{id}'),
    ],
    exceptionToStatus: [

    ]
)]
// If a property named `id` is found it is the property used in your URI template
// we recommend to use public properties to declare API resources.
class Book
{
    public string $id;
}
// Select the next example to see how to hook a persistence layer.
