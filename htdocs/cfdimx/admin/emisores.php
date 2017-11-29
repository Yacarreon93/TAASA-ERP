<?php
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
global $db, $conf;
$action=GETPOST('action');
$form=new Form($db);
$formcompany=new FormCompany($db);
if(isset($conf->global->MAIN_INFO_SIREN) && isset($conf->global->MAIN_INFO_SOCIETE_NOM) && $conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE!=0 ){
print "<table class='noborder' width='100%'>";
	print "<tr class='liste_titre'>";
		print "<td colspan='2'>Emisor predeterminado</td></tr>";
	print "<tr>";
		print "<td width='30%'>RFC</td>";
		print "<td>".$conf->global->MAIN_INFO_SIREN."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Regimen</td>";
		$regimen = getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
		print "<td>".$regimen."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Razon Social</td>";
		print "<td>".$conf->global->MAIN_INFO_SOCIETE_NOM."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Pais</td>";
		$tmp=explode(':',$conf->global->MAIN_INFO_SOCIETE_COUNTRY);
		$country_id=$tmp[0];
		$country_code=$tmp[1];
		$country=$tmp[2];
		print "<td>".$country."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Estado</td>";
		$estado = getState($conf->global->MAIN_INFO_SOCIETE_STATE);
		print "<td>".$estado."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Codigo Postal</td>";
		print "<td>".$conf->global->MAIN_INFO_SOCIETE_ZIP."</td>";
	print "</tr>";
	$sql="SELECT a.emisor_delompio, a.emisor_colonia, a.emisor_calle, a.emisor_noext, a.emisor_noint, b.password_timbrado_txt
			FROM ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp a,".MAIN_DB_PREFIX."cfdimx_config b
			WHERE a.emisor_rfc='".$conf->global->MAIN_INFO_SIREN."' AND a.entity_id=".$conf->entity."
			 AND a.emisor_rfc=b.emisor_rfc";
	$r=$db->query($sql);
	$rs=$db->fetch_object($r);
	print "<tr>";
		print "<td>Delegacion o Municipio</td>";
		print "<td>".$rs->emisor_delompio."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Colonia</td>";
		print "<td>".$rs->emisor_colonia."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Calle</td>";
		print "<td>".$rs->emisor_calle."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>No. EXT.</td>";
		print "<td>".$rs->emisor_noext."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>No. INT.</td>";
		print "<td>".$rs->emisor_noint."</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Password para Timbrar</td>";
		print "<td>".$rs->password_timbrado_txt."</td>";
	print "</tr>";
print "</table>";

if($conf->global->MAIN_INFO_SIREN){
	$sql="SELECT COUNT(*) as exist 
	FROM ".MAIN_DB_PREFIX."cfdimx_emisores_datacom 
	WHERE rfc='".$conf->global->MAIN_INFO_SIREN."' AND entity_id=".$conf->entity;
	$rd=$db->query($sql);
	$rn=$db->fetch_object($rd);
	if($rn->exist==0){
		$sql="SELECT count(*) as exist
			FROM ".MAIN_DB_PREFIX."cfdimx_emisores_datacom
			WHERE entity_id=".$conf->entity." AND predeterminado=1";
		$df=$db->query($sql);
		$fd=$db->fetch_object($df);
		$predeterminado=0;
		if($fd->exist==0){
			$predeterminado=1;
		}
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_emisores_datacom (rfc, regimen, razon_social, 
				pais, estado, codigo_postal, 
				delompio, colonia, calle, 
				noext, noint, entity_id, password_timbrado, 
				password_timbrado_txt, formato_cfdi, modo_timbrado, 
				config_seriefolio, status_conf,predeterminado)
	VALUES('".$conf->global->MAIN_INFO_SIREN."', '".$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE."', '".$conf->global->MAIN_INFO_SOCIETE_NOM."'
		 , '".$conf->global->MAIN_INFO_SOCIETE_COUNTRY."', '".$conf->global->MAIN_INFO_SOCIETE_STATE."', '".$conf->global->MAIN_INFO_SOCIETE_ZIP."'
		 , '".$rs->emisor_delompio."', '".$rs->emisor_colonia."', '".$rs->emisor_calle."'
		 , '".$rs->emisor_noext."', '".$rs->emisor_noint."', '".$conf->entity."' ,'".md5($rs->password_timbrado_txt)."'
		 , '".$rs->password_timbrado_txt."', 'standard', '1'
		 , '1', '1','".$predeterminado."')";
		//print $sql;
		$fd=$db->query($sql);
	}
}

if($action=='add'){
	$sql="SELECT count(*) as exist
			FROM llx_cfdimx_emisores_datacom
			WHERE entity_id=".$conf->entity." AND rfc='".GETPOST('rfc')."'";
	$td=$db->query($sql);
	$tg=$db->fetch_object($td);
	if($tg->exist==0){
	$sql="SELECT count(*) as exist
		FROM ".MAIN_DB_PREFIX."cfdimx_emisores_datacom
		WHERE entity_id=".$conf->entity." AND predeterminado=1";
	$tf=$db->query($sql);
	$tr=$db->fetch_object($tf);
	$predet=0;
	if($tr->exist==0){
		$predet=1;
	}else{
		$predet=0;
	}
	$tmparray=getCountry(GETPOST('country_id','int'),'all',$db,$langs,0);
	$country_id   =$tmparray['id'];
	$country_code =$tmparray['code'];
	$country_label=$tmparray['label'];
	$s=$country_id.':'.$country_code.':'.$country_label;
	
	$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_emisores_datacom (rfc, regimen, razon_social, pais, estado, 
			codigo_postal,delompio, colonia, calle, 
			noext, noint, entity_id, password_timbrado, password_timbrado_txt, 
			formato_cfdi, modo_timbrado, config_seriefolio, status_conf, predeterminado) 
		  VALUES('".GETPOST('rfc')."','".GETPOST('forme_juridique_code')."','".GETPOST('razonsoc')."','".$s."','".GETPOST('state_id')."'
		  		,'".GETPOST('cgpostal')."','".GETPOST('delompio')."','".GETPOST('colonia')."','".GETPOST('calle')."'
		  		,'".GETPOST('noext')."','".GETPOST('noint')."','".$conf->entity."','".md5(GETPOST('passwt'))."','".GETPOST('passwt')."'
		  		,'standard','1','1','1','".$predet."')";
	$tf=$db->query($sql);
	print "<script>window.location='".$_SERVER["PHP_SELF"]."?mod=emisores';</script>";
	}else{
		print "<p style='color:red;'>Error el RFC que ingreso ya se encuentra registrado.</p>";
	}
}

print "<br>";
if(GETPOST('country_id')){
	$varc=GETPOST('country_id');
}else{
	$varc=$mysoc->country_id;
}
if(GETPOST('forme_juridique_code')){
	$vfomj=GETPOST('forme_juridique_code');
}else{
	$vfomj=$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
}
$tmparray=getCountry($varc,'all',$db,$langs,0);
$country_code =$tmparray['code'];
print "<table class='noborder' width='100%'><form method='POST' action='".$_SERVER["PHP_SELF"]."?mod=emisores&action=add'>";
	print "<tr class='liste_titre'>";
		print "<td colspan='2'>Nuevo Emisor</td></tr>";
	print "<tr>";
		print "<td width='30%'>RFC</td>";
		print "<td><input type='text' name='rfc' required></td>";
	print "</tr>";
	print "<tr>";
		print "<td>Regimen</td>";
		print "<td>";
		print $formcompany->select_juridicalstatus($vfomj,$mysoc->country_code);
		print "</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Razon Social</td>";
		print "<td><input type='text' name='razonsoc' required></td>";
	print "</tr>";
	print "<tr>";
		print "<td>Pais</td>";
		print "<td>";
		print $form->select_country($varc,'country_id',' onchange=" window.location =\' '.$_SERVER["PHP_SELF"].'?mod=emisores&country_id=\'+this.options[this.selectedIndex].value"');
		print "</td>";
	print "</tr>";
	print "<tr>";
		print "<td>Estado</td>";
		print "<td><div id='estdiv' name='estdiv'>";
		$formcompany->select_departement($conf->global->MAIN_INFO_SOCIETE_STATE,$country_code,'state_id');
		print "</div></td>";
	print "</tr>";
	print "<tr>";
		print "<td>Codigo Postal</td>";
		print "<td><input type='text' name='cgpostal' required></td>";
	print "</tr>";
	print "<tr>";
		print "<td>Delegacion o Municipio</td>";
		print "<td><input type='text' name='delompio' required></td>";
	print "</tr>";
	print "<tr>";
		print "<td>Colonia</td>";
		print "<td><input type='text' name='colonia' required></td>";
	print "</tr>";
	print "<tr>";
		print "<td>Calle</td>";
		print "<td><input type='text' name='calle' required></td>";
	print "</tr>";
	print "<tr>";
		print "<td>No. EXT.</td>";
		print "<td><input type='text' name='noext' required></td>";
	print "</tr>";
	print "<tr>";
		print "<td>No. INT.</td>";
		print "<td><input type='text' name='noint' ></td>";
	print "</tr>";
	print "<tr>";
		print "<td>Password para Timbrar</td>";
		print "<td><input type='text' name='passwt' required></td>";
	print "</tr>";
	print "<tr>";
		print "<td colspan='2' align='center'><input type='submit' value='Registrar'></td>";
	print "</tr>";
print "</form></table>";

if($action=='delete'){
	$sql="SELECT predeterminado
	FROM ".MAIN_DB_PREFIX."cfdimx_emisores_datacom
	WHERE entity_id=".$conf->entity." AND rowid=".GETPOST("id");
	$qt=$db->query($sql);
	$qw=$db->fetch_object($qt);
	if($qw->predeterminado==1){
		print "<p style='color:red;'>Error no puede eliminar la Razon social predeterminada.</p>";
	}else{
		$sql="DELETE FROM ".MAIN_DB_PREFIX."cfdimx_emisores_datacom WHERE rowid=".GETPOST("id");
		$qt=$db->query($sql);
		print "<script>window.location='".$_SERVER["PHP_SELF"]."?mod=emisores';</script>";
	}
}
if($action=='predeterminado'){
	$sql="SELECT rowid, rfc, regimen, razon_social, pais, estado, codigo_postal, delompio, colonia,
			calle, noext, noint, password_timbrado, password_timbrado_txt, formato_cfdi, modo_timbrado,
			config_seriefolio, status_conf, predeterminado
	FROM ".MAIN_DB_PREFIX."cfdimx_emisores_datacom
	WHERE entity_id=".$conf->entity." AND rowid=".GETPOST("rfcdet");
	//print $sql."<br>";
	$qt=$db->query($sql);
	$qw=$db->fetch_object($qt);
	
	$sql="SELECT count(*) as exist
		FROM ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp
		WHERE emisor_rfc='".$qw->rfc."' AND entity_id=1";
	//print $sql."<br>";
	$cs=$db->query($sql);
	$cc=$db->fetch_object($cs);
	if($cc->exist==0){
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp (emisor_rfc, emisor_delompio, 
				emisor_colonia, emisor_calle, emisor_noext, emisor_noint, entity_id) 
			  VALUES('".$qw->rfc."','".$qw->delompio."','".$qw->colonia."','".$qw->calle."'
			  		,'".$qw->noext."','".$qw->noint."','".$conf->entity."')";
		//print $sql."<br>";
		$cs=$db->query($sql);
	}
	
	$sql="SELECT count(*) as exist
		FROM ".MAIN_DB_PREFIX."cfdimx_config
		WHERE emisor_rfc='".$qw->rfc."' AND entity_id=".$conf->entity;
	//print $sql."<br>";
	$cs=$db->query($sql);
	$cc=$db->fetch_object($cs);
	if($cc->exist==0){
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_config (emisor_rfc, password_timbrado, password_timbrado_txt, 
				formato_cfdi, modo_timbrado, config_seriefolio, status_conf, entity_id)
			  VALUES('".$qw->rfc."','".$qw->password_timbrado."','".$qw->password_timbrado_txt."','".$qw->formato_cfdi."'
			  		,'".$qw->modo_timbrado."','".$qw->config_seriefolio."','".$qw->status_conf."','".$conf->entity."')";
		//print $sql."<br>";
		$cs=$db->query($sql);
	}else{
		$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_config
			SET modo_timbrado=1
			WHERE emisor_rfc='".$qw->rfc."' AND entity_id=".$conf->entity ;
		//print $sql."<br>";
		$cs=$db->query($sql);
	}
	
	
	$sql="SELECT count(*) as exist
		FROM ".MAIN_DB_PREFIX."cfdimx_config_ws
		WHERE emisor_rfc='".$qw->rfc."' AND entity_id=".$conf->entity;
	//print $sql."<br>";
	$cs=$db->query($sql);
	$cc=$db->fetch_object($cs);
	if($cc->exist==0){
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_config_ws (emisor_rfc, ws_modo_timbrado, ws_pruebas, ws_produccion, 
				ws_status_conf, entity_id)
			  VALUES('".$qw->rfc."','1'
			  		,'http://www.cfdiauribox.com/PruebasCFDI/services/ServicioTimbrado1?wsdl'
			  		,'http://www.cfdiauribox.com/TimbraCFDI/services/ServicioTimbrado1?wsdl'
			  		,'','".$conf->entity."')";
		//print $sql."<br>";
		$cs=$db->query($sql);
	}else{
		$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_config_ws
			SET ws_modo_timbrado=1
			WHERE emisor_rfc='".$qw->rfc."' AND entity_id=".$conf->entity ;
		//print $sql."<br>";
		$cs=$db->query($sql);
	}
	$sql="UPDATE ".MAIN_DB_PREFIX."const
			SET value='http://www.cfdiauribox.com/TimbraCFDI/services/ServicioTimbrado1?wsdl'
			WHERE name='MAIN_MODULE_CFDIMX_WS' AND entity=".$conf->entity ;
	//print $sql."<br>";
	$cs=$db->query($sql);
	
	$sql="UPDATE ".MAIN_DB_PREFIX."const
			SET value='".$qw->rfc."'
			WHERE name='MAIN_INFO_SIREN' AND entity=".$conf->entity ;
	//print $sql."<br>";
	$cs=$db->query($sql);
	
	$sql="UPDATE ".MAIN_DB_PREFIX."const
			SET value='".$qw->regimen."'
			WHERE name='name='MAIN_INFO_SOCIETE_FORME_JURIDIQUE' AND entity=".$conf->entity ;
	//print $sql."<br>";
	$cs=$db->query($sql);
	
	$sql="UPDATE ".MAIN_DB_PREFIX."const
			SET value='".$qw->razon_social."'
			WHERE name='MAIN_INFO_SOCIETE_NOM' AND entity=".$conf->entity ;
	//print $sql."<br>";
	$cs=$db->query($sql);
	
	$sql="UPDATE ".MAIN_DB_PREFIX."const
			SET value='".$qw->pais."'
			WHERE name='MAIN_INFO_SOCIETE_COUNTRY' AND entity=".$conf->entity ;
	//print $sql."<br>";
	$cs=$db->query($sql);
	
	$sql="UPDATE ".MAIN_DB_PREFIX."const
			SET value='".$qw->estado."'
			WHERE name='MAIN_INFO_SOCIETE_STATE' AND entity=".$conf->entity ;
	//print $sql."<br>";
	$cs=$db->query($sql);
	
	$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_emisores_datacom
			SET predeterminado=0 
			WHERE entity_id=".$conf->entity ;
	//print $sql."<br>";
	$cs=$db->query($sql);
	
	$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_emisores_datacom
			SET predeterminado=1
			WHERE rfc='".$qw->rfc."' AND entity_id=".$conf->entity ;
	//print $sql."<br>";
	$cs=$db->query($sql);
	
	print "<script>window.location='".$_SERVER["PHP_SELF"]."?mod=emisores';</script>";
}
print "<br>";
print "<table class='noborder' width='100%'><form method='POST' action='".$_SERVER["PHP_SELF"]."?mod=emisores&action=predeterminado'>";
	print "<tr class='liste_titre'>";
		print "<td colspan='4'>Lista Emisores</td></tr>";
	$sql="SELECT rowid, rfc, regimen, razon_social, pais, estado, codigo_postal, delompio, colonia, 
			calle, noext, noint, password_timbrado, password_timbrado_txt, formato_cfdi, modo_timbrado, 
			config_seriefolio, status_conf, predeterminado
	FROM ".MAIN_DB_PREFIX."cfdimx_emisores_datacom
	WHERE entity_id=".$conf->entity;
	$qt=$db->query($sql);
	$x=0;
	while($qw=$db->fetch_object($qt)){
		if($x==0){
			$a=" class='pair' ";
			$x=1;
		}else{
			$a=" class='impair' ";
			$x=0;
		}
		print "<tr ".$a.">";
			print "<td rowspan='12' align='center'>";
			if($qw->predeterminado==1){
				print "<input type='radio' name='rfcdet' value='".$qw->rowid."' checked>";
			}else{
				print "<input type='radio' name='rfcdet' value='".$qw->rowid."'>";
			}
			print "</td>";
			print "<td width='30%'><strong>RFC</strong></td>";
			print "<td><strong>".$qw->rfc."</strong></td>";
			print "<td rowspan='12' align='center'>";
			print "<input type='button' value='Eliminar' onclick='window.location.href=\"".$_SERVER["PHP_SELF"]."?mod=emisores&action=delete&id=".$qw->rowid."\"'>";
			print "</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>Regimen</td>";
			$regimen = getFormeJuridiqueLabel($qw->regimen);
			print "<td>".$regimen."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>Razon Social</td>";
			print "<td>".$qw->razon_social."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>Pais</td>";
			$tmp=explode(':',$qw->pais);
			$country_id=$tmp[0];
			$country_code=$tmp[1];
			$country=$tmp[2];
			print "<td>".$country."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>Estado</td>";
			$estado = getState($qw->estado);
			print "<td>".$estado."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>Codigo Postal</td>";
			print "<td>".$qw->codigo_postal."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>Delegacion o Municipio</td>";
			print "<td>".$qw->delompio."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>Colonia</td>";
			print "<td>".$qw->colonia."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>Calle</td>";
			print "<td>".$qw->calle."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>No. EXT.</td>";
			print "<td>".$qw->noext."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>No. INT.</td>";
			print "<td>".$qw->noint."</td>";
		print "</tr>";
		print "<tr ".$a.">";
			print "<td>Password para Timbrar</td>";
			print "<td>".$qw->password_timbrado_txt."</td>";
		print "</tr>";
	}
	print "<tr>";
		print "<td colspan='3' align='center'><input type='submit' value='Actualizar predeterminado'></td>";
	print "</tr>";
print "</form></table>";

}else{
	print "<p style='color:red;'>Debe realizar la configuracion inicial del modulo.</p>";
}