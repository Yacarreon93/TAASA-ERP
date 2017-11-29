<?
require("../main.inc.php");
require_once("conf.php");

llxHeader('',"CFDI::Todas las facturas con CFDI");


//$head = societe_prepare_head($object);
$head = cfdimx_cliente_head( $_REQUEST["socid"] );

dol_fiche_head($head, 'tres', $langs->trans("ThirdParty"),0,'company');

$fechaq_show = "";

if( $_REQUEST["envqry"]=="Consultar" ){
	if( $_REQUEST["ftimbrado"]!="" ){
		$fechat = explode("/",$_REQUEST["ftimbrado"]);
		$dia=$fechat[0];
		$mes=$fechat[1];
		$anio=$fechat[2];
		$fechaq_show = mktime(0,0,0,$mes,$dia,$anio);
		$extra_qry.= ' AND c.fecha_timbrado = "'.$anio."-".$mes."-".$dia.'"';
	}
	
	if( $_REQUEST["serie"]!="" ){
		$extra_qry.=" AND c.factura_serie LIKE '%".$_REQUEST["serie"]."%'";
	}

	if( $_REQUEST["folio"]!="" ){
		$extra_qry.=" AND c.factura_folio LIKE '%".$_REQUEST["folio"]."%'";
	}
	
}

$form = new Form($db);

print '<form method="post">';

print "<strong>Filtrar por:</strong><br>";
print "<strong>Fecha Timbrado:</strong> ";
$form->select_date($fechaq_show,'ftimbrado','','','',"add",1,1);
print "&nbsp;";
print '<strong>Serie:</strong> <input type="text" name="serie" value="'.$_REQUEST["serie"].'">';
print "&nbsp;";
print '<strong>Folio:</strong> <input type="text" name="folio" value="'.$_REQUEST["folio"].'">';
print "&nbsp;";
print '<strong>UUID:</strong> <input type="text" name="UUID" size="50">';
print '&nbsp;<input type="submit" name="envqry" value="Consultar">';
print "</form>";
print "<p></p>";

$var=True;

print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
		print "<td>Factura</td>";
		print "<td>Fecha Timbrado</td>";
		print "<td>UUID</td>";
		print "<td>Status</td>";
		print "<td>Tipo</td>";
	print '</tr>';
	
	$sql = "
	SELECT 
		c.uuid, 
		c.fecha_timbrado, 
		c.fk_facture, 
		c.factura_seriefolio,
		c.cancelado,
		s.nom
	FROM  ".MAIN_DB_PREFIX."cfdimx c, ".MAIN_DB_PREFIX."facture f, ".MAIN_DB_PREFIX."societe s
	WHERE f.rowid = c.fk_facture
	AND s.rowid = f.fk_soc
	AND s.rowid = ".$_REQUEST["socid"]."
	".$extra_qry."
	ORDER BY fechaTimbrado DESC";
	$resql=$db->query($sql);
	if ($resql){
		 $num = $db->num_rows($resql);
		 $i = 0;
		 if ($num){
			 while ($i < $num){
				if( $obj->tipo_timbrado==1 ){ $tipo="Producción";  }else{ $tipo="Pruebas"; }
				if( $obj->cancelado==0 ){ $status="Activo";  }else{ $status="Cancelada"; }
				 
				 $obj = $db->fetch_object($resql);
				$var=!$var;
				print '<tr '.$bc[$var].'>';
					print "<td>&nbsp;<a href='../compta/facture.php?facid=".$obj->fk_facture."'>".$obj->factura_seriefolio."</a></td>";
					print "<td>".$obj->fecha_timbrado."</td>";
					print "<td><a href='facture.php?facid=".$obj->fk_facture."'>".$obj->uuid."</a></td>";
					print "<td>".$status."</td>";
					print "<td>".$tipo."</td>";
				print '</tr>';
				 $i++;
			 }
		 }
	}

print "</table>";


llxFooter();
$db->close();
?>