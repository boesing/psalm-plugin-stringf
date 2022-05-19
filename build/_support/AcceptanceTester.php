<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */

    /**
     * @Given I have Psalm newer than :arg1 because of :arg2
     */
    public function iHavePsalmNewerThanBecauseOf($arg1, $arg2)
    {
        $this->havePsalmOfACertainVersionRangeBecauseOf('newer than', $arg1, $arg2);
    }

    /**
     * @Given I have Psalm older than :arg1 because of :arg2
     */
    public function iHavePsalmOlderThanBecauseOf($arg1, $arg2)
    {
        $this->havePsalmOfACertainVersionRangeBecauseOf('older than', $arg1, $arg2);
    }
}
