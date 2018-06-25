<?php

require_once('../../../main.inc.php');
require_once('./report.class.php');

$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'product LIMIT 10';
$result = $db->query($sql);

if (!$result) { 
    echo 'Error: '.$db->lasterror;
    die;
}

// Crear una instancia del pdf con una función para generar los datos
$pdf = new ReportPDF($db, $result, function ($db,$result) {
    $i = 0;
    $num = $db->num_rows($result);
    $data = array();
    while ($i < $num)
    {
        $row = $db->fetch_object($result);
        $data[] = array(
            date    => '00/00/0000',
            type    => 'type',
            client  => 'qwe',
            amount  => '123',
            description => 'description',
        );
        $i++;            
    }
    return $data;
});

// Títulos de las columnas
$header = array(
    array(
        x => 40,
        y => 7,
        text => 'Fecha',
    ), 
    array(
        x => 40,
        y => 7,
        text => 'Tipo',
    ), 
    array(
        x => 40,
        y => 7,
        text => 'Descripción',
    ), 
    array(
        x => 40,
        y => 7,
        text => 'Tercero',
    ), 
    array(
        x => 40,
        y => 7,
        text => 'Saldo',
    ),
);
    
// Carga de datos
$pdf->SetFont('Arial', '', 14);
$pdf->AddPage();
$pdf->createHeader($header);
// $pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);
$pdf->Output();
