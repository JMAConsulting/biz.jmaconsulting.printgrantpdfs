<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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
    $config = CRM_Core_Config::singleton();
    $fileArray = array();
    define('DOMPDF_ENABLE_REMOTE', TRUE);
    define('DOMPDF_ENABLE_AUTOLOAD', FALSE);
    $pdfTemplate = $this->getPDFMessageTemplate();
    foreach ($this->_grantIds as $gid) {
      $values = array();
      $params['id'] = $gid;
      CRM_Grant_BAO_Grant::retrieve($params, $values);
      $values['attachment'] = CRM_Core_BAO_File::getEntityFile('civicrm_grant', $gid);
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
      
      $custom = CRM_Core_BAO_CustomValueTable::getEntityValues($gid, 'Grant');
      if (!empty($custom)) {
        $fileDAO = new CRM_Core_BAO_File();
        foreach ($custom as $keys => $cValue) {
          $vals = $fileArray = array();
          $cfParams = array('id' => $keys);
          CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_CustomField', $cfParams, $vals);
          $vals['data'] = $vals['value'] = $cValue;
          $values['custom'][$keys]['label'] = $vals['label'];
          
          if (!in_array($vals['html_type'], array('RichTextEditor', 'File'))) {
            $values['custom'][$keys]['value'] = CRM_Core_BAO_CustomGroup::formatCustomValues($vals, $vals);
          }
          elseif ($vals['html_type'] == "RichTextEditor") {
            $values['custom'][$keys]['value'] = strip_tags($vals['value']);
          } 
          elseif ( $vals['html_type'] == "File" ) {
            $fileDAO->id = $vals['value'];
            if( $fileDAO->find(true) ) {
              $source = CRM_Utils_System::url("civicrm/file", "reset=1&eid=$gid&id=$fileDAO->id", TRUE, NULL, FALSE);
              switch( $fileDAO->mime_type ) {
              case "text/plain":
                $raw = file($source);
                $data = implode('<br>', $raw);
                $values['custom'][$keys]['value'] = $data;
                break;
              case "image/jpeg":
              case "image/png":
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
                if (!file_exists($config->customFileUploadDir.$fileDAO->uri)) {
                  break;
                }
                $originFilePath = $config->customFileUploadDir.$fileDAO->uri;
                $outputDirPath  = $config->customFileUploadDir;
                CRM_Unoconv_Unoconv::convertToPdf($originFilePath, $outputDirPath);
                $fileArray[] = $outputDirPath . str_replace('.doc', '.pdf', $fileDAO->uri);  
                break;
              case "application/vnd.ms-excel":
                if (!file_exists($config->customFileUploadDir.$fileDAO->uri)) {
                  break;
                }
                $fileArray[] = self::convertXLStoPDF($fileDAO, 'custom');
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
            $source = CRM_Utils_System::url("civicrm/file", "reset=1&eid=".$gid."&id=".$attachValue['fileID']."", TRUE, NULL, FALSE);
            $values['attach'][$keys]['label'] = 'Attachment';
            $values['attach'][$keys]['value'] = "<img src='".$source."' />";
          break;
          case "text/plain":
            $raw = file($attachValue['fullPath']);
            $data = implode('<br>', $raw);
            $values['attach'][$keys]['label'] = 'Attachment';
            $values['attach'][$keys]['value'] = $data;
            break;
          case "application/rtf":
            $raw = file($attachValue['fullPath']);
            foreach ( $raw as $plain ) {
              $text[] = strip_tags($plain);
            }
            $data = implode('<br>', $text);
            $values['attach'][$keys]['label'] = 'Attachment';
            $values['attach'][$keys]['value'] = $data;
            break;
          case "application/msword":
            if (!file_exists($attachValue['fullPath'])) {
              break;
            }
            $originFilePath = $attachValue['fullPath'];
            $outputDirPath  = $config->customFileUploadDir;
            CRM_Unoconv_Unoconv::convertToPdf($originFilePath, $outputDirPath);
            $fileArray[] = $outputDirPath . str_replace('.doc', '.pdf', $attachValue['fileName']);
            break;
          case "application/vnd.ms-excel":
            if (!file_exists($attachValue['fullPath'])) {
              break;
            }
            $fileArray[] = self::convertXLStoPDF($attachValue, 'attachment');
          default:
            break;
          }
        }
      } 
      unset($values['attachment']);
      CRM_Core_Smarty::singleton()->assign('values', $values);
      // Generate PDF
      $out = CRM_Core_Smarty::singleton()->fetch("string:{$pdfTemplate}");
      $files[] = self::generatePDF($values, $out, $fileArray);
    }
    $zip = $config->customFileUploadDir . '/Grants_' . date('YmdHis') . '.zip';
    $export = new CRM_Financial_BAO_ExportFormat();
    $export->createZip($files, $zip, TRUE);
    // Initiate Download
    if (file_exists($zip)) {
      header('Content-Type: application/zip');
      header('Content-Disposition: attachment; filename=' . CRM_Utils_File::cleanFileName(basename($zip)));
      header('Content-Length: ' . filesize($zip));
      ob_clean();
      flush();
      readfile($config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($zip)));
      unlink($zip); //delete the zip to avoid clutter.
      CRM_Utils_System::civiExit();
    }
  } 
  /**
   * @return string
   */
  function getPDFMessageTemplate() {
    $query = 'SELECT msg_html html
      FROM civicrm_msg_template mt
      JOIN civicrm_option_value ov ON workflow_id = ov.id
      JOIN civicrm_option_group og ON ov.option_group_id = og.id
      WHERE og.name = %1 AND ov.name = %2 AND mt.is_default = 1';
    $sqlParams = array(1 => array('msg_tpl_workflow_grant', 'String'), 2 => array('grant_print_pdf', 'String'));
    return CRM_Core_DAO::singleValueQuery($query, $sqlParams);
  }

  function generatePDF($values, $html, $fileArray) {
    global $civicrm_root;
    if (!file_exists($civicrm_root . '/packages/dompdf/dompdf_config.inc.php')) {
      require_once 'vendor/dompdf/dompdf/dompdf_config.inc.php';
    }
    else {
      require_once("packages/dompdf/dompdf_config.inc.php");
      spl_autoload_register('DOMPDF_autoload');      
    }
    $fileName = 'Grant_'.$values['contact_id'].'_'.$values['grant_id'].'.pdf';
    $config = CRM_Core_Config::singleton();
    $filePath = $config->customFileUploadDir . $fileName;
    
    $dompdf = new DOMPDF();
    
    $dompdf->load_html($html);
    $dompdf->render();
    
    file_put_contents($filePath, $dompdf->output());
    
    // Merge attachments and files attached to custom fields
    if (!empty($fileArray)) {
      array_unshift($fileArray, $filePath);
      $name = 'Grants_'.$values['contact_id'].'_'.$values['grant_id'].'.pdf';
      $pdf = new CRM_PDFMerger_PDFMerger;
      $pdfs = '$pdf';
      foreach($fileArray as $file) {
        $pdfs .= '->addPDF("'.$file.'", "all")';
      }
      $pdfs .= '->merge("file", "'.$config->customFileUploadDir.$name.'");';
      eval($pdfs);
      $filePath = $config->customFileUploadDir . $name;
    }
    return $filePath;
  }

  function convertXLStoPDF($xls, $context) {
    global $civicrm_root;
    $config = CRM_Core_Config::singleton();
    require_once ('packages/PHPExcel.php');
    if ($context == 'custom') {
      $fileName = $xls->uri;
      $filePath = $config->customFileUploadDir.$xls->uri;
    }
    else {
      $fileName = $xls['fileName'];
      $filePath = $xls['fullPath'];
    }

    $rendererName = PHPExcel_Settings::PDF_RENDERER_DOMPDF;
    if (!file_exists($civicrm_root . '/packages/dompdf/dompdf_config.inc.php')) {
      $rendererLibraryPath = $civicrm_root . '/vendor/dompdf/dompdf';
    }
    else {
      $rendererLibraryPath = $civicrm_root . '/packages/dompdf';      
    }
    if (!PHPExcel_Settings::setPdfRenderer($rendererName,$rendererLibraryPath)) {
      CRM_Core_Error::fatal(ts('NOTICE: Please set the $rendererName and $rendererLibraryPath values' .
                               '<br />' .
                               'at the top of this script as appropriate for your directory structure'));
    }
    $outputDirPath  = $config->customFileUploadDir;
    $objPHPexcel = PHPExcel_IOFactory::load($filePath);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPexcel, 'Excel5'); 
    $objPHPexcel->addCellXf(new PHPExcel_Style);
    $objPHPexcel->addCellStyleXf(new PHPExcel_Style);
    $objPHPexcel->getDefaultStyle()->getFont()
      ->setName('Arila')
      ->setSize(10);
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPexcel, 'PDF');
    $objWriter->setPreCalculateFormulas(false);
    $objWriter->save($outputDirPath . str_replace('.xls', '.pdf', $fileName));
    return $outputDirPath . str_replace('.xls', '.pdf', $fileName);
  }
}
