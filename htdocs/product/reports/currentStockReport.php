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
    pfp.currency,
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
    if($row->currency == 'USD') {
        $priceTemp = $row->price * $currency_value;
    } else {
        $priceTemp = $row->price;
    }
    $totalTemp = ($priceTemp * $row->reel);
    $data[] = array(
        label => $row->label,
        stock => $row->reel,
        currency => $row->currency,
        price => '$'.price($priceTemp),
        total => '$'.price($priceTemp * $row->reel),
    );
    $total += $totalTemp;
    $i++;
}

$db->free($res);
$db->close();

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Producto',
    'Cantidad',
    'Moneda',
    'Precio',
    'Total'
);

if($stock_id == 1) {
    $inventoryName = 'Aguascalientes Bodega';
} else if($stock_id == 2) {
    $inventoryName = 'Aguascalientes Produccion ';
} else if($stock_id == 3) {
     $inventoryName = 'Leon ';
} else if($stock_id == 4) {
     $inventoryName = 'Lagos ';
}

$date = date('Y-m-d');

$report_title = 'Reporte de inventario virtual '. $inventoryName. ' a ';

// Carga de datos
$pdf->SetFont('Arial', '', 11);

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->EnableHour();
$pdf->AddPage();
$pdf->createDynamicHeader($header);
$pdf->createDynamicRows($data);
$pdf->SetFont('Arial', '', 11);
$pdf->Write("Total", "Total en inventario ");
$pdf->Write("Total", '$'.price($total));

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
