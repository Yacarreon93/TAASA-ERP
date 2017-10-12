<?php
global $db, $conf;
if((GETPOST('webprod') || GETPOST('webprueba')) && GETPOST('modotimbrado')!=''){
   //print GETPOST('webprod').'<br>';
	$sql="SELECT count(*) as exist FROM ".MAIN_DB_PREFIX."cfdimx_config_ws WHERE emisor_rfc='".$conf->global->MAIN_INFO_SIREN."'
		AND entity_id=".$conf->entity;
	$rs=$db->query($sql);
	$rss=$db->fetch_object($rs);
	if($rss->exist>0){
		$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_config_ws SET ws_modo_timbrado=".GETPOST('modotimbrado').",
				ws_pruebas='".GETPOST('webprueba')."',ws_produccion='".GETPOST('webprod')."'
				WHERE emisor_rfc='".$conf->global->MAIN_INFO_SIREN."'";
		//print $sql.'<br>';
		$rs=$db->query($sql);
		if(GETPOST('modotimbrado')==1){
			$ws=GETPOST('webprod');
		}
		if(GETPOST('modotimbrado')==2){
			$ws=GETPOST('webprueba');
		}
		//SELECT value FROM llx_const WHERE name='MAIN_MODULE_CFDIMX_WS'
		$sql  = ' UPDATE '.MAIN_DB_PREFIX.'const SET ';
		$sql .= ' value = "'.$ws.'"';
		$sql .= ' WHERE name = "MAIN_MODULE_CFDIMX_WS"';
		$sql .= ' AND entity = '.$conf->entity;
		$rs=$db->query($sql);
	}else{
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_config_ws (emisor_rfc,ws_modo_timbrado,ws_pruebas,ws_produccion,
				ws_status_conf,entity_id) 
			  VALUES('".$conf->global->MAIN_INFO_SIREN."',".GETPOST('modotimbrado').",'".GETPOST('webprueba')."',
			  		'".GETPOST('webprod')."','',".$conf->entity.")";
		//print $sql.'<br>';
		$rs=$db->query($sql);
		if(GETPOST('modotimbrado')==1){
			$ws=GETPOST('webprod');
		}
		if(GETPOST('modotimbrado')==2){
			$ws=GETPOST('webprueba');
		}
		$sql  = 'INSERT INTO '.MAIN_DB_PREFIX.'const ';
		$sql .= ' (name, entity, value';
		$sql .= '  , type, visible, note)';
		$sql .= ' VALUES("MAIN_MODULE_CFDIMX_WS", "'.$conf->entity.'", "'.$ws.'"';
		$sql .= ' , "chaine", "1", "ws cfdimx")';
		//print $sql;	
		$rs=$db->query($sql);
	}
// 	$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_config 
// 		SET modo_timbrado=".GETPOST('modotimbrado')."
// 		WHERE emisor_rfc='".$conf->global->MAIN_INFO_SIREN."' AND entity_id=".$conf->entity;
// 	$rs=$db->query($sql);
	print "<script>window.location='".$_SERVER["PHP_SELF"]."?mod=config';</script>";
}

$sql="SELECT count(*) as exist FROM ".MAIN_DB_PREFIX."cfdimx_config_ws WHERE emisor_rfc='".$conf->global->MAIN_INFO_SIREN."' 
		AND entity_id=".$conf->entity;
$rs=$db->query($sql);
$rss=$db->fetch_object($rs);
$a='';
$b='';
$c='';
if($rss->exist>0){
	$sql="SELECT emisor_rfc,ws_modo_timbrado,ws_pruebas,ws_produccion FROM ".MAIN_DB_PREFIX."cfdimx_config_ws 
			WHERE emisor_rfc='".$conf->global->MAIN_INFO_SIREN."'
			AND entity_id=".$conf->entity;
	//print $sql.'<br>';
	$rs=$db->query($sql);
	$rss=$db->fetch_object($rs);
	$a=$rss->ws_produccion;
	$b=$rss->ws_pruebas;
	$c=$rss->ws_modo_timbrado;
}else{
	$a='';
	$b='';
	$c='';
}
print '<table class="noborder" width ="100%">';
print '<tr class="liste_titre">';
	print '<td align="center" colspan="2">';
		print 'Configuracion del Webservice';
	print '</td>';
