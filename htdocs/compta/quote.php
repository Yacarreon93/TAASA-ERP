<?php

/**
 * \file 	htdocs/compta/quote.php
 * \ingroup facture
 * \brief 	Pagina para hacer cotizaciones
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
if (! empty($conf->commande->enabled))
	require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/cfdi/service/comprobantecfdiservice.php';

$langs->load('bills');
$langs->load('companies');
$langs->load('compta');
$langs->load('products');
$langs->load('banks');
$langs->load('main');
if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');
if (! empty($conf->margin->enabled))
	$langs->load('margins');

$sall = trim(GETPOST('sall'));
$projectid = (GETPOST('projectid') ? GETPOST('projectid', 'int') : 0);

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$lineid = GETPOST('lineid', 'int');
$userid = GETPOST('userid', 'int');
$search_ref = GETPOST('sf_ref') ? GETPOST('sf_ref', 'alpha') : GETPOST('search_ref', 'alpha');
$search_societe = GETPOST('search_societe', 'alpha');
$search_montant_ht = GETPOST('search_montant_ht', 'alpha');
$search_montant_ttc = GETPOST('search_montant_ttc', 'alpha');
$origin = GETPOST('origin', 'alpha');
$originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility
$isTicket =  GETPOST('isTicket', 'alpha');
$createCFDI = GETPOST('createCFDI', 'alpha');
// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Security check
$fieldid = (! empty($ref) ? 'facnumber' : 'rowid');
if ($user->societe_id) $socid = $user->societe_id;
$result = restrictedArea($user, 'facture', $id, '', '', 'fk_soc', $fieldid);

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES = 4;

$usehm = (! empty($conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE) ? $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE : 0);

$object = new Facture($db);
$extrafields = new ExtraFields($db);

// Load object
if ($id > 0 || ! empty($ref)) {
	$ret = $object->fetch($id, $ref);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('invoicecard','globalcard'));

$permissionnote = $user->rights->facture->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->facture->creer;	// Used by the include of actions_dellink.inc.php
$permissiontoedit = $user->rights->facture->creer; // Used by the include of actions_lineupdonw.inc.php

/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not includ_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->facture->creer) {
	//	if (1 == 0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"])) {
	//		$mesgs [] = '<div class="error">' . $langs->trans("NoCloneOptionsSpecified") . '</div>';
	//	} else {
			if ($object->fetch($id) > 0) {
				$result = $object->createFromClone($socid);
				if ($result > 0) {
					if($isTicket) {
						header("Location: " . $_SERVER['PHP_SELF'] . '?isTicket=1&facid=' . $result);
					} else {
						header("Location: " . $_SERVER['PHP_SELF'] . '?facid=' . $result);
					}
					exit();
				} else {
					setEventMessage($object->error, 'errors');
					$action = '';
				}
			}
	//	}
	}

	// Change status of invoice
	else if ($action == 'reopen' && $user->rights->facture->creer) {
		$result = $object->fetch($id);
		if ($object->statut == 2 || ($object->statut == 3 && $object->close_code != 'replaced')) {
			$result = $object->set_unpaid($user);
			if ($result > 0) {
				if($isTicket) {
					header('Location: ' . $_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $id);
				} else {
					header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id);
				}
				exit();
			} else {
				setEventMessage($object->error, 'errors');
			}
		}
	}

	// Delete line
	else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->fetch_thirdparty();

		$result = $object->deleteline(GETPOST('lineid'));
		if ($result > 0) {
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
				$newlang = $_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))
				$newlang = $object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$ret = $object->fetch($id); // Reload to get new records	
				$result = 1;

			}
			if ($result >= 0) {
				if($isTicket) {
					header('Location: ' . $_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $id);
				} else {
					header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id);
				}

				exit();
			}
		} else {
			setEventMessage($object->error, 'errors');
			$action = '';
		}
	}

	//Delete all lines
		else if ($action == 'confirm_deletealllines' && $user->rights->facture->creer)
	{
		$object->fetch($id);

		$result = $object->delete_lines();
		if ($result > 0) {
			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
				$newlang = $_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang))
				$newlang = $object->thirdparty->default_lang;
			if (! empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$ret = $object->fetch($id); // Reload to get new records	
				$result = 1;

			}
			if ($result >= 0) {
				if($isTicket) {
					header('Location: ' . $_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $id);
				} else {
					header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id);
				}

				exit();
			}
		} else {
			setEventMessage($object->error, 'errors');
			$action = '';
		}
	}

	// Delete link of credit note to invoice
	else if ($action == 'unlinkdiscount' && $user->rights->facture->creer)
	{
		$discount = new DiscountAbsolute($db);
		$result = $discount->fetch(GETPOST("discountid"));
		$discount->unlink_invoice();
	}

	else if ($action == 'set_thirdparty' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->setValueFrom('fk_soc', $socid);
		if($isTicket) {
			header('Location: ' . $_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $id);
		} else {
			header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id);
		}
		exit();
	}

	else if ($action == 'classin' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->setProject($_POST['projectid']);
	}

	else if ($action == 'setmode' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$result = $object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
		if ($result < 0)
			dol_print_error($db, $object->error);
	}

	else if ($action == 'setinvoicedate' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$old_date_lim_reglement = $object->date_lim_reglement;
		$date = dol_mktime(12, 0, 0, $_POST['invoicedatemonth'], $_POST['invoicedateday'], $_POST['invoicedateyear']);
		if (empty($date))
		{
		    setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")),'errors');
		    if($isTicket) {
		    	header('Location: '.$_SERVER["PHP_SELF"].'?&isTicket=1&facid='.$id.'&action=editinvoicedate');
		    } else {
		    	header('Location: '.$_SERVER["PHP_SELF"].'?facid='.$id.'&action=editinvoicedate');
		    }

		    exit;
		}
	    $object->date=$date;
		$new_date_lim_reglement = $object->calculate_date_lim_reglement();
		if ($new_date_lim_reglement > $old_date_lim_reglement) $object->date_lim_reglement = $new_date_lim_reglement;
		if ($object->date_lim_reglement < $object->date) $object->date_lim_reglement = $object->date;
		$result = $object->update($user);
		if ($result < 0) dol_print_error($db, $object->error);
	}

	else if ($action == 'setconditions' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->cond_reglement_code = 0; // To clean property
		$object->cond_reglement_id = 0; // To clean property
		$result = $object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));
		if ($result < 0) dol_print_error($db, $object->error);

		$old_date_lim_reglement = $object->date_lim_reglement;
		$new_date_lim_reglement = $object->calculate_date_lim_reglement();
		if ($new_date_lim_reglement > $old_date_lim_reglement) $object->date_lim_reglement = $new_date_lim_reglement;
		if ($object->date_lim_reglement < $object->date) $object->date_lim_reglement = $object->date;
		$result = $object->update($user);
		if ($result < 0) dol_print_error($db, $object->error);
	}

	else if ($action == 'setpaymentterm' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->date_lim_reglement = dol_mktime(12, 0, 0, $_POST['paymenttermmonth'], $_POST['paymenttermday'], $_POST['paymenttermyear']);
		if ($object->date_lim_reglement < $object->date) {
			$object->date_lim_reglement = $object->calculate_date_lim_reglement();
			setEventMessage($langs->trans("DatePaymentTermCantBeLowerThanObjectDate"), 'warnings');
		}
		$result = $object->update($user);
		if ($result < 0)
			dol_print_error($db, $object->error);
	}

	else if ($action == 'setrevenuestamp' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->revenuestamp = GETPOST('revenuestamp');
		$result = $object->update($user);
		$object->update_price(1);
		if ($result < 0)
			dol_print_error($db, $object->error);
	}

	// Set incoterm
	elseif ($action == 'set_incoterms' && !empty($conf->incoterm->enabled))
    {
    	$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
    }

	// bank account
	else if ($action == 'setbankaccount' && $user->rights->facture->creer)
	{
	    $result=$object->setBankAccount(GETPOST('fk_account', 'int'));
	}

	else if ($action == 'setremisepercent' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$result = $object->set_remise($user, $_POST['remise_percent']);
	}

	else if ($action == "setabsolutediscount" && $user->rights->facture->creer)
	{
		// POST[remise_id] ou POST[remise_id_for_payment]
		if (! empty($_POST["remise_id"])) {
			$ret = $object->fetch($id);
			if ($ret > 0) {
				$result = $object->insert_discount($_POST["remise_id"]);
				if ($result < 0) {
					setEventMessage($object->error, 'errors');
				}
			} else {
				dol_print_error($db, $object->error);
			}
		}
		if (! empty($_POST["remise_id_for_payment"])) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
			$discount = new DiscountAbsolute($db);
			$discount->fetch($_POST["remise_id_for_payment"]);

			$result = $discount->link_to_invoice(0, $id);
			if ($result < 0) {
				setEventMessage($discount->error, 'errors');
			}
		}
	}

	else if ($action == 'set_ref_client' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->set_ref_client($_POST['ref_client']);
	}

	// Classify to validated
	else if ($action == 'confirm_valid' && $confirm == 'yes' &&
        ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->validate)))
	)
	{
		$idwarehouse = GETPOST('idwarehouse');

		$object->fetch($id);
		$object->fetch_thirdparty();

		// Check parameters

		// Check for mandatory prof id
		for($i = 1; $i < 6; $i ++)
		{
			$idprof_mandatory = 'SOCIETE_IDPROF' . ($i) . '_INVOICE_MANDATORY';
			$idprof = 'idprof' . $i;
			if (! $object->thirdparty->$idprof && ! empty($conf->global->$idprof_mandatory))
			{
				if (! $error)
					$langs->load("errors");
				$error ++;

				setEventMessage($langs->trans('ErrorProdIdIsMandatory', $langs->transcountry('ProfId' . $i, $object->thirdparty->country_code)), 'errors');
			}
		}

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		// Check for warehouse
		if ($object->type != Facture::TYPE_DEPOSIT && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
		{
			if (! $idwarehouse || $idwarehouse == - 1) {
				$error ++;
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), 'errors');
				$action = '';
			}
		}

		if (! $error)
		{
			$result = $object->validate($user, '', $idwarehouse);
			if ($result >= 0)
			{
				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$model=$object->modelpdf;
					$ret = $object->fetch($id); // Reload to get new records


					// $result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					$result = 1;

	    			if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

	    			if($createCFDI) { //Save CFDI info
						$service = new ComprobanteCFDIService();
						$service->SaveCFDIFromFacture($db, $id);
					}
				}
			}
			else
			{
				if (count($object->errors)) setEventMessage($object->errors, 'errors');
				else setEventMessage($object->error, 'errors');
			}
		}
	}

	// Go back to draft status (unvalidate)
	else if ($action == 'confirm_modif' &&
		((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->unvalidate)))
	)
	{
		$idwarehouse = GETPOST('idwarehouse');

		$object->fetch($id);
		$object->fetch_thirdparty();

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}

		// Check parameters
		if ($object->type != Facture::TYPE_DEPOSIT && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change)
		{
			if (! $idwarehouse || $idwarehouse == - 1) {
				$error ++;
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Warehouse")), 'errors');
				$action = '';
			}
		}

		if (! $error) {
			// On verifie si la facture a des paiements
			$sql = 'SELECT pf.amount';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'paiement_facture as pf';
			$sql .= ' WHERE pf.fk_facture = ' . $object->id;

			$result = $db->query($sql);
			if ($result) {
				$i = 0;
				$num = $db->num_rows($result);

				while ($i < $num) {
					$objp = $db->fetch_object($result);
					$totalpaye += $objp->amount;
					$i ++;
				}
			} else {
				dol_print_error($db, '');
			}

			$resteapayer = $object->total_ttc - $totalpaye;

			// On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
			$ventilExportCompta = $object->getVentilExportCompta();

			// On verifie si aucun paiement n'a ete effectue
			if ($resteapayer == $object->total_ttc && $object->paye == 0 && $ventilExportCompta == 0)
			{
				$result=$object->set_draft($user, $idwarehouse);
				if ($result<0) setEventMessage($object->error,'errors');


				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$model=$object->modelpdf;
					$ret = $object->fetch($id); // Reload to get new records

					$document_mode = 'F';
					$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $document_mode);
				}
			}
		}
	}

	// Classify "paid"
	else if ($action == 'confirm_paid' && $confirm == 'yes' && $user->rights->facture->paiement)
	{
		$object->fetch($id);
		$result = $object->set_paid($user);
		if ($result<0) setEventMessage($object->error,'errors');
	} // Classif "paid partialy"
	else if ($action == 'confirm_paid_partially' && $confirm == 'yes' && $user->rights->facture->paiement)
	{
		$object->fetch($id);
		$close_code = $_POST["close_code"];
		$close_note = $_POST["close_note"];
		if ($close_code) {
			$result = $object->set_paid($user, $close_code, $close_note);
			if ($result<0) setEventMessage($object->error,'errors');
		} else {
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason")), 'errors');
		}
	} // Classify "abandoned"
	else if ($action == 'confirm_canceled' && $confirm == 'yes') {
		$object->fetch($id);
		$close_code = $_POST["close_code"];
		$close_note = $_POST["close_note"];
		if ($close_code) {
			$result = $object->set_canceled($user, $close_code, $close_note);
			if ($result<0) setEventMessage($object->error,'errors');
		} else {
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Reason")), 'errors');
		}
	}

	// Convertir en reduc
	else if ($action == 'confirm_converttoreduc' && $confirm == 'yes' && $user->rights->facture->creer)
	{
		$object->fetch($id);
		$object->fetch_thirdparty();
		//$object->fetch_lines();	// Already done into fetch

		// Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
		$discountcheck=new DiscountAbsolute($db);
		$result=$discountcheck->fetch(0,$object->id);

		$canconvert=0;
		if ($object->type == Facture::TYPE_DEPOSIT && $object->paye == 1 && empty($discountcheck->id)) $canconvert=1;	// we can convert deposit into discount if deposit is payed completely and not already converted (see real condition into condition used to show button converttoreduc)
		if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->paye == 0 && empty($discountcheck->id)) $canconvert=1;	// we can convert credit note into discount if credit note is not payed back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
		if ($canconvert)
		{
			$db->begin();

			// Boucle sur chaque taux de tva
			$i = 0;
			foreach ($object->lines as $line)
			{
				if ($line->total_ht!=0)
				{ 	// no need to create discount if amount is null
					$amount_ht[$line->tva_tx] += $line->total_ht;
					$amount_tva[$line->tva_tx] += $line->total_tva;
					$amount_ttc[$line->tva_tx] += $line->total_ttc;
					$i ++;
				}
			}

			// Insert one discount by VAT rate category
			$discount = new DiscountAbsolute($db);
			if ($object->type == Facture::TYPE_CREDIT_NOTE)
				$discount->description = '(CREDIT_NOTE)';
			elseif ($object->type == Facture::TYPE_DEPOSIT)
				$discount->description = '(DEPOSIT)';
			else {
				setEventMessage($langs->trans('CantConvertToReducAnInvoiceOfThisType'),'errors');
			}
			$discount->tva_tx = abs($object->total_ttc);
			$discount->fk_soc = $object->socid;
			$discount->fk_facture_source = $object->id;

			$error = 0;

			foreach ($amount_ht as $tva_tx => $xxx)
			{
				$discount->amount_ht = abs($amount_ht[$tva_tx]);
				$discount->amount_tva = abs($amount_tva[$tva_tx]);
				$discount->amount_ttc = abs($amount_ttc[$tva_tx]);
				$discount->tva_tx = abs($tva_tx);

				$result = $discount->create($user);
				if ($result < 0)
				{
					$error++;
					break;
				}
			}

			if (empty($error))
			{
				// Classe facture
				$result = $object->set_paid($user);
				if ($result >= 0)
				{
					$db->commit();
				}
				else
				{
					setEventMessage($object->error,'errors');
					$db->rollback();
				}
			}
			else
			{
				setEventMessage($discount->error,'errors');
				$db->rollback();
			}
		}
	}

	/*
	 * Insert new invoice in database
	*/
	else if ($action == 'add' && $user->rights->facture->creer)
	{
		if ($socid > 0) $object->socid = GETPOST('socid', 'int');

		$db->begin();

		$error = 0;

		// Fill array 'array_options' with data from add form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		if($isTicket == "true") {
			$ret = $extrafields->setOptionalsFromPostTicket($extralabels, $object);
			$ret = 0;
		}
		else {
			$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		}
		if ($ret < 0) $error++;

		// Replacement invoice
		if ($_POST['type'] == Facture::TYPE_REPLACEMENT)
		{
			$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($dateinvoice))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")),'errors');
			}

			if (! ($_POST['fac_replacement'] > 0)) {
				$error ++;
				setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ReplaceInvoice")), 'errors');
			}
			if (!$_POST['cond_reglement_id'])
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired","Condicion de pago",'errors'));
			}

			if (!$_POST['mode_reglement_id']) {
				$error ++;
				setEventMessage($langs->trans("ErrorFieldRequired", "Forma de pago", 'errors'));
			}



			if (! $error) {
				// This is a replacement invoice
				$result = $object->fetch($_POST['fac_replacement']);
				$object->fetch_thirdparty();

				$object->date				= $dateinvoice;
				$object->note_public		= trim($_POST['note_public']);
				$object->note				= trim($_POST['note']);
				$object->ref_client			= $_POST['ref_client'];
				$object->ref_int			= $_POST['ref_int'];
				$object->modelpdf			= $_POST['model'];
				$object->fk_project			= $_POST['projectid'];
				$object->cond_reglement_id	= $_POST['cond_reglement_id'];
				$object->mode_reglement_id	= $_POST['mode_reglement_id'];
	            $object->fk_account         = GETPOST('fk_account', 'int');
				$object->remise_absolue		= $_POST['remise_absolue'];
				$object->remise_percent		= $_POST['remise_percent'];
				$object->fk_incoterms 		= GETPOST('incoterm_id', 'int');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');

				// Proprietes particulieres a facture de remplacement
				$object->fk_facture_source = $_POST['fac_replacement'];
				$object->type = Facture::TYPE_REPLACEMENT;

				$id = $object->createFromCurrent($user);
				if ($id <= 0) {
					setEventMessage($object->error, 'errors');
				}
			}
		}

		// Credit note invoice
		if ($_POST['type'] == Facture::TYPE_CREDIT_NOTE)
		{
			if (! ($_POST['fac_avoir'] > 0))
			{
				$error ++;
				setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CorrectInvoice")), 'errors');
			}

			$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($dateinvoice))
			{
				$error ++;
				setEventMessage($langs->trans("ErrorFieldRequired", $langs->trans("Date")), 'errors');
			}

			if (!$_POST['cond_reglement_id'])
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired","Condicion de pago",'errors'));
			}

			if (!$_POST['mode_reglement_id']) {
				$error ++;
				setEventMessage($langs->trans("ErrorFieldRequired", "Forma de pago", 'errors'));
			}

			if (! $error)
			{
				$object->socid				= GETPOST('socid','int');
				$object->number				= $_POST['facnumber'];
				$object->date				= $dateinvoice;
				$object->note_public		= trim($_POST['note_public']);
				$object->note				= trim($_POST['note']);
				$object->ref_client			= $_POST['ref_client'];
				$object->ref_int			= $_POST['ref_int'];
				$object->modelpdf			= $_POST['model'];
				$object->fk_project			= $_POST['projectid'];
				$object->cond_reglement_id	= 0;
				$object->mode_reglement_id	= $_POST['mode_reglement_id'];
	            $object->fk_account         = GETPOST('fk_account', 'int');
				$object->remise_absolue		= $_POST['remise_absolue'];
				$object->remise_percent		= $_POST['remise_percent'];
				$object->fk_incoterms 		= GETPOST('incoterm_id', 'int');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');

				// Proprietes particulieres a facture avoir
				$object->fk_facture_source = $_POST['fac_avoir'];
				$object->type = Facture::TYPE_CREDIT_NOTE;

				$id = $object->create($user);

				if (GETPOST('invoiceAvoirWithLines', 'int')==1 && $id>0)
				{
	                $facture_source = new Facture($db); // fetch origin object
	                if ($facture_source->fetch($object->fk_facture_source)>0)
	                {

	                    foreach($facture_source->lines as $line)
	                    {
	                        $line->fk_facture = $object->id;

	                        $line->subprice =-$line->subprice; // invert price for object
	                        $line->pa_ht = -$line->pa_ht;
	                        $line->total_ht=-$line->total_ht;
	                        $line->total_tva=-$line->total_tva;
	                        $line->total_ttc=-$line->total_ttc;
	                        $line->total_localtax1=-$line->total_localtax1;
	                        $line->total_localtax2=-$line->total_localtax2;

	                        $line->insert();

	                        $object->lines[] = $line; // insert new line in current object
	                    }

	                    $object->update_price(1);
	                }

				}

	            if(GETPOST('invoiceAvoirWithPaymentRestAmount', 'int')==1 && $id>0)
	            {
	                $facture_source = new Facture($db); // fetch origin object if not previously defined
	                if ($facture_source->fetch($object->fk_facture_source)>0)
	                {
	                    $totalpaye = $facture_source->getSommePaiement();
	                    $totalcreditnotes = $facture_source->getSumCreditNotesUsed();
	                    $totaldeposits = $facture_source->getSumDepositsUsed();
	                    $remain_to_pay = abs($facture_source->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits);

	                    $object->addline($langs->trans('invoiceAvoirLineWithPaymentRestAmount'),$remain_to_pay,1,0,0,0,0,0,'','','TTC');

	                }
	            }

				// Add predefined lines
				/*
	             TODO delete
	             for($i = 1; $i <= $NBLINES; $i ++) {
					if ($_POST['idprod' . $i]) {
						$product = new Product($db);
						$product->fetch($_POST['idprod' . $i]);
						$startday = dol_mktime(12, 0, 0, $_POST['date_start' . $i . 'month'], $_POST['date_start' . $i . 'day'], $_POST['date_start' . $i . 'year']);
						$endday = dol_mktime(12, 0, 0, $_POST['date_end' . $i . 'month'], $_POST['date_end' . $i . 'day'], $_POST['date_end' . $i . 'year']);
						$result = $object->addline($product->description, $product->price, $_POST['qty' . $i], $product->tva_tx, $product->localtax1_tx, $product->localtax2_tx, $_POST['idprod' . $i], $_POST['remise_percent' . $i], $startday, $endday, 0, 0, '', $product->price_base_type, $product->price_ttc, $product->type);
					}
				}*/
			}
		}

		// Standard invoice or Deposit invoice created from a Predefined invoice
		if (($_POST['type'] == Facture::TYPE_STANDARD || $_POST['type'] == Facture::TYPE_DEPOSIT) && $_POST['fac_rec'] > 0)
		{
			$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($dateinvoice))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")),'errors');
			}
			if (!$_POST['cond_reglement_id'])
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired","Condicion de pago",'errors'));
			}

			if (!$_POST['mode_reglement_id']) {
				$error ++;
				setEventMessage($langs->trans("ErrorFieldRequired", "Forma de pago", 'errors'));
			}

			if (! $error)
			{
				$object->socid			= GETPOST('socid','int');
				$object->type           = $_POST['type'];
				$object->number         = $_POST['facnumber'];
				$object->date           = $dateinvoice;
				$object->note_public	= trim($_POST['note_public']);
				$object->note_private   = trim($_POST['note_private']);
				$object->ref_client     = $_POST['ref_client'];
				$object->ref_int     	= $_POST['ref_int'];
				$object->modelpdf       = $_POST['model'];

				// Source facture
				$object->fac_rec = $_POST['fac_rec'];

				$id = $object->create($user);
			}
		}

		// Standard or deposit or proforma invoice
		if (($_POST['type'] == Facture::TYPE_STANDARD || $_POST['type'] == Facture::TYPE_DEPOSIT || $_POST['type'] == Facture::TYPE_PROFORMA || ($_POST['type'] == Facture::TYPE_SITUATION && empty($_POST['situations']))) && $_POST['fac_rec'] <= 0)
		{
			if (GETPOST('socid', 'int') < 1)
			{
				$error ++;
				setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Customer")), 'errors');
			}

			$dateinvoice = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($dateinvoice))
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")),'errors');
			}
			if (!$_POST['cond_reglement_id'])
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired","Condicion de pago",'errors'));
			}

			if (!$_POST['mode_reglement_id']) {
				$error ++;
				setEventMessage($langs->trans("ErrorFieldRequired", "Forma de pago", 'errors'));
			}

			if (! $error)
			{
				// Si facture standard
				$object->socid				= GETPOST('socid','int');
				$object->type				= GETPOST('type');
				$object->number				= $_POST['facnumber'];
				$object->date				= $dateinvoice;
				$object->note_public		= trim($_POST['note_public']);
				$object->note_private		= trim($_POST['note_private']);
				$object->ref_client			= $_POST['ref_client'];
				$object->ref_int			= $_POST['ref_int'];
				$object->modelpdf			= $_POST['model'];
				$object->fk_project			= $_POST['projectid'];
				$object->cond_reglement_id	= ($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
				$object->mode_reglement_id	= $_POST['mode_reglement_id'];
	            $object->fk_account         = GETPOST('fk_account', 'int');
				$object->amount				= $_POST['amount'];
				$object->remise_absolue		= $_POST['remise_absolue'];
				$object->remise_percent		= $_POST['remise_percent'];
				$object->fk_incoterms 		= GETPOST('incoterm_id', 'int');
				$object->location_incoterms = GETPOST('location_incoterms', 'alpha');

				if (GETPOST('type') == Facture::TYPE_SITUATION)
				{
					$object->situation_counter = 1;
					$object->situation_final = 0;
					$object->situation_cycle_ref = $object->newCycle();
				}

				$object->fetch_thirdparty();

				// If creation from another object of another module (Example: origin=propal, originid=1)
				if ($origin && $originid)
				{
					// Parse element/subelement (ex: project_task)
					$element = $subelement = $origin;
					if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
						$element = $regs [1];
						$subelement = $regs [2];
					}

					// For compatibility
					if ($element == 'order') {
						$element = $subelement = 'commande';
					}
					if ($element == 'propal') {
						$element = 'comm/propal';
						$subelement = 'propal';
					}
					if ($element == 'contract') {
						$element = $subelement = 'contrat';
					}
					if ($element == 'inter') {
						$element = $subelement = 'ficheinter';
					}
					if ($element == 'shipping') {
						$element = $subelement = 'expedition';
					}

					$object->origin = $origin;
					$object->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$object->linked_objects[$object->origin] = $object->origin_id;
					// link with order if it is a shipping invoice
					if ($object->origin == 'shipping')
					{
						require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
						$exp = new Expedition($db);
						$exp->fetch($object->origin_id);
						$exp->fetchObjectLinked();
						if (count($exp->linkedObjectsIds['commande']) > 0) {
							foreach ($exp->linkedObjectsIds['commande'] as $key => $value){
								$object->linked_objects['commande'] = $value;
							}
						}
					}

					if (is_array($_POST['other_linked_objects']) && ! empty($_POST['other_linked_objects']))
					{
						$object->linked_objects = array_merge($object->linked_objects, $_POST['other_linked_objects']);
					}

					$id = $object->create($user);

					if ($id > 0)
					{
						// If deposit invoice
						if ($_POST['type'] == Facture::TYPE_DEPOSIT)
						{
							$typeamount = GETPOST('typedeposit', 'alpha');
							$valuedeposit = GETPOST('valuedeposit', 'int');

							if ($typeamount == 'amount')
							{
								$amountdeposit = $valuedeposit;
							}
							else
							{
								$amountdeposit = 0;

								dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

								$classname = ucfirst($subelement);
								$srcobject = new $classname($db);

								dol_syslog("Try to find source object origin=" . $object->origin . " originid=" . $object->origin_id . " to add deposit lines");
								$result = $srcobject->fetch($object->origin_id);
								if ($result > 0)
								{
									$totalamount = 0;
									$lines = $srcobject->lines;
									$numlines=count($lines);
									for ($i=0; $i<$numlines; $i++)
									{
										$qualified=1;
										if (empty($lines[$i]->qty)) $qualified=0;	// We discard qty=0, it is an option
										if (! empty($lines[$i]->special_code)) $qualified=0;	// We discard special_code (frais port, ecotaxe, option, ...)
										if ($qualified) $totalamount += $lines[$i]->total_ht;
									}

									if ($totalamount != 0) {
										$amountdeposit = ($totalamount * $valuedeposit) / 100;
									}
								} else {
									setEventMessage($srcobject->error, 'errors');
									$error ++;
								}
							}

							$result = $object->addline(
									$langs->trans('Deposit'),
									$amountdeposit,		 	// subprice
									1, 						// quantity
									$lines[$i]->tva_tx, 0, // localtax1_tx
									0, 						// localtax2_tx
									0, 						// fk_product
									0, 						// remise_percent
									0, 						// date_start
									0, 						// date_end
									0, $lines[$i]->info_bits, // info_bits
									0, 						// info_bits
									'HT',
									0,
									0, 						// product_type
									1,
									$lines[$i]->special_code,
									$object->origin,
									0,
									0,
									0,
									0,
									$langs->trans('Deposit')
								);
						}
						else
						{

							dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

							$classname = ucfirst($subelement);
							$srcobject = new $classname($db);

							dol_syslog("Try to find source object origin=" . $object->origin . " originid=" . $object->origin_id . " to add lines");
							$result = $srcobject->fetch($object->origin_id);
							if ($result > 0)
							{
								$lines = $srcobject->lines;
								if (empty($lines) && method_exists($srcobject, 'fetch_lines'))
								{
									$srcobject->fetch_lines();
									$lines = $srcobject->lines;
								}

								$fk_parent_line=0;
								$num=count($lines);
								for ($i=0;$i<$num;$i++)
								{
									// Don't add lines with qty 0 when coming from a shipment including all order lines
									if($srcobject->element == 'shipping' && $conf->global->SHIPMENT_GETS_ALL_ORDER_PRODUCTS && $lines[$i]->qty == 0) continue;

									$label=(! empty($lines[$i]->label)?$lines[$i]->label:'');
									$desc=(! empty($lines[$i]->desc)?$lines[$i]->desc:$lines[$i]->libelle);
									if ($object->situation_counter == 1) $lines[$i]->situation_percent =  0;

									if ($lines[$i]->subprice < 0)
									{
										// Negative line, we create a discount line
										$discount = new DiscountAbsolute($db);
										$discount->fk_soc = $object->socid;
										$discount->amount_ht = abs($lines[$i]->total_ht);
										$discount->amount_tva = abs($lines[$i]->total_tva);
										$discount->amount_ttc = abs($lines[$i]->total_ttc);
										$discount->tva_tx = $lines[$i]->tva_tx;
										$discount->fk_user = $user->id;
										$discount->description = $desc;
										$discountid = $discount->create($user);
										if ($discountid > 0) {
											$result = $object->insert_discount($discountid); // This include link_to_invoice
										} else {
											setEventMessage($discount->error, 'errors');
											$error ++;
											break;
										}
									} else {
										// Positive line
										$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

										// Date start
										$date_start = false;
										if ($lines[$i]->date_debut_prevue)
											$date_start = $lines[$i]->date_debut_prevue;
										if ($lines[$i]->date_debut_reel)
											$date_start = $lines[$i]->date_debut_reel;
										if ($lines[$i]->date_start)
											$date_start = $lines[$i]->date_start;

											// Date end
										$date_end = false;
										if ($lines[$i]->date_fin_prevue)
											$date_end = $lines[$i]->date_fin_prevue;
										if ($lines[$i]->date_fin_reel)
											$date_end = $lines[$i]->date_fin_reel;
										if ($lines[$i]->date_end)
											$date_end = $lines[$i]->date_end;

											// Reset fk_parent_line for no child products and special product
										if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
											$fk_parent_line = 0;
										}

										// Extrafields
										if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
											$lines[$i]->fetch_optionals($lines[$i]->rowid);
											$array_options = $lines[$i]->array_options;
										}

										// View third's localtaxes for now
										$localtax1_tx = get_localtax($lines[$i]->tva_tx, 1, $object->client);
										$localtax2_tx = get_localtax($lines[$i]->tva_tx, 2, $object->client);

										$result = $object->addline($desc, $lines[$i]->subprice, $lines[$i]->qty, $lines[$i]->tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product, $lines[$i]->remise_percent, $date_start, $date_end, 0, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $object->origin, $lines[$i]->rowid, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_options, $lines[$i]->situation_percent, $lines[$i]->fk_prev_id, $lines[$i]->fk_unit);

										if ($result > 0) {
											$lineid = $result;
										} else {
											$lineid = 0;
											$error ++;
											break;
										}

										// Defined the new fk_parent_line
										if ($result > 0 && $lines[$i]->product_type == 9) {
											$fk_parent_line = $result;
										}
									}
								}

								// Hooks
								$parameters = array('objFrom' => $srcobject);
								$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been
								                                                                               // modified by hook
								if ($reshook < 0)
									$error ++;
							} else {
								setEventMessage($srcobject->error, 'errors');
								$error ++;
							}
						}
					} else {
						setEventMessage($object->error, 'errors');
						$error ++;
					}
				} 			// If some invoice's lines already known
				else {
					$id = $object->create($user);

					for($i = 1; $i <= $NBLINES; $i ++) {
						if ($_POST['idprod' . $i]) {
							$product = new Product($db);
							$product->fetch($_POST['idprod' . $i]);
							$startday = dol_mktime(12, 0, 0, $_POST['date_start' . $i . 'month'], $_POST['date_start' . $i . 'day'], $_POST['date_start' . $i . 'year']);
							$endday = dol_mktime(12, 0, 0, $_POST['date_end' . $i . 'month'], $_POST['date_end' . $i . 'day'], $_POST['date_end' . $i . 'year']);
							$result = $object->addline($product->description, $product->price, $_POST['qty' . $i], $product->tva_tx, $product->localtax1_tx, $product->localtax2_tx, $_POST['idprod' . $i], $_POST['remise_percent' . $i], $startday, $endday, 0, 0, '', $product->price_base_type, $product->price_ttc, $product->type, -1, 0, '', 0, 0, null, 0, '', 0, 100, '', $product->fk_unit);
						}
					}
				}
			}
		}

		if (GETPOST('type') == Facture::TYPE_SITUATION && (!empty($_POST['situations'])))
		{
			$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($datefacture)) {
				$error++;
				$mesg = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("Date")) . '</div>';
			}

			if (!($_POST['situations'] > 0)) {
				$error++;
				$mesg = '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->trans("InvoiceSituation")) . '</div>';
			}
			if (!$_POST['cond_reglement_id'])
			{
				$error++;
				setEventMessage($langs->trans("ErrorFieldRequired","Condicion de pago",'errors'));
			}

			if (!$_POST['mode_reglement_id']) {
				$error ++;
				setEventMessage($langs->trans("ErrorFieldRequired", "Forma de pago", 'errors'));
			}

			if (!$error) {
				$result = $object->fetch($_POST['situations']);
				$object->fk_facture_source = $_POST['situations'];
				$object->type = Facture::TYPE_SITUATION;

				$object->fetch_thirdparty();
				$object->date = $datefacture;
				$object->note_public = trim($_POST['note_public']);
				$object->note = trim($_POST['note']);
				$object->ref_client = $_POST['ref_client'];
				$object->ref_int = $_POST['ref_int'];
				$object->modelpdf = $_POST['model'];
				$object->fk_project = $_POST['projectid'];
				$object->cond_reglement_id = $_POST['cond_reglement_id'];
				$object->mode_reglement_id = $_POST['mode_reglement_id'];
				$object->remise_absolue = $_POST['remise_absolue'];
				$object->remise_percent = $_POST['remise_percent'];

				// Proprietes particulieres a facture de remplacement

				$object->situation_counter = $object->situation_counter + 1;
				$id = $object->createFromCurrent($user);
				if ($id <= 0) $mesg = $object->error;
			}
		}

		// End of object creation, we show it
		if ($id > 0 && ! $error)
		{
			$db->commit();
			if($isTicket) {
				header('Location: ' . $_SERVER["PHP_SELF"] . '?&isTicket=1&facid=' . $id);
			} else {
				header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id);
			}
			exit();
		}
		else
		{
			$db->rollback();
			$action = 'create';
			$_GET["origin"] = $_POST["origin"];
			$_GET["originid"] = $_POST["originid"];
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Add a new line
	else if ($action == 'addline' && $user->rights->facture->creer)
	{
		$langs->load('errors');
		$error = 0;

		// Set if we used free entry or predefined product
		$predef='';
		$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
		$price_ht = GETPOST('price_ht');
		if (GETPOST('prod_entry_mode') == 'free')
		{
			$idprod=0;
			$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		}
		else
		{
			$idprod=GETPOST('idprod', 'int');
			$tva_tx = '';
		}

		$qty = GETPOST('qty' . $predef);
		$remise_percent = GETPOST('remise_percent' . $predef);

		// Extrafields
		$extrafieldsline = new ExtraFields($db);
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);

		// Validate required extrafields on invoice line
		$extrafields_passed_required = ExtraFields::validateRequired($array_options, $extrafieldsline->attribute_required);
		if (!$extrafields_passed_required) {
			setEventMessage($langs->trans('ErrorSomeRequiredExtrafieldsAreEmpty'), 'errors');
			$error ++;
		}

		if (empty($idprod) && ($price_ht < 0) && ($qty < 0)) {
			setEventMessage($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), 'errors');
			$error ++;
		}
		if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && GETPOST('type') < 0) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), 'errors');
			$error ++;
		}
		if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && (! ($price_ht >= 0) || $price_ht == '')) 	// Unit price can be 0 but not ''
		{
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), 'errors');
			$error ++;
		}
		if ($qty == '') {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), 'errors');
			$error ++;
		}
		if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && empty($product_desc)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), 'errors');
			$error ++;
		}
		if ($qty < 0) {
			$langs->load("errors");
			setEventMessage($langs->trans('ErrorQtyForCustomerInvoiceCantBeNegative'), 'errors');
			$error ++;
		}
		if (! $error && ($qty >= 0) && (! empty($product_desc) || ! empty($idprod))) {
			$ret = $object->fetch($id);
			if ($ret < 0) {
				dol_print_error($db, $object->error);
				exit();
			}
			$ret = $object->fetch_thirdparty();

			// Clean parameters
			$date_start = dol_mktime(GETPOST('date_start' . $predef . 'hour'), GETPOST('date_start' . $predef . 'min'), GETPOST('date_start' . $predef . 'sec'), GETPOST('date_start' . $predef . 'month'), GETPOST('date_start' . $predef . 'day'), GETPOST('date_start' . $predef . 'year'));
			$date_end = dol_mktime(GETPOST('date_end' . $predef . 'hour'), GETPOST('date_end' . $predef . 'min'), GETPOST('date_end' . $predef . 'sec'), GETPOST('date_end' . $predef . 'month'), GETPOST('date_end' . $predef . 'day'), GETPOST('date_end' . $predef . 'year'));
			$price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

			// Define special_code for special lines
			$special_code = 0;
			// if (empty($_POST['qty'])) $special_code=3; // Options should not exists on invoices

			// Ecrase $pu par celui du produit
			// Ecrase $desc par celui du produit
			// Ecrase $txtva par celui du produit
			// Ecrase $base_price_type par celui du produit
			// Replaces $fk_unit with the product's
			if (! empty($idprod)) {
				$prod = new Product($db);
				$prod->fetch($idprod);

				$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

				// Update if prices fields are defined
					$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
					$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
					$pu_ht = $prod->price;
					$pu_ttc = $prod->price_ttc;
					$price_min = $prod->price_min;
					$price_base_type = $prod->price_base_type;

				// multiprix

					$kg_mayoreo = $prod->array_options['options_kg_mayoreo'];

					// We define price for product
					if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->thirdparty->price_level))
					{

						// Pay attention to these flags
						define('MXN_INDEX', '1');
						define('USD_INDEX', '3');

						if ($object->array_options['options_currency'] == 'MXN') {
							$currency_index = MXN_INDEX;
						} else if ($object->array_options['options_currency'] == 'USD') {
							$currency_index = USD_INDEX;
						}

						if( $kg_mayoreo != '' && $kg_mayoreo > 0 && $qty >= $kg_mayoreo)
						{

							if ($conf->global->MULTI_CURRENCY) {

								$pu_ht = $prod->multiprices[$currency_index + 1];
								$pu_ttc = $prod->multiprices_ttc[$currency_index + 1];
								$price_min = $prod->multiprices_min[$currency_index + 1];
								$price_base_type = $prod->multiprices_base_type[$currency_index + 1];
								if (isset($prod->multiprices_tva_tx[$object->client->price_level])) $tva_tx=$prod->multiprices_tva_tx[$object->client->price_level];
								if (isset($prod->multiprices_recuperableonly[$object->client->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$object->client->price_level];
								$tva_tx=$prod->multiprices_tva_tx[$currency_index + 1];
								$tva_npr=$prod->multiprices_recuperableonly[$currency_index + 1];

							} else {

								$pu_ht = $prod->multiprices[2];
								$pu_ttc = $prod->multiprices_ttc[2];
								$price_min = $prod->multiprices_min[2];
								$price_base_type = $prod->multiprices_base_type[2];
								if (isset($prod->multiprices_tva_tx[$object->client->price_level])) $tva_tx=$prod->multiprices_tva_tx[$object->client->price_level];
								if (isset($prod->multiprices_recuperableonly[$object->client->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$object->client->price_level];
								$tva_tx=$prod->multiprices_tva_tx[2];
								$tva_npr=$prod->multiprices_recuperableonly[2];

							}

						}
						else
						{
							if ($conf->global->MULTI_CURRENCY) {

								$pu_ht = $prod->multiprices[$currency_index];
								$pu_ttc = $prod->multiprices_ttc[$currency_index];
								$price_min = $prod->multiprices_min[$currency_index];
								$price_base_type = $prod->multiprices_base_type[$currency_index];
								if (isset($prod->multiprices_tva_tx[$object->client->price_level])) $tva_tx=$prod->multiprices_tva_tx[$object->client->price_level];
								if (isset($prod->multiprices_recuperableonly[$object->client->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$object->client->price_level];
								$tva_tx=$prod->multiprices_tva_tx[$currency_index];
								$tva_npr=$prod->multiprices_recuperableonly[$currency_index];

							} else {

								$pu_ht = $prod->multiprices[$object->thirdparty->price_level];
								$pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
								$price_min = $prod->multiprices_min[$object->thirdparty->price_level];
								$price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
								if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx=$prod->multiprices_tva_tx[$object->thirdparty->price_level];
								if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$object->thirdparty->price_level];
								$tva_tx=$prod->multiprices_tva_tx[$object->thirdparty->price_level];
								$tva_npr=$prod->multiprices_recuperableonly[$object->thirdparty->price_level];

							}

						}
					}
					elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
					{
						require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

						$prodcustprice = new Productcustomerprice($db);

						$filter = array('t.fk_product' => $prod->id,'t.fk_soc' => $object->thirdparty->id);

						$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
						if ($result) {
							if (count($prodcustprice->lines) > 0) {
								$pu_ht = price($prodcustprice->lines [0]->price);
								$pu_ttc = price($prodcustprice->lines [0]->price_ttc);
								$price_base_type = $prodcustprice->lines [0]->price_base_type;
								$prod->tva_tx = $prodcustprice->lines [0]->tva_tx;
							}
						}
					}

					// if price ht is forced (ie: calculated by margin rate and cost price)
					if (! empty($price_ht)) {
						$pu_ht = price2num($price_ht, 'MU');
						$pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
					}

					// On reevalue prix selon taux tva car taux tva transaction peut etre different
					// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
					elseif ($tva_tx != $prod->tva_tx) {
						if ($price_base_type != 'HT') {
							$pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU');
						} else {
							$pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
						}
					}

					$desc = '';

					// Define output language
					if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
						$outputlangs = $langs;
						$newlang = '';
						if (empty($newlang) && GETPOST('lang_id'))
							$newlang = GETPOST('lang_id');
						if (empty($newlang))
							$newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}

						$desc = (! empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["Description"] : $prod->description;
					} else {
						$desc = $prod->description;
					}

					$desc = dol_concatdesc($desc, $product_desc);

					// Add custom code and origin country into description
					if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (! empty($prod->customcode) || ! empty($prod->country_code))) {
						$tmptxt = '(';
						if (! empty($prod->customcode))
							$tmptxt .= $langs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
						if (! empty($prod->customcode) && ! empty($prod->country_code))
							$tmptxt .= ' - ';
						if (! empty($prod->country_code))
							$tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $langs, 0);
						$tmptxt .= ')';
						$desc = dol_concatdesc($desc, $tmptxt);
					}

				$type = $prod->type;
				$fk_unit = $prod->fk_unit;
			} else {
				$pu_ht = price2num($price_ht, 'MU');
				$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
				$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
				$tva_tx = str_replace('*', '', $tva_tx);
				$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
				$desc = $product_desc;
				$type = GETPOST('type');
				$fk_unit= GETPOST('units', 'alpha');
			}

			// Margin
			$fournprice = price2num(GETPOST('fournprice' . $predef) ? GETPOST('fournprice' . $predef) : '');
			$buyingprice = price2num(GETPOST('buying_price' . $predef) ? GETPOST('buying_price' . $predef) : '');

			// Local Taxes
			$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
			$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

			$info_bits = 0;
			if ($tva_npr)
				$info_bits |= 0x01;

			if (! empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min))) {
				$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency));
				setEventMessage($mesg, 'errors');
			} else {
				// Insert line
				$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $idprod, $remise_percent, $date_start, $date_end, 0, $info_bits, '', $price_base_type, $pu_ttc, $type, - 1, $special_code, '', 0, GETPOST('fk_parent_line'), $fournprice, $buyingprice, $label, $array_options, $_POST['progress'], '', $fk_unit);

				if ($result > 0)
				{
					// Define output language
					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
					{
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
						if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}
						$model=$object->modelpdf;
						$ret = $object->fetch($id); // Reload to get new records

						$document_mode = 'F';
						$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref, $document_mode);
						$result = 1;

						if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
					}

					unset($_POST['prod_entry_mode']);

					unset($_POST['qty']);
					unset($_POST['type']);
					unset($_POST['remise_percent']);
					unset($_POST['price_ht']);
					unset($_POST['price_ttc']);
					unset($_POST['tva_tx']);
					unset($_POST['product_ref']);
					unset($_POST['product_label']);
					unset($_POST['product_desc']);
					unset($_POST['fournprice']);
					unset($_POST['buying_price']);
					unset($_POST['np_marginRate']);
					unset($_POST['np_markRate']);
					unset($_POST['dp_desc']);
					unset($_POST['idprod']);
					unset($_POST['units']);

					unset($_POST['date_starthour']);
					unset($_POST['date_startmin']);
					unset($_POST['date_startsec']);
					unset($_POST['date_startday']);
					unset($_POST['date_startmonth']);
					unset($_POST['date_startyear']);
					unset($_POST['date_endhour']);
					unset($_POST['date_endmin']);
					unset($_POST['date_endsec']);
					unset($_POST['date_endday']);
					unset($_POST['date_endmonth']);
					unset($_POST['date_endyear']);

					unset($_POST['situations']);
					unset($_POST['progress']);

					// Unset extrafield
					if (is_array($extralabelsline)) {
						// Get extra fields
						foreach ($extralabelsline as $key => $value) {
							unset($_POST["options_" . $key . $predef]);
						}
					}
				} else {
					setEventMessage($object->error, 'errors');
				}

				$action = '';
			}
		}
	}

	elseif ($action == 'updateligne' && $user->rights->facture->creer && ! GETPOST('cancel'))
	{
		if (! $object->fetch($id) > 0)	dol_print_error($db);
		$object->fetch_thirdparty();

		// Clean parameters
		$date_start = '';
		$date_end = '';
		$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
		$description = dol_htmlcleanlastbr(GETPOST('product_desc'));
		$pu_ht = GETPOST('price_ht');
		$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		$qty = GETPOST('qty');

		// Define info_bits
		$info_bits = 0;
		if (preg_match('/\*/', $vat_rate))
			$info_bits |= 0x01;

		// Define vat_rate
		$vat_rate = str_replace('*', '', $vat_rate);
		$localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty);
		$localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty);

		// Add buying price
		$fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
		$buyingprice = price2num(GETPOST('buying_price') ? GETPOST('buying_price') : '');

		// Extrafields
		$extrafieldsline = new ExtraFields($db);
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_" . $key]);
			}
		}

		// Define special_code for special lines
		$special_code=GETPOST('special_code');
		if (! GETPOST('qty')) $special_code=3;

		$line = new FactureLigne($db);
		$line->fetch(GETPOST('lineid'));
		$percent = $line->get_prev_progress();

		if (GETPOST('progress') < $percent)
		{
			$mesg = '<div class="warning">' . $langs->trans("CantBeLessThanMinPercent") . '</div>';
			setEventMessages($mesg, null, 'warnings');
			$error++;
			$result = -1;
		}

		// Check minimum price
		$productid = GETPOST('productid', 'int');
		if (! empty($productid))
		{
			$product = new Product($db);
			$product->fetch($productid);

			$type = $product->type;

			$price_min = $product->price_min;
			if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->thirdparty->price_level))
				$price_min = $product->multiprices_min [$object->thirdparty->price_level];

			$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

			// Check price is not lower than minimum (check is done only for standard or replacement invoices)
			if (($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT) && $price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min))) {
				setEventMessage($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency)), 'errors');
				$error ++;
			}
		} else {
			$type = GETPOST('type');
			$label = (GETPOST('product_label') ? GETPOST('product_label') : '');

			// Check parameters
			if (GETPOST('type') < 0) {
				setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), 'errors');
				$error ++;
			}
		}
		if ($qty < 0) {
			$langs->load("errors");
			setEventMessage($langs->trans('ErrorQtyForCustomerInvoiceCantBeNegative'), 'errors');
			$error ++;
		}

		// Update line
		if (! $error) {
			$result = $object->updateline(GETPOST('lineid'), $description, $pu_ht, $qty, GETPOST('remise_percent'),
				$date_start, $date_end, $vat_rate, $localtax1_rate, $localtax2_rate, 'HT', $info_bits, $type,
				GETPOST('fk_parent_line'), 0, $fournprice, $buyingprice, $label, $special_code, $array_options, GETPOST('progress'),
				$_POST['units']);

			if ($result >= 0) {
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					// Define output language
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id'))
						$newlang = GETPOST('lang_id');
					if ($conf->global->MAIN_MULTILANGS && empty($newlang))
						$newlang = $object->thirdparty->default_lang;
					if (! empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$ret = $object->fetch($id); // Reload to get new records

					$document_mode = 'F';
					$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $document_mode);
				}

				unset($_POST['qty']);
				unset($_POST['type']);
				unset($_POST['productid']);
				unset($_POST['remise_percent']);
				unset($_POST['price_ht']);
				unset($_POST['price_ttc']);
				unset($_POST['tva_tx']);
				unset($_POST['product_ref']);
				unset($_POST['product_label']);
				unset($_POST['product_desc']);
				unset($_POST['fournprice']);
				unset($_POST['buying_price']);
				unset($_POST['np_marginRate']);
				unset($_POST['np_markRate']);

				unset($_POST['dp_desc']);
				unset($_POST['idprod']);
				unset($_POST['units']);

		    	unset($_POST['date_starthour']);
		    	unset($_POST['date_startmin']);
		    	unset($_POST['date_startsec']);
		    	unset($_POST['date_startday']);
		    	unset($_POST['date_startmonth']);
		    	unset($_POST['date_startyear']);
		    	unset($_POST['date_endhour']);
		    	unset($_POST['date_endmin']);
		    	unset($_POST['date_endsec']);
		    	unset($_POST['date_endday']);
		    	unset($_POST['date_endmonth']);
		    	unset($_POST['date_endyear']);

				unset($_POST['situations']);
				unset($_POST['progress']);
			} else {
				setEventMessage($object->error, 'errors');
			}
		}
	}

	else if ($action == 'updatealllines' && $user->rights->facture->creer && $_POST['all_percent'] == $langs->trans('Modifier'))
	{
		if (!$object->fetch($id) > 0) dol_print_error($db);
		if (!is_null(GETPOST('all_progress')) && GETPOST('all_progress') != "")
		{
			foreach ($object->lines as $line)
			{
				$percent = $line->get_prev_progress();
				if (GETPOST('all_progress') < $percent) {
					$mesg = '<div class="warning">' . $langs->trans("CantBeLessThanMinPercent") . '</div>';
					$result = -1;
				} else
					$object->update_percent($line, $_POST['all_progress']);
			}
		}
	}

	else if ($action == 'updateligne' && $user->rights->facture->creer && $_POST['cancel'] == $langs->trans('Cancel')) {
		if($isTicket) {
			header('Location: ' . $_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $id); // Pour reaffichage de la fiche en cours d'edition
		} else {
			header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $id); // Pour reaffichage de la fiche en cours d'edition
		}
		exit();
	}

	// Link invoice to order
	if (GETPOST('linkedOrder')) {
		$object->fetch($id);
		$object->fetch_thirdparty();
		$result = $object->add_object_linked('commande', GETPOST('linkedOrder'));
	}


	/*
	 * Send mail
	 */

	// Actions to send emails
	if (empty($id)) $id=$facid;
	$actiontypecode='AC_FAC';
	$trigger_name='BILL_SENTBYMAIL';
	$paramname='id';
	$mode='emailfrominvoice';
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


	/*
	* Generate document
	*/
	if ($action == 'builddoc') // En get ou en post
	{
		echo 'yui';
		$object->fetch($id);
		$object->fetch_thirdparty();

		// Save last template used to generate document
		if (GETPOST('model'))
			$object->setDocModel($user, GETPOST('model', 'alpha'));
        if (GETPOST('fk_bank')) { // this field may come from an external module
            $object->fk_bank = GETPOST('fk_bank');
        } else {
            $object->fk_bank = $object->fk_account;
        }

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang = $object->thirdparty->default_lang;
		if (! empty($newlang))
		{
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		$document_mode = 'F';
		$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $document_mode);

		if ($result <= 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
	        $action='';
		}
	}

	// Remove file in doc form
	else if ($action == 'remove_file') {
		if ($object->fetch($id)) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

			$object->fetch_thirdparty();

			$langs->load("other");
			$upload_dir = $conf->facture->dir_output;
			$file = $upload_dir . '/' . GETPOST('file');
			$ret = dol_delete_file($file, 0, 0, 0, $object);
			if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
			else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
			$action = '';
		}
	}

	else if ($action == 'update_extras') {

		// Fill array 'array_options' with data from add form

		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
		if ($ret < 0) $error++;

		if (! $error) {
			// Actions on extra fields (by external module or standard code)
			// TODO le hook fait double emploi avec le trigger !!
			$hookmanager->initHooks(array('invoicedao'));
			$parameters = array('id' => $object->id);

			$reshook = $hookmanager->executeHooks('insertExtraFields', $parameters, $object, $action); // Note that $action and $object may have been modified by
																																														// some hooks
			if (empty($reshook)) {
						$result = $object->insertExtraFields();
					if ($result < 0) {
						$error ++;
					}
				} else if ($reshook < 0)
					$error ++;
			}

			if ($error)
				$action = 'edit_extras';
		}
	}


	if ($action == 'update_extras_fixed') {

		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
		if ($ret < 0) $error++;

		if (! $error) {

			$res = $object->update_extrafields();

			if ($res < 0) $error ++;

			if ($error) $action = 'edit_extras';

		}
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->facture->creer)
	{
		if ($action == 'addcontact')
		{
			$result = $object->fetch($id);

			if ($result > 0 && $id > 0) {
				$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
				$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
			}

		if ($result >= 0) {
			if($isTicket) {
				header("Location: " . $_SERVER['PHP_SELF'] . "?isTicket=1&id=" . $object->id);
				exit();
			} else {
				header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
				exit();
			}

		} else {
			if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$langs->load("errors");
				setEventMessage($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), 'errors');
			} else {
				setEventMessage($object->error, 'errors');
			}
		}
	}

	// bascule du statut d'un contact
	else if ($action == 'swapstatut')
	{
		if ($object->fetch($id)) {
			$result = $object->swapContactStatus(GETPOST('ligne'));
		} else {
			dol_print_error($db);
			}
	}

	// Efface un contact
	else if ($action == 'deletecontact')
	{
		$object->fetch($id);
		$result = $object->delete_contact($lineid);

		if ($result >= 0) {
			if($isTicket) {
				header("Location: " . $_SERVER['PHP_SELF'] . "?isTicket=1&id=" . $object->id);
			} else {
				header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
			}
			exit();
		} else {
			dol_print_error($db);
		}
	}

}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$bankaccountstatic = new Account($db);
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$now = dol_now();

