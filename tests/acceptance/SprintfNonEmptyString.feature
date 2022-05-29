Feature: non empty template passed to sprintf results in non-empty-string
  Non empty templates should be detected as non-empty string

  Background:
    Given I have the following config
    """
    <?xml version="1.0"?>
    <psalm errorLevel="1">
      <projectFiles>
        <directory name="."/>
      </projectFiles>
      <plugins>
        <pluginClass class="Boesing\PsalmPluginStringf\Plugin"/>
      </plugins>
    </psalm>
    """
    And I have the following code preamble
    """
    <?php declare(strict_types=1);
    /**
    * @param non-empty-string $_
    */
    function nonEmptyString(string $_): void
    {}
    """

  Scenario: template is empty but value which is passed to the string is boolean (true)
    Given I have the following code
    """
      /**
       * @psalm-suppress InvalidScalarArgument Ignore the fact that we are passing `true` to sprintf for testing purposes
       * @psalm-suppress InvalidArgument With Psalm v5, `sprintf` usage with non-(`float`|`int`|`string`) is being reported.
       */
      $string = sprintf('%s', true);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template contains a whitespace
    Given I have the following code
    """
      $string = sprintf(' %s', '');
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template contains a character
    Given I have the following code
    """
      $string = sprintf('%sa', '');
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template contains a number
    Given I have the following code
    """
      $string = sprintf('%s0', '');
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template contains special character
    Given I have the following code
    """
      $string = sprintf('%s!', '');
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is provided as variable
    Given I have the following code
    """
      $template = '%s!';
      $string = sprintf($template, '');
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is provided as variable with string from constant
    Given I have the following code
    """
      const FOO = '%s ';
      $template = FOO;
      $string = sprintf($template, '');
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is empty but variable which is passed to the string is non-empty-string
    Given I have the following code
    """
      $variable = ' ';
      $string = sprintf('%s', $variable);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is empty but variable which is passed to the string is known and numeric
    Given I have the following code
    """
      $variable = '1';
      $string = sprintf('%d', $variable);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is empty but variable which is passed to the string is numeric
    Given I have the following code
    """
      /** @psalm-var numeric-string */
      $variable = '';
      $string = sprintf('%d', $variable);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is empty but variable which is passed to the string is special character
    Given I have the following code
    """
      $variable = '?';
      $string = sprintf('%s', $variable);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is empty and almost all variables are empty but the last variable which is passed to the string is numeric
    Given I have the following code
    """
      $variable = '1';
      $string = sprintf('%s%s%d', '', '', $variable);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is empty but variable which is passed to the string is integer
    Given I have the following code
    """
      $variable = 1;
      $string = sprintf('%d', $variable);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is empty but value which is passed to the string is integer
    Given I have the following code
    """
      $string = sprintf('%d', 1);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is empty but value which is passed to the string is float
    Given I have the following code
    """
      $string = sprintf('%d', 0.9);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template is empty and value which is passed to the string is boolean (false)
    Given I have the following code
    """
      /** @psalm-suppress InvalidArgument Ignore the fact that we are passing `false` to sprintf for testing purposes */
      $string = sprintf('%s', false);
      nonEmptyString($string);
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | ArgumentTypeCoercion | Argument 1 of nonEmptyString expects non-empty-string, parent type string provided |
    And I see no other errors

  Scenario: template is empty but value which is passed to the string is of type non-empty-string
    Given I have the following code
    """
      /** @psalm-var non-empty-string $nonEmptyString */
      $nonEmptyString = '';
      $string = sprintf('%s', $nonEmptyString);
      nonEmptyString($string);
    """
    When I run psalm
    Then I see no errors

  Scenario: template gets passed float argument without knowing its value
    Given I have the following code
    """
      /** @psalm-var float $float */
      $float = 0.00;
      $string = sprintf('%0.2f', $float);
      nonEmptyString($string);
    """
    When I run psalm
    Then I see these errors
      | Type  | Message |
      | ArgumentTypeCoercion | Argument 1 of nonEmptyString expects non-empty-string, parent type string provided |
    And I see no other errors

  Scenario: template is non-empty and loaded from constant
    Given I have the following code
    """
      namespace {
        const CONSTANT_WITH_NON_EMPTY_STRING = 'bar baz %s';

        final class Foo
        {
             public const CONSTANT_WITH_NON_EMPTY_STRING = 'foo bar %s';

             /** @return non-empty-string */
             public function createNonEmptyString(): string
             {
                  return sprintf(self::CONSTANT_WITH_NON_EMPTY_STRING, 'baz');
             }
        }

        nonEmptyString(sprintf(Foo::CONSTANT_WITH_NON_EMPTY_STRING, 'baz'));
        nonEmptyString(sprintf(\DifferentNamespace\Whatever::CONSTANT_WITH_NON_EMPTY_STRING, 'baz'));
        /** @psalm-suppress ArgumentTypeCoercion Can be removed after https://github.com/vimeo/psalm/issues/7920 got fixed */
        nonEmptyString(sprintf(\DifferentNamespace\CONSTANT_WITH_NON_EMPTY_STRING, 'baz'));
        nonEmptyString(sprintf(CONSTANT_WITH_NON_EMPTY_STRING, 'baz'));
      }
      namespace DifferentNamespace {
        const CONSTANT_WITH_NON_EMPTY_STRING = 'bar baz %s';
        final class Whatever
        {
            public const CONSTANT_WITH_NON_EMPTY_STRING = 'foo bar %s';
        }
      }
    """
    When I run psalm
    Then I see no errors

  Scenario: template is non-empty because non-empty-string is being returned from called function
    Given I have the following code
    """
      /** @return non-empty-string */
      function createNonEmptyString(): string
      {
          return 'foo';
      }

      nonEmptyString(sprintf('%s', createNonEmptyString()));
    """
    When I run psalm
    Then I see no errors


