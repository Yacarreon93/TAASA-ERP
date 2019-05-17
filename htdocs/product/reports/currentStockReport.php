<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

$stockId=GETPOST('stockId','int');
if (!$stock_id) $stock_id = 1;

$sql = 'SELECT
    p.rowid,
    p.label,
    ps.reel,
    ps.fk_entrepot
    FROM
        llx_product AS p
    LEFT JOIN llx_product_stock AS ps ON ps.fk_product = p.rowid
    WHERE
        ps.fk_entrepot ='.$stock_id.' 
    ORDER BY
        p.rowid';

if (!$result) { 
    echo 'Error: '.$db->lasterror;
    die;
}

$i = 0;
$total = 0;
$result = $db->query($sql);
$data = array();
while ($row = $db->fetch_object($result))
{
    $data[] = array(
        id => $row->rowid,
        label => $row->label,
        stock => $row->reel,
    );
    $total += $row->total;
    $i++;
}

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Id',
    'Producto',
    'Cantidad',
);

$report_title = 'Reporte de inventario virtual';
    
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
$pdf->Write("Total", "Total en inventario ");
$pdf->Write("Total", $total);

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
