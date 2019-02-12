Feature: DTO input and output
  In order to use an hypermedia API
  As a client software developer
  I need to be able to use DTOs on my resources as Input or Output objects.

  @createSchema
  Scenario: Create a resource with a custom Input.
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_customs" with body:
    """
    {
      "foo": "test",
      "bar": 1
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyDtoCustom",
      "@id": "/dummy_dto_customs/1",
      "@type": "DummyDtoCustom",
      "lorem": "test",
      "ipsum": "1",
      "id": 1
    }
    """

  @createSchema
  Scenario: Get an item with a custom output
    Given there is a DummyCustomDto
    When I send a "GET" request to "/dummy_dto_custom_output/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "foo": {
          "@type": "@id"
        },
        "bar": {
          "@type": "@id"
        }
      },
      "@type": "CustomOutputDto",
      "foo": "test",
      "bar": 0
    }
    """

  @createSchema
  Scenario: Get a collection with a custom output
    Given there are 2 DummyCustomDto
    When I send a "GET" request to "/dummy_dto_custom_output"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be a superset of:
    """
    {
      "@context": "/contexts/DummyDtoCustom",
      "@id": "/dummy_dto_customs",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "foo": "test",
          "bar": 1
        },
        {
          "foo": "test",
          "bar": 2
        }
      ],
      "hydra:totalItems": 2
    }
    """

  @createSchema
  Scenario: Create a DummyCustomDto object without output
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_custom_post_without_output" with body:
    """
    {
      "lorem": "test",
      "ipsum": "1"
    }
    """
    Then the response status code should be 201
    And the response should be empty

  @createSchema
  Scenario: Create and update a DummyInputOutput
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_dto_input_outputs" with body:
    """
    {
      "foo": "test",
      "bar": 1
    }
    """
    Then the response status code should be 201
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "baz": {
          "@type": "@id"
        },
        "bat": {
          "@type": "@id"
        }
      },
      "@type": "OutputDto",
      "baz": 1,
      "bat": "test"
    }
    """
    Then I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_dto_input_outputs/1" with body:
    """
    {
      "foo": "test",
      "bar": 2
    }
    """
    Then the response status code should be 200
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http:\/\/example.com\/docs.jsonld#",
        "hydra": "http:\/\/www.w3.org\/ns\/hydra\/core#",
        "baz": {
          "@type": "@id"
        },
        "bat": {
          "@type": "@id"
        }
      },
      "@type": "OutputDto",
      "baz": 2,
      "bat": "test"
    }
    """

  @createSchema
  Scenario: Use DTO with relations on User
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/users" with body:
    """
    {
      "username": "soyuka",
      "plainPassword": "a real password",
      "email": "soyuka@example.com"
    }
    """
    Then the response status code should be 201
    Then I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/users/recover/1" with body:
    """
    {
      "user": "/users/1"
    }
    """
    Then the response status code should be 200
    And the JSON should be a superset of:
    """
    {
      "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "user": {
            "@type": "@id"
        }
      },
      "@type": "RecoverPasswordOutput",
      "user": {
        "@id": "/users/1",
        "@type": "User",
        "email": "soyuka@example.com",
        "fullname": null,
        "username": "soyuka"
      }
    }
    """

#  @createSchema
#  Scenario: Execute a GraphQL query on DTO
#    Given there are 2 DummyCustomDto
#    When I send the following GraphQL request:
#    """
#    {
#      dummyDtoCustom(id: "/dummy_dto_customs/1") {
#        lorem
#        ipsum
#      }
#    }
#    """
#    Then the response status code should be 200
#    And the response should be in JSON
#    And the header "Content-Type" should be equal to "application/json"
#    Then print last JSON response
#    And the JSON node "data.dummy.id" should be equal to "/dummies/1"
#    And the JSON node "data.dummy.name" should be equal to "Dummy #1"
