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

require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/transporteDAO.php';

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$subaction	= GETPOST('subaction','alpha');
$group		= GETPOST("group","int",3);

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

    if (! $_POST["nombre"])
    {
	    $error++;
        setEventMessage('Nombre no definido', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["config_vehicular"])
    {
	    $error++;
	    setEventMessage('RFC no definido', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["placas"])
    {
	    $error++;
	    setEventMessage('Numero de licencia no definido', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["anio"])
    {
	    $error++;
        setEventMessage('Nombre no definido', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["aseguradora"])
    {
	    $error++;
	    setEventMessage('RFC no definido', 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["poliza"])
    {
	    $error++;
	    setEventMessage('Numero de licencia no definido', 'errors');
        $action="create";       // Go back to create page
    }

    if (!$error)
    {
        $object["nombre"]		= GETPOST("nombre",'alpha');
        $object["config_vehicular"]	    = GETPOST("config_vehicular",'alpha');
        $object["placas"]		    = GETPOST("placas",'alpha');
        $object["anio"]		= GETPOST("anio",'alpha');
        $object["aseguradora"]	    = GETPOST("aseguradora",'alpha');
        $object["poliza"]		    = GETPOST("poliza",'alpha');

        $transporteDao = new TransporteDAO($db);
        $id = $transporteDao->InsertTransporte($object);

        if($id) {
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        } else {
            print_r('error');
            //header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        }

    }
}
/*
 * View
 */

$form = new Form($db);

llxHeader('','Transportes');

if (($action == 'create'))
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Affichage fiche en mode creation                                           */
    /*                                                                            */
    /* ************************************************************************** */

    print_fiche_titre('Nuevo Transporte');

    print "<br>";

    print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createOperador">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    dol_fiche_head('', '', '', 0, '');

    print '<table class="border" width="100%">';

    print '<tr>';

    // Nombre
    print '<td width="160"><span class="fieldrequired">Nombre</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="nombre" name="nombre" value="'.GETPOST('nombre').'">';
    print '</td></tr>';

    // Configuracion Vehicular
    print '<td width="160"><span class="fieldrequired">Configuracion Vehicular</span></td>';
    print '<td>';
    print '<select name="config_vehicular" >';
    print '<option value="VL">VL</option>';
    print '<option value="C2">C2</option>';
    print '<option value="C3">C3</option>';
    print '</select>';
    //print '<input size="30" type="text" id="config_vehicular" name="config_vehicular" value="'.GETPOST('config_vehicular').'">';
    print '</td></tr>';

    // Placas
    print '<td width="160"><span class="fieldrequired">Placas</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="placas" name="placas" value="'.GETPOST('placas').'">';
    print '</td></tr>';

    // Anio
    print '<td width="160"><span class="fieldrequired">Año</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="anio" name="anio" value="'.GETPOST('anio').'">';
    print '</td></tr>';

    // Aseguradora
    print '<td width="160"><span class="fieldrequired">Aseguradora</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="aseguradora" name="aseguradora" value="'.GETPOST('aseguradora').'">';
    print '</td></tr>';

    // Poliza
    print '<td width="160"><span class="fieldrequired">Poliza</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="poliza" name="poliza" value="'.GETPOST('poliza').'">';
    print '</td></tr>';
    

 	print "</table>\n";

 	dol_fiche_end();

    print '<div align="center">';
    print '<input class="button" value="Crear Transporte" name="create" type="submit">';
    //print '&nbsp; &nbsp; &nbsp;';
    //print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
    print '</div>';

    print "</form>";
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
        $transporteDao = new transporteDao($db);
        $object = $transporteDao->GetTransporteById($id);

        // Show tabs
        //$head = user_prepare_head($object);
        $title = 'Transportes';

        /*
         * Fiche en mode visu
         */
        
        if ($action == 'update' && $canedituser) {

            $object->nombre	= GETPOST("nombre",'alpha');
            $object->config_vehicular	= GETPOST("config_vehicular",'alpha');
            $object->placas		= GETPOST("placas",'alpha');
            $object->anio	= GETPOST("anio",'alpha');
            $object->aseguradora	= GETPOST("aseguradora",'alpha');
            $object->poliza		= GETPOST("poliza",'alpha');

            $transporteDao->UpdateTransporte($id, $object);
        }

        if ($action != 'edit')
        {
            $h = 0;
            $head = array();
        
            $head[$h][0] = DOL_URL_ROOT.'/custom/traslado/transporte/card.php?id='.$object->rowid;
            $head[$h][1] = 'Ficha Transporte';
            $head[$h][2] = 'Transporte';
            $h++;

			//dol_fiche_head($head, 'user', $title, 0, 'user');
            dol_fiche_head($head, 'transporte', 'Transporte', 0, 'transporte');

            $rowspan=19;

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%">Ref</td>';
            print '<td colspan="2">'.$object->rowid.'</td>';
            //print '</tr>'."\n";         
        
            // Nombre
            print '<tr><td>Nombre</td>';
            print '<td colspan="2">'.$object->nombre.'</td>';
            print '</tr>'."\n";

            // Configuracion Vehicular
            print '<tr><td>Configuracion Vehicular</td>';
            print '<td colspan="2">'.$object->config_vehicular.'</td>';
            print '</tr>'."\n";

            // Placas
            print '<tr><td>Placas</td>';
            print '<td colspan="2">'.$object->placas.'</td>';
            print '</tr>'."\n";

            // Año
            print '<tr><td>Año</td>';
            print '<td colspan="2">'.$object->anio.'</td>';
            print '</tr>'."\n";

            // Aseguradora
            print '<tr><td>Aseguradora</td>';
            print '<td colspan="2">'.$object->aseguradora.'</td>';
            print '</tr>'."\n";

            // Poliza
            print '<tr><td>Poliza</td>';
            print '<td colspan="2">'.$object->poliza.'</td>';
            print '</tr>'."\n";

			print "</table>\n";

            //dol_fiche_end();


            /*
             * Buttons actions
             */

            print '<div class="tabsAction">';

            //Editar
            if ($caneditfield)
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->rowid.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>';
            }

            // Delete
            if ($candisableuser)
            {
                print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->rowid.'">'.$langs->trans("DeleteUser").'</a></div>';
            }

            print "</div>\n";
            print "<br>\n";
        } 


        /*
         * Fiche en mode edition
         */
        if ($action == 'edit' && $canedituser)
        {
        	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->rowid.'" method="POST" name="updateuser" enctype="multipart/form-data">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';

            dol_fiche_head($head, 'user', $title, 0, 'user');

        	$rowspan=19;

            print '<table width="100%" class="border">';

			print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
            print $object->rowid;
            print '</td>';
            print '</tr>';

            // Nombre
            print "<tr>";
            print '<td class="fieldrequired">Nombre</td>';
            print '<td>';
            print '<input size="30" type="text" class="flat" name="nombre" value="'.$object->nombre.'">';
            print '</td>';
            print '</tr>';

            // Configuracion Vehicular
            print "<tr>".'<td>config_vehicular</td>';
            print '<td>';

            print '<input size="30" type="text" class="flat" name="config_vehicular" value="'.$object->config_vehicular.'">';
            
            print '</td></tr>';

            // Placas
            print '<tr><td>Placas</td>';
            print '<td>';

            print '<input size="30" type="text" name="placas" value="'.$object->placas.'">';
            
            print '</td></tr>';  
            
            // Año
            print "<tr>".'<td>Año</td>';
            print '<td>';

            print '<input size="30" type="text" class="flat" name="anio" value="'.$object->anio.'">';
            
            print '</td></tr>';

            // Aseguradora
            print '<tr><td>Aseguradora</td>';
            print '<td>';

            print '<input size="30" type="text" name="aseguradora" value="'.$object->aseguradora.'">';
            
            print '</td></tr>';  

            // Poliza
            print '<tr><td>Poliza</td>';
            print '<td>';

            print '<input size="30" type="text" name="poliza" value="'.$object->poliza.'">';
            
            print '</td></tr>';  

            print '</table>';

            

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
