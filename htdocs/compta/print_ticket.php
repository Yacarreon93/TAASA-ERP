<?php

/**
 * \file 	htdocs/compta/print_ticket.php
 * \ingroup facture
 * \brief 	Page to print the ticket of a ticket-invoice
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

$facid = (GETPOST('facid', 'int'));

$object = new Facture($db);

define('MAX_CHAR', 33);

function separator() {
    $str = '<p>';
    for ($i = 0; $i < MAX_CHAR; $i++) {
        $str .= '-';
    }
    $str .= '</p>';
    return $str;
}

function fitString($str) {  
    $new_str = trim($str);  
    if (strlen($new_str) > MAX_CHAR) {
        $new_str .= substr($new_str, 0 , MAX_CHAR - 3);
        $new_str .= '...';        
        return $new_str;
    }
    return substr($new_str, 0, MAX_CHAR);
}

// Load object
if ($facid > 0 || ! empty($ref)) {
	$ret = $object->fetch($facid, $ref);
}

if ($ret) {
    $lines_str = '';    
    $ticket_type = $object->cond_reglement_id == 1 ? 'cash' : 'credit';
    $lines_str .= '<p class="d-flex">';
    $lines_str .= '<span class="flex">DESCRIPTION</span>';
    $lines_str .= '<span class="flex">IMPORTE</span>';
    $lines_str .= '</p>';
    if ($ticket_type === 'cash') {
        $lines_str .= separator();
        foreach ($object->lines as $line) {
            $lines_str .= '<p>'.fitString($line->libelle).'</p>';
            $lines_str .= '<p class="d-flex">';
            $lines_str .= '<span class="flex right">'.$line->qty.'</span>';
            $lines_str .= '<span class="flex right">$'.price($line->total_ttc / $line->qty).'</span>';
            $lines_str .= '<span class="flex right">$'.price($line->total_ttc).'</span>';
            $lines_str .= '</p>';
        }
        $lines_str .= separator();
    }
} else {
    echo "ERROR: Id Factura requerido";
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
      .container {
        max-width: 260px;
        padding: 20px 10px;
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
        flex: 1 0 auto;
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