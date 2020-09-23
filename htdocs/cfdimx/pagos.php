<?php
require('../main.inc.php');

require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/cfdi/service/comprobantecfdiservice.php';
require('conf.php');
include('lib/nusoap/lib/nusoap.php');
global $db;
$facid=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST("ref");
$object=new Facture($db);
$paymentstatic=new Paiement($db);
$bankaccountstatic = new Account($db);
$form2=new Form($db);
$object->fetch($facid,$ref);

$head = facture_prepare_head($object);
llxHeader('','Pagos CFDI');
dol_fiche_head($head, "tabfactpagosclt", 'CFDI', 0, '');

$action=GETPOST('action');
$soc = new Societe($db);
$socid=$object->socid;
if ($socid) $res=$soc->fetch($socid);
$langs->load('bills');
$langs->load('banks');
$langs->load('companies');

if(GETPOST("mesg")=="err1"){
	dol_htmloutput_errors("Error al registrar la informacion del Pago CFDI");
}
if( GETPOST("msgerr")==1 && $_SESSION["errorCFDIP"]!=""){
	print '
				<p></p>
				<div align="center">
				<div align="center" style="width:800px; border:solid 1px; height:40px; background-color:#FFC; padding-top:10px, color:#C00">
				<strong>'.$_SESSION["errorCFDIP"].'</strong>
				</div></div><p></p>';
	$_SESSION["errorCFDIP"]="";
}
$sqlc="SELECT uuid FROM ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture=".$facid;
$rqc=$db->query($sqlc);
$nrc=$db->num_rows($rqc);
$uuid="";
if($nrc>0){
	$rslc=$db->fetch_object($rqc);
	$uuid=$rslc->uuid;
}
print '<table class="border" width="100%">';
// Ref
print '<tr><td class="titlefield" >'.$langs->trans('Ref').' Factura</td>';
print '<td colspan="3">';
$morehtmlref='';
print $form2->showrefnav($object,'ref','',1,'facnumber','ref',$morehtmlref);
print '</td></tr>';
// Third party
print '<tr><td class="titlefield">'.$langs->trans('Company').'</td>';
print '</td><td colspan="3">';
	print ' &nbsp;'.$soc->getNomUrl(1,'compta');
print '</td></tr>';
print '<tr><td class="titlefield">UUID</td>';
print '</td><td colspan="3">';
print $uuid;
print '</td></tr>';
print '</table>';

if($action==""){
	print '<table class="noborder paymenttable" width="100%">';
	$sign = 1;
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">' . ($object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("PaymentsBack") : $langs->trans('Payments')) . '</td>';
	print '<td class="liste_titre">' . $langs->trans('Date') . '</td>';
	print '<td class="liste_titre">' . $langs->trans('Type') . '</td>';
	if (! empty($conf->banque->enabled)) {
		print '<td class="liste_titre" align="right">' . $langs->trans('BankAccount') . '</td>';
	}
	print '<td class="liste_titre" align="right">' . $langs->trans('Amount') . '</td>';
	print '<td class="liste_titre" align="right">CFDI</td>';
	print '<td class="liste_titre" align="right">CFDI 2</td>';
	print '<td class="liste_titre" width="18">&nbsp;</td>';
	print '</tr>';
	// Payments already done (from payment on this invoice)
	$sql = 'SELECT p.datep as dp, p.rowid as ref, p.num_paiement, p.rowid, p.fk_bank,';
	$sql .= ' c.code as payment_code, c.libelle as payment_label,';
	$sql .= ' pf.amount,';
	$sql .= ' ba.rowid as baid, ba.ref as baref, ba.label';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_paiement as c, ' . MAIN_DB_PREFIX . 'paiement_facture as pf, ' . MAIN_DB_PREFIX . 'paiement as p';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank as b ON p.fk_bank = b.rowid';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON b.fk_account = ba.rowid';
	$sql .= ' WHERE pf.fk_facture = ' . $object->id . ' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
	$sql .= ' ORDER BY p.datep, p.tms';

	$result = $db->query($sql);

		@$num = $db->num_rows($result);
		$i = 0;
	
		// if ($object->type != 2)
		// {
		if ($num > 0) {
			while ($i < $num) {
				$objp = $db->fetch_object($result);
				$var = ! $var;
				print '<tr ' . $bc[$var] . '><td>';
				$paymentstatic->id = $objp->rowid;
				$paymentstatic->datepaye = $db->jdate($objp->dp);
				$paymentstatic->ref = $objp->ref;
				$paymentstatic->num_paiement = $objp->num_paiement;
				$paymentstatic->payment_code = $objp->payment_code;
				print $paymentstatic->getNomUrl(1);
				print '</td>';
				print '<td>' . dol_print_date($db->jdate($objp->dp), 'day') . '</td>';
				$label = ($langs->trans("PaymentType" . $objp->payment_code) != ("PaymentType" . $objp->payment_code)) ? $langs->trans("PaymentType" . $objp->payment_code) : $objp->payment_label;
				print '<td>' . $label . ' ' . $objp->num_paiement . '</td>';
				if (! empty($conf->banque->enabled)) {
					$bankaccountstatic->id = $objp->baid;
					$bankaccountstatic->ref = $objp->baref;
					$bankaccountstatic->label = $objp->baref;
					print '<td align="right">';
					if ($bankaccountstatic->id)
						print $bankaccountstatic->getNomUrl(1, 'transactions');
					print '</td>';
				}
				print '<td align="right">' . price($sign * $objp->amount) . '</td>';
				$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".$facid." AND fk_paiement=".$objp->rowid;
				//print $sql;
				$req=$db->query($sql);
				$nmr=$db->num_rows($req);
				$uuidP="";
				$uuidCFDI="";
				$sql2="SELECT * FROM cfdi_comprobante WHERE fk_comprobante=".$facid." AND fk_payment=".$objp->rowid;
				$req2 = $db->query($sql2);
				$nmr2=$db->num_rows($req2);
				if($nmr2>0){
					$rsl2=$db->fetch_object($req2);
					if($rsl2->UUID!="" && $rsl2->UUID!=null){
						$uuidCFDI=$rsl2->UUID;
					}
				}
				if($nmr>0){
					$rsl=$db->fetch_object($req);
					if($rsl->uuid!="" && $rsl->uuid!=null){
						$uuidP=$rsl->uuid;
					}
				}
				if($uuidP==""){
					if($uuid!=""){
						if($uuidCFDI != "") {
							print '<td align="right">'.$uuidCFDI.'</a></td>';
						} else {
							print '<td align="right"><a href="pagos.php?facid='.$object->id.'&pagcid='.$paymentstatic->id.'&action=cfdi">Genera CFDI</a></td>';
						}
					} else{
						print '<td align="right">No se ha timbrado la Factura</td>';
					}
				} else {
					print '<td align="right"><a href="pagos.php?facid='.$object->id.'&pagcid='.$paymentstatic->id.'&action=cfdi1">'.$uuidP.'</a></td>';
				}
				if($uuidCFDI==""){
					if($uuid!=""){
						if($uuidP != "") {
							print '<td align="right">'.$uuidP.'</a></td>';
						} else {
							print '<td align="right"><a href="pagos.php?facid='.$object->id.'&pagcid='.$paymentstatic->id.'&action=cfdi2">Genera CFDI 2</a></td>';
						}
						
					}else{
						print '<td align="right">No se ha timbrado la Factura</td>';
					}
				} else {
					print '<td align="right"><a href="pagos.php?facid='.$object->id.'&pagcid='.$paymentstatic->id.'&action=cfdi2">'.$uuidCFDI.'</a></td>';
				}
				print '<td>&nbsp;</td>';
				print '</tr>';
				$i ++;
			}
		} else {
			print '<tr ' . $bc[false] . '><td colspan="' . $nbcols . '" class="opacitymedium">' . $langs->trans("None") . '</td><td></td><td></td></tr>';
		}
		// }
		print '</table>';
		$db->free($result);
	
}

