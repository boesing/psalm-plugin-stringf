Feature: Integer is not allowed to be passed to printf string placeholder when option is set

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
           <ReportPossiblyInvalidArgumentForSpecifier allowIntegerForString="no"/>
          </experimental>
        </pluginClass>
      </plugins>
    </psalm>
    """
    And I have the following code preamble
    """
    <?php declare(strict_types=1);

    """

  Scenario: template requires string but integer is passed
    Given I have the following code
    """
      printf('%s', 1);
      sprintf('%s', 1);
    """
    When I run Psalm
    Then I see these errors
      | Type  | Message |
      | PossiblyInvalidArgument | Argument 1 inferred as "1" does not match (any of) the suggested type(s) "string" |
      | PossiblyInvalidArgument | Argument 1 inferred as "1" does not match (any of) the suggested type(s) "string" |
    And I see no other errors
