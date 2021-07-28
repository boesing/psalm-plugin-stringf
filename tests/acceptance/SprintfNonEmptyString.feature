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
