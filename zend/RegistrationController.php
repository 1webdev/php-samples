<?php

class User_RegistrationController extends System_Controller_Action
{
    public function init()
    {
        // Redirect logged in users away 
        if ($this->getUserId()) {
            if ($this->_helper->adblade->isCobrand()) {
                $this->_helper->redirector->gotoSimpleAndExit(null,'ads');
            } else {
                $this->_helper->redirector->gotoSimpleAndExit('account','control');
            }
        }

        // Set custom layout for cobrands
        if ($this->_helper->adblade->isCobrand()) {
            $this->getHelper('layout')->getLayoutInstance()->setLayout('cobrand/cobrand-registration');
        } else {
            $this->getHelper('layout')->getLayoutInstance()->setLayout('home');
        }
    }

    public function indexAction()
    {
        $this->_helper->redirector->gotoUrlAndExit('/registration/advertiser');
    }

    public function getWizardBreadCrumbs()
    {
        $model  = App_Model_Models::getUsersModel();
        $wizard = $model->getPublisherRegWizardForm();

        $pages  = array();
        foreach ($wizard->getSubForms() as $form) {
            $pages[$form->getName()] = $form->getDescription();
        }

        $passed   = array();
        $lastPage = null;
        foreach ($this->getSessionNamespace() as $name => $data) {
            $passed[] = $name;
            $lastPage = $name;
        }

        /*
         * Obviously all passed forms(pages) should be freely editable.
         * As well as the next form after last passed(stored), since we do not
         * clear stored forms data on jumps between pages(steps).
         */
        $editable = $passed;
        if ($lastPage) {
            $allPages    = $this->getAllSubFormNames();
            $nextPageKey = array_search($lastPage, $allPages) + 1;
            if (isset($allPages[$nextPageKey])) {
                $editable = $editable + array($nextPageKey => $allPages[$nextPageKey]);
            }
        }

        $this->view->assign(array(
            'pages'           => $pages,
            'active'          => $this->getNextSubForm()->getName(),
            'highLightPassed' => true,
            'passed'          => $passed,
            'editable'        => $editable
        ));

        return $this->view->render('/registration/bread-crumbs.phtml');
    }

    /**
     * @param  string|Zend_Form_SubForm $subForm    Form name or object
     * @param  string                   $param      Element name to return value for if exists
     * @param  null                     $default    Default value to return in case provided element has no value set
     * @return mixed
     * @throws Exception
     */
    public function getSubFormValues($subForm, $param = '', $default = null)
    {
        return App_Model_Models::getUsersModel()->getPublisherRegWizardSubFormValues(
            $subForm, $param, $default
        );
    }

    /**
     * Return values for all stored forms merged into single array
     * @return array
     */
    public function getSubFormsValues()
    {
        return App_Model_Models::getUsersModel()->getPublisherRegWizardSubFormsValues();
    }

    /**
     * Is the sub form valid?
     *
     * @param  Zend_Form_SubForm $subForm
     * @param  array             $data
     * @return bool
     */
    public function subFormIsValid(Zend_Form_SubForm $subForm, array $data)
    {
        $name = $subForm->getName();

        // Dissolve data array by sub-form name
        if (isset($data[$name])) {
            $data = $data[$name];
        }

        if ($subForm->isValid($data)) {

            $values = $subForm->getValues();

            /*
             * Check if given sub-form has nested sub-forms and if so
             * return values only for that nested sub-form which was submitted
             */
            if ($subForm->getSubForms()) {

                $nestedSubFormName = key($data);
                if ($nestedSubFormName) {

                    $subFormValues = current($values);
                    foreach ($subFormValues as $key => $val) {
                        if ($key !== $nestedSubFormName) {
                            unset($subFormValues[$key]);
                        }
                    }

                    // Maintain proper data structure
                    $values = array($name => $subFormValues);
                }
            }

            App_Model_Models::getUsersModel()->setPublisherRegWizardStoredForm($name, $values);
            return true;
        }

        return false;
    }

    /**
     * Is the full form valid?
     * @return bool
     */
    public function wizardIsComplete()
    {
        return array_diff($this->getAllSubFormNames(), $this->getStoredForms())
               ? false
               : true;
    }

