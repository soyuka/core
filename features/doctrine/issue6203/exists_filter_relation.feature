Feature: Use an exists filter on a ManyToOne

  @!mongodb
  @createSchema
  Scenario: Create a Product and filter on its categories
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/issue6203_products" with body:
    """
    {}
    """
    Then the response status code should be 201
    And the response should be in JSON
    Then I send a "GET" request to "/issue6203_products?exists[productCategory]=0"
    And print last JSON response
