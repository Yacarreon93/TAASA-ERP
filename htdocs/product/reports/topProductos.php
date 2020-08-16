<?php

require_once('../../main.inc.php');
date_default_timezone_set('America/Mexico_City');
setlocale(LC_TIME, 'es_ES');

/** Se agrega la libreria PHPExcel */
require_once '../../includes/phpexcel/PHPExcel.php';

// Se crea el objeto PHPExcel
$objPHPExcel = new PHPExcel();

$month = GETPOST('month');

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Top_10_Productos_'.substr(strtoupper(strftime('%b')), 0, -1).'_'.strftime('%Y').'.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
