<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

/* Obtener datos del vendedor */

$vendor = GETPOST('vendor');

if(!$vendor) {
    die('Error: se requiere el vendedor!');
}

$sql = 'SELECT * FROM llx_user WHERE rowid = '.$vendor;

$res = $db->query($sql) or die('ERROR en la consulta: '.$sql);

$row = $db->fetch_object($res);

$vendedor = $row->firstname;

$db->free($res);

/* Obtener a los clientes y su cartera vencida */

$sql = 'SELECT s.rowid, nom, vendor
FROM llx_societe AS s
JOIN llx_societe_extrafields AS se ON s.rowid = se.fk_object
WHERE vendor = '.$vendor;

$result = $db->query($sql);

if (!$result) {
    die ('Error: '.$db->lasterror);
}

$data = array();

$object = new Client($db);

while ($row = $db->fetch_object($result))
{
    $object->id = $row->rowid; // set id para poder obtener la cartera vencida

    $cartera_vencida = $object->get_OutstandingBill();

    $data[] = array(
        id => $row->rowid,
        name => substr($row->nom, 0, 50),
        amount => formatMoney($cartera_vencida),
    );
}

$db->free($result);
$db->close();

/* Reporte */

$pdf = new ReportPDF('l');

$header = array(
    'Id',
    'Cliente',
    'Riesgo alcanzado',
);

$columnWidth = $pdf->maxWidth / count($header);

$report_title = 'REPORTE DE CARTERA VENCIDA';
$report_subtitle = 'Vendedor: '.$vendedor;

$pdf->SetFont('Arial', '', 11);
$pdf->SetTitle($report_title);
$pdf->SetSubtitle($report_subtitle);
$pdf->EnableHour();
$pdf->AddPage();
$pdf->createDynamicHeader($header,array(
    'bold' => true,
    'background' => array(
        1 => [235, 235, 235],
        2 => [235, 235, 235],
        3 => [235, 235, 235],
    ),
));
$pdf->createDynamicRows($data, null);

$pdf->Output();