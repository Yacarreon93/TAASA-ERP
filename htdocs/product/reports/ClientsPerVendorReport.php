<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

$vendor     = GETPOST('vendor');
if(!$vendor) {
    $vendor = 13;
}

$sql = 'SELECT * FROM llx_user WHERE rowid = '.$vendor;
$res = $db->query($sql) or die('ERROR en la consulta: '.$sql);
$row = $db->fetch_object($res);
$vendedor = $row->firstname;

$sql = 'SELECT s.rowid, nom, vendor
FROM llx_societe AS s
JOIN llx_societe_extrafields AS se ON s.rowid = se.fk_object
WHERE vendor = ' .$vendor;

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
        name => substr($row->nom, 0, 18),
    );
    $i++;
}

$db->free($res);
$db->close();
// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Id',
    'Nombre'
);

$report_title =' Clientes por Vendedor '.$vendedor;

// Carga de datos
$pdf->SetFont('Arial', '', 11);

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->EnableHour();
$pdf->AddPage();
$pdf->createDynamicHeader($header,null);
$pdf->createDynamicRows($data, null);
$pdf->SetFont('Arial', '', 11);

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
$db->close();
