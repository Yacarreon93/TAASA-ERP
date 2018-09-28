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
    while ($i < 100)
    {
        // $row = $db->fetch_object($result);
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
    'Fecha',
    'Tipo',
    'Descripción',
    'Tercero',
    'Saldo',
);

$report_title = 'Reporte corte de caja';
    
// Carga de datos
$pdf->SetFont('Arial', '', 12);

// 7 es la altura por default
// $pdf->setRowHeight(7);
$pdf->SetTitle($report_title);
$pdf->setTitle($report_title);
$pdf->AddPage();
$pdf->createDynamicHeader($header);
$pdf->createDynamicRows();

// $pdf->BasicTable($header,$data);
// $pdf->AddPage();
// $pdf->ImprovedTable($header,$data);
// $pdf->AddPage();
// $pdf->FancyTable($header,$data);

$pdf->Output();
