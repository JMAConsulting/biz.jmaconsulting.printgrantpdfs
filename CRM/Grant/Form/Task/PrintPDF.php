<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * This class handle grant related functions
 *
 */
class CRM_Grant_Form_Task_PrintPDF extends CRM_Grant_Form_Task {
  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preprocess();

    // set print view, so that print templates are called
    $this->controller->setPrint(1);

    // get the formatted params
    $queryParams = $this->get('queryParams');

    $sortID = NULL;
    if ($this->get(CRM_Utils_Sort::SORT_ID)) {
      $sortID = CRM_Utils_Sort::sortIDValue($this->get(CRM_Utils_Sort::SORT_ID),
        $this->get(CRM_Utils_Sort::SORT_DIRECTION)
      );
    }

    $selector = new CRM_Grant_Selector_Search($queryParams, $this->_action, $this->_componentClause);
    $controller = new CRM_Core_Selector_Controller($selector, NULL, $sortID, CRM_Core_Action::VIEW, $this, CRM_Core_Selector_Controller::SCREEN);
    $controller->setEmbedded(TRUE);
    $controller->run();
  }

   /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    // Process grants and assign to TPL 
    $grantIds = $this->getVar('_grantIds');
    $config = CRM_Core_Config::singleton();
    global $base_url;
    foreach ($grantIds as $gid) {
      $values = array();
      $params['id'] = $gid;
      CRM_Grant_BAO_Grant::retrieve($params, $values);
      $values['attachment'] = CRM_Core_BAO_File::getEntityFile('civicrm_grant', $gid);
      $custom = CRM_Core_BAO_CustomValueTable::getEntityValues($gid, 'Grant');
      $ids = array_keys($custom);
      $count = 0;
      foreach( $ids as $key => $val ) {
        $customData[$count]['label'] = CRM_Core_DAO::getFieldValue("CRM_Core_DAO_CustomField", $val, "label", "id");
        $customData[$count]['html_type'] = CRM_Core_DAO::getFieldValue("CRM_Core_DAO_CustomField", $val, "html_type", "id");
        $customData[$count]['data_type'] = CRM_Core_DAO::getFieldValue("CRM_Core_DAO_CustomField", $val, "data_type", "id");
        $cfParams = array('id' => $val);
        $cfDefaults = array();
        CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $cfParams, $cfDefaults);
        $columnName = $cfDefaults['column_name'];
        
        //table name of custom data
        $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',
                                                 $cfDefaults['custom_group_id'],
                                                 'table_name', 'id'
                                                 );
        
        //query to fetch id from civicrm_file
        $query = "SELECT {$columnName} FROM {$tableName} where entity_id = {$gid}";
        $fileID = CRM_Core_DAO::singleValueQuery($query);
        $customData[$count]['value'] = $fileID;
        $count++;
      }
      if (isset($gid)) {
        $noteDAO = new CRM_Core_BAO_Note();
        $noteDAO->entity_table = 'civicrm_grant';
        $noteDAO->entity_id = $gid;
        if ($noteDAO->find(TRUE)) {
          $values['noteId'] = $noteDAO->note;
        }
      }
      $values['display_name'] = CRM_Contact_BAO_Contact::displayName($values['contact_id']);
      $values['application_received_date'] = isset($values['application_received_date']) ? date('jS F Y', strtotime($values['application_received_date'])) : "";
      $values['decision_date'] = isset($values['decision_date']) ? date('jS F Y', strtotime($values['decision_date'])) : "";
      $values['money_transfer_date'] = isset($values['money_transfer_date']) ? date('jS F Y', strtotime($values['money_transfer_date'])) : "";
      $values['grant_due_date'] = isset($values['grant_due_date']) ? date('jS F Y', strtotime($values['grant_due_date'])) : "";
      $values['amount_total'] = isset($values['amount_total']) ? $values['amount_total'] : '0.00';
      $values['amount_requested'] = isset($values['amount_requested']) ? $values['amount_requested'] : '0.00';
      $values['amount_granted'] = isset($values['amount_granted']) ? $values['amount_granted'] : '0.00';
      if (!empty($customData)) {
        $fileDAO = new CRM_Core_BAO_File();
        foreach( $customData as $keys => $vals ) {
          if (($vals['html_type'] == "Text" || 
               $vals['html_type'] == "Autocomplete-Select" || 
               $vals['html_type'] == "Radio"|| 
               $vals['html_type'] == "Select Date") && $vals['data_type'] != "ContactReference") {
            $values['custom'][$keys]['label'] = $vals['label'];
            $values['custom'][$keys]['value'] = $vals['value'];
          } 
          elseif (($vals['html_type'] == "AdvMulti-Select" || 
                   $vals['html_type'] == "Multi-Select" || 
                   $vals['html_type'] == "CheckBox" ) && !empty($vals['value'])) {
            $key = explode(CRM_Core_DAO::VALUE_SEPARATOR, $vals['value']);
            $key = array_filter($key);
            $key = implode(', ', $key);
            $values['custom'][$keys]['label'] = $vals['label'];
            $values['custom'][$keys]['value'] = $key;
          } 
          elseif ($vals['data_type'] == "ContactReference" && !empty($vals['value'])) {
            $values['custom'][$keys]['label'] = $vals['label'];
            $values['custom'][$keys]['value'] = CRM_Contact_BAO_Contact::displayName($vals['value']);
          } 
          elseif ($vals['html_type'] == "RichTextEditor") {
            $values['custom'][$keys]['label'] = $vals['label'];
            $values['custom'][$keys]['value'] = strip_tags($vals['value']);
          } 
          elseif ( $vals['html_type'] == "File" ) {
            $fileDAO->id = $vals['value'];
            if( $fileDAO->find(true) ) {
              $source = $base_url.'/sites/default/files/civicrm/custom/'.$fileDAO->uri; // FIXME: do not use hardcoded paths!
              $sourcePDF = $config->customFileUploadDir;
              switch( $fileDAO->mime_type ) {
              case "text/plain":
                $raw = file($source);
                $data = implode('<br>', $raw);
                // $html .="<tr><td><b>".$vals['label']."<b></td><td>".$data."</td></tr>";
                $values['custom'][$keys]['label'] = $vals['label'];
                $values['custom'][$keys]['value'] = $data;
                break;
              case "image/jpeg":
              case "image/png":
                // $html .="<tr><td><b>".$vals['label']."<b></td><td><img src='".$source."' /></td></tr>";
                $values['custom'][$keys]['label'] = $vals['label'];
                $values['custom'][$keys]['value'] = "<img src='".$source."' />";
              break;
              case "application/rtf":
                $raw = file($source);
                foreach ( $raw as $plain ) {
                  $text[] = strip_tags($plain);
                }
                $data = implode('<br>', $text);
                $html .="<tr><td><b>Attachment<b></td><td>".$data."</td></tr>";
                $values['custom'][$keys]['label'] = 'Attachment';
                $values['custom'][$keys]['value'] = $data;
                break;
              case "application/msword":
                shell_exec('/usr/bin/unoconvtest.sh');
                $command = 'unoconv -f pdf '.$sourcePDF.$fileDAO->uri;
                exec($command);
                $pdfPath = array_filter(explode('/', $attachValue['fullPath']));
                $lastItem = array_pop($pdfPath);
                $newItem = str_replace('.doc', '.pdf', $lastItem);
                array_push($pdfPath, $newItem);
                $pdfPathNew = implode('/', $pdfPath);
                $pdfPathNew = '/'.$pdfPathNew;
                $fileArray[] = $pdfPathNew;
              default:
                break;
              }
            }
          }
        }
      } 
      if (!empty($values['attachment'])) {
        foreach( $values['attachment'] as $attachKey => $attachValue ) {
          switch( $attachValue['mime_type'] ) {
          case "image/jpeg":
          case "image/png":
            //$html .="<tr><td><b>Attachment<b></td><td><img src=".$base_url."/sites/default/files/civicrm/custom/".$attachValue['fileName']." /></td></tr>";
            $values['custom'][$keys]['label'] = 'Attachment';
            $values['custom'][$keys]['value'] = "<img src=".$base_url."/sites/default/files/civicrm/custom/".$attachValue['fileName']." />"; // FIXME: dod not use hardcoded paths!
          break;
          case "text/plain":
            $raw = file($attachValue['fullPath']);
            $data = implode('<br>', $raw);
            //$html .="<tr><td><b>Attachment<b></td><td>".$data."</td></tr>";
            $values['custom'][$keys]['label'] = 'Attachment';
            $values['custom'][$keys]['value'] = $data;
            break;
          case "application/rtf":
            $raw = file($attachValue['fullPath']);
            foreach ( $raw as $plain ) {
              $text[] = strip_tags($plain);
            }
            $data = implode('<br>', $text);
            //$html .="<tr><td><b>Attachment<b></td><td>".$data."</td></tr>";
            $values['custom'][$keys]['label'] = 'Attachment';
            $values['custom'][$keys]['value'] = $data;
            break;
          case "application/msword":
            shell_exec('/usr/bin/unoconvtest.sh');
            $command = 'unoconv -f pdf '.$attachValue['fullPath'];
            
            shell_exec($command);
            //Pulling the file from the directory
            $pdfPath = array_filter(explode('/', $attachValue['fullPath']));
            $lastItem = array_pop($pdfPath);
            $newItem = str_replace('.doc', '.pdf', $lastItem);
            array_push($pdfPath, $newItem);
            $pdfPathNew = implode('/', $pdfPath);
            $pdfPathNew = '/'.$pdfPathNew;
            $fileArray[] = $pdfPathNew;
          default:
            break;
          }
        }
      } 
      $tplFile = $this->getHookedTemplateFileName();
      CRM_Core_Smarty::singleton()->assign('values', $values);
      $out = CRM_Core_Smarty::singleton()->fetch($tplFile);
      CRM_Core_Error::debug( '$out', $out );
      exit;
      // Generate PDF
      self::generatePDF($values);
    }
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Print Grant List as PDFs'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'back',
          'name' => ts('Back'),
        ),
      )
    );
  } 
  /**
   * @return string
   */
  function getHookedTemplateFileName() {
    return 'PrintGrantPDF/GrantPDF.tpl';
  }

  function postProcess() {
   
  }

  function generatePDF($values) {
    global $base_url;
    require_once("packages/dompdf/dompdf_config.inc.php");
    spl_autoload_register('DOMPDF_autoload');
    $fileName = 'Grant_'.$values['contact_id'].'_'.$values['grant_id'].'.pdf';
    $config = CRM_Core_Config::singleton();
    $filePath = $config->customFileUploadDir . $fileName;
    $fileArray[] = $filePath;
    
    $dompdf = new DOMPDF();
    
    $dompdf->load_html($html);
    $dompdf->render();
    
    file_put_contents($filePath, $dompdf->output());
    
    //$fileArray= array($filePath, $pdfPathNew);
    $datadir = $config->customFileUploadDir;
    $outputName = $datadir.'Grants_'.$values['grant_id'].'_'.$values['contact_id'].'.pdf';
    $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$outputName ";
    foreach($fileArray as $file) {
      $cmd .= $file." ";
    }
    
    $result = shell_exec($cmd);
    // Initiate Download
    if (file_exists($outputName)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename='.basename($outputName));
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      header('Content-Length: ' . filesize($outputName));
      ob_clean();
      flush();
      readfile($outputName);
      CRM_Utils_System::civiExit();
    }
  }
}
