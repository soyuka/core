Feature: Issue 5926
  In order to reproduce the issue at https://github.com/api-platform/core/issues/5926
  As a client software developer
  I need to be able to use every operation on a resource with non-resources embed objects

  Scenario: Create and retrieve a WriteResource
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/issue5926"
    Then print last JSON response
