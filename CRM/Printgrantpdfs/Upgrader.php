<?php

/**
 * Collection of upgrade steps
 */
class CRM_Printgrantpdfs_Upgrader extends CRM_Printgrantpdfs_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   * @throws Exception
   *
   */
  public function upgrade_4500() {
    $this->ctx->log->info('Applying update 1.1');
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('currentDirectoryPath', __DIR__ . '/../../');
    CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $smarty->fetch(__DIR__ . '/../../sql/civicrm_msg_template.tpl'), NULL, TRUE);
    return TRUE;
  }

}
