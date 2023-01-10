# Link

* Status: Draft
* Deciders: @dunglas, @soyuka, @alanpoulain, @vincentchalamon

Implementation: [#5297][pull/5297], [#5275][pull/5275]

## Context and Problem Statement

API Platform is a Resource-oriented framework that currently supports persistence with:
 - Doctrine ORM
 - Doctrine ODM
 - Elasticsearch
 
In the past, several issues arised when users try to separate their **Resource** configuration from the actual persistence class. This is very common when using
Doctrine where you have a doctrine Entity used by one or more resources. Therefore, persistence systems need specific options that are not present in the base resource.

How do we specify state specific options without impacting the developer experience?

## Considered Options

1. Decline Resource and Operation attributes in the state namespace with specific options.

When adding an option that is related to a specific state system (eg: Doctrine, MongoDB, Elasticsearch) we decling the Operation classes in that namespace. The developer uses the Operation he wants. 

#### Elasticsearch example

In this example, Elasticsearch needs an `index` mapping. 

```
use ApiPlatform\Elasticsearch\Metadata\ApiResource;

#[ApiResource(index: "my-index")]
class MyResource {}
```

#### MongoDB example

We want to use `['cursor' => true]` in the `getAggregation` method:

```
use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Doctrine\Odm\Metadata\GetCollection;

#[GetCollection(cursor: true)]
```

#### ORM example

We want to specify an entityClass to use with Doctrine ORM:

```
use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Doctrine\Orm\Metadata\GetCollection;

#[GetCollection(entityClass: Foo::class)]
```

2. Use a `stateOptions` property within Resource and Operation to fill the wanted options.

#### Elasticsearch example

In this example, Elasticsearch needs an `index` mapping. But this also enables the `ElasticsearchProvider`.

```
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(options: new Options(index: "my-index")]
class MyResource {}
```

#### MongoDB example

We want to use `['cursor' => true]` in the `getAggregation` method:

```
use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Metadata\GetCollection;

#[GetCollection(stateOptions: new Options(cursor: true))]
```

#### ORM example

We want to specify an entityClass to use with Doctrine ORM:

```
use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(stateOptions: new Options(cursor: true))]
```

2. Use a `stateOptions` property within Resource and Operation to fill the wanted options.

## Decision Outcome

...

[pull/5297]: https://github.com/api-platform/core/pull/5297 "Elasticsearch stateOptions implementation"
[pull/5275]: https://github.com/api-platform/core/pull/5275 "Doctrine entityClass concept separation"
