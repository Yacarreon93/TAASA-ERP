<?php

require_once DOL_DOCUMENT_ROOT.'/comprobanteCFDI/dao/ComprobanteCFDIDao.php';


class ComprobanteCFDIService {

	public function SaveCFDIFromFacture($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$comprobanteData = $CFDIDao->FetchComprobanteData($factureId);
		$CFDIDao->InsertIntoCFDIComprobante($comprobanteData);
		$conceptosData = $CFDIDao->FetchConceptosData($factureId);
		$CFDIDao->InsertIntoConceptosComprobante($conceptosData);
		$impuestosData =$CFDIDao->FetchImpuestosData($factureId);
		$CFDIDao->InsertIntoConceptosTipoImpuesto($impuestosData);
	}
}