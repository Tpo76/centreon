<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Tester\Exception\PendingException;

/**
 * Defines application features from the specific context.
 */
class GeneratePollerContext extends CentreonContext
{
    /**
     * @Given a Centreon platform with multiple pollers
     */
    public function aCentreonPlatformWithMultiplePollers()
    {
        /* Go to the page to list pollers */
        $this->minkContext->visit('/centreon/main.php?p=60901');

        /* Wait page loaded */
        $this->spin(
            function ($context) {
                return $context->session->getPage()->has(
                    'css',
                    'input[name="searchP"]'
                );
            },
            30
        );
    }

    /**
     * @Given one poller is selected
     */
    public function onePollerIsSelected()
    {
        $inputPoller1 = $this->assertFind('css', 'input#poller_1');
        $inputPoller1->check();
    }

    /**
     * @Given multiple pollers are selected
     */
    public function multiplePollersAreSelected()
    {
        $inputPoller1 = $this->assertFind('css', 'input#poller_1');
        $inputPoller1->check();
        $inputPoller3 = $this->assertFind('css', 'input#poller_3');
        $inputPoller3->check();
    }

    /**
     * @Given I click on the button :arg1
     */
    public function iClickOnTheButton($arg1)
    {
        $applyConfigurationButton = $this->assertFind('css', 'input[name="' . $arg1 . '"]');
        $applyConfigurationButton->click();
    }

    /**
     * @When I am redirected to generate page
     */
    public function iAmRedirectedToGeneratePage()
    {
        /* Wait page loaded */
        $this->spin(
            function ($context) {
                return $context->session->getPage()->has(
                    'css',
                    'select#nhost'
                );
            },
            30
        );
    }

    /**
     * @When I select an other poller
     */
    public function iSelectAnOtherPoller()
    {
        $inputField = $this->assertFind('css', 'select#nhost');
        $choice = $inputField->getParent()->find('css', '.select2-selection');
        if (!$choice) {
            throw new \Exception('No select2 choice found');
        }
        $choice->press();

        $this->spin(
            function ($context) {
                return count($context->session->getPage()->findAll('css', '.select2-container--open li.select2-results__option')) == 2;
            },
            30
        );

        $chosenResults = $this->session->getPage()->findAll('css', '.select2-results li:not(.select2-results__option--highlighted)');
        foreach ($chosenResults as $result) {
            if ($result->getText() == "Central_1") {
                $result->click();
                break;
            }
        }
    }

    /**
     * @Then the pollers are already selected
     */
    public function thePollersAreAlreadySelected()
    {
        $applyConfigurationButton = $this->assertFind('css', 'select#nhost');
        $selectedPollers = $applyConfigurationButton->getValue();
        sort($selectedPollers);
        if ($selectedPollers != array("1", "3")) {
            throw new \Exception('Wrong selected pollers');
        }
    }

    /**
     * @Then poller configuration is generated
     */
    public function pollerConfigurationIsGenerated()
    {
        /* Wait configuration is generated */
        $this->spin(
            function ($context) {
                return count($context->session->getPage()->findAll('css', 'div#consoleDetails font[color="green"]')) == 2;
            },
            30
        );
    }
}
