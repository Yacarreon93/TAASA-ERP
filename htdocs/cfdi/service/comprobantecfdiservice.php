<?php

require_once DOL_DOCUMENT_ROOT.'/cfdi/dao/ComprobanteCFDIDao.php';


class ComprobanteCFDIService {

	public function SaveCFDIFromFacture($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$comprobanteData = $CFDIDao->FetchComprobanteData($factureId);
		$CFDIDao->InsertIntoCFDIComprobante($comprobanteData);
		$lastId = $CFDIDao->GetLastInsertedId();
		$conceptosData = $CFDIDao->FetchConceptosData($factureId);
		$CFDIDao->InsertIntoConceptosComprobante($conceptosData, $lastId);
		$impuestosData =$CFDIDao->FetchImpuestosData($factureId);
		$CFDIDao->InsertIntoConceptosTipoImpuesto($impuestosData, $lastId);
	}

	public function SaveCFDIFromPayment($db, $paymentArray) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$CFDIDao->InsertIntoCFDIComprobantePago($paymentArray);
		$lastId = $CFDIDao->GetLastInsertedId();
		$CFDIDao->InsertIntoCFDIRelacionados($paymentArray, $lastId);
		$CFDIDao->InsertIntoConceptosPago($paymentArray, $lastId);
		$CFDIDao->InsertIntoCFDIComplementoPago($paymentArray, $lastId);
		$CFDIDao->InsertIntoCFDIDocRelacionado($paymentArray, $lastId);
	}
}