print '</tr>';
print '<tr class="pair">';
	print '<td  width="25%">';
		print 'Webservice Produccion:';
	print '</td>';
	print '<td><form method="POST" action="'.$_SERVER["PHP_SELF"].'?mod=config">';
		print '<input type="text" name="webprod" id="webprod" size="70" value="'.$a.'">';
	print '</td>';
print '</tr>';
print '<tr class="impair">';
	print '<td  width="25%">';
		print 'Webservice Pruebas:';
	print '</td>';
	print '<td>';
		print '<input type="text"  name="webprueba" id="webprueba" size="70" value="'.$b.'">';
	print '</td>';
print '</tr>';
print '<tr class="pair">';
	print '<td  width="25%">';
		print 'Modo:';
	print '</td>';
	print '<td>';
	if($c==''){$d="SELECTED";$e='';$f='';}
	if($c==1){$d='';$e="SELECTED";$f='';}
	if($c==2){$d='';$e='';$f="SELECTED";}
		print '<select name="modotimbrado">
				<option value="" '.$d.'>--Seleccione--</option>
				 <option value="1" '.$e.'>Produccion</option>
				 <option value="2" '.$f.'>Pruebas</option>
			   </select>';
	print '</td>';
print '</tr>';
print '<tr class="imspair">';
	print '<td align="center" colspan="2">';
	print '<input type="submit" value="Guardar" class="button">';
	print '</form></td>';
print '</tr>';
print '</table><br>';


/* print '<table width ="100%">';
print '<tr>';
print '<td align="right">';
$ws_url_select = str_replace('cfdimx.php', 'ws_form_select.php', $_SERVER['PHP_SELF']);
print '<input type="button" onClick=window.open("'.$ws_url_select.'","ws_form_select","scrollbars=1,width=800,height=250"); id="ws_form_select" name="ws_form_select" class="button" value="WS conf">';
print '</td>';
print '</tr>';
print '</table>'; */


function checkURL($url){
     if(!filter_var($url, FILTER_VALIDATE_URL)) {
          return 0;
     }
     $curlInit = curl_init($url);
     curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 5); 
     curl_setopt($curlInit, CURLOPT_HEADER, true);
     curl_setopt($curlInit, CURLOPT_NOBODY, true);
     curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
     $response = curl_exec($curlInit);
     curl_close($curlInit); 
     if ($response) return 1;
     return 0;
}
$status_ws = checkURL($wscfdi);

if( $status_ws==1 ){
	$client = new nusoap_client($wscfdi, 'wsdl');
	$result = $client->call('validaCliente',array( "rfc"=>$conf->global->MAIN_INFO_SIREN ));
	
	$status_clt = $result["return"]["status_cliente_id"];
	$status_clt_desc = $result["return"]["status_cliente_desc"];
	$folios_timbrados = $result["return"]["folios_timbrados"];
	$folios_adquiridos = $result["return"]["folios_adquiridos"];
	$folios_disponibles = $result["return"]["folios_disponibles"];
}

//echo $_SESSION['dol_entity']."<----";
/*
$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."entity WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."'");
if ($resql){
	 $conf_num = $db->num_rows($resql);
	 $i = 0;
	 if ($conf_num){
		 while ($i < $conf_num){
			 $obj = $db->fetch_object($resql);
			 if ($obj){
				 $status_conf = $obj->status_conf;
				 $formato_cfdi = $obj->formato_cfdi;
				 $password_timbrado_txt = $obj->password_timbrado_txt;
				 $modo_timbrado = $obj->modo_timbrado;
				 $config_seriefolio = $obj->config_seriefolio;
			 }
			 $i++;
		 }
	 }
}
*/

