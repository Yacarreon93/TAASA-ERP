<?php

require_once('../../main.inc.php');
require_once('./report.class.php');
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

$vendor     = GETPOST('vendor');
if(!$vendor) {
    $vendor = 13;
}

$sql = 'SELECT * FROM llx_user WHERE rowid = '.$vendor;
$res = $db->query($sql) or die('ERROR en la consulta: '.$sql);
$row = $db->fetch_object($res);
$vendedor = $row->firstname;

$sql = 'SELECT
    llx_societe.rowid, llx_societe.nom as nom,
    sum(total_ttc) as total
FROM
    llx_facture AS f
JOIN llx_societe ON f.fk_soc = llx_societe.rowid
JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
WHERE
    f.paye = 0
AND f.fk_statut = 1
AND f.entity = 1
AND fe.vendor = '.$vendor.'
GROUP BY nom';

if (!$result) {
    echo 'Error: '.$db->lasterror;
    die;
}

$object = new Client($db);
$i = 0;
$total = 0;
$result = $db->query($sql);
$data = array();
while ($row = $db->fetch_object($result))
{
  $object->fetch($row->rowid);
  $outstandingBills = $object->get_OutstandingBill();
    $data[] = array(
        nom => $row->nom,
        total => price($object->get_OutstandingBill()),
    );
    $i++;
    $total+=$outstandingBills;
}

$db->free($res);
$db->close();

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Tercero',
    'Total'
);

$report_title = 'Total cuentas por cobrar '.$vendedor;

// Carga de datos
$pdf->SetFont('Arial', '', 11);

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->AddPage();
$pdf->createDynamicHeader($header);
$pdf->createDynamicRows($data);
$pdf->SetFont('Arial', '', 11);
 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
