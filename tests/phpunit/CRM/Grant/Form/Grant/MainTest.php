<?php

require_once __DIR__ . '/../GrantPageTest.php';
use CRM_Grantapplications_ExtensionUtil as E;
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
class CRM_Grant_Form_Grant_MainTest extends CRM_Grant_Form_GrantPageTest {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function testGrantApplicationPage() {
    $indvidualParams = [
      'contact_type' => 'Individual',
      'first_name' => 'Adam' . substr(sha1(rand()), 0, 7),
      'last_name' => 'Cooper' . substr(sha1(rand()), 0, 7),
      'email-Primary' => 'test3@test.com',

    ];
    $contactID = $this->createDummyContact($indvidualParams);
    $grantPageID = $this->testGrantPage();
    $values = $this->callAPISuccessGetSingle('GrantApplicationPage', ['id' => $grantPageID]);
    $form = new CRM_Grant_Form_Grant_Confirm();
    $form->setVar('_values', [
      'is_for_organization' => 1,
      'for_organization' => 'On behalf of',
      'custom_pre_id' => $this->_getGrantProfileID($grantPageID),
      'id' => $grantPageID,
      'title' => $values['title'],
      'grant_type_id' => $values['grant_type_id'],
      'default_amount' => $values['default_amount'],
      'is_draft' => $values['is_draft'],
      'draft_title' => $values['draft_title'],
      'draft_text' => $values['draft_text'],
      'thankyou_title', $values['thankyou_title'],
      'is_email_receipt' => 0,
      'is_active' => $values['is_active'],
      'start_date' => $values['start_date'],
      'grant_app_start_date' => $values['start_date'],
      'created_id' => $values['created_id'],
      'created_date' => $values['created_date'],
      'onbehalf_profile_id' =>  $this->_getGrantOnBehalfProfile($grantPageID),
    ]);
    $grantParams = array_merge($indvidualParams, [
      'description' => 'Online Grant Application: ' . $values['title'],
      'currencyID' => USD,
      'onbehalfof_id' => 203,
      'org_option' => NULL,
      'is_for_organization' => TRUE,
      'onBehalf' => [
        'organization_name' => 'Test org',
        'email-Primary' => 'test@test.com',
      ],
      'default_amount_hidden' => $values['default_amount'],
      'amount' => $values['default_amount'],
      'organization_id' => 203,
      'organization_name' => 'Test org',
    ])
    $form->setVar('_params', $grantParams);
    $fieldTypes = [];
    $grant = CRM_Grant_BAO_Grant_Utils::processConfirm($form,
      $grantParams,
      $contactID,
      $values['grant_type_id'],
      'grant',
      $fieldTypes
    );
  }

}
