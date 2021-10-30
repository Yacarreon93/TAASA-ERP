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

	public function GetComprobantePagoData($db, $pagoId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$comprobanteData = $CFDIDao->FetchComprobantePagoData($pagoId);
		return $comprobanteData;
	}

	public function GetComprobanteRelacionadoData($db, $pagoId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$comprobanteData = $CFDIDao->FetchComprobanteRelacionadoData($pagoId);
		return $comprobanteData;
	}

	public function GetComprobanteInfo($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$comprobanteData = $CFDIDao->FetchComprobanteInfo($factureId);
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

	public function GetVendorEmailByFactureId($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$data = $CFDIDao->GetVendorEmailByFactureId($factureId);
		return $data;
	}

	public function GetAuthorEmailByFactureId($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$data = $CFDIDao->GetAuthorEmailByFactureId($factureId);
		return $data;
	}

	public function UpdateControlTable($db, $factureId, $data) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$CFDIDao->InsertIntoCFDIControlTable($factureId, $data);
	}

	public function UpdateControlTableFromPayment($db, $factureId, $data) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$CFDIDao->InsertIntoCFDIControlTableFromPayment($factureId, $data);
	}

	public function UpdateUUID($db, $factureId, $data) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$CFDIDao->UpdateCFDIUUID($factureId, $data);
	}

	public function UpdatePaymentCFDIUUID($db, $pagoId, $data) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$CFDIDao->UpdatePaymentCFDIUUID($pagoId, $data);
	}

	public function GetCFDIId($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$cfdiId = $CFDIDao->GetCFDIId($factureId);
		return $cfdiId;
	}

	public function GetComprobanteIdFromFactureId($db, $factureId) {
		$CFDIDao = new ComprobanteCFDIDao($db);
		$cfdiId = $CFDIDao->GetComprobanteIdByFactureId($factureId);
		return $cfdiId;
	}

	public function CancelCFDI($cfdiId) {
		$cancelUrl = 'https://api.facturama.mx/cfdi/'.$cfdiId.'?type=issued';
		$curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $cancelUrl);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization: Basic bG1pcmExOTpMdWlzYXp1bF8xOQ==',
        'Content-Type: application/json',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        $result = curl_exec($curl);
        if(!$result){die("Connection Failure");}
        curl_close($curl);
        return $result;
	}

	public function SendCFDI($cfdiId, $email) {
		$sendUrl = 'https://api.facturama.mx/cfdi?cfdiType=issued&cfdiId='.$cfdiId.'&email='.$email;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, 1);
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $sendUrl);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization: Basic bG1pcmExOTpMdWlzYXp1bF8xOQ==',
		'Content-Type: application/json',
		'Content-Length: 0'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        $result = curl_exec($curl);
        if(!$result){die("Connection Failure");}
		curl_close($curl);
        return $result;
	}
}