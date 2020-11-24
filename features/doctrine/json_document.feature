Feature: Store a json document using dunglas/doctrine-json-odm

  @createSchema
  Scenario: Retrieve the json document
    Given there is a JsonDocument
    When I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/json_documents"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    [{
      "id": 1,
      "name": "My documents",
      "misc": {"foo": "foo", "bar": 1}
   }]
  """

  @createSchema
  Scenario: Create a json document (json-ld)
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/json_documents" with body:
    """
    {
      "name": "My Documents",
      "misc": {"foo": "foo", "bar": 1}
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/json_documents/1"
    And the header "Location" should be equal to "/json_documents/1"

  @createSchema
  Scenario: Create a json document (json)
    When I add "Accept" header equal to "application/json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/json_documents" with body:
    """
    {
      "name": "My Documents",
      "misc": {"foo": "foo", "bar": 1}
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the header "Content-Location" should be equal to "/json_documents/1"
    And the header "Location" should be equal to "/json_documents/1"
    And the JSON should be equal to:
    """
    {
      "id": 1,
      "name": "My Documents",
      "misc": { "foo": "foo", "bar": 1}
    }
    """

  @createSchema
  Scenario: Update a json document (json-ld)
    Given there is a JsonDocument
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/json_documents/1" with body:
    """
    {
      "@id": "/json_documents/1",
      "name": "My Documents updated",
      "misc": {"foo": "foo edited", "bar": 3}
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  @createSchema
  Scenario: Update a json document (json)
    Given there is a JsonDocument
    When I add "Accept" header equal to "application/json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "/json_documents/1" with body:
    """
    {
      "id": "1",
      "name": "My Documents updated",
      "misc": {"foo": "foo edited"}
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "id": 1,
      "name": "My Documents updated",
      "misc": {"foo": "foo edited", "bar": 1}
    }
    """

  @createSchema
  Scenario: Patch an item
    Given there is a JsonDocument
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/json_documents/1" with body:
    """
    {
      "id": 1,
      "name": "My Documents updated",
      "misc": {"foo": "foo edited"}
    }
    """
    Then I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/json_documents/1"
    And the JSON should be equal to:
    """
    {
      "id": 1,
      "name": "My Documents updated",
      "misc": {"foo": "foo edited", "bar": 1}
    }
    """

