<?php
// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res) die("Include of main fails");
include 'ws_formselect.class.php';

$obj = new ws_formselect($db);
$obj->main_db_prefix = MAIN_DB_PREFIX;
$obj->emisorrfc = $conf->global->MAIN_INFO_SIREN;
$obj->entity=$_SESSION['dol_entity'];

$obj->fetch();

$obj->wsprod = isset($_REQUEST['wsprod'])?$_REQUEST['wsprod']:$obj->wsprod;
$obj->wspruebas = isset($_REQUEST['wspruebas'])?$_REQUEST['wspruebas']:$obj->wspruebas;
$obj->modo = isset($_REQUEST['modo'])?$_REQUEST['modo']:$obj->modo;

$action = $_REQUEST['action'];
$msg='';

$varmensaje=$_GET['varmensaje'];
if($varmensaje=='save'){
	print "<a style='font-size:15px'>&nbsp;&nbsp; Guardado exitosamente...</a>";
}else{
	if($varmensaje=='act'){
		print "<a style='font-size:15px'>&nbsp;&nbsp; Actualizado exitosamente...</a>";
	}
}
if($action == 'save'){
	if(!empty($obj->wspruebas) && !empty($obj->wsprod)){
		$res_up = $obj->update();
		if($res_up){
			// inserta en tabla llx_const
			$res_up = $obj->update_const();
			if ($res_up) {
				//echo '<script>alert("Guardado correctamente");window.location.href="ws_form_select.php"</script>';
				//echo '<script>alert("Guardado correctamente");window.opener.location.reload(); window.close();</script>';
				header("Location: ".$_SERVER["PHP_SELF"]."?varmensaje=save");
			}else{
				print 'Error al actualizar const:'.$res_up;
				//header("Location: ".$_SERVER["PHP_SELF"]);
			}
		}else{
			print 'Error al guardar'.$res_up;
		}
	}else{
		print '<script>alert("Falta ingresar la url del web services")</script>';
	}
	
}elseif($action == 'update'){
	 
	 $res_up = $obj->update();
	 
	if($res_up){
		//print '<script>alert("Actualizado"); window.location.href="ws_form_select.php"</script>';
		header("Location: ".$_SERVER["PHP_SELF"]."?varmensaje=act");
	}else{
		print 'Error al actualizar:'.$res_up;
	}
}

$var = true;

function llxMainArea($head, $title, $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss=''){

	top_htmlhead($head, $title, $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='');
	main_area($title);
}

llxMainArea('','View','');



print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2" align="center">WebServices info</td>';
print '</tr>';
print '<tr>';
print '<td width="25%">RFC:</td>';
print '<td>'.$conf->global->MAIN_INFO_SIREN.'</td>';
print '</tr>';
	print '<form method="post">';
	print '<input type="hidden" name="action" value="save">';
	print '<tr>';
	print '<td width="25%">Webservice Produccion:</td>';
	print '<td>';
	
	if ($action == 'editconditionsprod'){
		print '<input type="hidden" name="action" value="update">';
		print '<input type="text" id="wsprod" name="wsprod" size="85" value="'.$obj->wsprod.'">';
		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	}else{
		print '<input type="hidden" id="wsprod" name="wsprod" size="85" value="'.$obj->wsprod.'">';
		print isset($obj->wsprod)?$obj->wsprod:null;		
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=editconditionsprod">'.img_edit($langs->trans('SetConditions'),1).'</a>';
	}
	
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td width="25%">Webservice Pruebas:</td>';
	print '<td>';
	
	if ($action == 'editconditionspruebas'){
		print '<input type="hidden" name="action" value="update">';
		print '<input type="text" id="wspruebas" name="wspruebas" size="85" value="'.$obj->wspruebas.'">';
		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	}else{
		print '<input type="hidden" id="wspruebas" name="wspruebas" size="85" value="'.$obj->wspruebas.'">';
		print isset($obj->wspruebas)?$obj->wspruebas:null;
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=editconditionspruebas">'.img_edit($langs->trans('SetConditions'),1).'</a>';
	}	
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td width="25%">Modo:</td>';	
	print '<td>';
	if(isset($obj->modo)){if ($obj->modo == 1){$modov='Produccion';}elseif($obj->modo == 2){$modov='Pruebas';}}
	print '<select name="modo">';
	print '<option value="'.$obj->modo.'">'.$modov.'</option>';
    print '<option value="2">Pruebas</option>';
    print '<option value="1">Produccion</option>';
    print '</select>';
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td width="25%"></td>';
	print '<td>';
	print '&nbsp;';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;';
	$msg_close = "'cerrar'";
	print '<input type="button" class="button" id="cerrar" name="cerrar" onclick="window.opener.location.reload(); window.close();" value="Cerrar">';
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td width="25%"></td>';
	print '<td>';
	print '</td>';
	print '</tr>';	
	print '</form>';

print '</table>';

?>
