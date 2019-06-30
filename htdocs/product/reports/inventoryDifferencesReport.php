<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

$stockId=GETPOST('stockId','int');
if (!$stockId) $stockId = 1;

$sql = 'SELECT
        p.rowid,
        p.label,
        ps.reel,
        icp.reel as reel_icp    
        FROM
        llx_product AS p
        LEFT JOIN llx_product_stock AS ps ON ps.fk_product = p.rowid
        LEFT JOIN llx_inventory_closing_temp AS icp ON icp.fk_product = p.rowid
        WHERE
        ps.fk_entrepot ='.$stockId.'
        ORDER BY
        p.rowid';

if (!$result) { 
    echo 'Error: '.$db->lasterror;
    die;
}

$i = 0;
$total = 0;
$totalStock = 0;
$totalStockIcp = 0;
$totalDifference = 0;
$result = $db->query($sql);
$data = array();
while ($row = $db->fetch_object($result))
{
    $totalStock += $row->reel;
    $totalStockIcp += $row->reel_icp;
    $totalDifference += ($row->reel -  $row->reel_icp);
    $data[] = array(
        id =>  $row->rowid,
        label => $row->label,
        stock => $row->reel,
        stock_icp => $row->reel_icp,
        difference=> ($row->reel -  $row->reel_icp)
    );
    $i++;
}

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Id',
    'Producto',
    'Cantidad Virtual',
    'Cantidad Fisica',
    'Diferencia'
);

if($stockId == 1) {
    $inventoryName = 'Aguascalientes Bodega';
} else if($stockId == 2) {
    $inventoryName = 'Aguascalientes Produccion ';
} else if($stockId == 3) {
     $inventoryName = 'Leon ';
} else if($stockId == 4) {
     $inventoryName = 'Lagos ';
} 

$date = date('Y-m-d');

$report_title = 'Reporte de diferencias en inventario '. $inventoryName. ' a  '.$date;
    
// Carga de datos
$pdf->SetFont('Arial', '', 11);

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->setTitle($report_title);
$pdf->AddPage();
$pdf->createDynamicHeader($header);
$pdf->createDynamicRows($data);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(
                    $pdf->maxWidth / count($data[0]),
                    $pdf->rowHeight,
                    utf8_decode('Total'),
                    1,
                    0,
                    'L'
                );
$pdf->Cell(
                    $pdf->maxWidth / count($data[0]),
                    $pdf->rowHeight,
                    utf8_decode(''),
                    1,
                    0,
                    'L'
                );
$pdf->Cell(
                    $pdf->maxWidth / count($data[0]),
                    $pdf->rowHeight,
                    utf8_decode($totalStock),
                    1,
                    0,
                    'L'
                );
$pdf->Cell(
                    $pdf->maxWidth / count($data[0]),
                    $pdf->rowHeight,
                    utf8_decode($totalStockIcp),
                    1,
                    0,
                    'L'
                );
$pdf->Cell(
                    $pdf->maxWidth / count($data[0]),
                    $pdf->rowHeight,
                    utf8_decode($totalDifference),
                    1,
                    0,
                    'L'
                );
$pdf->Output();
