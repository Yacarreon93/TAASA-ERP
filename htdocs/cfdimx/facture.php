<?php
date_default_timezone_set("America/Mexico_City");

require('../main.inc.php');
require('conf.php');
include('lib/nusoap/lib/nusoap.php');
include("lib/phpqrcode/qrlib.php");
require('lib/numero_a_letra.php');
require_once('lib/mimemail/htmlMimeMail5.php');
$maild = new htmlMimeMail5();
session_start();
$tpdomic='';
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/cfdi/service/comprobantecfdiservice.php');

if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
if ($conf->projet->enabled)
{
	require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
	require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
}

$client = new nusoap_client($wscfdi, 'wsdl');
$result = $client->call('validaCliente',array( "rfc"=>$conf->global->MAIN_INFO_SIREN ));
$status_clt = $result["return"]["status_cliente_id"];
$status_clt_desc = $result["return"]["status_cliente_desc"];
$folios_timbrados = $result["return"]["folios_timbrados"];
$folios_adquiridos = $result["return"]["folios_adquiridos"];
$folios_disponibles = $result["return"]["folios_disponibles"];

$timbreProfact = true;

if( $_REQUEST["cfdi_commit"]==1 ){
	$msg_cfdi_final = "El comprobante se ha generado de manera exitosa ".$comp_email;
} else if( $_REQUEST["cfdi_commit"]==100 ){
	$msg_cfdi_final = "Generando comprobante ".$comp_email;
}

$sql= " SELECT * FROM ".MAIN_DB_PREFIX ."facture WHERE rowid = ".$_REQUEST["facid"];
$resql=$db->query($sql);
if ($resql){
	$num_fact = $db->num_rows($resql);
	$i=0;
	if($num_fact){
		while ($i < $num_fact){
			$obj = $db->fetch_object($resql);
			$facnumber = $obj->facnumber;
			$separafac = explode("-", $facnumber);
			$serie=$separafac[0];
			$folio=$separafac[1];
			$i++;
		}
	}
}

$langs->load('bills');
//print 'ee'.$langs->trans('BillsCustomer');exit;

$langs->load('companies');
$langs->load('products');
$langs->load('main');

if (GETPOST('mesg','int',1) && isset($_SESSION['message'])) $mesg=$_SESSION['message'];

$sall=trim(GETPOST('sall'));
$projectid=(GETPOST('projectid')?GETPOST('projectid','int'):0);

$id=(GETPOST('id','int')?GETPOST('id','int'):GETPOST('facid','int'));  // For backward compatibility
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$lineid=GETPOST('lineid','int');
$userid=GETPOST('userid','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_montant_ttc=GETPOST('search_montant_ttc','alpha');

// Security check
$fieldid = (! empty($ref)?'facnumber':'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id,'','','fk_soc',$fieldid);

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

$usehm=$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;

$object=new Facture($db);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('invoicecard'));
//$soc_rfc='';
//Datos del receptor
$sql = "
SELECT * FROM  ".MAIN_DB_PREFIX."facture f,  ".MAIN_DB_PREFIX."societe s 
WHERE f.rowid = '".$_REQUEST["facid"]."' 
AND f.fk_soc = s.rowid";
$resql=$db->query($sql);
if ($resql){
	 $soc_num = $db->num_rows($resql);
	 $i = 0;
	 if ($soc_num){
		 while ($i < $soc_num){
			 $obj = $db->fetch_object($resql);
			 if ($obj){
				 $soc_rfc = $obj->siren;
				 $soc_id = $obj->rowid;
				 $soc_email = $obj->email; 
				 $status=$obj->fk_statut;
			 }
			 $i++;
		 }
	 }
}

//Datos de configuración
$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_config WHERE emisor_rfc = '".$conf->global->MAIN_INFO_SIREN."' AND entity_id = " . $_SESSION['dol_entity']);
if ($resql){
	 $conf_num = $db->num_rows($resql);
	 $i = 0;
	 if ($conf_num){
		 while ($i < $conf_num){
			 $obj = $db->fetch_object($resql);
			 if ($obj){
				 $status_conf = $obj->status_conf;
				 $modo_timbrado = $obj->modo_timbrado;
				 $passwd_timbrado = $obj->password_timbrado_txt;
			 }
			 $i++;
		 }
	 }
}

//Datos de la factura
$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture = " . $_REQUEST["facid"]);
if ($resql){
	 $cfdi_tot = $db->num_rows($resql);
	 $i = 0;
	 if ($conf_num){
		 while ($i < $conf_num){
			 $obj = $db->fetch_object($resql);
			 if ($obj){
				 $cfdi_cancela = $obj->cancelado;
				 $uuid = $obj->uuid;
				 $selloSAT = $obj->selloSAT;
				 $selloCFD = $obj->selloCFD;
				 $fechaTimbrado = $obj->fechaTimbrado;
				 $factura_id = $obj->factura_id;
                 $divisa = $obj->divisa;
			 }
			 $i++;
		 }
	 }
}
if($conf->global->MAIN_MODULE_MULTICURRENCY){
	$object->total_ht=$object->multicurrency_total_ht;
	$object->total_tva=$object->multicurrency_total_tva;
	$object->total_ttc=$object->multicurrency_total_ttc;
	//$object->multicurrency_total_ht;
	//$object->multicurrency_total_ttc;
	//$object->multicurrency_total_tva;
}
//Status del comprobante
//print 'uuid: '.$uuid.'<br>';
//print 'usr: '.$conf->global->MAIN_INFO_SIREN.'<br>';
//print 'pwd: '.$passwd_timbrado.'<br>';/
if($uuid == "Pendiente") {
    $resql=$db->query("SELECT UUID FROM  cfdi_comprobante WHERE fk_comprobante = ". $_REQUEST["facid"]);
    if ($resql){
        $obj = $db->fetch_object($resql);
        $dbUuid = $obj->UUID;
    }
    if($dbUuid) {
        $uuid = $dbUuid;
        $resql=$db->query("UPDATE llx_cfdimx set uuid = '" . $uuid . "' WHERE fk_facture = ". $_REQUEST["facid"]);
    }
}

if($uuid == "Pendiente") {
    $status_comprobante="Sin timbrar";
} else if($uuid != ""){
    if( $selloCFD == "Pendiente") {
        $status_comprobante="Enviado";
    } else {
        $result = $client->call('getStatusUUID',array( 
            "uuid"=>$uuid, 
            "timbrado_usuario"=>$conf->global->MAIN_INFO_SIREN, 
            "timbrado_password"=>$passwd_timbrado
        ));
        $status_comprobante=$result["return"]["status"];
    }
}else{
	$status_comprobante="Sin timbrar";
}

//Datos de configuración
/*
 * Actions
 */
// --> DIXI
if( isset($_REQUEST["action"]) && $_REQUEST["action"] == "generaCFDI"){

    foreach ($_REQUEST as $key => $value) {
        //echo $key .'=>'. $value.'<br>';
        $$key = $value;
    }

    //include 'consumeTestService.php';
    include("generaCFDI.php"); //AMM generaCFDI.php
    

} else if( isset($_REQUEST["action"]) && $_REQUEST["action"] == "generaCFDI2"){
    include("generaCFDI2.php");

}

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

/*
 * Add file in email form
 */
if ($_POST['addfile'])
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    // Set tmp user directory
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    $mesg=dol_add_file_process($upload_dir_tmp,0,0);

    $action='presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']))
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    // Set tmp user directory
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

	// TODO Delete only files that was uploaded from email form
    $mesg=dol_remove_file_process($_POST['removedfile'],0);

    $action='presend';
}

/*
 * Send mail
 */
if (($action == 'send' || $action == 'relance') && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
    $langs->load('mails');

    $actiontypecode='';$subject='';$actionmsg='';$actionmsg2='';

    $result=$object->fetch($id);
    $result=$object->fetch_thirdparty();

    if ($result > 0)
    {
//        $ref = dol_sanitizeFileName($object->ref);
//        $file = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';

//        if (is_readable($file))
//        {
            if ($_POST['sendto'])
            {
                // Le destinataire a ete fourni via le champ libre
                $sendto = $_POST['sendto'];
                $sendtoid = 0;
            }
            elseif ($_POST['receiver'] != '-1')
            {
                // Recipient was provided from combo list
                if(DOL_VERSION<'4.0'){
                if ($_POST['receiver'] == 'thirdparty') // Id of third party
                {
                    $sendto = $object->client->email;
                    $sendtoid = 0;
                }
                else	// Id du contact
                {
                    $sendto = $object->client->contact_get_property($_POST['receiver'],'email');
                    $sendtoid = $_POST['receiver'];
                }
                }else{
                	if ($_POST['receiver'] == 'thirdparty') // Id of third party
                	{
                		$sendto = $object->thirdparty->email;
                		$sendtoid = 0;
                	}
                	else	// Id du contact
                	{
                		$sendto = $object->thirdparty->contact_get_property($_POST['receiver'],'email');
                		$sendtoid = $_POST['receiver'];
                	}
                }
            }

            if (dol_strlen($sendto))
            {
                $langs->load("commercial");

                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
                $replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
                $message = $_POST['message'];
                $sendtocc = $_POST['sendtocc'];
                $deliveryreceipt = $_POST['deliveryreceipt'];

                if ($action == 'send')
                {
                    if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
                    else $subject = $langs->transnoentities('Bill').' '.$object->ref;
                    $actiontypecode='AC_FAC';
                    $actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
                    if ($message)
                    {
                        $actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
                        $actionmsg.=$message;
                    }
                    //$actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
                }
                if ($action == 'relance')
                {
                    if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
                    else $subject = $langs->transnoentities('Relance facture '.$object->ref);
                    $actiontypecode='AC_FAC';
                    $actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
                    if ($message) {
                        $actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
                        $actionmsg.=$message;
                    }
                    //$actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
                }

                // Create form object
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
                $formmail = new FormMail($db);

                $attachedfiles=$formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Send mail
                require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
                $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt,-1);
                if ($mailfile->error)
                {
                    $mesg='<div class="error">'.$mailfile->error.'</div>';
                }
                else
                {
                    $result=$mailfile->sendfile();
                    if ($result)
                    {
                        $mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));		// Must not contain "

                        $error=0;

                        // Initialisation donnees
                        $object->sendtoid		= $sendtoid;
                        $object->actiontypecode	= $actiontypecode;
                        $object->actionmsg		= $actionmsg;  // Long text
                        $object->actionmsg2		= $actionmsg2; // Short text
                        $object->fk_element		= $object->id;
                        $object->elementtype	= $object->element;

                        // Appel des triggers
                        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('BILL_SENTBYMAIL',$object,$user,$langs,$conf);
                        if ($result < 0) { $error++; $this->errors=$interface->errors; }
                        // Fin appel triggers

                        if ($error)
                        {
                            dol_print_error($db);
                        }
                        else
                        {
                            // Redirect here
                            // This avoid sending mail twice if going out and then back to page
                            $_SESSION['message'] = $mesg;
                            Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&mesg=1');
                            exit;
                        }
                    }
                    else
                    {
                        $langs->load("other");
                        $mesg='<div class="error">';
                        if ($mailfile->error)
                        {
                            $mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
                            $mesg.='<br>'.$mailfile->error;
                        }
                        else
                        {
                            $mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
                        }
                        $mesg.='</div>';
                    }
                }
/*            }
            else
            {
                $langs->load("other");
                $mesg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').'</div>';
                dol_syslog('Recipient email is empty');
            }*/
        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
            dol_syslog('Failed to read file: '.$file);
        }
    }
    else
    {
        $langs->load("other");
        $mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")).'</div>';
        dol_syslog('Impossible de lire les donnees de la facture. Le fichier facture n\'a peut-etre pas ete genere.');
    }

    $action = 'presend'; 
}

/*
 * Generate document
 */
else if ($action == 'builddoc')	// En get ou en post
{ 
    $object->fetch($id);
    $object->fetch_thirdparty();

    if (GETPOST('model'))
    {
        $object->setDocModel($user, GETPOST('model'));
    }

    // Define output language
    $outputlangs = $langs;
    $newlang='';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }
    $result=facture_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
    else
    {
        Header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
        exit;
    }
}

