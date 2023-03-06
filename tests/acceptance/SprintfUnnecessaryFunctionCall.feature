Feature: unnecessary sprintf function call

  Background:
    Given I have the following config
    """
    <?xml version="1.0"?>
    <psalm errorLevel="1" findUnusedPsalmSuppress="true" findUnusedBaselineEntry="true" findUnusedCode="false">
      <projectFiles>
        <directory name="."/>
      </projectFiles>
      <plugins>
        <pluginClass class="Boesing\PsalmPluginStringf\Plugin">
          <experimental>
           <ReportUnnecessaryFunctionCalls/>
          </experimental>
        </pluginClass>
      </plugins>
      <issueHandlers>
        <UnusedFunctionCall>
          <errorLevel type="suppress">
            <directory name="."/>
          </errorLevel>
        </UnusedFunctionCall>
      </issueHandlers>
    </psalm>
    """
    And I have the following code preamble
    """
    <?php declare(strict_types=1);

    """

  Scenario: template is empty
    Given I have the following code
    """
      sprintf('');
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | UnnecessaryFunctionCall | Function call is unnecessary as there is no placeholder within the template |
    And I see no other errors

  Scenario: template contains no identifier
    Given I have the following code
    """
      sprintf('Foo bar baz');
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | UnnecessaryFunctionCall | Function call is unnecessary as there is no placeholder within the template |
    And I see no other errors
