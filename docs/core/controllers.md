# Creating Custom Operations and Controllers

Note: using custom controllers with API Platform is **discouraged**. Also, GraphQL is **not supported**.
[For most use cases, better extension points, working both with REST and GraphQL, are available](design.md).
If your endpoint is not concering a Resource or is an RPC endpoint (remote procedure call) we recommend to use a Symfony controller and to [extend the OpenAPI documentation](/docs/guide/extend-openapi-documentation). 

API Platform can leverage the Symfony routing system to register custom operations related to custom controllers. Such custom
controllers can be any valid [Symfony controller](http://symfony.com/doc/current/book/controller.html), including standard
Symfony controllers extending the [`Symfony\Bundle\FrameworkBundle\Controller\AbstractController`](http://api.symfony.com/4.1/Symfony/Bundle/FrameworkBundle/Controller/AbstractController.html)
helper class.

However, API Platform recommends to use **action classes** instead of typical Symfony controllers. Internally, API Platform
implements the [Action-Domain-Responder](https://github.com/pmjones/adr) pattern (ADR), a web-specific refinement of
[MVC](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93controller).

The distribution of API Platform also eases the implementation of the ADR pattern: it automatically registers action classes
stored in `api/src/Controller` as autowired services.

Thanks to the [autowiring](http://symfony.com/doc/current/components/dependency_injection/autowiring.html) feature of the
Symfony Dependency Injection container, services required by an action can be type-hinted in its constructor, it will be
automatically instantiated and injected, without having to declare it explicitly.

In the following examples, the built-in `GET` operation is registered as well as a custom operation called `post_publication`.

By default, API Platform uses the first `Get` operation defined to generate the IRI of an item and the first `GetCollection` operation to generate the IRI of a collection.

If your resource does not have any `Get` operation, API Platform automatically adds an operation to help generating this IRI.
If your resource has any identifier, this operation will look like `/books/{id}`. But if your resource doesn't have any identifier, API Platform will use the Skolem format `/.well-known/genid/{id}`.
Those routes are not exposed from any documentation (for instance OpenAPI), but are anyway declared on the Symfony routing and always return a HTTP 404.

If you create a custom operation, you will probably want to properly document it.
See the [OpenAPI](openapi.md) part of the documentation to do so.

Pick out one of these guide to handle a Controller with API Platform:
  - [use a custom controller](/docs/guide/custom-controller.php)
  - [use our placeholder action](/docs/guide/use-action-placeholder.php)
  - [use the route name configuration](/docs/guide/use-route-name-to-link-api-platform-and-the-symfony-router.php)

You may also want to [disable API Platform's automatic state retrieval](/docs/guide/read-false-to-prevent-state-retrieval.php), for example when using `POST` on a route with an identifier.

