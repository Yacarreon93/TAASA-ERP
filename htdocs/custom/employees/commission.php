<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';  //Importing class accounting; this is required in this file
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->projet->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->multicompany->enabled)) dol_include_once('/multicompany/class/actions_multicompany.class.php');

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$subaction	= GETPOST('subaction','alpha');
$group		= GETPOST("group","int",3);

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.datef';
$limit = $conf->liste_limit;

$pageprev = $page - 1;
$pagenext = $page + 1;

$search_user = GETPOST('search_user','int');
$search_sale = GETPOST('search_sale','int');
$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');
$month_general  = GETPOST('month_general','int');
$year_general   = GETPOST('year_general','int');
$monthSelected =  GETPOST('monthSelected','int');
if($month_general != '') $month = $month_general;
if($year_general != '') $year = $year_general;
$day_lim    = GETPOST('day_lim','int');
$month_lim	= GETPOST('month_lim','int');
$year_lim	= GETPOST('year_lim','int');
$filtre	= GETPOST('filtre');
$fromDate = GETPOST('fromDate');
$toDate = GETPOST('toDate');

// Define value to know what current user can do on users
$canadduser=(! empty($user->admin) || $user->rights->user->user->creer);
$canreaduser=(! empty($user->admin) || $user->rights->user->user->lire);
$canedituser=(! empty($user->admin) || $user->rights->user->user->creer);
$candisableuser=(! empty($user->admin) || $user->rights->user->user->supprimer);
$canreadgroup=$canreaduser;
$caneditgroup=$canedituser;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
    $canreadgroup=(! empty($user->admin) || $user->rights->user->group_advance->read);
    $caneditgroup=(! empty($user->admin) || $user->rights->user->group_advance->write);
}
// Define value to know what current user can do on properties of edited user
if ($id)
{
    // $user est le user qui edite, $id est l'id de l'utilisateur edite
    $caneditfield=((($user->id == $id) && $user->rights->user->self->creer)
    || (($user->id != $id) && $user->rights->user->user->creer));
    $caneditpassword=((($user->id == $id) && $user->rights->user->self->password)
    || (($user->id != $id) && $user->rights->user->user->password));
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2='user';
if ($user->id == $id) { $feature2=''; $canreaduser=1; } // A user can always read its own card
if (!$canreaduser) {
	$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
}
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("ldap");
$langs->load("admin");

$object = new User($db);
$extrafields = new ExtraFields($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('paymentlist'));

/**
 * Actions
 */

/*
 * View
 */

$form = new Form($db);
$formother=new FormOther($db);

llxHeader('',$langs->trans("UserCard"));

/* ************************************************************************** */
/*                                                                            */
/* View and edition                                                            */
/*                                                                            */
/* ************************************************************************** */

if ($id > 0)
{
    $object->fetch($id);
    if ($res < 0) { dol_print_error($db,$object->error); exit; }
    $res=$object->fetch_optionals($object->id,$extralabels);

    // Show tabs
    $head = user_prepare_head_for_vendors($object);
    $title = "Vendedores";

    /*
     * Fiche en mode visu
     */
    if ($action != 'edit')
    {
		dol_fiche_head($head, 'comiission', $title, 0, 'user');

        $form = new Form($db);
		$formother = new FormOther($db);
		$formfile = new FormFile($db);
		$bankaccountstatic=new Account($db);
		$facturestatic=new Facture($db);

		$sql = 'SELECT';
		if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
		$sql.= ' f.rowid as facid, f.facnumber, f.ref_client, f.type, f.note_private, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc,';
		$sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
		$sql.= ' f.paye as paye, f.fk_statut,';
		$sql.= ' s.nom as name, s.rowid as socid, s.code_client, s.client ';
		if (! $sall) $sql.= ', SUM(pf.amount) as am';   // To be able to sort on status
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
		if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
		else $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
		if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
		if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
		// We'll need this table joined to the select in order to filter by sale
		if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		if ($search_user > 0)
		{
		    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
		    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
		}
		$sql.= ' JOIN '.MAIN_DB_PREFIX.'facture_extrafields as ef ON ef.fk_object = f.rowid';
		$sql.= ' WHERE f.fk_soc = s.rowid';
		$sql.= ' AND ef.vendor = '.$id;
        $sql.= ' AND f.fk_statut = 1';
		$sql.= " AND f.entity = ".$conf->entity;
		if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
		if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
		if ($userid)
		{
		    if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
		    else $sql.=' AND f.fk_user_author = '.$userid;
		}

		if ($filtre)
		{
		    $aFilter = explode(',', $filtre);
		    foreach ($aFilter as $filter)
		    {
		        $filt = explode(':', $filter);
		        $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
		    }
		}
		if ($search_ref) $sql .= natural_search('f.facnumber', $search_ref);
		if ($search_refcustomer) $sql .= natural_search('f.ref_client', $search_refcustomer);
		if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
		if ($search_montant_ht != '') $sql.= natural_search('f.total', $search_montant_ht, 1);
		if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
		if ($search_status != '' && $search_status >= 0) $sql.= " AND f.fk_statut = ".$db->escape($search_status);
        if ($fromDate && $toDate) {
            $sql.= " AND f.datef BETWEEN '".$fromDate."' AND '".$toDate."'";
        }
		else if ($month > 0)
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
		if ($month_lim > 0)
		{
			if ($year_lim > 0 && empty($day_lim))
				$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,$month_lim,false))."' AND '".$db->idate(dol_get_last_day($year_lim,$month_lim,false))."'";
			else if ($year_lim > 0 && ! empty($day_lim))
				$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_lim, $day_lim, $year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_lim, $day_lim, $year_lim))."'";
			else
				$sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$month_lim."'";
		}
		else if ($year_lim > 0)
		{
			$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($year_lim,12,false))."'";
		}
		if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
		if ($search_user > 0)
		{
		    $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
		}
		if (! $sall)
		{
		    $sql.= ' GROUP BY f.rowid, f.facnumber, ref_client, f.type, f.note_private, f.increment, f.total, f.tva, f.total_ttc,';
		    $sql.= ' f.datef, f.date_lim_reglement,';
		    $sql.= ' f.paye, f.fk_statut,';
		    $sql.= ' s.nom, s.rowid, s.code_client, s.client';
		}
		else
		{
		    $sql .= natural_search(array('s.nom', 'f.facnumber', 'f.note_public', 'fd.description'), $sall);
		}
		$sql.= ' ORDER BY ';
		$listfield=explode(',',$sortfield);
		foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
		$sql.= ' f.rowid DESC ';

		$nbtotalofrecords = 0;
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$result = $db->query($sql);
			$nbtotalofrecords = $db->num_rows($result);
		}


		$sql.= $db->plimit($limit+1,$offset);

		$resql = $db->query($sql);
		if ($resql)
		{
		    $num = $db->num_rows($resql);

		    if ($socid)
		    {
		        $soc = new Societe($db);
		        $soc->fetch($socid);
		    }

		    $param='&socid='.$socid;
		    if ($month)              $param.='&month='.$month;
		    if ($year)               $param.='&year=' .$year;
		    if ($search_ref)         $param.='&search_ref=' .$search_ref;
		    if ($search_refcustomer) $param.='&search_refcustomer=' .$search_refcustomer;
		    if ($search_societe)     $param.='&search_societe=' .$search_societe;
		    if ($search_sale > 0)    $param.='&search_sale=' .$search_sale;
		    if ($search_user > 0)    $param.='&search_user=' .$search_user;
		    if ($search_montant_ht != '')  $param.='&search_montant_ht='.$search_montant_ht;
		    if ($search_montant_ttc != '') $param.='&search_montant_ttc='.$search_montant_ttc;

		    print_barre_liste("Comisión".' '.($socid?' '.$soc->name:''),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_accountancy.png');

            print '<p class="to-left input-height no-mar-top">Porcentaje de comisión asginado</p>';

            print '<input type="text" class="to-left" name="commission" value="'.$object->array_options['options_commission'].'" readonly>';

		    $form=new Form($db);
            $formother=new FormOther($db);

            if (GETPOST("orphelins"))
            {
                // Paiements lies a aucune facture (pour aide au diagnostic)
                $sql = "SELECT p.rowid, p.datep as dp, p.amount,";
                $sql.= " p.statut, p.num_paiement,";
                $sql.= " c.code as paiement_code";
                // Add fields for extrafields
                foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
                // Add fields from hooks
                $parameters=array();
                $reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
                $sql.=$hookmanager->resPrint;
                $sql.= " FROM (".MAIN_DB_PREFIX."paiement as p,";
                $sql.= " ".MAIN_DB_PREFIX."c_paiement as c)";
                $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
                $sql.= " WHERE p.fk_paiement = c.id";
                $sql.= " AND p.entity = ".$conf->entity;
                $sql.= " AND pf.fk_facture IS NULL";
                // Add where from hooks
                $parameters=array();
                $reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
                $sql.=$hookmanager->resPrint;
            }
            else  //Begins the query displayed in the page
            {
                $form = new Form($db);
				$formother = new FormOther($db);
				$formfile = new FormFile($db);
				$bankaccountstatic=new Account($db);
				$facturestatic=new Facture($db);

				$sql = 'SELECT';
				if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
				$sql.= ' f.rowid as facid, f.facnumber, f.ref_client, f.type, f.note_private, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc,';
				$sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
				$sql.= ' f.paye as paye, f.fk_statut,';
				$sql.= ' s.nom as name, s.rowid as socid, s.code_client, s.client, se.commission as com';
				if (! $sall) $sql.= ', SUM(pf.amount) as am';   // To be able to sort on status
				$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
				$sql.= ' JOIN '.MAIN_DB_PREFIX.'societe_extrafields AS se ON se.fk_object = s.rowid';
				$sql.= ', '.MAIN_DB_PREFIX.'facture as f';
				if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
				else $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
				if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
				if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
				// We'll need this table joined to the select in order to filter by sale
				if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
				if ($search_user > 0)
				{
				    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
				    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
				}
				$sql.= ' JOIN '.MAIN_DB_PREFIX.'facture_extrafields as ef ON ef.fk_object = f.rowid';
				$sql.= ' WHERE f.fk_soc = s.rowid';
				$sql.= ' AND ef.vendor = '.$id;
	            $sql.= ' AND f.fk_statut = 2';
				$sql.= " AND f.entity = ".$conf->entity;
				if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
				if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
				if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
				if ($userid)
				{
				    if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
				    else $sql.=' AND f.fk_user_author = '.$userid;
				}
				if ($filtre)
				{
				    $aFilter = explode(',', $filtre);
				    foreach ($aFilter as $filter)
				    {
				        $filt = explode(':', $filter);
				        $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
				    }
				}
				if ($search_ref) $sql .= natural_search('f.facnumber', $search_ref);
				if ($search_refcustomer) $sql .= natural_search('f.ref_client', $search_refcustomer);
				if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
				if ($search_montant_ht != '') $sql.= natural_search('f.total', $search_montant_ht, 1);
				if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
				if ($search_status != '' && $search_status >= 0) $sql.= " AND f.fk_statut = ".$db->escape($search_status);
	            if ($fromDate && $toDate) {
	                $sql.= " AND f.datef BETWEEN '".$fromDate."' AND '".$toDate."'";
	            }
				else if ($month > 0)
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
				if ($month_lim > 0)
				{
					if ($year_lim > 0 && empty($day_lim))
						$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,$month_lim,false))."' AND '".$db->idate(dol_get_last_day($year_lim,$month_lim,false))."'";
					else if ($year_lim > 0 && ! empty($day_lim))
						$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_lim, $day_lim, $year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_lim, $day_lim, $year_lim))."'";
					else
						$sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$month_lim."'";
				}
				else if ($year_lim > 0)
				{
					$sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($year_lim,12,false))."'";
				}
				if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
				if ($search_user > 0)
				{
				    $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
				}
				if (! $sall)
				{
				    $sql.= ' GROUP BY f.rowid, f.facnumber, ref_client, f.type, f.note_private, f.increment, f.total, f.tva, f.total_ttc,';
				    $sql.= ' f.datef, f.date_lim_reglement,';
				    $sql.= ' f.paye, f.fk_statut,';
				    $sql.= ' s.nom, s.rowid, s.code_client, s.client';
				}
				else
				{
				    $sql .= natural_search(array('s.nom', 'f.facnumber', 'f.note_public', 'fd.description'), $sall);
				}

            }
            $sql.= $db->order($sortfield,$sortorder);
            $sql.= $db->plimit($limit+1, $offset);
            //print "$sql";
            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                $i = 0;

                $paramlist='';
                $paramlist.=(GETPOST("orphelins")?"&orphelins=1":"");
                $paramlist.=($search_ref?"&search_ref=".$search_ref:"");
                $paramlist.=($search_company?"&search_company=".$search_company:"");
                $paramlist.=($search_amount?"&search_amount=".$search_amount:"");
                if ($month)              $paramlist.='&month='.$month;
			    if ($year)               $paramlist.='&year=' .$year;
                print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
                echo "<input type='hidden' name='id' value='".$id."'>";
                echo '<br>';
                echo '<br>';
                echo '<br>';
                echo '<div id ="date_filter" style="margin-bottom: 10px; background:rgb(140,150,180); font-weight: bold; color: #FFF; border-collapse: collapse; background-image: -webkit-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%); padding:5px;">';
                echo '<p style="margin:0">Seleccionar Mes</p>';
                echo '<div style="display:inline-block;">';
                echo '<select id="selectMonth" name="month_general">
                      <option value="0"></option>
                      <option value="1">Enero</option>
                      <option value="2">Febrero</option>
                      <option value="3">Marzo</option>
                      <option value="4">Abril</option>
                      <option value="5">Mayo</option>
                      <option value="6">Junio</option>
                      <option value="7">Julio</option>
                      <option value="8">Agosto</option>
                      <option value="9">Septiembre</option>
                      <option value="10">Octubre</option>
                      <option value="11">Noviembre</option>
                      <option value="12">Diciembre</option>
                      </select>';
                //echo '<input class="flat" type="text" size="1" maxlength="2" name="month_general" value="'.$month.'" style="margin-left:10px;">';
                $formother->select_year($year?$year:-1,'year_general',1, 20, 5);
                print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'" style="padding:5px; padding-left: 20px; vertical-align: bottom;">';
                echo '</div>';
                echo '</div>';
                //Script to auto fill date search filter
				echo '<script>
                            function setActualDate() {
                            var today = new Date();
                            var mm = today.getMonth()+1;
                            var yyyy = today.getFullYear();
                            document.getElementById("selectMonth").value =' . $month . '
                            document.getElementById("year_general").value = yyyy;
                            };
                            window.onload = setActualDate;
                        </script>';

                //add code here

                print '<table class="liste" width="100%">';

			 	// If the user can view prospects other than his'
			    $moreforfilter='';
			 	

			    if ($moreforfilter)
			    {
			        print '<tr class="liste_titre">';
			        print '<td class="liste_titre" colspan="11">';
			        print $moreforfilter;
			        print '</td></tr>';
			    }

			    print '<tr class="liste_titre">';
			    print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.facnumber','',$param,'',$sortfield,$sortorder);
			    print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.datef','',$param,'align="center"',$sortfield,$sortorder);
			    print_liste_field_titre($langs->trans("DateDue"),$_SERVER['PHP_SELF'],"f.date_lim_reglement",'',$param,'align="center"',$sortfield,$sortorder);
			    print_liste_field_titre($langs->trans('ThirdParty'),$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
			    print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total','',$param,'align="right"',$sortfield,$sortorder);
			    print_liste_field_titre($langs->trans('AmountVAT'),$_SERVER['PHP_SELF'],'f.tva','',$param,'align="right"',$sortfield,$sortorder);
			    print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
			    print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'am','',$param,'align="right"',$sortfield,$sortorder);
			    print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'fk_statut,paye,am','',$param,'align="right"',$sortfield,$sortorder);
			    print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
			    print "</tr>\n";

			    // Filters lines
			    print '<tr class="liste_titre">';
			    print '<td class="liste_titre" align="left">';
			    print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
			    print '</td>';
			    print '<td class="liste_titre" align="center">';
			    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
			    print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
			    $formother->select_year($year?$year:-1,'year',1, 20, 5);
			    print '</td>';
			 	print '<td class="liste_titre" align="center">';
			    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day_lim" value="'.$day_lim.'">';
			    print '<input class="flat" type="text" size="1" maxlength="2" name="month_lim" value="'.$month_lim.'">';
			    $formother->select_year($year_lim?$year_lim:-1,'year_lim',1, 20, 5);
			    print '</td>';
			    print '<td class="liste_titre" align="left"><input class="flat" type="text" size="8" name="search_societe" value="'.$search_societe.'"></td>';
			    print '<td class="liste_titre" align="right"><input class="flat" type="text" size="6" name="search_montant_ht" value="'.$search_montant_ht.'"></td>';
			    print '<td class="liste_titre"></td>';
			    print '<td class="liste_titre" align="right"><input class="flat" type="text" size="6" name="search_montant_ttc" value="'.$search_montant_ttc.'"></td>';
			    print '<td class="liste_titre"></td>';
			    print '<td class="liste_titre" align="right">';
				$liststatus=array('0'=>$langs->trans("BillShortStatusDraft"), '1'=>$langs->trans("BillShortStatusNotPaid"), '2'=>$langs->trans("BillShortStatusPaid"), '3'=>$langs->trans("BillShortStatusCanceled"));
				print $form->selectarray('search_status', $liststatus, $search_status, 1);
			    print '</td>';
			    print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
				print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
			    print "</td></tr>\n";

			     if ($num > 0)
			    {
			        $var=true;
			        $total_ht=0;
			        $total_tva=0;
			        $total_ttc=0;
			        $totalrecu=0;
			        $total_commission=0;

			        while ($i < min($num,$limit))
			        {
			        	$special_commission = -1;
			            $objp = $db->fetch_object($resql);
			            $var=!$var;
			            //die(get_class($db)); 
			            $datelimit=$db->jdate($objp->datelimite);

			            print '<tr '.$bc[$var].'>';
			            print '<td class="nowrap">';

			            $facturestatic->id=$objp->facid;
			            $facturestatic->ref=$objp->facnumber;
			            $facturestatic->type=$objp->type;
			            $notetoshow=dol_string_nohtmltag(($user->societe_id>0?$objp->note_public:$objp->note),1);
			            $paiement = $facturestatic->getSommePaiement();
			            $lastPayment = $facturestatic->getLastPaiement(); //function added to get last payment
			            $lastPayment = explode(' ',$lastPayment)[0];
			            //die(var_dump($lastPayment , $objp->datelimite));
			            if ($lastPayment > $objp->datelimite) { //If last payment was done before limit day, it counts
			            	$i++;
			            	continue;
			            }
			            print '<table class="nobordernopadding"><tr class="nocellnopadd">';

			            print '<td class="nobordernopadding nowrap">';
			            print $facturestatic->getNomUrl(1,'',200,0,$notetoshow);
			            print $objp->increment;
			            print '</td>';

			            print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
			            if (! empty($objp->note_private))
			            {
							print ' <span class="note">';
							print '<a href="'.DOL_URL_ROOT.'/compta/facture/note.php?id='.$objp->facid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
							print '</span>';
						}
			            $filename=dol_sanitizeFileName($objp->facnumber);
			            $filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($objp->facnumber);
			            $urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->facid;
			            print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
						print '</td>';
			            print '</tr>';
			            print '</table>';

			            print "</td>\n";


						// Date
			            print '<td align="center" class="nowrap">';
			            print dol_print_date($db->jdate($objp->df),'day');
			            print '</td>';

			            // Date limit
			            print '<td align="center" class="nowrap">'.dol_print_date($datelimit,'day');
			            if ($datelimit < ($now - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1 && ! $paiement)
			            {
			                print img_warning($langs->trans('Late'));
			            }
			            print '</td>';

			            print '<td>';
			            $thirdparty=new Societe($db);
			            $thirdparty->id=$objp->socid;
			            $thirdparty->name=$objp->name;
			            $thirdparty->client=$objp->client;
			            $thirdparty->code_client=$objp->code_client;
			            print $thirdparty->getNomUrl(1,'customer');
			            print '</td>';

			            print '<td align="right">'.price($objp->total_ht,0,$langs).'</td>';

			            print '<td align="right">'.price($objp->total_tva,0,$langs).'</td>';

			            print '<td align="right">'.price($objp->total_ttc,0,$langs).'</td>';

			            print '<td align="right">'.(! empty($paiement)?price($paiement,0,$langs):'&nbsp;').'</td>';

			            // Affiche statut de la facture
			            print '<td align="right" class="nowrap">';
			            print $facturestatic->LibStatut($objp->paye,$objp->fk_statut,5,$paiement,$objp->type);
			            print "</td>";

			            print "<td></td>";

			            print "</tr>\n";
			            $total_ht+=$objp->total_ht;
			            $total_tva+=$objp->total_tva;
			            $total_ttc+=$objp->total_ttc;
			            $totalrecu+=$paiement;
			            $special_commission=$objp->com; //Check for special client commission
			            if($special_commission > 0) {
		                	$total_commission += ($paiement * ($special_commission)/ 100);
		                }
		                else {
		                	$total_commission += ($paiement * ($object->array_options['options_commission'])/ 100);
		                }
			            $i++;
			        }

			        if (($offset + $num) <= $limit)
			        {
			            // Print total
			            print '<tr class="liste_total">';
			            print '<td class="liste_total" colspan="5" align="left">'.$langs->trans('Total').'</td>';
			            print '<td class="liste_total" align="right">'.price($total_ht,0,$langs).'</td>';
			            print '<td class="liste_total" align="right">'.price($total_tva,0,$langs).'</td>';
			            print '<td class="liste_total" align="right">'.price($total_ttc,0,$langs).'</td>';
			            print '<td class="liste_total" align="right">'.price($totalrecu,0,$langs).'</td>';
			            print '<td class="liste_total"></td>';
			            print '<td class="liste_total"></td>';
			            print '</tr>';
			        }
			    }

			    print "</table>\n";

               

                echo '<div id ="date_filter" style="margin-bottom: 10px; background:rgb(140,150,180); font-weight: bold; color: #FFF; border-collapse: collapse; background-image: -webkit-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%); padding:5px;">';
                echo '<p style="margin:0">Comisión Total</p>';
                echo '<div style="display:inline-block;">';
                echo '</div>';
                echo '</div>';
                echo '<p style="margin-left:5px;">'.number_format($total_commission,2).'</p>';
            }


            else
            {
                dol_print_error($db);
            }

		}
		else
		{
		    dol_print_error($db);
		}         	          
       
    }
}	

?>

<style type="text/css">
    
    .to-left { float: left; margin-right: 10px }

    .input-height { line-height: 28px; vertical-align: middle }

    .no-mar-top { margin-top: 0px }

</style>

<?php

llxFooter();
$db->close();