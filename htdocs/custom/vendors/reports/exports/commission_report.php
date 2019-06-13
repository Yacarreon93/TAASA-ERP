<?php

require_once '../../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/vendors/class/CommissionsPDF.php';

$id         = GETPOST('id','int');
$sortfield  = GETPOST("sortfield",'alpha');
$sortorder  = GETPOST("sortorder",'alpha');
$offset = $conf->liste_limit;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.datef';
$limit = $conf->liste_limit;
$day         = GETPOST('day','int');
$month       = GETPOST('month','int');
$year        = GETPOST('year','int');
$month_general  = GETPOST('month_general','int');
$year_general   = GETPOST('year_general','int');
if($month_general != '') $month = $month_general;
if($year_general != '') $year = $year_general;

$object = new User($db);

$res = $object->fetch($id);
if ($res < 0) { dol_print_error($db,$object->error); exit; }

$sql = 'SELECT DISTINCT
	p.rowid,
	f.facnumber,
	s.nom AS name,
	datef as df,
	total_ttc,
	p.datep,
	f.date_lim_reglement,
	pf.amount,
	se.commission
FROM
	llx_facture AS f
RIGHT JOIN llx_paiement_facture AS pf ON pf.fk_facture = f.rowid
JOIN llx_paiement AS p ON pf.fk_paiement = p.rowid
JOIN llx_societe AS s ON f.fk_soc = s.rowid
JOIN llx_societe_extrafields AS se ON se.fk_object = s.rowid
JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
WHERE
	f.fk_statut != 3 AND fe.vendor = '.$id.' AND (p.datep < f.date_lim_reglement OR DATE(p.datep) = DATE(f.date_lim_reglement))';

if ($fromDate && $toDate) {
    $sql.= " AND f.datef BETWEEN '".$fromDate."' AND '".$toDate."'";
}
else if ($month > 0)
{
    if ($year > 0 && empty($day))
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    $sql.= " AND date_format(f.datef, '%m') = '".$month."'";
}
else if ($year > 0)
{
    $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($month_lim > 0)
{
    if ($year_lim > 0 && empty($day_lim))
        $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,$month_lim,false))."' AND '".$db->idate(dol_get_last_day($year_lim,$month_lim,false))."'";
    else if ($year_lim > 0 && ! empty($day_lim))
        $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_lim, $day_lim, $year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_lim, $day_lim, $year_lim))."'";
    else
        $sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$month_lim."'";
}
else if ($year_lim > 0)
{
    $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($year_lim,12,false))."'";
}
$sql.= $db->order($sortfield,$sortorder);


$result = $db->query($sql);
$num = $db->num_rows($result);

//Convert the Total Price to a number with (.) for thousands, and (,) for decimals.
$total = $total;

//Create a new PDF file
$pdf = new CommissionsPDF();
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 8);

if($object)
{
    // Print the name of vendor
    $pdf->Cell(80, 10, strtoupper($object->firstname.' '.$object->lastname), 0, 0, 'L');
}
else
{
    $pdf->Cell(80, 10, '', 0, 0, 'L');
}

// Print the date range
if($month && $year)
{
    // Get the date range
    $a_date = $year.'-'.$month.'-01';
    $first_date_of_month = date("d/m/y", strtotime($a_date));
    $last_date_of_month = date("t/m/y", strtotime($a_date));

    // Print the date range
    $pdf->Cell(30, 10, 'DEL: '.$first_date_of_month.' AL: '.$last_date_of_month, 0, 0, 'C');

}

$pdf->ln(10);

//Fields Name position
// $Y_Fields_Name_position = 50;
//Table position, under Fields Name
// $Y_Table_Position = 26;

//First create each Field Name
//Gray color filling each Field Name box
$pdf->SetFillColor(232,232,232);
//Bold Font for Field Name
$pdf->SetFont('Arial','B',6);
// $pdf->SetY($Y_Fields_Name_position);
// $pdf->SetX(20);
$pdf->Cell(30,6,'PAGO',1,0,'L',1);
$pdf->SetX(40);
$pdf->Cell(20,6,'FACTURA',1,0,'L',1);
$pdf->SetX(60);
$pdf->Cell(20,6,'CLIENTE',1,0,'L',1);
$pdf->SetX(80);
$pdf->Cell(20,6,'VENCIMIENTO',1,0,'L',1);
$pdf->SetX(100);
$pdf->Cell(20,6,'TOTAL',1,0,'R',1);
$pdf->SetX(120);
$pdf->Cell(20,6,'FECHA',1,0,'R',1);
$pdf->SetX(140);
$pdf->Cell(20,6,'ABONO',1,0,'R',1);
$pdf->SetX(160);
$pdf->Cell(20,6,'% COMISION',1,0,'C',1);
$pdf->SetX(180);
$pdf->Cell(20,6,'COMISION',1,0,'R',1);
$pdf->Ln();

