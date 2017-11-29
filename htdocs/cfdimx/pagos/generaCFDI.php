<?php
require('../../main.inc.php');
require('../conf.php');
include('../lib/nusoap/lib/nusoap.php');
include("../lib/phpqrcode/qrlib.php");
require('../lib/numero_a_letra.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');

global $conf;
$pagcid=GETPOST("pagcid");
$facid=GETPOST("facid");

$serie="";
$folio="";

$pago=new Paiement($db);
$pago->fetch($pagcid);
$facture=new Facture($db);
$facture->fetch($facid);
//print_r($pago);
//print $pago->ref;
$separa=explode("-", $pago->ref);
$serie=$separa[0];
$folio=$separa[1];
//DATOS DEL HEADER DEL COMPROBANTE
$header=array();
if( $serie!="" ){
	$header["serie"]=trim(preg_replace("/ +/"," ",$serie));
}
if( $folio!="" ){
	$header["folio"]=trim(preg_replace("/ +/"," ",$folio));
}

$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".$facid." AND fk_paiement=".$pagcid;
$rq=$db->query($sql);
$respag=$db->fetch_object($rq);

$fechap=str_replace(" ","T",$respag->fechaPago);
$header["fecha"]=$fechap;
$header["subTotal"]=0;
$header["moneda"]="XXX";
$header["total"]=0;
$header["tipoDeComprobante"]="P";
$header["lugarExpedicion"]=$conf->global->MAIN_INFO_SOCIETE_ZIP;

//DATOS DEL EMISOR
$emisor=array();
$regimen = $conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
$emisor["emisorRFC"]=$conf->global->MAIN_INFO_SIREN;
$emisor["nombre"]=$conf->global->MAIN_INFO_SOCIETE_NOM;
$emisor["emisorRegimen"]=$regimen;

//DATOS DEL RECEPTOR
$receptor=array();
$sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe	WHERE rowid = ".$facture->socid;
$req=$db->query($sql);
$resocid=$db->fetch_object($req);

$receptor["rfc"]=$resocid->siren;
$receptor["nombre"]=$resocid->nom;
$factura_usocfdi="P01";
if($factura_usocfdi!=""){
	$receptor["usoCFDI"]=trim(preg_replace("/ +/"," ",$factura_usocfdi));
}
$conceptos[0] = array(
		'descripcion' =>"Pago",
		'cantidad' =>1,
		'valorUnitario'=>0,
		'importe'=>0,
		'unidad'=>"ACT",
		'claveProdServ'=>"84111506"
);
// 'importeImpuesto'=>NULL,
// 'impuesto'=>NULL,// IVA == 002
// 'tasa'=>NULL,
$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id=".$conf->entity);
$obj = $db->fetch_object($resql);
$modo_timbrado = $obj->modo_timbrado;
$passwd_timbrado = $obj->password_timbrado_txt;

$pagos=array();
$pagos["fechaPago"]=$fechap;
$pagos["formaDePago"]=$respag->formaDePago;
$pagos["monedaP"]=$respag->monedaP;

if($respag->TipoCambioP!=NULL && $respag->TipoCambioP!=""){
	$pagos["tipoCambioP"]=$respag->TipoCambioP;
}
$pagos["monto"]=str_replace(",", "", number_format($respag->monto,2));
if($respag->numOperacion!=NULL && $respag->numOperacion!=""){
	$pagos["numOperacion"]=$respag->numOperacion;
}
if($respag->rfcEmisorCtaOrd!=NULL && $respag->rfcEmisorCtaOrd!=""){
	$pagos["rfcEmisorCtaOrd"]=$respag->rfcEmisorCtaOrd;
}
if($respag->nomBancoOrdExt!=NULL && $respag->nomBancoOrdExt!=""){
	$pagos["nomBancoOrdExt"]=$respag->nomBancoOrdExt;
}
if($respag->ctaOrdenante!=NULL && $respag->ctaOrdenante!=""){
	$pagos["ctaOrdenante"]=$respag->ctaOrdenante;
}
if($respag->rfcEmisorCtaBen!=NULL && $respag->rfcEmisorCtaBen!=""){
	$pagos["rfcEmisorCtaBen"]=$respag->rfcEmisorCtaBen;
}
if($respag->ctaBeneficiario!=NULL && $respag->ctaBeneficiario!=""){
	$pagos["ctaBeneficiario"]=$respag->ctaBeneficiario;
}
if($respag->tipoCadPago!=NULL && $respag->tipoCadPago!=""){
	$pagos["tipoCadPago"]=$respag->tipoCadPago;
}
if($respag->certPago!=NULL && $respag->certPago!=""){
	$pagos["certPago"]=$respag->certPago;
}
if($respag->cadPago!=NULL && $respag->cadPago!=""){
	$pagos["cadPago"]=$respag->cadPago;
}
if($respag->selloPago!=NULL && $respag->selloPago!=""){
	$pagos["selloPago"]=$respag->selloPago;
}

