<?php
error_reporting(E_ERROR);
date_default_timezone_set("America/Mexico_City");

if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");

if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

if (! $res) die("Include of main fails");

//if(file_exists("lib/nusoap/lib/nusoap.php")) {include("/lib/nusoap/lib/nusoap.php");}else{print 'nusoap lib not found';}

require_once("lib/nusoap/lib/nusoap.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once("conf.php");



global $db;
$action	= GETPOST('action');

llxHeader('','','','','','','CFDI','',0,0);
$form=new Form($db);


foreach ($_REQUEST as $key => $value) {

    //echo $key .'=>'. $value.'<br>';

}

$facturestatic = new Facture($db);
$id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
$object = new Societe($db);
$object->fetch($id);
$head = societe_prepare_head($object);
dol_fiche_head($head, 'tabfactclient2', $langs->trans("ThirdParty"),0,'company');

$socid=$_REQUEST["socid"];



$sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.amount, f.total, f.total_ttc, f.datef as df, 
       f.datec as dc, f.paye as paye, f.fk_statut as statut, s.nom, s.rowid as socid, SUM(pf.amount) as am 
		FROM '.MAIN_DB_PREFIX.'societe as s,'.MAIN_DB_PREFIX.'facture as f LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture 
		WHERE s.rowid="'.$socid.'" AND f.fk_soc = s.rowid AND f.entity = '.$conf->entity.' 
		GROUP BY f.rowid, f.facnumber, f.type, f.amount, f.total, f.total_ttc, f.datef, 
		         f.datec, f.paye, f.fk_statut, s.nom, s.rowid 
		ORDER BY f.datef DESC, f.datec DESC';


$resql=$db->query($sql);
$num=$db->num_rows($resql);
$MAXLIST=$num;

if ($resql)

{

	$var=true;

	$num = $db->num_rows($resql);

	$i = 0;

	if ($num > 0)

	{

/* Admin WS*/ 
		
		// Se realiza una consula al ws de administracion
		
		$wsadmin='http://facturacion.auriboxenlinea.com/admincltdoli.php?wsdl';
		
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
		
		$sql_tim  = '';
		$sql_tim .= 'SELECT * FROM '.MAIN_DB_PREFIX.'cfdimx_config ';
		$sql_tim .= 'WHERE emisor_rfc = "'.$conf->global->MAIN_INFO_SIREN.'" AND entity_id = '. $_SESSION['dol_entity'];
		
		$res_tim=$db->query($sql_tim);
		
		if ($res_tim){
		
			//$nnum = $db->num_rows($res_tim);
			$obj_tim = $db->fetch_object($res_tim);
		}
		
		if(isset($obj_tim->password_timbrado_txt) && isset($conf->global->MAIN_INFO_SIREN)){
			$status_ws = checkURL($wsadmin);
			if($status_ws){
				$admincltdoli = new nusoap_client($wsadmin, 'wsdl');
				$argumentos = array( "rfc"=>$conf->global->MAIN_INFO_SIREN, "passwd_timbrado"=>$obj_tim->password_timbrado_txt);
				$result = $admincltdoli->call('mensaje',$argumentos);
		
				//print_r($result);
				//print '<br>';
				
				//print $result["msg"];
				//print '<br>';
				//print $result["rsp"];
			}
		}
		
	if($result["rsp"]!=0){	
		print '<table width="100%" class="notopnoleftnoright">';
		print '<tr>';
		print '<td>';		
			print '<table width="100%" class="noborder">';
			print '<tr class="liste_titre">';
				print '<td>Mensajes';
				print '</td>';
			print '</tr>';			
			print '<tr>';
				//print '<td>'.$result["msg"];
				//print '</td>';
			print '<td style="width:100px;">';
				print '<div style="overflow:scroll; height:80px">';
					print $result["msg"];
				print '</div>';
			print '</td>';
			print '</tr>';
			print '</table>';			
		print '</td>';
		print '</tr>';
		print '</table><br>';		           
	}




if( $_REQUEST["bus"]==1 ){





	if( $_REQUEST["sallSER"]!="" ){

		$extra_qry.=" AND factura_serie LIKE '%".$_REQUEST["sallSER"]."%'";

	}



	if( $_REQUEST["sallFOL"]!="" ){

		$extra_qry.=" AND factura_folio LIKE '%".$_REQUEST["sallFOL"]."%'";

	}



	if( $_REQUEST["sallUID"]!="" ){

		$extra_qry.=" AND uuid = '".$_REQUEST["sallUID"]."'";

	}



	//EJEMPLO 2013-12-07T12:43:01 in DATABASE	
	if( $_REQUEST["invoicedateyear"]!="" ){
	
		if(isset($_REQUEST["invoicedatemonth"])){
			if($_REQUEST["invoicedatemonth"]<10){
				$_REQUEST["invoicedatemonth"] = '0'.$_REQUEST["invoicedatemonth"];
			}
		}
		if(isset($_REQUEST["invoicedateday"])){
			if($_REQUEST["invoicedateday"]<10){
				$_REQUEST["invoicedateday"] = '0'.$_REQUEST["invoicedateday"];
			}
		}		
		$fechaTimbrado  = $_REQUEST["invoicedateyear"].'-'.$_REQUEST["invoicedatemonth"].'-'.$_REQUEST["invoicedateday"];
		$fechaTimbrado .= 'T%';
		
		$extra_qry.=" AND fechaTimbrado LIKE '".$fechaTimbrado."'";
	
	}	
	
	
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';

		print "<td>Factura</td>";

		print "<td>UUID</td>";

		print "<td>Status</td>";

	print '</tr>';



	$ssql = "

	SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE entity_id = ".$_SESSION['dol_entity']." 

	".$extra_qry."

	ORDER BY fechaTimbrado DESC";

	$ressql=$db->query($ssql);

	if ($ressql){

		 $nnum = $db->num_rows($ressql);

		 $i = 0;

		 if ($nnum){

			 while ($i < $nnum){

				 $oobj = $db->fetch_object($ressql);

				$var=!$var;

				if( $oobj->cancelado==0 ){ $status="Activo";  }else{ $status="Cancelada"; }								

				print '<tr '.$bc[$var].'>';

					print "<td>&nbsp;<a href='../compta/facture.php?facid=".$oobj->fk_facture."'>".$oobj->factura_seriefolio."</a></td>";

					print "<td><a href='facture.php?facid=".$oobj->fk_facture."'>".$oobj->uuid."</a></td>";

					print "<td>".$status."</td>";

				print '</tr>';

				 $i++;

			 }

		 }

	}

	print "</table><br>";

}


print '</td>';



// --> DIXI -->Todas

print '<td valign="top" width="80%" class="notopnoleftnoright">';

		print '<table width="100%" class="noborder" >';

		$tableaushown=1;
	    print '<tr class="liste_titre">';			
		print ' 
				<td>Facturación</td>
				<td>Fecha Factura</td>
			    <td>Receptor</td>
				<td align="center">UUID</td>
                <td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$object->id.'"> Total: ('.$num.')</a>
				</td>
				<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a>
				</td>';
		print '</tr>';		
		
	}
	$facturestaticSociete =new Societe($db);

	while ($i < $num && $i < $MAXLIST)

	{

		$objp = $db->fetch_object($resql);

		$var=!$var;

		print "<tr $bc[$var]>";

		print '<td>';

		$facturestatic->id=$objp->facid;

		$facturestatic->ref=$objp->facnumber;

		$facturestatic->type=$objp->type;

		print $facturestatic->getNomUrl(1);

		print '</td>';

		if ($objp->df > 0)

		{

			print "<td align=\"left\">".dol_print_date($db->jdate($objp->df),'day')."</td>\n";

		}

		else

		{

			print "<td align=\"right\"><b>!!!</b></td>\n";

		}

		//print "<td align=\"right\">".price($objp->total_ttc)."</td>\n";

		//Receptor
		print '<td>';

		$facturestaticSociete->id=$objp->socid;
    
		$facturestaticSociete->nom=$objp->nom;
    
    	$facturestaticSociete->client=1;
    
		print $facturestaticSociete->getNomUrl(1,'',16);

		print '</td>';
		
        //print $objp->statut;
		print "<td align=\"center\">".getLinkGeneraCFDI( $objp->statut, $objp->facid, $db )."</td>\n";



		// --> DIXI

		/*

		$ssq = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx WHERE factura_id = ".$objp->facid. "";

		$resqll=$db->query($ssq);

		$num_reg = $db->num_rows($resqll);

		if( $num_reg==0){

			print "<td align=\"right\"></td>\n";

		}else{

			print "<td align=\"right\"></td>\n";

		}

        */                      

                                

		print '<td align="right" nowrap="nowrap" colspan="2">'.($facturestatic->LibStatut($objp->paye,$objp->statut,5,$objp->am))."</td>\n";

		print "</tr>\n";

		$i++;

	}

	$db->free($resql);



	if ($num > 0) print "</table><br>";

        

        



}else{

	dol_print_error($db);

}

llxFooter();
$db->close();
