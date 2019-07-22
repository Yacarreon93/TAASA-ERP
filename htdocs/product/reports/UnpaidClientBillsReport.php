<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

$month     = GETPOST('month');
$year     = GETPOST('year');
if(!$month) {
    $month = date("M");
}
if(!$year) {
    $year = date("Y");
}

$dateBefore = $year . "0" .$month . "01000000";

$sql = 'SELECT
    f.facnumber,
    s.nom AS NAME,
    f.total AS base_imponible,
    f.tva AS importe_iva,
    f.total_ttc AS importe_total,
    DATEDIFF(CURDATE(), datef) AS days_p,
    DATEDIFF(f.date_lim_reglement, f.datef) AS limit_days,
    f.datef AS fecha_emision,
    f.date_lim_reglement AS fecha_limite,
    f.note_private,
    SUM(pf.amount) AS abonado,
    SUM(total_ttc) AS deuda_total
FROM
    llx_societe AS s,
    llx_facture AS f
LEFT JOIN llx_paiement_facture AS pf ON pf.fk_facture = f.rowid
JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
WHERE
    f.fk_soc = s.rowid
AND f.entity = 1
AND f.fk_statut = 1
AND DATE(datef) < "'.$dateBefore.'"
GROUP BY
    f.rowid,
    f.facnumber,
    ref_client,
    f.type,
    f.note_private,
    f.increment,
    f.total,
    f.tva,
    f.total_ttc,
    f.datef,
    f.date_lim_reglement,
    f.paye,
    f.fk_statut,
    s.nom,
    s.rowid,
    s.code_client,
    s.client
ORDER BY
    s.nom ASC,
    f.rowid DESC';

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
    $importeAPagar = ($row->importe_total - $row->abonado);
    $data[] = array(
        id => $row->facnumber,
        name => substr($row->NAME, 0, 18),
        importe_total => price($row->importe_total),
        fecha_emision => $row->fecha_emision,
        fecha_limite => $row->fecha_limite,
         dias_credito => $row->limit_days,
        dias_transcurridos=>$row->days_p,
        abonado => price($row->abonado)
    );
    $total += $importeAPagar;
    $i++;
}

$db->free($res);
$db->close();

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Id',
    'Name',
    'Importe Total',
    'Fecha de Emision',
    'Fecha Limite',
    'Dias de credito',
    'Dias transcurridos',
    'Abonado'
);

$report_title = 'Reporte de facturas pendientes de cobro';

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
$pdf->Cell(80, 10, 'TOTAL POR COBRAR: $'.number_format($total, 2, '.', ','), 0, 0, 'L');

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