// Remove file in doc form
else if ($action == 'remove_file')
{
	if ($object->fetch($id))
	{
		require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

		$object->fetch_thirdparty();

		$langs->load("other");
		$upload_dir = $conf->facture->dir_output;
		$file = $upload_dir . '/' . GETPOST('file');
		dol_delete_file($file,0,0,0,$object);
		$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved",GETPOST('file')).'</div>';
	}
}
if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
{
	if ($action == 'addcontact' && $user->rights->facture->creer)
	{
		$result = $object->fetch($id);

		if ($result > 0 && $id > 0)
		{
			$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
			$result = $result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
		}

		if ($result >= 0)
		{
			Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else
		{
			if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$langs->load("errors");
				$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
			}
			else
			{
				$mesg = '<div class="error">'.$object->error.'</div>';
			}
		}
	}

	// bascule du statut d'un contact
	else if ($action == 'swapstatut' && $user->rights->facture->creer)
	{
		if ($object->fetch($id))
		{
			$result=$object->swapContactStatus(GETPOST('ligne'));
		}
		else
		{
			dol_print_error($db);
		}
	}

	// Efface un contact
	else if ($action == 'deletecontact' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$result = $object->delete_contact($lineid);

		if ($result >= 0)
		{
			Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else {
			dol_print_error($db);
		}
	}
}

/*
 * View
 */
llxHeader('',$langs->trans('Bill'),'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');
if(file_exists(DOL_DOCUMENT_ROOT.'/multidivisa/main.inc.php')) require_once DOL_DOCUMENT_ROOT.'/multidivisa/main.inc.php';//Addon multimoneda
$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$now=dol_now();

/*********************************************************************
 *
 * Mode creation
 *
 **********************************************************************/
if ($action == 'create')
{
    $facturestatic=new Facture($db);

    print_fiche_titre($langs->trans('NewBill'));

    dol_htmloutput_mesg($mesg);
    dol_htmloutput_errors('',$errors);

    $soc = new Societe($db);
    if ($socid) $res=$soc->fetch($socid);

    if (GETPOST('origin') && GETPOST('originid'))
    {
        // Parse element/subelement (ex: project_task)
        $element = $subelement = GETPOST('origin');
        if (preg_match('/^([^_]+)_([^_]+)/i',GETPOST('origin'),$regs))
        {
            $element = $regs[1];
            $subelement = $regs[2];
        }

        if ($element == 'project')
        {
            $projectid=GETPOST('originid');
        }
        else
        {
            // For compatibility
            if ($element == 'order' || $element == 'commande')    { $element = $subelement = 'commande'; }
            if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
            if ($element == 'contract') { $element = $subelement = 'contrat'; }
            if ($element == 'shipping') { $element = $subelement = 'expedition'; }

            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

            $classname = ucfirst($subelement);
            $objectsrc = new $classname($db);
            $objectsrc->fetch(GETPOST('originid'));
            if (empty($objectsrc->lines) && method_exists($objectsrc,'fetch_lines'))  $objectsrc->fetch_lines();
            $objectsrc->fetch_thirdparty();

            $projectid			= (!empty($objectsrc->fk_project)?$objectsrc->fk_project:'');
            $ref_client			= (!empty($objectsrc->ref_client)?$objectsrc->ref_client:'');
            $ref_int			= (!empty($objectsrc->ref_int)?$objectsrc->ref_int:'');

            $soc = $objectsrc->client;
            $cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
            $mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
            $remise_percent 	= (!empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
            $remise_absolue 	= (!empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
            $dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
        }
    }
    else
    {
        $cond_reglement_id 	= $soc->cond_reglement_id;
        $mode_reglement_id 	= $soc->mode_reglement_id;
        $remise_percent 	= $soc->remise_percent;
        $remise_absolue 	= 0;
        $dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
    }
    $absolute_discount=$soc->getAvailableDiscounts();


    if ($conf->use_javascript_ajax)
    {
        print ajax_combobox('fac_replacement');
        print ajax_combobox('fac_avoir');
    }

    print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
    print '<input name="facnumber" type="hidden" value="provisoire">';
    print '<input name="ref_client" type="hidden" value="'.$ref_client.'">';
    print '<input name="ref_int" type="hidden" value="'.$ref_int.'">';
    print '<input type="hidden" name="origin" value="'.GETPOST('origin').'">';
    print '<input type="hidden" name="originid" value="'.GETPOST('originid').'">';

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';

    // Factures predefinies
    if (empty($_GET['propalid']) && empty($_GET['commandeid']) && empty($_GET['contratid']) && empty($_GET['originid']))
    {
        $sql = 'SELECT r.rowid, r.titre, r.total_ttc';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_rec as r';
        $sql.= ' WHERE r.fk_soc = '.$soc->id;

        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;

            if ($num > 0)
            {
                print '<tr><td>'.$langs->trans('CreateFromRepeatableInvoice').'</td><td>';
                print '<select class="flat" name="fac_rec">';
                print '<option value="0" selected="selected"></option>';
                while ($i < $num)
                {
                    $objp = $db->fetch_object($resql);
                    print '<option value="'.$objp->rowid.'"';
                    if ($_POST["fac_rec"] == $objp->rowid) print ' selected="selected"';
                    print '>'.$objp->titre.' ('.price($objp->total_ttc).' '.$langs->trans("TTC").')</option>';
                    $i++;
                }
                print '</select></td></tr>';
            }
            $db->free($resql);
        }
        else
        {
            dol_print_error($db);
        }
    }

    // Tiers
    print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td><td colspan="2">';
    print $soc->getNomUrl(1);
    print '<input type="hidden" name="socid" value="'.$soc->id.'">';
    print '</td>';
    print '</tr>'."\n";

    // Type de facture
    $facids=$facturestatic->list_replacable_invoices($soc->id);
    if ($facids < 0)
    {
        dol_print_error($db,$facturestatic);
        exit;
    }
    $options="";
    foreach ($facids as $facparam)
    {
        $options.='<option value="'.$facparam['id'].'"';
        if ($facparam['id'] == $_POST['fac_replacement']) $options.=' selected="selected"';
        $options.='>'.$facparam['ref'];
        $options.=' ('.$facturestatic->LibStatut(0,$facparam['status']).')';
        $options.='</option>';
    }

    $facids=$facturestatic->list_qualified_avoir_invoices($soc->id);
    if ($facids < 0)
    {
        dol_print_error($db,$facturestatic);
        exit;
    }
    $optionsav="";
    foreach ($facids as $key => $value)
    {
        $newinvoice=new Facture($db);
        $newinvoice->fetch($key);
        $optionsav.='<option value="'.$key.'"';
        if ($key == $_POST['fac_avoir']) $optionsav.=' selected="selected"';
        $optionsav.='>';
        $optionsav.=$newinvoice->ref;
        $optionsav.=' ('.$newinvoice->getLibStatut(1,$value).')';
        $optionsav.='</option>';
    }

    print '<tr><td valign="top" class="fieldrequired">'.$langs->trans('Type').'</td><td colspan="2">';
    print '<table class="nobordernopadding">'."\n";

    // Standard invoice
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="0"'.(GETPOST('type')==0?' checked="checked"':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    // Deposit
    print '<tr height="18"><td width="16px" valign="middle">';
    print 'X<input type="radio" name="type" value="3"'.(GETPOST('type')==3?' checked="checked"':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceDeposit"),$langs->transnoentities("InvoiceDepositDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    // Proforma
    if ($conf->global->FACTURE_USE_PROFORMAT)
    {
        print '<tr height="18"><td width="16px" valign="middle">';
        print '<input type="radio" name="type" value="4"'.(GETPOST('type')==4?' checked="checked"':'').'>';
        print '</td><td valign="middle">';
        $desc=$form->textwithpicto($langs->trans("InvoiceProForma"),$langs->transnoentities("InvoiceProFormaDesc"),1);
        print $desc;
        print '</td></tr>'."\n";
    }

    // Replacement
    print '<tr height="18"><td valign="middle">';
    print '<input type="radio" name="type" value="1"'.(GETPOST('type')==1?' checked="checked"':'');
    if (! $options) print ' disabled="disabled"';
    print '>';
    print '</td><td valign="middle">';
    $text=$langs->trans("InvoiceReplacementAsk").' ';
    $text.='<select class="flat" name="fac_replacement" id="fac_replacement"';
    if (! $options) $text.=' disabled="disabled"';
    $text.='>';
    if ($options)
    {
        $text.='<option value="-1"></option>';
        $text.=$options;
    }
    else
    {
        $text.='<option value="-1">'.$langs->trans("NoReplacableInvoice").'</option>';
    }
    $text.='</select>';
    $desc=$form->textwithpicto($text,$langs->transnoentities("InvoiceReplacementDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    // Credit note
    print '<tr height="18"><td valign="middle">';
    print '<input type="radio" name="type" value="2"'.(GETPOST('type')==2?' checked=true':'');
    if (! $optionsav) print ' disabled="disabled"';
    print '>';
    print '</td><td valign="middle">';
    $text=$langs->transnoentities("InvoiceAvoirAsk").' ';
    //	$text.='<input type="text" value="">';
    $text.='<select class="flat" name="fac_avoir" id="fac_avoir"';
    if (! $optionsav) $text.=' disabled="disabled"';
    $text.='>';
    if ($optionsav)
    {
        $text.='<option value="-1"></option>';
        $text.=$optionsav;
    }
    else
    {
        $text.='<option value="-1">'.$langs->trans("NoInvoiceToCorrect").'</option>';
    }
    $text.='</select>';
    $desc=$form->textwithpicto($text,$langs->transnoentities("InvoiceAvoirDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    print '</table>';
    print '</td></tr>';

    // Discounts for third party
    print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
    if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",'<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">'.$soc->remise_client.'</a>');
    else print $langs->trans("CompanyHasNoRelativeDiscount");
    print ' <a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">('.$langs->trans("EditRelativeDiscount").')</a>';
    print '. ';
    print '<br>';
    if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",'<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">'.price($absolute_discount).'</a>',$langs->trans("Currency".$conf->currency));
    else print $langs->trans("CompanyHasNoAbsoluteDiscount");
    print ' <a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?socid='.$soc->id.'&action='.$action.'&origin='.GETPOST('origin').'&originid='.GETPOST('originid')).'">('.$langs->trans("EditGlobalDiscounts").')</a>';
    print '.';
    print '</td></tr>';

    // Date invoice
    print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
    $form->select_date($dateinvoice,'','','','',"add",1,1);
    print '</td></tr>';

    // Payment term
    print '<tr><td nowrap>'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
    $form->select_conditions_paiements(isset($_POST['cond_reglement_id'])?$_POST['cond_reglement_id']:$cond_reglement_id,'cond_reglement_id');
    print '</td></tr>';

    // Payment mode
    print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
    $form->select_types_paiements(isset($_POST['mode_reglement_id'])?$_POST['mode_reglement_id']:$mode_reglement_id,'mode_reglement_id');
    print '</td></tr>';

    // Project
    if ($conf->projet->enabled)
    {
        $langs->load('projects');
        print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
        select_projects($soc->id, $projectid, 'projectid');
        print '</td></tr>';
    }

    // Other attributes
    $parameters=array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
        foreach($extrafields->attribute_label as $key=>$label)
        {
            $value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
            print '<tr><td>'.$label.'</td><td colspan="3">';
            print $extrafields->showInputField($key,$value);
            print '</td></tr>'."\n";
        }
    }

    // Modele PDF
    print '<tr><td>'.$langs->trans('Model').'</td>';
    print '<td>';
    include_once(DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php');
    $liste=ModelePDFFactures::liste_modeles($db);
    print $form->selectarray('model',$liste,$conf->global->FACTURE_ADDON_PDF);
    print "</td></tr>";

    // Public note
    print '<tr>';
    print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
    print '<td valign="top" colspan="2">';
    print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';
    if (is_object($objectsrc))    // Take value from source object
    {
        print $objectsrc->note_public;
    }
    print '</textarea></td></tr>';

    // Private note
    if (! $user->societe_id)
    {
        print '<tr>';
        print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
        print '<td valign="top" colspan="2">';
        print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';
        if (is_object($objectsrc))    // Take value from source object
        {
            print $objectsrc->note;
        }
        print '</textarea></td></tr>';
    }

    if (is_object($objectsrc))
    {
        // TODO for compatibility
        if ($_GET['origin'] == 'contrat')
        {
            // Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
            $objectsrc->remise_absolue=$remise_absolue;
            $objectsrc->remise_percent=$remise_percent;
            $objectsrc->update_price(1,-1,1);
        }

        print "\n<!-- ".$classname." info -->";
        print "\n";
        print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
        print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
        print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
        print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
        print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

        $newclassname=$classname;
        if ($newclassname=='Propal') $newclassname='CommercialProposal';
        print '<tr><td>'.$langs->trans($newclassname).'</td><td colspan="2">'.$objectsrc->getNomUrl(1).'</td></tr>';
        print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($objectsrc->total_ht).'</td></tr>';
        print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($objectsrc->total_tva)."</td></tr>";
        if ($mysoc->pays_code=='ES')
        {
            if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
            {
                print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->pays_code).'</td><td colspan="2">'.price($objectsrc->total_localtax1)."</td></tr>";
            }

            if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
            {
                print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->pays_code).'</td><td colspan="2">'.price($objectsrc->total_localtax2)."</td></tr>";
            }
        }
        print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($objectsrc->total_ttc)."</td></tr>";
    }
    else
    {
        // Show deprecated optional form to add product line here
        if ($conf->global->PRODUCT_SHOW_WHEN_CREATE)
        {
            print '<tr><td colspan="3">';

            // Zone de choix des produits predefinis a la creation
            print '<table class="noborder" width="100%">';
            print '<tr>';
            print '<td>'.$langs->trans('ProductsAndServices').'</td>';
            print '<td>'.$langs->trans('Qty').'</td>';
            print '<td>'.$langs->trans('ReductionShort').'</td>';
            print '<td> &nbsp; &nbsp; </td>';
            if ($conf->service->enabled)
            {
                print '<td>'.$langs->trans('ServiceLimitedDuration').'</td>';
            }
            print '</tr>';
            for ($i = 1 ; $i <= $NBLINES ; $i++)
            {
                print '<tr>';
                print '<td>';
                // multiprix
                if($conf->global->PRODUIT_MULTIPRICES)
                $form->select_produits('','idprod'.$i,'',$conf->product->limit_size,$soc->price_level);
                else
                $form->select_produits('','idprod'.$i,'',$conf->product->limit_size);
                print '</td>';
                print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
                print '<td nowrap="nowrap"><input type="text" size="1" name="remise_percent'.$i.'" value="'.$soc->remise_client.'">%</td>';
                print '<td>&nbsp;</td>';
                // Si le module service est actif, on propose des dates de debut et fin a la ligne
                if ($conf->service->enabled)
                {
                    print '<td nowrap="nowrap">';
                    print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                    print '<td class="nobordernopadding" nowrap="nowrap">';
                    print $langs->trans('From').' ';
                    print '</td><td class="nobordernopadding" nowrap="nowrap">';
                    print $form->select_date('','date_start'.$i,$usehm,$usehm,1,"add");
                    print '</td></tr>';
                    print '<td class="nobordernopadding" nowrap="nowrap">';
                    print $langs->trans('to').' ';
                    print '</td><td class="nobordernopadding" nowrap="nowrap">';
                    print $form->select_date('','date_end'.$i,$usehm,$usehm,1,"add");
                    print '</td></tr></table>';
                    print '</td>';
                }
                print "</tr>\n";
            }

            print '</table>';
            print '</td></tr>';
        }
    }

    print "</table>\n";

    // Button "Create Draft"
    print '<br><center><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></center>';

    print "</form>\n";

    // Show origin lines
    if (is_object($objectsrc))
    {
        print '<br>';

        $title=$langs->trans('ProductsAndServices');
        print_titre($title);

        print '<table class="noborder" width="100%">';

        $objectsrc->printOriginLinesList($hookmanager);

        print '</table>';
    }
}
else
{
    /*
     * Show object in view mode
     */
    if ($id > 0 || ! empty($ref))
    {
        dol_htmloutput_mesg($mesg);
        dol_htmloutput_errors('',$errors);

        $result=$object->fetch($id,$ref);
        if($conf->global->MAIN_MODULE_MULTICURRENCY){
        	$object->total_ht=$object->multicurrency_total_ht;
        	$object->total_tva=$object->multicurrency_total_tva;
        	$object->total_ttc=$object->multicurrency_total_ttc;
        	//$object->multicurrency_total_ht;
        	//$object->multicurrency_total_ttc;
        	//$object->multicurrency_total_tva;
        }
        if ($result > 0)
        {
            if ($user->societe_id>0 && $user->societe_id!=$object->socid)  accessforbidden('',0);

            $result=$object->fetch_thirdparty();

            $soc = new Societe($db);
            $soc->fetch($object->socid);

            $totalpaye  = $object->getSommePaiement();
            $totalcreditnotes = $object->getSumCreditNotesUsed();
            $totaldeposits = $object->getSumDepositsUsed();
            //print "totalpaye=".$totalpaye." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits;

            // We can also use bcadd to avoid pb with floating points
            // For example print 239.2 - 229.3 - 9.9; does not return 0.
            //$resteapayer=bcadd($object->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
            //$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
            $resteapayer = price2num($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits,'MT');

            if ($object->paye) $resteapayer=0;
            $resteapayeraffiche=$resteapayer;

            if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
            {
                $filterabsolutediscount="fk_facture_source IS NULL";  // If we want deposit to be substracted to payments only and not to total of final invoice
                $filtercreditnote="fk_facture_source IS NOT NULL";    // If we want deposit to be substracted to payments only and not to total of final invoice
            }
            else
            {
                $filterabsolutediscount="fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND description='(DEPOSIT)')";
                $filtercreditnote="fk_facture_source IS NOT NULL AND description <> '(DEPOSIT)'";
            }

            $absolute_discount=$soc->getAvailableDiscounts('',$filterabsolutediscount);
            $absolute_creditnote=$soc->getAvailableDiscounts('',$filtercreditnote);
            $absolute_discount=price2num($absolute_discount,'MT');
            $absolute_creditnote=price2num($absolute_creditnote,'MT');

            $author = new User($db);
            if ($object->user_author)
            {
                $author->fetch($object->user_author);
            }

            $objectidnext=$object->getIdReplacingInvoice();


            $head = facture_prepare_head($object);

           dol_fiche_head($head, "tabfactclient", 'CFDI', 0, '');

            $formconfirm='';

            // Confirmation de la conversion de l'avoir en reduc
            if ($action == 'converttoreduc')
            {
                $text=$langs->trans('ConfirmConvertToReduc');
                $formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id,$langs->trans('ConvertToReduc'),$text,'confirm_converttoreduc','',"yes",2);
            }

            // Confirmation to delete invoice
            if ($action == 'delete')
            {
                $text=$langs->trans('ConfirmDeleteBill');
                $formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id,$langs->trans('DeleteBill'),$text,'confirm_delete','',0,1);
            }

            // Confirmation de la validation
            if ($action == 'valid')
            {
                // on verifie si l'objet est en numerotation provisoire
                $objectref = substr($object->ref, 1, 4);
                if ($objectref == 'PROV')
                {
                    $savdate=$object->date;
                    if (! empty($conf->global->FAC_FORCE_DATE_VALIDATION))
                    {
                        $object->date=dol_now();
                        $object->date_lim_reglement=$object->calculate_date_lim_reglement();
                    }
                    $numref = $object->getNextNumRef($soc);
                    //$object->date=$savdate;
                }
                else
                {
                    $numref = $object->ref;
                }

                $text=$langs->trans('ConfirmValidateBill',$numref);
                if ($conf->notification->enabled)
                {
                    require_once(DOL_DOCUMENT_ROOT ."/core/class/notify.class.php");
                    $notify=new Notify($db);
                    $text.='<br>';
                    $text.=$notify->confirmMessage('NOTIFY_VAL_FAC',$object->socid);
                }
                $formquestion=array();

                if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1))
                {
                    $langs->load("stocks");
                    require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
                    $formproduct=new FormProduct($db);
                    $label=$object->type==2?$langs->trans("SelectWarehouseForStockIncrease"):$langs->trans("SelectWarehouseForStockDecrease");
                    $formquestion=array(
                    //'text' => $langs->trans("ConfirmClone"),
                    //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                    //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                    array('type' => 'other', 'name' => 'idwarehouse',   'label' => $label,   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1)));
                }
                if ($object->type != 2 && $object->total_ttc < 0)    // Can happen only if $conf->global->FACTURE_ENABLE_NEGATIVE is on
                {
                     $text.='<br>'.img_warning().' '.$langs->trans("ErrorInvoiceOfThisTypeMustBePositive");
                }
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('ValidateBill'),$text,'confirm_valid',$formquestion,(($object->type != 2 && $object->total_ttc < 0)?"no":"yes"),($conf->notification->enabled?0:2));
            }

            // Confirm back to draft status
            if ($action == 'modif')
            {
                $text=$langs->trans('ConfirmUnvalidateBill',$object->ref);
                $formquestion=array();
                if ($object->type != 3 && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $object->hasProductsOrServices(1))
                {
                    $langs->load("stocks");
                    require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
                    $formproduct=new FormProduct($db);
                    $label=$object->type==2?$langs->trans("SelectWarehouseForStockDecrease"):$langs->trans("SelectWarehouseForStockIncrease");
                    $formquestion=array(
                    //'text' => $langs->trans("ConfirmClone"),
                    //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                    //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                    array('type' => 'other', 'name' => 'idwarehouse',   'label' => $label,   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1)));
                }

                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('UnvalidateBill'),$text,'confirm_modif',$formquestion,"yes",1);
            }

            // Confirmation du classement paye
            if ($action == 'paid' && $resteapayer <= 0)
            {
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('ClassifyPaid'),$langs->trans('ConfirmClassifyPaidBill',$object->ref),'confirm_paid','',"yes",1);
            }
            if ($action == 'paid' && $resteapayer > 0)
            {
                // Code
                $i=0;
                $close[$i]['code']='discount_vat';$i++;
                $close[$i]['code']='badcustomer';$i++;
                // Help
                $i=0;
                $close[$i]['label']=$langs->trans("HelpEscompte").'<br><br>'.$langs->trans("ConfirmClassifyPaidPartiallyReasonDiscountVatDesc");$i++;
                $close[$i]['label']=$langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");$i++;
                // Texte
                $i=0;
                $close[$i]['reason']=$form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonDiscountVat",$resteapayer,$langs->trans("Currency".$conf->currency)),$close[$i]['label'],1);$i++;
                $close[$i]['reason']=$form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer",$resteapayer,$langs->trans("Currency".$conf->currency)),$close[$i]['label'],1);$i++;
                // arrayreasons[code]=reason
                foreach($close as $key => $val)
                {
                    $arrayreasons[$close[$key]['code']]=$close[$key]['reason'];
                }

                // Cree un tableau formulaire
                $formquestion=array(
				'text' => $langs->trans("ConfirmClassifyPaidPartiallyQuestion"),
                array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"),  'values' => $arrayreasons),
                array('type' => 'text',  'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'size' => '100')
                );
                // Paiement incomplet. On demande si motif = escompte ou autre
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('ClassifyPaid'),$langs->trans('ConfirmClassifyPaidPartially',$object->ref),'confirm_paid_partially',$formquestion,"yes");
            }

            // Confirmation du classement abandonne
            if ($action == 'canceled')
            {
                // S'il y a une facture de remplacement pas encore validee (etat brouillon),
                // on ne permet pas de classer abandonner la facture.
                if ($objectidnext)
                {
                    $facturereplacement=new Facture($db);
                    $facturereplacement->fetch($objectidnext);
                    $statusreplacement=$facturereplacement->statut;
                }
                if ($objectidnext && $statusreplacement == 0)
                {
                    print '<div class="error">'.$langs->trans("ErrorCantCancelIfReplacementInvoiceNotValidated").'</div>';
                }
                else
                {
                    // Code
                    $close[1]['code']='badcustomer';
                    $close[2]['code']='abandon';
                    // Help
                    $close[1]['label']=$langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
                    $close[2]['label']=$langs->trans("ConfirmClassifyAbandonReasonOtherDesc");
                    // Texte
                    $close[1]['reason']=$form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer",$object->ref),$close[1]['label'],1);
                    $close[2]['reason']=$form->textwithpicto($langs->transnoentities("ConfirmClassifyAbandonReasonOther"),$close[2]['label'],1);
                    // arrayreasons
                    $arrayreasons[$close[1]['code']]=$close[1]['reason'];
                    $arrayreasons[$close[2]['code']]=$close[2]['reason'];

                    // Cree un tableau formulaire
                    $formquestion=array(
					'text' => $langs->trans("ConfirmCancelBillQuestion"),
                    array('type' => 'radio', 'name' => 'close_code', 'label' => $langs->trans("Reason"),  'values' => $arrayreasons),
                    array('type' => 'text',  'name' => 'close_note', 'label' => $langs->trans("Comment"), 'value' => '', 'size' => '100')
                    );

                    $formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$object->id,$langs->trans('CancelBill'),$langs->trans('ConfirmCancelBill',$object->ref),'confirm_canceled',$formquestion,"yes");
                }
            }

            // Confirmation de la suppression d'une ligne produit
            if ($action == 'ask_deleteline')
            {
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 'no', 1);
            }

            // Clone confirmation
            if ($action == 'clone')
            {
                // Create an array for form
                $formquestion=array(
                //'text' => $langs->trans("ConfirmClone"),
                //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1)
                );
                // Paiement incomplet. On demande si motif = escompte ou autre
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?facid='.$object->id,$langs->trans('CloneInvoice'),$langs->trans('ConfirmCloneInvoice',$object->ref),'confirm_clone',$formquestion,'yes',1);
            }

            if (! $formconfirm)
            {
                $parameters=array('lineid'=>$lineid);
                $formconfirm=$hookmanager->executeHooks('formConfirm',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            }

            // Print form confirm
            if($formconfirm!=0){
            	print $formconfirm;
            }


            // Invoice content

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="20%">'.$langs->trans('Ref').'</td>';
            print '<td colspan="5">';
            $morehtmlref='';
            $discount=new DiscountAbsolute($db);
            $result=$discount->fetch(0,$object->id);
            if ($result > 0)
            {
                $morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
            }
            if ($result < 0)
            {
                dol_print_error('',$discount->error);
            }
            print $form->showrefnav($object,'ref','',1,'facnumber','ref',$morehtmlref);
            print '</td></tr>';

            // Third party
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%">';
            print '<tr><td>'.$langs->trans('Company').'</td>';
            print '</td><td colspan="5">';
            if ($conf->global->FACTURE_CHANGE_THIRDPARTY && $action != 'editthirdparty' && $object->brouillon && $user->rights->facture->creer)
            print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editthirdparty&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetLinkToThirdParty'),1).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="5">';
            if ($action == 'editthirdparty')
            {
                $form->form_thirdparty($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->socid,'socid');
            }
            else
            {
                print ' &nbsp;'.$soc->getNomUrl(1,'compta');
                print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$object->socid.'">'.$langs->trans('OtherBills').'</a>)';
            }
            print '</tr>';

            // Type
            print '<tr><td>'.$langs->trans('Type').'</td><td colspan="5">';
            print $object->getLibType();
            if ($object->type == 1)
            {
                $facreplaced=new Facture($db);
                $facreplaced->fetch($object->fk_facture_source);
                print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
            }
            if ($object->type == 2)
            {
                $facusing=new Facture($db);
                $facusing->fetch($object->fk_facture_source);
                print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
            }

            $facidavoir=$object->getListIdAvoirFromInvoice();
            if (count($facidavoir) > 0)
            {
                print ' ('.$langs->transnoentities("InvoiceHasAvoir");
                $i=0;
                foreach($facidavoir as $id)
                {
                    if ($i==0) print ' ';
                    else print ',';
                    $facavoir=new Facture($db);
                    $facavoir->fetch($id);
                    print $facavoir->getNomUrl(1);
                }
                print ')';
            }
            if ($objectidnext > 0)
            {
                $facthatreplace=new Facture($db);
                $facthatreplace->fetch($objectidnext);
                print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
            }
            print '</td></tr>';

            // Relative and absolute discounts
            $addrelativediscount='<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("EditRelativeDiscounts").'</a>';
            $addabsolutediscount='<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("EditGlobalDiscounts").'</a>';
            $addcreditnote='<a href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&socid='.$soc->id.'&type=2&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("AddCreditNote").'</a>';

            print '<tr><td>'.$langs->trans('Discounts');
            print '</td><td colspan="5">';
            if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
            else print $langs->trans("CompanyHasNoRelativeDiscount");
            //print ' ('.$addrelativediscount.')';

            if ($absolute_discount > 0)
            {
                print '. ';
                if ($object->statut > 0 || $object->type == 2 || $object->type == 3)
                {
                    if ($object->statut == 0)
                    {
                        print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
                        print '. ';
                    }
                    else
                    {
                        if ($object->statut < 1 || $object->type == 2 || $object->type == 3)
                        {
                            $text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
                            print '<br>'.$text.'.<br>';
                        }
                        else
                        {
                            $text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
                            $text2=$langs->trans("AbsoluteDiscountUse");
                            print $form->textwithpicto($text,$text2);
                        }
                    }
                }
                else
                {
                    // Remise dispo de type remise fixe (not credit note)
                    print '<br>';
                    $form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, GETPOST('discountid'), 'remise_id', $soc->id, $absolute_discount, $filterabsolutediscount, $resteapayer, ' ('.$addabsolutediscount.')');
                }
            }
            else
            {
                if ($absolute_creditnote > 0)    // If not, link will be added later
                {
                    if ($object->statut == 0 && $object->type != 2 && $object->type != 3) print ' ('.$addabsolutediscount.')<br>';
                    else print '. ';
                }
                else print '. ';
            }
            if ($absolute_creditnote > 0)
            {
                // If validated, we show link "add credit note to payment"
                if ($object->statut != 1 || $object->type == 2 || $object->type == 3)
                {
                    if ($object->statut == 0 && $object->type != 3)
                    {
                        $text=$langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->currency));
                        print $form->textwithpicto($text,$langs->trans("CreditNoteDepositUse"));
                    }
                    else
                    {
                        print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->currency)).'.';
                    }
                }
                else
                {
                    // Remise dispo de type avoir
                    if (! $absolute_discount) print '<br>';
                    //$form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filtercreditnote, $resteapayer);
                    $form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filtercreditnote, 0);    // We must allow credit not even if amount is higher
                }
            }
            if (! $absolute_discount && ! $absolute_creditnote)
            {
                print $langs->trans("CompanyHasNoAbsoluteDiscount");
                if ($object->statut == 0 && $object->type != 2 && $object->type != 3) print ' ('.$addabsolutediscount.')<br>';
                else print '. ';
            }
            /*if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
             {
             if (! $absolute_discount && ! $absolute_creditnote) print '<br>';
             //print ' &nbsp; - &nbsp; ';
             print $addabsolutediscount;
             //print ' &nbsp; - &nbsp; '.$addcreditnote;      // We disbale link to credit note
             }*/
            print '</td></tr>';

