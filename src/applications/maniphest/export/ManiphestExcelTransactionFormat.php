<?php

/**
 * This is largely based off ManiphestExcelDefaultFormat with a few changes:
 * 1. (All) Custom Fields are included in the output.
 * 2. To accommodate having custom fields, the way headers were organized has
 *   been changed from individual arrays for each cell 'metadata' to instead be
 *   a single array of cell 'metadata' objects.
 * 3. Description moved to last cell as it's typically the lengthiest.
 * 4. This was developed ad-hoc/as-needed in the moment so coding habits were
 *   favored over proper php coding guidelines (I use Java daily not PHP)
 *   and is also the reason for the following warnings..
 *
 * IMPORTANT: The current manner of getting custom fields was implemented with
 *   no regard to proper usage - ex: fiddling with the proxy to get field data.
 *   It was not clear what the appropriate manner for retrieving this data is
 *   so it is more likely to break with upgrades to Phabricator.
 *
 *   The only custom field types tested are the following:
 *    - PhabricatorStandardCustomFieldInt
 *    - PhabricatorStandardCustomFieldSelect
 *
 *   Another issues is that *_NO_* policy-checking is done on the fields -
 *   It wasn't clear whether this was the case, but PhabricatorCustomField
 *   does seem to have some methods for setting/requiring a "viewer".
 *   This was not needed in the moment so it's ignored for the time being.
 */

final class ManiphestExcelTransactionFormat extends ManiphestExcelFormat {
    public function getName() {
        return pht('Transaction Format');
    }

    public function getFileName() {
        return 'maniphest_transaction_' . date('Ymd');
    }

    /**
     * @param PHPExcel $workbook
     * @param array $tasks
     * @param array $handles
     * @param PhabricatorUser $user
     * @throws Exception
     */
    public function buildWorkbook(
        PHPExcel $workbook,
        array $tasks,
        array $handles,
        PhabricatorUser $user) {

        $sheet = $workbook->setActiveSheetIndex(0);
        $sheet->setTitle(pht('Tasks'));

        // Header Cell
        // title => the displayed header title in the spreadsheet, in row 0
        // width => initial width in pixels for the column, null leaves unspecified
        // celltype => which format the column data should be set as, default is STRING
        //   can be null if it's a date field
        // isDate => there is no date format in the PHPExcel_Cell_DataType, so this is needed
        // cftype => the custom field data type, only specified for custom field headers

        $colHeaders = array(
            array(
                'title' => pht('Task_ID'),
                'width' => null,
                'celltype' => PHPExcel_Cell_DataType::TYPE_STRING,
                'isDate' => false,
            ),
            array(
                'title' => pht('Title'),
                'width' => 60,
                'celltype' => PHPExcel_Cell_DataType::TYPE_STRING,
                'isDate' => false,
            ),
            array(
                'title' => pht('Projects'),
                'width' => 20,
                'celltype' => PHPExcel_Cell_DataType::TYPE_STRING,
                'isDate' => false,
            ),
            array(
                'title' => pht('State'),
                'width' => 30,
                'celltype' => PHPExcel_Cell_DataType::TYPE_STRING,
                'isDate' => false,
            ),
            array(
                'title' => pht('UpdatedTime'),
                'width' => 30,
                'celltype' => PHPExcel_Cell_DataType::TYPE_STRING,
                'isDate' => false,
            ),
        );

        $status_map = ManiphestTaskStatus::getTaskStatusMap();
        $pri_map = ManiphestTaskPriority::getTaskPriorityMap();


        $header_format = array(
            'font' => array(
                'bold' => true,
            ),
        );

        $rows = array();

        $headerRow = array();
        foreach ($colHeaders as $colIdx => $column) {
            $headerRow[] = $column['title'];
        }
        $rows[] = $headerRow;


        $transactions = array();
        if ($tasks) {
            $transactions = id(new ManiphestTransactionQuery())
                ->setViewer($user)
                ->withObjectPHIDs(mpull($tasks, 'getPHID'))
                ->needComments(true)
                ->execute();
        }

        $transactions = array_reverse($transactions, true);

        $tasks = mpull($tasks, null, 'getPHID');
        $transactions_temp = array();
        foreach ($transactions as $transaction) {
            if ($transaction->getTransactionType() == ManiphestTransaction::TYPE_STATUS) {
                $transactions_temp[] = $transaction;
            }

        }

        $transactions = $transactions_temp;

        foreach ($transactions as $transaction) {
            $task_owner = null;
            $task_author = null;
            $row = array(
                'T' . $tasks[$transaction->getObjectPHID()]->getID(),
                $tasks[$transaction->getObjectPHID()]->getTitle(),
                $task_author,
                $task_owner,
                $transaction->getObjectPHID(),
                $transaction->getNewValue(),
                "666 ",
            );

            $rows[] = $row;
        }

//        foreach ($tasks as $task) {
//            $task_owner = null;
//            $task_author = null;
//            if ($task->getOwnerPHID()) {
//                $task_owner = $handles[$task->getOwnerPHID()]->getName();
//            }
//            if ($task->getAuthorPHID()) {
//                $task_author = $handles[$task->getAuthorPHID()]->getName();
//            }
//
//
//
//            $row = array(
//                'T' . $task->getID(),
//                $task_author,
//                $task_owner,
//                idx($status_map, $task->getStatus(), '?'),
//                $task->getSubtype(),
//                idx($pri_map, $task->getPriority(), '?'),
//                date('Y-m-d H:i:s',$task->getDateCreated()),
//                date('Y-m-d H:i:s',$task->getDateModified()),
//                $task->getTitle(),
//                PhabricatorEnv::getProductionURI('/T' . $task->getID()),
//                "888",
//                current($transactions)->getNewValue(),
//                $task_ids[current($transactions)],
//                "666 ",
//            );
//
//            $rows[] = $row;
//        }

        foreach ($rows as $row => $cols) {
            foreach ($cols as $col => $spec) {
                $cell_name = $this->col($col) . ($row + 1);
                $cell = $sheet
                    ->setCellValue($cell_name, $spec, $return_cell = true);

                // If the header row only apply the bold-style and width, but do not
                // apply the date-format/data-type since the values will always be string
                if ($row == 0) {
                    $sheet->getStyle($cell_name)->applyFromArray($header_format);

                    $width = $colHeaders[$col]['width'];
                    if ($width !== null) {
                        $sheet->getColumnDimension($this->col($col))->setWidth($width);
                    }
                } else {
                    $is_date = $colHeaders[$col]['isDate'];
                    if ($is_date) {
                        $code = PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD2;
                        $sheet
                            ->getStyle($cell_name)
                            ->getNumberFormat()
                            ->setFormatCode($code);
                    } else {
                        $cellType = $colHeaders[$col]['celltype'];
                        if ($cellType == null) {
                            $cellType = PHPExcel_Cell_DataType::TYPE_STRING;
                        }
                        $cell->setDataType($cellType);
                    }
                }
            }
        }
    }

    private function col($n) {
        return chr(ord('A') + $n);
    }
}

