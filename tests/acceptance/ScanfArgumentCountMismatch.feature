Feature: scanf argument count mismatch

  Background:
    Given I have the following config
    """
    <?xml version="1.0"?>
    <psalm errorLevel="1" findUnusedBaselineEntry="true" findUnusedCode="false">
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

  Scenario: sscanf is returning the values of the parameters in case no parameters are passed
    Given I have the following code
    """
      $value = '1';
      $result = sscanf($value, '%d');
      assert($result !== null);
    """
    When I run psalm
    Then I see no errors

  Scenario: fscanf is returning the values of the parameters in case no parameters are passed
    Given I have the following code
    """
      $result = fscanf(STDIN, '%d');
      assert(isset($result[0]));
    """
    When I run psalm
    Then I see no errors