//Option divisas AMM
            print '<tr>';
            print '<td>';print 'Divisa';print '</td>';
            print '<td colspan="4">';
            
if( $cfdi_tot>0 ){
            if(!empty($divisa)){echo $divisa;}
}else{ 
            //if(!empty($_REQUEST['osd'])){$osd=$_REQUEST['osd'];}else{$osd = 'MXN';}
            //$conf->currency
		// se modifica para multidivisa
          if($conf->global->MAIN_MODULE_MULTIDIVISA){
          	$sql="SELECT divisa FROM ".MAIN_DB_PREFIX."multidivisa_facture WHERE fk_object=".$id;
          	$ra=$db->query($sql);
          	$rb=$db->fetch_object($ra);
          	print $rb->divisa;
          	$conf->currency=$rb->divisa;
          	print '<input type="hidden" id="osd" name="osd" value="'.$conf->global->MAIN_MODULE_MULTIDIVISA.'">';
          }else{
          		if($conf->global->MAIN_MODULE_MULTICURRENCY){
          			$sql="SELECT multicurrency_code AS divisa FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$id;
          			$ra=$db->query($sql);
          			$rb=$db->fetch_object($ra);
          			print $rb->divisa;
          			$conf->currency=$rb->divisa;
          			print '<input type="hidden" id="osd" name="osd" value="'.$conf->global->MAIN_MODULE_MULTICURRENCY.'">';
          		}else{
	//           	print $conf->global->MAIN_MONNAIE;
	//           	print '<input type="hidden" id="osd" name="osd" value="'.$conf->global->MAIN_MONNAIE.'">';
			        if(!empty($_REQUEST['osd'])){$osd=$_REQUEST['osd'];}else{$osd = $conf->currency;}
		            print '<select id="osd" name="osd" onchange=location.href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;osd="+this.value>';
		            print '<option value="">'.$osd.'</option>';
		            print '<option value=""></option>';
		            print '<option '.$selectedMXN.'value="MXN">MXN</option>';
		            print '<option '.$selectedUSD.'value="USD">USD</option>';
		            print '</select>';
		            print '</td>';
		            print '</tr>';
          		}
          }
}//AMM//
if(GETPOST('tdocument') && GETPOST('edittp')==1){
   $sql="UPDATE ".MAIN_DB_PREFIX."cfdimx_type_document SET tipo_document=".GETPOST('tdocument')."
   		  WHERE fk_facture=".$id;
   //print $sql;
   $rsq=$db->query($sql);
   $sql="DELETE FROM ".MAIN_DB_PREFIX."cfdimx_retenciones
				WHERE fk_facture=".$id;
   $rsq=$db->query($sql);
   $sql="DELETE FROM ".MAIN_DB_PREFIX."cfdimx_retencionesdet
				WHERE factura_id=".$id;
   $rsq=$db->query($sql);
   if((GETPOST('tdocument')==2 || GETPOST('tdocument')==3)){
	   	$sql="SELECT rowid,total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$id;
	   	if($conf->global->MAIN_MODULE_MULTICURRENCY){
	   		$sql="SELECT rowid,multicurrency_total_ht as total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$id;
	   	}
	   	$retiva=0;
       	$retisr=0;
    	$resultset=$db->query($sql);
    	while($rsqq=$db->fetch_object($resultset)){
    		$isrprod=$rsqq->total_ht*0.10;
    		$ivaprod=$rsqq->total_ht*0.106667;
    		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) 
    				VALUES(".$id.",".$rsqq->rowid.",'".round($rsqq->total_ht,2)."','002','Tasa','0.106667','".round($ivaprod,2)."')";
    		$rsq=$db->query($sql);
    		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe)
    				VALUES(".$id.",".$rsqq->rowid.",'".round($rsqq->total_ht,2)."','001','Tasa','0.10','".round($isrprod,2)."')";
    		$rsq=$db->query($sql);
    		$retiva+=$ivaprod;
    		$retisr+=$isrprod;
    	}
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retenciones (factura_id,fk_facture,impuesto,importe)
					VALUES(".$id.",".$id.",'IVA',".round($retiva,2).")";
	   	$rsq=$db->query($sql);
	   	$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retenciones (factura_id,fk_facture,impuesto,importe)
					VALUES(".$id.",".$id.",'ISR',".round($retisr,2).")";
	   	$rsq=$db->query($sql);
	   	print "<script>location.href='facture.php?facid=".$id."'</script>";
   }
   if(GETPOST('tdocument')==5){
   		$sql="SELECT rowid,total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$id;
   		if($conf->global->MAIN_MODULE_MULTICURRENCY){
   			$sql="SELECT rowid,multicurrency_total_ht as total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$id;
	   	}
   		$retiva=0;
       	$resultset=$db->query($sql);
    	while($rsqq=$db->fetch_object($resultset)){
    		$ivaprod=$rsqq->total_ht*0.04;
    		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) 
    				VALUES(".$id.",".$rsqq->rowid.",'".round($rsqq->total_ht,2)."','002','Tasa','0.04','".round($ivaprod,2)."')";
    		$rsq=$db->query($sql);
    		$retiva+=$ivaprod;
    	}
	   	$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retenciones (factura_id,fk_facture,impuesto,importe)
					VALUES(".$id.",".$id.",'IVA',".round($retiva,2).")";
	   	$rsq=$db->query($sql);
	   	print "<script>location.href='facture.php?facid=".$id."'</script>";
   }
}
if(GETPOST('tdocument') && GETPOST('edittp')==2){
	$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_type_document (fk_facture,tipo_document) 
			VALUES (".$id.",".GETPOST('tdocument').")";
	$rs=$db->query($sql);
	if(GETPOST('tdocument')==2 || GETPOST('tdocument')==3){
		$sql="SELECT rowid,total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$id;
	   	if($conf->global->MAIN_MODULE_MULTICURRENCY){
	   		$sql="SELECT rowid,multicurrency_total_ht as total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$id;
	   	}
	   	$retiva=0;
       	$retisr=0;
    	$resultset=$db->query($sql);
    	while($rsqq=$db->fetch_object($resultset)){
    		$isrprod=$rsqq->total_ht*0.10;
    		$ivaprod=$rsqq->total_ht*0.106667;
    		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) 
    				VALUES(".$id.",".$rsqq->rowid.",'".round($rsqq->total_ht,2)."','002','Tasa','0.106667','".round($ivaprod,2)."')";
    		$rsq=$db->query($sql);
    		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe)
    				VALUES(".$id.",".$rsqq->rowid.",'".round($rsqq->total_ht,2)."','001','Tasa','0.10','".round($isrprod,2)."')";
    		$rsq=$db->query($sql);
    		$retiva+=$ivaprod;
    		$retisr+=$isrprod;
    	}
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retenciones (factura_id,fk_facture,impuesto,importe)
					VALUES(".$id.",".$id.",'IVA',".round($retiva,2).")";
	   	$rsq=$db->query($sql);
	   	$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retenciones (factura_id,fk_facture,impuesto,importe)
					VALUES(".$id.",".$id.",'ISR',".round($retisr,2).")";
	   	$rsq=$db->query($sql);
	   	print "<script>location.href='facture.php?facid=".$id."'</script>";
	}
	if(GETPOST('tdocument')==5){
		$sql="SELECT rowid,total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$id;
   		if($conf->global->MAIN_MODULE_MULTICURRENCY){
   			$sql="SELECT rowid,multicurrency_total_ht as total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$id;
	   	}
   		$retiva=0;
       	$resultset=$db->query($sql);
    	while($rsqq=$db->fetch_object($resultset)){
    		$ivaprod=$rsqq->total_ht*0.04;
    		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe) 
    				VALUES(".$id.",".$rsqq->rowid.",'".round($rsqq->total_ht,2)."','002','Tasa','0.04','".round($ivaprod,2)."')";
    		$rsq=$db->query($sql);
    		$retiva+=$ivaprod;
    	}
	   	$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retenciones (factura_id,fk_facture,impuesto,importe)
					VALUES(".$id.",".$id.",'IVA',".round($retiva,2).")";
	   	$rsq=$db->query($sql);
	   	print "<script>location.href='facture.php?facid=".$id."'</script>";
	}
}
$sql="SELECT IFNULL(tipo_document,NULL) as tipo_document
			FROM ".MAIN_DB_PREFIX."cfdimx_type_document
			WHERE fk_facture=".$id;
