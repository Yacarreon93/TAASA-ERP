<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne                 <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani            <acianfa@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin               <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2011 Juanjo Menent               <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/comm/fiche.php
 *       \ingroup    commercial compta
 *       \brief      Page to show customer card of a third party
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once("conf.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
if ($conf->adherent->enabled) require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
if ($conf->ficheinter->enabled) require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");

$langs->load("companies");
if ($conf->contrat->enabled)  $langs->load("contracts");
if ($conf->commande->enabled) $langs->load("orders");
if ($conf->facture->enabled) $langs->load("bills");
if ($conf->projet->enabled)  $langs->load("projects");
if ($conf->ficheinter->enabled) $langs->load("interventions");
if ($conf->notification->enabled) $langs->load("mails");

// Security check
$id = (GETPOST('socid','int') ? GETPOST('socid','int') : GETPOST('id','int'));
if ($user->societe_id > 0) $id=$user->societe_id;
$result = restrictedArea($user,'societe',$id,'&societe');

$action		= GETPOST('action');
$mode		= GETPOST("mode");
$modesearch	= GETPOST("mode_search");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";

$object = new Societe($db);

/*
 * Actions
 */
// --> DIXI

if( isset($_REQUEST["env_data_rec"]) ){
    foreach ($_REQUEST as $key => $value) {
        //echo $key .'=>'. $value.'<br>';
        $$key = $value;
    }
    
	$sql = "SELECT COUNT(*) AS NR FROM  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp WHERE receptor_rfc  = '$rfcactu' AND entity_id = " . $_SESSION['dol_entity'];
    $resql=$db->query($sql);
    $obj = $db->fetch_object($resql); 
    
    if($obj->NR == 0){// Nuevo
    	$sql_Chk = "SELECT COUNT(*) AS NR FROM  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp WHERE receptor_rfc  = '$rfcactu' AND entity_id = " . $_SESSION['dol_entity'];
    	$resql_Chk=$db->query($sql_Chk);
    	$obj_Chk = $db->fetch_object($resql_Chk);
    	if($obj_Chk->NR != 0){
    		$update = "
    		UPDATE  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp SET
    		entity_id = '".$_SESSION['dol_entity']."'
    	    		WHERE receptor_rfc = '$rfcactu'";
    		$db->query( $update );
    	}else{
    		$insert = "
    		INSERT INTO  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp (
    		receptor_rfc,
    		receptor_delompio,
    		receptor_colonia,
    		receptor_calle,
    		receptor_noext,
    		receptor_noint,
    		entity_id
    		) VALUES (
    		'$rfcactu',
    		'$delompio',
    		'$colonianw',
    		'$calle',
    		'$noext',
    		'$noint',
    		'".$_SESSION['dol_entity']."'
    		)";
    				$db->query( $insert );
    		print "<script>alert('Se guardo exitosamente');</script>";
    	}
		
    }else{ // actualiza
    	$update = "	
		UPDATE  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp SET 		
			receptor_delompio='$delompio',
			receptor_colonia='$colonianw',
			receptor_calle='$calle',
			receptor_noext='$noext',
			receptor_noint='$noint' 
		WHERE receptor_rfc = '$rfcactu' AND entity_id = " . $_SESSION['dol_entity'];
        $db->query( $update );
		//echo $update;
    }
}


if ($action == 'setcustomeraccountancycode')
{
	$result=$object->fetch($id);
	$object->code_compta=$_POST["customeraccountancycode"];
	$result=$object->update($object->id,$user,1,1,0);
	if ($result < 0)
	{
		$mesg=join(',',$object->errors);
	}
	$action="";
}

// conditions de reglement
if ($action == 'setconditions' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$result=$object->setPaymentTerms(GETPOST('cond_reglement_id','int'));
	if ($result < 0) dol_print_error($db,$object->error);
}
// mode de reglement
if ($action == 'setmode' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$result=$object->setPaymentMethods(GETPOST('mode_reglement_id','int'));
	if ($result < 0) dol_print_error($db,$object->error);
}
// assujetissement a la TVA
if ($action == 'setassujtva' && $user->rights->societe->creer)
{
	$object->fetch($id);
	$object->tva_assuj=$_POST['assujtva_value'];

	// TODO move to DAO class
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET tva_assuj='".$_POST['assujtva_value']."' WHERE rowid='".$id."'";
	$result = $db->query($sql);
	if (! $result) dol_print_error($result);
}



/*
 * View
 */

llxHeader('',$langs->trans('CustomerCard'));

$datareceptor = get_data_receptor( $db, $id );
$contactstatic = new Contact($db);
$userstatic=new User($db);
$form = new Form($db);


if ($mode == 'search')
{
	if ($modesearch == 'soc')
	{
		// TODO move to DAO class
		$sql = "SELECT s.rowid";
		if (!$user->rights->societe->client->voir && !$id) $sql .= ", sc.fk_soc, sc.fk_user ";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
		if (!$user->rights->societe->client->voir && !$id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	}

	$resql=$db->query($sql);
	if ($resql)
	{
		if ( $db->num_rows($resql) == 1)
		{
			$obj = $db->fetch_object($resql);
			$id = $obj->rowid;
		}
		$db->free($resql);
	}
}


if ($id > 0)
{
	// Load data of third party
	$object->fetch($id);
       
	if ($object->id <= 0)
	{
		dol_print_error($db,$object->error);
	}

	if ($errmesg)
	{
		print "<b>".$errmesg."</b><br>";
	}

	/*
	 * Affichage onglets
	 */

	$head = cfdimx_cliente_head( $_REQUEST["socid"] );

	dol_fiche_head($head, 'uno', $langs->trans("ThirdParty"),0,'company');

	if( $datareceptor["rfc"]!="" ){
		$rfc = $datareceptor["rfc"];
	}else{ $rfc=img_warning() . ' ' . '<font class="error">Dato requerido para CFDI</font>'; }

	print '<table width="100%" class="notopnoleftnoright">';
	print '<tr><td valign="top" class="notopnoleft">';

	print '<table class="border" width="100%">';

	print '<tr><td width="30%">'.$langs->trans("ThirdPartyName").'</td><td width="70%" colspan="3">';
	$object->next_prev_filter="te.client in (1,3)";
	print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom','','') ." ".info_admin('Para modifical el valor de este campo podrá realizarlo en el detalle del Tercero en el campo: Nombre del Tercero',1);
	print '</td></tr>';

	print '<tr><td width="30%">R.F.C.</td><td width="70%" colspan="3">';
	print $rfc ." ".info_admin('Para modifical el valor de este campo podrá realizarlo en el detalle del Tercero en el campo: R.F.C.',1);
	print '</td></tr>';

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	$img=picto_from_langcode($object->country_code);
	if ($object->isInEEC()) print $form->textwithpicto(($img?$img.' ':'').$object->country,$langs->trans("CountryIsInEEC"),1,0);
	else print ($img?$img.' ':'').$object->country ." ".info_admin('Para modifical el valor de este campo podrá realizarlo en el detalle del Tercero en el campo: País',1);
	print '</td></tr>';

	// Estado
	print '<tr><td valign="top">Estado</td><td colspan="3">';
	print $object->state ." ".info_admin('Para modifical el valor de este campo podrá realizarlo en el detalle del Tercero en el campo: Provincia',1);
	print "</td></tr>";

	// Zip / Town
	print '<tr><td nowrap="nowrap">'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td>';
	print '<td colspan="3">'.$object->zip.(($object->zip && $object->town)?' / ':'')." ".info_admin('Para modifical el valor de este campo podrá realizarlo en el detalle del Tercero',1)."</td>";
	print '</tr>';
        // --> DIXI
        // Cta
	// Ban
        if (empty($conf->global->SOCIETE_DISABLE_BANKACCOUNT))
        {
            print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans('RIB');
            print '<td><td align="right">';
            if ($user->rights->societe->creer)
            print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id.'">'.img_edit().'</a>';
            else
            print '&nbsp;';
            print '</td></tr></table>';
            print '</td>';
            print '<td colspan="3">';
            print $object->display_rib();
            print '</td></tr>';
        }
        
	print '
	<script>
	function enviaTercero(){
		location.href="../societe/soc.php?socid='.$id.'&action=edit";
	}
	</script>
	';

        
	// Modifcar datos del tercero
	print '<tr><td colspan="4" align="center">';
	print '<p><input type="button" name="modif" value="Modificar datos de Tercero" class="button" onclick="enviaTercero()"></p>';
	print "</td></tr>";

	// --> DIXI
	// --> Si hay datos llena campos
	if( $rfc !="" ){
		$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_receptor_datacomp WHERE receptor_rfc  = '" . $rfc."' AND entity_id = " . $_SESSION['dol_entity'];
		//echo $sql;
		$resql=$db->query($sql);
		$objx = $db->fetch_object($resql);
								 
	}
	print '<form method="post" name="compdatareceptor">';
        print '<input type="hidden" size="30" name="rfcactu" value="'.$rfc.'">';
	// Delegación o Municipio
	print '<tr><td valign="top">Delegación o Municipio</td><td colspan="3">';
	print '<input type="text" size="30" name="delompio" value="'.$objx->receptor_delompio.'">';
	print "</td></tr>";

	// Colonia
	print '<tr><td valign="top">Colonia</td><td colspan="3">';
	print '<input type="text" size="30" name="colonianw" value="'.$objx->receptor_colonia .'">';
	print "</td></tr>";
	
	// Calle
	print '<tr><td valign="top">Calle</td><td colspan="3">';
	print '<input type="text" size="30" name="calle" value="'.$objx->receptor_calle .'">';
	print "</td></tr>";

	// NoExt
	print '<tr><td valign="top">No. Exterior</td><td colspan="3">';
	print '<input type="text" size="30" name="noext" value="'.$objx->receptor_noext .'">';
	print "</td></tr>";

	// NoInt
	print '<tr><td valign="top">No. Interior</td><td colspan="3">';
	print '<input type="text" size="30" name="noint" value="'.$objx->receptor_noint .'">';
	print "</td></tr>";

	// Guardar
	print '<tr><td colspan="4" align="center">';
	if($user->rights->cfdimx->config->create){
		print '<p><input type="submit" name="env_data_rec" value="Guardar" class="button"></p>';
	}else{
		print '<p>Not Allowed</p>';
	}
	print "</td></tr>";

	print '</form>';

	print "</table>";

	print "</td>\n";


	print '<td valign="top" width="50%" class="notopnoleftnoright">';

	// Nbre max d'elements des petites listes
	$MAXLIST=4;
	$tableaushown=1;

	// Lien recap
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/recap-compta.php?socid='.$object->id.'">'.$langs->trans("ShowCustomerPreview").'</a></td></tr></table></td>';
	print '</tr>';
	print '</table>';
	print '<br>';

	$now=dol_now();

	$sql = "
	SELECT COUNT(*) total_facturado FROM  ".MAIN_DB_PREFIX."cfdimx c, ".MAIN_DB_PREFIX."facture f, ".MAIN_DB_PREFIX."societe s
	WHERE f.rowid = c.fk_facture
	AND s.rowid = f.fk_soc
	AND s.rowid = ".$_REQUEST["socid"];
	$resql=$db->query($sql);
	$objx = $db->fetch_object($resql);

	echo "<p><a href='cfdi_usuario.php?socid=".$_REQUEST["socid"]."'>Todas las facturas timbradas del cliente (".$objx->total_facturado.")</a></p>";
	
	//PREPARADO PARA MOSTRAR LOS ÚLTIMOS REGISTROS CFDI POR CLIENTE
	/*
	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>Titulo</td>
	<td align="right">Mustra Todo</td>';
	print '</tr></table></td>';
	print '</tr>';

	$var=!$var;
	print "<tr $bc[$var]>";
	print '<td nowrap="nowrap">Algo</td>';
	print '<td align="right" width="80">2</td>';
	print '<td align="right" width="120">3</td>';
	print '<td align="right" width="100">4</td></tr>';
	
	print "</table>";
	*/
	

	/*
	 *   Last invoices
	 */
	if ($conf->facture->enabled && $user->rights->facture->lire)
	{
		$facturestatic = new Facture($db);

		$sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.amount, f.total, f.total_ttc,';
		$sql.= ' f.datef as df, f.datec as dc, f.paye as paye, f.fk_statut as statut,';
		$sql.= ' s.nom, s.rowid as socid,';
		$sql.= ' SUM(pf.amount) as am';
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';
		$sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$object->id;
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= ' GROUP BY f.rowid, f.facnumber, f.type, f.amount, f.total, f.total_ttc,';
		$sql.= ' f.datef, f.datec, f.paye, f.fk_statut,';
		$sql.= ' s.nom, s.rowid';
		$sql.= " ORDER BY f.datef DESC, f.datec DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num > 0)
			{
		        print '<table class="noborder" width="100%">';

			    $tableaushown=1;
				print '<tr class="liste_titre">';
				print '<td colspan="6"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastCustomersBills",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$object->id.'">'.$langs->trans("AllBills").' ('.$num.')</a></td>';
                print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/stats/index.php?socid='.$object->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
				print '</tr></table></td>';
				print '</tr>';
			}

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
					print "<td align=\"right\">".dol_print_date($db->jdate($objp->df),'day')."</td>\n";
				}
				else
				{
					print "<td align=\"right\"><b>!!!</b></td>\n";
				}
				//print "<td align=\"right\">".price($objp->total_ttc)."</td>\n";

				// --> DIXI
				$ssq = "SELECT * FROM ".MAIN_DB_PREFIX."cfdimx WHERE factura_id = ".$objp->facid. "";
				$resqll=$db->query($ssq);
				$num_reg = $db->num_rows($resqll);
				if( $num_reg==0){
					print "<td align=\"right\"></td>\n";
				}else{
					print "<td align=\"right\"></td>\n";
				}
				
				
				print '<td align="right" nowrap="nowrap">'.($facturestatic->LibStatut($objp->paye,$objp->statut,5,$objp->am))."</td>\n";
				print "</tr>\n";
				$i++;
			}
			$db->free($resql);

			if ($num > 0) print "</table>";
		}
		else
		{
			dol_print_error($db);
		}
	}

	print "</td></tr>";
	print "</table>";

	print "\n</div>\n";


	/*
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	if ($conf->propal->enabled && $user->rights->propale->creer)
	{
		$langs->load("propal");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddProp").'</a>';
	}

	if ($conf->commande->enabled && $user->rights->commande->creer)
	{
		$langs->load("orders");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddOrder").'</a>';
	}

	if ($user->rights->contrat->creer)
	{
		$langs->load("contracts");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/contrat/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddContract").'</a>';
	}

	if ($conf->ficheinter->enabled && $user->rights->ficheinter->creer)
	{
		$langs->load("fichinter");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a>';
	}

	// Add invoice
	if ($user->societe_id == 0)
	{
		if ($conf->deplacement->enabled)
		{
			$langs->load("trips");
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/deplacement/fiche.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddTrip").'</a>';
		}

		if ($conf->facture->enabled)
		{
			if ($user->rights->facture->creer)
			{
				$langs->load("bills");
				if ($object->client != 0) print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&socid='.$object->id.'">'.$langs->trans("AddBill").'</a>';
				else print '<a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a>';
			}
			else
			{
				print '<a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a>';
			}
		}
	}

	// Add action
	if ($conf->agenda->enabled && ! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
	{
		if ($user->rights->agenda->myactions->create)
		{
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$object->id.'">'.$langs->trans("AddAction").'</a>';
		}
		else
		{
			print '<a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a>';
		}
	}

	print '</div>';
	print "<br>\n";

	if (! empty($conf->global->MAIN_REPEATCONTACTONEACHTAB))
	{
	    print '<br>';
		// List of contacts
		show_contacts($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	}
	
	// Addresses list
	if (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) && ! empty($conf->global->MAIN_REPEATADDRESSONEACHTAB))
	{
		$result=show_addresses($conf,$langs,$db,$object,$_SERVER["PHP_SELF"].'?socid='.$object->id);
	}

    if (! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
    {
        print load_fiche_titre($langs->trans("ActionsOnCompany"),'','');

        // List of todo actions
		show_actions_todo($conf,$langs,$db,$object);

        // List of done actions
		show_actions_done($conf,$langs,$db,$object);
	}
}
else
{
	dol_print_error($db,'Bad value for socid parameter');
}


// End of page
llxFooter();
$db->close();
?>