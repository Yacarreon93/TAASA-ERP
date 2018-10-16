<?php

require_once('tfpdf/tfpdf.php');

date_default_timezone_set('America/Mexico_City');
setlocale(LC_TIME, 'es_ES');

class ReportPDF extends tFPDF
{
    // Recibe la función cargadora de datos
    function __construct() {
        parent::__construct();
        // Ajustar la anchura de la página
        $this->maxWidth = $this->w - 20;
        $this->rowHeight = 7;
    }

    // Asignar el alto de las columnas
    function setRowHeight($height) {
        $this->rowHeight = $height;
    }

    function Header() {
        $this->SetFontSize(14);
        $this->Cell(100, 20, utf8_decode($this->title));
        $this->Cell(90, 20, strftime('%A %d/%b/%G'), 0, 0, 'R');
        $this->Ln();
    }

    function Footer() {
        $this->SetFontSize(12);
        $this->SetY($this->h - $this->rowHeight);
        $this->Cell(0, $this->setRowHeight, "Parte $this->page", 0, 0, 'C');
    }

    // Crea el encabezado con una anchura definida
    function createHeader($header) {
        $this->SetFontSize(12);
        foreach($header as $col) {
            $this->Cell(
                $col['x'],
                $col['y'],
                utf8_decode($col['text']),
                1
            );
        }
        $this->Ln();
    }

    // Crea el encabezado con una anchura dinamica
    function createDynamicHeader($header, $size) {
        $this->SetFontSize($size);
        foreach($header as $col) {
            $this->Cell(
                $this->maxWidth / count($header),
                $this->rowHeight,
                utf8_decode($col),
                1
            );
        }
        $this->Ln();
    }

    // Crea las columnas con una anchura dinámica
    function createDynamicRows($data, $size) {
        $this->SetFontSize($size);
        foreach($data as $row) {
            $i = 0;
            foreach ($row as $key => $value) {
                $this->Cell(
                    $this->maxWidth / count($data[0]),
                    $this->rowHeight,
                    utf8_decode($value),
                    1,
                    0,
                    'L'
                );
                $i++;
            }
            $this->Ln();
        }
        $this->Ln();
    }

    function showTotal($data, $total_debe, $total_haber, $total_general) {
        $this->SetX(($this->maxWidth / count($data[0])) * (count($data[0]) - 3) + 10);
        $this->Cell(
            $this->maxWidth / count($data[0]),
            $this->rowHeight,
            utf8_decode('Totales'),
            1,
            0,
            'L'
        );
        $this->Cell(
            $this->maxWidth / count($data[0]),
            $this->rowHeight,
            utf8_decode($total_debe),
            1,
            0,
            'L'
        );
        $this->Cell(
            $this->maxWidth / count($data[0]),
            $this->rowHeight,
            utf8_decode($total_haber),
            1,
            0,
            'L'
        );
        $this->Ln();
        $this->SetX(($this->maxWidth / count($data[0])) * (count($data[0]) - 2) + 10);
        $this->Cell(
            $this->maxWidth / count($data[0]),
            $this->rowHeight,
            utf8_decode("Total"),
            1,
            0,
            'L'
        );
        $this->Cell(
            $this->maxWidth / count($data[0]),
            $this->rowHeight,
            utf8_decode($total_general),
            1,
            0,
            'L'
        );
    }

    // Tabla simple
    function basicTable($header, $data)
    {
        // Cabecera
        foreach($header as $col)
            $this->Cell(40,7,$col,1);
        $this->Ln();
        // Datos
        foreach($data as $row)
        {
            foreach($row as $col)
                $this->Cell(40,6,$col,1);
            $this->Ln();
        }
    }

    // Una tabla más completa
    function ImprovedTable($header, $data)
    {
        // Anchuras de las columnas
        $w = array(40, 35, 45, 40);
        // Cabeceras
        for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,$header[$i],1,0,'C');
        $this->Ln();
        // Datos
        foreach($data as $row)
        {
            $this->Cell($w[0],6,$row[0],'LR');
            $this->Cell($w[1],6,$row[1],'LR');
            $this->Cell($w[2],6,number_format($row[2]),'LR',0,'R');
            $this->Cell($w[3],6,number_format($row[3]),'LR',0,'R');
            $this->Ln();
        }
        // Línea de cierre
        $this->Cell(array_sum($w),0,'','T');
    }

    // Tabla coloreada
    function FancyTable($header, $data)
    {
        // Colores, ancho de l�nea y fuente en negrita
        $this->SetFillColor(255,0,0);
        $this->SetTextColor(255);
        $this->SetDrawColor(128,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        // Cabecera
        $w = array(40, 35, 45, 40);
        for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
        $this->Ln();
        // Restauraci�n de colores y fuentes
        $this->SetFillColor(224,235,255);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Datos
        $fill = false;
        foreach($data as $row)
        {
            $this->Cell($w[0],6,$row[0],'LR',0,'L',$fill);
            $this->Cell($w[1],6,$row[1],'LR',0,'L',$fill);
            $this->Cell($w[2],6,number_format($row[2]),'LR',0,'R',$fill);
            $this->Cell($w[3],6,number_format($row[3]),'LR',0,'R',$fill);
            $this->Ln();
            $fill = !$fill;
        }
        // L�nea de cierre
        $this->Cell(array_sum($w),0,'','T');
    }
}