$resp=$db->query($sql);
$respp=$db->fetch_object($resp);
if($respp->tipo_document==NULL){
	$sql="SELECT type FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$id;
	$resp=$db->query($sql);
	$respp=$db->fetch_object($resp);
	if($respp->type==2){
		$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_type_document (fk_facture,tipo_document)
			VALUES (".$id.",4)";
		$resp=$db->query($sql);
	}
}
if( $cfdi_tot>0 ){
	print "<tr>";
		print "<td>";
			print "Tipo de Documento:";
		print "</td>";
		$sql="SELECT IFNULL(tipo_document,NULL) as tipo_document
			FROM ".MAIN_DB_PREFIX."cfdimx_type_document
			WHERE fk_facture=".$id;
		$resp=$db->query($sql);
		$respp=$db->fetch_object($resp);
		print "<td colspan='4'>";
		if($respp->tipo_document!=NULL){
			if($respp->tipo_document==1){
				print "Factura Estandar";
			}
			if($respp->tipo_document==2){
				print "Recibo de Honorarios";
			}
			if($respp->tipo_document==3){
				print "Recibo de Arrendamiento";
			}
			if($respp->tipo_document==4){
				print "Nota de Credito";
			}
			if($respp->tipo_document==5){
				print "Factura de Fletes";
			}
		}else{
			$sql="SELECT type FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$id;
					$resp=$db->query($sql);
					$respp=$db->fetch_object($resp);
					if($respp->type==2){
						print "Nota de Credito";
					}else{
						print "Factura Estandar";
					}
		}
		print "</td>";
	print "</tr>";
}else{
	print "<tr>";
		print "<td>";
			print "Tipo de Documento:";
		print "</td>";
	$sql="SELECT IFNULL(tipo_document,NULL) as tipo_document
			FROM ".MAIN_DB_PREFIX."cfdimx_type_document
			WHERE fk_facture=".$id;
	$resp=$db->query($sql);
	$respp=$db->fetch_object($resp);
	if($respp->tipo_document!=NULL){
		print "<td colspan='2'>";
			if($respp->tipo_document==1){
				print "Factura Estandar";
			}
			if($respp->tipo_document==2){
				print "Recibo de Honorarios";
			}
			if($respp->tipo_document==3){
				print "Recibo de Arrendamiento";
			}
			if($respp->tipo_document==4){
				print "Nota de Credito";
			}
			if($respp->tipo_document==5){
				print "Factura de Fletes";
			}
			if($respp->tipo_document!=4){
			print "<td colspan='2'><form action='facture.php?facid=".$id."&edittp=1' method='POST'>";
			print "<select name='tdocument' id='tdocument'>";
			print "<option value='1'>Factura Estandar</option>";
			print "<option value='2'>Recibo de Honorarios</option>";
			print "<option value='3'>Recibo de Arrendamiento</option>";
			print "<option value='5'>Factura de Fletes</option>";
			print "</select>
				<input type='submit' value='Editar'></form>";
			print "</td>";
			}
		print "</td>";
	}else{
	
		print "<td colspan='4'><form action='facture.php?facid=".$id."&edittp=2' method='POST'>";
	    print "<select name='tdocument' id='tdocument'>";
			print "<option value='1'>Factura Estandar</option>";
			print "<option value='2'>Recibo de Honorarios</option>";
			print "<option value='3'>Recibo de Arrendamiento</option>";
			print "<option value='5'>Factura de Fletes</option>";
		print "</select>
				<input type='submit' value='Seleccionar'></form>";
		print "</td>";
	}
	print "</tr>";
}
            // Date invoice
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('Date');
            print '</td>';
            if ($object->type != 2 && $action != 'editinvoicedate' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editinvoicedate&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';

            if ($object->type != 2)
            {
                if ($action == 'editinvoicedate')
                {
                    $form->form_date($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->date,'invoicedate');
                }
                else
                {
                    print dol_print_date($object->date,'daytext');
                }
            }
            else
            {
                print dol_print_date($object->date,'daytext');
            }
            print '</td>';

            /*
             * List of payments
             */
            $nbrows=8;
            if ($conf->projet->enabled) $nbrows++;

            //Local taxes
            if ($mysoc->pays_code=='ES')
            {
                if($mysoc->localtax1_assuj=="1") $nbrows++;
                if($mysoc->localtax2_assuj=="1") $nbrows++;
            }

            print '<td rowspan="'.$nbrows.'" colspan="2" valign="top">';

            print '<table class="nobordernopadding" width="100%">';

            // List of payments already done
            print '<tr class="liste_titre">';
            print '<td>'.($object->type == 2 ? $langs->trans("PaymentsBack") : $langs->trans('Payments')).'</td>';
            print '<td>'.$langs->trans('Type').'</td>';
            print '<td align="right">'.$langs->trans('Amount').'</td>';
            print '<td width="18">&nbsp;</td>';
            print '</tr>';

            $var=true;

            // Payments already done (from payment on this invoice)
            $sql = 'SELECT p.datep as dp, p.num_paiement, p.rowid,';
            $sql.= ' c.code as payment_code, c.libelle as payment_label,';
            $sql.= ' pf.amount';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'c_paiement as c, '.MAIN_DB_PREFIX.'paiement_facture as pf';
            $sql.= ' WHERE pf.fk_facture = '.$object->id.' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
            $sql.= ' ORDER BY dp, tms';

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                $i = 0;

                if ($object->type != 2)
                {
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($result);
                        $var=!$var;
                        print '<tr '.$bc[$var].'><td>';
                        print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('ShowPayment'),'payment').' ';
                        print dol_print_date($db->jdate($objp->dp),'day').'</a></td>';
                        $label=($langs->trans("PaymentType".$objp->payment_code)!=("PaymentType".$objp->payment_code))?$langs->trans("PaymentType".$objp->payment_code):$objp->payment_label;
                        print '<td>'.$label.' '.$objp->num_paiement.'</td>';
                        print '<td align="right">'.price($objp->amount).'</td>';
                        print '<td>&nbsp;</td>';
                        print '</tr>';
                        $i++;
                    }
                }
                $db->free($result);
            }
            else
            {
                dol_print_error($db);
            }

            if ($object->type != 2)
            {
                // Total already paid
                print '<tr><td colspan="2" align="right">';
                if ($object->type != 3) print $langs->trans('AlreadyPaidNoCreditNotesNoDeposits');
                else print $langs->trans('AlreadyPaid');
                print ' :</td><td align="right">'.price($totalpaye).'</td><td>&nbsp;</td></tr>';

                $resteapayeraffiche=$resteapayer;

                // Loop on each credit note or deposit amount applied
                $creditnoteamount=0;
                $depositamount=0;
                $sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
                $sql.= " re.description, re.fk_facture_source";
                $sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re";
                $sql.= " WHERE fk_facture = ".$object->id;
                $resql=$db->query($sql);
                if ($resql)
                {
                    $num = $db->num_rows($resql);
                    $i = 0;
                    $invoice=new Facture($db);
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($resql);
                        $invoice->fetch($obj->fk_facture_source);
                        print '<tr><td colspan="2" align="right">';
                        if ($invoice->type == 2) print $langs->trans("CreditNote").' ';
                        if ($invoice->type == 3) print $langs->trans("Deposit").' ';
                        print $invoice->getNomUrl(0);
                        print ' :</td>';
                        print '<td align="right">'.price($obj->amount_ttc).'</td>';
                        print '<td align="right">';
                        print '<a href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&action=unlinkdiscount&discountid='.$obj->rowid.'">'.img_delete().'</a>';
                        print '</td></tr>';
                        $i++;
                        if ($invoice->type == 2) $creditnoteamount += $obj->amount_ttc;
                        if ($invoice->type == 3) $depositamount += $obj->amount_ttc;
                    }
                }
                else
                {
                    dol_print_error($db);
                }

                // Paye partiellement 'escompte'
                if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'discount_vat')
                {
                    print '<tr><td colspan="2" align="right" nowrap="1">';
                    print $form->textwithpicto($langs->trans("Escompte").':',$langs->trans("HelpEscompte"),-1);
                    print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
                    $resteapayeraffiche=0;
                }
                // Paye partiellement ou Abandon 'badcustomer'
                if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'badcustomer')
                {
                    print '<tr><td colspan="2" align="right" nowrap="1">';
                    print $form->textwithpicto($langs->trans("Abandoned").':',$langs->trans("HelpAbandonBadCustomer"),-1);
                    print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
                    //$resteapayeraffiche=0;
                }
                // Paye partiellement ou Abandon 'product_returned'
                if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'product_returned')
                {
                    print '<tr><td colspan="2" align="right" nowrap="1">';
                    print $form->textwithpicto($langs->trans("ProductReturned").':',$langs->trans("HelpAbandonProductReturned"),-1);
                    print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
                    $resteapayeraffiche=0;
                }
                // Paye partiellement ou Abandon 'abandon'
                if (($object->statut == 2 || $object->statut == 3) && $object->close_code == 'abandon')
                {
                    print '<tr><td colspan="2" align="right" nowrap="1">';
                    $text=$langs->trans("HelpAbandonOther");
                    if ($object->close_note) $text.='<br><br><b>'.$langs->trans("Reason").'</b>:'.$object->close_note;
                    print $form->textwithpicto($langs->trans("Abandoned").':',$text,-1);
                    print '</td><td align="right">'.price($object->total_ttc - $creditnoteamount - $depositamount - $totalpaye).'</td><td>&nbsp;</td></tr>';
                    $resteapayeraffiche=0;
                }

                // Billed
                print '<tr><td colspan="2" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($object->total_ttc).'</td><td>&nbsp;</td></tr>';

                // Remainder to pay
                print '<tr><td colspan="2" align="right">';
                if ($resteapayeraffiche >= 0) print $langs->trans('RemainderToPay');
                else print $langs->trans('ExcessReceived');
                print ' :</td>';
                print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayeraffiche).'</b></td>';
                print '<td nowrap="nowrap">&nbsp;</td></tr>';
            }
            else
            {
                // Sold credit note
                print '<tr><td colspan="2" align="right">'.$langs->trans('TotalTTC').' :</td>';
                print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price(abs($object->total_ttc)).'</b></td><td>&nbsp;</td></tr>';
            }

            print '</table>';

            print '</td></tr>';

            // Date payment term
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('DateMaxPayment');
            print '</td>';
            if ($object->type != 2 && $action != 'editpaymentterm' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($object->type != 2)
            {
                if ($action == 'editpaymentterm')
                {
                    $form->form_date($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->date_lim_reglement,'paymentterm');
                }
                else
                {
                    print dol_print_date($object->date_lim_reglement,'daytext');
                    if ($object->date_lim_reglement < ($now - $conf->facture->client->warning_delay) && ! $object->paye && $object->statut == 1 && ! $object->am) print img_warning($langs->trans('Late'));
                }
            }
            else
            {
                print '&nbsp;';
            }
            print '</td></tr>';

            // Conditions de reglement
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('PaymentConditionsShort');
            print '</td>';
            if ($object->type != 2 && $action != 'editconditions' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($object->type != 2)
            {
                if ($action == 'editconditions')
                {
                    $form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->cond_reglement_id,'cond_reglement_id');
                }
                else
                {
                    $form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->cond_reglement_id,'none');
                }
            }
            else
            {
                print '&nbsp;';
            }
            print '</td></tr>';

			$sql = "SELECT * FROM ".MAIN_DB_PREFIX."societe_rib WHERE default_rib=1 AND fk_soc = " . $soc->id;
			$resql=$db->query($sql);
			$nmc = $db->fetch_object($resql);

			print '<tr><td>Cuenta:';
            print '</td><td colspan="3">'.$nmc->number.' '.info_admin('El valor de este dato es el correspondiente al campo Numero de Cuenta de Pago de la factura electrónica, Nota: No es requerido',1).' <a href="../societe/rib.php?socid='.$soc->id.'&action=edit">Modificar Valor</a></td></tr>';
			
            // Mode de reglement
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('PaymentMode');
            print '</td>';
            if ($action != 'editmode' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($action == 'editmode')
            {
                $form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
            }
            else
            {
                $form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->mode_reglement_id,'none');
            }
            if($cfdi_tot<1){
            	if(GETPOST('action')=='agregarmtp'){
            		$msql="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_mode_paiement 
            				WHERE fk_facture=".$object->id." AND fk_c_paiement=".GETPOST('mtdpago');
            		$mqs=$db->query($msql);
            		$mnrw=$db->num_rows($mqs);
            		if($mnrw==0){
	            		$misql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_facture_mode_paiement (fk_facture,fk_c_paiement) 
	            				VALUES ('".$object->id."','".GETPOST('mtdpago')."')";
	            		$mqs=$db->query($misql);
            		}
            	}
            	if(GETPOST('action')=='delmtdp'){
            		$dsql="DELETE FROM ".MAIN_DB_PREFIX."cfdimx_facture_mode_paiement WHERE rowid=".GETPOST('idpa');
            		$mqs=$db->query($dsql);
            	}
            	$sqlm="SELECT id,code,libelle FROM ".MAIN_DB_PREFIX."c_paiement
						WHERE active=1 AND id!=".$object->mode_reglement_id." AND id!=0 
								AND id NOT IN (SELECT fk_c_paiement FROM ".MAIN_DB_PREFIX."cfdimx_facture_mode_paiement 
            				WHERE fk_facture=".$object->id." )";
            	$mqs=$db->query($sqlm);
            	//print ":::".$sqlm;
            	print "<br><br><form action='facture.php?facid=".$object->id."' method='POST'> 
            			<input type='hidden' name='action' id='action' value='agregarmtp'>
            			<table><tr><td>Agregar mas metodos de pago:</td></tr>";
            	print "<tr><td><select name='mtdpago' id='mtdpago'>";
            	while($mrs=$db->fetch_object($mqs)){
            		print "<option value='".$mrs->id."'>".$langs->trans("PaymentTypeShort".$mrs->code)."</option>";
            	}
            	print "</select> <input type='submit' value='Agregar'> </td></tr></table></form>";
            	$smq="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_mode_paiement,".MAIN_DB_PREFIX."c_paiement 
            				WHERE fk_facture=".$object->id." AND fk_c_paiement=id";
            	$mqs=$db->query($smq);
            	$mnrw=$db->num_rows($mqs);
            	if($mnrw>0){
            		while($mrs=$db->fetch_object($mqs)){
            			if($mrs->accountancy_code=='' || $mrs->accountancy_code== null){
            				$codpa=99;
            			}else{
            				$codpa=$mrs->accountancy_code;
            			}
            			print $langs->trans("PaymentTypeShort".$mrs->code)." - ".$codpa." <a href='facture.php?facid=".$object->id."&action=delmtdp&idpa=".$mrs->rowid."'>".img_delete()."</a><br>";
            		}
            	}
            }else{
            	$smq="SELECT * FROM ".MAIN_DB_PREFIX."cfdimx_facture_mode_paiement,".MAIN_DB_PREFIX."c_paiement 
            				WHERE fk_facture=".$object->id." AND fk_c_paiement=id";
            	$mqs=$db->query($smq);
            	$mnrw=$db->num_rows($mqs);
            	if($mnrw>0){
            		while($mrs=$db->fetch_object($mqs)){
            			if($mrs->accountancy_code=='' || $mrs->accountancy_code== null){
            				$codpa=99;
            			}else{
            				$codpa=$mrs->accountancy_code;
            			}
            			print "<br>".$langs->trans("PaymentTypeShort".$mrs->code)." - ".$codpa;
            		}
            	}
            }
            print '</td></tr>';

            // Amount
            print '<tr><td>'.$langs->trans('AmountHT').'</td>';
            print '<td align="right" colspan="2" nowrap>'.price($object->total_ht).'</td>';
            $divisa = isset($divisa)?$divisa:$_REQUEST['osd'];
            print '<td>'.$langs->trans('Currency'.(isset($divisa)?$divisa:$conf->currency)).'</td></tr>';
            print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2" nowrap>'.price($object->total_tva).'</td>';
            print '<td>'.$langs->trans('Currency'.(isset($divisa)?$divisa:$conf->currency)).'</td></tr>';

            // Amount Local Taxes
            if ($mysoc->pays_code=='ES')
            {
                if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
                {
                    print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->pays_code).'</td>';
                    print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax1).'</td>';
                    print '<td>'.$langs->trans("Currency".(isset($divisa)?$divisa:$conf->currency)).'</td></tr>';
                }
                if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
                {
                    print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->pays_code).'</td>';
                    print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax2).'</td>';
                    print '<td>'.$langs->trans("Currency".(isset($divisa)?$divisa:$conf->currency)).'</td></tr>';
                }
            }
          /*INI ISH*/
            if(1){
            	$sql="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."product_extrafields LIKE 'prodcfish'";
            	$resql=$db->query($sql);
            	$existe_ish = $db->num_rows($resql);
            	$totalish=0;
            	if( $existe_ish > 0 ){
            		 $sql="SELECT a.fk_product,a.total_ht,b.prodcfish,((b.prodcfish/100)*a.total_ht) as impish,c.ref,c.label
							FROM ".MAIN_DB_PREFIX."facturedet a,
							(SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields WHERE prodcfish!=0 AND prodcfish IS NOT NULL) b,
									".MAIN_DB_PREFIX."product c
							WHERE a.fk_facture=".$id." AND
								a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
            		$ass=$db->query($sql);
            		$asf=$db->num_rows($ass);
            		if($asf>0){
            			while($asd=$db->fetch_object($ass)){
            				$totalish=$totalish+$asd->impish;
            			}
            		}
            	}
            	if($totalish>0){
            		print "<tr>
            			<td>Importe ISH</td>
            			<td align='right' colspan='2' nowrap>".number_format($totalish,2)."</td>
            			<td>".$langs->trans('Currency'.(isset($divisa)?$divisa:$conf->currency))."</td>
            		   </tr>";
            		
            		//if($cfdi_tot<1){
            			$object->total_ttc=$object->total_ttc+$totalish;
            			$object->total_ttc=str_replace(",", "", number_format($object->total_ttc,2));
            		//}
            		//$object->total_ttc=$object->total_ttc+$totalish;
            	}
            }
            /*FIN ISH*/
            
            $sqm="SELECT COUNT(*) AS count FROM information_schema.tables
					WHERE table_schema = '".$db->database_name."' AND table_name = '".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales'";
            $rqm=$db->query($sqm);
            $rqsm=$db->fetch_object($rqm);
            if($rqsm>0){
            	$resqm=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE fk_facture = " . $_REQUEST["facid"]);
            	if ($resqm){
            		$cfdi_m = $db->num_rows($resqm);
            		$i = 0;
            		if ($cfdi_m>0){
            			while ($i < $cfdi_m){
            				$obm = $db->fetch_object($resqm);
            				if($cfdi_tot<1){
            					print "<tr>
		            			<td>Ret. ".$obm->codigo."</td>
		            			<td align='right' colspan='2' nowrap>".number_format($obm->importe,2)."</td>
		            			<td>".$langs->trans('Currency'.(isset($divisa)?$divisa:$conf->currency))."</td>
		            		   </tr>";
            					$object->total_ttc=$object->total_ttc-$obm->importe;
            					$object->total_ttc=str_replace(",", "", number_format($object->total_ttc,2));
            				}else{
            					print "<tr>
		            			<td>Ret. ".$obm->codigo."</td>
		            			<td align='right' colspan='2' nowrap>".number_format($obm->importe,2)."</td>
		            			<td>".$langs->trans('Currency'.(isset($divisa)?$divisa:$conf->currency))."</td>
		            		   </tr>";
            				}
            				$i++;
            			}
            		}
            	}
            }
            
            $sql="SELECT count(*) as exist
			FROM ".MAIN_DB_PREFIX."cfdimx_retenciones
			WHERE fk_facture=".$id;
            $rsq=$db->query($sql);
            $rsqq=$db->fetch_object($rsq);
            if($rsqq->exist>0){
            	$sql="SELECT impuesto,importe
						FROM ".MAIN_DB_PREFIX."cfdimx_retenciones
						WHERE fk_facture=".$id;
            	$rsq=$db->query($sql);
            	$restar=0;
            	while($rsqq=$db->fetch_object($rsq)){
            		$restar=$restar+$rsqq->importe;
            	print "<tr>
            			<td>Retencion de ".$rsqq->impuesto."</td>
            			<td align='right' colspan='2' nowrap>".number_format($rsqq->importe,2)."</td>
            			<td>".$langs->trans('Currency'.(isset($divisa)?$divisa:$conf->currency))."</td>
            		   </tr>";
            	}
	            if(1/*$cfdi_tot<1*/){
	            	$total_res=$object->total_ttc-$restar;
	            	print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($total_res).'</td>';
	            	print '<td>'.$langs->trans('Currency'.(isset($divisa)?$divisa:$conf->currency)).'</td></tr>';
	            }
	            else{
		            print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($object->total_ttc).'</td>';
		            print '<td>'.$langs->trans('Currency'.(isset($divisa)?$divisa:$conf->currency)).'</td></tr>';
	            }
            }else{
            print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($object->total_ttc).'</td>';
            print '<td>'.$langs->trans('Currency'.(isset($divisa)?$divisa:$conf->currency)).'</td></tr>';
            }
            // Statut
            print '<tr><td>'.$langs->trans('Status').'</td>';
            print '<td align="left" colspan="3">'.($object->getLibStatut(4,$totalpaye)).'</td></tr>';

            // Project
            if ($conf->projet->enabled)
            {
                $langs->load('projects');
                print '<tr>';
                print '<td>';

                print '<table class="nobordernopadding" width="100%"><tr><td>';
                print $langs->trans('Project');
                print '</td>';
                if ($action != 'classify')
                {
                    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;facid='.$object->id.'">';
                    print img_edit($langs->trans('SetProject'),1);
                    print '</a></td>';
                }
                print '</tr></table>';

                print '</td><td colspan="3">';
                if ($action == 'classify')
                {
                    $form->form_project($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->socid,$object->fk_project,'projectid');
                }
                else
                {
                    $form->form_project($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->socid,$object->fk_project,'none');
                }
                print '</td>';
                print '</tr>';
            }

            // Other attributes
            $parameters=array('colspan' => ' colspan="3"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
                foreach($extrafields->attribute_label as $key=>$label)
                {
                    $value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
                    print '<tr><td>'.$label.'</td><td colspan="3">';
                    print $extrafields->showInputField($key,$value);
                    print '</td></tr>'."\n";
                }
            }

            print '</table><br>';

            if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
            {
            	require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
            	require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
            	$formcompany= new FormCompany($db);

            	$blocname = 'contacts';
            	$title = $langs->trans('ContactsAddresses');
            	include(DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php');
            }

            if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
            {
            	$blocname = 'notes';
            	$title = $langs->trans('Notes');
            	include(DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php');
            }

            /*
             * Lines
             */
            $result = $object->getLinesArray();

            if ($conf->use_javascript_ajax && $object->statut == 0)
            {
                include(DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php');
            }

           /* print '<table id="tablelines" class="noborder noshadow" width="100%">';

            // Show object lines
            if (! empty($object->lines)) $object->printObjectLines($action,$mysoc,$soc,$lineid,1,$hookmanager);

            /*
             * Form to add new line
             * /
            if ($object->statut == 0 && $user->rights->facture->creer && $action <> 'valid' && $action <> 'editline')
            {
                $var=true;

                $object->formAddFreeProduct(1,$mysoc,$soc,$hookmanager);

                // Add predefined products/services
                if ($conf->product->enabled || $conf->service->enabled)
                {
                    $var=!$var;
                    $object->formAddPredefinedProduct(1,$mysoc,$soc,$hookmanager);
                }

                $parameters=array();
                $reshook=$hookmanager->executeHooks('formAddObject',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            }

            print "</table>\n"; */
            
            print "</div>\n";
            
            /*
             * Boutons actions
             */
			if( isset( $_REQUEST["cancelaCFDIaction"] ) ){
                //Cancel in web service
                $service = new ComprobanteCFDIService();
                $cfdiId = $service->GetCFDIId($db, $id);
                $canceledResult = $service->CancelCFDI($cfdiId);
                
                //Cancel in database
                $cancela_fact = "
                UPDATE ".MAIN_DB_PREFIX."facture SET close_code = 'abandon', close_note = 'CFDI Cancelado', fk_statut = 3 
                WHERE rowid = " . $_REQUEST["facid"];
                $rr = $db->query( $cancela_fact );
                        
                $cancela_update = "UPDATE  ".MAIN_DB_PREFIX."cfdimx SET cancelado = 1 WHERE fk_facture = " . $_REQUEST["facid"];
                $rr = $db->query( $cancela_update );

                $cancela_cfdi = "UPDATE cfdi_comprobante SET cancelado = 2 WHERE fk_comprobante = " . $_REQUEST["facid"];
                $rr = $db->query( $cancela_cfdi );

                echo '
                <script>
                location.href="?facid='.$_REQUEST["facid"].'";
                </script>';

            }
            
            if( isset( $_REQUEST["reenviaCFDIaction"] ) ){
                //Cancel in web service
                $service = new ComprobanteCFDIService();
                $cfdiId = $service->GetCFDIId($db, $id);

                $cfdi_soc_data = $service->GetClientDataByFactureId($db, $id);
                $vendorEmail = $service->GetAuthorEmailByFactureId($db, $id);

                if($cfdi_soc_data[0]['email']) {
                    $sendResponse = $service->sendCFDI($response['Id'], $cfdi_soc_data[0]['email']);
                }

                if($vendorEmail) {
                    $service->sendCFDI($response['Id'], $vendorEmail);
                }

                echo '
                <script>
                location.href="?facid='.$_REQUEST["facid"].'";
                </script>';

			}

			if( $msg_cfdi_final!="" ){
				print '
				<p></p>
				<div align="center">
				<div align="center" style="width:800px; border:solid 1px; height:40px; background-color:#FFC; padding-top:10px, color:#C00">
				<strong>'.$msg_cfdi_final.'</strong>
				</div></div><p></p>';
			}
			echo '<div style="display:table-row">';
			echo '<div style="display:table-cell; width:600px; vertical-align:top">';
			print '<table width="100%" style="border:1px solid;">';
			print '<tr class="liste_titre">';
			print '<td>&nbsp;Retenciones</td>';
			print '</tr>';
			print '<tr><td>';
			print '</div></div>';
				
			if( $cfdi_tot<1 ){
				echo '
			<form method="post">
				<strong>Retencion:</strong><br>
				Impuesto:
				<select name="impuesto">
					<option value="002">IVA</option>
					<option value="001">ISR</option>
				</select>
				Tasa (c_TasaOCuota): <input type="text" name="importe" size="5">
				<input type="submit" name="envRetencion" value="&nbsp;Registrar Retenci&oacute;n&nbsp;">
			</form>
			<br>';
				/* $sqmm="SELECT rowid, impuesto FROM ".MAIN_DB_PREFIX."cfdimx_catalog_retenciones
						ORDER BY rowid";
				$rqm=$db->query($sqmm);
				while($rsm=$db->fetch_object($rqm)){
					echo '<option value="'.$rsm->impuesto.'">'.$rsm->impuesto.'</option>';
				} */
					
					
				print '<table>';
				print '<tr><td align="center"><strong>Borrar</strong></td><td><strong>Impuesto</strong></td><td><strong>Importe</strong></td></tr>';
			
				$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones WHERE fk_facture = " . $_REQUEST["facid"]);
				if ($resql){
					$cfdi_tott = $db->num_rows($resql);
					$i = 0;
					if ($cfdi_tott>0){
						while ($i < $cfdi_tott){
							$obj = $db->fetch_object($resql);
							print '<tr><td valign="bottom">
						 <a href="?facid='.$_REQUEST["facid"].'&del_retencion='.$obj->retenciones_id.'&tptre='.$obj->impuesto.'" onClick="return confirm(\'¿Esta seguro de eliminar el registro?\')">
						'.img_delete().'</a></td><td>
						 &nbsp;'.$obj->impuesto.'</td><td valign="bottom">&nbsp;'.number_format($obj->importe,2).'</td></tr>';
							$i++;
						}
					}else{
						print '<tr><td colspan="2" align="center">No hay registros relacionados</td></tr>';
					}
				}
			
				print '</table>';
					
			}else{
				$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones WHERE fk_facture = " . $_REQUEST["facid"]);
				if ($resql){
					$cfdi_tott = $db->num_rows($resql);
					$i = 0;
					if ($cfdi_tott>0){
						while ($i < $cfdi_tott){
							$obj = $db->fetch_object($resql);
							print '<strong>Impuesto:</strong> '.$obj->impuesto.' <strong>Importe:</strong> '.$obj->importe."<br>";
							$i++;
						}
					}else{
						echo "No se registraron retenciones para esta factura";
					}
				}
			
			}
				
			print '</td></tr>';
			print '</table>';
			
			$sqm="SELECT COUNT(*) AS count FROM information_schema.tables
					WHERE table_schema = '".$db->database_name."' AND table_name = '".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales'";
			$rqm=$db->query($sqm);
			$rqsm=$db->fetch_object($rqm);
			if($rqsm>0){
				$sqm="SELECT rowid,cod,descripcion,tasa
					FROM ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales
					WHERE entity=".$conf->entity;
				$rqm=$db->query($sqm);
				$nrm=$db->num_rows($rqm);
				if($nrm>0){
					echo '<br>';
					print '<table width="100%" style="border:1px solid;">';
					print '<tr class="liste_titre">';
					print '<td>&nbsp;Retenciones Locales</td>';
					print '</tr>';
					print '<tr><td>';
					if( $cfdi_tot<1 ){
						echo '
				<form method="post">
					<input type="hidden" name="facid" id="facid" value="'.$_REQUEST["facid"].'">
					<strong>Retencion:</strong><br>
					Impuesto:
					 <select name="retlocal">';
						while($rms=$db->fetch_object($rqm)){
							print "<option value='".$rms->rowid."'>".$rms->cod." - ".$rms->tasa."%</option>";
						}
						echo '</select>
					<input type="submit" name="envRetencionLocal" value="&nbsp;Registrar Retenci&oacute;n&nbsp;">
					</form>
					<br>';
						print '<table>';
						print '<tr><td align="center"><strong>Borrar</strong></td><td><strong>Impuesto</strong></td><td><strong>Importe</strong></td></tr>';
						$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE fk_facture = " . $_REQUEST["facid"]);
						if ($resql){
							$cfdi_t = $db->num_rows($resql);
							$i = 0;
							if ($cfdi_t>0){
								while ($i < $cfdi_t){
									$obj = $db->fetch_object($resql);
									print '<tr><td valign="bottom">
							 <a href="?facid='.$_REQUEST["facid"].'&del_retencion_local='.$obj->rowid.'" onClick="return confirm(\'Esta seguro de eliminar el registro?\')">
							 <img src="../theme/auguria/img/delete.png"></a></td><td>
							 &nbsp;'.$obj->codigo.'&nbsp;'.$obj->tasa.'%</td><td valign="bottom">&nbsp;'.number_format($obj->importe,2).'</td></tr>';
									$i++;
								}
							}else{
								print '<tr><td colspan="2" align="center">No hay registros relacionados</td></tr>';
							}
						}
						print '</table>';
							
					}else{
						$resql=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE fk_facture = " . $_REQUEST["facid"]);
						if ($resql){
							$cfdi_t = $db->num_rows($resql);
							$i = 0;
							if ($cfdi_t>0){
								while ($i < $cfdi_t){
									$obj = $db->fetch_object($resql);
									print '<strong>Impuesto:</strong> '.$obj->codigo.' '.$obj->tasa.'% <strong>Importe:</strong> '.$obj->importe."<br>";
									$i++;
								}
							}else{
								echo "No se registraron retenciones para esta factura";
							}
						}
					}
					print '</td></tr>';
					print '</table>';
				}
			}
			
			if(2){
				$sql="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."product_extrafields LIKE 'prodcfish'";
				$resql=$db->query($sql);
				$existe_ish = $db->num_rows($resql);
				if( $existe_ish > 0 ){
					$sql="SELECT a.fk_product,a.total_ht,b.prodcfish,((b.prodcfish/100)*a.total_ht) as impish,c.ref,c.label
							FROM ".MAIN_DB_PREFIX."facturedet a,
							(SELECT fk_object,prodcfish FROM ".MAIN_DB_PREFIX."product_extrafields ) b,
									".MAIN_DB_PREFIX."product c
							WHERE a.fk_facture=".$object->id." AND
								a.fk_product =b.fk_object AND a.fk_product=c.rowid ORDER BY a.rowid";
					$ass=$db->query($sql);
					$asf=$db->num_rows($ass);
					if($asf>0){
					print "<br>";
					print '<table width="100%" style="border:1px solid;">';
					print '<tr class="liste_titre">';
					print '<td>&nbsp;Importe ISH &nbsp;&nbsp;&nbsp;</td>';
					print '</tr>';
					print '<tr><td>';
					
					if($asf>0){
						print "<table width='100%'>";
						print "<tr>";
						print "<td><strong>Producto</strong></td>";
						print "<td><strong>Importe Sin IVA</strong></td>";
						print "<td><strong>Porcentaje ISH</strong></td>";
						print "<td><strong>ISH</strong></td>";
						print "</tr>";
						$totalish=0;
						while($asd=$db->fetch_object($ass)){
							print "<tr>"; 
								print "<td>".$asd->ref."-".$asd->label."</td>"; 
								print "<td>".number_format($asd->total_ht,2)."</td>";
								print "<td>".$asd->prodcfish."</td>";
								print "<td>".number_format($asd->impish,2)."</td>";
							print "</tr>";
							$totalish=$totalish+$asd->impish;
						}
						print "<tr><td align='right' colspan='3'><strong>Total ISH:</strong></td><td>".number_format($totalish,2)."</td></tr>";
						print "</table>";
					}
					print '</td>';
					print '</tr>
							</table>';
				   }
				} 
			}
			if(1){
			$filename=dol_sanitizeFileName($object->ref);
			$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?facid='.$object->id.'&uuid='.$uuid;
			$genallowed=$user->rights->facture->creer;
			$delallowed=$user->rights->facture->supprimer;
			
			print '<br>';
			//print $formfile->showdocuments('facture',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang,$hookmanager);
			//$somethingshown=$formfile->numoffiles;
			include 'class/cfdi.html.formfile.class.php';
			if(1){
				// Send by mail
				if($uuid != ""){
					if (($object->statut == 1 || $object->statut == 2))
					{
						if ($objectidnext)
						{
							print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('SendByMail').'</span>';
						}
						else
						{
							if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send)
							{
								if ($action != 'prerelance' && $action != 'presend')
								{
									print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
								}
							}
							else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
						}
					}
				}
				//Estos son los archivos
				if ($action == 'presend')
				{
					/*
					 * Affiche formulaire mail
					 */
					//if (is_readable('afficheFormaMail.php'))include 'afficheFormaMail.php';
					// By default if $action=='presend'
					$titreform='SendBillByMail';
					$topicmail='SendBillRef';
					$action='send';
					$modelmail='facture_send';
					 
					if ($action == 'prerelance')	// For backward compatibility
					{
						$titrefrom='SendReminderBillByMail';
						$topicmail='SendReminderBillRef';
						$action='relance';
						$modelmail='facture_relance';
					}
					 
					$ref = dol_sanitizeFileName($object->ref);
					include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref,'/'));
					$file=$fileparams['fullname'];
					 
					// Build document if it not exists
					if (! $file || ! is_readable($file))
					{
						// Define output language
						$outputlangs = $langs;
						$newlang='';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
						if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
						if (! empty($newlang))
						{
							$outputlangs = new Translate("",$conf);
							$outputlangs->setDefaultLang($newlang);
						}
						 
						$result=facture_pdf_create($db, $object, GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
						if ($result <= 0)
						{
							dol_print_error($db,$result);
							exit;
						}
						$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref,'/'));
						$file=$fileparams['fullname'];
					}
					 
					print '<br>';
					print_titre($langs->trans($titreform));
					 
					// Cree l'objet formulaire mail
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);
					$formmail->fromtype = 'user';
					$formmail->fromid   = $user->id;
					$formmail->fromname = $user->getFullName($langs);
					$formmail->frommail = $user->email;
					$formmail->withfrom=1;
					$formmail->withto=empty($_POST["sendto"])?1:$_POST["sendto"];
					$formmail->withtosocid=$soc->id;
					$formmail->withtocc=1;
					$formmail->withtoccsocid=0;
					$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
					$formmail->withtocccsocid=0;
					if(DOL_VERSION<'3.9'){
						$formmail->withtopic=$langs->transnoentities($topicmail,'__FACREF__');
					}else{
						$formmail->withtopic=$langs->transnoentities($topicmail,'__REF__');
					}
					$formmail->withfile=2;
					$formmail->withbody=1;
					$formmail->withdeliveryreceipt=1;
					$formmail->withcancel=1;
					// Tableau des substitutions
					/* require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
					$contacn = new Contact($db);
					$sqlc="SELECT a.rowid FROM ".MAIN_DB_PREFIX."socpeople a,".MAIN_DB_PREFIX."element_contact b 
							WHERE a.fk_soc=".$soc->id." 
							AND fk_socpeople=a.rowid AND element_id=".$object->id." LIMIT 1";
					//print $sqlc;
					$rc=$db->query($sqlc);
					if($rsc=$db->fetch_object($rc)){
						$contacn->fetch($rsc->rowid);
						$formmail->substit['__CONTACTCIVNAME__']=$contacn->firstname.' '.$contacn->lastname;
					} */
					$formmail->substit['__CONTACTCIVNAME__']=$soc->name;
					if(DOL_VERSION<'3.9'){
						$formmail->substit['__FACREF__']=$object->ref;
					}else{
						$formmail->substit['__REF__']=$object->ref;
					}
					$formmail->substit['__SIGNATURE__']=$user->signature;
					$formmail->substit['__PERSONALIZED__']='';
					// Tableau des parametres complementaires du post
					$formmail->param['action']=$action;
					$formmail->param['models']=$modelmail;
					$formmail->param['facid']=$object->id;
					$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;
					 
					// Init list of files
					if (GETPOST("mode")=='init')
					{
						$formmail->clear_attached_files();
						 
						$uuidPDF = $uuid.'.pdf';
						$fileUUID = str_replace(basename($file), $uuidPDF, $file);
						$uuidXML = $uuid.'.xml';
						$fileXML = str_replace(basename($file), $uuidXML, $file);
						 
						$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
						 
						if ( $fileUUID && is_readable($fileUUID)){
							$formmail->add_attached_files($fileUUID,basename($fileUUID),dol_mimetype($fileUUID));
						}
						if ( $fileXML && is_readable($fileXML)){
							$formmail->add_attached_files($fileXML,basename($fileXML),dol_mimetype($fileXML));
						}
					}
					 
					$formmail->show_form();
					print '<br>';
					// Affiche formulaire mail Termina
				}
				print "<br>";
				print '<br>';
				print '<br>';
			}
			
			
			
			$formfileCfdimx = new FormFileCfdiMx($db);
			print $formfileCfdimx->showdocuments('facture',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang,$hookmanager,$cfdi_tot);
			$somethingshown=$formfileCfdimx->numoffiles;
			
			
			}
			
			echo '</div>';
			echo '<div style="display:table-cell">&nbsp;&nbsp;&nbsp;&nbsp;</div>';
			echo '<div>';
			$sqld="SELECT tpdomicilio,receptor_delompio,receptor_colonia,receptor_calle,receptor_noext,receptor_noint,
            		determinado FROM ".MAIN_DB_PREFIX."cfdimx_domicilios_receptor WHERE receptor_rfc='".$soc_rfc."' AND entity_id=".$conf->entity;
			//echo $sqld;
			$resd= $db->query($sqld);
			$dnum=$db->num_rows($resd);
			//$tpdomic='';
			if($dnum>=1){
				print '<form action="facture.php?facid='.$id.'" method="POST">';
				print '<table width="100%" style="border:1px solid;">';
				print "<tr class='liste_titre'>";
				print "<td colspan='2'>Domicilio Facturacion: ";
				print "<select name='dfacturacion' onchange='this.form.submit()'>";
				while($dres=$db->fetch_object($resd)){
					if($_REQUEST["dfacturacion"]==$dres->tpdomicilio){
						dol_syslog('ENTRA AQUI 1');
						$a='SELECTED';
						$tpdomic=$dres->tpdomicilio;
						$rdelompio=$dres->receptor_delompio;
						$rcolonia=$dres->receptor_colonia;
						$rcalle=$dres->receptor_calle;
						$rnoext=$dres->receptor_noext;
						$rnoint=$dres->receptor_noint;
						$_SESSION['tpdomic']=$dres->tpdomicilio;
					}else{
						if($dres->determinado==1 && $_REQUEST["dfacturacion"]==''){
							dol_syslog('ENTRA AQUI 2');
							$a='SELECTED';
							$tpdomic=$dres->tpdomicilio;
							$rdelompio=$dres->receptor_delompio;
							$rcolonia=$dres->receptor_colonia;
							$rcalle=$dres->receptor_calle;
							$rnoext=$dres->receptor_noext;
							$rnoint=$dres->receptor_noint;
							$_SESSION['tpdomic']=$dres->tpdomicilio;
						}else{$a='';}
					}
					
					print "<option value='".$dres->tpdomicilio."' ".$a.">".$dres->tpdomicilio."</option>";
				}
				print "<select>";
				//session_start();
				//dol_syslog('ESTE DOMICILIO'.$tpdomic);
				
				print "</td>";
				print "</tr>";
				print "<tr>";
				print "<td>Delegacion o Municipio</td>";
				print "<td>".$rdelompio."</td>";
				print "</tr>";
				print "<tr>";
				print "<td>Colonia</td>";
				print "<td>".$rcolonia."</td>";
				print "</tr>";
				print "<tr>";
				print "<td>Calle</td>";
				print "<td>".$rcalle."</td>";
				print "</tr>";
				print "<tr>";
				print "<td>No. Exterior</td>";
				print "<td>".$rnoext."</td>";
				print "</tr>";
				print "<tr>";
				print "<td>No. Interior</td>";
				print "<td>".$rnoint."</td>";
				print "</tr>";
				print "</table> ";
				print '</form>';
			}else{
				print '<table width="100%" height="100px" style="border:1px solid;">';
				print "<tr class='liste_titre'>";
				print "<td colspan='2'>Domicilio Facturacion:";
				print "</td>";
				print "</tr>";
				print "<tr><td colspan='2' rowspan='5'>";
				print "NO CUENTA CON UN DOMICILIO FISCAL:: <a href='domicilios.php?socid=".$soc_id."'>AGREGAR</a> ";
				print "</td></tr>";
				print "</table> ";
			}
			
			
			if(1){
				print '<br>';
				if( $cfdi_tot>0 ){
					print '<strong>Factura Timbrada - UUID:</strong>'.$uuid."&nbsp;<br>";
						
				}
				
				print '<strong>Status del Comprobante:</strong>'.$status_comprobante."&nbsp;<br>&nbsp;";
				
				if( $modo_timbrado==1 ){
					$modo_timbrado_desc = "Produccion";
				}else{ $modo_timbrado_desc = "Pruebas"; }
				echo '
				<div style="width:380px; border:solid 1px; height:40px; background-color:#990000; padding:10px">
				<font color="#FFFFFF">
				<strong>Modalidad de Facturacion:</strong> '.$modo_timbrado_desc.'<br>
				<strong>Folios Disponibles:</strong> '.$folios_disponibles.'<br>
				<strong>Folios Timbrados:</strong> '.$folios_timbrados.'<br>
				</font>
				</div>
				<div style="font-size:14px">';
				//echo 'Para adquirir folios <a href="http://facturacion.admin.auriboxenlinea.com/" target="_blank" >Click Aquí</a>';
				//print $soc_rfc."<br>";
				if($soc_rfc==''){
					echo "<br><br><font color='#990000'><strong>El Cliente (Receptor) no tiene un RFC asignado<br> Para complementar <a href='../societe/soc.php?socid=".$soc_id."&action=edit'>Click aquí</a></strong></font>";
				}else{
// 					$sql = "
// 									SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp
// 									WHERE receptor_rfc = '" . $soc_rfc . "' AND entity_id = " . $_SESSION['dol_entity'];
// 					//echo "<br>".$sql;
// 					$resql=$db->query($sql);
// 					if ($resql){
// 						$tot_rec_dom_receptor = $db->num_rows($resql);
// 						if($tot_rec_dom_receptor<1){
// 							echo "<br><br><font color='#990000'><strong>El Cliente (Receptor) no tiene un domicilio fiscal asignado<br> Para complementar <a href='domicilios.php?socid=".$soc_id."'>Click aquí</a></strong></font>";
// 						}
// 					}
				}
				echo	'</div>';
				
				if ($action != 'prerelance' && $action != 'presend')
				{
					if ($user->societe_id == 0 && $action <> 'valid' && $action <> 'editline')
					{
						print '<div class="tabsAction">';
							
						// --> DIXI
						if($status_conf==1){
							if( $cfdi_tot>0 ){
                               // print('there is cfdimx');
								if( $cfdi_cancela==1 ){
				
									print '<div style="font-size:12px; color:990000">CFDI Cancelado</div>';
								}else{
				
									if( $status_comprobante=="EnProceso" ){
										//echo '<font color="#990000"></font>';
									}else if( $status_comprobante=="Enviado" ){
										echo '<div align="right">';
										echo '<p>';
										echo '<form method="post" action="">';
										echo '<input type="hidden" name="uuid" value="'.$uuid.'">';
										echo '<input type="hidden" name="rfc_emisor" value="'.$conf->global->MAIN_INFO_SIREN.'">';
                                        if ($user->rights->facture->supprimer) {
                                            echo '<input type="submit" onclick="return confirm(\'¿Esta seguro de cancelar la factura?\')" name="cancelaCFDIaction" value="Cancelar CFDI" class="button">';
                                        }
                                        echo '<input type="reenviar" onclick="return confirm(\'¿Esta seguro de reenviar la factura?\')" name="reenviaCFDIaction" value="Reenviar CFDI" class="button">';
										echo '</form>';
										echo '</p>';
										echo '</div>';
									}else{
										print 'status comprobante: '.$status_comprobante;
									}
								}
							}
							if( $cfdi_tot<1 ){
                              // print('there is no cfdimx');
				
								if( $soc_rfc!="" ){
				
									//AQUI TIENES QUE PONER LA VALIDACIÓN DEL DOMICILIO DEL RECEPTOR
									$sql = "
								SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp
								WHERE receptor_rfc = '" . $soc_rfc . "' AND entity_id = " . $_SESSION['dol_entity'];
									//echo $sql;
									$resql=$db->query($sql);
									if ($resql){
										$tot_rec_dom_receptor =1;//= $db->num_rows($resql);
										if($tot_rec_dom_receptor<1)
										{
										}else{
										//		print('we have direction');
												
											//if($object->getLibStatut(1,$totalpaye)=='Borrador' || getLinkGeneraCFDI($status,$id,$db)=='Fuera de fecha de timbrado'){
											//	if($object->getLibStatut(1,$totalpaye)=='Borrador'){
											//		print 'No puede timbrar un borrador';
											//	}
												//if(getLinkGeneraCFDI($status,$id,$db)=='Fuera de fecha de timbrado'){
												//	print 'Fuera de fecha de timbrado';
												//}
											//}else{
                                               // print('ololol');
												if( true )
												{
                                                 //   print('yes');
                                                    if($timbreProfact ) {
                                                   //     print('herER===???');
                                                   print '<form name="generarCFDI" id="generarCFDI" action="' . $_SERVER["PHP_SELF"] . '?facid='.$object->id.'&amp;tpdomi='.$tpdomic.'&amp;osd='.$osd.'&amp;tdc='.$tdc.'&amp;action=generaCFDI2" method="POST">
                                                   <input type="hidden" name="tokentaasa" value="' . $_SESSION ['newtokentaasa'] . '">';
                                                   print '<input class="button" type="submit" name="generarCFDIButton" value="Generar CFDI" />';
                                                       // print '<a class="butAction" style="color:blue" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;tpdomi='.$tpdomic.'&amp;osd='.$osd.'&amp;tdc='.$tdc.'&amp;action=generaCFDI2">Generar CFDI </a>'."<br>".$msg_dom_receptor." ".$msg_mail;//boton generar CFDI 
                                                    } else {
                                                        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;tpdomi='.$tpdomic.'&amp;osd='.$osd.'&amp;tdc='.$tdc.'&amp;action=generaCFDI">Generar CFDI</a>'."<br>".$msg_dom_receptor." ".$msg_mail;//AMM boton generar CFDI
                                                    }
													
												}elseif( $modo_timbrado==2 )
												{
													print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;tpdomi='.$tpdomic.'&amp;osd='.$osd.'&amp;tdc='.$tdc.'&amp;&amp;action=generaCFDI">Generar CFDI</a>'."<br>".$msg_dom_receptor." ".$msg_mail;
												}
											
										}
									}else{
										$msg_cfdi_final = "El RFC del receptor es requerido para generar el comprobante";
									}
								}
							}
						}else{
							print 'Existen errores en la configuración';
				
						}
						print '</div>';
					}
				}
			}
			echo '</div>';
			echo '</div>';
			echo '<div style="display:table-row">';
			echo '<div style="display:table-cell; width:600px; vertical-align:top">';
			//Estos son los archivos
