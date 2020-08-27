<?php

require_once('../../main.inc.php');
date_default_timezone_set('America/Mexico_City');
setlocale(LC_TIME, 'es_ES');

/** Se agrega la libreria PHPExcel */
require_once '../../includes/phpexcel/PHPExcel.php';
require_once '../../compta/facture/class/facturestats.class.php'; // aquí viene dol_get_first_day y dol_get_last_day

// Se crea el objeto PHPExcel
$objPHPExcel = new PHPExcel();

$fromDate = GETPOST('fromDate');
$toDate = GETPOST('toDate');
$year = strftime('%Y');

$fromDate = $fromDate[6] . $fromDate[7] . $fromDate[8] . $fromDate[9] . $fromDate[3] . $fromDate[4] . $fromDate[0] . $fromDate[1] . "000000";
$toDate = $toDate[6] . $toDate[7] . $toDate[8] . $toDate[9] . $toDate[3] . $toDate[4] . $toDate[0] . $toDate[1] . "235900";

$sql = "SELECT
fk_product, p.ref, p.label,sum(fd.qty) as cantidad
FROM
llx_facture AS f
JOIN llx_facturedet AS fd ON f.rowid = fd.fk_facture
JOIN llx_product as p ON fd.fk_product = p.rowid";
$sql.= " AND f.datef BETWEEN '".$fromDate."' AND '".$toDate."'";
$sql.= "GROUP BY fk_product
ORDER BY cantidad DESC
LIMIT 10";

$result=array();
$limit = 10;

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0; $other=0;
    while ($i < $num)
    {
        $row = $db->fetch_row($resql);
        if ($i < $limit || $num == $limit) $result[$i] = array($row[2],$row[0],$row[3],$row[1]); // rowid, ref, label, nb
        $i++;
    }
    $db->free($resql);
}
else dol_print_error($db);

$objPHPExcel->setActiveSheetIndex(0)
    ->mergeCells('A1:D1')
    ->setCellValue('A1', 'Top 10 Productos')
    ->setCellValue('A3',  'ID')
    ->setCellValue('B3',  'REF')
    ->setCellValue('C3',  'ETIQUETA')
    ->setCellValue('D3',  'CANTIDAD');

$i = 4;

foreach ($result as $product) {
    $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A'.$i,  $product[0])
        ->setCellValue('B'.$i,  $product[1])
        ->setCellValue('C'.$i,  utf8_encode($product[2]))
        ->setCellValue('D'.$i,  $product[3]);    
    $i++;
}

$estiloTituloReporte = array(
    'font' => array(
        'name'      => 'Verdana',
        'bold'      => true,
        'italic'    => false,
        'strike'    => false,
        'size'      => 16,
        'color'     => array('rgb' => 'FFFFFF')
    ),
    'fill' => array(
        'type'	=> PHPExcel_Style_Fill::FILL_SOLID,
        'color'	=> array('argb' => 'FF220835')
    ),
    'borders' => array(
        'allborders'    => array(
            'style'     => PHPExcel_Style_Border::BORDER_NONE                    
        )
    ), 
    'alignment' =>  array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        'rotation'   => 0,
        'wrap'       => TRUE
    ),
);

$estiloTituloColumnas = array(
    'font' => array(
        'name'      => 'Arial',
        'bold'      => true,                          
        'color'     => array('rgb' => 'FFFFFF'),
    ),
    'fill' 	=> array(
        'type'		 => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
        'rotation'   => 90,
        'startcolor' => array(
            'rgb' => '6D9BC8'// Azulmarino claro en titulo columnas
            //'rgb' => 'c47cf2'
        ),
        'endcolor'   => array(
            'argb' => 'FF431a5d' //titulo
        )
    ),
    'borders' => array(
        'top'     => array(
            'style' => PHPExcel_Style_Border::BORDER_MEDIUM ,
            'color' => array(
                'rgb' => '143860'
            )
        ),
        'bottom'     => array(
            'style' => PHPExcel_Style_Border::BORDER_MEDIUM ,
            'color' => array('rgb' => '143860'),
        )
    ),
    'alignment' =>  array(
        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        'wrap'       => TRUE
    ),
);
    
$left = array('alignment' => array(
    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
));

$center = array('alignment' => array(
    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
));

$objPHPExcel->getActiveSheet()->getStyle('A1:D1')->applyFromArray($estiloTituloReporte);
$objPHPExcel->getActiveSheet()->getStyle('A3:D3')->applyFromArray($estiloTituloColumnas);
$objPHPExcel->getActiveSheet()->getStyle('A4:C13')->applyFromArray($left);
$objPHPExcel->getActiveSheet()->getStyle('D4:D13')->applyFromArray($center);

for ($i = 'A'; $i <= 'D'; $i++) {
    $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension($i)->setAutoSize(TRUE);
}

$mes = substr(strtoupper(strftime('%b')), 0, -1);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Top_10_Productos_'.$mes.'_'.$year.'.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
