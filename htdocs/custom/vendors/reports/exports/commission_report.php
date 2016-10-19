<?php
require_once '../../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fpdf/fpdf.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

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

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' f.rowid as facid, f.facnumber, f.ref_client, f.type, f.note_private, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc,';
$sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
$sql.= ' f.paye as paye, f.fk_statut,';
$sql.= ' s.nom as name, s.rowid as socid, s.code_client, s.client, se.commission as com';
if (! $sall) $sql.= ', SUM(pf.amount) as am';   // To be able to sort on status
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= ' JOIN '.MAIN_DB_PREFIX.'societe_extrafields AS se ON se.fk_object = s.rowid';
$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
else $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' JOIN '.MAIN_DB_PREFIX.'facture_extrafields as ef ON ef.fk_object = f.rowid';
$sql.= ' WHERE f.fk_soc = s.rowid';
$sql.= ' AND ef.vendor = '.$id;
$sql.= ' AND f.fk_statut = 2';
$sql.= " AND f.entity = ".$conf->entity;
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if ($userid)
{
    if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
    else $sql.=' AND f.fk_user_author = '.$userid;
}
if ($filtre)
{
    $aFilter = explode(',', $filtre);
    foreach ($aFilter as $filter)
    {
        $filt = explode(':', $filter);
        $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
    }
}
if ($search_ref) $sql .= natural_search('f.facnumber', $search_ref);
if ($search_refcustomer) $sql .= natural_search('f.ref_client', $search_refcustomer);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_montant_ht != '') $sql.= natural_search('f.total', $search_montant_ht, 1);
if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
if ($search_status != '' && $search_status >= 0) $sql.= " AND f.fk_statut = ".$db->escape($search_status);
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
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
}
if (! $sall)
{
    $sql.= ' GROUP BY f.rowid, f.facnumber, ref_client, f.type, f.note_private, f.increment, f.total, f.tva, f.total_ttc,';
    $sql.= ' f.datef, f.date_lim_reglement,';
    $sql.= ' f.paye, f.fk_statut,';
    $sql.= ' s.nom, s.rowid, s.code_client, s.client';
}
else
{
    $sql .= natural_search(array('s.nom', 'f.facnumber', 'f.note_public', 'fd.description'), $sall);
}
$sql.= $db->order($sortfield,$sortorder);

$result = $db->query($sql);
$num = $db->num_rows($result);

//Convert the Total Price to a number with (.) for thousands, and (,) for decimals.
$total = $total;

//Create a new PDF file
$pdf=new FPDF();
$pdf->AddPage();

//Fields Name position
$Y_Fields_Name_position = 20;
//Table position, under Fields Name
$Y_Table_Position = 26;

//First create each Field Name
//Gray color filling each Field Name box
$pdf->SetFillColor(232,232,232);
//Bold Font for Field Name
$pdf->SetFont('Arial','B',6);
$pdf->SetY($Y_Fields_Name_position);
$pdf->SetX(20);
$pdf->Cell(20,6,'FACTURA',1,0,'L',1);
$pdf->SetX(40);
$pdf->Cell(20,6,'FECHA',1,0,'L',1);
$pdf->SetX(60);
$pdf->Cell(20,6,'VENCIMIENTO',1,0,'L',1);
$pdf->SetX(80);
$pdf->Cell(20,6,'CLIENTE',1,0,'L',1);
$pdf->SetX(100);
$pdf->Cell(20,6,'IMPORTE BASE',1,0,'R',1);
$pdf->SetX(120);
$pdf->Cell(20,6,'IMPORTE IVA',1,0,'R',1);
$pdf->SetX(140);
$pdf->Cell(20,6,'IMPORTE TOTAL',1,0,'R',1);
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

$pdf->SetFillColor(255,255,255);
//Bold Font for Field Name
$pdf->SetFont('Arial','',6);

//For each row, add the field to the corresponding column
while($row = $db->fetch_object($result))
{
    $Y_Fields_Name_position += 6;

    $pdf->SetY($Y_Fields_Name_position);
    $pdf->SetX(20);
    $pdf->Cell(20,6,$row->facnumber,1,0,'L',1);
    $pdf->SetX(40);
    $pdf->Cell(20,6,dol_print_date($db->jdate($row->df),'day'),1,0,'L',1);
    $pdf->SetX(60);
    $pdf->Cell(20,6,dol_print_date($datelimit,'day'),1,0,'L',1);
    $pdf->SetX(80);
    $pdf->Cell(20,6,$row->name,1,0,'L',1);
    $pdf->SetX(100);
    $pdf->Cell(20,6,price($row->total_ht,0,$langs),1,0,'R',1);
    $pdf->SetX(120);
    $pdf->Cell(20,6,price($row->total_tva,0,$langs),1,0,'R',1);
    $pdf->SetX(140);
    $pdf->Cell(20,6,price($row->total_ttc,0,$langs),1,0,'R',1);

    $special_commission = $row->com;
    if($special_commission > 0) {
        $total_commission += ($row->total_ttc * ($special_commission)/ 100);
        $pdf->SetX(160);
        $pdf->Cell(20,6,number_format($special_commission,2),1,0,'C',1);
        $pdf->SetX(180);
        $pdf->Cell(20,6,($row->total_ttc * ($special_commission)/ 100),1,0,'R',1);
    }
    else {
        $total_commission += ($row->total_ttc * ($object->array_options['options_commission'])/ 100);
        $pdf->SetX(160);
        $pdf->Cell(20,6,number_format($object->array_options['options_commission'],2),1,0,'C',1);
        $pdf->SetX(180);
        $pdf->Cell(20,6,($row->total_ttc * ($object->array_options['options_commission'])/ 100),1,0,'R',1);
    }

    

    // $code = $row["ref"];
    // $name = substr($row["label"],0,20);
    // $real_price = $row["price_ttc"];
    // $price_to_show = $row["price_ttc"];

    // $column_code = $column_code.$code."\n";
    // $column_name = $column_name.$name."\n";
    // $column_price = $column_price.$price_to_show."\n";

    //Sum all the Prices (TOTAL)
    // $total = $total+$real_price;
}

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