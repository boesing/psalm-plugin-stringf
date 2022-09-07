Feature: printf argument count mismatch

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
      printf('%s');
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `printf` requires 1 specifier but 0 are passed. |
    And I see no other errors

  Scenario: template has one specifier but two arguments provided
    Given I have the following code
    """
      printf('%s', '', '');
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooManyArguments | Template passed to function `printf` requires 1 specifier but 2 are passed. |
    And I see no other errors

  Scenario: template has one specifier but repeated multiple times
    Given I have the following code
    """
      printf('%s %1$s, foo bar, %1$s', '', '', '');
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooManyArguments | Template passed to function `printf` requires 1 specifier but 3 are passed. |
    And I see no other errors

  Scenario: template length exceeds IBM punch card length of 80 characters
    Given I have the following code
    """
      abstract class Foo
      {
          protected const TEMPLATE = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa%s';

          final public function bar(): void
          {
              printf(self::TEMPLATE);
          }
      }
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `printf` requires 1 specifier but 0 are passed. |
    And I see no other errors

  Scenario: template is stored in any class constant
    Given I have the following code
    """
      const TEMPLATE = '%s';
      printf(TEMPLATE);
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `printf` requires 1 specifier but 0 are passed. |
    And I see no other errors

  Scenario: template is stored in a class constant using double quotes with newlines (concatenated)
    Given I have the following code
    """
      class Foo
      {
          private const MESSAGE = "%s,\n\n" .
          'Jada jada %s Foo barBaz qoo ook, foo. ' .
          "Yada yada, foo bar baz qoo %s!\n\n" .
          'YOLO test: %s';

          public function test(): void
          {
              printf(self::MESSAGE, 'foo', 'bar', 'baz');
          }
      }
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sprintf` requires 4 specifier but 3 are passed. |
    And I see no other errors

  Scenario: template is stored in a class constant using double quotes with newlines
    Given I have the following code
    """
      class Foo
      {
          private const MESSAGE = "Foo bar baz: %s of %s with %s.\n\nFoo bar baz ooq: %s";

          public function test(): void
          {
                printf(self::MESSAGE, 'foo', 'bar', 'baz');
          }
      }
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sprintf` requires 4 specifier but 3 are passed. |
    And I see no other errors

  Scenario: template is stored in a class constant using nowdoc
    Given I have the following code
    """
      class Baz
      {
          private const MESSAGE = <<<'FOO'
              YADA YADA bar
              %s, %s, %s
              FOO;

          public function test(): void
          {
                printf(self::MESSAGE, 'foo', 'bar');
          }
      }
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sprintf` requires 3 specifier but 2 are passed. |
    And I see no other errors


  Scenario: template is stored in a class constant using heredoc
    Given I have the following code
    """
      class Baz
      {
          private const MESSAGE = <<<FOO
YADA YADA bar
%s, %s, %s
FOO;

          public function test(): void
          {
                printf(self::MESSAGE, 'foo', 'bar');
          }
      }
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sprintf` requires 3 specifier but 2 are passed. |
    And I see no other errors
