<?php

if (! empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY))
{
    $tmp=explode(':',$conf->global->MAIN_INFO_SOCIETE_COUNTRY);
    $country_id=$tmp[0];
    if (! empty($tmp[1]))
    {
        $country_code=$tmp[1];
        $country=$tmp[2];
    }
    else
    {
        $tmparray=getCountry($country_id,'all');
        $country_code=$tmparray['code'];
        $country=$tmparray['label'];
    }
}
else
{
    $country_id=0;
    $country_code='';
    $country='';
}

$v_rfc = 0;
$v_rsocial = 0;
$v_regimen = 0;
$v_cp = 0;
$v_edo = 0;
$v_pais = 0;
$v_col = 0;

if( isset($conf->global->MAIN_INFO_SIREN) ){
	$rfc_emisor = $conf->global->MAIN_INFO_SIREN;
	$v_rfc = 1;
}else{ $rfc_emisor = img_warning() . ' ' . '<font class="error">Dato requerido para CFDI</font>'; }

if( isset($conf->global->MAIN_INFO_SOCIETE_NOM) ){
	$razon_social_emisor = $conf->global->MAIN_INFO_SOCIETE_NOM;
	$v_rsocial = 1;
}else{ $razon_social_emisor = img_warning() . ' ' . '<font class="error">Dato requerido para CFDI</font>'; }

if( $conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE!=0 ){
	$regimen = getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
	$v_regimen = 1;
}else{ $regimen = img_warning() . ' ' . '<font class="error">Dato requerido para CFDI</font>'; }

if( $country_code ){
	$pais = $country_code;
	$v_pais = 1;
}else{ $pais = img_warning() . ' ' . '<font class="error">Dato requerido para CFDI</font>'; }

if( $conf->global->MAIN_INFO_SOCIETE_STATE ){
	$estado = getState($conf->global->MAIN_INFO_SOCIETE_STATE);
	$v_edo = 1;
}else{ $estado = img_warning() . ' ' . '<font class="error">Dato requerido para CFDI</font>'; }

if( $conf->global->MAIN_INFO_SOCIETE_ZIP ){
	$cp = $conf->global->MAIN_INFO_SOCIETE_ZIP;
	$v_cp = 1;
}else{ $cp = img_warning() . ' ' . '<font class="error">Dato requerido para CFDI</font>'; }

if( $conf->global->MAIN_INFO_SOCIETE_TOWN ){
	$col = $conf->global->MAIN_INFO_SOCIETE_TOWN;
	$v_col = 1;
}else{ $col = img_warning() . ' ' . '<font class="error">Dato requerido para CFDI</font>'; }

if( $_REQUEST["save"]=="Guardar" ){
	if( $num_emisor_datacomp > 0 ){
		$db->begin();
				
		$qry_Chk = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp";
		$qry_Chk .= " WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."'";
		
		$res_Chk = $db->query($qry_Chk);
		
		if($res_Chk){
			$obj_Chk = $db->fetch_object($res_Chk);
			if(is_null($obj_Chk->entity_id)){
				$upd_Chk = "
					UPDATE  ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp SET
						entity_id = '".$_SESSION['dol_entity']."'
					WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."'";				
				$res_upd_Chk = $db->query($upd_Chk);	
				if($res_upd_Chk){
					print 'Se actualizo campo entity';
				}else{
					print 'Problemas al actualizar';
				}			
			}				
		}else{
			print 'Error query';
		}
		$update = "
		UPDATE  ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp SET 
			emisor_delompio = '".$_REQUEST["delmpio"]."',
			cod_municipio = '".$_REQUEST["codmuni"]."',
			emisor_colonia = '".$_REQUEST["colonianw"]."',
			emisor_calle = '".$_REQUEST["calle"]."',
			emisor_noext = '".$_REQUEST["noext"]."',
			emisor_noint = '".$_REQUEST["noint"]."'
		WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."'
		AND entity_id = '".$_SESSION['dol_entity']."'";
		
		$db->query($update);
		$rescomm = $db->commit();
		//if( $rescomm==3 ){	
				
			echo '
			<script>
				location.href="?mod=dataEmisor&statusSend=ok";
			</script>';
			
		//}
	}else{
		$insert = "
		INSERT INTO  ".MAIN_DB_PREFIX."cfdimx_emisor_datacomp (
			emisor_rfc,
			emisor_delompio,
			emisor_calle,
			emisor_noext,
			emisor_noint,
			emisor_colonia,
			entity_id,
			cod_municipio
		) VALUES (
			'".$conf->global->MAIN_INFO_SIREN."',
			'".$_REQUEST["delmpio"]."',
			'".$_REQUEST["calle"]."',
			'".$_REQUEST["noext"]."',
			'".$_REQUEST["noint"]."',
			'".$_REQUEST["colonianw"]."',
			'".$_SESSION['dol_entity']."',
			'".$_REQUEST["codmuni"]."'
		)";
		
		$db->begin();
		$db->query($insert);
		$rescomm = $db->commit();
		if( $rescomm==1 ){
			
			echo '
			<script>
				location.href="?mod=dataEmisor&statusSend=ok";
			</script>';
			
		}
	}
	
	
}

