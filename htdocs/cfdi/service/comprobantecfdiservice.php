<?php

require_once DOL_DOCUMENT_ROOT.'/cfdi/dao/ComprobanteCFDIDao.php';


class ComprobanteCFDIService {

	public function SaveAllFactures($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$cfdiExists = $CFDIDao->CheckIfExists($factureId);
		$rowNumber = mysqli_num_rows($cfdiExists); 
		if($rowNumber == 0) {
			$comprobanteData = $CFDIDao->FetchComprobanteData($factureId);
			$CFDIDao->InsertIntoCFDIComprobante($comprobanteData);
			$lastId = $CFDIDao->GetComprobanteIdByFactureId($factureId);
			$CFDIDao->InsertIntoCFDIComplementoPagoFromFacture($comprobanteData,$lastId);
			$CFDIDao->InsertIntoCFDIRelacionadosFromFacture($comprobanteData, $lastId);
			$conceptosData = $CFDIDao->FetchConceptosData($factureId);
			$CFDIDao->InsertIntoConceptosComprobante($conceptosData, $lastId);
			$impuestosData =$CFDIDao->FetchImpuestosData($factureId);
			$CFDIDao->InsertIntoConceptosTipoImpuesto($impuestosData, $lastId);
			$CFDIDao->InsertIntoCFDIMXComprobante($comprobanteData);
		}
	}

	public function SaveCFDIFromFacture($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$cfdiExists = $CFDIDao->CheckIfExists($factureId);
		if($cfdiExists) {
			$comprobanteData = $CFDIDao->FetchComprobanteData($factureId);
			$CFDIDao->InsertIntoCFDIComprobante($comprobanteData);
			$lastId = $CFDIDao->GetComprobanteIdByFactureId($factureId);
			$conceptosData = $CFDIDao->FetchConceptosData($factureId);
			$CFDIDao->InsertIntoConceptosComprobante($conceptosData, $lastId);
			$impuestosData =$CFDIDao->FetchImpuestosData($factureId);
			$CFDIDao->InsertIntoConceptosTipoImpuesto($impuestosData, $lastId);
		}
	}

	public function SaveCFDIFromPayment($db, $factureId, $paymentId, $paymentArray) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$CFDIDao->InsertIntoCFDIComprobantePago($factureId, $paymentArray);
		$comprobanteId = $CFDIDao->GetComprobanteIdByFactureId($factureId);
		$comprobantePagoId = $CFDIDao->GetComprobanteIdByPaymentId($paymentId);
		$CFDIDao->InsertIntoCFDIComplementoPago($paymentArray, $comprobantePagoId);
		$CFDIDao->InsertIntoCFDIRelacionados($paymentArray, $comprobantePagoId);
		$CFDIDao->InsertIntoConceptosPago($paymentArray, $comprobantePagoId);
		$CFDIDao->InsertIntoConceptosTipoImpuestoPago($paymentArray, $comprobantePagoId);
		$CFDIDao->InsertIntoImpuestosGlobalesPago($paymentArray, $comprobantePagoId);
		$CFDIDao->InsertIntoImpuestosTotalesPago($paymentArray, $comprobantePagoId);
	}

	public function SaveCFDIMXFromFacture($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$cfdiExists = $CFDIDao->CheckIfExists($factureId);
		if($cfdiExists) {
			$comprobanteData = $CFDIDao->FetchComprobanteData($factureId);
			$CFDIDao->InsertIntoCFDIMXComprobante($comprobanteData);
		}
	}

	public function GetComprobanteData($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$comprobanteData = $CFDIDao->FetchComprobanteData($factureId);
		return $comprobanteData;
	}

	public function FetchConceptosDataCFDI($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$conceptosData = $CFDIDao->FetchConceptosDataCFDI($factureId);
		$impuestosData = $CFDIDao->FetchImpuestosDataCFDI($factureId);
		for($i=0; $i < sizeof($conceptosData); $i++) {
			$conceptosData[$i]['Taxes'] = $impuestosData[$i];
		}
		return $conceptosData;
	}

	public function GetClientDataByFactureId($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$clientData = $CFDIDao->GetSocDataByFactureId($factureId);
		return $clientData;
	}

	public function UpdateControlTable($db, $factureId, $data) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$clientData = $CFDIDao->InsertIntoCFDIControlTable($factureId, $data);
		return $clientData;
	}

	public function UpdateUUID($db, $factureId, $data) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$clientData = $CFDIDao->UpdateCFDIUUID($factureId, $data);
		return $clientData;
	}
}