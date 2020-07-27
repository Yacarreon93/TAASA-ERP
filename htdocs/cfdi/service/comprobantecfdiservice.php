<?php

require_once DOL_DOCUMENT_ROOT.'/cfdi/dao/ComprobanteCFDIDao.php';


class ComprobanteCFDIService {

	public function SaveAllFactures($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$cfdiExists = $CFDIDao->CheckIfExists($factureId);
		if($cfdiExists) {
			$comprobanteData = $CFDIDao->FetchComprobanteData($factureId);
			$CFDIDao->InsertIntoCFDIComprobante($comprobanteData);
			$lastId = $CFDIDao->GetLastInsertedId();
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
			$lastId = $CFDIDao->GetLastInsertedId();
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
		$CFDIDao->InsertIntoCFDIComplementoPago($paymentArray,$comprobanteId);
		$CFDIDao->InsertIntoCFDIRelacionados($paymentArray, $comprobanteId);
		$CFDIDao->InsertIntoConceptosPago($paymentArray, $comprobanteId);
		$CFDIDao->InsertIntoConceptosTipoImpuestoPago($paymentArray, $comprobanteId);
		$CFDIDao->InsertIntoImpuestosGlobalesPago($paymentArray, $comprobanteId);
		$CFDIDao->InsertIntoImpuestosTotalesPago($paymentArray, $comprobanteId);
		//$CFDIDao->InsertIntoCFDIDocRelacionado($paymentArray, $comprobanteId);
	}

	public function SaveCFDIMXFromFacture($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$cfdiExists = $CFDIDao->CheckIfExists($factureId);
		if($cfdiExists) {
			$comprobanteData = $CFDIDao->FetchComprobanteData($factureId);
			$CFDIDao->InsertIntoCFDIMXComprobante($comprobanteData);
		}
	}
}