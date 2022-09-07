Feature: sprintf argument count mismatch

  Background:
    Given I have the following config
    """
    <?xml version="1.0"?>
    <psalm errorLevel="1">
      <projectFiles>
        <directory name="."/>
      </projectFiles>
      <issueHandlers>
        <UnusedFunctionCall>
          <errorLevel type="suppress">
            <directory name="."/>
          </errorLevel>
        </UnusedFunctionCall>
      </issueHandlers>
      <plugins>
        <pluginClass class="Boesing\PsalmPluginStringf\Plugin"/>
      </plugins>
    </psalm>
    """
    And I have the following code preamble
    """
    <?php declare(strict_types=1);
    """

  Scenario: template has one specifier but zero arguments provided
    Given I have the following code
    """
      sprintf('%s');
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sprintf` requires 1 specifier but 0 are passed. |
    And I see no other errors

  Scenario: template has one specifier but two arguments provided
    Given I have the following code
    """
      sprintf('%s', '', '');
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooManyArguments | Template passed to function `sprintf` requires 1 specifier but 2 are passed. |
    And I see no other errors

  Scenario: template has one specifier but repeated multiple times
    Given I have the following code
    """
      sprintf('%s %1$s, foo bar, %1$s', '', '', '');
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooManyArguments | Template passed to function `sprintf` requires 1 specifier but 3 are passed. |
    And I see no other errors

  Scenario: template length exceeds IBM punch card length of 80 characters
    Given I have the following code
    """
      abstract class Foo
      {
          protected const TEMPLATE = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa%s';

          final public function bar(): string
          {
              return sprintf(self::TEMPLATE);
          }
      }
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sprintf` requires 1 specifier but 0 are passed. |
    And I see no other errors

  Scenario: template contains more than 1000 characters stored in a constant
    Given I have the following code
    """
      const TEMPLATE = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.

Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis a: %s';
      sprintf(TEMPLATE);
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sprintf` requires 1 specifier but 0 are passed. |
    And I see no other errors

  Scenario: template contains more than 1000 characters stored in a class constant
    Given I have the following code
    """
      final class Foo
      {
          public const TEMPLATE = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.

          Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis a: %s';
      }

      sprintf(Foo::TEMPLATE);
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sprintf` requires 1 specifier but 0 are passed. |
    And I see no other errors

  Scenario: template is stored in any class constant
    Given I have the following code
    """
      const TEMPLATE = '%s';
      sprintf(TEMPLATE);
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sprintf` requires 1 specifier but 0 are passed. |
    And I see no other errors
