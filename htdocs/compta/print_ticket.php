<?php

/**
 * \file 	htdocs/compta/print_ticket.php
 * \ingroup facture
 * \brief 	Page to print the ticket of a ticket-invoice
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/price2letters.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';



date_default_timezone_set('America/Mexico_City');



define('CHAR_LIMIT', 41);


$facid = (GETPOST('facid', 'int'));
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
$telephone = '01 (449) 963 91 05';
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
    if($copy) {
      $lines_str .= '<span class="flex">*** COPIA ***</span>';
    } else {
       $lines_str .= '<span class="flex">*** ORIGINAL ***</span>';
    }
    
    if ($ticket_type === 'credit') {
       $lines_str .= '<span class="flex">'.$vendorInitials.'-2</span>';
    }
    else {
       $lines_str .= '<span class="flex">'.$vendorInitials.'-1</span>';
    }
    $lines_str .= '</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex center">DESCRIPTION</span>';
    $lines_str .= '<span class="flex center">IMPORTE</span>';
    $lines_str .= '</p>';

    $lines_str .= separator();
    foreach ($object->lines as $line) {
        $temp_prod->fetch($line->fk_product);
        if($temp_prod->array_options['options_umed'] == 'KGM') {
          $total_weight += $line->qty;
        }
        if (!in_array($line->tva_tx, $tva)) {
            $tva[] = intval($line->tva_tx);
        }
        $lines_str .= '<p>'.fitString($line->libelle).(getAsterisks($line->tva_tx, $tva)).'</p>';
        $lines_str .= '<p class="d-flex">';
        $lines_str .= '<span class="flex right">'.$line->qty.'</span>';
        $lines_str .= '<span class="flex right">$'.price($line->total_ttc / $line->qty).'</span>';
        $lines_str .= '<span class="flex right">$'.price($line->total_ttc).'</span>';
        $lines_str .= '</p>';
    }
    $lines_str .= separator();
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex-2 auto right">TOTAL</span>';
    $lines_str .= '<span class="flex right">$'.price($object->total_ttc).'</span>';
    $lines_str .= '</p>';
    $lines_str .= '<br>';
    $lines_str .= '<p>Kgs.: '.number_format((float)$total_weight, 2, '.', '').'</p>';
    $lines_str .= '<p>Empleado: '.strtoupper($user->firstname).'</p>';
    $lines_str .= '<br>';
    $lines_str .= '<p>('.strtoupper(price2letters(price($object->total_ttc))).')</p>';
    $lines_str .= '<br>';
    $count = 0;
    foreach ($tva as $t) {
        $lines_str .= '<p class="center">';
        $lines_str .= (concatChart('*', ++$count)).' = Producto con I.V.A. tasa '.(intval($t)).'%';
        $lines_str .= '</p>';
    }
    $lines_str .= '<p class="center">Recibi mercancia.</p>';
    $lines_str .= '<p class="center"> ________________________________</p>';

    if ($ticket_type === 'credit') {
        $lines_str .= '<p class="center justify">Este documento ampara la cantidad que suman los cargos por el crédito que me(nos) fue otorgado y que corresponde al valor de las mercancías detalladas en la orden de venta con número de folio ' .$folio. ', mismas que recibí(recibimos) a mi(nuestra) entera satisfacción, por lo que la suscripción de este documento hace prueba de la recepción de las mercancías y del adeudo de su valor, mismo que deberá pagarse el día '.$payday_limit.'. La falta de pago oportuno del valor de las mercancías generará un interés moratorio del 3.00% mensual.</p>';
        $lines_str .= '<p class="center">'.$societe->name.'</p>';
        $lines_str .= '<p class="center">'.$societe->address.'</p>';
        $lines_str .= '<p class="center">'.$societe->town.'</p>';
        $lines_str .= '<p class="center">'.$societe->phone.'</p>';
        $lines_str .= '<p class="center"> ________________________________</p>';
        $lines_str .= '<p class="center justify">Por este Pagaré, me(nos) obligo(obligamos) a pagar incondicionalmente a la orden de TECNOLOGÍA Y APLICACIONES ALIMENTARIAS, S.A. DE C.V., en la ciudad de Aguascalientes, Ags., el día '.$payday_limit.', la cantidad de $'.price($object->total_ttc).'('.strtoupper(price2letters(price($object->total_ttc))).'). Este pagaré es el ___ de una serie de ____ pagarés. La falta de pago oportuno de este pagaré generará un interés moratorio del 3.00% mensual. Aguascalientes, Ags., a '.date('d').' de '.$monthName.' de '.date('Y').'</p>';
        $lines_str .= '<p class="center">'.$societe->name.'</p>';
        $lines_str .= '<p class="center">'.$societe->address.'</p>';
        $lines_str .= '<p class="center">'.$societe->town.'</p>';
        $lines_str .= '<p class="center">'.$societe->phone.'</p>';
        $lines_str .= '<p class="center"> ________________________________</p>';
        $lines_str .= '<p class="center">Suscriptor</p>';
        $lines_str .= '<p class="center">>>> CUIDE SU CREDITO, PAGUE A TIEMPO <<<</p>';

    }
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