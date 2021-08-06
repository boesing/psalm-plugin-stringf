Feature: printf argument type verification

  Background:
    Given I have the following config
    """
    <?xml version="1.0"?>
    <psalm errorLevel="1" findUnusedPsalmSuppress="true">
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
        <pluginClass class="Boesing\PsalmPluginStringf\Plugin">
          <experimental>
           <ReportPossiblyInvalidArgumentForSpecifier/>
          </experimental>
        </pluginClass>
      </plugins>
    </psalm>
    """
    And I have the following code preamble
    """
    <?php declare(strict_types=1);

    """

  Scenario: template requires string but invalid values are passed
    Given I have the following code
    """
      printf('%s', 1);
      printf('%s', 1.035);
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | PossiblyInvalidArgument | Argument 1 inferred as "int" does not match (any of) the suggested type(s) "string" |
      | PossiblyInvalidArgument | Argument 1 inferred as "float" does not match (any of) the suggested type(s) "string" |

  Scenario: template requires double but invalid values are passed
    Given I have the following code
    """
      printf('%d', 'foo');
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | PossiblyInvalidArgument | Argument 1 inferred as "string" does not match (any of) the suggested type(s) "float\|int\|numeric-string" |

  Scenario: template requires double and called with a numeric-string
    Given I have the following code
    """
      /** @var numeric-string $string */
      $string = '';
      printf('%d', $string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template requires double and called with a numeric-string which is automatically inferred
    Given I have the following code
    """
      $string = '12345';
      printf('%d', $string);
    """
    When I run Psalm
    Then I see no errors

  Scenario: template requires double and called with a mixed variable
  (non -int/-string/-float arguments are reported by psalm)
    Given I have the following code
    """
      /** @psalm-var mixed $string */
      $string = '';

      /** @psalm-suppress MixedArgument */
      printf('%d', $string);
    """
    When I run Psalm
    Then I see no errors
