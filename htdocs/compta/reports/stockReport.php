<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

$stockId=GETPOST('stockId','int');

$sql = 'SELECT
    p.rowid,
    p.label,
    ps.reel,
    ps.fk_entrepot,
    pfp.price,
    pfp.datec,
    (ps.reel * pfp.price) AS total
FROM
    llx_product AS p
LEFT JOIN llx_product_stock AS ps ON ps.fk_product = p.rowid
LEFT JOIN (
    SELECT
        fk_product,
        MAX(datec) AS datec,
        price
    FROM
        llx_product_fournisseur_price
    GROUP BY
        fk_product
) AS pfp ON pfp.fk_product = p.rowid
WHERE
    ps.fk_entrepot = 1 -- Numero de almacen
ORDER BY
    p.rowid;';

$result = $db->query($sql);

if (!$result) { 
    echo 'Error: '.$db->lasterror;
    die;
}

$total = 0;
 $num = $db->num_rows($result);
 $i = 0;
  while ($i < $num)
    {
        $row = $db->fetch_object($result);
        $total+= $row->total;
        $i++;            
    }
// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF($db, $result, 'l', function ($db,$result) {
    $i = 0;
    $num = $db->num_rows($result);
    $data = array();
    while ($i < $num)
    {
        $row = $db->fetch_object($result);
        $data[] = array(
            id => $i,
            label    => $row->label,
            stock  => $row->reel,
            price => $row->price,
            total => $row->total,
        );
        $i++;            
    }
    return $data;
});

// Títulos de las columnas
$header = array(
    'Id',
    'Producto',
    'Cantidad',
    'Costo',
    'Total',
);

$report_title = 'Reporte de inventario';
    
// Carga de datos
$pdf->SetFont('Arial', '', 11);

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->setTitle($report_title);
$pdf->AddPage();
$pdf->createDynamicHeader($header);
$pdf->createDynamicRows();
$pdf->SetFont('Arial', '', 11);
$pdf->Write("Total","Total en inventario");
$pdf->Write("Total",$total);

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
