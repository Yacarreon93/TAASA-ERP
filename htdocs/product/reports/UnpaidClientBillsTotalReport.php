<?php

require_once('../../main.inc.php');
require_once('./report.class.php');
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

$month     = GETPOST('month');
$year     = GETPOST('year');
if(!$month) {
    $month = date("n");
}
if(!$year) {
    $year = date("Y");
}

if($month == 1) {
    $month_temp = 12;
} else {
    $month_temp = $month -1;
}

setlocale(LC_ALL, 'es_ES');


$dateObj   = DateTime::createFromFormat('!m', $month_temp);
$month_name = strftime('%B', $dateObj->getTimestamp());
if($month <= 9) {
    $dateBefore = $year . "0" .$month . "01000000";    
} else {
    $dateBefore = $year . $month . "01000000";
}

$sql = 'SELECT
    llx_societe.rowid, llx_societe.nom as nom,
    sum(total_ttc) as total, fk_account as account
FROM
    llx_facture AS f
JOIN llx_societe ON f.fk_soc = llx_societe.rowid
JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
WHERE
    f.paye = 0
AND f.fk_statut = 1
AND f.entity = 1
AND DATE(datef) < "'.$dateBefore.'"
GROUP BY nom';

if (!$result) {
    echo 'Error: '.$db->lasterror;
    die;
}

$object = new Client($db);
$i = 0;
$total = 0;
$totalAgs = 0;
$totalLeon = 0;
$result = $db->query($sql);
$data = array();
while ($row = $db->fetch_object($result))
{
  $object->fetch($row->rowid);
  $outstandingBills = $object->get_OutstandingBill($dateBefore);
   $total+=$outstandingBills;
    $data[] = array(
        nom => dol_trunc($row->nom,20),
        total =>  '$'.price($object->get_OutstandingBill($dateBefore))
    );
    $i++;
}

$db->free($res);
$db->close();


// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Nombre del cliente',
    'Total de deuda',
);

$report_title = 'Reporte de total de facturas pendientes de cobro - '.$month_name;

// Carga de datos
$pdf->SetFont('Arial', '', 11);

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->EnableHour();
$pdf->AddPage();
$pdf->createDynamicHeader($header, null);
$pdf->createDynamicRows($data, null);
$pdf->SetFont('Arial', '', 11);

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->AddPage();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(80, 10, 'TOTAL POR COBRAR: $'.number_format($total, 2, '.', ','), 0, 0, 'L');

$pdf->Output();
