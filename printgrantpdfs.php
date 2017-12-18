<?php

require_once 'printgrantpdfs.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function printgrantpdfs_civicrm_config(&$config) {
  _printgrantpdfs_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function printgrantpdfs_civicrm_xmlMenu(&$files) {
  _printgrantpdfs_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function printgrantpdfs_civicrm_install() {
  _printgrantpdfs_civix_civicrm_install();

  $smarty = CRM_Core_Smarty::singleton();
  $smarty->assign('currentDirectoryPath', __DIR__);
  if (!method_exists(new CRM_Utils_File(), 'runSqlQuery')) {
    CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $smarty->fetch(__DIR__ . '/sql/civicrm_msg_template.tpl'), NULL, TRUE);
  }
  else {
    CRM_Utils_File::runSqlQuery(CIVICRM_DSN, $smarty->fetch(__DIR__ . '/sql/civicrm_msg_template.tpl'));}
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function printgrantpdfs_civicrm_uninstall() {
  printgrantpdfs_enableDisableMessageTemplate(2);
  _printgrantpdfs_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function printgrantpdfs_civicrm_enable() {
  printgrantpdfs_enableDisableMessageTemplate(1);
  _printgrantpdfs_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function printgrantpdfs_civicrm_disable() {
  printgrantpdfs_enableDisableMessageTemplate(0);
  _printgrantpdfs_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function printgrantpdfs_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _printgrantpdfs_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function printgrantpdfs_civicrm_managed(&$entities) {
  _printgrantpdfs_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function printgrantpdfs_civicrm_caseTypes(&$caseTypes) {
  _printgrantpdfs_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function printgrantpdfs_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _printgrantpdfs_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function printgrantpdfs_civicrm_searchTasks($objectType, &$tasks) {
  if ($objectType == 'grant') {
    $tasks[CRM_Grant_Task::PRINT_GRANTS] = array(
      'title' => ts('Print Grants as PDF'),
      'class' => 'CRM_Grant_Form_Task_PrintPDF',
      'result' => FALSE,
    );
  }
}

/**
 * function to disable/enable/delete message template
 *
 * @param int $action
 *
 */

function printgrantpdfs_enableDisableMessageTemplate($action) {
  if ($action < 2) {
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_option_value
       INNER JOIN civicrm_option_group ON  civicrm_option_value.option_group_id = civicrm_option_group.id
       INNER JOIN civicrm_msg_template ON civicrm_msg_template.workflow_id = civicrm_option_value.id
         SET civicrm_option_value.is_active = %1,
           civicrm_option_group.is_active = %1,
           civicrm_msg_template.is_active = %1
       WHERE civicrm_option_group.name LIKE 'msg_tpl_workflow_grant' AND civicrm_option_value.name = 'grant_print_pdf'",
      array(
        1 => array($action, 'Integer'),
      )
    );
  }
  else {
    CRM_Core_DAO::executeQuery(
      "DELETE  civicrm_option_value.*, civicrm_option_group.*, civicrm_msg_template.*
FROM civicrm_option_value
INNER JOIN civicrm_option_group ON  civicrm_option_value.option_group_id = civicrm_option_group.id
INNER JOIN civicrm_msg_template ON civicrm_msg_template.workflow_id = civicrm_option_value.id
WHERE civicrm_option_group.name LIKE 'msg_tpl_workflow_grant' AND civicrm_option_value.name = 'grant_print_pdf'"
    );
  }
}
