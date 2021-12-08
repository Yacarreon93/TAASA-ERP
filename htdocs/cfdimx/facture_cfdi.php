<?php
    date_default_timezone_set("America/Mexico_City");

    require('../main.inc.php');
    require('conf.php');
    include('lib/nusoap/lib/nusoap.php');
    include("lib/phpqrcode/qrlib.php");
    require('lib/numero_a_letra.php');
    require_once('lib/mimemail/htmlMimeMail5.php');
    $maild = new htmlMimeMail5();
    session_start();
    $tpdomic='';
    require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
    require_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
    require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
    require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
    require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
    require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
    require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
	require_once(DOL_DOCUMENT_ROOT.'/cfdi/service/comprobantecfdiservice.php');

    define("API_URL", "https://api.facturama.mx/2/cfdis/");
    
    $id = $_REQUEST["facid"];
    if($id) {
        $service = new ComprobanteCFDIService();
        $service->SaveAllFactures($db, $id);
        
        print('Creating document...');
    
        //Include call to API
    
        $cfdi_main_data = $service->GetComprobanteData($db, $id);
        $cfdi_soc_data = $service->GetClientDataByFactureId($db, $id);
        $comprobanteId = $service->GetComprobanteIdFromFactureId($db, $id);
    
        strtok($cfdi_main_data[0]['serie'], '-');
        $serie = strtok('-');

        $duplicate_test = $service->CheckForDuplicate($db, $id);
        if($duplicate_test) {
            $new_cfdi = array( 
                //Datos generales
                "NameId" => "1",
                "Folio" => $comprobanteId,
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
        
            $make_call = callAPI(API_URL, $result);
            $response = json_decode($make_call, true);
            $errors   = $response['response']['errors'];
            $data     = $response['response']['data'][0];
        
            $service->UpdateControlTable($db, $id, $response);
            $service->UpdateUUID($db, $id, $response['Complement']['TaxStamp']);
        
            if($cfdi_soc_data[0]['email']) {
                $sendResponse = $service->sendCFDI($response['Id'], $cfdi_soc_data[0]['email']);
            }
        
            $authorEmail = $service->GetAuthorEmailByFactureId($db, $id);
        
            if($authorEmail) {
                $service->sendCFDI($response['Id'], $authorEmail);
            }
        }      
    }

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

?>
