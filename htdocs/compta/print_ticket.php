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

// Demo data
$telephone = '01 (449) 9779901';
$rfc = 'TAA121024V48';
$folio = $object->ref;
$today = date('d/m/Y');
$time = date('h:i:s a');
$total_weight = 0;
$cashier = getInitials($user);
$tva = [];

if ($ret) {
    $lines_str = '';
    $ticket_type = $object->cond_reglement_id == 1 ? 'cash' : 'credit';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex auto">TEL. '.$telephone.'</span>';
    $lines_str .= '<span class="flex auto">RFC '.strtoupper($rfc).'</span>';
    $lines_str .= '</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex auto">FECHA: '.$today.'</span>';
    $lines_str .= '<span class="flex auto">No. FOLIO: '.$folio.'</span>';
    $lines_str .= '</p>';
    $lines_str .= '<p>HORA: '.$time.'</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex">*** ORIGINAL ***</span>';
    $lines_str .= '<span class="flex">MA-1</span>';
    $lines_str .= '</p>';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex center">DESCRIPTION</span>';
    $lines_str .= '<span class="flex center">IMPORTE</span>';
    $lines_str .= '</p>';
    if ($ticket_type === 'cash') {
        $lines_str .= separator();
        foreach ($object->lines as $line) {
            $temp_prod->fetch($line->fk_product);      
            $total_weight += $temp_prod->weight ? $temp_prod->weight : 0;
            if (!in_array($line->tva_tx, $tva)) {
                $tva[] = $line->tva_tx;
            }
            $lines_str .= '<p>'.fitString($line->libelle).(getAsterisks($line->tva_tx, $tva)).'</p>';
            $lines_str .= '<p class="d-flex">';
            $lines_str .= '<span class="flex right">'.number_format((float)$line->qty, 2, '.', '').'</span>';
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
        $lines_str .= '<p>Empleado: '.strtoupper($cashier).'</p>';
        $lines_str .= '<br>';
        $lines_str .= '<p>('.strtoupper(price2letters(price($object->total_ttc))).')</p>';
        $lines_str .= '<br>';
        $count = 0;
        foreach ($tva as $t) {
            $lines_str .= '<p class="center">'.(concatChart('*', ++$count)).' = Producto con I.V.A. tasa '.$t.'%</p>';            
        }
        $lines_str .= '<p class="center">>>> CUIDE SU CREDITO, PAGUE A TIEMPO <<<</p>';
    } else if ($ticket_type === 'credit') {
      $lines_str = '';
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
        font-size: 11px;
      }
      p {
        margin: 5px;
      }
      .container {
        max-width: 260px;
        padding: 15px 5px;
        background: lightgray;
      }
      .center {
        text-align: center;
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
  <body>
    <div class="container">
      <p class="center">TECNOLOGIA Y APLICACIONES ALIMENTARIAS</p>
      <p class="center">S.A. DE C.V.</p>
      <p class="center">AV. ADOLFO RUIZ CORTINEZ 212 - B</p>
      <p class="center">COL FRANCISCO VILLA AGUASCALIENTES, AGS</p>
      <?=$lines_str;?>
    </div>
  </body>
</html>