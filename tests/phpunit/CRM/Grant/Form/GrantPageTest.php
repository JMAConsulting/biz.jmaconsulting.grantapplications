<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Grant_Form_GrantPageTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
      // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
      // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
      return \Civi\Test::headless()
        ->installMe(__DIR__)
        ->apply();
    }

    public function setUp() {
      parent::setUp();
      $this->createContact();
    }

    public function tearDown() {
      parent::tearDown();
    }

    /**
     * wrap api functions.
     * so we can ensure they succeed & throw exceptions without litterering the test with checks
     *
     * @param string $entity
     * @param string $action
     * @param array $params
     * @param mixed $checkAgainst
     *   Optional value to check result against, implemented for getvalue,.
     *   getcount, getsingle. Note that for getvalue the type is checked rather than the value
     *   for getsingle the array is compared against an array passed in - the id is not compared (for
     *   better or worse )
     *
     * @return array|int
     */
    public function callAPISuccess($entity, $action, $params, $checkAgainst = NULL) {
      $params = array_merge(array(
          'debug' => 1,
        ),
        $params
      );
      switch (strtolower($action)) {
        case 'getvalue':
          return $this->callAPISuccessGetValue($entity, $params, $checkAgainst);

        case 'getsingle':
          return $this->callAPISuccessGetSingle($entity, $params, $checkAgainst);

        case 'getcount':
          return $this->callAPISuccessGetCount($entity, $params, $checkAgainst);
      }
      $result = civicrm_api3($entity, $action, $params);
      return $result;
    }

    public function callAPISuccessGetValue($entity, $params, $type = NULL) {
      $params += array(
        'debug' => 1,
      );
      $result = civicrm_api3($entity, 'getvalue', $params);
      if ($type) {
        if ($type == 'integer') {
          // api seems to return integers as strings
          $this->assertTrue(is_numeric($result), "expected a numeric value but got " . print_r($result, 1));
        }
        else {
          $this->assertType($type, $result, "returned result should have been of type $type but was ");
        }
      }
      return $result;
    }

    public function callAPISuccessGetSingle($entity, $params, $checkAgainst = NULL) {
      $params += array(
        'debug' => 1,
      );
      $result = civicrm_api3($entity, 'getsingle', $params);
      if (!is_array($result) || !empty($result['is_error']) || isset($result['values'])) {
        throw new Exception('Invalid getsingle result' . print_r($result, TRUE));
      }
      if ($checkAgainst) {
        // @todo - have gone with the fn that unsets id? should we check id?
        $this->checkArrayEquals($result, $checkAgainst);
      }
      return $result;
    }

    public function callAPISuccessGetCount($entity, $params, $count = NULL) {
      $params += array(
        'debug' => 1,
      );
      $result = $this->civicrm_api3($entity, 'getcount', $params);
      if (!is_int($result) || !empty($result['is_error']) || isset($result['values'])) {
        throw new Exception('Invalid getcount result : ' . print_r($result, TRUE) . " type :" . gettype($result));
      }
      if (is_int($count)) {
        $this->assertEquals($count, $result, "incorrect count returned from $entity getcount");
      }
      return $result;
    }

    /**
     * Create contact.
     */
    public function createContact() {
      if (!empty($this->_contactID)) {
        return;
      }
      $results = $this->callAPISuccess('Contact', 'create', array(
        'contact_type' => 'Individual',
        'first_name' => 'Jose',
        'last_name' => 'Lopez',
      ));
      $this->_contactID = $results['id'];
    }

    /**
     * Create dummy contact.
     */
    public function createDummyContact($params = []) {
      $results = $this->callAPISuccess('Contact', 'create', array_merge([
        'contact_type' => 'Individual',
        'first_name' => 'Adam' . substr(sha1(rand()), 0, 7),
        'last_name' => 'Cooper' . substr(sha1(rand()), 0, 7),
      ], $params
      ));

      return $results['id'];
    }

    /**
     * Test grant setting page.
     *
     * @return int $id GrantPage ID
     */
    public function _grantPageSetting() {
      $onBehalfProfileID = 9;
      $form = new CRM_Grant_Form_GrantPage_Settings();
      $params = [
        'title' => 'Test Grant page',
        'grant_type_id' => 1,
        'intro_text' => 'welcome to grant page',
        'is_organization' => 1,
        'default_amount' => 1000,
        'is_active' => 1,
        'onbehalf_profile_id' => $onBehalfProfileID,
        'start_date' => '08/06/2020',
        'start_date_time' => '08:27AM',
        'end_date' => '',
        'end_date_time' => '',
        'for_organization' => '',
      ];

      $files = [];
      $fv = array_merge($params, ['title' => 'Wrong title that got /', 'start_date' => '08/06/2020', 'end_date' => '07/06/2020']);
      $errors = $form->formRule($fv, $files, $form);
      $expectedErrors = [
        'title' => "Please do not use '/' in Title",
        'end_date' => 'End date should be after Start date.',
      ];
      $this->assertEquals($expectedErrors, $errors);

      $id = $form->submit($params, TRUE);
      $this->assertTrue(!empty($id));

      $this->_getGrantOnBehalfProfile($id, $onBehalfProfileID);

      return $id;
    }

    public function _getGrantOnBehalfProfile($grantPageID) {
      $dao = new CRM_Core_DAO_UFJoin();
      $dao->entity_table = 'civicrm_grant_app_page';
      $dao->module = 'on_behalf';
      $dao->entity_id = $grantPageID;
      $dao->find(TRUE);
      $this->assertEquals($dao->N, 1);
      $expectedSettings = [
        'module' => 'on_behalf',
        'entity_table' => 'civicrm_grant_app_page',
        'entity_id' => $grantPageID,
        'uf_group_id' => $onBehalfProfileID ?: $dao->uf_group_id,
        'module_data' => '{"on_behalf":{"is_for_organization":false,"default":{"for_organization":""}}}',
      ];
      foreach ($expectedSettings as $attr => $value) {
        $this->assertEquals($dao->$attr, $value);
      }
    }

    /**
     * Test grant Draft page.
     *
     * @param int $id GrantPage ID
     */
    public function _grantPageDraft($id) {
      $form = new CRM_Grant_Form_GrantPage_Draft();
      $form->setvar('_id', $id);

      $params = [
        'is_draft' => TRUE,
        'draft_title' => 'Test Draft title',
        'draft_text' => 'Test Draft text',
        'draft_footer' => 'Test Draft footer',
      ];

      $fv = $params;
      unset($fv['draft_title']);
      $errors = $form->formRule($fv);
      $expectedErrors = [
        'draft_title' => "Draft Title is a required field",
      ];
      $this->assertEquals($expectedErrors, $errors);

      $form->submit($params);
    }

    /**
     * Test grant profile page.
     *
     * @param int $id GrantPage ID
     */
    public function _grantPageProfile($id) {
      $profileID = 1;
      $params = ['custom_pre_id' => $profileID];
      $form = new CRM_Grant_Form_GrantPage_Custom();
      $form->setvar('_id', $id);
      $form->submit($params);

      // TODO : we cannot use UFJoin.get api yet as its not supporting civicrm_grant_app_page entity_table in core and there is ni hook to enxtend
      // the valid list of entity_tables. For more details check CRM_Core_BAO_UFJoin::entityTables()
      $this->_getGrantProfileID($id, $profileID);
    }

    public function _getGrantProfileID($grantPageID, $profileID = NULL) {
      $dao = new CRM_Core_DAO_UFJoin();
      $dao->entity_table = 'civicrm_grant_app_page';
      $dao->module = 'CiviGrant';
      $dao->entity_id = $grantPageID;
      $dao->find(TRUE);
      $this->assertEquals($dao->N, 1);

      $expectedSettings = [
        'module' => 'CiviGrant',
        'entity_table' => 'civicrm_grant_app_page',
        'entity_id' => $id,
        'uf_group_id' => $profileID ?: $dao->uf_group_id,
      ];
      foreach ($expectedSettings as $attr => $value) {
        $this->assertEquals($dao->$attr, $value);
      }

      return $dao->uf_group_id;
    }

    /**
     * Test grant thankyou page.
     *
     * @param int $id GrantPage ID
     */
    public function _grantPageThankYou($id) {
      $form = new CRM_Grant_Form_GrantPage_ThankYou();
      $form->setvar('_id', $id);

      $params = [
        'is_email_receipt' => TRUE,
        'receipt_from_name' => 'Adam Jenkins',
        'receipt_from_email' => 'test@test.com',
        'receipt_text' => 'Test Receipt text',
      ];

      $fv = $params;
      $files = [];
      unset($fv['receipt_from_email']);
      $errors = $form->formRule($fv, $files, $form);
      $expectedErrors = [
        'receipt_from_email' => "A valid Receipt From Email address must be specified if Email Confirmation Receipt is enabled",
      ];
      $this->assertEquals($expectedErrors, $errors);

      $form->submit($params);
    }

    /**
     * Test all the setting pages of grant application
     */
    public function testGrantPage() {
      $grantPageID = $this->_grantPageSetting();
      $this->_grantPageDraft($grantPageID);
      $this->_grantPageProfile($grantPageID);
      $this->_grantPageThankYou($grantPageID);
      return $grantPageID;
    }

}
