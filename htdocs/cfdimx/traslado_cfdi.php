<?php
    date_default_timezone_set("America/Mexico_City");

    require('../main.inc.php');
    require('conf.php');
    // include('lib/nusoap/lib/nusoap.php');
    // include("lib/phpqrcode/qrlib.php");
    // require('lib/numero_a_letra.php');
    // require_once('lib/mimemail/htmlMimeMail5.php');
    session_start();
    // require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
    // require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
    // require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
    // require_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
    // require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
    // require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
    // require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
    // require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
    // require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
    require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/cartaDAO.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/transporteDAO.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/operadorDAO.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/origenDAO.php';
    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

    define("API_URL", "https://api.facturama.mx/2/cfdis/");
    
    $id = $_REQUEST["id"];
    if($id) {
        $cartaDAO = new CartaDAO($db);
        
        print('Creating document...');
    
        //Include call to API
    
        $object = $cartaDAO->GetTrasladoById($id);
        $objectFacture = new Facture($db);
        $transporteDAO = new TransporteDAO($db);
        $operadorDAO = new OperadorDAO($db);
        $origenDAO = new OrigenDAO($db);

        if($object->fk_facture) 
        {
            $objectFacture->fetch($object->fk_facture);
            $soc = new Societe($db);
            $soc->fetch($objectFacture->socid);	
        }
        $ubicacion_origen = $origenDAO->GetOrigenById($object->fk_ubicacion_origen);
        $transporte = $transporteDAO->GetTransporteById($object->fk_transporte);
        $operador = $operadorDAO->GetOperadorById($object->fk_operador);
        $domicilio_cliente = $cartaDAO->FetchDomicilioCliente($object->fk_cliente);
        $num = strlen($domicilio_cliente->rowid);
        $destino = "DE";
        for($i = 0; $i < (6 - $num); $i ++) 
        {
            $destino.= "0";
        }
        $destino.= $domicilio_cliente->rowid;
        $clienteData = $cartaDAO->GetSocDataByFactureId($object->fk_facture);
        //$cfdi_soc_data = $service->GetClientDataByFactureId($db, $id);

        //$duplicate_test = $service->CheckForDuplicate($db, $id);
        $duplicate_test = true;
        if($duplicate_test) {
            $new_cfdi = array( 
                //Datos generales
                //Receptor
                "Receiver" => array(
                    "Name" => $soc->nom,
                    "CfdiUse" => "P01",
                    "rfc" =>  $clienteData[0]["rfc"]
                ),
                "CfdiType"=> "T",
                "NameId"=> "33",
                "ExpeditionPlace" => trim($ubicacion_origen->cp),
                "Items" => "foo",
                "Complemento" => array(
                    "CartaPorte20" => array(
                        "TranspInternac" => "No",
                        "Ubicaciones" => array(
                            array(    //-----Origen------
                                "TipoUbicacion" => "Origen", 
                                "IDUbicacion" => $ubicacion_origen->id_ubicacion,
                                "RFCRemitenteDestinatario" => $ubicacion_origen->RFC,
                                "FechaHoraSalidaLlegada" => $object->fecha_salida,
                                "Domicilio" => array(
                                    "Pais" => "MEX",
                                    "CodigoPostal" => $ubicacion_origen->cp,
                                    "Estado" => $ubicacion_origen->codigo_estado,
                                    "Municipio" => $ubicacion_origen->codigo_municipio,
                                    "Localidad" => $ubicacion_origen->codigo_localidad,
                                    "Colonia" => $ubicacion_origen->codigo_colonia,
                                    "Calle" => $ubicacion_origen->calle
                                )
                            ),
                            array(     //-----Destino------
                                "TipoUbicacion" => "Destino", 
                                "IDUbicacion" => $destino,
                                "RFCRemitenteDestinatario" => $clienteData[0]["rfc"],
                                "FechaHoraSalidaLlegada" => $object->fecha_llegada,
                                "DistanciaRecorrida" => $object->distancia_recorrida,
                                "Domicilio" => array(
                                    "Pais" => "MEX",
                                    "CodigoPostal" => $soc->zip,
                                    "Estado" => $domicilio_cliente->cod_estado,
                                    "Municipio" => $domicilio_cliente->cod_municipio,
                                    "Localidad" => $domicilio_cliente->cod_localidad,
                                    "Colonia" => $domicilio_cliente->cod_colonia,
                                    "Calle" => $domicilio_cliente->receptor_calle
                                )
                            )
                        ),
                        "Mercancias" => array(
                            "UnidadPeso" => "KGM",
                            "Mercancia" => "foo",
                            "Autotransporte" => array(
                                "PermSCT" => "PXX00",
                                "NumPermisoSCT" => "Permiso no contemplado en el catálogo”",
                                "IdentificacionVehicular" => array(
                                    "ConfigVehicular" => $transporte->config_vehicular,
                                    "PlacaVM" => $transporte->placas,
                                    "AnioModeloVM" => $transporte->anio
                                ),
                                "Seguros" => array(
                                    "AseguraRespCivil" => $transporte->aseguradora,
                                    "PolizaRespCivil" => $transporte->poliza
                                )
                            )
                        ),
                        "FiguraTransporte" => array(
                            "TipoFigura" => "01",
                            "RFCFigura" => $operador->RFC,
                            "NombreFigura" =>  $operador->nombre,
                            "NumLicencia" =>  $operador->num_licencia,
                        )
                    )
                )
            );
        
            $cfdi_products = $cartaDAO->FetchConceptosDataCFDI($id);
            $cfdi_mercancias = $cartaDAO->FetchMercanciasData($id, $ubicacion_origen->id_ubicacion, $destino);
            $new_cfdi["Items"] = $cfdi_products;
            $new_cfdi["Complemento"]["CartaPorte20"]["Mercancias"]["Mercancia"]  = $cfdi_mercancias;
            $result = json_encode($new_cfdi);

            print_r($result);
            die();
        
            // $make_call = callAPI(API_URL, $result);
            // $response = json_decode($make_call, true);
            // $errors   = $response['response']['errors'];
            // $data     = $response['response']['data'][0];
        
            // $service->UpdateControlTable($db, $id, $response);
            // $service->UpdateUUID($db, $id, $response['Complement']['TaxStamp']);
        
            // if($cfdi_soc_data[0]['email']) {
            //     $sendResponse = $service->sendCFDI($response['Id'], $cfdi_soc_data[0]['email']);
            // }
        
            // $authorEmail = $service->GetAuthorEmailByFactureId($db, $id);
        
            // if($authorEmail) {
            //     $service->sendCFDI($response['Id'], $authorEmail);
            // }
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