if( $_REQUEST["reg"]=="Registrar" ){
		$result = $client->call('ValidaCliente',array( "emisorRFC"=>$conf->global->MAIN_INFO_SIREN ));
		if( $result["status_cliente_id"]==0 ){
			$regimen=getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
			$edo = getState($conf->global->MAIN_INFO_SOCIETE_STATE);
			$pais = getCountry($country_code,1);
			$rec_emisor = $client->call('RegistraCliente',
				array( 
					"emisorRFC"=>strtoupper($conf->global->MAIN_INFO_SIREN),
					"emisorRegimen"=>strtoupper($regimen),
					"emisorNombre"=>strtoupper($conf->global->MAIN_INFO_SOCIETE_NOM),
					"emisorCalle"=>strtoupper($emisor_calle),
					"emisorColonia"=>strtoupper($conf->global->MAIN_INFO_SOCIETE_TOWN),
					"emisorNoExterior"=>strtoupper($emisor_noext),
					"emisorNoInterior"=>strtoupper($emisor_noint),
					"emisorMunicipio"=>strtoupper($emisor_delompio),
					"emisorEstado"=>strtoupper($edo),
					"emisorCodigoPostal"=>strtoupper($conf->global->MAIN_INFO_SOCIETE_ZIP),
					"emisorPais"=>strtoupper($pais),
				)
			);
			if( $rec_emisor==1 ){
				/*
				$insert = "";
				$db->begin();
				$db->query($update);
				$rescomm = $db->commit();
				*/
				echo '
				<script>
				location.href="cfdimx.php?mod=config";
				</script>';
			}else{
				echo "No se registro en el webservice";
			}
		}else{
			echo "El cliente ya existe con el  status:".$result["status_cliente_id"];
		}
}

$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id = ". $_SESSION['dol_entity']);
if ($resql){
	 $conf_num = $db->num_rows($resql);
	 $i = 0;
	 if ($conf_num){
		 while ($i < $conf_num){
			 $obj = $db->fetch_object($resql);
			 if ($obj){
				 $status_conf = $obj->status_conf;
				 $formato_cfdi = $obj->formato_cfdi;
				 $password_timbrado_txt = $obj->password_timbrado_txt;
				 $modo_timbrado = $obj->modo_timbrado;
				 $config_seriefolio = $obj->config_seriefolio;
			 }
			 $i++;
		 }
	 }
 
	 else{
	 	$sql_Chk='';
	 	$sql_Chk="SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."'";
	 	$resql_Chk=$db->query($sql_Chk);
	 	$conf_num_Chk = $db->num_rows($resql_Chk);
	 	
	 	if($conf_num_Chk){	 		
	 		// Checa si el RFC existe en la tabla
	 		$obj_Chk = $db->fetch_object($resql_Chk);
	 		if($obj_Chk->entity_id == null || $obj_Chk->entity_id == "") {
	 		 // Si existe y pero el campo entity_id es null o vacio entonces hace un update
	 		 // agregandole dol_entity
	 		   ($_REQUEST["conf_rfc_emisor"])?$_REQUEST["conf_rfc_emisor"]:$conf->global->MAIN_INFO_SIREN;
	 		   
	 		   $rfc = $_REQUEST['conf_rfc_emisor']?$_REQUEST['conf_rfc_emisor']:$conf->global->MAIN_INFO_SIREN;
	 		   
	 		   $update_Chk = "
				UPDATE  ".MAIN_DB_PREFIX."cfdimx_config SET
							entity_id = '".$_SESSION['dol_entity']."'
				WHERE emisor_rfc = '".$rfc."'
				AND (entity_id = '' OR ISNULL(entity_id))";
	 			
	 			$result_Chk = $db->query( $update_Chk );
              	
					if($result_Chk){	 		
		 				echo "<script> location.href='?mod=config' </script>";
					}else{
						
						echo "<script> alert('Requiere Dar clien el Boton Guardar para Actualizar Tabla.'); </script>";
					}
			
	 		}
	 	}	 	
	 }
	 
}
//$error="Falta complementar datos para terminar la configuración";	
if( $status_conf==1 ){
	$error="Configuracion OK";
}

