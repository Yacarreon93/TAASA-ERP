<?php
require_once '../../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fpdf/fpdf.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

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

$sql = "SELECT DISTINCT p.rowid, p.datep as dp, p.amount,"; // DISTINCT is to avoid duplicate when there is a link to sales representatives
$sql.= " p.statut, p.num_paiement,";
$sql.= " c.code as paiement_code,";
$sql.= " ba.rowid as bid, ba.label,";
$sql.= " s.rowid as socid, s.nom as name";
$sql.= " FROM (".MAIN_DB_PREFIX."c_paiement as c, ".MAIN_DB_PREFIX."paiement as p)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
$sql.= " JOIN ".MAIN_DB_PREFIX."facture_extrafields as ef ON ef.fk_object = f.rowid"; // added extrafields to locate vendor
if (!$user->rights->societe->client->voir && !$socid)
{
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
}
$sql.= " WHERE p.fk_paiement = c.id";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= ' AND ef.vendor = '.$id; // searching a specific vendor
if (! $user->rights->societe->client->voir && ! $socid)
{
    $sql.= " AND sc.fk_user = " .$user->id;
}
if ($socid > 0) $sql.= " AND f.fk_soc = ".$socid;
if ($userid)
{
    if ($userid == -1) $sql.= " AND f.fk_user_author IS NULL";
    else  $sql.= " AND f.fk_user_author = ".$userid;
}
// Search criteria
if ($month > 0)
{
    if ($year > 0 && empty($day))
    $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    $sql.= " AND p.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    $sql.= " AND date_format(p.datep, '%m') = '".$month."'";
}
else if ($year > 0)
{
    $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($search_ref > 0)            $sql .=" AND p.rowid=".$search_ref;
if ($search_account > 0)        $sql .=" AND b.fk_account=".$search_account;
if ($search_paymenttype != "")  $sql .=" AND c.code='".$db->escape($search_paymenttype)."'";
if ($search_amount)             $sql .=" AND p.amount='".$db->escape(price2num($search_amount))."'";
if ($search_company)            $sql .= natural_search('s.nom', $search_company);
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= $db->order($sortfield,$sortorder);

$result = $db->query($sql);
$num = $db->num_rows($result);

//Initialize the 3 columns and the total
$column_code = "";
$column_name = "";
$column_price = "";
$total = 0;

//For each row, add the field to the corresponding column
while($row = $db->fetch_array($result))
{
    $code = $row["ref"];
    $name = substr($row["label"],0,20);
    $real_price = $row["price_ttc"];
    $price_to_show = $row["price_ttc"];

    $column_code = $column_code.$code."\n";
    $column_name = $column_name.$name."\n";
    $column_price = $column_price.$price_to_show."\n";

    //Sum all the Prices (TOTAL)
    $total = $total+$real_price;
}

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
$pdf->SetFont('Arial','B',12);
$pdf->SetY($Y_Fields_Name_position);
$pdf->SetX(45);
$pdf->Cell(20,6,'CODE',1,0,'L',1);
$pdf->SetX(65);
$pdf->Cell(100,6,'NAME',1,0,'L',1);
$pdf->SetX(135);
$pdf->Cell(30,6,'PRICE',1,0,'R',1);
$pdf->Ln();

//Now show the 3 columns
$pdf->SetFont('Arial','',12);
$pdf->SetY($Y_Table_Position);
$pdf->SetX(45);
$pdf->MultiCell(20,6,$column_code,1);
$pdf->SetY($Y_Table_Position);
$pdf->SetX(65);
$pdf->MultiCell(100,6,$column_name,1);
$pdf->SetY($Y_Table_Position);
$pdf->SetX(135);
$pdf->MultiCell(30,6,$columna_price,1,'R');
$pdf->SetX(135);
$pdf->MultiCell(30,6,'$ '.$total,1,'R');

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