//Initialize the 3 columns and the total
$column_code = "";
$column_name = "";
$column_price = "";
$total = 0;
$total_commission = 0;


$pdf->SetFillColor(255,255,255);
$pdf->SetFont('Arial','',6);

//For each row, add the field to the corresponding column
while($row = $db->fetch_object($result))
{
    $Y_Fields_Name_position += 6;

    // $pdf->SetY($Y_Fields_Name_position);
    // $pdf->SetX(20);
    $pdf->Cell(30,6,$row->rowid,1,0,'L',1);
    $pdf->SetX(40);
    $pdf->Cell(20,6,$row->facnumber,1,0,'L',1);
    $pdf->SetX(60);
    $pdf->Cell(20,6,$row->name,1,0,'L',1);
    $pdf->SetX(80);
    $pdf->Cell(20,6,dol_print_date($db->jdate($row->df),'day'),1,0,'L',1);
    $pdf->SetX(100);
    $pdf->Cell(20,6,price($row->total_ttc,0,$langs),1,0,'R',1);
    $pdf->SetX(120);
    $pdf->Cell(20,6,dol_print_date($db->jdate($row->datep),'day'),1,0,'L',1);
    $pdf->SetX(140);
    $pdf->Cell(20,6,price($row->amount,0,$langs),1,0,'R',1);

    $special_commission = $row->commission;
    if($special_commission > 0) {
        $total_commission += ($row->amount * ($special_commission)/ 100);
        $pdf->SetX(160);
        $pdf->Cell(20,6,number_format($special_commission,2),1,0,'C',1);
        $pdf->SetX(180);
        $pdf->Cell(20,6,price($row->amount * ($special_commission)/ 100),1,0,'R',1);
    }
    else {
        $total_commission += ($row->amount * ($object->array_options['options_commission'])/ 100);
        $pdf->SetX(160);
        $pdf->Cell(20,6,number_format($object->array_options['options_commission'],2),1,0,'C',1);
        $pdf->SetX(180);
        $pdf->Cell(20,6,price($row->amount * ($object->array_options['options_commission'])/ 100),1,0,'R',1);
    }

    // $code = $row["ref"];
    // $name = substr($row["label"],0,20);
    // $real_price = $row["price_ttc"];
    // $price_to_show = $row["price_ttc"];

    // $column_code = $column_code.$code."\n";
    // $column_name = $column_name.$name."\n";
    // $column_price = $column_price.$price_to_show."\n";

    // Sum (TOTAL)
    // $total += $real_price;

    $pdf->ln();
}

$pdf->SetFont('Arial','B',8);
$pdf->Cell(80, 10, 'TOTAL: $'.number_format($total_commission, 2, '.', ','), 0, 0, 'L');

//Now show the 3 columns
// $pdf->SetFont('Arial','',12);
// $pdf->SetY($Y_Table_Position);
// $pdf->SetX(45);
// $pdf->MultiCell(20,6,$column_code,1);
// $pdf->SetY($Y_Table_Position);
// $pdf->SetX(65);
// $pdf->MultiCell(100,6,$column_name,1);
// $pdf->SetY($Y_Table_Position);
// $pdf->SetX(135);
// $pdf->MultiCell(30,6,$columna_price,1,'R');
// $pdf->SetX(135);
// $pdf->MultiCell(30,6,'$ '.$total,1,'R');

//Create lines (boxes) for each ROW (Product)
//If you don't use the following code, you don't create the lines separating each row
$i = 0;
$pdf->SetY($Y_Table_Position);
while ($i < $number_of_products)
{
    $pdf->SetX(45);
    $pdf->MultiCell(120,6,'',1);
    $i = $i +1;
}

$pdf->Output();
die;
?>
