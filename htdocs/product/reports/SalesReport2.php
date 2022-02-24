<?php

require_once('../../main.inc.php');
require_once('./report.class.php');

require_once DOL_DOCUMENT_ROOT.'/product/service/FacturePaiementsService.php';

$account = GETPOST('account');
$month = GETPOST('month');
$year = GETPOST('year');

if(!$account) {
    $account = 1;
}
if(!$month) {
    $month = 1;
}
if(!$year) {
    $year = date("Y");
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
AND f.fk_soc != 1097
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
WHERE MONTH(p.datep) = ".$month;
if(!$year) {
  $sql.=" AND YEAR(p.datep) = YEAR(CURDATE()) AND fk_statut != 3 AND (b.fk_account =".$account.")";
} else {
  $sql.=" AND YEAR(p.datep) = ".$year." AND fk_statut != 3 AND (b.fk_account =".$account.")";
}
$sql.=" ORDER BY
p.datep ASC,
p.amount DESC";

$result = $db->query($sql);
if (!$result) {
    echo 'Error: '.$db->lasterror;
    die;
}

$totalAbonosAcredito = array_fill(0,31,0);
$totalAbonosContado = array_fill(0,31,0);
$VendidoContadoConIVA = array_fill(0,31,0);
$VendidoContadoSinIVA = array_fill(0,31,0);
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
          $VendidoContadoConIVA[$i]+= $row->subtotal;
        }else {
          $VendidoContadoSinIVA[$i]+= $row->importe_pago;
        }
    }
}

$factureService = new FacturePaiementsService();
//CREDITO
$VendidoCreditoSinIVA = $factureService->getTotalFacturasSinIVAACredito($db, $month, $year, $account);
$VendidoCreditoConIVA = $factureService->getTotalFacturasConIVAACredito($db, $month, $year, $account);

$IVACobrado = $factureService->getTotalIVACobrado($db, $month, $year, $account);

$totals = array();