print_titre("Datos del Emisor");

$var=true;
?>
<script type="text/javascript">
function valida_comp_dataemisor(){
	if( comp_dataemisor.delmpio.value.length < 5 ){
		alert("Ingrese la Delegación o Municipio de su dirección fiscal \n Debe contener al menos 4 dígitos");
		return false;
	}
	if( comp_dataemisor.calle.value.length < 1 ){
		alert("Ingrese la Calle de su dirección fiscal");
		return false;
	}
	if( comp_dataemisor.noext.value.length < 1 ){
		alert("Ingrese el Número Exterior de su dirección fiscal");
		return false;
	}
	return true
}
</script>

<table class="noborder" width="100%">
  <tr class="liste_titre">
  	<td colspan="2" align="center">Datos Generales</td>
  </tr>
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td width="40%" class="fieldrequired">RFC</td>
    <td><?=$rfc_emisor?><?=' '.info_admin('Para modifical el valor de este campo podrá realizarlo en el módulo de Empresa/Institución en el campo: R.F.C.',1)?></td>
  </tr>
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">Regimen</td>
    <td><?=$regimen?><?=' '.info_admin('Para modifical el valor de este campo podrá realizarlo en el módulo de Empresa/Institución en el campo: Forma jurídica',1)?></td>
  </tr>
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">Razón Social</td>
    <td><?=$razon_social_emisor?><?=' '.info_admin('Para modifical el valor de este campo podrá realizarlo en el módulo de Empresa/Institución en el campo: Nombre/Razón social',1)?></td>
  </tr>

  <tr class="liste_titre">
  	<td colspan="2" align="center">Dirección Fiscal</td>
  </tr>
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">País</td>
    <td>
    <?php
    if ($pais)
    {
        $img=picto_from_langcode($pais);
        print $img?$img.' ':'';
        print getCountry($pais,1);
    }
	?>
    <?=' '.info_admin('Para modifical el valor de este campo podrá realizarlo en el módulo de Empresa/Institución en el campo: País',1)?>
    </td>
  </tr>
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">Estado</td>
    <td><?=$estado?>
    <?=' '.info_admin('Para modifical el valor de este campo podrá realizarlo en el módulo de Empresa/Institución en el campo: Estado',1)?>
    </td>
  </tr>
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">Código Postal</td>
    <td><?=$cp?><?=' '.info_admin('Para modifical el valor de este campo podrá realizarlo en el módulo de Empresa/Institución en el campo: Código Postal',1)?></td>
  </tr>
  <!--
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">Delegación o Municipio</td>
    <td><?=$col?><?=' '.info_admin('Para modifical el valor de este campo podrá realizarlo en el módulo de Empresa/Institución en el campo: Población',1)?></td>
  </tr>
  -->
  <form method="post" name="comp_dataemisor" onsubmit="return valida_comp_dataemisor();">
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">Delegación o Municipio</td>
    <td><input name="delmpio" size="60" value="<?=$emisor_delompio?>"></td>
  </tr>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">Codigo del Municipio</td>
    <td><input name="codmuni" size="60" value="<?=$emisor_cod_municipio?>"></td>
  </tr>

  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">Colonia</td>
    <td><input name="colonianw" size="60" value="<?=$emisor_colonianw?>"></td>
  </tr>
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">Calle</td>
    <td><input name="calle" size="60" value="<?=$emisor_calle?>"></td>
  </tr>
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td class="fieldrequired">No. Exterior</td>
    <td><input name="noext" size="20" value="<?=$emisor_noext?>"></td>
  </tr>
  <? $var=!$var; ?>
  <tr <?=$bc[$var]?>>
  	<td>No. Interior</td>
    <td><input name="noint" size="20" value="<?=$emisor_noint?>"></td>
  </tr>
  <tr <?=$bc[$var]?>>
  	<td colspan="2">&nbsp;</td>
  </tr>
  <?
  if( $v_rfc == 1 && $v_rsocial == 1 && $v_regimen == 1 && $v_cp == 1 && $v_edo == 1 && $v_pais == 1){
  ?>
  <tr <?=$bc[$var]?>>
  	<td colspan="2" align="center"><input type="submit" class="button" name="save" value="Guardar"></td>
  </tr>
  <tr <?=$bc[$var]?>>
  	<td colspan="2">&nbsp;</td>
  </tr>
  <?
  }else{
  ?>
  <tr <?=$bc[$var]?>>
  	<td colspan="2" align="center"><?=img_warning() . ' ' . '<font class="error">Debe completar todos los campos marcados para guardar cambios</font>';?></td>
  </tr>
  <tr <?=$bc[$var]?>>
  	<td colspan="2">&nbsp;</td>
  </tr>
  <?
  }
  ?>
  <input type="hidden" name="emisor_rfc" value="<?=$conf->global->MAIN_INFO_SIREN?>" />
  </form>
</table>

<?php
if($_REQUEST["statusSend"]=="ok"){
	echo '
	<script>
	 alert("Registro actualizado de manera satisfactoria");
	</script>';
}
?>