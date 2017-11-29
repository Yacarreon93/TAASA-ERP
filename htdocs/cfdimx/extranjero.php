<?php
require('../main.inc.php');

require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
global $db;
$facid=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$object=new Facture($db);
$object->fetch($facid);
$head = facture_prepare_head($object);
llxHeader('','Comercio exterior');
dol_fiche_head($head, "tabfactextranjero", 'CFDI', 0, '');
$action=GETPOST('action');

//print $action."::::<br><br>";
//print_r($_POST);print "<br><br>";
if($action=='act'){
	$sql1="DELETE FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero WHERE fk_facture=".$facid;
	$rq1=$db->query($sql1);
	$tcambio=number_format(GETPOST('tipocambio'),4);
	$sql2="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero 
			(fk_facture, tipo_operacion, clv_pedimento, 
			no_exportador, incoterm, 
			observaciones, num_identificacion,tipocambio,certificadoorigen,subdivision) 
			VALUES('".$facid."','".GETPOST('tpoperacion')."','".GETPOST('clvpedimento')."'
					,'".GETPOST('noexportadorconf')."','".GETPOST('incoterm')."'
					,'".GETPOST('observaciones')."','".GETPOST('numidentificacion')."','".$tcambio."'
							,'".GETPOST('certificado')."','".GETPOST('subdivision')."')";
	//print $sql2."<br><br>";
	$rq2=$db->query($sql2);
	
	$sql3="DELETE FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero_mercancia WHERE fk_facture=".$facid;
	$rq3=$db->query($sql3);
	$sql4="SELECT  a.rowid,a.fk_product,a.description,a.qty, a.total_ttc, b.ref,b.label
	FROM ".MAIN_DB_PREFIX."facturedet a LEFT JOIN ".MAIN_DB_PREFIX."product as b on a.fk_product=b.rowid
	WHERE a.fk_facture=".$facid." ";//AND a.product_type=0
	//print $sql4."<br><br>";
	$rq4=$db->query($sql4);
	$nr4=$db->num_rows($rq4);
	$ttusd=0;
	if($nr4>0){
		while($rs4=$db->fetch_object($rq4)){
			if($_POST['produsd'.$rs4->rowid] && $_POST['prodiden'.$rs4->rowid]){
				$sql5="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero_mercancia 
						(fk_facture, fk_facturedet, 
						preciousd, noidentificacion) 
					  VALUES('".$facid."','".$rs4->rowid."',
					  		'".$_POST['produsd'.$rs4->rowid]."','".$_POST['prodiden'.$rs4->rowid]."')";
				//print $sql5."<br><br>";
				$rq5=$db->query($sql5);
				$ttusd+=$_POST['produsd'.$rs4->rowid];
			}
		}
	}
	$sql6="UPDATE ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero SET totalusd='".$ttusd."' 
			WHERE fk_facture=".$facid;
	$rq6=$db->query($sql6);
	print "<script>window.location.href='extranjero.php?facid=".$facid."'</script>";
}

print "Factura: <a href='".DOL_MAIN_URL_ROOT."/compta/facture.php?id=".$facid."'>".$object->ref."</a><br><br>";

$sql="SELECT tipo_operacion, clv_pedimento, no_exportador, incoterm, observaciones, num_identificacion
		,tipocambio,certificadoorigen,subdivision,totalusd
FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero
WHERE fk_facture=".$facid;
//print $sql;
$reqs=$db->query($sql);
$numr=$db->num_rows($reqs);
//print $numr."<<";
if($numr>0){
	$res=$db->fetch_object($reqs);
	$tpoperacion=$res->tipo_operacion;
	$clvpedimento=$res->clv_pedimento;
	$noexportadorconf=$res->no_exportador;
	$incoterm=$res->incoterm;
	$observaciones=$res->observaciones;
	$numidentificacion=$res->num_identificacion;
	$tipocambio=$res->tipocambio;
	$certificado=$res->certificadoorigen;
	$subdivision=$res->subdivision;
	$totalusd=$res->totalusd;
}
//print $tpoperacion."::";
print "<form method='POST' action='extranjero.php?facid=".$facid."&action=act'><table class='border' width='100%'>";
print "<tr>";
print "<td width='50%'><strong>Tipo de operacion</strong></td>";
print "<td width='50%'><input type='text' name='tpoperacion' value='".$tpoperacion."' required></td>";
print "</tr>";
print "<tr>";
print "<td>Clave de pedimento</td>";
print "<td><input type='text' name='clvpedimento' value='".$clvpedimento."'></td>";
print "</tr>";
print "<tr>";
print "<td>Numero de Exportador confiable</td>";
print "<td><input type='text' name='noexportadorconf' value='".$noexportadorconf."'></td>";
print "</tr>";
print "<tr>";
print "<td>Incoterm</td>";
print "<td><input type='text' name='incoterm' value='".$incoterm."'></td>";
print "</tr>";
print "<tr>";
print "<td>Observaciones</td>";
print "<td><input type='text' name='observaciones' value='".$observaciones."'></td>";
print "</tr>";
print "<tr>";
print "<td>Certificado Origen</td>";
print "<td><input type='text' name='certificado' value='".$certificado."'></td>";
print "</tr>";
print "<tr>";
print "<td>Subdivision</td>";
print "<td><input type='text' name='subdivision' value='".$subdivision."'></td>";
print "</tr>";
print "<tr>";
print "<td><strong>Receptor: Numero de identificacion (NumRegIdTrib)</strong></td>";
print "<td><input type='text' name='numidentificacion' value='".$numidentificacion."' required></td>";
print "</tr>";
print "<tr>";
if($tipocambio==''){
	if($conf->global->MAIN_MODULE_MULTIDIVISA){
		$sqlm="SELECT divisa
				FROM ".MAIN_DB_PREFIX."multidivisa_facture
				WHERE fk_object=".$facid;
		$rqdm=$db->query($sqlm);
		$nrdm=$db->num_rows($rqdm);
		if($nrdm>0){
			$rsdm=$db->fetch_object($rqdm);
			$divisa=$rsdm->divisa;
		}else{
			$divisa=$conf->currency;
		}
	}else{
		if($conf->global->MAIN_MODULE_MULTICURRENCY){
			$sql="SELECT multicurrency_code AS divisa FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$facid;
			$ra=$db->query($sql);
			$rb=$db->fetch_object($ra);
			$divisa=$rb->divisa;
		}else{
			$divisa=$conf->currency;
		}
	}
	
	if($divisa!='USD'){
		$de=$divisa;
		$a='USD';
		$url = 'http://finance.yahoo.com/d/quotes.csv?f=l1d1t1&s='.$de.$a.'=X';
		$handle = fopen($url, 'r');
		if ($handle) {
			$result = fgetcsv($handle);
			fclose($handle);
		}
		$tipocambio=$result[0];
	}else{
		$tipocambio=1;
	}
}
print "<td><strong>Tipo de cambio:</strong></td>";
print "<td><input type='text' name='tipocambio' value='".$tipocambio."' required></td>";
print "</tr>";
print "<tr><td colspan='2'>";
$sql="SELECT  a.rowid,a.fk_product,a.description,a.qty, a.total_ttc, b.ref,b.label
FROM ".MAIN_DB_PREFIX."facturedet a LEFT JOIN ".MAIN_DB_PREFIX."product as b on a.fk_product=b.rowid
WHERE a.fk_facture=".$facid." ";//AND a.product_type=0
$rq=$db->query($sql);
$nr=$db->num_rows($rq);
if($nr>0){
	print "<table width='100%'class='noborder'>";
	print "<tr class='liste_titre'>";
	print "<td>Producto</td>";
	print "<td>Cant.</td>";
	print "<td>Precio Total</td>";
	print "<td>Precio Total en Dolares</td>";
	print "<td>No. Identificacion</td>";
	print "</tr>";
	while($rs=$db->fetch_object($rq)){
		print "<tr>";
		if($rs->fk_product!=null){
			print "<td>".$rs->ref." - ".$rs->label."</td>";
			$noidentif=$rs->ref;
		}else{
			$noidentif="";
			print "<td>".$rs->description."</td>";
		}
		print "<td>".$rs->qty."</td>";
		print "<td>".$rs->total_ttc."</td>";
		$sql="SELECT preciousd,noidentificacion
		FROM ".MAIN_DB_PREFIX."cfdimx_facture_comercio_extranjero_mercancia
		WHERE fk_facture=".$facid." AND fk_facturedet=".$rs->rowid;
		$reqs=$db->query($sql);
		$numr=$db->num_rows($reqs);
		if($numr>0){
			$rst=$db->fetch_object($reqs);
			$totusd=$rst->preciousd;
			$noidentif=$rst->noidentificacion;
		}else{
			$totusd=$rs->total_ttc*$tipocambio;
			//$totusd=1;
		}
		print "<td><input type='text' name='produsd".$rs->rowid."' value='".str_replace(",", "",number_format($totusd,2))."'></td>";
		print "<td><input type='text' name='prodiden".$rs->rowid."' value='".$noidentif."'></td>";
		//unset($rs);
		print "</tr>";
	}
	print "</table>";
}else{
	print "La factura no cuenta con algun producto.";
}
print "</td></tr>";
print "<tr>";
$sqm="SELECT factura_id FROM ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture=".$facid;
$rm=$db->query($sqm);
$rnm=$db->num_rows($rm);
if($rnm==0){
print "<td colspan='2' align='center'><input type='submit' value='Guardar'></td>";
}else{
	print "<td colspan='2' align='center'>La factura ya se ha timbrado</td>";
}
print "</tr>";
print "</table></form>";