<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

$stock_id=GETPOST('stockId','int');
$currency_value=GETPOST('currencyValue','int');
if (!$stock_id) $stock_id = 1;
if (!$currency_value) $currency_value = 19.5;

$sql = 'SELECT
    p.rowid,
    p.label,
    ps.reel,
    pfp.price,
    ps.reel * pfp.price AS total
    FROM
        llx_product AS p
    LEFT JOIN llx_product_stock AS ps ON ps.fk_product = p.rowid
    LEFT JOIN llx_product_fournisseur_price AS pfp ON pfp.rowid = (SELECT ROWID FROM llx_product_fournisseur_price AS pfp WHERE pfp.fk_product = p.rowid ORDER BY datec DESC LIMIT 1 ) 
    WHERE
        ps.fk_entrepot ='.$stock_id.'
    ORDER BY
        label ASC';

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
        label => $row->label,
        stock => $row->reel,
        price => ($row->price * $currency_value),
        total => ($row->price * $currency_value * $row->reel),
    );
    $total += $row->total;
    $i++;
}

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Producto',
    'Cantidad',
    'Precio',
    'Total'
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
