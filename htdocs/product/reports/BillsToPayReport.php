<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

$sql = 'SELECT s.rowid AS socid, s.nom AS name,
fac.rowid AS facid, fac.ref AS ref, fac.datef AS date,
fac.date_lim_reglement AS date_echeance, fac.total_ttc AS total,
fac.paye AS paye, fac.fk_statut AS fk_statut, fac.libelle
FROM llx_societe AS s, llx_facture_fourn AS fac
LEFT JOIN llx_projet AS p ON p.rowid = fac.fk_projet
WHERE fac.entity = 1 AND fac.fk_soc = s.rowid
ORDER BY fac.datef DESC,fac.rowid DESC';

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
  if($row->fk_statut == 1) {
    $status = 'Pte. pago';
  } else if($row->fk_statut == 2) {
    $status = 'Pagada';
  } else {
    $status = 'Borrador';
  }
    $data[] = array(
        id => $row->ref,
        fecha => $row->date,
        fecha_vencimiento => $row->date_echeance,
        proveedor => substr($row->name, 0, 18),
        importe => '$'.price($row->total),
        estado => $status
    );
    if($row->fk_statut == 1) {
        $total += $row->total;
    }
    $i++;
}

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Id',
    'Fecha',
    'Fecha de Vencimiento',
    'Proveedor',
    'Importe',
    'Estado'
);

$report_title = 'Reporte de antiguedad de saldos';

// Carga de datos
$pdf->SetFont('Arial', '', 11);

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->AddPage();
$pdf->createDynamicHeader($header);
$pdf->createDynamicRows($data);
$pdf->AddPage();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(80, 10, 'TOTAL POR PAGAR: $'.number_format($total, 2, '.', ','), 0, 0, 'L');

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