llxHeader('', $langs->trans('Bill'), 'EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes');



/**
 * *******************************************************************
 *
 * Mode creation
 *
 * ********************************************************************
 */

if ($action == 'create')
{

	$facturestatic = new Facture($db);
	$extralabels = $extrafields->fetch_name_optionals_label($facturestatic->table_element);

	if($isTicket == "true") {
		print_fiche_titre("Nuevo Ticket");
	}
	else {
		print_fiche_titre($langs->trans('NewBill'));
	}

	$soc = new Societe($db);
	if ($socid > 0)
		$res = $soc->fetch($socid);

	// Load objectsrc
	if (! empty($origin) && ! empty($originid))
	{
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
			$element = $regs [1];
			$subelement = $regs [2];
		}

		if ($element == 'project') {
			$projectid = $originid;
		} else {
			// For compatibility
			if ($element == 'order' || $element == 'commande') {
				$element = $subelement = 'commande';
			}
			if ($element == 'propal') {
				$element = 'comm/propal';
				$subelement = 'propal';
			}
			if ($element == 'contract') {
				$element = $subelement = 'contrat';
			}
			if ($element == 'shipping') {
				$element = $subelement = 'expedition';
			}

			dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

			$classname = ucfirst($subelement);
			$objectsrc = new $classname($db);
			$objectsrc->fetch($originid);
			if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines'))
				$objectsrc->fetch_lines();
			$objectsrc->fetch_thirdparty();

			$projectid = (! empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');
			$ref_client = (! empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');
			$ref_int = (! empty($objectsrc->ref_int) ? $objectsrc->ref_int : '');

			// only if socid not filled else it's allready done upper
			if (empty($socid))
				$soc = $objectsrc->thirdparty;

			$cond_reglement_id 	= (! empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(! empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
			$mode_reglement_id 	= (! empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(! empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
            $fk_account         = (! empty($objectsrc->fk_account)?$objectsrc->fk_account:(! empty($soc->fk_account)?$soc->fk_account:0));
			$remise_percent 	= (! empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(! empty($soc->remise_percent)?$soc->remise_percent:0));
			$remise_absolue 	= (! empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(! empty($soc->remise_absolue)?$soc->remise_absolue:0));
			$dateinvoice		= (empty($dateinvoice)?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:''):$dateinvoice);

			// Replicate extrafields
			$objectsrc->fetch_optionals($originid);
			$object->array_options = $objectsrc->array_options;
		}
		$dateinvoice = empty($conf->global->MAIN_AUTOFILL_DATE) ? -1 : '';	// Dot not set 0 here (0 for a date is 1970)
	}
	else
	{
		$cond_reglement_id 	= $soc->cond_reglement_id;
		$mode_reglement_id 	= $soc->mode_reglement_id;
		$fk_account        	= $soc->fk_account;
		$remise_percent 	= $soc->remise_percent;
		$remise_absolue 	= 0;
		$object->array_options['options_vendor'] = $soc->array_options['options_vendor'];
		$dateinvoice		= (empty($dateinvoice)?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:''):$dateinvoice);		// Do not set 0 here (0 for a date is 1970)
	}
	$absolute_discount = $soc->getAvailableDiscounts();

	if (! empty($conf->use_javascript_ajax))
	{
		require_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
		print ajax_combobox('fac_replacement');
		print ajax_combobox('fac_avoir');
		print ajax_combobox('situations');

	}

	print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	if($isTicket) {
		print '<input type="hidden" name="isTicket" value="true">';
		print '<input type="hidden" class="flat" name="options_isticket" value="1">';
	}
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';
	if ($soc->id > 0) print '<input type="hidden" name="socid" value="' . $soc->id . '">' . "\n";
	print '<input name="facnumber" type="hidden" value="provisoire">';
	print '<input name="ref_client" type="hidden" value="' . $ref_client . '">';
	print '<input name="ref_int" type="hidden" value="' . $ref_int . '">';
	print '<input type="hidden" name="origin" value="' . $origin . '">';
	print '<input type="hidden" name="originid" value="' . $originid . '">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="fieldrequired">' . $langs->trans('Ref') . '</td><td colspan="2">' . $langs->trans('Draft') . '</td></tr>';

	// Thirdparty
	print '<td class="fieldrequired">' . $langs->trans('Customer') . '</td>';
	if ($soc->id > 0)
	{
		print '<td colspan="2">';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="' . $soc->id . '">';
		// Outstanding Bill
		$outstandingBills = $soc->get_OutstandingBill();
		print ' (' . $langs->trans('CurrentOutstandingBill') . ': ';
		print price($outstandingBills, '', $langs, 0, 0, -1, $conf->currency);
		if ($soc->outstanding_limit != '')
		{
			if ($outstandingBills > $soc->outstanding_limit) print img_warning($langs->trans("OutstandingBillReached"));
			print ' / ' . price($soc->outstanding_limit, '', $langs, 0, 0, -1, $conf->currency);
		}
		print ')';
		print '</td>';
	}
	else
	{
		print '<td colspan="2">';
		print $form->select_company('', 'socid', 's.client = 1 OR s.client = 3', 1);
		print '</td>';
	}
	print '</tr>' . "\n";

	// Predefined invoices
	if (empty($origin) && empty($originid) && $socid > 0)
	{
		$sql = 'SELECT r.rowid, r.titre, r.total_ttc';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'facture_rec as r';
		$sql .= ' WHERE r.fk_soc = ' . $soc->id;

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num > 0)
			{
				print '<tr><td>' . $langs->trans('CreateFromRepeatableInvoice') . '</td><td>';
				print '<select class="flat" name="fac_rec">';
				print '<option value="0" selected></option>';
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					print '<option value="' . $objp->rowid . '"';
					if (GETPOST('fac_rec') == $objp->rowid)
						print ' selected';
					print '>' . $objp->titre . ' (' . price($objp->total_ttc) . ' ' . $langs->trans("TTC") . ')</option>';
					$i ++;
				}
				print '</select></td></tr>';
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
	}

	// Type de facture
	$facids = $facturestatic->list_replacable_invoices($soc->id);
	if ($facids < 0) {
		dol_print_error($db, $facturestatic);
		exit();
	}
	$options = "";
	foreach ($facids as $facparam)
	{
		$options .= '<option value="' . $facparam ['id'] . '"';
		if ($facparam ['id'] == $_POST['fac_replacement'])
			$options .= ' selected';
		$options .= '>' . $facparam ['ref'];
		$options .= ' (' . $facturestatic->LibStatut(0, $facparam ['status']) . ')';
		$options .= '</option>';
	}

	// Show link for credit note
	$facids=$facturestatic->list_qualified_avoir_invoices($soc->id);
	if ($facids < 0)
	{
		dol_print_error($db,$facturestatic);
		exit;
	}
	$optionsav = "";
	$newinvoice_static = new Facture($db);
	foreach ($facids as $key => $valarray)
	{
		$newinvoice_static->id = $key;
		$newinvoice_static->ref = $valarray ['ref'];
		$newinvoice_static->statut = $valarray ['status'];
		$newinvoice_static->type = $valarray ['type'];
		$newinvoice_static->paye = $valarray ['paye'];

		$optionsav .= '<option value="' . $key . '"';
		if ($key == $_POST['fac_avoir'])
			$optionsav .= ' selected';
		$optionsav .= '>';
		$optionsav .= $newinvoice_static->ref;
		$optionsav .= ' (' . $newinvoice_static->getLibStatut(1, $valarray ['paymentornot']) . ')';
		$optionsav .= '</option>';
	}

	if($isTicket != "true") {
		print '<tr><td valign="top" class="fieldrequired">' . $langs->trans('Type') . '</td><td colspan="2">';

		print '<div class="tagtable">' . "\n";

		// Standard invoice
		print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
		$tmp='<input type="radio" id="radio_standard" name="type" value="0"' . (GETPOST('type') == 0 ? ' checked' : '') . '> ';
		$desc = $form->textwithpicto($tmp.$langs->trans("InvoiceStandardAsk"), $langs->transnoentities("InvoiceStandardDesc"), 1, 'help', '', 0, 3);
		print $desc;
		print '</div></div>';

		if ((empty($origin)) || ((($origin == 'propal') || ($origin == 'commande')) && (! empty($originid))))
		{
			// Deposit
			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp='<input type="radio" id="radio_deposit" name="type" value="3"' . (GETPOST('type') == 3 ? ' checked' : '') . '> ';
			print '<script type="text/javascript" language="javascript">
			jQuery(document).ready(function() {
				jQuery("#typedeposit, #valuedeposit").click(function() {
					jQuery("#radio_deposit").prop("checked", true);
				});
			});
			</script>';

			$desc = $form->textwithpicto($tmp.$langs->trans("InvoiceDeposit"), $langs->transnoentities("InvoiceDepositDesc"), 1, 'help', '', 0, 3);
			print '<table class="nobordernopadding"><tr><td>';
			print $desc;
			print '</td>';
			if (($origin == 'propal') || ($origin == 'commande'))
			{
				print '<td class="nowrap" style="padding-left: 5px">';
				$arraylist = array('amount' => 'FixAmount','variable' => 'VarAmount');
				print $form->selectarray('typedeposit', $arraylist, GETPOST('typedeposit'), 0, 0, 0, '', 1);
				print '</td>';
				print '<td class="nowrap" style="padding-left: 5px">' . $langs->trans('Value') . ':<input type="text" id="valuedeposit" name="valuedeposit" size="3" value="' . GETPOST('valuedeposit', 'int') . '"/>';
			}
			print '</td></tr></table>';

			print '</div></div>';
		}

		if ($socid > 0)
		{
			if (! empty($conf->global->INVOICE_USE_SITUATION))
			{
				// First situation invoice
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp='<input type="radio" name="type" value="5"' . (GETPOST('type') == 5 ? ' checked' : '') . '> ';
				$desc = $form->textwithpicto($tmp.$langs->trans("InvoiceFirstSituationAsk"), $langs->transnoentities("InvoiceFirstSituationDesc"), 1, 'help', '', 0, 3);
				print $desc;
				print '</div></div>';

				// Next situation invoice
				$opt = $form->selectSituationInvoices(GETPOST('originid'), $socid);
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp='<input type="radio" name="type" value="5"' . (GETPOST('type') == 5 && GETPOST('originid') ? ' checked' : '');
				if ($opt == ('<option value ="0" selected>' . $langs->trans('NoSituations') . '</option>') || (GETPOST('origin') && GETPOST('origin') != 'facture')) $tmp.=' disabled';
				$tmp.= '> ';
				$text = $tmp.$langs->trans("InvoiceSituationAsk") . ' ';
				$text .= '<select class="flat" id="situations" name="situations">';
				$text .= $opt;
				$text .= '</select>';
				$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceSituationDesc"), 1, 'help', '', 0, 3);
				print $desc;
				print '</div></div>';
			}

			// Replacement
			print '<!-- replacement line --><div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp='<input type="radio" name="type" id="radio_replacement" value="1"' . (GETPOST('type') == 1 ? ' checked' : '');
			if (! $options) $tmp.=' disabled';
			$tmp.='> ';
			print '<script type="text/javascript" language="javascript">
			jQuery(document).ready(function() {
				jQuery("#fac_replacement").change(function() {
					jQuery("#radio_replacement").prop("checked", true);
				});
			});
			</script>';
			$text = $tmp.$langs->trans("InvoiceReplacementAsk") . ' ';
			$text .= '<select class="flat" name="fac_replacement" id="fac_replacement"';
			if (! $options)
				$text .= ' disabled';
			$text .= '>';
			if ($options) {
				$text .= '<option value="-1">&nbsp;</option>';
				$text .= $options;
			} else {
				$text .= '<option value="-1">' . $langs->trans("NoReplacableInvoice") . '</option>';
			}
			$text .= '</select>';
			$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceReplacementDesc"), 1, 'help', '', 0, 3);
			print $desc;
			print '</div></div>';
		}
		else
		{
			print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
			$tmp='<input type="radio" name="type" id="radio_replacement" value="0" disabled> ';
			$text = $tmp.$langs->trans("InvoiceReplacement") . ' ';
			$text.= '('.$langs->trans("YouMustCreateInvoiceFromThird").') ';
			$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceReplacementDesc"), 1, 'help', '', 0, 3);
			print $desc;
			print '</div></div>';
		}

		if (empty($origin))
		{
			if ($socid > 0)
			{
				// Credit note
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp='<input type="radio" id="radio_creditnote" name="type" value="2"' . (GETPOST('type') == 2 ? ' checked' : '');
				if (! $optionsav) $tmp.=' disabled';
				$tmp.= '> ';
				// Show credit note options only if we checked credit note
				print '<script type="text/javascript" language="javascript">
				jQuery(document).ready(function() {
					if (! jQuery("#radio_creditnote").is(":checked"))
					{
						jQuery("#credit_note_options").hide();
					}
					jQuery("#radio_creditnote").click(function() {
						jQuery("#credit_note_options").show();
					});
					jQuery("#radio_standard, #radio_replacement, #radio_deposit").click(function() {
						jQuery("#credit_note_options").hide();
					});
				});
				</script>';
				$text = $tmp.$langs->transnoentities("InvoiceAvoirAsk") . ' ';
				// $text.='<input type="text" value="">';
				$text .= '<select class="flat" name="fac_avoir" id="fac_avoir"';
				if (! $optionsav)
					$text .= ' disabled';
				$text .= '>';
				if ($optionsav) {
					$text .= '<option value="-1"></option>';
					$text .= $optionsav;
				} else {
					$text .= '<option value="-1">' . $langs->trans("NoInvoiceToCorrect") . '</option>';
				}
				$text .= '</select>';
				$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceAvoirDesc"), 1, 'help', '', 0, 3);
				print $desc;

				print '<div id="credit_note_options" class="clearboth">';
		        print '&nbsp;&nbsp;&nbsp; <input data-role="none" type="checkbox" name="invoiceAvoirWithLines" id="invoiceAvoirWithLines" value="1" onclick="if($(this).is(\':checked\') ) { $(\'#radio_creditnote\').prop(\'checked\', true); $(\'#invoiceAvoirWithPaymentRestAmount\').removeAttr(\'checked\');   }" '.(GETPOST('invoiceAvoirWithLines','int')>0 ? 'checked':'').' /> <label for="invoiceAvoirWithLines">'.$langs->trans('invoiceAvoirWithLines')."</label>";
		        print '<br>&nbsp;&nbsp;&nbsp; <input data-role="none" type="checkbox" name="invoiceAvoirWithPaymentRestAmount" id="invoiceAvoirWithPaymentRestAmount" value="1" onclick="if($(this).is(\':checked\') ) { $(\'#radio_creditnote\').prop(\'checked\', true);  $(\'#invoiceAvoirWithLines\').removeAttr(\'checked\');   }" '.(GETPOST('invoiceAvoirWithPaymentRestAmount','int')>0 ? 'checked':'').' /> <label for="invoiceAvoirWithPaymentRestAmount">'.$langs->trans('invoiceAvoirWithPaymentRestAmount')."</label>";
				print '</div>';

				print '</div></div>';
			}
			else
			{
				print '<div class="tagtr listofinvoicetype"><div class="tagtd listofinvoicetype">';
				$tmp='<input type="radio" name="type" id="radio_creditnote" value="0" disabled> ';
				$text = $tmp.$langs->trans("InvoiceAvoir") . ' ';
				$text.= '('.$langs->trans("YouMustCreateInvoiceFromThird").') ';
				$desc = $form->textwithpicto($text, $langs->transnoentities("InvoiceAvoirDesc"), 1, 'help', '', 0, 3);
				print $desc;
				print '</div></div>' . "\n";
			}
		}
	}
	print '</div>';

	print '</td></tr>';

	if ($socid > 0)
	{
		// Discounts for third party
		print '<tr><td>' . $langs->trans('Discounts') . '</td><td colspan="2">';
		if ($soc->remise_percent)
			print $langs->trans("CompanyHasRelativeDiscount", '<a href="' . DOL_URL_ROOT . '/comm/remise.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?socid=' . $soc->id . '&action=' . $action . '&origin=' . GETPOST('origin') . '&originid=' . GETPOST('originid')) . '">' . $soc->remise_percent . '</a>');
		else
			print $langs->trans("CompanyHasNoRelativeDiscount");
		print ' <a href="' . DOL_URL_ROOT . '/comm/remise.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?socid=' . $soc->id . '&action=' . $action . '&origin=' . GETPOST('origin') . '&originid=' . GETPOST('originid')) . '">(' . $langs->trans("EditRelativeDiscount") . ')</a>';
		print '. ';
		print '<br>';
		if ($absolute_discount)
			print $langs->trans("CompanyHasAbsoluteDiscount", '<a href="' . DOL_URL_ROOT . '/comm/remx.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?socid=' . $soc->id . '&action=' . $action . '&origin=' . GETPOST('origin') . '&originid=' . GETPOST('originid')) . '">' . price($absolute_discount) . '</a>', $langs->trans("Currency" . $conf->currency));
		else
			print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print ' <a href="' . DOL_URL_ROOT . '/comm/remx.php?id=' . $soc->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?socid=' . $soc->id . '&action=' . $action . '&origin=' . GETPOST('origin') . '&originid=' . GETPOST('originid')) . '">(' . $langs->trans("EditGlobalDiscounts") . ')</a>';
		print '.';
		print '</td></tr>';
	}

	// Date invoice
	print '<tr><td class="fieldrequired">' . $langs->trans('Date') . '</td><td colspan="2">';
	$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
	print $form->select_date($datefacture?$datefacture:$dateinvoice, '', '', '', '', "add", 1, 1, 1);
	print '</td></tr>';

	// Payment term
	print '<tr><td class="nowrap fieldrequired">' . $langs->trans('PaymentConditionsShort') . '</td><td colspan="2">';
	$form->select_conditions_paiements(isset($_POST['cond_reglement_id']) ? $_POST['cond_reglement_id'] : $cond_reglement_id, 'cond_reglement_id');
	print '</td></tr>';

	// Payment mode
	print '<tr><td class="fieldrequired">' . $langs->trans('PaymentMode') . '</td><td colspan="2">';
	$form->select_types_paiements(isset($_POST['mode_reglement_id']) ? $_POST['mode_reglement_id'] : $mode_reglement_id, 'mode_reglement_id', 'CRDT');
	print '</td></tr>';

    // Bank Account
	if (isset($_POST['fk_account'])) {
		$fk_account = $_POST['fk_account'];
	}

    print '<tr><td>' . 'Caja' . '</td><td colspan="2">';
    $form->select_comptes($fk_account, 'fk_account', 0, '', 1);
    print '</td></tr>';

    if($isTicket != "true") {

		// Project
		if (! empty($conf->projet->enabled) && $socid > 0)
		{
			$projectid = GETPOST('projectid')?GETPOST('projectid'):0;
			if ($origin == 'project') $projectid = ($originid ? $originid : 0);

			$langs->load('projects');
			print '<tr><td>' . $langs->trans('Project') . '</td><td colspan="2">';
			$numprojet = $formproject->select_projects($soc->id, $projectid, 'projectid', 0);
			print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid=' . $soc->id . '&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'">' . $langs->trans("AddProject") . '</a>';
			print '</td></tr>';
		}

		// Incoterms
		if (!empty($conf->incoterm->enabled))
		{
			print '<tr>';
			print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $objectsrc->libelle_incoterms, 1).'</label></td>';
	        print '<td colspan="3" class="maxwidthonsmartphone">';
	        print $form->select_incoterms((!empty($objectsrc->fk_incoterms) ? $objectsrc->fk_incoterms : ''), (!empty($objectsrc->location_incoterms)?$objectsrc->location_incoterms:''));
			print '</td></tr>';
		}


		// Other attributes
		$parameters = array('objectsrc' => $objectsrc,'colspan' => ' colspan="3"');
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
		                                                                                      // hook
		if (empty($reshook) && ! empty($extrafields->attribute_label)) {
			print $object->showOptionals($extrafields, 'edit');

			//add scriptttttt
		}
		// Template to use by default
		print '<tr><td>' . $langs->trans('Model') . '</td>';
		print '<td>';
		include_once DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php';
		$liste = ModelePDFFactures::liste_modeles($db);
		print $form->selectarray('model', $liste, $conf->global->FACTURE_ADDON_PDF);
		print "</td></tr>";
	}

	else { //Print when is ticket

		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
		                                                                                      // hook
		if (empty($reshook) && ! empty($extrafields->attribute_label)) {

			//Getting vendors list
			$sqlVendors = 'SELECT u.firstname, u.rowid
					FROM ' . MAIN_DB_PREFIX . 'user as u
					LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s
					ON u.fk_soc = s.rowid
					LEFT JOIN ' . MAIN_DB_PREFIX . 'user as u2
					ON u.fk_user = u2.rowid
					JOIN ' . MAIN_DB_PREFIX . 'user_extrafields as ef
					ON ef.fk_object = u.rowid
					WHERE u.entity IN (0,1)
					AND (u.statut=1)
					AND ef.rol = 1
					ORDER BY u.login ASC';
			$resultVendors = $db->query($sqlVendors);
			// print'<pre>';
			// print_r($extrafields->attribute_param['vendor']['options']);
			// print'</pre>';
			// die();
			print'
			<!-- showOptionalsInput -->
			<tr >
			   <td><span class="fieldrequired">Vendedor</span></td>
			   <td colspan="3">
			      <select class="flat" name="options_vendor" id="options_vendor" >';
			     if ($resultVendors) {
					 $num = $db->num_rows($resultVendors);
    				 $j = 0;
					while ($j < $num) {
						$objVendor = $db->fetch_object($resultVendors);
						print'<option value="'.$objVendor->rowid.'">'.$objVendor->firstname.'</option>';
						$j++;
					}
				} else {
					dol_print_error($db, '');
				}
			    print '
			      	<option value="0">&nbsp;</option>
			      </select>
			   </td>
			</tr>
			<tr >
			   <td><span class="fieldrequired">Moneda</span></td>
			   <td colspan="3">
			      <select class="flat" name="options_currency" id="options_currency" >
			         <option value="0">&nbsp;</option>
			         <option value="MXN"> MXN</option>
			         <option value="USD"> USD</option>
			      </select>
			   </td>
			</tr>
			      <input type="hidden" name="options_usocfdi" id="options_usocfdi" value="NA">
			      <input type="hidden" name="options_formpagcfdi" id="options_formpagcfdi" value="NA" >
			  	  <input type="hidden" name="options_isticket" value="1"  >';
		}
	}

	// Public note
	print '<tr>';
	print '<td class="border" valign="top">' . $langs->trans('NotePublic') . '</td>';
	print '<td valign="top" colspan="2">';
	$note_public = '';
	if (is_object($objectsrc)) 	// Take value from source object
	{
		$note_public = $objectsrc->note_public;
	}
	$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
	print $doleditor->Create(1);

	// Private note
	if (empty($user->societe_id))
	{
		print '<tr>';
		print '<td class="border" valign="top">' . $langs->trans('NotePrivate') . '</td>';
		print '<td valign="top" colspan="2">';
		$note_private = '';
		if (! empty($origin) && ! empty($originid) && is_object($objectsrc)) 		// Take value from source object
		{
			$note_private = $objectsrc->note_private;
		}
		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
		print $doleditor->Create(1);
		// print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea>
		print '</td></tr>';
	}

	// Lines from source
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc))
	{
		echo 'al else???????';
		// TODO for compatibility
		if ($origin == 'contrat') {
			// Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
			$objectsrc->remise_absolue = $remise_absolue;
			$objectsrc->remise_percent = $remise_percent;
			$objectsrc->update_price(1, - 1, 1);
		}

		print "\n<!-- " . $classname . " info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="' . $objectsrc->total_ht . '">' . "\n";
		print '<input type="hidden" name="total"          value="' . $objectsrc->total_ttc . '">' . "\n";
		print '<input type="hidden" name="tva"            value="' . $objectsrc->total_tva . '">' . "\n";
		print '<input type="hidden" name="origin"         value="' . $objectsrc->element . '">';
		print '<input type="hidden" name="originid"       value="' . $objectsrc->id . '">';

		switch ($classname) {
			case 'Propal':
				$newclassname = 'CommercialProposal';
				break;
			case 'Commande':
				$newclassname = 'Order';
				break;
			case 'Expedition':
				$newclassname = 'Sending';
				break;
			case 'Contrat':
				$newclassname = 'Contract';
				break;
			case 'Fichinter':
				$newclassname = 'Intervention';
				break;
			default:
				$newclassname = $classname;
		}

		print '<tr><td>' . $langs->trans($newclassname) . '</td><td colspan="2">' . $objectsrc->getNomUrl(1);
		//We check if Origin document has already an invoice attached to it
		$objectsrc->fetchObjectLinked($originid,'','','facture');
		$cntinvoice=count($objectsrc->linkedObjects['facture']);
		if ($cntinvoice>=1)
		{
		    setEventMessage('WarningBillExist','warnings');
		    echo ' ('.$langs->trans('LatestRelatedBill').end($objectsrc->linkedObjects['facture'])->getNomUrl(1).')';
		}
		echo '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalHT') . '</td><td colspan="2">' . price($objectsrc->total_ht) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalVAT') . '</td><td colspan="2">' . price($objectsrc->total_tva) . "</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) 		// Localtax1
		{
			print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax1) . "</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2
		{
			print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax2) . "</td></tr>";
		}
		print '<tr><td>' . $langs->trans('TotalTTC') . '</td><td colspan="2">' . price($objectsrc->total_ttc) . "</td></tr>";
	}
	else
	{
		// Show deprecated optional form to add product line here
		if (! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE)) {
			print '<tr><td colspan="3">';

			// Zone de choix des produits predefinis a la creation
			print '<table class="noborder" width="100%">';
			print '<tr>';
			print '<td>' . $langs->trans('ProductsAndServices') . '</td>';
			print '<td>' . $langs->trans('Qty') . '</td>';
			print '<td>' . $langs->trans('ReductionShort') . '</td>';
			print '<td> &nbsp; &nbsp; </td>';
			if (! empty($conf->service->enabled)) {
				print '<td>' . $langs->trans('ServiceLimitedDuration') . '</td>';
			}
			print '</tr>';
			for($i = 1; $i <= $NBLINES; $i ++) {
				print '<tr>';
				print '<td>';
				// multiprix
				if (! empty($conf->global->PRODUIT_MULTIPRICES))
					$form->select_produits('', 'idprod' . $i, '', $conf->product->limit_size, $soc->price_level);
				else
					$form->select_produits('', 'idprod' . $i, '', $conf->product->limit_size);
				print '</td>';
				print '<td><input type="text" size="2" name="qty' . $i . '" value="1"></td>';
				print '<td class="nowrap"><input type="text" size="1" name="remise_percent' . $i . '" value="' . $soc->remise_percent . '">%</td>';
				print '<td>&nbsp;</td>';
				// Si le module service est actif, on propose des dates de debut et fin a la ligne
				if (! empty($conf->service->enabled)) {
					print '<td class="nowrap">';
					print '<table class="nobordernopadding"><tr class="nocellnopadd">';
					print '<td class="nobordernopadding nowrap">';
					print $langs->trans('From') . ' ';
					print '</td><td class="nobordernopadding nowrap">';
					print $form->select_date('', 'date_start' . $i, $usehm, $usehm, 1, "add", 1, 0, 1);
					print '</td></tr>';
					print '<td class="nobordernopadding nowrap">';
					print $langs->trans('to') . ' ';
					print '</td><td class="nobordernopadding nowrap">';
					print $form->select_date('', 'date_end' . $i, $usehm, $usehm, 1, "add", 1, 0, 1);
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

	dol_fiche_end();

	// Button "Create Draft"
	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('CreateDraft') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print "</form>\n";

	// Show origin lines
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc)) {
		print '<br>';

		$title = $langs->trans('ProductsAndServices');
		print_titre($title);

		print '<table class="noborder" width="100%">';

		$objectsrc->printOriginLinesList();

		print '</table>';
	}

	print '<br>';
}
else if ($id > 0 || ! empty($ref))
{
	/*
	 * Show object in view mode
	 */

	$result = $object->fetch($id, $ref);
	if ($result <= 0) {
		dol_print_error($db, $object->error);
		exit();
	}

	// fetch optionals attributes and labels
	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

	if ($user->societe_id > 0 && $user->societe_id != $object->socid)
	accessforbidden('', 0);

	$result = $object->fetch_thirdparty();

	$soc = new Societe($db);
	$result=$soc->fetch($object->socid);
	if ($result < 0) dol_print_error($db);
	$selleruserevenustamp = $mysoc->useRevenueStamp();

	$totalpaye = $object->getSommePaiement();
	$totalcreditnotes = $object->getSumCreditNotesUsed();
	$totaldeposits = $object->getSumDepositsUsed();
	// print "totalpaye=".$totalpaye." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits."
	// selleruserrevenuestamp=".$selleruserevenustamp;

	// We can also use bcadd to avoid pb with floating points
	// For example print 239.2 - 229.3 - 9.9; does not return 0.
	// $resteapayer=bcadd($object->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
	// $resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
	$resteapayer = price2num($object->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');

	if ($object->paye)
	$resteapayer = 0;
	$resteapayeraffiche = $resteapayer;

	if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
		$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
		$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
	} else {
		$filterabsolutediscount = "fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND description='(DEPOSIT)')";
		$filtercreditnote = "fk_facture_source IS NOT NULL AND description <> '(DEPOSIT)'";
	}

	$absolute_discount = $soc->getAvailableDiscounts('', $filterabsolutediscount);
	$absolute_creditnote = $soc->getAvailableDiscounts('', $filtercreditnote);
	$absolute_discount = price2num($absolute_discount, 'MT');
	$absolute_creditnote = price2num($absolute_creditnote, 'MT');

	$author = new User($db);
	if ($object->user_author) {
		$author->fetch($object->user_author);
	}

	$objectidnext = $object->getIdReplacingInvoice();

	$formconfirm = '';

	// Confirmation de la conversion de l'avoir en reduc
	if ($action == 'converttoreduc') {
		$text = $langs->trans('ConfirmConvertToReduc');
		if($isTicket) {
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?isTicket=1&facid=' . $object->id, $langs->trans('ConvertToReduc'), $text, 'confirm_converttoreduc', '', "yes", 2);
		} else {
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $langs->trans('ConvertToReduc'), $text, 'confirm_converttoreduc', '', "yes", 2);
		}
	}

	// Confirm back to draft status
	if ($action == 'modif') {
		$text = $langs->trans('ConfirmUnvalidateBill', $object->ref);
		$formquestion = array();

		$qualified_for_stock_change = 0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			$qualified_for_stock_change = $object->hasProductsOrServices(2);
		} else {
			$qualified_for_stock_change = $object->hasProductsOrServices(1);
		}
		if ($object->type != Facture::TYPE_DEPOSIT && ! empty($conf->global->STOCK_CALCULATE_ON_BILL) && $qualified_for_stock_change) {
			$langs->load("stocks");
			require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
			require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
			$formproduct = new FormProduct($db);
			$warehouse = new Entrepot($db);
			$warehouse_array = $warehouse->list_array();
			if (count($warehouse_array) == 1) {
				$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("WarehouseForStockDecrease", current($warehouse_array)) : $langs->trans("WarehouseForStockIncrease", current($warehouse_array));
				$value = '<input type="hidden" id="idwarehouse" name="idwarehouse" value="' . key($warehouse_array) . '">';
			} else {
				$label = $object->type == Facture::TYPE_CREDIT_NOTE ? $langs->trans("SelectWarehouseForStockDecrease") : $langs->trans("SelectWarehouseForStockIncrease");
				$value = $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1);
			}
			$formquestion = array(
								// 'text' => $langs->trans("ConfirmClone"),
								// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
								// 1),
								// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
								// => 1),
								array('type' => 'other','name' => 'idwarehouse','label' => $label,'value' => $value));
		}
		if($isTicket) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $object->id, $langs->trans('UnvalidateBill'), $text, 'confirm_modif', $formquestion, "yes", 1);
		} else {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('UnvalidateBill'), $text, 'confirm_modif', $formquestion, "yes", 1);
		}
	}

	// Confirmation du classement paye
	if ($action == 'paid' && $resteapayer <= 0) {
		if($isTicket) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $object->ref), 'confirm_paid', '', "yes", 1);
		} else {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $object->ref), 'confirm_paid', '', "yes", 1);
		}
	}
	if ($action == 'paid' && $resteapayer > 0) {
		// Code
		$i = 0;
		$close [$i] ['code'] = 'discount_vat';
		$i ++;
		$close [$i] ['code'] = 'badcustomer';
		$i ++;
		// Help
		$i = 0;
		$close [$i] ['label'] = $langs->trans("HelpEscompte") . '<br><br>' . $langs->trans("ConfirmClassifyPaidPartiallyReasonDiscountVatDesc");
		$i ++;
		$close [$i] ['label'] = $langs->trans("ConfirmClassifyPaidPartiallyReasonBadCustomerDesc");
		$i ++;
		// Texte
		$i = 0;
		$close [$i] ['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonDiscountVat", $resteapayer, $langs->trans("Currency" . $conf->currency)), $close [$i] ['label'], 1);
		$i ++;
		$close [$i] ['reason'] = $form->textwithpicto($langs->transnoentities("ConfirmClassifyPaidPartiallyReasonBadCustomer", $resteapayer, $langs->trans("Currency" . $conf->currency)), $close [$i] ['label'], 1);
		$i ++;
		// arrayreasons[code]=reason
		foreach ($close as $key => $val) {
			$arrayreasons [$close [$key] ['code']] = $close [$key] ['reason'];
		}

		// Cree un tableau formulaire
		$formquestion = array('text' => $langs->trans("ConfirmClassifyPaidPartiallyQuestion"),array('type' => 'radio','name' => 'close_code','label' => $langs->trans("Reason"),'values' => $arrayreasons),array('type' => 'text','name' => 'close_note','label' => $langs->trans("Comment"),'value' => '','size' => '100'));
		// Paiement incomplet. On demande si motif = escompte ou autre
		if($isTicket) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidPartially', $object->ref), 'confirm_paid_partially', $formquestion, "yes");
		} else {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidPartially', $object->ref), 'confirm_paid_partially', $formquestion, "yes");
		}

	}

	// Confirmation de la suppression d'une ligne produit
	if ($action == 'ask_deleteline') {
		if($isTicket) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 'no', 1);
		} else {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 'no', 1);
		}

	}

	if (! $formconfirm) {
		$parameters = array('lineid' => $lineid);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Invoice content

	print '<table class="border" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td colspan="5">Cotizacion</td></tr>';

	// Amount
	print '<tr><td>' . $langs->trans('AmountHT') . '</td>';
	print '<td colspan="5" class="nowrap">' . price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	print '<tr><td>' . $langs->trans('AmountVAT') . '</td><td colspan="3" class="nowrap">' . price($object->total_tva, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	print '</tr>';

	// Amount Local Taxes
	if (($mysoc->localtax1_assuj == "1" && $mysoc->useLocalTax(1)) || $object->total_localtax1 != 0) 	// Localtax1
	{
		print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
		print '<td colspan="3" class="nowrap">' . price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	}
	if (($mysoc->localtax2_assuj == "1" && $mysoc->useLocalTax(2)) || $object->total_localtax2 != 0) 	// Localtax2
	{
		print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
		print '<td colspan="3" class=nowrap">' . price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
	}

	// Revenue stamp
	if ($selleruserevenustamp) 	// Test company use revenue stamp
	{
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('RevenueStamp');
		print '</td>';
		if ($action != 'editrevenuestamp' && ! empty($object->brouillon) && $user->rights->facture->creer)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editrevenuestamp&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetRevenuStamp'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editrevenuestamp') {
			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setrevenuestamp">';
			print $formother->select_revenue_stamp(GETPOST('revenuestamp'), 'revenuestamp', $mysoc->country_code);
			// print '<input type="text" class="flat" size="4" name="revenuestamp" value="'.price2num($object->revenuestamp).'">';
			print ' <input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print price($object->revenuestamp, 1, '', 1, - 1, - 1, $conf->currency);
		}
		print '</td></tr>';
	}

	// Total with tax
	print '<tr><td>' . $langs->trans('AmountTTC') . '</td><td colspan="3" class="nowrap">' . price($object->total_ttc, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';

	// Lines
	$result = $object->getLinesArray();

	if($isTicket) {
		print '	<form name="addproduct" id="addproduct" onsubmit="document.getElementById(\'addline\').disabled=true" action="' . $_SERVER["PHP_SELF"] . '?isTicket=true&id=' . $object->id . (($action != 'editline') ? '#add' : '#line_' . GETPOST('lineid')) . '" method="POST">
		<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateligne') . '">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id . '">
		<input type="hidden" name="isTicket" value="true">
		';
	} else {
		print '	<form name="addproduct" onsubmit="document.getElementById(\'addline\').disabled=true" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#add' : '#line_' . GETPOST('lineid')) . '" method="POST">
		<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateligne') . '">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id . '">
		';
	}



	if (! empty($conf->use_javascript_ajax) && $object->statut == 0) {
		include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
	}

	print '<table id="tablelines" class="noborder noshadow" width="100%">';

	// Show global modifiers
	if (! empty($conf->global->INVOICE_US_SITUATION))
	{
		if ($object->situation_cycle_ref && $object->statut == 0) {
			print '<tr class="liste_titre nodrag nodrop">';
			if($isTicket) {
				print '<form name="updatealllines" id="updatealllines" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '"#updatealllines" method="POST">';
			} else {
				print '<form name="updatealllines" id="updatealllines" action="' . $_SERVER['PHP_SELF'] . '?isTicket=1&id=' . $object->id . '"#updatealllines" method="POST">';
			}
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
			print '<input type="hidden" name="action" value="updatealllines" />';
			print '<input type="hidden" name="id" value="' . $object->id . '" />';

			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td align="center" width="5">&nbsp;</td>';
			}
			print '<td>' . $langs->trans('ModifyAllLines') . '</td>';
			print '<td align="right" width="50">&nbsp;</td>';
			print '<td align="right" width="80">&nbsp;</td>';
			if ($inputalsopricewithtax) print '<td align="right" width="80">&nbsp;</td>';
			print '<td align="right" width="50">&nbsp</td>';
			print '<td align="right" width="50">&nbsp</td>';
			print '<td align="right" width="50">' . $langs->trans('Progress') . '</td>';
			if (! empty($conf->margin->enabled) && empty($user->societe_id))
			{
				print '<td align="right" class="margininfos" width="80">&nbsp;</td>';
				if ((! empty($conf->global->DISPLAY_MARGIN_RATES) || ! empty($conf->global->DISPLAY_MARK_RATES)) && $user->rights->margins->liretous) {
					print '<td align="right" class="margininfos" width="50">&nbsp;</td>';
				}
			}
			print '<td align="right" width="50">&nbsp;</td>';
			print '<td>&nbsp;</td>';
			print '<td width="10">&nbsp;</td>';
			print '<td width="10">&nbsp;</td>';
			print "</tr>\n";

			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td align="center" width="5">&nbsp;</td>';
			}
			print '<tr width="100%" class="nodrag nodrop">';
			print '<td>&nbsp;</td>';
			print '<td width="50">&nbsp;</td>';
			print '<td width="80">&nbsp;</td>';
			print '<td width="50">&nbsp;</td>';
			print '<td width="50">&nbsp;</td>';
			print '<td align="right" class="nowrap"><input type="text" size="1" value="" name="all_progress">%</td>';
			print '<td colspan="4" align="right"><input class="button" type="submit" name="all_percent" value="Modifier" /></td>';
			print '</tr>';
			print '</form>';
		}
	}

	// Show object lines
	if (! empty($object->lines))
		$ret = $object->printObjectLines($action, $mysoc, $soc, $lineid, 1);

	// Form to add new line
	if (($object->statut == 0) && $user->rights->facture->creer && $action != 'valid' && $action != 'editline' && ($object->is_first() || !$object->situation_cycle_ref))
	{
		if ($action != 'editline')
		{
			$var = true;

			// Add free products/services
			$object->formAddObjectLine(1, $mysoc, $soc);

			$parameters = array();
			$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		}
	}

	print "</table>\n";

	print "</form>\n";

	dol_fiche_end();


	// Actions buttons

	if ($action != 'prerelance' && $action != 'presend' && $action != 'valid' && $action != 'editline')
	{
		print '<div class="tabsAction">';

		$parameters = array();
		if(!$isTicket) {
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		}

		                                                                                          // modified by hook
		if (empty($reshook)) {
			// Editer une facture deja validee, sans paiement effectue et pas exporte en compta
			if ($object->statut == 1)
			{
				if ($isTicket) {
					$ticket_type = $object->cond_reglement_id == 1 ? 'cash' : 'credit';
					print '<div class="inline-block divButAction"><a class="butAction" href="#" onclick="window.open(`print_ticket.php?facid='.$object->id.'`);
    window.open(`print_ticket.php?facid='.$object->id.'&copy=1`);">Imprimir Ticket</a></div>';

				}
				// On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
				$ventilExportCompta = $object->getVentilExportCompta();

				if ($resteapayer == $object->total_ttc && empty($object->paye) && $ventilExportCompta == 0)
				{
					if (! $objectidnext && $object->is_last_in_cycle())
					{
					    if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->creer))
       						|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->facture->invoice_advance->unvalidate)))
						{
							print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=modif">' . $langs->trans('Modify') . '</a></div>';
						} else {
							print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("NotEnoughPermissions") . '">' . $langs->trans('Modify') . '</span></div>';
						}
					} else if (!$object->is_last_in_cycle()) {
						print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("NotLastInCycle") . '">' . $langs->trans('Modify') . '</span></div>';
					} else {
						print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('Modify') . '</span></div>';
					}
				}
			}

			// Reopen a standard paid invoice
			if ((($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT)
				|| ($object->type == Facture::TYPE_CREDIT_NOTE && empty($discount->id))
				|| ($object->type == Facture::TYPE_DEPOSIT && empty($discount->id)))
				&& ($object->statut == 2 || $object->statut == 3)
				&& $user->rights->facture->creer)				// A paid invoice (partially or completely)
			{
				if (! $objectidnext && $object->close_code != 'replaced') 				// Not replaced by another invoice
				{
					if($isTicket) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?isTicket=1&facid=' . $object->id . '&amp;action=reopen">' . $langs->trans('ReOpen') . '</a></div>';
					}
					else {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=reopen">' . $langs->trans('ReOpen') . '</a></div>';
					}
				} else {
					if($isTicket) {
						print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('ReOpen') . '</span></div>';
					}
					else {
						print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('ReOpen') . '</span></div>';
					}

				}
			}

			// Send by mail
			if (($object->statut == 1 || $object->statut == 2) || ! empty($conf->global->FACTURE_SENDBYEMAIL_FOR_ALL_STATUS)) {
				if ($objectidnext) {
					print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('SendByMail') . '</span></div>';
				} else {
					if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=presend&amp;mode=init">' . $langs->trans('SendByMail') . '</a></div>';
					} else
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendByMail') . '</a></div>';
				}
			}

			if (! empty($conf->global->FACTURE_SHOW_SEND_REMINDER)) 			// For backward compatibility
			{
				if (($object->statut == 1 || $object->statut == 2) && $resteapayer > 0) {
					if ($objectidnext) {
						print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('SendRemindByMail') . '</span></div>';
					} else {
						if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->facture->invoice_advance->send) {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=prerelance&amp;mode=init">' . $langs->trans('SendRemindByMail') . '</a></div>';
						} else
							print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">' . $langs->trans('SendRemindByMail') . '</a></div>';
					}
				}
			}

			// Create payment
			if ($object->type != Facture::TYPE_CREDIT_NOTE && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement) {
				if ($objectidnext) {
					print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('DoPayment') . '</span></div>';
				} else {
					if ($resteapayer == 0) {
						print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseRemainderToPayIsZero") . '">' . $langs->trans('DoPayment') . '</span></div>';
					} else {
						if($isTicket) {
							print '<div class="inline-block divButAction"><a class="butAction" href="paiement.php?isTicket=1&facid=' . $object->id . '&amp;action=create&amp;accountid='.$object->fk_account.'&amp;currency='.$object->array_options['options_currency'].'">' . $langs->trans('DoPayment') . '</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butAction" href="paiement.php?facid=' . $object->id . '&amp;action=create&amp;accountid='.$object->fk_account.'&amp;currency='.$object->array_options['options_currency'].'">' . $langs->trans('DoPayment') . '</a></div>';
						}

					}
				}
			}

			// Reverse back money or convert to reduction
			if ($object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT) {
				// For credit note only
				if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement)
				{
					if ($resteapayer == 0)
					{
						print '<div class="inline-block divButAction"><span class="butActionRefused" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPaymentBack').'</span></div>';
					}
					else
					{
						print '<div class="inline-block divButAction"><a class="butAction" href="paiement.php?facid='.$object->id.'&amp;action=create">'.$langs->trans('DoPaymentBack').'</a></div>';
					}
				}

				// For credit note
				if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->statut == 1 && $object->paye == 0 && $user->rights->facture->creer && $object->getSommePaiement() == 0) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id . '&amp;action=converttoreduc">' . $langs->trans('ConvertToReduc') . '</a></div>';
				}
				// For deposit invoice
				if ($object->type == Facture::TYPE_DEPOSIT && $object->paye == 1 && $resteapayer == 0 && $user->rights->facture->creer && empty($discount->id))
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?facid='.$object->id.'&amp;action=converttoreduc">'.$langs->trans('ConvertToReduc').'</a></div>';
				}
			}

			// Classify paid
			if ($object->statut == 1 && $object->paye == 0 && $user->rights->facture->paiement && (($object->type != Facture::TYPE_CREDIT_NOTE && $object->type != Facture::TYPE_DEPOSIT && $resteapayer <= 0) || ($object->type == Facture::TYPE_CREDIT_NOTE && $resteapayer >= 0))
				|| ($object->type == Facture::TYPE_DEPOSIT && $object->paye == 0 && $resteapayer == 0 && $user->rights->facture->paiement && empty($discount->id))
			)
			{
				if($isTicket) {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?isTicket=1&facid='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a></div>';
				} else {
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?facid='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a></div>';
				}

			}

			// Classify 'closed not completely paid' (possible si validee et pas encore classee payee)
			if ($object->statut == 1 && $object->paye == 0 && $resteapayer > 0 && $user->rights->facture->paiement)
			{
				if ($totalpaye > 0 || $totalcreditnotes > 0)
				{
					// If one payment or one credit note was linked to this invoice
					if($isTicket) {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?isTicket=1&facid=' . $object->id . '&amp;action=paid">' . $langs->trans('ClassifyPaidPartially') . '</a></div>';
					} else {
						print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=paid">' . $langs->trans('ClassifyPaidPartially') . '</a></div>';
					}

				}
				else
				{
					if ($objectidnext) {
						print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseReplacedInvoice") . '">' . $langs->trans('ClassifyCanceled') . '</span></div>';
					} else {
						if($isTicket) {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?isTicket=1&facid=' . $object->id . '&amp;action=canceled">' . $langs->trans('ClassifyCanceled') . '</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=canceled">' . $langs->trans('ClassifyCanceled') . '</a></div>';
						}
					}
				}
			}

			print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("NotAllowed") . '">' . $langs->trans('Delete') . '</a></div>';

			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?facid=' . $object->id . '&amp;action=confirm_deletealllines">Borrar Lineas</a></div>';
			

			print '</div>';
		}
	}
	print '<br>';
}