if($action=="guardar"){
	// 	print "<pre>";
	// 	print_r($_REQUEST);
	// 	print "</pre>";
	$fechaaux=str_replace("/", "-", GETPOST('fechaPago'));
	$fechaPago=date("Y-m-d",strtotime($fechaaux));
	$fechaPago=$fechaPago." ".GETPOST('fechaPagohour').":".GETPOST("fechaPagomin").":00";
	
	$sql1="SELECT rowid FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".GETPOST('facid')." AND fk_paiement=".GETPOST('pagcid')." AND entity=".$conf->entity;
	$resq=$db->query($sql1);
	$numr=$db->num_rows($resq);
	if($numr==0){
		
		//print $fechaPago;
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos
			   (fk_facture,
				fk_paiement,
				fechaPago,
				formaDePago,
				monedaP,
				TipoCambioP,
				monto,
				numOperacion,
				rfcEmisorCtaOrd,
				nomBancoOrdExt,
				ctaOrdenante,
				rfcEmisorCtaBen,
				ctaBeneficiario,
				tipoCadPago,
				certPago,
				cadPago,
				selloPago,
				entity) 
				VALUES (
				".GETPOST('facid').",
				".GETPOST('pagcid').",
				'".$fechaPago."',
				".(GETPOST('formpago')!=''?"'".GETPOST('formpago')."'":'NULL').",
				".(GETPOST('monedapago')!=''?"'".GETPOST('monedapago')."'":'NULL').",
				".(GETPOST('tipocambio')!=''?"'".GETPOST('tipocambio')."'":'NULL').",
				".(GETPOST('montop')!=''?"'".GETPOST('montop')."'":'NULL').",
				".(GETPOST('numoperacion')!=''?"'".GETPOST('numoperacion')."'":'NULL').",
				".(GETPOST('rfcemisorctaorigen')!=''?"'".GETPOST('rfcemisorctaorigen')."'":'NULL').",
				".(GETPOST('nombancoordenante')!=''?"'".GETPOST('nombancoordenante')."'":'NULL').",
				".(GETPOST('ctaordenante')!=''?"'".GETPOST('ctaordenante')."'":'NULL').",
				".(GETPOST('rfcemisorctabeneficiario')!=''?"'".GETPOST('rfcemisorctabeneficiario')."'":'NULL').",
				".(GETPOST('ctabeneficiario')!=''?"'".GETPOST('ctabeneficiario')."'":'NULL').",
				".(GETPOST('tipocadenapago')!=''?"'".GETPOST('tipocadenapago')."'":'NULL').",
				".(trim(GETPOST('certificadopago'))!=''?"'".GETPOST('certificadopago')."'":'NULL').",
				".(trim(GETPOST('cadenaoriginal'))!=''?"'".GETPOST('cadenaoriginal')."'":'NULL').",
				".(trim(GETPOST('sellopago'))!=''?"'".GETPOST('sellopago')."'":'NULL').",
				".$conf->entity."
				)";
		
		//print "<br>".$sql."<br>";
		if($res=$db->query($sql)){
			$last=$db->last_insert_id(MAIN_DB_PREFIX."cfdimx_recepcion_pagos");
			$fk_recepago=$last;
			//print_r($fk_recepago);
			//$fk_recepago=1;
			
			$sql2="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado
			(fk_recepago,
		     idDocumento,
		     serie,
		     folio,
		     monedaDR,
		     tipoCambioDR,
		     metodoDePagoDR,
		     numParcialidad,
		     impSaldoAnt,
		     impPagado,
		     impSaldoInsoluto,
		   	 entity)
			 VALUES (
					'".$fk_recepago."',
					".(GETPOST('idDocumento')!=''?"'".GETPOST('idDocumento')."'":'NULL').",
					".(GETPOST('docSerie')!=''?"'".GETPOST('docSerie')."'":'NULL').",
					".(GETPOST('docFolio')!=''?"'".GETPOST('docFolio')."'":'NULL').",
					".(GETPOST('monedaDR')!=''?"'".GETPOST('monedaDR')."'":'NULL').",
					".(GETPOST('tipocambiodr')!=''?"'".GETPOST('tipocambiodr')."'":'NULL').",
					".(GETPOST('metodoPDR')!=''?"'".GETPOST('metodoPDR')."'":'NULL').",
					".(GETPOST('numparcialidaddr')!=''?"'".GETPOST('numparcialidaddr')."'":'NULL').",
					".(GETPOST('impSaldoAnterior')!=''?"'".GETPOST('impSaldoAnterior')."'":'NULL').",
					".(GETPOST('impPagadodr')!=''?"'".GETPOST('impPagadodr')."'":'NULL').",
					".(GETPOST('impSaldoInsoluto')!=''?"'".GETPOST('impSaldoInsoluto')."'":'NULL').",
					".$conf->entity."
			 )";
			
			//print "<br>".$sql2."<br>";
			$res2=$db->query($sql2);
			print "<script>window.location.href='pagos.php?action=cfdi&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."'</script>";
		}else{
			print "<script>window.location.href='pagos.php?action=cfdi&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."&mesg=err1'</script>";
		}
	}else{
		$resultado=$db->fetch_object($resq);
		$sql3="UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos
				SET
				fechaPago='".$fechaPago."',
				formaDePago=".(GETPOST('formpago')!=''?"'".GETPOST('formpago')."'":'NULL').",
				monedaP=".(GETPOST('monedapago')!=''?"'".GETPOST('monedapago')."'":'NULL').",
				TipoCambioP=".(GETPOST('tipocambio')!=''?"'".GETPOST('tipocambio')."'":'NULL').",
				monto=".(GETPOST('montop')!=''?"'".GETPOST('montop')."'":'NULL').",
				numOperacion=".(GETPOST('numoperacion')!=''?"'".GETPOST('numoperacion')."'":'NULL').",
				rfcEmisorCtaOrd=".(GETPOST('rfcemisorctaorigen')!=''?"'".GETPOST('rfcemisorctaorigen')."'":'NULL').",
				nomBancoOrdExt=".(GETPOST('nombancoordenante')!=''?"'".GETPOST('nombancoordenante')."'":'NULL').",
				ctaOrdenante=".(GETPOST('ctaordenante')!=''?"'".GETPOST('ctaordenante')."'":'NULL').",
				rfcEmisorCtaBen=".(GETPOST('rfcemisorctabeneficiario')!=''?"'".GETPOST('rfcemisorctabeneficiario')."'":'NULL').",
				ctaBeneficiario=".(GETPOST('ctabeneficiario')!=''?"'".GETPOST('ctabeneficiario')."'":'NULL').",
				tipoCadPago=".(GETPOST('tipocadenapago')!=''?"'".GETPOST('tipocadenapago')."'":'NULL').",
				certPago=".(trim(GETPOST('certificadopago'))!=''?"'".GETPOST('certificadopago')."'":'NULL').",
				cadPago=".(trim(GETPOST('cadenaoriginal'))!=''?"'".GETPOST('cadenaoriginal')."'":'NULL').",
				selloPago=".(trim(GETPOST('sellopago'))!=''?"'".GETPOST('sellopago')."'":'NULL')."
				WHERE rowid=".$resultado->rowid;		
		
		//print "<br>".$sql3;
		if($res=$db->query($sql3)){
			$sql4="UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado
					SET
					 idDocumento=".(GETPOST('idDocumento')!=''?"'".GETPOST('idDocumento')."'":'NULL').",
				     serie=".(GETPOST('docSerie')!=''?"'".GETPOST('docSerie')."'":'NULL').",
				     folio=".(GETPOST('docFolio')!=''?"'".GETPOST('docFolio')."'":'NULL').",
				     monedaDR=".(GETPOST('monedaDR')!=''?"'".GETPOST('monedaDR')."'":'NULL').",
				     tipoCambioDR=".(GETPOST('tipocambiodr')!=''?"'".GETPOST('tipocambiodr')."'":'NULL').",
				     metodoDePagoDR=".(GETPOST('metodoPDR')!=''?"'".GETPOST('metodoPDR')."'":'NULL').",
				     numParcialidad=".(GETPOST('numparcialidaddr')!=''?"'".GETPOST('numparcialidaddr')."'":'NULL').",
				     impSaldoAnt=".(GETPOST('impSaldoAnterior')!=''?"'".GETPOST('impSaldoAnterior')."'":'NULL').",
				     impPagado=".(GETPOST('impPagadodr')!=''?"'".GETPOST('impPagadodr')."'":'NULL').",
				     impSaldoInsoluto=".(GETPOST('impSaldoInsoluto')!=''?"'".GETPOST('impSaldoInsoluto')."'":'NULL')."
					WHERE fk_recepago=".$resultado->rowid;
			//print "<br>".$sql4;
			$res2=$db->query($sql4);
			print "<script>window.location.href='pagos.php?action=cfdi&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."'</script>";
		}else{
			print "<script>window.location.href='pagos.php?action=cfdi&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."&mesg=err1'</script>";
		}
		
	}
}