//             if ($action != 'prerelance' && $action != 'presend')
//             {
//                 print '<table width="100%"><tr><td width="50%" valign="top">';
//                 print '<a name="builddoc"></a>'; // ancre

//                 /*
//                  * Documents generes
//                  */
//                 /* $filename=dol_sanitizeFileName($object->ref);
//                 $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
//                 $urlsource=$_SERVER['PHP_SELF'].'?facid='.$object->id.'&uuid='.$uuid;
//                 $genallowed=$user->rights->facture->creer;
//                 $delallowed=$user->rights->facture->supprimer;

//                 print '<br>';
//                 //print $formfile->showdocuments('facture',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang,$hookmanager);
//                 //$somethingshown=$formfile->numoffiles;
				
//                 include 'class/cfdi.html.formfile.class.php';
                
//                 $formfileCfdimx = new FormFileCfdiMx($db);
//                 print $formfileCfdimx->showdocuments('facture',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang,$hookmanager);
//                 $somethingshown=$formfileCfdimx->numoffiles; */

//                 /*
//                  * Linked object block
//                  */
//                /*  $somethingshown=$object->showLinkedObjectBlock();

//                 // Link for paypal payment
//                 if ($conf->paypal->enabled && $object->statut != 0)
//                 {
//                     include_once(DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php');
//                     print showPaypalPaymentUrl('invoice',$object->ref);
//                 }

