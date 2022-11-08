<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *       \file       htdocs/user/card.php
 *       \brief      Tab of user card
 */

require '../../../main.inc.php';
// require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
// require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
// require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
// require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
// require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
// require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
// if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
// if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
// if (! empty($conf->multicompany->enabled)) dol_include_once('/multicompany/class/actions_multicompany.class.php');

require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/cartaDAO.php';
require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/transporteDAO.php';
require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/operadorDAO.php';
require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/origenDAO.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$subaction	= GETPOST('subaction','alpha');
$group		= GETPOST("group","int",3);
$factureId	= GETPOST('factureId','alpha');
$factureName	= GETPOST('factureName','alpha');

//TODO security check
$caneditfield = true;
$candisableuser = true;
$canedituser = true;
/**
 * Actions
 */

// Action Add user
if ($action == 'add')
{
    $object = array();
	$error = 0;

    if (! $_POST["fk_facture"])
    {
	    $error++;
        setEventMessage('Factura no definida', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["fk_origen"])
    {
	    $error++;
	    setEventMessage('Origen no definido', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["fk_cliente"])
    {
	    $error++;
	    setEventMessage('Cliente no definido', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["re"])
    {
	    $error++;
        setEventMessage('Fecha Salida no definida', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["re2"])
    {
	    $error++;
	    setEventMessage('Fecha llegada no definida', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["distancia_recorrida"])
    {
	    $error++;
	    setEventMessage('Distancia Recorrida no definida', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["fk_transporte"])
    {
	    $error++;
	    setEventMessage('Transporte no definido', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["fk_operador"])
    {
	    $error++;
	    setEventMessage('Operador no definido', 'errors');
        $action="create";       // Go back to create page
    }

    if (!$error)
    {
        $date1=date("Y-m-d",strtotime(GETPOST("re",'alpha')));
        $date2=date("Y-m-d",strtotime(GETPOST("re2",'alpha')));

        $object["fk_facture"]		= GETPOST("fk_facture",'alpha');
        $object["fk_ubicacion_origen"]	    = GETPOST("fk_origen",'alpha');
        $object["fk_cliente"]		    = GETPOST("fk_cliente",'alpha');
        $object["fecha_salida"]		= $date1;
        $object["fecha_llegada"]	    = $date2;
        $object["distancia_recorrida"]		    = GETPOST("distancia_recorrida",'alpha');
        $object["fk_transporte"]		    = GETPOST("fk_transporte",'alpha');
        $object["fk_operador"]		    = GETPOST("fk_operador",'alpha');

        $cartaDAO = new CartaDAO($db);
        $id = $cartaDAO->InsertTraslado($object);

        if($id) {
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        } else {
            print_r('error');
            //header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        }

    }
}
//Action Update user
else if ($action == 'update' && $canedituser) {

    $date1=date("Y-m-d",strtotime(GETPOST("re",'alpha')));
    $date2=date("Y-m-d",strtotime(GETPOST("re2",'alpha')));
    $object->fk_ubicacion_origen	= GETPOST("fk_origen",'alpha');
    $object->fecha_salida	= $date1;
    $object->fecha_llegada	= $date2;
    $object->distancia_recorrida		= GETPOST("distancia_recorrida",'alpha');
    $object->fk_transporte		= GETPOST("fk_transporte",'alpha');
    $object->fk_operador		= GETPOST("fk_operador",'alpha');

    $cartaDAO = new CartaDAO($db);
    $cartaDAO->UpdateTraslado($id, $object);

    if($id) {
        header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
    } else {
        print_r('error');
        //header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
    }
}
/*
 * View
 */

$object = new Facture($db);
$form = new Form($db);
$transporteDAO = new TransporteDAO($db);
$operadorDao = new OperadorDAO($db);
$origenDAO = new OrigenDAO($db);

llxHeader('','Carta Porte');

if (($action == 'create'))
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Affichage fiche en mode creation                                           */
    /*                                                                            */
    /* ************************************************************************** */

    // $cartaDAO = new CartaDAO($db);
    // $object = $cartaDAO->GetTrasladoById($id);

    if($factureName)
    {
        $cartaDAO = new CartaDAO($db);
        $factureId = $cartaDAO->SearchForFacture($factureName);
    }

    if($factureId > 0) 
    {
        $object->fetch($factureId);
        $soc = new Societe($db);
		$soc->fetch($object->socid);	
        $factureName = $object->ref;
    }

    print_fiche_titre('Nueva Carta Porte');

    print '<form action="'.$_SERVER['PHP_SELF'].'?action=create" method="POST" name="searchFacture">';

    dol_fiche_head('', '', '', 0, '');

    print '<table class="border" width="100%">';

    print '<tr style="px solid #E0E0E0">';

    // fk_facture
    print '<td width="160"><span class="fieldrequired">Factura Relacionada</span></td>';
    print '<td>';
    print '<input size="30" style="margin-right:30px" type="text" id="fk_facture" name="factureName" value="'.$factureName.'">';
    //print $cartaDAO->select_facture_list('', 'fk_facture', '', 1);
    //print '<input size="30" type="text" id="fk_facture" name="fk_facture" value="'.GETPOST('fk_facture').'">';
    print '<input type="submit" class="button" value="Buscar" name="search_facture"></button>';
    //print '<a href="/custom/traslado/carta_porte/card.php?action=create&factureId="'.''.' class="button" onclick="getFacture()" value="Buscar" name="search_facture">Buscar</button>';

    print '</td></tr>';

    print "</table>\n";

   print "</form>";

    print "<br>";

    print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createTraslado">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    //dol_fiche_head('', '', '', 0, '');

    print '<table class="border" width="100%">';

    print '<tr>';

    // fk_facture
    print '<td width="160"><span class="fieldrequired">Factura Relacionada</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="fk_facture" name="factureName" value="'.$factureName.'">';
    print '<input type="hidden" name="fk_facture" value="'.$factureId.'">';
    //print $cartaDAO->select_facture_list('', 'fk_facture', '', 1);
    //print '<input size="30" type="text" id="fk_facture" name="fk_facture" value="'.GETPOST('fk_facture').'">';
    //print '<button type="button" class="button" onclick="getFacture()" value="Buscar" name="search_facture">Buscar</button>';

    print '</td></tr>';

    // Origen
    print '<tr>';
    print '<td width="160"><span class="fieldrequired">Ubicacion Origen</span></td>';
    print '<td>';
    print '<select id="fk_origen" name="fk_origen" value="'.GETPOST('fk_origen').'">';
    $origenes = $origenDAO->GetOrigenes();

    for($i = 0; $i < count($origenes); $i++) {
        print '<option size="30" value="'.$origenes[$i]['rowid'].'" class="select2-drop-mask">'.$origenes[$i]['alias'].'</option>';
    }
    print'</select>';
    print '</td></tr>';

    // Cliente
    print '<td width="160"><span class="fieldrequired">Cliente</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="nombre_cliente" name="nombre_cliente" value="'.$soc->name.'">';
    print '<input type="hidden" id="fk_cliente" name="fk_cliente" value="'.$soc->id.'">';
    print '<td colspan="2">';
    //print $form->select_company('', 'fk_cliente', 's.client = 1 OR s.client = 3', 1);
    print '</td>';
    print '</tr>';

    //  Fecha Salida
	print '<tr><td class="fieldrequired">Fecha Salida</td><td colspan="2">';
	$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
	print $form->select_date($datefacture?$datefacture:$dateinvoice, '', '', '', '', "add", 1, 1, 1);
	print '</td></tr>';

    // Fecha Llegada
	print '<tr><td class="fieldrequired">Fecha Llegada</td><td colspan="2">';
	$datefacture = dol_mktime(12, 0, 0, $_POST['re2month'], $_POST['re2day'], $_POST['re2year']);
	print $form->select_date($datefacture?$datefacture:$dateinvoice, 're2', '', '', '', "add", 1, 1, 1);
	print '</td></tr>';

    // Distancia Recorrida
    print '<tr>';
    print '<td width="160"><span class="fieldrequired">Distancia Recorrida</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="distancia_recorrida" name="distancia_recorrida" value="'.GETPOST('distancia_recorrida').'"> KM';
    print '</td></tr>';

    // Transporte
    print '<tr>';
    print '<td width="160"><span class="fieldrequired">Transporte</span></td>';
    print '<td>';
    print '<select id="fk_transporte" name="fk_transporte" value="'.GETPOST('fk_transporte').'">';
    $transportes = $transporteDAO->GetTransportes();

    for($i = 0; $i < count($transportes); $i++) {
        print '<option value="'.$transportes[$i]['rowid'].'" size="30" class="select2-drop-mask">'.$transportes[$i]['nombre'].'</option>';
    }
    print'</select>';
    print '</td></tr>';

    // Operador
    print '<tr>';
    print '<td width="160"><span class="fieldrequired">Operador</span></td>';
    print '<td>';
    print '<select id="fk_operador" name="fk_operador" value="'.GETPOST('fk_operador').'">';

    $operadores = $operadorDao->GetOperadores();

    for($i = 0; $i < count($operadores); $i++) {
        print '<option value="'.$operadores[$i]['rowid'].'" size="30" class="select2-drop-mask">'.$operadores[$i]['nombre'].'</option>';
    }
    print'</select>';
    print '</td></tr>';

 	print "</table>\n";

 	dol_fiche_end();

    print '<div align="center">';
    print '<input class="button" value="Crear Carta Porte" name="create" type="submit">';
    print '<br><br>';
    //print '&nbsp; &nbsp; &nbsp;';
    //print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
    print '</div>';

    print "</form>";

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

        print "</table>\n";
    
}
else
{
    /* ************************************************************************** */
    /*                                                                            */
    /* View and edition                                                            */
    /*                                                                            */
    /* ************************************************************************** */

    if ($id > 0)
    {
        $cartaDAO = new CartaDAO($db);
        $object = $cartaDAO->GetTrasladoById($id);
        $objectFacture = new Facture($db);
        $transporteDAO = new TransporteDAO($db);
        $operadorDAO = new OperadorDAO($db);
        $origenDAO = new OrigenDAO($db);
        if($object->fk_facture) 
        {
            $objectFacture->fetch($object->fk_facture);
            $soc = new Societe($db);
            $soc->fetch($objectFacture->socid);	
        }

        // Show tabs
        //$head = user_prepare_head($object);
        $title = 'Carta Porte';

        /*
         * Fiche en mode visu
         */
        

        if ($action != 'edit')
        {
            //Datos de la factura
            $resql=$db->query("SELECT * FROM  cfdi_traslado WHERE rowid = " . $id);
            if ($resql)
            {
                $cfdi_tot = $db->num_rows($resql);
                $i = 0;
                    $obj = $db->fetch_object($resql);
                    if ($obj)
                    {
                        $uuid = $obj->UUID;
                    }
            }

            $h = 0;
            $head = array();
        
            $head[$h][0] = DOL_URL_ROOT.'/custom/carta_porte/card.php?id='.$object->rowid;
            $head[$h][1] = 'Ficha Carta Porte';
            $head[$h][2] = 'carta_porte';
            $h++;

			//dol_fiche_head($head, 'user', $title, 0, 'user');
            dol_fiche_head($head, 'carta-porte', 'Carta Porte', 0, 'carta');

            $rowspan=19;

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%">Ref</td>';
            print '<td colspan="2">'.$object->rowid.'</td>';
            //print '</tr>'."\n";         
            print '</tr>'."\n";                 

            // Factura
            print '<tr><td>Factura Relacionada</td>';
            print '<td colspan="2">'.$object->fk_facture.'</td>';
            print '</tr>'."\n";         

            
            // Ubicacion
            $ubicacion = $origenDAO->GetOrigenById($object->fk_ubicacion_origen);
            print '<tr><td>Ubicacion</td>';
            print '<td colspan="2">'.$ubicacion->alias.'</td>';
            print '</tr>'."\n";   

            // Cliente
            print '<tr><td>Cliente</td>';
            print '<td colspan="2">'.$soc->nom.'</td>';
            print '</tr>'."\n";  
            
            // Fecha Salida
            print '<tr><td>Fecha Salida</td>';
            print '<td colspan="2">';
            print dol_print_date($object->fecha_salida,'daytext');
            print '</td>';
            print '</tr>'."\n";   

            // Fecha Llegada
            print '<tr><td>Fecha Llegada</td>';
            print '<td colspan="2">';
            print dol_print_date($object->fecha_llegada,'daytext');
            print '</td>';
            print '</tr>'."\n";   

            // Distancia Recorrida
            print '<tr><td>Distancia Recorrida</td>';
            print '<td colspan="2">'.$object->distancia_recorrida.'</td>';
            print '</tr>'."\n";   

             // Transporte
            $transporte = $transporteDAO->GetTransporteById($object->fk_transporte);
            print '<tr><td>Transporte</td>';
            print '<td colspan="2">'.$transporte->nombre.'</td>';
            print '</tr>'."\n";
            
            // Operador
            $operador =  $operadorDAO->GetOperadorById($object->fk_operador);
            print '<tr><td>Operador</td>';
            print '<td colspan="2">'.$operador->nombre.'</td>';
            print '</tr>'."\n";   

            // CFDI ID
            $cfdiId =  $cartaDAO->GetCFDIId($object->rowid);
            print '<tr><td>ID CFDI</td>';
            print '<td colspan="2">'.$cfdiId.'</td>';
            print '</tr>'."\n";   

			print "</table>\n";

            print '<br><br>';

            print '<table id="tablelines" class="noborder noshadow" width="100%">';
        
            // Show object lines
            if (! empty($objectFacture->lines))
                $ret = $objectFacture->printObjectLines($action, $mysoc, $soc, $lineid, 1);
        
                print "</table>\n";

            //dol_fiche_end();


            /*
             * Buttons actions
             */

            print '<div class="tabsAction">';

            //Editar
            if (!$uuid)
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->rowid.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>';
            }

            // Timbrar
            if (!$uuid)
            {
                print '<div class="inline-block divButAction"><a class="butActionDelete" href="/cfdimx/traslado_cfdi.php?action=generar_cfdi&amp;id='.$object->rowid.'">Generar CFDI</a></div>';
            }

            // Delete
            if (!$uuid)
            {
                print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->rowid.'">'.$langs->trans("DeleteUser").'</a></div>';
            }

            print "</div>\n";
            print '<br>';
            if( $uuid){
                print '<strong>Traslado Timbrado - UUID: </strong>'.$uuid."&nbsp;<br>";
                    
            }
        } 

        /*
         * Fiche en mode edition
         */
        if ($action == 'edit' && $canedituser)
        {
            dol_fiche_head($head, 'carta_porte', $title, 0, 'carta_porte');

        
            print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="updateTraslado">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="'.$id.'">';
        
            //dol_fiche_head('', '', '', 0, '');
        
            print '<table class="border" width="100%">';
        
            print '<tr>';
        
            // fk_facture
            print '<td width="160"><span class="fieldrequired">Factura Relacionada</span></td>';
            print '<td>';
            print '<input disabled size="30" type="text" id="fk_facture" name="fk_facture" value="'.$object->fk_facture.'">';
            //print $cartaDAO->select_facture_list('', 'fk_facture', '', 1);
            //print '<input size="30" type="text" id="fk_facture" name="fk_facture" value="'.GETPOST('fk_facture').'">';
            //print '<button type="button" class="button" onclick="getFacture()" value="Buscar" name="search_facture">Buscar</button>';
        
            print '</td></tr>';
        
            // Origen
            print '<tr>';
            print '<td width="160"><span class="fieldrequired">Ubicacion Origen</span></td>';
            print '<td>';
            print '<select id="fk_origen" name="fk_origen" >';
            $origenes = $origenDAO->GetOrigenes();
        
            for($i = 0; $i < count($origenes); $i++) {
                //print '<option size="30" class="select2-drop-mask">'.$origenes[$i]['alias'].'</option>';
                if($origenes[$i]['rowid'] == $object->fk_ubicacion_origen) {
                    print '<option selected value="'.$origenes[$i]['rowid'].'" size="30" class="select2-drop-mask">'.$origenes[$i]['alias'].'</option>';
                 } else {
                    print '<option size="30" value="'.$origenes[$i]['rowid'].'" class="select2-drop-mask">'.$origenes[$i]['alias'].'</option>';
                 }
            }
            print'</select>';
            print '</td></tr>';
        
            // Cliente
            print '<td width="160"><span class="fieldrequired">Cliente</span></td>';
            print '<td>';
            print '<input disabled size="30" type="text" id="fk_cliente" name="fk_cliente" value="'.$soc->nom.'">';
            print '</td></tr>';
        
            //  Fecha Salida
            print '<tr><td class="fieldrequired">Fecha Salida</td><td colspan="2">';
            $date  = strtotime($object->fecha_salida);
            $day   = date('d',$date);
            $month = date('m',$date);
            $year  = date('Y',$date);
            $datefacture = dol_mktime(12, 0, 0, $month, $day, $year);
            print $form->select_date( $datefacture, '', '', '', '', "add", 1, 1, 1);
            print '</td></tr>';
        
            // Fecha Llegada
            print '<tr><td class="fieldrequired">Fecha Llegada</td><td colspan="2">';
            $date  = strtotime($object->fecha_llegada);
            $day   = date('d',$date);
            $month = date('m',$date);
            $year  = date('Y',$date);
            $datefacture = dol_mktime(12, 0, 0, $month, $day, $year);
            print $form->select_date($datefacture, 're2', '', '', '', "add", 1, 1, 1);
            print '</td></tr>';
        
            // Distancia Recorrida
            print '<tr>';
            print '<td width="160"><span class="fieldrequired">Distancia Recorrida</span></td>';
            print '<td>';
            print '<input size="30" type="text" id="distancia_recorrida" name="distancia_recorrida" value="'.$object->distancia_recorrida.'">';
            print '</td></tr>';
        
            // Transporte
            print '<tr>';
            print '<td width="160"><span class="fieldrequired">Transporte</span></td>';
            print '<td>';
            print '<select id="fk_transporte" name="fk_transporte" value="">';
            $transportes = $transporteDAO->GetTransportes();
        
            for($i = 0; $i < count($transportes); $i++) {
                 if($transportes[$i]['rowid'] == $object->fk_transporte) {
                    print '<option selected value="'.$transportes[$i]['rowid'].'" size="30" class="select2-drop-mask">'.$transportes[$i]['nombre'].'</option>';
                 } else {
                    print '<option size="30" value="'.$transportes[$i]['rowid'].'" class="select2-drop-mask">'.$transportes[$i]['nombre'].'</option>';
                 }
            }
            print'</select>';
            print '</td></tr>';
        
            // Operador
            print '<tr>';
            print '<td width="160"><span class="fieldrequired">Operador</span></td>';
            print '<td>';
            print '<select id="fk_operador" name="fk_operador" value="">';
        
            $operadores = $operadorDao->GetOperadores();
        
            for($i = 0; $i < count($operadores); $i++) {
                if($operadores[$i]['rowid'] == $object->fk_operador) {
                    print '<option selected value="'.$operadores[$i]['rowid'].'" size="30" class="select2-drop-mask">'.$operadores[$i]['nombre'].'</option>';
                 } else {
                    print '<option size="30" value="'.$operadores[$i]['rowid'].'" class="select2-drop-mask">'.$operadores[$i]['nombre'].'</option>';
                 }
            }
            print'</select>';
            print '</td></tr>';
        
            print "</table>\n";

            dol_fiche_end();

            print '<div align="center">';
            print '<input value="'.$langs->trans("Save").'" class="button" type="submit" name="save">';
            print '&nbsp; &nbsp; &nbsp;';
            print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
            print '</div>';

            print '</form>';
        } 

    }
}

llxFooter();
$db->close();

?>
<!-- <script type="text/javascript" language="javascript">

    $("#socid").change(function()
    {
        console.log('olololololo1');
		var fk_facture = document.getElementById("fk_facture").value;

	    var params = {  "factureId" : fk_facture };

	    /*Autofill fields when client is selected. If the client debt exceeds the limit, the facture can only have cond reglement 1.*/
	    $.ajax(
	    {
	        data: params,
	        url: "/scripts/commande/getFactureData.php",
	        type: "post",
	        dataType: "json",
	        success:  function (data)
	        {
                console.log('sucess');
                console.log(data);
	            document.getElementById("fk_cliente").value = data.nom;
	            document.getElementById("options_vendor").value = data.fk_soc;
	            document.getElementsByName("options_currency")[0].value = data.nom;
	            // document.getElementsByName("fk_account")[0].value = data.cash_desk;
	            // //Make them disable
	            // document.getElementById("selectmode_reglement_id").disabled = true;
	            // document.getElementById("options_vendor").disabled = true;
	            // document.getElementsByName("cond_reglement_id").disabled = true;
	            // document.getElementsByName("options_isticket").disabled = true;
	            // document.getElementsByName("fk_account")[0].disabled = true;
	            // if(data.debt) {
	            // 	$debt = Number.parseFloat(data.debt);
		        //     $credit_limit = Number.parseFloat(data.credit_limit);
		        //     if($debt > $credit_limit) {
		        //     	document.getElementsByName("cond_reglement_id")[0].value = 1;
		        //     	document.getElementsByName("cond_reglement_id")[0].disabled = true;
		        //     	document.getElementById("cond_reglement_id").disabled = false;
		        //     	document.getElementById("cond_reglement_id").value = 1;
		        //     	document.getElementById("cond_reglement_id").setAttribute("name", "cond_reglement_id");
		        //     	//document.getElementById("createButton").disabled = true;
		        //     	document.getElementById("messageDebt").style.color = "red";
		        //     	document.getElementById("messageDebt").innerHTML = "Limite de credito Excedido. Solo ventas de contado";
		        //     } else {
		        //     	//document.getElementById("cond_reglement_id").value = 1;
		        //     	document.getElementById("cond_reglement_id").removeAttribute("name");
		        //     	document.getElementsByName("cond_reglement_id")[0].disabled = false;
		        //     	document.getElementById("messageDebt").innerHTML = "";
		        //     	if(data.cond) {
		        //     	document.getElementsByName("cond_reglement_id")[0].value = data.cond;
			    //         } else {
			    //         	document.getElementsByName("cond_reglement_id")[0].value = 1;
			    //         }
		        //     }
	            // } else {
	            // 	document.getElementById("cond_reglement_id").removeAttribute("name");
		        //     document.getElementsByName("cond_reglement_id")[0].disabled = false;
		        //    if(data.cond) {
		        //     	document.getElementsByName("cond_reglement_id")[0].value = data.cond;
			    //         } else {
			    //         	document.getElementsByName("cond_reglement_id")[0].value = 1;
			    //         }
		        //     document.getElementById("messageDebt").innerHTML = "";
	            // }
	        }
	    });
	});
</script> -->