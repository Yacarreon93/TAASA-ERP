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

//Primero se obtienen todos los productos de cada factura y separamos ventas a credito y de contado, con IVA y sin IVA

$sql = "SELECT f.rowid,facnumber, fk_cond_reglement, f.total_ttc, datef, description, subprice, fd.total_ht, fd.total_tva, fd.total_ht as product_price
FROM
    llx_facture as f
    JOIN
    llx_facturedet as fd ON f.rowid = fd.fk_facture
    JOIN
    llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
WHERE MONTH(f.datef) = ".$month;
if(!$year) {
  $sql.=" AND YEAR(f.datef) = YEAR(CURDATE()) AND fk_statut != 3 AND (f.fk_account =".$account.")";
} else {
  $sql.=" AND YEAR(f.datef) = ".$year." AND fk_statut != 3 AND (f.fk_account =".$account.")";
}
$sql.=" AND fe.isticket = 1";
$sql.=" AND f.fk_soc != 1097";
$sql.=" ORDER BY
f.datef ASC";
$result = $db->query($sql);
if (!$result) {
    echo 'Error: '.$db->lasterror;
    die;
}

$VendidoContadoConIVA = array_fill(0,31,0);
$VendidoContadoSinIVA = array_fill(0,31,0);
$VendidoCreditoConIVA = array_fill(0,31,0);
$VendidoCreditoSinIVA = array_fill(0,31,0);
$IVAContado = array_fill(0,31,0);
$IVACredito = array_fill(0,31,0);

$dateArray = array();
$fechaTemp = '';
$i  = -1;
$dayCounter = 0;
$j = 0;
while ($row = $db->fetch_object($result))
{   if($fechaTemp != $row->datef) {
      $fechaTemp = $row->datef;
      $i++;
      $dayCounter++;
      $dateArray[$i] = $row->datef;
    }
    if($row->fk_cond_reglement != 1) { //facturas a credito
      if($row->total_tva > 0) { //con IVA
        $IVACredito[$i]+= $row->total_tva;
        $VendidoCreditoConIVA[$i] += ($row->product_price * 1.16);
      } else {
        $VendidoCreditoSinIVA[$i] += $row->product_price;
      }
    } else { //facturas de contado
        if($row->total_tva > 0) { //con IVA
          $IVAContado[$i]+= $row->total_tva;
          $VendidoContadoConIVA[$i]+= ($row->product_price * 1.16);
        }else {
          $VendidoContadoSinIVA[$i]+= $row->product_price;
        }
    }
}

//Obtenemos pagos de facturas y calculamos IVA Cobrado e IVA por cobrar

$factureService = new FacturePaiementsService();
$IVACobrado = $factureService->getTotalIVACobrado($db, $month, $year, $account);
$totalPerRowPagosACredito = $factureService->getTotalCobradoCreditoPerAccount($db, $month, $year, $account);
//$totalPerRowPagosContado = $factureService->getTotalCobradoContadoPerAccount($db, $month, $year, $account);
$importeContado = 0;
$importeCredito = 0;
$importeAbonos = 0;
$totalPerRowIVA = array_fill(0,31,0);
$totalPerRowAbonado = array_fill(0,31,0);





$totals = array();

for ($i = 0; $i < $dayCounter; $i++) {
    $totals[$i]['fecha'] = date('d/m/Y', strtotime($dateArray[$i]));;
    $totals[$i]['ventasCreditoSinIVA'] = formatMoney($VendidoCreditoSinIVA[$i]);
    $totals[$i]['ventasCreditoConIVA'] = formatMoney($VendidoCreditoConIVA[$i]);
    $totals[$i]['ventasContadoSinIVA'] = formatMoney($VendidoContadoSinIVA[$i]);
    $totals[$i]['ventasContadoConIVA'] = formatMoney($VendidoContadoConIVA[$i]);
    //$totals[$i]['IVA']  = formatMoney($IVAContado[$i] + $IVACredito[$i]);

    //$totals[$i]['totalSinIVA'] = formatMoney($VendidoCreditoSinIVA[$i] + $VendidoContadoSinIVA[$i]);
    //$totals[$i]['totalConIVA']  = formatMoney($VendidoCreditoConIVA[$i] + $VendidoContadoConIVA[$i]);

    if($totals[$i]['fecha'] == $totals[$i]['fecha']) {
      $totals[$i]['IVACobrado']  = formatMoney($IVACobrado["iva"][$i]);
  } else {
      $totals[$i]['IVACobrado']  = 0;
  }

  $importeContado += $VendidoContadoSinIVA[$i] + $VendidoContadoConIVA[$i];
  $importeCredito += $VendidoCreditoSinIVA[$i] + $VendidoCreditoConIVA[$i];

  //totals per row
  $totalPerRowCreditoSinIVA += $VendidoCreditoSinIVA[$i];
  $totalPerRowCreditoConIVA += $VendidoCreditoConIVA[$i];
  $totalPerRowContadoSinIVA += $VendidoContadoSinIVA[$i];
  $totalPerRowContadoConIVA += $VendidoContadoConIVA[$i];
  $totalPerRowIVACobrado += $IVACobrado["iva"][$i];
}

$totalContado = $totalPerRowContadoSinIVA + $totalPerRowContadoConIVA;
$totalCredito = $totalPerRowCreditoConIVA + $totalPerRowCreditoSinIVA;
//$totalIVA = (($totalPerRowContadoConIVA * 0.16) + ($totalPerRowCreditoConIVA * 0.16));


// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF('l');

// Títulos de las columnas
$header = array(
    'Fecha',
    'Sin IVA', //ventas credito sin iva
    'Con IVA', //ventas credito con iva
    'Sin IVA', //contado sin iva
    'Con IVA', //subtotal contado con iva
    'Ventas a credito', //iva cobrado de pagas a facturas a credito
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

$report_title = strtr('REPORTE DE TICKETS DEL MES - $M $Y', array(
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
//$pdf->Cell($columnWidth, 10, ' ', 0, 0, 'L');
$pdf->SetFillColor(...$grayRGB1);
$pdf->Cell($columnWidth * 1, 10, 'IVA COBRADO ', 1, 0, 'C', 1);
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
$pdf->SetFillColor(...$grayRGB1);
$pdf->Cell(($columnWidth), $pdf->rowHeight, formatMoney($totalPerRowIVACobrado), 1, 0, 'L', 1);


// //Totals
$pdf->ln();
$pdf->ln();
$pdf->SetFont('Arial', 'B', $pdf->fontSizeHeader);
$pdf->Cell($columnWidth * 3);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, 'Importe Contado', 1);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, formatMoney($totalContado), 1);
$pdf->ln();
$pdf->Cell($columnWidth * 3);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, utf8_decode('Importe Crédito'), 1);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, formatMoney($totalCredito), 1);
$pdf->ln();
$pdf->Cell($columnWidth * 3);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, 'Abonos a credito', 1);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, formatMoney($totalPerRowPagosACredito), 1);
$pdf->ln();
$pdf->Cell($columnWidth * 3);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, 'Total Cobrado', 1);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, formatMoney($totalContado + $totalPerRowPagosACredito), 1);
$pdf->ln();
$pdf->SetFillColor(...$grayRGB1);
$pdf->Cell($columnWidth * 3);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, 'TOTAL DEL CORTE', 1, 0, 'L', 1);
$pdf->Cell($columnWidth * 2, $pdf->rowHeight, formatMoney($totalContado + $totalCredito), 1, 0, 'L', 1);

// $pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
$db->close();
