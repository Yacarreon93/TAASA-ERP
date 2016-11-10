<?php 

require_once DOL_DOCUMENT_ROOT.'/core/lib/fpdf/fpdf.php';

class CommissionsPDF extends FPDF
{
	function Header()
	{
	    // Select Arial bold 12
	    $this->SetFont('Arial','B',12);
	    // Move to the right
	    $this->Cell(80);
	    // Framed title
	    $this->Cell(30,10,'Tecnologia y Aplicaciones Alimentarias, S.A. de C.V.',0,0,'C');
	    // Line break
	    $this->Ln(8);
	    // Select Arial bold 12
	    $this->SetFont('Arial','B',8);
	    // Move to the right
	    $this->Cell(80);
	    // Framed title
	    $this->Cell(30,10,'REPORTE DE COMISIONES',0,0,'C');
	    // Line break
	    $this->Ln(5);
	}
}

?>