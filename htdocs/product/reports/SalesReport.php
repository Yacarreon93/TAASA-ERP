<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

$stockId = GETPOST('stockId');
if(!$stockId) {
    $stockId = 3;
}

$sql = 'SELECT
  f.facnumber,
  s.nom AS NAME,
  fe.isticket,
  f.total_ttc AS importe_total,
  DATEDIFF(CURDATE(), datef) AS days_p,
  DATEDIFF(f.date_lim_reglement, f.datef) AS limit_days,
  f.datef AS fecha_emision,
  fe.vendor,
  f.fk_cond_reglement,
  f.date_lim_reglement AS fecha_limite,
  SUM(pf.amount) AS abonado,
  (SUM(total_ttc) - SUM(pf.amount)) AS restante
  FROM
  llx_societe AS s,
  llx_facture AS f
  LEFT JOIN llx_paiement_facture AS pf ON pf.fk_facture = f.rowid
  JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
  WHERE
  f.fk_soc = s.rowid
  AND MONTH(f.datef) = MONTH(CURDATE())
  AND YEAR(f.datef) = YEAR(CURDATE())
  AND fk_statut != 3
  AND f.fk_account = '.$stockId.'
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
  f.datef DESC';

if (!$result) {
    echo 'Error: '.$db->lasterror;
    die;
}

$i = 0;
$totalVendido = 0;
$totalContado = 0;
$totalContadoIVA = 0;
$totalCredito = 0;
$totalCreditoIVA = 0;
$totalAbonado = 0;
$totalAbonadoIVA = 0;
$result = $db->query($sql);
$data = array();
while ($row = $db->fetch_object($result))
{
    $data[] = array(
        id => $row->facnumber,
        name => substr($row->NAME, 0, 18),
        importe_total => price($row->importe_total),
        fecha_limite => $row->fecha_limite,
         dias_credito => $row->limit_days,
        dias_transcurridos=>$row->days_p,
        abonado => price($row->abonado),
        restante => price($row->restante)
    );
    $totalVendido += $row->importe_total; //Total vendido
    if($row->fk_cond_reglement == 2) { //Ventas a credito
      $totalCredito += $row->importe_total; //Total vendido a credito
      $totalAbonado += $row->abonado; //Total abonado a credito
      if($row->isticket == 1) {
        $totalCreditoSinIVA += $row->importe_total; //Total vendido a credito sin IVA
        $totalAbonadoCreditoSinIVA += $row->abonado; //Total abonado a credito sin IVA
      } else {
        $totalCreditoIVA += $row->importe_total;
        $totalAbonadoIVA += $row->abonado;
      }
    } else { //Ventas de contado
      $totalContado += $row->importe_total;
    }
    $i++;
}

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Id',
    'Name',
    'Importe Total',
    'Fecha Limite',
    'Dias de credito',
    'Dias transcurridos',
    'Abonado',
    'Restante'
);

$report_title = 'Reporte de facturas del mes';

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
$pdf->Cell(80, 10, 'TOTAL VENDIDO: $'.number_format($totalVendido, 2, '.', ','), 0, 0, 'L');
  $pdf->ln();
$pdf->Cell(80, 10, 'TOTAL CONTADO: $'.number_format($totalContado, 2, '.', ','), 0, 0, 'L');
  $pdf->ln();
$pdf->Cell(80, 10, 'TOTAL CREDITO: $'.number_format($totalCredito, 2, '.', ','), 0, 0, 'L');
  $pdf->ln();
$pdf->Cell(80, 10, 'TOTAL ABONADO: $'.number_format($totalAbonado, 2, '.', ','), 0, 0, 'L');
  $pdf->ln();
$pdf->Cell(80, 10, 'TOTAL RECIBIDO: $'.number_format($totalAbonado+$totalContado, 2, '.', ','), 0, 0, 'L');

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