/*
	Acción para guardar la info del pago (custom):
	
	1. Obtener la info.
	2. Revisar si existe la info del pago en la base de datos:
		- Si SI: Actualizar la info en la base de datos.
		- Si NO: Crear el registro de la info en la base de datos.
	3. Refrescar la página.
*/
if ($action == "guardar2") {
	//die('checkpoint 1'); // @BORRAR

	$fechaaux = str_replace("/", "-", GETPOST('fechaPago'));
	$fechaPago = date("Y-m-d",strtotime($fechaaux));
	$fechaPago = $fechaPago." ".GETPOST('fechaPagohour').":".GETPOST("fechaPagomin").":00";

	$facid_custom = GETPOST('facid'); // ".GETPOST('facid').",
	$pagcid_custom = GETPOST('pagcid'); // ".GETPOST('pagcid').",

	$formpago_custom = GETPOST('formpago') != '' ? "'".GETPOST('formpago')."'" : 'NULL'; // ".(GETPOST('formpago')!=''?"'".GETPOST('formpago')."'":'NULL').",
	$monedapago_custom = GETPOST('monedapago') != '' ? "'".GETPOST('monedapago')."'" : 'NULL'; // ".(GETPOST('monedapago')!=''?"'".GETPOST('monedapago')."'":'NULL').",
	$tipocambio_custom = GETPOST('tipocambio') != '' ? "'".GETPOST('tipocambio')."'" : 'NULL'; // ".(GETPOST('tipocambio')!=''?"'".GETPOST('tipocambio')."'":'NULL').",
	$montop_custom = GETPOST('montop') != '' ? GETPOST('montop') : 'NULL'; // ".(GETPOST('montop')!=''?"'".GETPOST('montop')."'":'NULL').",
	$numoperacion_custom = GETPOST('numoperacion') != '' ? "'".GETPOST('numoperacion')."'" : 'NULL'; // ".(GETPOST('numoperacion')!=''?"'".GETPOST('numoperacion')."'":'NULL').",
	$rfcemisorctaorigen_custom = GETPOST('rfcemisorctaorigen') != '' ? "'".GETPOST('rfcemisorctaorigen')."'" : 'NULL'; // ".(GETPOST('rfcemisorctaorigen')!=''?"'".GETPOST('rfcemisorctaorigen')."'":'NULL').",
	$nombancoordenante_custom = GETPOST('nombancoordenante') != '' ? "'".GETPOST('nombancoordenante')."'" : 'NULL'; // ".(GETPOST('nombancoordenante')!=''?"'".GETPOST('nombancoordenante')."'":'NULL').",
	$ctaordenante_custom = GETPOST('ctaordenante') != '' ? "'".GETPOST('ctaordenante')."'" : 'NULL'; // ".(GETPOST('ctaordenante')!=''?"'".GETPOST('ctaordenante')."'":'NULL').",
	$rfcemisorctabeneficiario_custom = GETPOST('rfcemisorctabeneficiario') != '' ? "'".GETPOST('rfcemisorctabeneficiario')."'" : 'NULL'; // ".(GETPOST('rfcemisorctabeneficiario')!=''?"'".GETPOST('rfcemisorctabeneficiario')."'":'NULL').",
	$ctabeneficiario_custom = GETPOST('ctabeneficiario') != '' ? "'".GETPOST('ctabeneficiario')."'" : 'NULL'; // ".(GETPOST('ctabeneficiario')!=''?"'".GETPOST('ctabeneficiario')."'":'NULL').",
	$tipocadenapago_custom = GETPOST('tipocadenapago') != '' ? "'".GETPOST('tipocadenapago')."'" : 'NULL'; // ".(GETPOST('tipocadenapago')!=''?"'".GETPOST('tipocadenapago')."'":'NULL').",
	$certificadopago_custom = trim(GETPOST('certificadopago')) != '' ? "'".GETPOST('certificadopago')."'" : 'NULL'; // ".(trim(GETPOST('certificadopago'))!=''?"'".GETPOST('certificadopago')."'":'NULL').",
	$cadenaoriginal_custom = trim(GETPOST('cadenaoriginal')) != '' ? "'".GETPOST('cadenaoriginal')."'" : 'NULL'; // ".(trim(GETPOST('cadenaoriginal'))!=''?"'".GETPOST('cadenaoriginal')."'":'NULL').",
	$sellopago_custom = trim(GETPOST('sellopago')) != '' ? "'".GETPOST('sellopago')."'" : 'NULL'; // ".(trim(GETPOST('sellopago'))!=''?"'".GETPOST('sellopago')."'":'NULL').",

	// Estos son los datos extra:
	$idDocumento_custom = GETPOST('idDocumento') != '' ? "'".GETPOST('idDocumento')."'" : 'NULL'; // ".(GETPOST('idDocumento')!=''?"'".GETPOST('idDocumento')."'":'NULL').",
	$docSerie_custom = GETPOST('docSerie') != '' ? "'".GETPOST('docSerie')."'" : 'NULL'; // ".(GETPOST('docSerie')!=''?"'".GETPOST('docSerie')."'":'NULL').",
	$docFolio_custom = GETPOST('docFolio') != '' ? "'".GETPOST('docFolio')."'" : 'NULL'; // ".(GETPOST('docFolio')!=''?"'".GETPOST('docFolio')."'":'NULL').",
	$monedaDR_custom = GETPOST('monedaDR') != '' ? "'".GETPOST('monedaDR')."'" : 'NULL'; // ".(GETPOST('monedaDR')!=''?"'".GETPOST('monedaDR')."'":'NULL').",
	$tipocambiodr_custom = GETPOST('tipocambiodr') != '' ? "'".GETPOST('tipocambiodr')."'" : 'NULL'; // ".(GETPOST('tipocambiodr')!=''?"'".GETPOST('tipocambiodr')."'":'NULL').",
	$metodoPDR_custom = GETPOST('metodoPDR') != '' ? "'".GETPOST('metodoPDR')."'" : 'NULL'; // ".(GETPOST('metodoPDR')!=''?"'".GETPOST('metodoPDR')."'":'NULL').",
	$numparcialidaddr_custom = GETPOST('numparcialidaddr') != '' ? "'".GETPOST('numparcialidaddr')."'" : 'NULL'; // ".(GETPOST('numparcialidaddr')!=''?"'".GETPOST('numparcialidaddr')."'":'NULL').",
	$impSaldoAnterior_custom = GETPOST('impSaldoAnterior') != '' ? "'".GETPOST('impSaldoAnterior')."'" : 'NULL'; // ".(GETPOST('impSaldoAnterior')!=''?"'".GETPOST('impSaldoAnterior')."'":'NULL').",
	$impPagadodr_custom = GETPOST('impPagadodr') != '' ? "'".GETPOST('impPagadodr')."'" : 'NULL'; // ".(GETPOST('impPagadodr')!=''?"'".GETPOST('impPagadodr')."'":'NULL').",
	$impSaldoInsoluto_custom = GETPOST('impSaldoInsoluto') != '' ? "'".GETPOST('impSaldoInsoluto')."'" : 'NULL'; // ".(GETPOST('impSaldoInsoluto')!=''?"'".GETPOST('impSaldoInsoluto')."'":'NULL').",

	$data_custom = array();
	$data_custom['facid'] = $facid_custom;
	$data_custom['pagcid'] = $pagcid_custom;
	$data_custom['fechaPago'] = $fechaPago;
	$data_custom['formpago'] = $formpago_custom;
	$data_custom['monedapago'] = $monedapago_custom;
	$data_custom['tipocambio'] = $tipocambio_custom;
	$data_custom['montop'] = $montop_custom;
	$data_custom['numoperacion'] = $numoperacion_custom;
	$data_custom['rfcemisorctaorigen'] = $rfcemisorctaorigen_custom;
	$data_custom['nombancoordenante'] = $nombancoordenante_custom;
	$data_custom['ctaordenante'] = $ctaordenante_custom;
	$data_custom['rfcemisorctabeneficiario'] = $rfcemisorctabeneficiario_custom;
	$data_custom['ctabeneficiario'] = $ctabeneficiario_custom;
	$data_custom['tipocadenapago'] = $tipocadenapago_custom;
	$data_custom['certificadopago'] = $certificadopago_custom;
	$data_custom['cadenaoriginal'] = $cadenaoriginal_custom;
	$data_custom['sellopago'] = $sellopago_custom;
	
	// Estos son los datos extra:
	$data_custom['idDocumento'] = $idDocumento_custom;
	$data_custom['docSerie'] = $docSerie_custom;
	$data_custom['docFolio'] = $docFolio_custom;
	$data_custom['monedaDR'] = $monedaDR_custom;
	$data_custom['tipocambiodr'] = $tipocambiodr_custom;
	$data_custom['metodoPDR'] = $metodoPDR_custom;
	$data_custom['numparcialidaddr'] = $numparcialidaddr_custom;
	$data_custom['impSaldoAnterior'] = $impSaldoAnterior_custom;
	$data_custom['impPagadodr'] = $impPagadodr_custom;
	$data_custom['impSaldoInsoluto'] = $impSaldoInsoluto_custom;

	$sql1 = "SELECT rowid FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".$facid_custom." AND fk_paiement=".$pagcid_custom." AND entity=".$conf->entity;
	$resq = $db->query($sql1);
	$numr = $db->num_rows($resq);
	
	if ($numr == 0) {
		//die('checkpoint 2'); // @BORRAR

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos (
				fk_facture,
				fk_paiement,
				fechaPago,
				formaDePago,
				monedaP,
				TipoCambioP,
				monto,
				numOperacion,
				rfcEmisorCtaOrd,
				nomBancoOrdExt,
				ctaOrdenante,
				rfcEmisorCtaBen,
				ctaBeneficiario,
				tipoCadPago,
				certPago,
				cadPago,
				selloPago,
				entity
			) VALUES (
				".$facid_custom.",
				".$pagcid_custom.",
				'".$fechaPago."',
				".(GETPOST('formpago')!=''?"'".GETPOST('formpago')."'":'NULL').",
				".(GETPOST('monedapago')!=''?"'".GETPOST('monedapago')."'":'NULL').",
				".(GETPOST('tipocambio')!=''?"'".GETPOST('tipocambio')."'":'NULL').",
				".(GETPOST('montop')!=''?"'".GETPOST('montop')."'":'NULL').",
				".(GETPOST('numoperacion')!=''?"'".GETPOST('numoperacion')."'":'NULL').",
				".(GETPOST('rfcemisorctaorigen')!=''?"'".GETPOST('rfcemisorctaorigen')."'":'NULL').",
				".(GETPOST('nombancoordenante')!=''?"'".GETPOST('nombancoordenante')."'":'NULL').",
				".(GETPOST('ctaordenante')!=''?"'".GETPOST('ctaordenante')."'":'NULL').",
				".(GETPOST('rfcemisorctabeneficiario')!=''?"'".GETPOST('rfcemisorctabeneficiario')."'":'NULL').",
				".(GETPOST('ctabeneficiario')!=''?"'".GETPOST('ctabeneficiario')."'":'NULL').",
				".(GETPOST('tipocadenapago')!=''?"'".GETPOST('tipocadenapago')."'":'NULL').",
				".(trim(GETPOST('certificadopago'))!=''?"'".GETPOST('certificadopago')."'":'NULL').",
				".(trim(GETPOST('cadenaoriginal'))!=''?"'".GETPOST('cadenaoriginal')."'":'NULL').",
				".(trim(GETPOST('sellopago'))!=''?"'".GETPOST('sellopago')."'":'NULL').",
				".$conf->entity."
			)";

		if ($res = $db->query($sql)) {
			$last = $db->last_insert_id(MAIN_DB_PREFIX."cfdimx_recepcion_pagos");
			$fk_recepago = $last;

			$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado (
					fk_recepago,
					idDocumento,
					serie,
					folio,
					monedaDR,
					tipoCambioDR,
					metodoDePagoDR,
					numParcialidad,
					impSaldoAnt,
					impPagado,
					impSaldoInsoluto,
					entity
				) VALUES (
					'".$fk_recepago."',
					".(GETPOST('idDocumento')!=''?"'".GETPOST('idDocumento')."'":'NULL').",
					".(GETPOST('docSerie')!=''?"'".GETPOST('docSerie')."'":'NULL').",
					".(GETPOST('docFolio')!=''?"'".GETPOST('docFolio')."'":'NULL').",
					".(GETPOST('monedaDR')!=''?"'".GETPOST('monedaDR')."'":'NULL').",
					".(GETPOST('tipocambiodr')!=''?"'".GETPOST('tipocambiodr')."'":'NULL').",
					".(GETPOST('metodoPDR')!=''?"'".GETPOST('metodoPDR')."'":'NULL').",
					".(GETPOST('numparcialidaddr')!=''?"'".GETPOST('numparcialidaddr')."'":'NULL').",
					".(GETPOST('impSaldoAnterior')!=''?"'".GETPOST('impSaldoAnterior')."'":'NULL').",
					".(GETPOST('impPagadodr')!=''?"'".GETPOST('impPagadodr')."'":'NULL').",
					".(GETPOST('impSaldoInsoluto')!=''?"'".GETPOST('impSaldoInsoluto')."'":'NULL').",
					".$conf->entity."
				)";
			
			$res2 = $db->query($sql2);

			
			//die('checkpoint 4'); // @BORRAR
				
			$service = new ComprobanteCFDIService();
			$service->SaveCFDIFromPayment($db, $facid_custom, $pagcid_custom, $data_custom);


			// @MIRA: Crear registro (agregar error handling).

			print "<script>window.location.href='pagos.php?action=cfdi2&facid=".$facid_custom."&pagcid=".$pagcid_custom."'</script>";
		} else {
			print "<script>window.location.href='pagos.php?action=cfdi2&facid=".$facid_custom."&pagcid=".$pagcid_custom."&mesg=err1'</script>";
		}
	} else {
		echo 'si encontro algo guardado';
		//die('checkpoint 3'); // @BORRAR
	
		$resultado = $db->fetch_object($resq);

		$sql3 = "UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos
			SET
				fechaPago='".$fechaPago."',
				formaDePago=".(GETPOST('formpago')!=''?"'".GETPOST('formpago')."'":'NULL').",
				monedaP=".(GETPOST('monedapago')!=''?"'".GETPOST('monedapago')."'":'NULL').",
				TipoCambioP=".(GETPOST('tipocambio')!=''?"'".GETPOST('tipocambio')."'":'NULL').",
				monto=".(GETPOST('montop')!=''?"'".GETPOST('montop')."'":'NULL').",
				numOperacion=".(GETPOST('numoperacion')!=''?"'".GETPOST('numoperacion')."'":'NULL').",
				rfcEmisorCtaOrd=".(GETPOST('rfcemisorctaorigen')!=''?"'".GETPOST('rfcemisorctaorigen')."'":'NULL').",
				nomBancoOrdExt=".(GETPOST('nombancoordenante')!=''?"'".GETPOST('nombancoordenante')."'":'NULL').",
				ctaOrdenante=".(GETPOST('ctaordenante')!=''?"'".GETPOST('ctaordenante')."'":'NULL').",
				rfcEmisorCtaBen=".(GETPOST('rfcemisorctabeneficiario')!=''?"'".GETPOST('rfcemisorctabeneficiario')."'":'NULL').",
				ctaBeneficiario=".(GETPOST('ctabeneficiario')!=''?"'".GETPOST('ctabeneficiario')."'":'NULL').",
				tipoCadPago=".(GETPOST('tipocadenapago')!=''?"'".GETPOST('tipocadenapago')."'":'NULL').",
				certPago=".(trim(GETPOST('certificadopago'))!=''?"'".GETPOST('certificadopago')."'":'NULL').",
				cadPago=".(trim(GETPOST('cadenaoriginal'))!=''?"'".GETPOST('cadenaoriginal')."'":'NULL').",
				selloPago=".(trim(GETPOST('sellopago'))!=''?"'".GETPOST('sellopago')."'":'NULL')."
			WHERE rowid=".$resultado->rowid;	

			$sqlCFDI = "UPDATE cfdi_complemento_pago 
			SET
				fecha_pago='".$fechaPago."',
				forma_pago=".(GETPOST('formpago')!=''?"'".GETPOST('formpago')."'":'NULL').",
				moneda=".(GETPOST('monedapago')!=''?"'".GETPOST('monedapago')."'":'NULL').",
				tipo_cambio=".(GETPOST('tipocambio')!=''?"'".GETPOST('tipocambio')."'":'NULL').",
				monto=".(GETPOST('montop')!=''?"'".GETPOST('montop')."'":'NULL').",
				num_operacion=".(GETPOST('numoperacion')!=''?"'".GETPOST('numoperacion')."'":'NULL').",
				emisor_rfc=".(GETPOST('rfcemisorctaorigen')!=''?"'".GETPOST('rfcemisorctaorigen')."'":'NULL').",
				nombre_banco_ordenante=".(GETPOST('nombancoordenante')!=''?"'".GETPOST('nombancoordenante')."'":'NULL').",
				cuenta_ordenante=".(GETPOST('ctaordenante')!=''?"'".GETPOST('ctaordenante')."'":'NULL').",
				rfc_emisor_cuenta_beneficiario=".(GETPOST('rfcemisorctabeneficiario')!=''?"'".GETPOST('rfcemisorctabeneficiario')."'":'NULL').",
				cuenta_beneficiario=".(GETPOST('ctabeneficiario')!=''?"'".GETPOST('ctabeneficiario')."'":'NULL').",
				tipo_cadena_pago=".(GETPOST('tipocadenapago')!=''?"'".GETPOST('tipocadenapago')."'":'NULL').",
				certificado_pago=".(trim(GETPOST('certificadopago'))!=''?"'".GETPOST('certificadopago')."'":'NULL').",
				cadena_pago=".(trim(GETPOST('cadenaoriginal'))!=''?"'".GETPOST('cadenaoriginal')."'":'NULL').",
				sello_pago=".(trim(GETPOST('sellopago'))!=''?"'".GETPOST('sellopago')."'":'NULL')."
				WHERE id_pago = ".$pagcid_custom;
		
		if ($res = $db->query($sql3) && $resCFDI = $db->query($sqlCFDI)) {
			$sql4 = "UPDATE ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado
				SET
					idDocumento=".(GETPOST('idDocumento')!=''?"'".GETPOST('idDocumento')."'":'NULL').",
					serie=".(GETPOST('docSerie')!=''?"'".GETPOST('docSerie')."'":'NULL').",
					folio=".(GETPOST('docFolio')!=''?"'".GETPOST('docFolio')."'":'NULL').",
					monedaDR=".(GETPOST('monedaDR')!=''?"'".GETPOST('monedaDR')."'":'NULL').",
					tipoCambioDR=".(GETPOST('tipocambiodr')!=''?"'".GETPOST('tipocambiodr')."'":'NULL').",
					metodoDePagoDR=".(GETPOST('metodoPDR')!=''?"'".GETPOST('metodoPDR')."'":'NULL').",
					numParcialidad=".(GETPOST('numparcialidaddr')!=''?"'".GETPOST('numparcialidaddr')."'":'NULL').",
					impSaldoAnt=".(GETPOST('impSaldoAnterior')!=''?"'".GETPOST('impSaldoAnterior')."'":'NULL').",
					impPagado=".(GETPOST('impPagadodr')!=''?"'".GETPOST('impPagadodr')."'":'NULL').",
					impSaldoInsoluto=".(GETPOST('impSaldoInsoluto')!=''?"'".GETPOST('impSaldoInsoluto')."'":'NULL')."
				WHERE fk_recepago=".$resultado->rowid;

			$res2 = $db->query($sql4);


			$sqlCFDIDoc = "UPDATE cfdi_comprobante_relacionados
				SET
					id_documento=".(GETPOST('idDocumento')!=''?"'".GETPOST('idDocumento')."'":'NULL').",
					monedaDR=".(GETPOST('monedaDR')!=''?"'".GETPOST('monedaDR')."'":'NULL').",
					tipoCambioDR=".(GETPOST('tipocambiodr')!=''?"'".GETPOST('tipocambiodr')."'":'NULL').",
					metodoPDR=".(GETPOST('metodoPDR')!=''?"'".GETPOST('metodoPDR')."'":'NULL').",
					numparcialidaddr=".(GETPOST('numparcialidaddr')!=''?"'".GETPOST('numparcialidaddr')."'":'NULL').",
					impSaldoAnterior=".(GETPOST('impSaldoAnterior')!=''?"'".GETPOST('impSaldoAnterior')."'":'NULL').",
					impPagadodr=".(GETPOST('impPagadodr')!=''?"'".GETPOST('impPagadodr')."'":'NULL').",
					impSaldoInsoluto=".(GETPOST('impSaldoInsoluto')!=''?"'".GETPOST('impSaldoInsoluto')."'":'NULL')."
					WHERE id_pago = ".$pagcid_custom;

			$resCFDIDoc = $db->query($sqlCFDIDoc);

			print "<script>window.location.href='pagos.php?action=cfdi2&facid=".$facid_custom."&pagcid=".$pagcid_custom."'</script>";
		} else {
			print "<script>window.location.href='pagos.php?action=cfdi2&facid=".$facid_custom."&pagcid=".$pagcid_custom."&mesg=err1'</script>";
		}
	}
}