$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado WHERE fk_recepago=".$respag->rowid;
$rq=$db->query($sql);
$resdocto=$db->fetch_object($rq);
$pagos["idDocumento"]=$resdocto->idDocumento;
if($resdocto->serie!=NULL && $resdocto->serie!=""){
	$pagos["serie"]=$resdocto->serie;
}
if($resdocto->folio!=NULL && $resdocto->folio!=""){
	$pagos["folio"]=$resdocto->folio;
}
$pagos["monedaDR"]=$resdocto->monedaDR;
if($resdocto->tipoCambioDR!=NULL && $resdocto->tipoCambioDR!=""){
	$pagos["tipoCambioDR"]=$resdocto->tipoCambioDR;
}
$pagos["metodoDePagoDR"]=$resdocto->metodoDePagoDR;
if($resdocto->numParcialidad!=NULL && $resdocto->numParcialidad!=""){
	$pagos["numParcialidad"]=$resdocto->numParcialidad;
}
if($resdocto->impSaldoAnt!=NULL && $resdocto->impSaldoAnt!=""){
	$pagos["impSaldoAnt"]=$resdocto->impSaldoAnt;
}
if($resdocto->impPagado!=NULL && $resdocto->impPagado!=""){
	$pagos["impPagado"]=$resdocto->impPagado;
}
if($resdocto->impSaldoInsoluto!=NULL && $resdocto->impSaldoInsoluto!=""){
	$pagos["impSaldoInsoluto"]=$resdocto->impSaldoInsoluto;
}
$adicionales["repagos"]=$pagos;
$client = new nusoap_client($wscfdi, 'wsdl');
$result = $client->call("timbraCFDI",
		array(
				"comprobante"=>$header,
				"conceptos"=>$conceptos,
				"emisor"=>$emisor,
				"receptor"=>$receptor,
				"timbrado_usuario"=>$conf->global->MAIN_INFO_SIREN,
				"timbrado_password"=>$passwd_timbrado,
				"adicionales"=>$adicionales
		)
);

$prmsnd["logosmall"]=$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;
if( $result["return"]["rsp"]==1 ){

	$separa_ftimbrado = explode("T",$result["return"]["fechaTimbrado"]);

	
	if(file_exists($conf->facture->dir_output."/".$facture->ref)){}else{
		mkdir($conf->facture->dir_output."/".$facture->ref,0700);
	}

	$file_xml = fopen ($conf->facture->dir_output."/".$facture->ref."/Pago_".$result["return"]["uuid"].".xml", "w");
	fwrite($file_xml,utf8_encode($result["return"]["xml"]));
	fclose($file_xml);
	$file_xml_str = $conf->facture->dir_output."/".$facture->ref."/Pago_".$result["return"]["uuid"].".xml";
	try{
		$the_xml = file_get_contents($file_xml_str);
		$sxe = new SimpleXMLElement($the_xml);
		$ns = $sxe->getNamespaces(true);
		$sxe->registerXPathNamespace('t', $ns['cfdi']);
		foreach ($sxe->xpath('//t:Comprobante') as $tfd) {
			$noCertificado = "{$tfd['NoCertificado']}";
		}
	}catch(Exception $e){
		echo $e->getMessage()."<br>";
	}
	$result["return"]["version"]=isset($result["return"]["version"])?$result["return"]["version"]:"1.0";// AMM solucion provicional
	$separa_ftimbrado = explode("T",$result["return"]["fechaTimbrado"]);
	$sqm="UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos 
			SET 
			xml='".$db->escape($result["return"]["xml"])."',
			cadena='".$db->escape($result["return"]["cadenaOrig"])."',
			version='".$result["return"]["version"]."',
			selloCFD='".$db->escape($result["return"]["selloCFD"])."',
			certificado='".$db->escape($result["return"]["certSAT"])."',
			sello='".$db->escape($result["return"]["selloSAT"])."',
			certEmisor='".$noCertificado."',
			uuid='".$result["return"]["uuid"]."',
			fecha_emision='".$separa_ftimbrado[0]."',
			hora_emision='".$separa_ftimbrado[1]."'
			WHERE fk_facture=".$facid." AND fk_paiement=".$pagcid;
	$rq=$db->query($sqm);
	$sqlm="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".$facid." AND fk_paiement=".$pagcid;
	$rq=$db->query($sqlm);
	$obj=$db->fetch_object($rq);
	$fechap=str_replace(" ","T",$obj->fechaPago);
	
	$prmsnd["version"] = $obj->version;
	$prmsnd["uuid"] = $obj->uuid;
	$prmsnd["cadena"] = $obj->cadena;
	$prmsnd["selloCFD"] = $obj->selloCFD;
	$prmsnd["selloSAT"] = $obj->sello;
	$prmsnd["fechaTimbrado"] = $result["return"]["fechaTimbrado"];
	$prmsnd["certificado"] = $obj->certificado;
	$prmsnd["certEmisor"] = $obj->certEmisor;
	$prmsnd["fechaEmision"] =$fechap;
	$prmsnd["coccds"] = "||".$prmsnd["version"]."|".$prmsnd["uuid"]."|".$prmsnd["fechaTimbrado"]."|".$prmsnd["selloCFD"]."|".$prmsnd["selloSAT"]."||";
	include("generaPDF.php");
	print "<script>window.location.href='../pagos.php?action=cfdi1&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."'</script>";
}else{
	if($result["return"]["rsp"]!=""){
		$_SESSION["errorCFDIP"] = $result["return"]["rsp"]." - ".$result["return"]["msg"];
	}else{
		//primera vuelta
		$_SESSION["errorCFDIP"] = "No hubo respuesta para la peticion, intente nuevamente";
	}
	print "<script>window.location.href='../pagos.php?action=cfdi&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."&msgerr=1'</script>";
}

// print "<br><br>";
// print_r($header);
// print "<br><br>";
// print_r($emisor);
// print "<br><br>";
// print_r($receptor);
// print "<br><br>";
// print_r($conceptos);
// print "<br><br>";
// print_r($pagos);