<?php

	require_once DOL_DOCUMENT_ROOT.'/cfdi/service/comprobantecfdiservice.php';

	$service = new ComprobanteCFDIService();
	$service->SaveAllFactures($db, $id);

	//Include call to API

	$cfdi_main_data = $service->GetComprobanteData($db, $id);
	$cfdi_soc_data = $service->GetClientDataByFactureId($db, $id);

	strtok($cfdi_main_data[0]['serie'], '-');
	$serie = strtok('-');
	
	$new_cfdi = array( 
		//Datos generales
		"NameId" => "1",
		"Folio" => $cfdi_main_data[0]['folio'],
		"CfdiType"=> "I",
		"ExpeditionPlace" => trim($cfdi_main_data[0]['lugar_de_expedicion']),
		"PaymentForm" => $cfdi_main_data[0]['forma_pago'],
		"PaymentMethod" => $cfdi_main_data[0]['metodo_pago'],
		"Currency" => $cfdi_main_data[0]['moneda'],
		//Receptor
		"Receiver" => array(
			"Name" => $cfdi_soc_data[0]['name'],
			"CfdiUse" => $cfdi_main_data[0]['uso_cfdi'],
			"rfc" => $cfdi_soc_data[0]['rfc']),
		//conceptos
		"Items" => "foo"
	);

	$cfdi_products = $service->FetchConceptosDataCFDI($db, $id);

	$new_cfdi["Items"] = $cfdi_products;
	$result = json_encode($new_cfdi);

	$make_call = callAPI('https://apisandbox.facturama.mx/2/cfdis/', $result);
	$response = json_decode($make_call, true);
	$errors   = $response['response']['errors'];
	$data     = $response['response']['data'][0];

	$service->UpdateControlTable($db, $id, $response);
	$service->UpdateUUID($db, $id, $response['Complement']['TaxStamp']);

	print '<script>
	location.href="facture.php?facid='.$_REQUEST["facid"].'&cfdi_commit=1";
	</script>';


	function callAPI( $url, $data){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        if ($data)
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization: Basic cHJ1ZWJhczpwcnVlYmFzMjAxMQ==',
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

?>
