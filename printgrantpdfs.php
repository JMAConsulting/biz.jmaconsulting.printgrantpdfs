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
    CRM_Utils_File::runSqlQuery(CIVICRM_DSN, $smarty->fetch(__DIR__ . '/sql/civicrm_msg_template.tpl'));
  }
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function printgrantpdfs_civicrm_uninstall() {
  printgrantpdfs_enableDisableMessageTemplate(2);
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
}

function printgrantpdfs_civicrm_searchTasks($objectType, &$tasks) {
  if ($objectType == 'grant') {
    $tasks[CRM_Grant_Task::TASK_PRINT] = array(
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