//                 print '</td><td valign="top" width="50%">';

//                 print '<br>';

//                 // List of actions on element
//                 include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
//                 $formactions=new FormActions($db);
//                 $somethingshown=$formactions->showactions($object,'invoice',$socid); */

//                 print '</td>'; 				
// 				print '</tr></table>';
//             }else{
//              /*
//               * Affiche formulaire mail
//               */
//                 //if (is_readable('afficheFormaMail.php'))include 'afficheFormaMail.php';
//             	// By default if $action=='presend'
//             	$titreform='SendBillByMail';
//             	$topicmail='SendBillRef';
//             	$action='send';
//             	$modelmail='facture_send';
            	
//             	if ($action == 'prerelance')	// For backward compatibility
//             	{
//             		$titrefrom='SendReminderBillByMail';
//             		$topicmail='SendReminderBillRef';
//             		$action='relance';
//             		$modelmail='facture_relance';
//             	}
            	
//             	$ref = dol_sanitizeFileName($object->ref);
//             	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
//             	$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref,'/'));
//             	$file=$fileparams['fullname'];
            	
//             	// Build document if it not exists
//             	if (! $file || ! is_readable($file))
//             	{
//             		// Define output language
//             		$outputlangs = $langs;
//             		$newlang='';
//             		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
//             		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
//             		if (! empty($newlang))
//             		{
//             			$outputlangs = new Translate("",$conf);
//             			$outputlangs->setDefaultLang($newlang);
//             		}
            	
