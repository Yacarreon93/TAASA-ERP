<?php
global $db, $conf;
if(GETPOST('action')=='add'){
	$ret=GETPOST('cod');
	$descripcion=GETPOST('descripcion');
	$tasa=GETPOST('tasa');
	$sql="SELECT rowid, cod,descripcion,tasa FROM ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales 
			WHERE cod='".$ret."' AND entity=".$conf->entity ;
	//print $sql."<br>";
	$rqes=$db->query($sql);
	$nrow=$db->num_rows($rqes);
	if($nrow==0){
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales(cod,descripcion,tasa,entity)
				VALUES('".$ret."','".$descripcion."','".$tasa."','".$conf->entity."')";
		//print $sql."<br>";
		$rqes=$db->query($sql);
	}
	print "<script>window.location.href='cfdimx.php?mod=retenciones'</script>";
}
if(GETPOST('action')=='del'){
	$idi=GETPOST('id');
	$sql="DELETE FROM ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales WHERE rowid=".$idi;
	//print $sql."<br>";
	$rqes=$db->query($sql);
	//print "<script>window.location.href='cfdimx.php?mod=retenciones'</script>";
}
print "<table width='100%' class='noborder'>";
print "<tr class='liste_titre'>";
print "<td colspan='3'>Retenciones</td>";
print "</tr>";
print "<tr class='liste_titre'>";
	print "<td align='center'>";
		print "Codigo";
	print "</td>";
	print "<td align='center'>";
		print "Descripcion";
	print "</td>";
	print "<td align='center'>";
		print "Tasa %";
	print "</td>";
print "</tr>";
$sql="SELECT rowid, cod,descripcion,tasa FROM ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales WHERE entity=".$conf->entity." ORDER BY rowid";
//PRINT $sql;
$rqs=$db->query($sql);
$m=0;
while($rs=$db->fetch_object($rqs)){
	if($m==0){
		$aa=" class='pair'";
		$m=1;
	}else{
		$aa=" class='impair'";
		$m=0;
	}
	print "<tr ".$aa.">";
		print "<td align='center'>".$rs->cod."</td>";
		print "<td align='center'>".$rs->descripcion."</td>";
		print "<td align='center'>".$rs->tasa." 
			    <a href='cfdimx.php?mod=retenciones&action=del&id=".$rs->rowid."'>".img_delete()."</a></td>";
	print "</tr>";
}
print "</table>";

print "<br>";

print "<form method='POST' action='cfdimx.php?mod=retenciones&action=add'><table width='100%' class='noborder'>";
print "<tr class='liste_titre'>";
	print "<td colspan='3'>Agregar retenciones</td>";
print "</tr>";
print "<tr class='liste_titre'>";
	print "<td>";
		print "Codigo";
	print "</td>";
	print "<td>";
		print "Descripcion";
	print "</td>";
	print "<td>";
		print "Tasa %";
	print "</td>";
print "</tr>";
print "<tr>";
	print "<td>";
		print "<input type='text' name='cod' id='cod'>";
	print "</td>";
	print "<td>";
		print "<input type='text' name='descripcion' id='descripcion'>";
	print "</td>";
	print "<td>";
		print "<input type='text' name='tasa' id='tasa'>";
	print "</td>";
print "</tr>";
print "<tr>";
print "<td colspan='2' align='center'><input type='submit' value='Agregar'></td>";
print "</tr>";
print "</table></form>";