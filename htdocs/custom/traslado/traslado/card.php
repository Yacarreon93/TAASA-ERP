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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/cartaDAO.php';
require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/transporteDAO.php';
require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/operadorDAO.php';
require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/origenDAO.php';
require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/trasladoDAO.php';
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

    if (! $_POST["fk_origen"])
    {
	    $error++;
	    setEventMessage('Origen no definido', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["fk_ubicacion_destino"])
    {
	    $error++;
	    setEventMessage('Destino no definido', 'errors');
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

        $object["fk_ubicacion_origen"]	    = GETPOST("fk_origen",'alpha');
        $object["fk_ubicacion_destino"]	    = GETPOST("fk_ubicacion_destino",'alpha');
        $object["fecha_salida"]		= $date1;
        $object["fecha_llegada"]	    = $date2;
        $object["distancia_recorrida"]		    = GETPOST("distancia_recorrida",'alpha');
        $object["fk_transporte"]		    = GETPOST("fk_transporte",'alpha');
        $object["fk_operador"]		    = GETPOST("fk_operador",'alpha');

        $trasladoDAO = new trasladoDAO($db);
        $id = $trasladoDAO->InsertTraslado($object);

        if($id) {
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        } else {
            print_r('error');
            //header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        }

    }
}

//delete product line from traslado
else if($action == 'deleteline')
{
    $lineid = GETPOST('lineid','int');
    if($id && $lineid)
    {
        $trasladoDAO = new trasladoDAO($db);
        $result = $trasladoDAO->DeleteProductLine($id, $lineid);
        header("Location: /custom/traslado/traslado/card.php?id=".$id);
    }
}


//delete traslado
else if($action == 'confirm_delete')
{
    if($id)
    {
        $trasladoDAO = new trasladoDAO($db);
        $result = $trasladoDAO->DeleteTraslado($id);
        if($result)
        {
            $trasladoDAO->DeleteProducts($id);
        }
        header("Location: /custom/traslado/traslado/index.php");
    }
}

//Action add product
else if($action == 'addproduct') {
    $data[] = array(
        fk_traslado=> $id,
        fk_product=>GETPOST('idprod'),
        description=>GETPOST('description'),
        qty=>GETPOST('qty'),
        pesoenkg=> GETPOST('pesoenkg'),
        valor_mercancia=>round(GETPOST('valor_mercancia'), 2)
    );

    $trasladoDAO = new TrasladoDAO($db);
    $trasladoDAO->InsertProductLine($data);

    if($id) {
        header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
    } else {
        print_r('error');
        //header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
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

    $trasladoDAO = new TrasladoDAO($db);
    $trasladoDAO->UpdateTraslado($id, $object);

    if($id) {
        header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
    } else {
        print_r('error');
        //header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
    }
}

//Action Validate
else if ($action == 'validate') {
    $trasladoDAO = new TrasladoDAO($db);
    $trasladoDAO->ValidateTraslado($id);

    header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
}
/*
 * View
 */

$object = new Facture($db);
$form = new Form($db);
$transporteDAO = new TransporteDAO($db);
$operadorDao = new OperadorDAO($db);
$origenDAO = new OrigenDAO($db);
$trasladoDAO = new TrasladoDAO($db);

llxHeader('','Traslado');

if (($action == 'create'))
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Affichage fiche en mode creation                                           */
    /*                                                                            */
    /* ************************************************************************** */


    print_fiche_titre('Nuevo Traslado');

    dol_fiche_head('', '', '', 0, '');

    print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createTraslado">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    //dol_fiche_head('', '', '', 0, '');

    print '<table class="border" width="100%">';

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
    print '</td>';
    print '</tr>';

    // Destino
    print '<tr>';
    print '<td width="160"><span class="fieldrequired">Ubicacion Destino</span></td>';
    print '<td>';
    print '<select id="fk_ubicacion_destino" name="fk_ubicacion_destino" value="'.GETPOST('fk_ubicacion_destino').'">';
    $origenes = $origenDAO->GetOrigenes();

    for($i = 0; $i < count($origenes); $i++) {
        print '<option size="30" value="'.$origenes[$i]['rowid'].'" class="select2-drop-mask">'.$origenes[$i]['alias'].'</option>';
    }
    print'</select>';
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
    print '<input class="button" value="Crear Traslado" name="create" type="submit">';
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

    print "</table>\n";

    print "</form>\n";
    
}
// else if($action == 'delete')
// {
//     $form = new Form($db);
//     $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?confirm_delete&id=' . $id, "Borrar Traslado", "olooolo", 'confirm_delete', $formquestion, "yes", 1);
//     //$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?isTicket=1&facid=' . $object->id, $langs->trans('CloneInvoice'), $langs->trans('ConfirmCloneInvoice', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
//     print $formconfirm;

// }
else
{
    /* ************************************************************************** */
    /*                                                                            */
    /* View and edition                                                            */
    /*                                                                            */
    /* ************************************************************************** */

    if ($id > 0)
    {
        $trasladoDAO = new TrasladoDAO($db);
        $object = $trasladoDAO->GetTrasladoById($id);
        $transporteDAO = new TransporteDAO($db);
        $operadorDAO = new OperadorDAO($db);
        $origenDAO = new OrigenDAO($db);

        // Show tabs
        //$head = user_prepare_head($object);
        $title = 'Traslado';

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
        
            $head[$h][0] = DOL_URL_ROOT.'/custom/traslado/card.php?id='.$object->rowid;
            $head[$h][1] = 'Ficha Traslado';
            $head[$h][2] = 'carta_porte';
            $h++;

			//dol_fiche_head($head, 'user', $title, 0, 'user');
            dol_fiche_head($head, 'traslado', 'Traslado', 0, 'traslado');

            $rowspan=19;

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%">Ref</td>';
            print '<td colspan="2">'.$object->rowid.'</td>';
            //print '</tr>'."\n";         
            print '</tr>'."\n";                       
            
            // Ubicacion Origen
            $ubicacion = $origenDAO->GetOrigenById($object->fk_ubicacion_origen);
            print '<tr><td>Origen</td>';
            print '<td colspan="2">'.$ubicacion->alias.'</td>';
            print '</tr>'."\n";
            
            // Ubicacion Destino
            $ubicacion = $origenDAO->GetOrigenById($object->fk_ubicacion_destino);
            print '<tr><td>Destino</td>';
            print '<td colspan="2">'.$ubicacion->alias.'</td>';
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
            $cfdiId =  $trasladoDAO->GetCFDIId($object->rowid);
            print '<tr><td>ID CFDI</td>';
            print '<td colspan="2">'.$cfdiId.'</td>';
            print '</tr>'."\n";   

			print "</table>\n";

            print '<br><br>';

            print '<table id="tablelines" class="noborder noshadow" width="100%">';

            print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?action=addproduct&id=' . $id . '#add" method="POST">
            <input type="hidden" name="tokentaasa" value="' . $_SESSION ['newtokentaasa'] . '">
            <input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateligne') . '">
            <input type="hidden" name="mode" value="">
            <input type="hidden" name="id" value="' . $object->id . '">
            <input type="hidden" name="description" value="">
            <input type="hidden" name="pesoenkg" value="">
            <input type="hidden" name="valor_mercancia" value="">
            ';

            $objectFacture = new Facture($db);
            $objectFacture->fetch("24311");
            include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
            
            $trasladoDAO->PrintObjectLines($langs, $object->rowid);

            if ($action != 'editline' && $object->state == 0)
            {
                $var = true;
                print '<table id="tablelines" class="noborder noshadow" width="100%">';

                // Add free products/services
                $objectFacture->formAddObjectLine(1, $mysoc, $soc);

            }
            print "</form>";
            print "</table>\n";

            //dol_fiche_end();


            /*
             * Buttons actions
             */

            print '<div class="tabsAction">';

            if($object->state == 0) {

                //Editar
                if (!$uuid)
                {
                    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->rowid.'&amp;action=validate">Validar</a></div>';
                }
                // Delete
                if (!$uuid)
                {
                    print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->rowid.'">'.$langs->trans("DeleteUser").'</a></div>';
                }
            } 
            else if($object->state == 1) {
                // Timbrar
                if (!$uuid)
                {
                    print '<div class="inline-block divButAction"><a class="butActionDelete" href="/cfdimx/traslado_cfdi2.php?action=generar_cfdi&amp;id='.$object->rowid.'">Generar CFDI</a></div>';
                }
                //Editar
                if (!$uuid)
                {
                    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->rowid.'&amp;action=validate">Editar</a></div>';
                }
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
        else if($action == 'delete')
        {
            $form = new Form($db);
            $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?confirm_delete&id=' . $object->rowid, "Borrar Traslado", "Eliminar traslado?", 'confirm_delete', $formquestion, "yes", 1);
            print $formconfirm;
        } 

    }
}

llxFooter();
$db->close();

?>
<script type="text/javascript" language="javascript">

    $("#search_idprod").change(function()
    {
		var fk_prod = document.getElementById("idprod").value;
        console.log(fk_prod);
	    var params = {  "productId" : fk_prod };

	    /*Autofill fields when client is selected. If the client debt exceeds the limit, the facture can only have cond reglement 1.*/
	    $.ajax(
	    {
	        data: params,
	        url: "/scripts/commande/getProductData.php",
	        type: "post",
	        dataType: "json",
	        success:  function (data)
	        {
                console.log('sucess');
                console.log(data);
	            document.getElementsByName("options_claveprodserv")[0].value = data.bienesTransp;
	            document.getElementsByName("options_umed")[0].value = data.umed;
	            document.getElementsByName("description")[0].value = data.descripcion;
                document.getElementsByName("valor_mercancia")[0].value = data.valor_mercancia;
                document.getElementsByName("pesoenkg")[0].value = data.peso_kg;
                console.log(data);
	        }
	    });
	});
</script>