//             		$result=facture_pdf_create($db, $object, GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $hookmanager);
//             		if ($result <= 0)
//             		{
//             			dol_print_error($db,$result);
//             			exit;
//             		}
//             		$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref,'/'));
//             		$file=$fileparams['fullname'];
//             	}
            	
//             	print '<br>';
//             	print_titre($langs->trans($titreform));
            	
//             	// Cree l'objet formulaire mail
//             	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
//             	$formmail = new FormMail($db);
//             	$formmail->fromtype = 'user';
//             	$formmail->fromid   = $user->id;
//             	$formmail->fromname = $user->getFullName($langs);
//             	$formmail->frommail = $user->email;
//             	$formmail->withfrom=1;
//             	$formmail->withto=empty($_POST["sendto"])?1:$_POST["sendto"];
//             	$formmail->withtosocid=$soc->id;
//             	$formmail->withtocc=1;
//             	$formmail->withtoccsocid=0;
//             	$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
//             	$formmail->withtocccsocid=0;
//             	$formmail->withtopic=$langs->transnoentities($topicmail,'__FACREF__');
//             	$formmail->withfile=2;
//             	$formmail->withbody=1;
//             	$formmail->withdeliveryreceipt=1;
//             	$formmail->withcancel=1;
//             	// Tableau des substitutions
//             	$formmail->substit['__FACREF__']=$object->ref;
//             	$formmail->substit['__SIGNATURE__']=$user->signature;
//             	$formmail->substit['__PERSONALIZED__']='';
//             	// Tableau des parametres complementaires du post
//             	$formmail->param['action']=$action;
//             	$formmail->param['models']=$modelmail;
//             	$formmail->param['facid']=$object->id;
//             	$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;
            	
//             	// Init list of files
//             	if (GETPOST("mode")=='init')
//             	{
//             		$formmail->clear_attached_files();
            	
//             		$uuidPDF = $uuid.'.pdf';
//             		$fileUUID = str_replace(basename($file), $uuidPDF, $file);
//             		$uuidXML = $uuid.'.xml';
//             		$fileXML = str_replace(basename($file), $uuidXML, $file);
            		 
//             		$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
            		 
//             		if ( $fileUUID && is_readable($fileUUID)){
//             			$formmail->add_attached_files($fileUUID,basename($fileUUID),dol_mimetype($fileUUID));
//             		}
//             		if ( $fileXML && is_readable($fileXML)){
//             			$formmail->add_attached_files($fileXML,basename($fileXML),dol_mimetype($fileXML));
//             		}
//             	}
            	
//             	$formmail->show_form();
//             	print '<br>';
//             	 // Affiche formulaire mail Termina
//             }				
			echo '</div>
				<div style="display:table-cell">&nbsp;&nbsp;&nbsp;&nbsp;</div>
				<div style="display:table-cell; vertical-align:top">';
				
// 				if( $cfdi_tot>0 ){					
// 					print '<strong>Factura Timbrada - UUID:</strong>'.$uuid."&nbsp;<br>";
					
// 				}
				
// 				print '<strong>Status del Comprobante:</strong>'.$status_comprobante."&nbsp;<br>&nbsp;";
				
// 				if( $modo_timbrado==1 ){
// 					$modo_timbrado_desc = "Producción";
// 				}else{ $modo_timbrado_desc = "Pruebas"; }
// 				echo '
// 				<div style="width:380px; border:solid 1px; height:40px; background-color:#990000; padding:10px">
// 				<font color="#FFFFFF">
// 				<strong>Modalidad de Facturación:</strong> '.$modo_timbrado_desc.'<br>
// 				<strong>Folios Disponibles:</strong> '.$folios_disponibles.'<br>
// 				<strong>Folios Timbrados:</strong> '.$folios_timbrados.'<br>
// 				</font>
// 				</div>
// 				<div style="font-size:14px">';
// 				//echo 'Para adquirir folios <a href="http://facturacion.admin.auriboxenlinea.com/" target="_blank" >Click Aquí</a>';
// 				if($soc_rfc==''){
// 					echo "<br><br><font color='#990000'><strong>El Cliente (Receptor) no tiene un RFC asignado<br> Para complementar <a href='../societe/soc.php?socid=".$soc_id."&action=edit'>Click aquí</a></strong></font>";
// 				}else{
// 					$sql = "
// 									SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp
// 									WHERE receptor_rfc = '" . $soc_rfc . "' AND entity_id = " . $_SESSION['dol_entity'];
// 					//echo "<br>".$sql;
// 					$resql=$db->query($sql);
// 					if ($resql){
// 						$tot_rec_dom_receptor = $db->num_rows($resql);
// 						if($tot_rec_dom_receptor<1){
// 						echo "<br><br><font color='#990000'><strong>El Cliente (Receptor) no tiene un domicilio fiscal asignado<br> Para complementar <a href='domicilios.php?socid=".$soc_id."'>Click aquí</a></strong></font>";
// 						}
// 					}
// 				}
// 			   echo	'</div>';

//             if ($action != 'prerelance' && $action != 'presend')
//             {
//                 if ($user->societe_id == 0 && $action <> 'valid' && $action <> 'editline')
//                 {
//                     print '<div class="tabsAction">';
					
//                     // --> DIXI
// 					if($status_conf==1){
// 						if( $cfdi_tot>0 ){
// 							if( $cfdi_cancela==1 ){
								
// 								print '<div style="font-size:12px; color:990000">CFDI Cancelado</div>';
// 							}else{
								