for ($i = 0; $i < $dayCounter; $i++) {
    $totals[$i]['fecha'] = date('d/m/Y', strtotime($dateArray[$i]));;
    $totals[$i]['ventasCreditoSinIVA'] = formatMoney($VendidoCreditoSinIVA[$i]['total']);
    $totals[$i]['ventasCreditoConIVA'] = formatMoney($VendidoCreditoConIVA[$i]['total']);
    $totals[$i]['ventasContadoSinIVA'] = formatMoney($VendidoContadoSinIVA[$i]);
    $totals[$i]['ventasContadoConIVA'] = formatMoney($VendidoContadoConIVA[$i]);
    if($totals[$i]['fecha'] == $totals[$i]['fecha']) {
        $totals[$i]['IVA']  = formatMoney($IVACobrado["iva"][$i]);
    } else {
        $totals[$i]['IVA']  = 0;
    }
    $totals[$i]['totalSinIVA'] = formatMoney($VendidoCreditoSinIVA[$i]['total'] + $VendidoContadoSinIVA[$i]);
    $totals[$i]['totalConIVA']  = formatMoney($VendidoCreditoConIVA[$i]['total'] + $VendidoContadoConIVA[$i]);
    $totals[$i]['importeAbonos'] = formatMoney($totalAbonosAcredito[$i]);

  $importeContado += $VendidoContadoSinIVA[$i] + $VendidoContadoConIVA[$i];
  $importeCredito += $VendidoCreditoSinIVA[$i]['total'] + $VendidoCreditoConIVA[$i]['total'];
  $importeAbonos += $totalAbonosAcredito[$i];

  //totals per row
  $totalPerRowCreditoSinIVA += $VendidoCreditoSinIVA[$i]['total'];
  $totalPerRowCreditoConIVA += $VendidoCreditoConIVA[$i]['total'];
  $totalPerRowContadoSinIVA += $VendidoContadoSinIVA[$i];
  $totalPerRowContadoConIVA += $VendidoContadoConIVA[$i];
  $totalPerRowIVA += $IVACobrado["iva"][$i];
  $totalPerRowTotalSinIVA += $VendidoCreditoSinIVA[$i]['total'] + $VendidoContadoSinIVA[$i];
  $totalPerRowTotalConIVA += $VendidoCreditoConIVA[$i]['total'] + $VendidoContadoConIVA[$i];
  $totalPerRowAbonado += $totalAbonosAcredito[$i];

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

$dateObj   = DateTime::createFromFormat('!m', $month);
$month_name = strftime('%B', $dateObj->getTimestamp());

$yearTemp = 

$report_title = strtr('REPORTE DE VENTAS DEL MES - $M $Y', array(
    '$M' => $month_name,
    '$Y' =>  $year,
));

$report_subtitle = "CUENTA: $accountName";

// Carga de datos
$pdf->SetFont('Arial', '', 11);

$columnWidth = $pdf->maxWidth / count($header);

$grayRGB1 = [235, 235, 235];
$grayRGB2 = [191, 191, 191];

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->SetSubtitle($report_subtitle);
$pdf->EnableHour();
$pdf->AddPage();
$pdf->Cell($columnWidth, 10, ' ', 0, 0, 'L');
$pdf->SetFillColor(...$grayRGB1);
$pdf->SetFont('', 'B');
$pdf->Cell($columnWidth * 2, 10, 'VENTAS CREDITO', 1, 0, 'C', 1);
$pdf->SetFillColor(...$grayRGB2);
$pdf->Cell($columnWidth * 2, 10, 'VENTAS CONTADO', 1, 0, 'C', 1);
$pdf->Cell($columnWidth, 10, ' ', 0, 0, 'L');
$pdf->SetFillColor(...$grayRGB1);
$pdf->Cell($columnWidth * 2, 10, 'TOTAL DE VENTAS', 1, 0, 'C', 1);
$pdf->SetFont('', '');
$pdf->ln();

$pdf->createDynamicHeader($header, array(
    'bold' => true,
    'background' => array(
        '2' => $grayRGB1,
        '3' => $grayRGB1,
        '4' => $grayRGB2,
        '5' => $grayRGB2,
        '7' => $grayRGB1,
        '8' => $grayRGB1,
    ),
));

// for($i = 0; $i < 10; $i++) {
//     $totals[$i][] = date('d/m/Y', strtotime('2019-12-26 12:00:00'));
//     $totals[$i][] = 'qwe';
//     $totals[$i][] = 'qwe';
//     $totals[$i][] = 'qwe';
//     $totals[$i][] = 'qwe';
//     $totals[$i][] = 'qwe';
//     $totals[$i][] = 'qwe';
//     $totals[$i][] = 'qwe';
//     $totals[$i][] = 'qwe';
// } 

$pdf->createDynamicRows($totals, array(
    'bold' => false,
    'background' => array(
        '2' => $grayRGB1,
        '3' => $grayRGB1,
        '4' => $grayRGB2,
        '5' => $grayRGB2,
        '7' => $grayRGB1,
        '8' => $grayRGB1,
    ),
));

//Totals per row
$pdf->SetFont('', 'B', $pdf->fontSizeHeader);
$pdf->Cell(($columnWidth), $pdf->rowHeight, 'Total', 1, 0, 'L');
$pdf->SetFont('', 'B', $pdf->fontSizeRows);
$pdf->SetFillColor(...$grayRGB1);
$pdf->Cell(($columnWidth), $pdf->rowHeight, formatMoney($totalPerRowCreditoSinIVA), 1, 0, 'L', 1);
$pdf->Cell(($columnWidth), $pdf->rowHeight, formatMoney($totalPerRowCreditoConIVA), 1, 0, 'L', 1);
$pdf->SetFillColor(...$grayRGB2);
$pdf->Cell(($columnWidth), $pdf->rowHeight, formatMoney($totalPerRowContadoSinIVA), 1, 0, 'L', 1);
$pdf->Cell(($columnWidth), $pdf->rowHeight, formatMoney($totalPerRowContadoConIVA), 1, 0, 'L', 1);
$pdf->Cell(($columnWidth), $pdf->rowHeight, formatMoney($totalPerRowIVA), 1, 0, 'L');
$pdf->SetFillColor(...$grayRGB1);
$pdf->Cell(($columnWidth), $pdf->rowHeight, formatMoney($totalPerRowTotalSinIVA), 1, 0, 'L', 1);
$pdf->Cell(($columnWidth), $pdf->rowHeight, formatMoney($totalPerRowTotalConIVA), 1, 0, 'L', 1);
$pdf->Cell(($columnWidth), $pdf->rowHeight, formatMoney($totalPerRowAbonado), 1, 0, 'L');

//Totals
$pdf->ln();
$pdf->ln();
$pdf->SetFont('Arial', 'B', $pdf->fontSizeHeader);
$pdf->Cell($columnWidth * 5);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, 'Importe Contado', 1);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, formatMoney($importeContado), 1);
$pdf->ln();
$pdf->Cell($columnWidth * 5);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, utf8_decode('Importe Crédito'), 1);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, formatMoney($importeCredito), 1);
$pdf->ln();
$pdf->Cell($columnWidth * 5);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, 'Importe de Abonos', 1);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, formatMoney($importeAbonos), 1);
$pdf->ln();
$pdf->SetFillColor(...$grayRGB1);
$pdf->Cell($columnWidth * 5);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, 'TOTAL DEL CORTE', 1, 0, 'L', 1);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, formatMoney($importeContado + $importeAbonos), 1, 0, 'L', 1);

 //$pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
$db->close();
