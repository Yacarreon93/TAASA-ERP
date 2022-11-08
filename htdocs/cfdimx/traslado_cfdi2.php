<?php
    date_default_timezone_set("America/Mexico_City");

    require('../main.inc.php');
    require('conf.php');
    session_start();
    require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/trasladoDAO.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/transporteDAO.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/operadorDAO.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/origenDAO.php';
    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

    define("API_URL", "https://api.facturama.mx/2/cfdis/");
    //define("API_URL", "https://apisandbox.facturama.mx/2/cfdis/");
    
    $id = $_REQUEST["id"];
    if($id) {
        $trasladoDAO = new TrasladoDAO($db);

        print('Creating document...');
    
        //Include call to API
    
        $object = $trasladoDAO->GetTrasladoById($id);
        $transporteDAO = new TransporteDAO($db);
        $operadorDAO = new OperadorDAO($db);
        $origenDAO = new OrigenDAO($db);

        $dataComprobante[] = array(
            serie=>"T-".$id,
            folio=>$id,
            tipo_comprobante=> "T",
            fk_traslado=>$id
        );
        $trasladoDAO->InsertIntoCFDIComprobante($dataComprobante);
        $ubicacion_origen = $origenDAO->GetOrigenById($object->fk_ubicacion_origen);
        $transporte = $transporteDAO->GetTransporteById($object->fk_transporte);
        $operador = $operadorDAO->GetOperadorById($object->fk_operador);
        //$domicilio_cliente = $trasladoDAO->FetchDomicilioCliente($object->fk_cliente);
        $ubicacion_destino = $origenDAO->GetOrigenById($object->fk_ubicacion_destino);

        // $num = strlen($domicilio_cliente->rowid);
        // $destino = "DE";
        // for($i = 0; $i < (6 - $num); $i ++) 
        // {
        //     $destino.= "0";
        // }
        // $destino.= $domicilio_cliente->rowid;
        //$clienteData = $trasladoDAO->GetSocDataByFactureId($object->fk_facture);
        $folio = $trasladoDAO->GetComprobanteIdFromFactureId($id);
        //$cfdi_soc_data = $service->GetClientDataByFactureId($db, $id);

        //$duplicate_test = $service->CheckForDuplicate($db, $id);
        $duplicate_test = $trasladoDAO->CheckForDuplicate($id);
        if($duplicate_test) {
            $new_cfdi = array( 
                //Datos generales
                //Receptor
                "Receiver" => array(
                    "Name" => "Tecnologia y Aplicaciones Almentarias SA de CV",
                    "CfdiUse" => "P01",
                    "rfc" =>  $ubicacion_destino->RFC
                ),
                "CfdiType"=> "T",
                "NameId"=> "33",
                "Folio"=> $folio,
                "ExpeditionPlace" => trim($ubicacion_origen->cp),       
                "Items" => "foo",
                "Complemento" => array(
                    "CartaPorte20" => array(
                        "TranspInternac" => "No",
                        "Ubicaciones" => array(
                            array(    //-----Origen------
                                "TipoUbicacion" => "Origen", 
                                // "IDUbicacion" => $ubicacion_origen->id_ubicacion,
                                "RFCRemitenteDestinatario" => $ubicacion_origen->RFC,
                                "FechaHoraSalidaLlegada" => $object->fecha_salida,
                                "Domicilio" => array(
                                    "Pais" => "MEX",
                                    "CodigoPostal" => $ubicacion_origen->cp,
                                    "Estado" => $ubicacion_origen->codigo_estado,
                                    // "Municipio" => $ubicacion_origen->codigo_municipio,
                                    // "Localidad" => $ubicacion_origen->codigo_localidad,
                                    // "Colonia" => $ubicacion_origen->codigo_colonia,
                                    // "Calle" => $ubicacion_origen->calle
                                )
                            ),
                            array(     //-----Destino------
                                "TipoUbicacion" => "Destino", 
                                // "IDUbicacion" => $destino,
                                "RFCRemitenteDestinatario" => $ubicacion_destino->RFC,
                                "FechaHoraSalidaLlegada" => $object->fecha_llegada,
                                "DistanciaRecorrida" => $object->distancia_recorrida,
                                "Domicilio" => array(
                                    "Pais" => "MEX",
                                    "CodigoPostal" => $ubicacion_destino->cp,
                                    "Estado" => $ubicacion_destino->codigo_estado,
                                    // "Municipio" => $domicilio_cliente->cod_municipio,
                                    // "Localidad" => $domicilio_cliente->cod_localidad,
                                    // "Colonia" => $domicilio_cliente->cod_colonia,
                                    // "Calle" => $domicilio_cliente->receptor_calle
                                )
                            )
                        ),
                        "Mercancias" => array(
                            "UnidadPeso" => "KGM",
                            "Mercancia" => "foo",
                            "Autotransporte" => array(
                                "PermSCT" => "TPXX00",
                                "NumPermisoSCT" => "Permiso no contemplado en el catálogo",
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
                            array(
                            "TipoFigura" => "01",
                            "RFCFigura" => $operador->RFC,
                            "NombreFigura" =>  $operador->nombre,
                            "NumLicencia" =>  $operador->num_licencia,
                            )
                        )
                    )
                )
            );
        
            $cfdi_products = $trasladoDAO->FetchConceptosDataCFDI($id);
            $cfdi_mercancias = $trasladoDAO->FetchMercanciasData($id);
            $new_cfdi["Items"] = $cfdi_products;
            $new_cfdi["Complemento"]["CartaPorte20"]["Mercancias"]["Mercancia"]  = $cfdi_mercancias;
            $result = json_encode($new_cfdi);
        
            $make_call = callAPI(API_URL, $result);
            $response = json_decode($make_call, true);
            $errors   = $response['response']['errors'];
            $data     = $response['response']['data'][0];
        
            $trasladoDAO->UpdateControlTable($id, $response);
            $trasladoDAO->UpdateUUID($id, $response['Complement']['TaxStamp']);
        
            // if($cfdi_soc_data[0]['email']) {
            //     $sendResponse = $service->sendCFDI($response['Id'], $cfdi_soc_data[0]['email']);
            // }
        
            //$authorEmail = $trasladoDAO->GetAuthorEmailByFactureId($db, $user->id);
            $authorEmail = $user->email;
        
            if($authorEmail) {
                $trasladoDAO->sendCFDI($response['Id'], $authorEmail);
            }
        }      
    }

	print '<script>
	location.href="/custom/traslado/traslado/card.php?id='.$id.'&cfdi_commit=1";
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