$form = $form2;

if ($action=="cfdi") {

	$obpag = new Paiement($db);
	$obpag->fetch(GETPOST("pagcid"));
	print '<table class="border centpercent">'."\n";

	// Ref
	print '<tr><td class="titlefield">'.$langs->trans('Ref').' Pago</td><td colspan="3">';
	print $form->showrefnav($obpag, 'ref', $linkback, 0, 'ref', 'ref', '');
	print '</td></tr>';
	
	// Date payment
	print '<tr><td>'.$langs->trans("Date").'</td><td colspan="3">';
	print $form->editfieldval("Date",'datep',$obpag->date,$obpag,$user->rights->facture->paiement,'datepicker','',null,$langs->trans('PaymentDateUpdateSucceeded'));
	print '</td></tr>';
	
	// Payment type (VIR, LIQ, ...)
	$labeltype=$langs->trans("PaymentType".$obpag->type_code)!=("PaymentType".$obpag->type_code)?$langs->trans("PaymentType".$obpag->type_code):$obpag->type_libelle;
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="3">'.$labeltype.'</td></tr>';
	
	// Payment numero
	print '<tr><td>'.$langs->trans("Number").'</td><td colspan="3">';
	print $form->editfieldval("Numero",'num_paiement',$obpag->numero,$obpag,$obpag->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
	print '</td></tr>';
	
	// Amount
	print '<tr><td>'.$langs->trans('Amount').'</td><td colspan="3">'.price($obpag->montant,'',$langs,0,0,-1,$conf->currency).'</td></tr>';
	
	// Note
	print '<tr><td class="tdtop">'.$langs->trans("Note").'</td><td colspan="3">';
	print $form->editfieldval("Note",'note',$obpag->note,$obpag,$user->rights->facture->paiement,'textarea');
	print '</td></tr>';
	
	$disable_delete = 0;
	// Bank account
	if (! empty($conf->banque->enabled))
	{
		if ($obpag->fk_account > 0)
		{
			$bankline=new AccountLine($db);
			$bankline->fetch($obpag->bank_line);
			if ($bankline->rappro)
			{
				$disable_delete = 1;
				$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
			}
	
			print '<tr>';
			print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td colspan="3">';
			print $bankline->getNomUrl(1,0,'showconciliated');
			print '</td>';
			print '</tr>';
	
			print '<tr>';
			print '<td>'.$langs->trans('BankAccount').'</td>';
			print '<td colspan="3">';
			$accountstatic=new Account($db);
			$accountstatic->fetch($bankline->fk_account);
			$monedaa=$accountstatic->currency_code;
			print $accountstatic->getNomUrl(1);
			print '</td>';
			print '</tr>';
	
			if ($object->type_code == 'CHQ' && $bankline->fk_bordereau > 0)
			{
				dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
				$bordereau = new RemiseCheque($db);
				$bordereau->fetch($bankline->fk_bordereau);
	
				print '<tr>';
				print '<td>'.$langs->trans('CheckReceipt').'</td>';
				print '<td colspan="3">';
				print $bordereau->getNomUrl(1);
				print '</td>';
				print '</tr>';
			}
		}
	}
	print '</table>';
	
	print '<br>';
	
	$datep="";
	$formpago="";
	$monedapago="";
	$tipocambio="";
	$montop="";
	
	$numoperacion="";
	$rfcemisorctaorigen="";
	$nombancoordenante="";
	$ctaordenante="";
	$rfcemisorctabeneficiario="";
	$ctabeneficiario="";
	$tipocadenapago="";
	$certificadopago="";
	$cadenaoriginal="";
	$sellopago="";
	
	$idDocumento="";
	$monedaDR="";
	$metodoPDR="";
	$docSerie="";
	$docFolio="";
	$tipocambiodr="";
	$numparcialidaddr="";
	$impSaldoAnterior="";
	$impPagadodr="";
	$impSaldoInsoluto="";
	
	$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".GETPOST("facid")." AND fk_paiement=".GETPOST("pagcid");
	//print $sql;
	$req=$db->query($sql);
	$nmr=$db->num_rows($req);
	if($nmr>0){
		$rsl=$db->fetch_object($req);
		$datep=strtotime($rsl->fechaPago);
		$formpago=$rsl->formaDePago;
		$monedapago=$rsl->monedaP;
		$tipocambio=$rsl->TipoCambioP;
		$montop=$rsl->monto;
		$numoperacion=$rsl->numOperacion;
		$rfcemisorctaorigen=$rsl->rfcEmisorCtaOrd;
		$nombancoordenante=$rsl->nomBancoOrdExt;
		$ctaordenante=$rsl->ctaOrdenante;
		$rfcemisorctabeneficiario=$rsl->rfcEmisorCtaBen;
		$ctabeneficiario=$rsl->ctaBeneficiario;
		$tipocadenapago=$rsl->tipoCadPago;
		$certificadopago=$rsl->certPago;
		$cadenaoriginal=$rsl->cadPago;
		$sellopago=$rsl->selloPago;
		$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado WHERE fk_recepago=".$rsl->rowid;
		$req=$db->query($sql);
		$rsl=$db->fetch_object($req);
		$idDocumento=$rsl->idDocumento;
		$monedaDR=$rsl->monedaDR;
		$metodoPDR=$rsl->metodoDePagoDR;
		$docSerie=$rsl->serie;
		$docFolio=$rsl->folio;
		$tipocambiodr=$rsl->tipoCambioDR;
		$numparcialidaddr=$rsl->numParcialidad;
		$impSaldoAnterior=$rsl->impSaldoAnt;
		$impPagadodr=$rsl->impPagado;
		$impSaldoInsoluto=$rsl->impSaldoInsoluto;
	}
	/*?>
		<script type="text/javascript">
		//$( document ).ready(function() {
			//mosocultar();
		//});
		function mosocultar(){
			//alert("sadsadasd");
			if($("#mos").val()==0){
				$(".oculta").hide();
				$("#mos").val(1);
			}else{
				$(".oculta").show();
				$("#mos").val(0);
			}
		}
		</script>
		<?php*/
		print '<form method="POST" action="pagos.php?action=guardar">';
		print '<input type="hidden" name="facid" value="'.GETPOST("facid").'">';
		print '<input type="hidden" name="pagcid" value="'.GETPOST("pagcid").'">';
		print '<table class="border centpercent">'."\n";
		$formp = new Form($db);
		print '<tr><td class="titlefield" colspan="4" align="center"><strong>Timbrado de Pagos CFDI</strong></td>';
		//print '<tr><td class="titlefield" colspan="4" align="center"><strong>Timbrado de Pagos CFDI</strong> <button class="button" onclick="mosocultar()">Mostrar/Ocultar opcionales</button></td>';
		print '</tr>';
		if($datep==""){
			$datep=$obpag->date;
		}
		if($formpago==""){
			$sql="SELECT accountancy_code FROM ".MAIN_DB_PREFIX."c_paiement WHERE code='".$obpag->type_code."'";
			$req=$db->query($sql);
			$rnr=$db->num_rows($req);
			if($rnr>0){
				$res=$db->fetch_object($req);
				$formpago=$res->accountancy_code;
			}else{
				$formpago="";
			}
		}
		if($monedapago==""){
			if($monedaa){
				$monedapago=$monedaa;
			}
		}
		if($montop==""){
			$montop=str_replace(",","",number_format($obpag->montant,2));
		}
		if($idDocumento=="" && $monedaDR=="" && $metodoPDR==""){
			$sql="SELECT a.uuid, a.divisa, c.accountancy_code FROM ".MAIN_DB_PREFIX."cfdimx a, ".MAIN_DB_PREFIX."facture b,".MAIN_DB_PREFIX."c_paiement c  
			WHERE a.fk_facture=".GETPOST("facid")." AND a.fk_facture=b.rowid AND b.fk_mode_reglement=c.id";
			//print $sql;
			$rq=$db->query($sql);
			$rs=$db->fetch_object($rq);
			$sqlm="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'formpagcfdi'";
			$resqlv=$db->query($sqlm);
			$existe_form = $db->num_rows($resqlv);
			if($existe_form>0){
				$sqlv="SELECT formpagcfdi FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object=".GETPOST("facid");
				$rv=$db->query($sqlv);
				$vrs=$db->fetch_object($rv);
				$factura_metodopago=$vrs->formpagcfdi;
			}else{
				$factura_metodopago="";
			}
			$monedaDR=$rs->divisa;
			$idDocumento=$rs->uuid;
			$metodoPDR=$factura_metodopago;
		}

		$savedHour = date('G', $datep);
		$savedMin = date('i', $datep);
		
		print '<tr><td class="titlefield"><strong>Fecha de Pago</strong></td>';
		print '<td>';
		print '<input type="hidden" name="mos" id="mos" value="0">';
		if($datep != "") { 
			print'<input id="fechaPago" readonly="readonly" name="fechaPago" type="date" size="9" maxlength="9"  value="'.date("Y-m-d",$datep).'">';
		}

		print '<select class="flat " id="fechaPagohour" name="fechaPagohour">';
		for($k = 0; $k < 24; $k++) {
			if($k < 10) {
				$hourValue = "0".$k;
				if($hourValue == $savedHour) {
					print '<option selected value="'.$hourValue.'">'.$hourValue.'</option>';
				}
				else {
					print '<option value="'.$hourValue.'">'.$hourValue.'</option>';	
				}
			}
			else {
				$hourValue = $k;
				if($hourValue == $savedHour) {
					print '<option selected value="'.$hourValue.'">'.$hourValue.'</option>';
				} else {
					print '<option value="'.$hourValue.'">'.$hourValue.'</option>';
				}
			}
		}
		print '</select>';

		print '<select class="flat " id="fechaPagomin" name="fechaPagomin">';
		for($k = 0; $k < 60; $k++) {
			if($k < 10) {
				$minValue = "0".$k;
				if($minValue == $savedMin) {
					print '<option selected value="'.$minValue.'">'.$minValue.'</option>';	
				}
				else {
					print '<option value="'.$minValue.'">'.$minValue.'</option>';
				}
					
			}
			else {
				$minValue = $k;
				if($minValue == $savedMin) {
					print '<option selected value="'.$minValue.'">'.$minValue.'</option>';
				}
				else {
					print '<option value="'.$minValue.'">'.$minValue.'</option>';
				}
			}
		}
		
		print '</td>';
		
		print '<td class="titlefield"><strong>Forma de Pago</strong></td>';
		print '<td><input type="text" name="formpago" value="'.$formpago.'" ></td></tr>';
	
		print '<tr><td class="titlefield"><strong>Moneda del Pago</strong></td>';
		print '<td><input type="text" name="monedapago" value="'.$monedapago.'" ></td>';
		
		print '<td class="titlefield"><strong>Monto</strong></td>';
		print '<td><input type="text" name="montop" value="'.$montop.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Tipo de cambio del Pago</td>';
		print '<td><input type="text" name="tipocambio" value="'.$tipocambio.'" ></td>';
		
		print '<td class="titlefield">Numero de operacion</td>';
		print '<td><input type="text" name="numoperacion" value="'.$numoperacion.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">RFC emisor cuenta ordenante</td>';
		print '<td><input type="text" name="rfcemisorctaorigen" value="'.$rfcemisorctaorigen.'" ></td>';
		
		print '<td class="titlefield">Nombre del banco ordenante (Extranjero)</td>';
		print '<td><input type="text" name="nombancoordenante" value="'.$nombancoordenante.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Cuenta Ordenante</td>';
		print '<td><input type="text" name="ctaordenante" value="'.$ctaordenante.'" ></td>';
		
		print '<td class="titlefield">RFC emisor cuenta beneficiario</td>';
		print '<td><input type="text" name="rfcemisorctabeneficiario" value="'.$rfcemisorctabeneficiario.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Cuenta beneficiario</td>';
		print '<td><input type="text" name="ctabeneficiario" value="'.$ctabeneficiario.'" ></td>';
		
		print '<td class="titlefield">Tipo cadena de pago</td>';
		print '<td><input type="text" name="tipocadenapago" value="'.$tipocadenapago.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Certificado del pago</td>';
		print '<td colspan="3"><textarea name="certificadopago" rows="4" cols="60">'.$certificadopago.'
				</textarea></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Cadena Original del comprobante pago</td>';
		print '<td colspan="3"><textarea name="cadenaoriginal" rows="4" cols="60">'.$cadenaoriginal.'
				</textarea></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Sello Pago</td>';
		print '<td colspan="3"><textarea name="sellopago" rows="4" cols="60">'.$sellopago.'
				</textarea></td></tr>';
		////////////////////////////////////////////////////////////////
		print '<tr><td class="titlefield" colspan="4" align="center"><strong>Documento relacionado</strong></td></tr>';
		
		print '<tr><td class="titlefield"><strong>ID Documento</strong></td>';
		print '<td colspan="3"><input type="text" name="idDocumento" value="'.$idDocumento.'" size="40"></td></tr>';
		
		print '<tr><td class="titlefield"><strong>Moneda del Documento Relacionado</strong></td>';
		print '<td><input type="text" name="monedaDR" value="'.$monedaDR.'" ></td>';
		
		print '<td class="titlefield"><strong>Metodo de Pago Documento Relacionado</strong></td>';
		print '<td><input type="text" name="metodoPDR" value="'.$metodoPDR.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Serie</td>';
		print '<td><input type="text" name="docSerie" value="'.$docSerie.'" ></td>';
		
		print '<td class="titlefield">Folio</td>';
		print '<td><input type="text" name="docFolio" value="'.$docFolio.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Tipo Cambio Documento Relacionado</td>';
		print '<td><input type="text" name="tipocambiodr" value="'.$tipocambiodr.'" ></td>';
		
		print '<td class="titlefield">Numero de Parcialidad</td>';
		print '<td><input type="text" name="numparcialidaddr" value="'.$numparcialidaddr.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Importe Saldo Anterior</td>';
		print '<td><input type="text" name="impSaldoAnterior" value="'.$impSaldoAnterior.'" ></td>';
		
		print '<td class="titlefield">Importe Pagado</td>';
		print '<td><input type="text" name="impPagadodr" value="'.$impPagadodr.'" ></td></tr>';
		
		print '<tr class="oculta"><td class="titlefield">Importe Saldo Insoluto</td>';
		print '<td colspan="3"><input type="text" name="impSaldoInsoluto" value="'.$impSaldoInsoluto.'" ></td></tr>';
		
		
		print '<tr><td class="titlefield" colspan="4" align="center"><input type="submit" name="guardar" value="Guardar informacion" class="button"></td></tr>';
		
		print '</table>';
		print '</form>';
		$client = new nusoap_client($wscfdi, 'wsdl');
		$result = $client->call('validaCliente',array( "rfc"=>$conf->global->MAIN_INFO_SIREN ));
		$status_clt = $result["return"]["status_cliente_id"];
		$status_clt_desc = $result["return"]["status_cliente_desc"];
		$folios_timbrados = $result["return"]["folios_timbrados"];
		$folios_adquiridos = $result["return"]["folios_adquiridos"];
		$folios_disponibles = $result["return"]["folios_disponibles"];
		if( $modo_timbrado==1 ){
			$modo_timbrado_desc = "Produccion";
		}else{ 
			$modo_timbrado_desc = "Pruebas"; 
		}
		print '<br>
				<div style="width:380px; border:solid 1px; height:40px; background-color:#990000; padding:10px">
					<font color="#FFFFFF">
						<strong>Modalidad de Facturacion:</strong> '.$modo_timbrado_desc.'<br>
						<strong>Folios Disponibles:</strong> '.$folios_disponibles.'<br>
						<strong>Folios Timbrados:</strong> '.$folios_timbrados.'<br>
					</font>
				</div>
				<br>
				<div style="font-size:14px">';
		if($nmr==0){
			print "Debe guardar la informacion del Pago para poder timbrar";
		}else{
			if($folios_disponibles>0 )
			{
				print '<a class="butAction" href="pagos/generaCFDI.php?facid='.GETPOST("facid").'&pagcid='.GETPOST("pagcid").'&action=generaCFDI">Generar CFDI</a>'."<br>";
			}
		}
		print "</div>";
}

/*
	Acción para mostrar el formulario de pago (custom).
*/
if ($action == "cfdi2") {

	$pagoFacturado = false;
	$pagoCancelado = false;
	$CFDIPendiente = true;
	$uuidCFDIFinal = "";

	$sqlpagoFacturado = "SELECT * FROM cfdi_comprobante
					WHERE fk_payment = ".GETPOST("pagcid");

	$respagoFacturado = $db->query($sqlpagoFacturado);
	$nmrPagoFacturado=$db->num_rows($respagoFacturado);
	if($nmrPagoFacturado>0){
		$rsl=$db->fetch_object($respagoFacturado);
		if( $rsl->cancelado == 2) {
			$pagoCancelado = true;
		}
		else if($rsl->UUID == "Pendiente") {
			//$pagoFacturado = true;
			$uuidCFDIFinal = $rsl->UUID;
		}
		else if($rsl->UUID != null){
			$pagoFacturado = true;
			$CFDIPendiente = false;
			$uuidCFDIFinal = $rsl->UUID;
		}
	}

	$obpag = new Paiement($db);
	$obpag->fetch(GETPOST("pagcid"));

	print '<table class="border centpercent" style="background: #d2e7fa">'."\n";

	// Ref
	print '<tr><td class="titlefield">'.$langs->trans('Ref').' Pago</td><td colspan="3">';
	print $form->showrefnav($obpag, 'ref', $linkback, 0, 'ref', 'ref', '');
	print '</td></tr>';
	
	// Date payment
	print '<tr><td>'.$langs->trans("Date").'</td><td colspan="3">';
	print $form->editfieldval("Date",'datep',$obpag->date,$obpag,$user->rights->facture->paiement,'datepicker','',null,$langs->trans('PaymentDateUpdateSucceeded'));
	print '</td></tr>';
	
	// Payment type (VIR, LIQ, ...)
	$labeltype=$langs->trans("PaymentType".$obpag->type_code)!=("PaymentType".$obpag->type_code)?$langs->trans("PaymentType".$obpag->type_code):$obpag->type_libelle;
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="3">'.$labeltype.'</td></tr>';
	
	// Payment numero
	print '<tr><td>'.$langs->trans("Number").'</td><td colspan="3">';
	print $form->editfieldval("Numero",'num_paiement',$obpag->numero,$obpag,$obpag->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
	print '</td></tr>';
	// Amount
	print '<tr><td>'.$langs->trans('Amount').'</td><td colspan="3">'.price($obpag->montant,'',$langs,0,0,-1,$conf->currency).'</td></tr>';
	
	// Note
	print '<tr><td class="tdtop">'.$langs->trans("Note").'</td><td colspan="3">';
	print $form->editfieldval("Note",'note',$obpag->note,$obpag,$user->rights->facture->paiement,'textarea');
	print '</td></tr>';
	
	$disable_delete = 0;

	// Bank account
	if (!empty($conf->banque->enabled)) {
		if ($obpag->fk_account > 0) {
			$bankline=new AccountLine($db);
			$bankline->fetch($obpag->bank_line);
			
			if ($bankline->rappro) {
				$disable_delete = 1;
				$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
			}
	
			print '<tr>';
			print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td colspan="3">';
			print $bankline->getNomUrl(1,0,'showconciliated');
			print '</td>';
			print '</tr>';
	
			print '<tr>';
			print '<td>'.$langs->trans('BankAccount').'</td>';
			print '<td colspan="3">';

			$accountstatic=new Account($db);
			$accountstatic->fetch($bankline->fk_account);
			$monedaa=$accountstatic->currency_code;

			print $accountstatic->getNomUrl(1);
			print '</td>';
			print '</tr>';
	
			if ($object->type_code == 'CHQ' && $bankline->fk_bordereau > 0) {
				dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
				
				$bordereau = new RemiseCheque($db);
				$bordereau->fetch($bankline->fk_bordereau);
	
				print '<tr>';
				print '<td>'.$langs->trans('CheckReceipt').'</td>';
				print '<td colspan="3">';
				print $bordereau->getNomUrl(1);
				print '</td>';
				print '</tr>';
			}
		}
	}

	print '</table>';
	print '<br>';
	
	$datep="";
	$formpago="";
	$monedapago="";
	$tipocambio="";
	$montop="";
	
	$numoperacion="";
	$rfcemisorctaorigen="";
	$nombancoordenante="";
	$ctaordenante="";
	$rfcemisorctabeneficiario="";
	$ctabeneficiario="";
	$tipocadenapago="";
	$certificadopago="";
	$cadenaoriginal="";
	$sellopago="";
	
	$idDocumento="";
	$monedaDR="";
	$metodoPDR="";
	$docSerie="";
	$docFolio="";
	$tipocambiodr="";
	$numparcialidaddr="";
	$impSaldoAnterior="";
	$impPagadodr="";
	$impSaldoInsoluto="";

	//Checkpoint 1 (bug saldo insoluto)
	if(!$pagoFacturado) {
		$sqlfacture = "SELECT * 
				FROM llx_facture 
				WHERE rowid = ".GETPOST("facid");
		$resFacture = $db->query($sqlfacture);
		$rslFacture=$db->fetch_object($resFacture);

		$sqlfacturePayments = "SELECT count(*) as numero, sum(amount) as pagado 
				FROM llx_paiement_facture 
				WHERE fk_facture = ".GETPOST("facid").
				" AND fk_paiement != ".GETPOST("pagcid");
		$resFacturePayments = $db->query($sqlfacturePayments);
		$rslFacturePayments=$db->fetch_object($resFacturePayments);
		$numparcialidaddr = (($rslFacturePayments->numero)+1);
		$impSaldoAnterior = ($rslFacture->total_ttc - $rslFacturePayments->pagado);
		$impPagadodr = number_format($obpag->montant, 2);
		$impSaldoInsoluto = $impSaldoAnterior - $obpag->montant;
		
	}

	$facid_custom = GETPOST('facid'); // ".GETPOST('facid').",
	$pagcid_custom = GETPOST('pagcid'); // ".GETPOST('pagcid').",
	
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".$facid_custom." AND fk_paiement=".$pagcid_custom;
	$req = $db->query($sql);
	$nmr = $db->num_rows($req);

	if ($nmr > 0) {
		$rsl=$db->fetch_object($req);
		$datep=strtotime($rsl->fechaPago);
		$formpago=$rsl->formaDePago;
		$monedapago=$rsl->monedaP;
		$tipocambio=$rsl->TipoCambioP;
		$montop=$rsl->monto;
		$numoperacion=$rsl->numOperacion;
		$rfcemisorctaorigen=$rsl->rfcEmisorCtaOrd;
		$nombancoordenante=$rsl->nomBancoOrdExt;
		$ctaordenante=$rsl->ctaOrdenante;
		$rfcemisorctabeneficiario=$rsl->rfcEmisorCtaBen;
		$ctabeneficiario=$rsl->ctaBeneficiario;
		$tipocadenapago=$rsl->tipoCadPago;
		$certificadopago=$rsl->certPago;
		$cadenaoriginal=$rsl->cadPago;
		$sellopago=$rsl->selloPago;
		
		$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado WHERE fk_recepago=".$rsl->rowid;
		$req=$db->query($sql);
		$rsl=$db->fetch_object($req);

		$idDocumento=$rsl->idDocumento;
		$monedaDR=$rsl->monedaDR;
		$metodoPDR=$rsl->metodoDePagoDR;
		$docSerie=$rsl->serie;
		$docFolio=$rsl->folio;
		$tipocambiodr=$rsl->tipoCambioDR;
		$numparcialidaddr=$rsl->numParcialidad;
		$impSaldoAnterior=$rsl->impSaldoAnt;
		$impPagadodr=$rsl->impPagado;
		$impSaldoInsoluto=$rsl->impSaldoInsoluto;
	}

	



	print '<form method="POST" action="pagos.php?action=guardar2">';
	print '<input type="hidden" name="facid" value="'.GETPOST("facid").'">';
	print '<input type="hidden" name="pagcid" value="'.GETPOST("pagcid").'">';
	print '<table class="border centpercent" style="background: #d2e7fa">'."\n";
		
	$formp = new Form($db);
		
	print '<tr><td class="titlefield" colspan="4" align="center"><strong>Timbrado de Pagos CFDI</strong></td>';
	print '</tr>';

	if ($datep == "") {
		$datep=$obpag->date;
	}

	if ($formpago == "") {
		$sql="SELECT accountancy_code FROM ".MAIN_DB_PREFIX."c_paiement WHERE code='".$obpag->type_code."'";
		$req=$db->query($sql);
		$rnr=$db->num_rows($req);

		if ($rnr > 0) {
			$res=$db->fetch_object($req);
			$formpago=$res->accountancy_code;
		} else {
			$formpago="";
		}
	}
		
	if ($monedapago == "") {
		if ($monedaa) {
			$monedapago=$monedaa;
		}
	}
	
	if ($montop == "") {
		$montop=str_replace(",","",number_format($obpag->montant,2));
	}
	
	if ($idDocumento == "" && $monedaDR == "" && $metodoPDR == "") {
		$sql="SELECT a.uuid, a.divisa, c.accountancy_code FROM ".MAIN_DB_PREFIX."cfdimx a, ".MAIN_DB_PREFIX."facture b,".MAIN_DB_PREFIX."c_paiement c  
		WHERE a.fk_facture=".GETPOST("facid")." AND a.fk_facture=b.rowid AND b.fk_mode_reglement=c.id";
		$rq=$db->query($sql);
		$rs=$db->fetch_object($rq);
		$sqlm="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."facture_extrafields LIKE 'formpagcfdi'";
		$resqlv=$db->query($sqlm);
		$existe_form = $db->num_rows($resqlv);
		
		if ($existe_form > 0) {
			$sqlv="SELECT formpagcfdi FROM ".MAIN_DB_PREFIX."facture_extrafields WHERE fk_object=".GETPOST("facid");
			$rv=$db->query($sqlv);
			$vrs=$db->fetch_object($rv);
			$factura_metodopago=$vrs->formpagcfdi;
		} else {
			$factura_metodopago="";
		}

		$monedaDR=$rs->divisa;
		$idDocumento=$rs->uuid;
		$metodoPDR=$factura_metodopago;
	}

	$savedHour = date('G', $datep);
	$savedMin = date('i', $datep);
		
	print '<tr><td class="titlefield"><strong>Fecha de Pago</strong></td>';
	print '<td>';
	print '<input type="hidden" name="mos" id="mos" value="0">';

	if ($datep != "") { 
		print'<input id="fechaPago" readonly="readonly" name="fechaPago" type="date" size="9" maxlength="9"  value="'.date("Y-m-d",$datep).'">';
	}

	print '<select class="flat " id="fechaPagohour" name="fechaPagohour">';

	for ($k = 0; $k < 24; $k++) {
		if ($k < 10) {
			$hourValue = "0".$k;

			if ($hourValue == $savedHour) {
				print '<option selected value="'.$hourValue.'">'.$hourValue.'</option>';
			} else {
				print '<option value="'.$hourValue.'">'.$hourValue.'</option>';	
			}
		} else {
			$hourValue = $k;

			if ($hourValue == $savedHour) {
				print '<option selected value="'.$hourValue.'">'.$hourValue.'</option>';
			} else {
				print '<option value="'.$hourValue.'">'.$hourValue.'</option>';
			}
		}
	}

	print '</select>';
	print '<select class="flat " id="fechaPagomin" name="fechaPagomin">';

	for ($k = 0; $k < 60; $k++) {
		if ($k < 10) {
			$minValue = "0".$k;
			
			if ($minValue == $savedMin) {
				print '<option selected value="'.$minValue.'">'.$minValue.'</option>';	
			} else {
				print '<option value="'.$minValue.'">'.$minValue.'</option>';
			}
		} else {
			$minValue = $k;

			if ($minValue == $savedMin) {
				print '<option selected value="'.$minValue.'">'.$minValue.'</option>';
			} else {
				print '<option value="'.$minValue.'">'.$minValue.'</option>';
			}
		}
	}
		
	print '</td>';
	
	print '<td class="titlefield"><strong>Forma de Pago</strong></td>';
	print '<td><input type="text" name="formpago" value="'.$formpago.'" ></td></tr>';

	print '<tr><td class="titlefield"><strong>Moneda del Pago</strong></td>';
	print '<td><input type="text" name="monedapago" value="'.$monedapago.'" ></td>';
	
	print '<td class="titlefield"><strong>Monto</strong></td>';
	print '<td><input type="text" name="montop" value="'.$montop.'" ></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Tipo de cambio del Pago</td>';
	print '<td><input type="text" name="tipocambio" value="'.$tipocambio.'" ></td>';
	
	print '<td class="titlefield">Numero de operacion</td>';
	print '<td><input type="text" name="numoperacion" value="'.$numoperacion.'" ></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">RFC emisor cuenta ordenante</td>';
	print '<td><input type="text" name="rfcemisorctaorigen" value="'.$rfcemisorctaorigen.'" ></td>';
	
	print '<td class="titlefield">Nombre del banco ordenante (Extranjero)</td>';
	print '<td><input type="text" name="nombancoordenante" value="'.$nombancoordenante.'" ></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Cuenta Ordenante</td>';
	print '<td><input type="text" name="ctaordenante" value="'.$ctaordenante.'" ></td>';
	
	print '<td class="titlefield">RFC emisor cuenta beneficiario</td>';
	print '<td><input type="text" name="rfcemisorctabeneficiario" value="'.$rfcemisorctabeneficiario.'" ></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Cuenta beneficiario</td>';
	print '<td><input type="text" name="ctabeneficiario" value="'.$ctabeneficiario.'" ></td>';
	
	print '<td class="titlefield">Tipo cadena de pago</td>';
	print '<td><input type="text" name="tipocadenapago" value="'.$tipocadenapago.'" ></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Certificado del pago</td>';
	print '<td colspan="3"><textarea name="certificadopago" rows="4" cols="60">'.$certificadopago.'
			</textarea></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Cadena Original del comprobante pago</td>';
	print '<td colspan="3"><textarea name="cadenaoriginal" rows="4" cols="60">'.$cadenaoriginal.'
			</textarea></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Sello Pago</td>';
	print '<td colspan="3"><textarea name="sellopago" rows="4" cols="60">'.$sellopago.'
			</textarea></td></tr>';

	////////////////////////////////////////////////////////////////

	print '<tr><td class="titlefield" colspan="4" align="center"><strong>Documento relacionado</strong></td></tr>';
	
	print '<tr><td class="titlefield"><strong>ID Documento</strong></td>';
	print '<td colspan="3"><input type="text" name="idDocumento" value="'.$idDocumento.'" size="40"></td></tr>';
	
	print '<tr><td class="titlefield"><strong>Moneda del Documento Relacionado</strong></td>';
	print '<td><input type="text" name="monedaDR" value="'.$monedaDR.'" ></td>';
	
	print '<td class="titlefield"><strong>Metodo de Pago Documento Relacionado</strong></td>';
	print '<td><input type="text" name="metodoPDR" value="'.$metodoPDR.'" ></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Serie</td>';
	print '<td><input type="text" name="docSerie" value="'.$docSerie.'" ></td>';
	
	print '<td class="titlefield">Folio</td>';
	print '<td><input type="text" name="docFolio" value="'.$docFolio.'" ></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Tipo Cambio Documento Relacionado</td>';
	print '<td><input type="text" name="tipocambiodr" value="'.$tipocambiodr.'" ></td>';
	
	print '<td class="titlefield">Numero de Parcialidad</td>';
	print '<td><input type="text" name="numparcialidaddr" value="'.$numparcialidaddr.'" ></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Importe Saldo Anterior</td>';
	print '<td><input type="text" name="impSaldoAnterior" value="'.$impSaldoAnterior.'" ></td>';
	
	print '<td class="titlefield">Importe Pagado</td>';
	print '<td><input type="text" name="impPagadodr" value="'.$impPagadodr.'" ></td></tr>';
	
	print '<tr class="oculta"><td class="titlefield">Importe Saldo Insoluto</td>';
	print '<td colspan="3"><input type="text" name="impSaldoInsoluto" value="'.$impSaldoInsoluto.'" ></td></tr>';


	if(!$pagoCancelado) {
		if($pagoFacturado == false) {
			$laInfoDelPagoEstaGuardadaEnLasNuevasTablas = false;
			$sqlCFDICheck = "SELECT * FROM cfdi_complemento_pago
							WHERE id_pago = ".$pagcid_custom;
		
			$resCFDICheck = $db->query($sqlCFDICheck);
			$nmrCFDICheck=$db->num_rows($resCFDICheck);
			if($nmrCFDICheck > 0){
				$laInfoDelPagoEstaGuardadaEnLasNuevasTablas = true;
			}
	
			print '<tr>';
	
			print '<td style="background:#b2d6f7;border:#b2d6f7;padding:10px 0" colspan="2">';
			print '</td>';
		
			print '<td style="background:#b2d6f7;border:#b2d6f7;padding:10px 0;font-weight:bold">';
		
			if ($nmr == 0) {
				print 'Debe guardar la información del Pago para poder timbrarlo';
			} else if ($laInfoDelPagoEstaGuardadaEnLasNuevasTablas) {
				print '<a class="butAction" style="float:right" href="pagos.php?facid='.$object->id.'&pagcid='.GETPOST('pagcid','int').'&action=timbrarCFDIProfact">Generar CFDI</a>';
			} else {
				print 'Intenta guardar nuevamente la información del Pago para poder timbrarlo';
			}
		
			print '<td style="background:#b2d6f7;border:#b2d6f7;padding:10px 0">';
			print '<input type="submit" name="guardar2" value="Guardar información" class="button">';
			print '</td>';
			
			print '</tr>';
	
		} else {
			print '
			<script>
				var inputs = document.getElementsByTagName("INPUT");
				for (var i = 0; i < inputs.length; i++) {
					inputs[i].disabled = true;
				}
			</script>';
			print '<tr></tr>';
			print '<tr>';
			print '<td class="titlefield"><strong>Pago Timbrado - UUID: '.$uuidCFDIFinal.'</strong></td>';
			print '<td style="padding:10px 0">';
			print '<a class="butAction" style="float:right" href="pagos.php?facid='.$object->id.'&pagcid='.GETPOST('pagcid','int').'&action=validaCancelaTimbreProfact">Cancelar CFDI</a>';
			print '</td>';
		}
	
	} else {
		print '
		<script>
			var inputs = document.getElementsByTagName("INPUT");
			for (var i = 0; i < inputs.length; i++) {
				inputs[i].disabled = true;
			}
		</script>';
		print '<tr></tr>';
		print '<tr>';
		print '<td class="titlefield" colspan="4" style="background:#871614"><strong>Pago Cancelado</strong></td>';
	}
	
	print '</form>';
	print '</table>';
}

if($action=="cfdi1"){
	$obpag = new Paiement($db);
	$obpag->fetch(GETPOST("pagcid"));
	print '<table class="border centpercent">'."\n";

	// Ref
	print '<tr><td class="titlefield">'.$langs->trans('Ref').' Pago</td><td colspan="3">';
	print $form->showrefnav($obpag, 'ref', $linkback, 0, 'ref', 'ref', '');
	print '</td></tr>';

	// Date payment
	print '<tr><td>'.$langs->trans("Date").'</td><td colspan="3">';
	print $form->editfieldval("Date",'datep',$obpag->date,$obpag,$user->rights->facture->paiement,'datepicker','',null,$langs->trans('PaymentDateUpdateSucceeded'));
	print '</td></tr>';

	// Payment type (VIR, LIQ, ...)
	$labeltype=$langs->trans("PaymentType".$obpag->type_code)!=("PaymentType".$obpag->type_code)?$langs->trans("PaymentType".$obpag->type_code):$obpag->type_libelle;
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="3">'.$labeltype.'</td></tr>';

	// Payment numero
	print '<tr><td>'.$langs->trans("Number").'</td><td colspan="3">';
	print $form->editfieldval("Numero",'num_paiement',$obpag->numero,$obpag,$obpag->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
	print '</td></tr>';

	// Amount
	print '<tr><td>'.$langs->trans('Amount').'</td><td colspan="3">'.price($obpag->montant,'',$langs,0,0,-1,$conf->currency).'</td></tr>';

	// Note
	print '<tr><td class="tdtop">'.$langs->trans("Note").'</td><td colspan="3">';
	print $form->editfieldval("Note",'note',$obpag->note,$obpag,$user->rights->facture->paiement,'textarea');
	print '</td></tr>';

	$disable_delete = 0;
	// Bank account
	if (! empty($conf->banque->enabled))
	{
		if ($obpag->fk_account > 0)
		{
			$bankline=new AccountLine($db);
			$bankline->fetch($obpag->bank_line);
			if ($bankline->rappro)
			{
				$disable_delete = 1;
				$title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
			}

			print '<tr>';
			print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td colspan="3">';
			print $bankline->getNomUrl(1,0,'showconciliated');
			print '</td>';
			print '</tr>';

			print '<tr>';
			print '<td>'.$langs->trans('BankAccount').'</td>';
			print '<td colspan="3">';
			$accountstatic=new Account($db);
			$accountstatic->fetch($bankline->fk_account);
			$monedaa=$accountstatic->currency_code;
			print $accountstatic->getNomUrl(1);
			print '</td>';
			print '</tr>';

			if ($object->type_code == 'CHQ' && $bankline->fk_bordereau > 0)
			{
				dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
				$bordereau = new RemiseCheque($db);
				$bordereau->fetch($bankline->fk_bordereau);

				print '<tr>';
				print '<td>'.$langs->trans('CheckReceipt').'</td>';
				print '<td colspan="3">';
				print $bordereau->getNomUrl(1);
				print '</td>';
				print '</tr>';
			}
		}
	}
	print '</table>';

	print '<br>';

	$datep="";
	$formpago="";
	$monedapago="";
	$tipocambio="";
	$montop="";

	$numoperacion="";
	$rfcemisorctaorigen="";
	$nombancoordenante="";
	$ctaordenante="";
	$rfcemisorctabeneficiario="";
	$ctabeneficiario="";
	$tipocadenapago="";
	$certificadopago="";
	$cadenaoriginal="";
	$sellopago="";

	$idDocumento="";
	$monedaDR="";
	$metodoPDR="";
	$docSerie="";
	$docFolio="";
	$tipocambiodr="";
	$numparcialidaddr="";
	$impSaldoAnterior="";
	$impPagadodr="";
	$impSaldoInsoluto="";
	$uuidP="";
	$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos WHERE fk_facture=".GETPOST("facid")." AND fk_paiement=".GETPOST("pagcid");
	//print $sql;
	$req=$db->query($sql);
	$nmr=$db->num_rows($req);
	if($nmr>0){
		$rsl=$db->fetch_object($req);
		$datep=strtotime($rsl->fechaPago);
		$formpago=$rsl->formaDePago;
		$monedapago=$rsl->monedaP;
		$tipocambio=$rsl->TipoCambioP;
		$montop=$rsl->monto;
		$numoperacion=$rsl->numOperacion;
		$rfcemisorctaorigen=$rsl->rfcEmisorCtaOrd;
		$nombancoordenante=$rsl->nomBancoOrdExt;
		$ctaordenante=$rsl->ctaOrdenante;
		$rfcemisorctabeneficiario=$rsl->rfcEmisorCtaBen;
		$ctabeneficiario=$rsl->ctaBeneficiario;
		$tipocadenapago=$rsl->tipoCadPago;
		$certificadopago=$rsl->certPago;
		$cadenaoriginal=$rsl->cadPago;
		$sellopago=$rsl->selloPago;
		$uuidP=$rsl->uuid;
		$sql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_recepcion_pagos_docto_relacionado WHERE fk_recepago=".$rsl->rowid;
		$req=$db->query($sql);
		$rsl=$db->fetch_object($req);
		$idDocumento=$rsl->idDocumento;
		$monedaDR=$rsl->monedaDR;
		$metodoPDR=$rsl->metodoDePagoDR;
		$docSerie=$rsl->serie;
		$docFolio=$rsl->folio;
		$tipocambiodr=$rsl->tipoCambioDR;
		$numparcialidaddr=$rsl->numParcialidad;
		$impSaldoAnterior=$rsl->impSaldoAnt;
		$impPagadodr=$rsl->impPagado;
		$impSaldoInsoluto=$rsl->impSaldoInsoluto;
		
	}
	/*		?>
		<script type="text/javascript">
		//$( document ).ready(function() {
		//mosocultar();
		//});
		function mosocultar(){
		//alert("sadsadasd");
		if($("#mos").val()==0){
		$(".oculta").hide();
		$("#mos").val(1);
		}else{
		$(".oculta").show();
		$("#mos").val(0);
		}
		}
		</script>
		<?php*/
	
	print '<input type="hidden" name="facid" value="'.GETPOST("facid").'">';
	print '<input type="hidden" name="pagcid" value="'.GETPOST("pagcid").'">';
	print '<table class="border centpercent">'."\n";
	$formp = new Form($db);
	print '<tr><td class="titlefield" colspan="4" align="center"><strong>Timbrado de Pagos CFDI</strong></td>';
	//print '<tr><td class="titlefield" colspan="4" align="center"><strong>Timbrado de Pagos CFDI</strong> <button class="button" onclick="mosocultar()">Mostrar/Ocultar opcionales</button></td>';
	print '</tr>';

	print '<tr><td class="titlefield"><strong>Fecha de Pago</strong></td>';
	print '<td>';
	print '</td>';

	print '<td class="titlefield"><strong>Forma de Pago</strong></td>';
	print '<td>'.$formpago.'</td></tr>';

	print '<tr><td class="titlefield"><strong>Moneda del Pago</strong></td>';
	print '<td>'.$monedapago.'</td>';

	print '<td class="titlefield"><strong>Monto</strong></td>';
	print '<td>'.$montop.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Tipo de cambio del Pago</td>';
	print '<td>'.$tipocambio.'</td>';

	print '<td class="titlefield">Numero de operacion</td>';
	print '<td>'.$numoperacion.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">RFC emisor cuenta ordenante</td>';
	print '<td>'.$rfcemisorctaorigen.'</td>';

	print '<td class="titlefield">Nombre del banco ordenante (Extranjero)</td>';
	print '<td>'.$nombancoordenante.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Cuenta Ordenante</td>';
	print '<td>'.$ctaordenante.'</td>';

	print '<td class="titlefield">RFC emisor cuenta beneficiario</td>';
	print '<td>'.$rfcemisorctabeneficiario.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Cuenta beneficiario</td>';
	print '<td>'.$ctabeneficiario.'</td>';

	print '<td class="titlefield">Tipo cadena de pago</td>';
	print '<td>'.$tipocadenapago.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Certificado del pago</td>';
	print '<td colspan="3">'.$certificadopago.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Cadena Original del comprobante pago</td>';
	print '<td colspan="3">'.$cadenaoriginal.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Sello Pago</td>';
	print '<td colspan="3">'.$sellopago.'</td></tr>';
	////////////////////////////////////////////////////////////////
	print '<tr><td class="titlefield" colspan="4" align="center"><strong>Documento relacionado</strong></td></tr>';

	print '<tr><td class="titlefield"><strong>ID Documento</strong></td>';
	print '<td colspan="3">'.$idDocumento.'</td></tr>';

	print '<tr><td class="titlefield"><strong>Moneda del Documento Relacionado</strong></td>';
	print '<td>'.$monedaDR.'</td>';

	print '<td class="titlefield"><strong>Metodo de Pago Documento Relacionado</strong></td>';
	print '<td>'.$metodoPDR.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Serie</td>';
	print '<td>'.$docSerie.'</td>';

	print '<td class="titlefield">Folio</td>';
	print '<td>'.$docFolio.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Tipo Cambio Documento Relacionado</td>';
	print '<td>'.$tipocambiodr.'</td>';

	print '<td class="titlefield">Numero de Parcialidad</td>';
	print '<td>'.$numparcialidaddr.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Importe Saldo Anterior</td>';
	print '<td>'.$impSaldoAnterior.'</td>';

	print '<td class="titlefield">Importe Pagado</td>';
	print '<td>'.$impPagadodr.'</td></tr>';

	print '<tr class="oculta"><td class="titlefield">Importe Saldo Insoluto</td>';
	print '<td colspan="3">'.$impSaldoInsoluto.'</td></tr>';
	print '</table>';
	$client = new nusoap_client($wscfdi, 'wsdl');
	$result = $client->call('validaCliente',array( "rfc"=>$conf->global->MAIN_INFO_SIREN ));
	$status_clt = $result["return"]["status_cliente_id"];
	$status_clt_desc = $result["return"]["status_cliente_desc"];
	$folios_timbrados = $result["return"]["folios_timbrados"];
	$folios_adquiridos = $result["return"]["folios_adquiridos"];
	$folios_disponibles = $result["return"]["folios_disponibles"];
	if( $modo_timbrado==1 ){
		$modo_timbrado_desc = "Produccion";
	}else{
		$modo_timbrado_desc = "Pruebas";
	}
	print '<br>
				<div style="width:380px; border:solid 1px; height:40px; background-color:#990000; padding:10px">
					<font color="#FFFFFF">
						<strong>Modalidad de Facturacion:</strong> '.$modo_timbrado_desc.'<br>
						<strong>Folios Disponibles:</strong> '.$folios_disponibles.'<br>
						<strong>Folios Timbrados:</strong> '.$folios_timbrados.'<br>
					</font>
				</div>
				<br>
				<div>';
	
	print '<strong>Pago Timbrado - UUID: </strong> '.$uuidP."&nbsp;<br>";
	print "</div>";
	if(1){
		$filedir=$conf->facture->dir_output.'/'.$object->ref.'/';
		$titletoshow="Archivos";
		$modulepart="facture";
		$conf->$modulepart->dir_output=$object->ref;
		$file_list=dol_dir_list($filedir,'files',0,'','\.meta$','date',SORT_DESC);
		// Affiche en-tete tableau si non deja affiche
		if (! empty($file_list) && ! $headershown)
		{
			$headershown=1;
			//$out.= '<div class="liste_titre">'.$titletoshow.'</div>';
			$out.= '<br><table class="border" summary="listofdocumentstable" width="50%">';
			$out.= '<tr class="liste_titre"><td colspan="3">'.$titletoshow.'</td></tr>';
		}
		// Loop on each file found
		if (is_array($file_list))
		{
			foreach($file_list as $file)
			{
				if(strpos($file["name"], $uuidP)){
					$var=!$var;
					// Define relative path for download link (depends on module)
					$relativepath=$object->ref."/".$res->rowid."/".$file["name"];								// Cas general
					//if ($filename) $relativepath=$filename."/".$file["name"];	// Cas propal, facture...
					// Autre cas
					//if ($modulepart == 'donation')            { $relativepath = get_exdir($filename,2).$file["name"]; }
					//if ($modulepart == 'export')              { $relativepath = $file["name"]; }
					$out.= "<tr ".$bc[$var].">";
					// Show file name with link to download
					$out.= '<td nowrap="nowrap">';
					$out.= '<a href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'"';
					//$out.= '<a href="'.DOL_DOCUMENT_ROOT."/cfdinomina/".$relativepath.'"';
					$mime=dol_mimetype($relativepath,'',0);
					if (preg_match('/text/',$mime)) $out.= ' target="_blank"';
					$out.= '>';
					$out.= img_mime($file["name"],$langs->trans("File").': '.$file["name"]).' '.dol_trunc($file["name"],$maxfilenamelength);
					$out.= '</a>'."\n";
					$out.= '</td>';
					// Show file size
					$size=(! empty($file['size'])?$file['size']:dol_filesize($filedir."/".$file["name"]));
					$out.= '<td align="right" nowrap="nowrap">'.dol_print_size($size).'</td>';
					// Show file date
					$date=(! empty($file['date'])?$file['date']:dol_filemtime($filedir."/".$file["name"]));
					$out.= '<td align="right" nowrap="nowrap">'.dol_print_date($date, 'dayhour').'</td>';
				}
	
			}
	
			$out.= '</tr>';
		}
		$out.= '</table><br><br>';
		print $out;
	}
}

if($action=="timbrarCFDIProfact") {
	
	$sqlTimbrarCFDI = "UPDATE cfdi_comprobante
	SET
		status = 0
		WHERE fk_payment = ".GETPOST('pagcid','int');
	$resTimbrarCFDI = $db->query($sqlTimbrarCFDI);

	print "<script>window.location.href='pagos.php?action=cfdi2&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."'</script>";
}

if($action=="validaCancelaTimbreProfact") {
	print '</div>';
	print '<div style="background:#b2d6f7;border:#b2d6f7;padding:10px 0">';
	print "<strong class='titlefield'>Confirmar borrar timbre de pago " . GETPOST('pagcid','int').'</strong>';
	print '<br/><br/>';
	print '<a class="butAction" href="pagos.php?facid='.$object->id.'&pagcid='.GETPOST('pagcid','int').'&action=cfdi2">Salir</a>';
	print '<a class="butAction" href="pagos.php?facid='.$object->id.'&pagcid='.GETPOST('pagcid','int').'&action=cancelaTimbreProfact">Cancelar CFDI</a>';
	print '<div>';
}
	
if($action=="cancelaTimbreProfact") {

	$sqlTimbrarCFDI = "UPDATE cfdi_comprobante
	SET
		status = 0,
		cancelado = 2
		WHERE fk_payment = ".GETPOST('pagcid','int');
	$resTimbrarCFDI = $db->query($sqlTimbrarCFDI);

	$sqlTimbrarCFDI = "UPDATE llx_cfdimx_recepcion_pagos
	SET
		cancelado = 1
		WHERE fk_paiement = ".GETPOST('pagcid','int');
	$resTimbrarCFDI = $db->query($sqlTimbrarCFDI);

	print "<script>window.location.href='pagos.php?action=cfdi2&facid=".GETPOST("facid")."&pagcid=".GETPOST("pagcid")."'</script>";
}

?>
<script type="text/javascript" language="javascript">

	window.onload = function()
	{
		var d = new Date();
		if(document.getElementById("fechaPagohour").value == "12"
			&& document.getElementById("fechaPagomin").value == "00") {
			document.getElementById("fechaPagohour").value = d.getHours();
			document.getElementById("fechaPagomin").value = d.getMinutes();
		}
	};

</script>

<?php


llxFooter();
$db->close();
?>