?>
<script type="text/javascript" language="javascript">

	$("#socid").change(function()
	{
		var socid = document.getElementById("socid").value;
	    var client = $("#socid option:selected").text();

	    var params = {  "socid" : socid };

	    $.ajax(
	    {
	        data: params,
	        url: "../scripts/commande/getClientData.php",
	        type: "post",
	        dataType: "json",
	        success:  function (data)
	        {
	            document.getElementsByName("cond_reglement_id")[0].value = data.cond;
	            document.getElementById("selectmode_reglement_id").value = data.mode;
	            document.getElementById("options_vendor").value = data.vendor;
	            document.getElementsByName("options_currency")[0].value = data.currency;
	            document.getElementsByName("fk_account")[0].value = data.cash_desk;
	            //Make them disable
	            document.getElementById("selectmode_reglement_id").disabled = true;
	            document.getElementById("options_vendor").disabled = true;
	            document.getElementsByName("cond_reglement_id").disabled = true;
	            document.getElementsByName("options_isticket").disabled = true;
	            document.getElementsByName("fk_account")[0].disabled = true;
	        }
	    });
	});

	jQuery(function ($) {
	  $('form').bind('submit', function () { //function to enable fields before the submit
	    document.getElementById("selectmode_reglement_id").disabled = false;
	    document.getElementById("options_vendor").disabled = false;
	    document.getElementsByName("options_isticket")[0].disabled = false;
	    document.getElementsByName("fk_account")[0].disabled = false;
	  });
	});

	document.addEventListener('DOMContentLoaded', function() { //Disable the fields when the document is ready
     //Make them disable
	document.getElementById("selectmode_reglement_id").disabled = true;
	document.getElementById("options_vendor").disabled = true;
	document.getElementsByName("cond_reglement_id").disabled = true;
	document.getElementsByName("options_isticket")[0].disabled = true;
	document.getElementsByName("fk_account")[0].disabled = true;
	}, false);


</script>

<?php
llxFooter();
$db->close();