if( $_REQUEST["saveconf"]=="Guardar" ){
	if( $_REQUEST["conf_rfc_emisor"]!="" && $_REQUEST["formato_cfdi"] && $_REQUEST["passtimbrado"] && $_REQUEST["conf_modo"] && $_REQUEST["conf_seriefolio"] ){
		if( $conf_num==0 ){
			$insert = "
			INSERT INTO  ".MAIN_DB_PREFIX."cfdimx_config VALUES (
				'".$_REQUEST["conf_rfc_emisor"]."',
				'".md5($_REQUEST["passtimbrado"])."',
				'".$_REQUEST["passtimbrado"]."',
				'".$_REQUEST["formato_cfdi"]."',
				'".$_REQUEST["conf_modo"]."',
				'".$_REQUEST["conf_seriefolio"]."',
				1,
				'".$_SESSION['dol_entity']."'
			)";
			
			$db->query( $insert );
			echo "<script> location.href='?mod=config' </script>";
		}else{
			$update = "
			UPDATE  ".MAIN_DB_PREFIX."cfdimx_config SET 
				emisor_rfc = '".$_REQUEST["conf_rfc_emisor"]."',
				password_timbrado = '".md5($_REQUEST["passtimbrado"])."',
				password_timbrado_txt = '".$_REQUEST["passtimbrado"]."',
				formato_cfdi = '".$_REQUEST["formato_cfdi"]."',
				modo_timbrado = '".$_REQUEST["conf_modo"]."',
				config_seriefolio = '".$_REQUEST["conf_seriefolio"]."'
			WHERE emisor_rfc = '".$_REQUEST["conf_rfc_emisor"]."'
			AND entity_id = '".$_SESSION['dol_entity']."'";
			$db->query( $update );
			echo "<script> location.href='?mod=config' </script>";
		}
	}else{
		if( $_REQUEST["conf_rfc_emisor"]!="")
		echo "<script>alert('El RFC del emisor esta vacio')</script>";
		if( $_REQUEST["formato_cfdi"]!="")
		echo "<script>alert('Formato vacio')</script>";
		if( $_REQUEST["passtimbrado"]!="")
		echo "<script>alert('Password de timbrado vacio')</script>";
		if( $_REQUEST["conf_modo"]!="")
		echo "<script>alert('Modo de timbrado vacio')</script>";
		if( $_REQUEST["conf_seriefolio"]!="")
		echo "<script>alert('Serie y Folio vacio')</script>";
	}
}

