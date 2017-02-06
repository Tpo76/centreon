<?php

use Centreon\Test\Behat\CentreonContext;
use Centreon\Test\Behat\CustomViewsPage;

class CustomViewsContext extends CentreonContext
{
    private $customViewName;

    /**
     *  Build a new context.
     */
    public function __construct()
    {
        $this->customViewName = 'AcceptanceTestCustomView';
    }

    /**
     *  @Given I am logged in a Centreon server with some widgets
     */
    public function iAmLoggedInCentreonWithWidgets()
    {
        $this->launchCentreonWebContainer('web_widgets');
        $this->iAmLoggedIn();
    }

    /**
     *  @Given a publicly shared custom view
     */
    public function aPubliclySharedCustomView()
    {
        $page = new CustomViewsPage($this);

        $page->showEditBar();
        $page->createNewView($this->customViewName, 2, true);

        $this->spin(
            function ($context)  {
                return $this->assertFind('css', '#ui-tabs-1');
            },
            30,
            'First custom view not create'
        );

        $page->createNewView($this->customViewName, 2, true);

        $this->spin(
            function ($context)  {
                return $this->assertFind('css', '#ui-tabs-2');
            },
            30,
            'Second custom view not create'
        );

        $page->addWidget('First widget', 'Host Monitoring');

        $this->spin(
            function ($context)  {
                return $this->assertFind('css', 'div.portlet-header span#title_1');
            },
            30,
            'First widget not create'
        );

        $page->addWidget('Second widget', 'Service Monitoring');

        $this->spin(
            function ($context)  {
                return $this->assertFind('css', 'div.portlet-header span#title_2');
            },
            30,
            'Second widget not create'
        );



        $page->shareView(1, 'guest');



        sleep(999999);

        $this -> iAmLoggedOut();
    }

    /**
     *  @Given a user is using the shared view
     *  @Given the user is using the shared view
     */
    public function aUserIsUsingThisSharedView()
    {
        /* $page = new ContactConfigurationBranch($this); */

    }

    /**
     *  @Given a custom view shared in read only with a user
     */
    public function aCustomViewSharedInReadOnlyWithAUser()
    {
        // XXX
    }

    /**
     *  @When a user wishes to add a new custom view
     *  @When the user wishes to add a new custom view
     */
    public function anotherUserWishesToAddANewCustomView()
    {
        // Nothing to do here, the view creation will be made
        // with a single call, in the next step.
    }

    /**
     *  @When he removes the shared view
     */
    public function thisOtherUserRemovesTheSharedView()
    {
        // XXX
    }

    /**
     *  @When the owner modifies the custom view
     */
    public function theOwnerModifiesTheCustomView()
    {
        // XXX
    }

    /**
     *  @When the owner removes the view
     */
    public function theOwnerRemovesTheView()
    {
        // XXX
    }

    /**
     *  @Then he can add the shared view
     */
    public function heCanAddTheSharedView()
    {
        $this->parameters['centreon_user'] = $this->user ;
        $this->iAmLoggedIn();

        $page = new CustomViewsPage($this);
        $page->loadView($this->customViewName);

    }

    /**
     *  @Then he cannot modify the content of the shared view
     */
    public function heCannotModifyTheContentOfTheSharedView()
    {
        if(!$this->assertFind('css', '.editView btnAction')->getAttribute('aria-disabled')){
            throw new Exception('The user can edit the view');
        };
    }

    /**
     *  @Then the view is not visible anymore
     */
    public function theViewIsNotVisibleAnymore()
    {
        // XXX
    }

    /**
     *  @Then the user can use it again
     */
    public function theUserCanUseItAgain()
    {
        // XXX
    }

    /**
     *  @Then the changes are reflected on all users displaying the custom view
     */
    public function theChangesAreReflectedOnAllUsersDisplayingTheCustomView()
    {
        // XXX
    }

    /**
     *  @Then the view is removed for all users displaying the custom view
     */
    public function theViewIsRemovedForAllUsersDisplayingTheCustomView()
    {
        // XXX
    }
}
