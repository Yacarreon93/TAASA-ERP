<?php

require_once DOL_DOCUMENT_ROOT.'/comprobanteCFDI/dao/ComprobanteCFDIDao.php';


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

	public function SaveCFDIFromPayment($db, $paymentId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		//$CFDIDao->InsertIntoPagoCFDI
	}
}