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
      printf('%s %1$s, foo bar, %1$s', '');
    """
    When I run Psalm
    Then I see no errors

  Scenario: sscanf has two specifier but only one variable is passed to retrieve the result
    Given I have the following code
    """
      sscanf('1', '%d %d', $number);
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `sscanf` declares 2 specifier but only 1 argument is passed. |
    And I see no other errors

  Scenario: fscanf has two specifier but only one variable is passed to retrieve the result
    Given I have the following code
    """
      /** @psalm-var resource $resource */
      $resource = null;
      fscanf($resource, '%s %f', $foo);
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | TooFewArguments | Template passed to function `fscanf` declares 2 specifier but only 1 argument is passed. |
    And I see no other errors