// 								if( $status_comprobante=="EnProceso" ){
// 									//echo '<font color="#990000"></font>';
// 								}else if( $status_comprobante=="Enviado" ){
// 									echo '<div align="right">';
// 										echo '<p>';
// 										echo '<form method="post" action="">';
// 										echo '<input type="hidden" name="uuid" value="'.$uuid.'">';
// 										echo '<input type="hidden" name="rfc_emisor" value="'.$conf->global->MAIN_INFO_SIREN.'">';	
// 										echo '<input type="submit" onclick="return confirm(\'¿Esta seguro de cancelar la factura?\')" name="cancelaCFDIaction" value="Cancelar CFDI" class="button">';
// 										echo '</form>';
// 										echo '</p>';
// 									echo '</div>';									
// 								}else{
// 									print 'status_comprobante: '.$status_comprobante;
// 								}
// 							}
// 						}
// 						if( $cfdi_tot<1 ){
														
// 							if( $soc_rfc!="" ){

// 								//AQUI TIENES QUE PONER LA VALIDACIÓN DEL DOMICILIO DEL RECEPTOR
// 								$sql = "
// 								SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp 
// 								WHERE receptor_rfc = '" . $soc_rfc . "' AND entity_id = " . $_SESSION['dol_entity'];
// 								//echo $sql;
// 								$resql=$db->query($sql);
// 								if ($resql){
// 									 $tot_rec_dom_receptor = $db->num_rows($resql);
// 									 if($tot_rec_dom_receptor<1)
// 									 {
// 									 }else{
									 	
									 	
// 									 	if($object->getLibStatut(1,$totalpaye)=='Borrador' || getLinkGeneraCFDI($status,$id,$db)=='Fuera de fecha de timbrado'){
// 									 		if($object->getLibStatut(1,$totalpaye)=='Borrador'){
// 									 			print 'No puede timbrar un borrador';
// 									 		}
// 									 		if(getLinkGeneraCFDI($status,$id,$db)=='Fuera de fecha de timbrado'){
// 									 			print 'Fuera de fecha de timbrado';
// 									 		}
// 									 	}else{
// 										 	if( $modo_timbrado==1 && $folios_disponibles>0 )
// 										 	{
// 										 		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;tpdomi='.$tpdomic.'&amp;osd='.$osd.'&amp;tdc='.$tdc.'&amp;action=generaCFDI">Generar CFDI</a>'."<br>".$msg_dom_receptor." ".$msg_mail;//AMM boton generar CFDI
// 										 	}elseif( $modo_timbrado==2 )
// 										 	{
// 										 		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;tpdomi='.$tpdomic.'&amp;osd='.$osd.'&amp;tdc='.$tdc.'&amp;&amp;action=generaCFDI">Generar CFDI</a>'."<br>".$msg_dom_receptor." ".$msg_mail;
// 										 	}
// 									 	}
// 									 }
// 							    }else{
// 								 $msg_cfdi_final = "El RFC del receptor es requerido para generar el comprobante";
// 							    }
// 						    }
// 					   }
// 					}else{
// 						 print 'Existen errores en la configuración';	
					   
// 					}
//                     print '</div>';
//                 }
//             }
            		
			echo '</div>
			</div>
			<p></p>';			
			
			if( $_REQUEST["del_retencion"]!="" ){
				$delete="DELETE FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones WHERE retenciones_id = " . $_REQUEST["del_retencion"];
				$db->query($delete);
				$rescomm = $db->commit();
				if($_REQUEST["tptre"]=="IVA"){
					$delete="DELETE FROM  ".MAIN_DB_PREFIX."cfdimx_retencionesdet WHERE impuesto='002' AND factura_id = ".$_REQUEST["facid"];
					$db->query($delete);
				}
				if($_REQUEST["tptre"]=="ISR"){
					$delete="DELETE FROM  ".MAIN_DB_PREFIX."cfdimx_retencionesdet WHERE impuesto='001' AND factura_id = ".$_REQUEST["facid"];
					$db->query($delete);
				}
				
				echo '<script>location.href="?facid='.$_REQUEST["facid"].'"</script>';
			}
			if( $_REQUEST["del_retencion_local"]!="" ){
				$delete="DELETE FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE rowid = " . $_REQUEST["del_retencion_local"];
				//print $delete;
				$db->query($delete);
				$rescomm = $db->commit();
				echo '<script>location.href="?facid='.$_REQUEST["facid"].'"</script>';
			}
			if( $_REQUEST["envRetencion"]!="" ){
				if( $_REQUEST["impuesto"]!="" && $_REQUEST["importe"]!="" ){
// 					$insert = "
// 					INSERT INTO  ".MAIN_DB_PREFIX."cfdimx_retenciones (
// 						fk_facture,
// 						impuesto,
// 						importe
// 					) VALUES ( 
// 						'".$_REQUEST["facid"]."',
// 						'".$_REQUEST["impuesto"]."',
// 						'".$_REQUEST["importe"]."'
// 					)";
					
					$sql="SELECT rowid,total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$_REQUEST["facid"];
					if($conf->global->MAIN_MODULE_MULTICURRENCY){
						$sql="SELECT rowid,multicurrency_total_ht as total_ht FROM ".MAIN_DB_PREFIX."facturedet WHERE fk_facture=".$_REQUEST["facid"];
					}
					$retiva=0;
					$resultset=$db->query($sql);
					while($rsqq=$db->fetch_object($resultset)){
						$ivaprod=$rsqq->total_ht*$_REQUEST["importe"];
						$sql="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retencionesdet(factura_id, fk_facturedet, base, impuesto, tipo_factor, tasa, importe)
    				VALUES(".$_REQUEST["facid"].",".$rsqq->rowid.",'".round($rsqq->total_ht,2)."','".$_REQUEST["impuesto"]."','Tasa','".$_REQUEST["importe"]."','".round($ivaprod,2)."')";
						$rsq=$db->query($sql);
						$retiva+=$ivaprod;
					}
					$insert="INSERT INTO ".MAIN_DB_PREFIX."cfdimx_retenciones (factura_id,fk_facture,impuesto,importe)
					VALUES(".$_REQUEST["facid"].",".$_REQUEST["facid"].",'IVA',".round($retiva,2).")";
					
					$db->query($insert);
					$rescomm = $db->commit();
					if( $rescomm==1 ){
						echo '<script>location.href="?facid='.$_REQUEST["facid"].'"</script>';
					}else{
						echo 'Error al insertar';	
					}
				}
			}
			if( $_REQUEST["envRetencionLocal"]!="" ){
				if( $_REQUEST["retlocal"]!=""){
					$sqm="SELECT rowid, cod,descripcion,tasa FROM ".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales
							WHERE entity=".$conf->entity." AND rowid=".$_REQUEST["retlocal"];
					//print $sqm;
					$rqs=$db->query($sqm);
					$mrs=$db->fetch_object($rqs);
					$importe=$object->total_ht*($mrs->tasa/100);
					$insert = "
					INSERT INTO  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales (
						fk_facture,codigo,tasa,importe
					) VALUES (
						'".$_REQUEST["facid"]."',
						'".$mrs->cod."',
						'".$mrs->tasa."',
						'".$importe."'
					)";
					$db->query($insert);
					$rescomm = $db->commit();
					if( $rescomm==1 ){
						echo '<script>location.href="?facid='.$_REQUEST["facid"].'"</script>';
					}else{
						echo 'Error al insertar';
					}
				}
			}

        }
        else
        {
            dol_print_error($db,$object->error);
        }
    }
    else
    {
        /***************************************************************************
         *                                                                         *
         *                      Mode Liste                                         *
         *                                                                         *
         ***************************************************************************/
        $now=dol_now();

        $sortfield = GETPOST("sortfield",'alpha');
        $sortorder = GETPOST("sortorder",'alpha');
        $page = GETPOST("page",'int');
        if ($page == -1) { $page = 0; }
        $offset = $conf->liste_limit * $page;
        if (! $sortorder) $sortorder='DESC';
        if (! $sortfield) $sortfield='f.datef';
        $limit = $conf->liste_limit;

        $pageprev = $page - 1;
        $pagenext = $page + 1;

        $day	= GETPOST('day','int');
        $month	= GETPOST('month','int');
        $year	= GETPOST('year','int');

        $facturestatic=new Facture($db);

        if (! $sall) $sql = 'SELECT';
        else $sql = 'SELECT DISTINCT';
        $sql.= ' f.rowid as facid, f.facnumber, f.type, f.increment, f.total, f.total_ttc,';
        $sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
        $sql.= ' f.paye as paye, f.fk_statut,';
        $sql.= ' s.nom, s.rowid as socid';
        if (! $sall) $sql.= ', SUM(pf.amount) as am';   // To be able to sort on status
        $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
        if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= ', '.MAIN_DB_PREFIX.'facture as f';
        if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
        else $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
        $sql.= ' WHERE f.fk_soc = s.rowid';
        $sql.= " AND f.entity = ".$conf->entity;
        if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socid) $sql.= ' AND s.rowid = '.$socid;
        if ($userid)
        {
            if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
            else $sql.=' AND f.fk_user_author = '.$userid;
        }
        if ($_GET['filtre'])
        {
            $filtrearr = explode(',', $_GET['filtre']);
            foreach ($filtrearr as $fil)
            {
                $filt = explode(':', $fil);
                $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
            }
        }
        if ($search_ref)
        {
            $sql.= ' AND f.facnumber LIKE \'%'.$db->escape(trim($search_ref)).'%\'';
        }
        if ($search_societe)
        {
            $sql.= ' AND s.nom LIKE \'%'.$db->escape(trim($search_societe)).'%\'';
        }
        if ($search_montant_ht)
        {
            $sql.= ' AND f.total = \''.$db->escape(trim($search_montant_ht)).'\'';
        }
        if ($search_montant_ttc)
        {
            $sql.= ' AND f.total_ttc = \''.$db->escape(trim($search_montant_ttc)).'\'';
        }
        if ($month > 0)
        {
            if ($year > 0 && empty($day))
            $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
            else if ($year > 0 && ! empty($day))
            $sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
            else
            $sql.= " AND date_format(f.datef, '%m') = '".$month."'";
        }
        else if ($year > 0)
        {
            $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
        }
        if (! $sall)
        {
            $sql.= ' GROUP BY f.rowid, f.facnumber, f.type, f.increment, f.total, f.total_ttc,';
            $sql.= ' f.datef, f.date_lim_reglement,';
            $sql.= ' f.paye, f.fk_statut,';
            $sql.= ' s.nom, s.rowid';
        }
        else
        {
        	$sql.= ' AND (s.nom LIKE \'%'.$db->escape($sall).'%\' OR f.facnumber LIKE \'%'.$db->escape($sall).'%\' OR f.note LIKE \'%'.$db->escape($sall).'%\' OR fd.description LIKE \'%'.$db->escape($sall).'%\')';
        }
        $sql.= ' ORDER BY ';
        $listfield=explode(',',$sortfield);
        foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
        $sql.= ' f.rowid DESC ';
        $sql.= $db->plimit($limit+1,$offset);
        //print $sql;

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);

            if ($socid)
            {
                $soc = new Societe($db);
                $soc->fetch($socid);
            }

            $param='&amp;socid='.$socid;
            if ($month) $param.='&amp;month='.$month;
            if ($year)  $param.='&amp;year=' .$year;

            print_barre_liste($langs->trans('BillsCustomers').' '.($socid?' '.$soc->nom:''),$page,'facture.php',$param,$sortfield,$sortorder,'',$num);
			
            $i = 0;
            print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
            print '<table class="liste" width="100%">';
            print '<tr class="liste_titre">';
            print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.facnumber','',$param,'',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("DateDue"),$_SERVER['PHP_SELF'],"f.date_lim_reglement","&amp;socid=$socid","",'align="center"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('Company'),$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','',$param,'align="right"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','',$param,'align="right"',$sortfield,$sortorder);
            //print '<td class="liste_titre">&nbsp;</td>';
            print '</tr>';

            // Filters lines
            print '<tr class="liste_titre">';
            print '<td class="liste_titre" align="left">';
            print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'">';
            print '</td>';
            print '<td class="liste_titre" align="center">';
            if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
            print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
            $htmlother->select_year($year?$year:-1,'year',1, 20, 5);
            print '</td>';
            print '<td class="liste_titre" align="left">&nbsp;</td>';
            print '<td class="liste_titre" align="left">';
            print '<input class="flat" type="text" name="search_societe" value="'.$search_societe.'">';
            print '</td><td class="liste_titre" align="right">';
            print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$search_montant_ht.'">';
            print '</td><td class="liste_titre" align="right">';
            print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$search_montant_ttc.'">';
            print '</td>';
            print '<td class="liste_titre" align="right">';
            print '&nbsp;';
            print '</td>';
            print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
            print "</td></tr>\n";

            if ($num > 0)
            {
                $var=True;
                $total=0;
                $totalrecu=0;

                while ($i < min($num,$limit))
                {
                    $objp = $db->fetch_object($resql);
                    $var=!$var;

                    $datelimit=$db->jdate($objp->datelimite);

                    print '<tr '.$bc[$var].'>';
                    print '<td nowrap="nowrap">';

                    $facturestatic->id=$objp->facid;
                    $facturestatic->ref=$objp->facnumber;
                    $facturestatic->type=$objp->type;
                    $paiement = $facturestatic->getSommePaiement();

                    print '<table class="nobordernopadding"><tr class="nocellnopadd">';

                    print '<td class="nobordernopadding" nowrap="nowrap">';
                    print $facturestatic->getNomUrl(1);
                    print $objp->increment;
                    print '</td>';

                    print '<td width="16" align="right" class="nobordernopadding">';
                    $filename=dol_sanitizeFileName($objp->facnumber);
                    $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->facnumber);
                    $urlsource=$_SERVER['PHP_SELF'].'?facid='.$objp->facid;
                    $formfile->show_documents('facture',$filename,$filedir,$urlsource,'','','',1,'',1);
                    print '</td>';
                    print '</tr></table>';

                    print "</td>\n";

                    // Date
                    print '<td align="center" nowrap>';
                    print dol_print_date($db->jdate($objp->df),'day');
                    print '</td>';

                    // Date limit
                    print '<td align="center" nowrap="1">'.dol_print_date($datelimit,'day');
                    if ($datelimit < ($now - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1 && ! $paiement)
                    {
                        print img_warning($langs->trans('Late'));
                    }
                    print '</td>';

                    print '<td>';
                    $thirdparty=new Societe($db);
                    $thirdparty->id=$objp->socid;
                    $thirdparty->nom=$objp->nom;
                    print $thirdparty->getNomUrl(1,'customer');
                    print '</td>';

                    print '<td align="right">'.price($objp->total).'</td>';

                    print '<td align="right">'.price($objp->total_ttc).'</td>';

                    print '<td align="right">'.price($paiement).'</td>';

                    // Affiche statut de la facture
                    print '<td align="right" nowrap="nowrap">';
                    print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$paiement,$objp->type);
                    print "</td>";
                    //print "<td>&nbsp;</td>";
                    print "</tr>\n";
                    $total+=$objp->total;
                    $total_ttc+=$objp->total_ttc;
                    $totalrecu+=$paiement;
                    $i++;
                }

                if (($offset + $num) <= $limit)
                {
                    // Print total
                    print '<tr class="liste_total">';
                    print '<td class="liste_total" colspan="4" align="left">'.$langs->trans('Total').'</td>';
                    print '<td class="liste_total" align="right">'.price($total).'</td>';
                    print '<td class="liste_total" align="right">'.price($total_ttc).'</td>';
                    print '<td class="liste_total" align="right">'.price($totalrecu).'</td>';
                    print '<td class="liste_total" align="center">&nbsp;</td>';
                    print '</tr>';
                }
            }

            print "</table>\n";
            print "</form>\n";
            $db->free($resql);
						
        }
        else
        {
            dol_print_error($db);
        }
    }
}

llxFooter();
$db->close();
?>
