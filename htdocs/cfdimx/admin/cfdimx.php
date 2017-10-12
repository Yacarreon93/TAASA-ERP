<?php
require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
include("../lib/nusoap/lib/nusoap.php");
include("../conf.php");

if( !isset($_REQUEST["mod"]) || $_REQUEST["mod"]=="dataEmisor" ){
	$inc = "datosEmisor.php";
	$tab="uno";
}
if( $_REQUEST["mod"]=="config" ){
	$inc = "configuracion.php";
	$tab="dos";
}
if( $_REQUEST["mod"]=="emisores" ){
	$inc = "emisores.php";
	$tab="tres";
}
if( $_REQUEST["mod"]=="descuentos" ){
	$inc = "descuentos.php";
	$tab="cuatro";
}
if( $_REQUEST["mod"]=="retenciones" ){
	$inc = "retenciones.php";
	$tab="cinco";
}

$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."'");
if ($resql){
	 $num_emisor_datacomp = $db->num_rows($resql);
	 $i = 0;
	 if ($num_emisor_datacomp){
		 while ($i < $num_emisor_datacomp){
			 $obj = $db->fetch_object($resql);
			 if ($obj){
				 $emisor_delompio = $obj->emisor_delompio;
				 $emisor_calle = $obj->emisor_calle;
				 $emisor_noint = $obj->emisor_noint;
				 $emisor_noext = $obj->emisor_noext;
				 $emisor_colonianw = $obj->emisor_colonia;
				 $emisor_cod_municipio=$obj->cod_municipio;
			 }
			 $i++;
		 }
	 }
}

function valida_datos_emisor($conf, $num_emisor_datacomp){
	if(  
		$conf->global->MAIN_INFO_SIREN!="" &&
		$conf->global->MAIN_INFO_SIREN!="" &&
		$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE!="" &&
		$conf->global->MAIN_INFO_SOCIETE_NOM!="" &&
		$conf->global->MAIN_INFO_SOCIETE_COUNTRY!="" &&
		$conf->global->MAIN_INFO_SOCIETE_STATE!="" &&
		$conf->global->MAIN_INFO_SOCIETE_ZIP!="" &&
		//$conf->global->MAIN_INFO_SOCIETE_VILLE!="" &&
		$num_emisor_datacomp > 0
	){
		return 1;
	}else{ return 0; }
}


if (!$user->admin) accessforbidden();

$title="Configuración CFDI";
llxHeader('',$title);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($title,$linkback,'setup');
print "<p><p/>";

$head=cfdimx_admin_prepare_head();
dol_fiche_head($head, $tab, "Configuración CFDI", 0, 'product');

include($inc);

dol_fiche_end();

llxFooter();

$db->close();
?>