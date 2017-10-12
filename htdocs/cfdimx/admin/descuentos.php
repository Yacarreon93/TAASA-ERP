<?php
global $db, $conf;

$sql="SELECT count(*) as exist
FROM ".MAIN_DB_PREFIX."cfdimx_descuentos
WHERE entity_id=".$conf->entity;
$rqs=$db->query($sql);
$rs=$db->fetch_object($rqs);
if($rs->exist==0){
	$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_descuentos(entity_id,mostrar) VALUES(".$conf->entity.",1)";
	$rqs=$db->query($sql);
}
$sql="SELECT *
FROM ".MAIN_DB_PREFIX."cfdimx_descuentos
WHERE entity_id=".$conf->entity;
$rqs=$db->query($sql);
$rs=$db->fetch_object($rqs);
$a='';
$b='';
if($rs->mostrar==1){
	$a='SELECTED';
}else{
	$b='SELECTED';
}
$action=GETPOST('action');
if($action=='actualdesc'){
	$valmostrar=GETPOST('valmostrar');
	$sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_descuentos
			SET mostrar=".$valmostrar." WHERE entity_id=".$conf->entity;
	$rqs=$db->query($sql);
	print "<script>window.location.href='cfdimx.php?mod=descuentos'</script>";
}
?>
<form method="post" action="cfdimx.php?mod=descuentos&action=actualdesc">
<table class="noborder" width="100%">
	<tr class="liste_titre">
		<td colspan="3" align="center">Mostrar descuentos en PDF y XML</td>
	</tr>
	<tr>
		<td>Mostrar</td>
		<td>
			<select name='valmostrar'>
				<option value="1" <?=$a?>>Si</option>
				<option value="2" <?=$b?>>No</option>
			</select>
		</td>
		<td><input type="submit" value="Actualizar"></td>
	</tr>
</table>
</form>