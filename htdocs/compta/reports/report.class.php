<?php

require_once('tfpdf/tfpdf.php');

date_default_timezone_set('America/Mexico_City');
setlocale(LC_TIME, 'es_ES');

/*
 strftime('%A %d/%b/%Y')
*/
function getFullStrCurrentDate() {
    return utf8_decode(strtr('$D $d/$m/$Y', array(
        '$D' => getCurrentDayStr(),
        '$d' => strftime('%d'),
        '$m' => getCurrentMonthAbbrStr(),
        '$Y' => strftime('%Y'),
    )));
}

function getHour() {
    return date("h:i:s a");
}

function getCurrentDayStr() {
    return ucfirst(strftime('%A'));
}

function getCurrentMonthAbbrStr() {
    return strtoupper(strftime('%b'));
}

function getCurrentMonthNameStr() {
    return ucfirst(strftime('%B'));
}

function asDollars($value) {
    if ($value < 0) return '-'.asDollars(-$value);
    return '$'.number_format($value, 2);
}

function formatMoney($money) {
    return function_exists('money_format') ? money_format('$%.2n', $money) : asDollars($money);
}
/*
*/

class ReportPDF extends tFPDF
{
    // Recibe la función cargadora de datos
    function __construct($orientation='p') {
        parent::__construct($orientation);
        // Ajustar la anchura de la página
        $this->maxWidth = $this->w - 20;
        $this->rowHeight = 7;
        $this->fontSizeHeader = 12;
        $this->fontSizeRows = 8;
    }

    function setTitle($title) {
        $this->title = $title;
    }

    function setSubtitle($subtitle) {
        $this->subtitle = $subtitle;
    }
    
    function enableHour() {
        $this->hour = getHour();
    }
    
    // Asignar el alto de las columnas
    function setRowHeight($height) {
        $this->rowHeight = $height;
    }

    function Header() {
        $this->SetFont('', 'B', 14);
        $this->Cell(1, 10, utf8_decode($this->title));
        $this->SetFont('', '');
        $this->Cell(0, 10, getFullStrCurrentDate(), 0, 0, 'R');
        $this->Ln();
        if (isset($this->subtitle)) {
            $this->Cell(1, 5, utf8_decode($this->subtitle));
        }
        if (isset($this->hour)) {
            $this->Cell(0, 10, "Hora: $this->hour", 0, 0, 'R');
        }
        if (isset($this->subtitle) || isset($this->hour)) {
            $this->Ln();
        }
        $this->Ln();
    }

    function Footer() {
        $this->SetFontSize($this->fontSizeHeader);
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
    function createDynamicHeader($header, $options = []) {
        $this->SetFont('', '', $this->fontSizeHeader);
        
        if (count($options)) {
            if ($options['bold']) {
                $this->SetFont('', 'B');
            }
        }

        $colIndex = 1;
        
        foreach($header as $col) {
            $fill = false;

            if (count($options)) {
                if ($options['background']) {
                    if ($options['background'][$colIndex]) {
                        $this->SetFillColor(...$options['background'][$colIndex]);
                        $fill = true;
                    }
                }
            }

            $this->Cell(
                $this->maxWidth / count($header),
                $this->rowHeight,
                utf8_decode($col),
                1,
                0,
                'L',
                $fill
            );

            $colIndex++;
        }

        $this->Ln();
    }

    // Crea las columnas con una anchura dinámica
    function createDynamicRows($data, $options = []) {
        $this->SetFont('', '', $this->fontSizeRows);

        if (count($options)) {
            if ($options['bold']) {
                $this->SetFont('', 'B');
            }
        }

        foreach ($data as $row) {
            $colIndex = 1;

            foreach ($row as $key => $value) {
                $fill = false;

                if (count($options)) {
                    if ($options['background']) {
                        if ($options['background'][$colIndex]) {
                            $this->SetFillColor(...$options['background'][$colIndex]);
                            $fill = true;
                        }
                    }
                }

                $this->Cell(
                    $this->maxWidth / count($data[0]),
                    $this->rowHeight,
                    utf8_decode($value),
                    1,
                    0,
                    'L',
                    $fill
                );

                $colIndex++;
            }

            $this->Ln();
        }
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