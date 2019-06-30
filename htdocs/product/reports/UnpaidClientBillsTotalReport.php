<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

$sql = 'SELECT
    llx_societe.nom as nom,
    sum(total_ttc) as total
FROM
    llx_facture AS f
JOIN llx_societe ON f.fk_soc = llx_societe.rowid
JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
WHERE
    f.paye = 0
AND f.fk_statut = 1
AND f.entity = 1
GROUP BY nom';

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
        nom => $row->nom,
        total => price($row->total),
    );
    $i++;
    $total+=$row->total;
}

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Nom',
    'Total'
);

$report_title = 'Reporte de total de facturas pendientes de cobro';

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

$pdf->AddPage();
$pdf->SetFont('Arial','B',11);
$pdf->Cell(80, 10, 'TOTAL POR COBRAR: $'.number_format($total, 2, '.', ','), 0, 0, 'L');

$pdf->Output();
