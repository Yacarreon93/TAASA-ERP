<?php

require_once('../../../main.inc.php');
require_once('./report.class.php');



$langs->load("bills");

$account_id=GETPOST('account_id', 'int');

if (!$account_id) {
    echo 'Error: account_id requerido';
    die;
}

$sqlAccount = "SELECT
    b.rowid,
    b.dateo AS do,
    b.datev AS dv,
    b.amount,
    b.label,
    b.rappro,
    b.num_releve,
    b.num_chq,
    b.fk_type,
    b.fk_bordereau,
    ba.rowid AS bankid,
    ba.ref AS bankref,
    ba.label AS banklabel,
    s.rowid AS socid,
    s.nom AS thirdparty";
$sqlAccount.= " FROM 
    ".MAIN_DB_PREFIX."bank_account AS ba,
    ".MAIN_DB_PREFIX."bank AS b
    LEFT JOIN ".MAIN_DB_PREFIX."bank_url AS bu1 ON bu1.fk_bank = b.rowid 
	AND bu1.type = 'company'
	LEFT JOIN ".MAIN_DB_PREFIX."societe AS s ON bu1.url_id = s.rowid
	LEFT JOIN ".MAIN_DB_PREFIX."bank_url AS bu2 ON bu2.fk_bank = b.rowid 
	AND bu2.type = 'payment_vat'
	LEFT JOIN ".MAIN_DB_PREFIX."tva AS t ON bu2.url_id = t.rowid
	LEFT JOIN ".MAIN_DB_PREFIX."bank_url AS bu3 ON bu3.fk_bank = b.rowid 
	AND bu3.type = 'payment_salary'
	LEFT JOIN ".MAIN_DB_PREFIX."payment_salary AS sal ON bu3.url_id = sal.rowid";
$sqlAccount.= " WHERE 
    b.fk_account = ".$account_id."
    AND b.fk_account = ba.rowid";
// @Y: status
// 0 -> No han entrado a un corte de caja
// 1 -> Pertenecen al corte de caja actual
// 2 -> Pertenecen al corte de caja anterior
$sqlAccount.= " AND b.status = 1";
$sqlAccount.= " ORDER BY
	b.datev ASC,
    b.datec ASC";

$resultAccount = $db->query($sqlAccount);

if (!$resultAccount) { 
    echo 'Error: '.$db->lasterror;
    die;
}

// @Y: Crear la matriz de datos
$i = 0;
$num = $db->num_rows($resultAccount);
$data = array();
while ($i < $num)
{
    $row = $db->fetch_object($resultAccount);
    $type = $langs->trans('PaymentTypeShort'.$row->fk_type) != 'PaymentTypeShort'.$row->fk_type ? $langs->trans('PaymentTypeShort'.$row->fk_type): $row->fk_type;
    $description = dol_trunc($row->label, 23);
    $debe = 0;
    $haber = 0;
    if ($row->amount < 0)
    {
        $debe = '$'.price($row->amount * -1);
    }
    else
    {
        $haber = '$'.price($row->amount);
    }
    $data[] = array(
        dateo   => dol_print_date($db->jdate($row->do),"day"),
        datev   => dol_print_date($db->jdate($row->dv),"day"),
        type    => $type,
        description => $description,
        thirdparty  => $row->thirdparty,
        debe    => $debe,
        haber   => $haber,
    );
    $i++;            
}

$pdf = new ReportPDF();

$report_title = 'Reporte corte de caja';

// Títulos de las columnas
$header = array(
    'F. Operación',
    'F. Valor',
    'Tipo',
    'Descripción',
    'Tercero',
    'Debe',
    'Haber',
);

$pdf->SetTitle($report_title);
$pdf->AddPage();
$pdf->createDynamicHeader($header, 10);
$pdf->createDynamicRows($data, 7);
$pdf->Output();