    /**
     * Return the session namespace we're using
     * @return Zend_Session_Namespace
     */
    public function getSessionNamespace()
    {
        return App_Model_Models::getUsersModel()->getPublisherRegWizardSessionNS();
    }

    /**
     * Clears current session
     */
    public function clearSessionNamespace()
    {
        App_Model_Models::getUsersModel()->clearPublisherRegWizardSessionNS();
    }

    /**
     * Return a list of forms already stored in the session
     * @return array
     */
    public function getStoredForms()
    {
        return App_Model_Models::getUsersModel()->getPublisherRegWizardStoredForms();
    }

    /**
     * Return list of all sub form names available
     * @return array
     */
    public function getAllSubFormNames()
    {
        return App_Model_Models::getUsersModel()->getPublisherRegWizardSubFormNames();
    }

    /**
     * Return sub form which was submitted last
     * @return false|Zend_Form_SubForm
     */
    public function getSubmittedSubForm()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return false;
        }

        $form = App_Model_Models::getUsersModel()->getPublisherRegWizardForm();

        foreach ($this->getAllSubFormNames() as $name) {
            if ($data = $request->getPost($name, false)) {
                if (is_array($data)) {
                    return $form->getSubForm($name);
                    break;
                }
            }
        }

        return false;
    }

    /**
     * Return the next sub form to display
     * @return Zend_Form_SubForm|false
     */
    public function getNextSubForm()
    {
        $storedForms    = $this->getStoredForms();
        $potentialForms = $this->getAllSubFormNames();
        $wizard         = App_Model_Models::getUsersModel()->getPublisherRegWizardForm();

        $step = $this->getRequest()->getPost('stepName');
        if (in_array($step, $storedForms)) {
            return $wizard->getSubForm($step);
        }

        foreach ($potentialForms as $name) {
            if (!in_array($name, $storedForms)) {
                return $wizard->getSubForm($name);
            }
        }

        return false;
    }

    public function publisherSignupAction()
    {
        $model  = App_Model_Models::getUsersModel();
        $wizard = $model->getPublisherRegWizardForm();

        // Either re-display the current page, or grab the "next"
        // (first) sub form
        $form = $this->getSubmittedSubForm();
        if (!$form) {
            $form = $this->getNextSubForm();
            if (!$form) {
                $this->clearSessionNamespace();
                $this->redirect('/registration/publisher-signup');
            }
        }

        $this->view->assign(array(
            'form'                  => $wizard->prepareSubForm($form),
            'breadCrumbs'           => $this->getWizardBreadCrumbs(),
            'hidePremiumPublishers' => true,
            'useBootstrapFramework' => true
        ));
    }

    public function publisherSignupCompleteAction()
    {
        $this->view->assign('hidePremiumPublishers', true);
    }

    public function publisherSignupProcessAction()
    {
        $usersModel = App_Model_Models::getUsersModel();
        $wizard     = $usersModel->getPublisherRegWizardForm();

        $form = $this->getSubmittedSubForm();
        if (!$form) {
            return $this->forward('publisher-signup');
        }

        $this->view->assign('hidePremiumPublishers', true);
        $this->view->assign('useBootstrapFramework', true);

        if (!$this->subFormIsValid($form, $this->getRequest()->getPost())) {
            $this->view->assign('form', $wizard->prepareSubForm($form));
            $this->view->assign('breadCrumbs', $this->getWizardBreadCrumbs());
            return $this->render('publisher-signup');
        }

        if (!$this->wizardIsComplete()) {

            $values = $this->getSubFormValues(
                User_Form_PublisherRegWizard::SUBFORM_NAME_YOUR_TRAFFIC
            );

            /*
             * Check last submitted form is Contact Info form and there exists
             * traffic volume specified one the preceding (Your Traffic) form.
             *
             * Next check it's high volume (5M) publisher sign up and re-route
             * workflow if so.
             */
            if ($form->getName() == User_Form_PublisherRegWizard::SUBFORM_NAME_CONTACT_INFO) {

                /*
                 * Looks like session has timed out and we can't get values
                 * from the first step. Force user to start all over again.
                 */
                if (!$values) {
                    $this->clearSessionNamespace();
                    return $this->redirect('/registration/publisher-signup');
                }

                if ($values['trafficVolume'] == User_Form_SubForm_PublisherYourTraffic::TRAFFIC_VOLUME_HIGH) {
                    $usersModel->register($this->getSubFormsValues(), UsersTable::PUBLISHER_ROLE);

                    $this->clearSessionNamespace();
                    App_Model_Functions::updateUserLoginStatus(false);
                    return $this->redirect('/registration/publisher-signup-complete');
                }
            }

            $form = $this->getNextSubForm();
            $this->view->assign('form', $form);
            $this->view->assign('breadCrumbs', $this->getWizardBreadCrumbs());
            return $this->render('publisher-signup');
        }

        // Register low & med traffic volume publishers
        $pubId = $usersModel->register($this->getSubFormsValues(), UsersTable::PUBLISHER_ROLE);

        /*
         * Also update their payment info, allow ad zone builder and set initial
         * zones list allowed
         */
        if ($pubId) {
            $usersModel->setPublisherPaymentInfo(
                $this->getSubFormValues(User_Form_PublisherRegWizard::SUBFORM_NAME_PAYMENT_INFO),
                $pubId
            );
            App_Model_Models::getPubModel()->enableSelfServeBuilder($pubId);
        }

        $this->clearSessionNamespace();

        $this->_helper->getHelper('viewRenderer')
                      ->setScriptAction('publisher-signup-thank-you');
    }

    /**
     * Register a publisher: step one - complete short registration form
     */
    public function publisherAction()
    {
        if ($this->_helper->adblade->isCobrand()) {
            $this->_helper->redirector->gotoUrl('/registration/advertiser');
        }

        $userModel = $this->getModel()->getUsersModel();
        $form      = $userModel->getAdvPubRegFormPart1();

        if ($this->getRequest()->isPost()) {
            $postData  = $this->getRequest()->getPost();

            $shortRegId = (int) $userModel->saveAdvPubRegFormPart1(
                $postData, false, null,
                $this->_getParam(App_Model_DbTable_UserRegReferral::REG_REFERRER_PARAM_NAME)
            );
            if ($shortRegId > 0) {
                $params = array('shortRegId'=>$shortRegId);
                return $this->forward('publisher-complete', null, null, $params);

            }
        }

        $this->view->assign('form', $form);
    }

    /**
     * Register a publisher: step two - complete full registration form
     */
    public function publisherCompleteAction()
    {
        if ($this->_helper->adblade->isCobrand()) {
            $this->_helper->redirector->gotoUrl('/registration/advertiser');
        }
        
        $shortRegId  = (int) $this->_getParam('shortRegId');
        $shortRegTbl = App_Model_Tables::getShortRegistrationTable();
        
        // Simple security check to prevent bypass of the short reg form
        if (! $shortRegTbl->check($shortRegId)) {
            $this->redirect('registration/publisher');
        }
        
        $userModel  = $this->getModel()->getUsersModel();
        $request    = $this->getRequest();
        $form       = $userModel->getAdvPubRegFormFull(
                          UsersTable::PUBLISHER_ROLE,
                          null,
                          $shortRegId
                      );
        
        if ($request->isPost()) {
            $formData = $request->getPost();
            if ($userModel->registration(
                    $request,
                    $formData,
                    UsersTable::PUBLISHER_ROLE,
                    null,
                    $shortRegId
                )
            ) {
                $this->_helper->redirector->gotoSimpleAndExit(null, 'pub');
            }
        }
        
        $this->view->assign('form', $form);
        $this->view->headScript()->appendFile('/js/jquery.uniform.min.js');
        $this->view->headLink()->appendStylesheet('/css/uniform.white-theme.css');
    }

    /**
     * Register an advertiser: step one - complete short registration form
     */
    public function advertiserAction()
    {
        $userModel = $this->getModel()->getUsersModel();
        $refAppId  = (int)$this->_getParam('refAppId');
        $isCobrand = $this->_helper->adblade->isCobrand();
        $request   = $this->getRequest();

        /*
         * Get complete (one-step) registration form for cobrands and
         * multi-part registration form for regular advertisers
         */
        $form = $isCobrand
            ? $userModel->getRegistrationForm()
            : $userModel->getAdvPubRegFormPart1();

        if ($request->isPost()) {
            $postData  = $request->getPost();

            if ($isCobrand) {
                if ($userModel->registration(
                              $request,
                                  $postData,
                                  UsersTable::ADVERTISER_ROLE,
                                  $refAppId,
                                  null,
                                  $isCobrand
                )
                ) {
                    $this->redirect('/wizard');
                }
            } else {
                $shortRegId = (int) $userModel->saveAdvPubRegFormPart1(
                                              $postData, false, null,
                                                  $this->_getParam(App_Model_DbTable_UserRegReferral::REG_REFERRER_PARAM_NAME)
                );
                if ($shortRegId > 0) {
                    $params = array('shortRegId' => $shortRegId, 'refAppId' => $refAppId);
                    return $this->forward('advertiser-complete', null, null, $params);
                }
            }
        }

        $this->view->assign('form', $form);
        if ($isCobrand) {
            $this->_helper->viewRenderer->setRender('advertiser-cobrand');
        }
    }

    /**
     * Register an advertiser: step two - complete full registration form
     */
    public function advertiserCompleteAction()
    {
        $refAppId    = (int) $this->_getParam('refAppId');
        $shortRegId  = (int) $this->_getParam('shortRegId');
        $shortRegTbl = App_Model_Tables::getShortRegistrationTable();

        // Simple security check to prevent bypass of the short reg form
        if (! $shortRegTbl->check($shortRegId)) {
            $this->redirect('registration/advertiser');
        }

        $userModel  = $this->getModel()->getUsersModel();
        $request    = $this->getRequest();
        $form       = $userModel->getAdvPubRegFormFull(
                                UsersTable::ADVERTISER_ROLE,
                                    $refAppId,
                                    $shortRegId
        );

        if ($request->isPost()) {
            $formData = $request->getPost();
            if ($userModel->registration(
                          $request,
                              $formData,
                              UsersTable::ADVERTISER_ROLE,
                              $refAppId,
                              $shortRegId
            )
            ) {
                if ($this->getHelper('adblade')->isCobrand()) {
                    $this->redirect('/wizard');
                } else {
                    $this->redirect('/ads/create?reg=1');
                }
            }
        }

        $this->view->assign('form', $form);
        $this->view->headScript()->appendFile('/js/jquery.uniform.min.js');
        $this->view->headLink()->appendStylesheet('/css/uniform.white-theme.css');
    }

    public function forgotPasswordAction()
    {
        $userModel = $this->getModel()->getUsersModel();
        $request   = $this->getRequest();

        if ($request->isPost()) {
            if ($userModel->sendForgotPassword($request->getPost())) {
                $this->view->assign('errorMessage', 'Please check your email for your login information');
            }
        }

        $this->view->assign('form', $userModel->getForgotPasswordForm());

        //Set custom layout for cobrands
        if ($this->_helper->adblade->isCobrand()) {
            $this->getHelper('layout')->getLayoutInstance()->setLayout('cobrand/cobrand-header-footer-only');
        }
    }

    public function advertiserLpNativeadsAction()
    {
        if ($this->_getParam('subdomain') == 'nativeads-branded') {
            $this->view->assign('isBrandedAds', true);
        }

        $params = array('subdomain' => 'nativeads');
        $this->forward('advertiser-reg-form-page-1', null, null, $params);
    }

    /**
     * Serves various landing pages for advetisers
     *
     * Short registration form (form 1 of 2)
     * See originated task #4249
     */
    public function advertiserRegFormPage1Action()
    {
        $request   = $this->getRequest();
        $subDomain = $request->getParam('subdomain', '');
        $subDomain = strtolower($subDomain);

        // Check corresponding view script exists, otherwise use default one
        $viewScript = 'index';
        foreach ($this->view->getScriptPaths() as $dir) {
            $fileName = $dir . "registration/{$subDomain}.phtml";
            if (Zend_Loader::isReadable($fileName)) {
                $viewScript = $subDomain;
                break;
            }
        }

        $userModel = $this->getModel()->getUsersModel();

        if ($request->isPost()) {

            $post  = $request->getPost();

            $newId = $userModel->saveAdvPubRegFormPart1(
                               $post, false, $subDomain,
                                   $this->_getParam(App_Model_DbTable_UserRegReferral::REG_REFERRER_PARAM_NAME), true
            );

            if ($newId) {
                $url = '/registration/advertiser-reg-form-page-2?shortreg_id=' . $newId;
                $this->redirect($url, array('exit' => true));
            }
        }

        $layout = $this->_getLandingPageLayoutScript($subDomain);
        $this->_helper->layout->getLayoutInstance()->setLayout($layout);
        $this->_helper->viewRenderer->setScriptAction($viewScript);

        $this->view->assign('form', $userModel->getAdvPubRegFormPart1($subDomain, true));
    }

    /**
     * Since #6521 different landing pages can have different page layout
     *
     * @param  string $pageName
     * @return string
     */
    protected function _getLandingPageLayoutScript($pageName)
    {
        $pageName = (string) $pageName;

        switch ($pageName) {
            case 'nativeads':
            case 'nativeads-branded':
                $scriptName = 'multi-page-reg-type2';
                break;
            default:
                $scriptName = 'multi-page-reg';
                break;
        }

        return $scriptName;
    }

    /**
     *
     * short registration form (form 2 of 2)
     * see task #4249
     */
    public function advertiserRegFormPage2Action()
    {
        $isCobrand  = $this->_helper->adblade->isCobrand();
        $userModel  = $this->getModel()->getUsersModel();
        $shortRegId = $this->getRequest()->getParam('shortreg_id');
        $post       = $this->getRequest()->getPost();

        if (isset($post['formname']) && $post['formname'] == 'advlongForm') {

            $shortRegId = $post['shortRegId'];
            if ($userModel->saveAdvRegFormPart2($post, $shortRegId, $isCobrand)) {
                $this->redirect('/ads/create?reg=1');
            }
        }

        $this->getHelper('layout')->getLayoutInstance()->setLayout('multi-page-reg');
        $this->view->assign('form', $userModel->getAdvRegFormPart2($shortRegId));
        $this->view->assign('showFacebookConvPixel', true);
    }

    /**
     * Don't know what this page is referred by(seems like no subdomain attached)
     * so leaving the method as is so far...
     *
     * short registration (variant b of page 1)
     * task #4445
     */
    public function advertiserRegFormPage1bAction()
    {
        $this->forward('advertiser-reg-form-page-1', null, null,
            array('subdomain' => 'index2'));
    }

    /**
     *
     * registration via call. no submit.
     * task #4813
     */
    public function advertiserSimpleAction()
    {
        $this->getHelper('layout')->getLayoutInstance()->setLayout('multi-page-reg');
    }

    public function ajaxGetCountryStatesAction()
    {
        $states = array();

        if ($this->getRequest()->isXmlHttpRequest()) {
            $country = $this->_getParam('c');
            if ($country == 'ca') {
                $states = App_Model_Models::getStatesModel()->getCanadianProvincesAndTerritories();
            } else {
                $states = App_Model_Models::getStatesModel()->getStates();
            }
        }

        $this->_helper->json(array('status' => true, 'data' => $states));
    }

    public function optiserveAction()
    {
        if ($this->_helper->adblade->isCobrand()) {
            $this->_helper->redirector->gotoUrl('/registration/advertiser');
        }

        $userModel = $this->getModel()->getUsersModel();
        $form = $userModel->getAdvPubRegFormPart1();

        if ($this->getRequest()->isPost()) {
            $postData  = $this->getRequest()->getPost();

            $shortRegId = (int) $userModel->saveAdvPubRegFormPart1($postData, true);
            if ($shortRegId > 0) {
                $this->_helper->flashMessenger->addMessage("Thank you");
                $this->_helper->redirector->gotoSimple('publisher-solutions-self-serve-ad-platform', 'doc');
            }
        }
        $this->view->assign('form', $form);
    }

}