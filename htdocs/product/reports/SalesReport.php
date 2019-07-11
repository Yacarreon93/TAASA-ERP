<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

require_once DOL_DOCUMENT_ROOT.'/product/service/FacturePaiementsService.php';

$account = GETPOST('account');
$month = GETPOST('month');

if(!$account) {
    $account = 1;
}
if(!$month) {
    $month = 1;
}

$sql = "SELECT
 datep AS fecha_pago, p.rowid AS id_pago, p.amount AS total_pago, pf.amount AS importe_pago,
 f.rowid AS facid, f.facnumber, f.fk_cond_reglement, total_ttc, total AS subtotal, tva, t1.abonado
FROM
 llx_paiement_facture AS pf
JOIN llx_paiement AS p ON pf.fk_paiement = p.rowid
JOIN llx_bank AS b ON p.fk_bank = b.rowid
LEFT JOIN llx_facture AS f ON pf.fk_facture = f.rowid
JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
JOIN (SELECT
f.rowid,
    f.facnumber,
    SUM(pf.amount) AS abonado
FROM
    llx_societe AS s,
    llx_facture AS f
LEFT JOIN llx_paiement_facture AS pf ON pf.fk_facture = f.rowid
JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
WHERE
    f.fk_soc = s.rowid
AND f.entity = 1
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
    datef ASC,
    f.rowid DESC
	 ) t1 ON t1.rowid = f.rowid
WHERE MONTH(p.datep) = ".$month." AND YEAR(p.datep) = YEAR(CURDATE()) AND fk_statut != 3 AND (b.fk_account =".$account.")
ORDER BY
p.datep ASC,
p.amount DESC";

$result = $db->query($sql);
if (!$result) {
    echo 'Error: '.$db->lasterror;
    die;
}

$totalAbonosAcredito = array_fill(0,30,0);
$totalAbonosContado = array_fill(0,30,0);
$VendidoContadoConIVA = array_fill(0,30,0);
$VendidoContadoSinIVA = array_fill(0,30,0);
$importeContado = 0;
$importeCredito = 0;
$importeAbonos = 0;

$dateArray = array();
$fechaTemp = '';
$i  = -1;
$dayCounter = 0;
$j = 0;
while ($row = $db->fetch_object($result))
{   if($fechaTemp != $row->fecha_pago) {
      $fechaTemp = $row->fecha_pago;
      $i++;
      $dayCounter++;
      $dateArray[$i] = $row->fecha_pago;
    }
    if($row->fk_cond_reglement == 2) { //facturas a credito
      $totalAbonosAcredito[$i] += $row->importe_pago;
    } else { //facturas de contado
        $totalAbonosContado[$i] += $row->importe_pago;
        if($row->tva > 0) {
          $IVAContado[$i]+= $row->tva;
          $VendidoContadoConIVA[$i]+= $row->subtotal;
        }else {
          $VendidoContadoSinIVA[$i]+= $row->importe_pago;
        }
    }
}

$factureService = new FacturePaiementsService();
//CREDITO
$VendidoCreditoSinIVA = $factureService->getTotalFacturasSinIVAACredito($db, $month, $account);
$VendidoCreditoConIVA = $factureService->getTotalFacturasConIVAACredito($db, $month, $account);
$IVACredito = $factureService->getTotalIVAFacturasACredito($db, $month, $account);

//CONTADO
//$VendidoContadoSinIVA = $factureService->getTotalFacturasSinIVAAContado($dateArray, $db, $month, $account);
//$VendidoContadoConIVA = $factureService->getTotalFacturasConIVAAContado($db, $month, $account);
//$IVAContado = $factureService->getTotalIVAFacturasContado($db, $month, $account);

$totals = array();

for($i = 0; $i < $dayCounter; $i++) {
    $totals[$i]['fecha'] = $dateArray[$i];
    $totals[$i]['ventasCreditoSinIVA'] = $VendidoCreditoSinIVA[$i]['total'];
    $totals[$i]['ventasCreditoConIVA'] = $VendidoCreditoConIVA[$i]['total'];
    $totals[$i]['ventasContadoSinIVA'] = $VendidoContadoSinIVA[$i];
    $totals[$i]['ventasContadoConIVA'] = $VendidoContadoConIVA[$i];
    $totals[$i]['IVA']  = $IVAContado[$i] + $IVACredito[$i]['total'];
    $totals[$i]['totalSinIVA'] = $VendidoCreditoSinIVA[$i]['total'] + $VendidoContadoSinIVA[$i];
    $totals[$i]['totalConIVA']  = $VendidoCreditoConIVA[$i]['total'] + $VendidoContadoConIVA[$i];
    $totals[$i]['importeAbonos'] = $totalAbonosAcredito[$i];
  $importeContado += $VendidoContadoSinIVA[$i] + $VendidoContadoConIVA[$i];
  $importeCredito += $VendidoCreditoSinIVA[$i]['total'] + $VendidoCreditoConIVA[$i]['total'];
  $importeAbonos += $totalAbonosAcredito[$i];
}


// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Fecha',
    'Sin IVA', //ventas credito sin iva
    'Con IVA', //ventas credito con iva
    'Sin IVA', //contado sin iva
    'Con IVA', //subtotal contado con iva
    'IVA', //total de IVA contado + credito
    'Sin IVA', //credito + contado sin iva
    'Con IVA', //subtotal credito + contado con iva
    'Abonado', //total de abonos a credito
);

if($account == 1)
{
    $accountName = 'Aguascalientes';
} else if($account == 3)
{
    $accountName = 'Lagos ';
} else if($account == 5 )
{
     $accountName = 'Leon ';
}

$report_title = $accountName .' Reporte de ventas del mes';

// Carga de datos
$pdf->SetFont('Arial', '', 11);

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->AddPage();
$pdf->Cell(40, 10, ' ', 0, 0, 'L');
$pdf->Cell(60, 10, 'VENTAS CREDITO', 0, 0, 'L');
$pdf->Cell(90, 10, 'VENTAS CONTADO', 0, 0, 'L');
$pdf->Cell(80, 10, 'TOTAL DE VENTAS', 0, 0, 'L');
$pdf->ln();
$pdf->createDynamicHeader($header);
$pdf->createDynamicRows($totals);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(80, 10, 'Importe Contado: $'.number_format($importeContado, 2, '.', ','), 0, 0, 'L');
  $pdf->ln();
$pdf->Cell(80, 10, 'Importe Credito: $'.number_format($importeCredito, 2, '.', ','), 0, 0, 'L');
  $pdf->ln();
$pdf->Cell(80, 10, 'Importe de Abonos: $'.number_format($importeAbonos, 2, '.', ','), 0, 0, 'L');
  $pdf->ln();
$pdf->Cell(80, 10, 'TOTAL DEL CORTE: $'.number_format(($importeContado + $importeAbonos), 2, '.', ','), 0, 0, 'L');

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
