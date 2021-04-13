<?php

/**
 * \file 	htdocs/compta/print_ticket.php
 * \ingroup facture
 * \brief 	Page to print the ticket of a ticket-invoice
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/price2letters.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';


date_default_timezone_set('America/Mexico_City');

define('CHAR_LIMIT', 41);

$paiement_id = (GETPOST('id', 'int'));

$sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.total_ttc, f.paye, f.fk_statut, pf.amount, s.nom as name, s.rowid as socid';
$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
$sql.= ' WHERE pf.fk_facture = f.rowid';
$sql.= ' AND f.fk_soc = s.rowid';
$sql.= ' AND f.entity = 1';
$sql.= ' AND pf.fk_paiement = '.$paiement_id;
$resql=$db->query($sql);
$totalPagado = 0;
if ($resql)
{
  $num = $db->num_rows($resql);
  if ($num > 0)
  {
    while ($row =  $db->fetch_object($result))
    {
      $totalPagado += $row->amount;
      $resto = ($row->total_ttc - $row->amount);
      $paymentData[] = array(
            facnumber=>$row->facnumber,
            saldo=> $row->total_ttc,
            abono=>$row->amount,
            resto=>$resto
        );
    }
    $resql=$db->query($sql);
    $objp = $db->fetch_object($resql);
    $clientName=$objp->name;
    $facid = $objp->facid;
    $invoice=new Facture($db);
    $invoice->fetch($facid);
    $paiement = $invoice->getSommePaiement();
    $creditnotes=$invoice->getSumCreditNotesUsed();
    $deposits=$invoice->getSumDepositsUsed();
    $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
    $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');
  }
}

$copy = (GETPOST('copy', 'int'));

$object = new Facture($db);
$temp_prod = new Product($db);

function separator() {
    $str = '<p>';
    for ($i = 0; $i < CHAR_LIMIT; $i++) {
        $str .= '-';
    }
    $str .= '</p>';
    return $str;
}

function fitString($str) { 
    $new_str = trim($str);  
    if (strlen($new_str) > CHAR_LIMIT - 6) {
        $new_str .= substr($new_str, 0 , CHAR_LIMIT - 3);
        $new_str .= '...';        
        return $new_str;
    }
    return substr($new_str, 0, CHAR_LIMIT);
}

function getInitials($user) {
    $initials = '';
    if ($user->firstname) {
        $initials = substr($user->firstname, 0, 2);
    } else {
        $initials = substr($user->lastname, 0, 2);
    }
    return strtoupper($initials);
}

function getAsterisks($tva, $arr) {
    $count = 0;
    $asterisks = '';
    if ($tva) {
        foreach ($arr as $t) {
            $count++;
            if ($tva == $t) break;
        }
    }
    for ($i = 0; $i < $count; $i++) {
        $asterisks .= '*';
    }
    return $asterisks;
}

function concatChart($c, $numTimes) {
    $str = '';
    for ($i = 0; $i < $numTimes; $i++) {
        $str .= $c;
    }
    return $str;
}

// Load object
if ($facid > 0 || ! empty($ref)) {
	$ret = $object->fetch($facid, $ref);
}

//Load client
$socid = $object->socid;
if($socid > 0) {
  $societe = new Societe($db);
  $societe->fetch($socid);
}

//Load vendor agent
$sql = "SELECT firstname";
$sql .= " FROM ". MAIN_DB_PREFIX ."user";
$sql .= " WHERE rowid = (";
$sql .= " SELECT vendor";
$sql .= " FROM ". MAIN_DB_PREFIX ."facture_extrafields";
$sql .= " WHERE fk_object = " . $facid . ")";
$resql = $db->query($sql);
if ($resql)
{
  $vendor = $db->fetch_object($resql);
}
$vendorInitials = getInitials($vendor);

// Demo data
$telephone = '(449) 551 10 21';
$rfc = 'TAA121024V48';
$folio = $object->ref;
$today = date('d/m/Y');
$time = date('h:i:s a');
$payday_limit = date('d/m/Y', $object->date_lim_reglement);
setlocale(LC_TIME, 'spanish');  
$monthName=strftime("%B",mktime(0, 0, 0, date('m'), date('d'), date('Y'))); 
$total_weight = 0;
$tva = array();

if ($ret) {
    $lines_str = '';
    $ticket_type = $object->cond_reglement_id == 1 ? 'cash' : 'credit';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex auto">TEL. '.$telephone.'</span>';
    $lines_str .= '<span class="flex auto">RFC '.strtoupper($rfc).'</span>';
    $lines_str .= '</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex auto">FECHA: '.$today.'</span>';
    $lines_str .= '</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex auto">No. FOLIO: '.$folio.'</span>';
    $lines_str .= '</p>';
    $lines_str .= '<p>HORA: '.$time.'</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '</p>';
    $lines_str .= separator();
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex center">Recibi la cantidad de $'.price($totalPagado).'</span>';
    $lines_str .= '</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex center">del cliente</span>';
    $lines_str .= '</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex center">'.$clientName.'</span>';
    $lines_str .= '</p>';
    $lines_str .= '<p>Pago de la(s) siguiente(s) factura(s)</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex center">FACTURA</span>';
    $lines_str .= '<span class="flex center">SALDO</span>';
    $lines_str .= '<span class="flex center">ABONO</span>';
    $lines_str .= '<span class="flex center">RESTO</span>';
    $lines_str .= '</p>';
    $lines_str .= separator();
    for($j = 0; $j < $num; $j++) 
    {
      $lines_str .= '<p class="d-flex">';
      $lines_str .= '<span class="flex center">'.$paymentData[$j]['facnumber'].'</span>';
      $lines_str .= '<span class="flex center">$'.price($paymentData[$j]['saldo']).'</span>';
      $lines_str .= '<span class="flex center">$'.price($paymentData[$j]['abono']).'</span>';
      $lines_str .= '<span class="flex center">$'.price($paymentData[$j]['resto']).'</span>';
      $lines_str .= '</p>';
    }
    $lines_str .= separator();
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex center">TOTAL PAGADO</span>';
    $lines_str .= '<span class="flex center"></span>';
    $lines_str .= '<span class="flex center"></span>';
    $lines_str .= '<span class="flex center">$'.price($totalPagado).'</span>';
    $lines_str .= '</p>';
} else {
    echo "ERROR: Id de Factura requerido";
}

// View

?>

<html>
  <head>
    <meta charset="UTF-8">
    <title>TAASA Ticket</title>
    <style>
      body {        
        font-family: monospace;
        margin: 0;
        padding: 0;
      }
     p {
        width: 100%;
        margin: 0;
        font-size: 12px;
        line-height: 16px;
      }
      .container {
        max-width: 260px;
        padding: 20px 10px;
      }
      .center {
        text-align: center;
      }
      .justify {
        text-align: justify;
      }
      .right {
        text-align: right;
      }
      .d-flex {
        display: flex;
      }
      .flex {
        flex: 1 0 0;
      }
      .flex-2 {
        flex: 2 0 0;
      }
      .flex-3 {
        flex: 3 0 0;
      }
      .auto {
        flex-basis: auto !important;
      }
    </style>  
  </head>
  <body onload="window.print();">
    <div class="container">
      <p class="center">TECNOLOGIA Y APLICACIONES ALIMENTARIAS</p>
      <p class="center">S.A. DE C.V.</p>
      <p class="center">EMILIANO ZAPATA 824-B</p>
      <p class="center">COL EL CALVARIO AGUASCALIENTES, AGS</p>
      <?=$lines_str;?>
    </div>
  </body>
</html>