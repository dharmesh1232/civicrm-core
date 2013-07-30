<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

class CiviReportTestCase extends CiviUnitTestCase {
  function setUp() {
    parent::setUp();
  }

  function tearDown() {
    parent::tearDown();
  }

  function getReportOutputAsCsv($reportClass, $inputParams) {
    $config = CRM_Core_Config::singleton();
    $config->keyDisable = TRUE;
    $controller = new CRM_Core_Controller_Simple($reportClass, ts('some title'));
    $reportObj =& $controller->_pages['Detail']; //FIXME - Detail is going to change

    $tmpGlobals = array();
    $tmpGlobals['_REQUEST']['force'] = 1;
    $tmpGlobals['_GET'][$config->userFrameworkURLVar] = 'civicrm/placeholder';
    $tmpGlobals['_SERVER']['QUERY_STRING'] = '';
    if (!empty($inputParams['fields'])) {
      $fields = implode(',', $inputParams['fields']);
      $tmpGlobals['_GET']['fld'] = $fields;
      $tmpGlobals['_GET']['ufld'] = 1;
    }
    if (!empty($inputParams['filters'])) {
      foreach ($inputParams['filters'] as $key => $val) {
        $tmpGlobals['_GET'][$key] = $val;
      }
    }
    CRM_Utils_GlobalStack::singleton()->push($tmpGlobals);

    try {
      $reportObj->storeResultSet();
      $reportObj->buildForm();
      $rows = $reportObj->getResultSet();

      $tmpFile = $this->createTempDir() . CRM_Utils_File::makeFileName('CiviReport.csv');
      $csvContent = CRM_Report_Utils_Report::makeCsv($reportObj, $rows);
      file_put_contents($tmpFile, $csvContent);
    } catch (Exception $e) {
      CRM_Utils_GlobalStack::singleton()->pop();
      throw $e;
    }
    CRM_Utils_GlobalStack::singleton()->pop();

    return $tmpFile;
  }

  function getArrayFromCsv($csvFile) {
    $arrFile = array();
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $arrFile[] = $data;
      }
      fclose($handle);
    }
    return $arrFile;
  }

  /**
   * @param array $expectedCsvArray two-dimensional array representing a CSV table
   * @param array $actualCsvArray two-dimensional array representing a CSV table
   */
  public function assertCsvArraysEqual($expectedCsvArray, $actualCsvArray) {
    // TODO provide better debug output

    $this->assertEquals(
      count($actualCsvArray),
      count($expectedCsvArray),
      'Arrays have different number of rows; in line ' . __LINE__
    );

    foreach ($actualCsvArray as $intKey => $strVal) {
      $this->assertNotNull($expectedCsvArray[$intKey], 'In line ' . __LINE__);
      $this->assertEquals(
        count($actualCsvArray[$intKey]),
        count($expectedCsvArray[$intKey]),
        'Arrays have different number of columns at row ' . $intKey . '; in line ' . __LINE__
      );
      $this->assertEquals($expectedCsvArray[$intKey], $strVal);
    }
  }
}