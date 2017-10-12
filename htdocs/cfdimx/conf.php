<?php
/*
 * Datos para bgenerar CFDI
 */
// URL WS Produccion 1, pruebas 2.

//$tipo = 2;
//if($tipo == 1){
	//$wscfdi="http://www.cfdiauribox.com/TimbraCFDI/services/ServicioTimbrado1?wsdl";
//}elseif($tipo == 2){
	//$wscfdi="http://www.cfdiauribox.com/PruebasCFDI/services/ServicioTimbrado1?wsdl";
//}

$wscfdi = $conf->global->MAIN_MODULE_CFDIMX_WS;

function cfdimx_admin_prepare_head()
//function cfdimx_admin_prepare_head(null, $pestania)
{
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT."/cfdimx/admin/cfdimx.php?mod=dataEmisor";
    $head[$h][1] = "Datos del Emisor";
    $head[$h][2] = "uno";
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=config';
    $head[$h][1] = "Configuración";
    $head[$h][2] = "dos";
    $h++;
    
    $head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=emisores';
    $head[$h][1] = "Emisores";
    $head[$h][2] = "tres";
    $h++;
    
    $head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=descuentos';
    $head[$h][1] = "Descuentos";
    $head[$h][2] = "cuatro";
    $h++;
    
  	$head[$h][0] = DOL_URL_ROOT.'/cfdimx/admin/cfdimx.php?mod=retenciones';
    $head[$h][1] = "Retenciones";
    $head[$h][2] = "cinco";
    $h++;
    
    return $head;
}

function cfdimx_cliente_head($socid)
//function cfdimx_admin_prepare_head(null, $pestania)
{
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/cfdimx/fiche.php?socid=".$socid;
	$head[$h][1] = "CFDI";
	$head[$h][2] = "uno";
	$h++;

	/*
	$head[$h][0] = DOL_URL_ROOT.'/cfdimx/datos_receptor.php?socid='.$socid;
	$head[$h][1] = "Datos del Receptor";
	$head[$h][2] = "dos";
	$h++;
	*/

	$head[$h][0] = DOL_URL_ROOT.'/cfdimx/cfdi_usuario.php?socid='.$socid;
	$head[$h][1] = "Todas las facturas con CFDI";
	$head[$h][2] = "tres";
	$h++;

	return $head;
}


function cfdimx_factura_head($socid)
//function cfdimx_admin_prepare_head(null, $pestania)
{
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/cfdimx/fiche.php?socid=".$socid;
	$head[$h][1] = "Factura";
	$head[$h][2] = "uno";
	$h++;
	return $head;
}

function get_data_receptor( $db, $socid ){
	$data = array();
	$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."societe WHERE rowid = " . $socid;
	$resql=$db->query($sql);
	if ($resql){
		 $num = $db->num_rows($resql);
		 $i = 0;
		 if ($num){
			 while ($i < $num){
				 $obj = $db->fetch_object($resql);
				 if ($obj){
					 $data["id"] = $obj->rowid;
					 $data["nom"] = $obj->nom;
					 $data["rfc"] = $obj->siren;
				 }
				 $i++;
			 }
		 }
	}
	return $data;
}

function getLinkGeneraCFDI( $facstatut, $factura_id, $db ){
	//if( $facstatut==1 || $facstatut==2 ){
		$url = DOL_URL_ROOT.'/cfdimx/facture.php?facid='.$factura_id;
		$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture = ". $factura_id;
		$resql=$db->query($sql);
		if ($resql){
			 $num = $db->num_rows($resql);
			 $i = 0;
			 if ($num){
				 while ($i < $num){
					 $obj = $db->fetch_object($resql);
					 if ($obj){
						 return '<a href="facture.php?facid='.$factura_id.'">'. $obj->uuid .'</a>';
					 }
					 $i++;
				 }
			 }else{
				if( $facstatut==1 || $facstatut==2 ){
					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE rowid = " . $factura_id . " AND datef >  NOW() - INTERVAL 72 HOUR";
					$resql=$db->query($sql);
					if ($resql){
						 $num = $db->num_rows($resql);
						 $i = 0;
						 if ($num){ return '<a href="'.$url.'">Generar CFDI</a>'; }else{ return "Fuera de fecha de timbrado"; }
					}
				}else{
					return "N/A";
				}
			 }
		}else{
			return "N/A";
		}
	/*
	}else{
		return "N/A";
	}*/
}

function getSelected( $v1, $v2 ){
	if( $v1==$v2 ){
		return "selected";
	}else{ return ""; }
}
?>