$conf_emisor = valida_datos_emisor($conf, $num_emisor_datacomp);
if( $status_ws==1 ){
	if( $conf_emisor==1 ){
	$var = true;
	?>
	<table class="noborder" width="100%">
	  <tr class="liste_titre">
		<td colspan="2" align="center">Configuracion General</td>
	  </tr>
      <?php
      if( $error ){
	  ?>
	  <tr>
		<td colspan="2" align="center" style="font-size:16px; color:#C30"><strong><?=$error?></strong></td>
	  </tr>
      <?php
	  }
	  ?>
	  <? $var=!$var; ?>
	  <tr <?=$bc[$var]?>>
		<td width="25%">RFC:</td>
		<td><?=$conf->global->MAIN_INFO_SIREN?></td>
	  </tr>
	  <? $var=!$var; ?>
	  <tr <?=$bc[$var]?>>
		<td width="25%">Status:</td>
		<td><?=utf8_encode($status_clt_desc)?></td>
	  </tr>
	  <? $var=!$var; ?>
	  <tr <?=$bc[$var]?>>
		<td width="25%">URL Webservice:</td>
		<td>
		<?php print $conf->global->MAIN_MODULE_CFDIMX_WS; ?>
		</td>
	  </tr>	  
	  <? $var=!$var; ?>
	  <form method="post">
	  <tr <?=$bc[$var]?>>
		<td width="25%">Webservice:</td>
		<td>
		<?php if( $wscfdi=="" ){ echo 'Debe asignar valor a la variable <strong>$wscfdi</strong> en el archivo conf.php'; }else{ echo '<img src="palomita.gif" width="20">'; }?>
		</td>
	  </tr>
	  </form>
	  <?php
	  if($status_ws==1){
		  if( $status_clt==1 || $status_clt==2 || $status_clt==3 ){
		  ?>
		  <? $var=!$var; ?>
		  <tr <?=$bc[$var]?>>
			<td width="25%">Folios Disponibles:</td>
			<td><?=$folios_disponibles?></td>
		  </tr>
		  <? $var=!$var; ?>
		  <tr <?=$bc[$var]?>>
			<td width="25%">Folios Timbrados:</td>
			<td><?=$folios_timbrados?></td>
		  </tr>
		  <? $var=!$var; ?>
		  <tr <?=$bc[$var]?>>
			<td width="25%" valign="top">Adquirir Folios:</td>
			<td style="font-size:14px; color:#C30">
            <a href="http://facturacion.admin.auriboxenlinea.com/" target="_blank">Click Aqui�</a>
			</td>
		  </tr>
          <form method="post">
		  <? $var=!$var; ?>
		  <tr <?=$bc[$var]?>>
			<td width="25%">Password para Timbrar:</td>
			<td><input name="passtimbrado" type="password" size="40" value="<?=$password_timbrado_txt?>">
            </td>
		  </tr>
		  <? $var=!$var; ?>
		  <tr <?=$bc[$var]?>>
			<td width="25%">Formato de factura electronica:</td>
			<td>
            <select name="formato_cfdi">
            	<option value="standard">Formato Estandar</option>
            </select>
            </td>
		  </tr>          
		  <? $var=!$var; ?>
		  <tr <?=$bc[$var]?>>
			<td width="25%">Modo de Timbrado:</td>
			<td>
            <select name="conf_modo">
			  <? if( $status_clt==3 ){?>
              	<option value="">--Seleccione--</option>
                <option value="1" <?=getSelected( $modo_timbrado, 1 )?>>Produccion</option>
              	<option value="2" <?=getSelected( $modo_timbrado, 2 )?>>Pruebas</option>
              <? }elseif( $status_clt==2 ){
				  if( $modo_timbrado != $status_clt ){
				?>
                <option value="">--Seleccione--</option>
                <option value="2">Pruebas</option>
                <?  
				  }else{  
				  ?>
                  <option value="2" <?=getSelected( $modo_timbrado, $status_clt )?>>Pruebas</option>
                  <?
				  }
			  }elseif( $status_clt==1 ){
				  if( $modo_timbrado != $status_clt ){
				?>
                <option value="">--Seleccione--</option>
                <option value="1">Produccion</option>
                <?  
				  }else{  
				  ?>
                <option value="1" <?=getSelected( $modo_timbrado, $status_clt )?>>Produccion</option>
                <?  
				  }
			  }?>
              </select>
            </td>
		  </tr>          
		  <? $var=!$var; ?>
		  <tr <?=$bc[$var]?>>
			<td width="25%">Configuracion de Serie y Folio:</td>
			<td>
            <select name="conf_seriefolio">
            	<option value="1" selected="selected">Utilizar la configuracion de Dolibarr</option>
            </select>
            <?=info_admin('Para utilizar la configuracion de serie y folio actual de Dolibarr, debera tener separada la serie del folio con el caracter guion(-) Ejemplo: SERIE-FOLIO',1)?> <strong> &lArr; Nota Importante</strong>
            </td>
		  </tr>
          <? $var=!$var; ?>
		  <tr <?=$bc[$var]?>>
			<td colspan="2" align="center"><p>
            <input type="hidden" name="conf_rfc_emisor" value="<?=$conf->global->MAIN_INFO_SIREN?>" />
            <input name="saveconf" type="submit" value="Guardar" class="button">
            </p></td>
		  </tr>
          </form>
		  <?php
		  }
	  }else{
		?>
		  <? $var=!$var; ?>
		  <tr <?=$bc[$var]?>>
			<td colspan="2" align="center">Problemas de conexion con el Servicio Web</td>
		  </tr>    
		<?php  
	  }
	  ?>
	</table>
	<?php
	}else{
		echo img_warning().' '.'<font class="error">Los Datos del Emisor estan incompletos</font><br>';
		echo 'debera complementar esta informacion para continuar la configuracion del modulo';
	}
}else{
	echo img_warning().'<font class="error" align="center"> Problemas de conexion con el servicio web</font>';
}
?>