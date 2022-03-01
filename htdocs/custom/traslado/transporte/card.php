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

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$subaction	= GETPOST('subaction','alpha');
$group		= GETPOST("group","int",3);

// Define value to know what current user can do on users
// $canadduser=(! empty($user->admin) || $user->rights->user->user->creer);
// $canreaduser=(! empty($user->admin) || $user->rights->user->user->lire);
// $canedituser=(! empty($user->admin) || $user->rights->user->user->creer);
// $candisableuser=(! empty($user->admin) || $user->rights->user->user->supprimer);
// $canreadgroup=$canreaduser;
// $caneditgroup=$canedituser;
// if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
// {
//     $canreadgroup=(! empty($user->admin) || $user->rights->user->group_advance->read);
//     $caneditgroup=(! empty($user->admin) || $user->rights->user->group_advance->write);
// }
// // Define value to know what current user can do on properties of edited user
// if ($id)
// {
//     // $user est le user qui edite, $id est l'id de l'utilisateur edite
//     $caneditfield=((($user->id == $id) && $user->rights->user->self->creer)
//     || (($user->id != $id) && $user->rights->user->user->creer));
//     $caneditpassword=((($user->id == $id) && $user->rights->user->self->password)
//     || (($user->id != $id) && $user->rights->user->user->password));
// }

// Security check
// $socid=0;
// if ($user->societe_id > 0) $socid = $user->societe_id;
// $feature2='user';
// if ($user->id == $id) { $feature2=''; $canreaduser=1; } // A user can always read its own card
// if (!$canreaduser) {
// 	$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
// }
// if ($user->id <> $id && ! $canreaduser) accessforbidden();

// $langs->load("users");
// $langs->load("companies");
// $langs->load("ldap");
// $langs->load("admin");

// $object = new User($db);
// $extrafields = new ExtraFields($db);

// // fetch optionals attributes and labels
// $extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// // Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
// $hookmanager->initHooks(array('usercard','globalcard'));



/**
 * Actions
 */


/*
 * View
 */

$form = new Form($db);
$formother=new FormOther($db);

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

    print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createTransporte">';
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
    print '<td width="160"><span class="fieldrequired">Config_vehicular</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="config_vehicular" name="config_vehicular" value="'.GETPOST('config_vehicular').'">';
    print '</td></tr>';

    // Placas
    print '<td width="160"><span class="fieldrequired">Placas</span></td>';
    print '<td>';
    print '<input size="30" type="text" id="placas" name="placas" value="'.GETPOST('placas').'">';
    print '</td></tr>';

     // Año
     print '<td width="160"><span class="fieldrequired">Año</span></td>';
     print '<td>';
     print '<input size="30" type="text" id="anio" name="anio" value="'.GETPOST('anio').'">';
     print '</td></tr>';
 
     // Aseguradora
     print '<td width="160"><span class="fieldrequired">Aseguradora</span></td>';
     print '<td>';
     print '<input size="30" type="text" id="aseguradora" name="aseguradora" value="'.GETPOST('aseguradora').'">';
     print '</td></tr>';
 
     // Placas
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
// else
// {
//     /* ************************************************************************** */
//     /*                                                                            */
//     /* View and edition                                                            */
//     /*                                                                            */
//     /* ************************************************************************** */

//     if ($id > 0)
//     {
//         $object->fetch($id);
//         if ($res < 0) { dol_print_error($db,$object->error); exit; }
//         $res=$object->fetch_optionals($object->id,$extralabels);

//         // Connexion ldap
//         // pour recuperer passDoNotExpire et userChangePassNextLogon
//         if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid))
//         {
//             $ldap = new Ldap();
//             $result=$ldap->connect_bind();
//             if ($result > 0)
//             {
//                 $userSearchFilter = '('.$conf->global->LDAP_FILTER_CONNECTION.'('.$ldap->getUserIdentifier().'='.$object->login.'))';
//                 $entries = $ldap->fetch($object->login,$userSearchFilter);
//                 if (! $entries)
//                 {
//                     setEventMessage($ldap->error, 'errors');
//                 }

//                 $passDoNotExpire = 0;
//                 $userChangePassNextLogon = 0;
//                 $userDisabled = 0;
//                 $statutUACF = '';

//                 // Check options of user account
//                 if (count($ldap->uacf) > 0)
//                 {
//                     foreach ($ldap->uacf as $key => $statut)
//                     {
//                         if ($key == 65536)
//                         {
//                             $passDoNotExpire = 1;
//                             $statutUACF = $statut;
//                         }
//                     }
//                 }
//                 else
//                 {
//                     $userDisabled = 1;
//                     $statutUACF = "ACCOUNTDISABLE";
//                 }

//                 if ($ldap->pwdlastset == 0)
//                 {
//                     $userChangePassNextLogon = 1;
//                 }
//             }
//         }

//         // Show tabs
//         $head = user_prepare_head($object);
//         $title = $langs->trans("User");

//         /*
//          * Confirmation reinitialisation mot de passe
//          */
//         if ($action == 'password')
//         {
//             print $form->formconfirm("card.php?id=$object->id",$langs->trans("ReinitPassword"),$langs->trans("ConfirmReinitPassword",$object->login),"confirm_password", '', 0, 1);
//         }

//         /*
//          * Confirmation envoi mot de passe
//          */
//         if ($action == 'passwordsend')
//         {
//             print $form->formconfirm("card.php?id=$object->id",$langs->trans("SendNewPassword"),$langs->trans("ConfirmSendNewPassword",$object->login),"confirm_passwordsend", '', 0, 1);
//         }

//         /*
//          * Confirm deactivation
//          */
//         if ($action == 'disable')
//         {
//             print $form->formconfirm("card.php?id=$object->id",$langs->trans("DisableAUser"),$langs->trans("ConfirmDisableUser",$object->login),"confirm_disable", '', 0, 1);
//         }

//         /*
//          * Confirm activation
//          */
//         if ($action == 'enable')
//         {
//             print $form->formconfirm("card.php?id=$object->id",$langs->trans("EnableAUser"),$langs->trans("ConfirmEnableUser",$object->login),"confirm_enable", '', 0, 1);
//         }

//         /*
//          * Confirmation suppression
//          */
//         if ($action == 'delete')
//         {
//             print $form->formconfirm("card.php?id=$object->id",$langs->trans("DeleteAUser"),$langs->trans("ConfirmDeleteUser",$object->login),"confirm_delete", '', 0, 1);
//         }

//         /*
//          * Fiche en mode visu
//          */
//         if ($action != 'edit')
//         {
// 			dol_fiche_head($head, 'user', $title, 0, 'user');

//             $rowspan=19;

//             print '<table class="border" width="100%">';

//             // Ref
//             print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
//             print '<td colspan="3">';
//             print $form->showrefnav($object,'id','',$user->rights->user->user->lire || $user->admin);
//             print '</td>';
//             print '</tr>'."\n";

//             if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER)) $rowspan++;
//             if (! empty($conf->societe->enabled)) $rowspan++;
//             if (! empty($conf->adherent->enabled)) $rowspan++;
//             if (! empty($conf->skype->enabled)) $rowspan++;
// 			if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read)) $rowspan = $rowspan+3;
// 			if (! empty($conf->agenda->enabled)) $rowspan++;

//             // Lastname
//             print '<tr><td>'.$langs->trans("Lastname").'</td>';
//             print '<td colspan="2">'.$object->lastname.'</td>';

//             // Photo
//             print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
//             print $form->showphoto('userphoto',$object,100);
//             print '</td>';

//             print '</tr>'."\n";

//             // Firstname
//             print '<tr><td>'.$langs->trans("Firstname").'</td>';
//             print '<td colspan="2">'.$object->firstname.'</td>';
//             print '</tr>'."\n";

//             // Position/Job
//             print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
//             print '<td colspan="2">'.$object->job.'</td>';
//             print '</tr>'."\n";

//             // Gender
// 		    print '<tr><td>'.$langs->trans("Gender").'</td>';
// 		    print '<td>';
// 		    if ($object->gender) print $langs->trans("Gender".$object->gender);
// 		    print '</td></tr>';

//             // Login
//             print '<tr><td>'.$langs->trans("Login").'</td>';
//             if (! empty($object->ldap_sid) && $object->statut==0)
//             {
//                 print '<td colspan="2" class="error">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
//             }
//             else
//             {
//                 print '<td colspan="2">'.$object->login.'</td>';
//             }
//             print '</tr>'."\n";

//             // Password
//             print '<tr><td>'.$langs->trans("Password").'</td>';
//             if (! empty($object->ldap_sid))
//             {
//                 if ($passDoNotExpire)
//                 {
//                     print '<td colspan="2">'.$langs->trans("LdapUacf_".$statutUACF).'</td>';
//                 }
//                 else if($userChangePassNextLogon)
//                 {
//                     print '<td colspan="2" class="warning">'.$langs->trans("UserMustChangePassNextLogon",$ldap->domainFQDN).'</td>';
//                 }
//                 else if($userDisabled)
//                 {
//                     print '<td colspan="2" class="warning">'.$langs->trans("LdapUacf_".$statutUACF,$ldap->domainFQDN).'</td>';
//                 }
//                 else
//                 {
//                     print '<td colspan="2">'.$langs->trans("DomainPassword").'</td>';
//                 }
//             }
//             else
//             {
//                 print '<td colspan="2">';
//                 if ($object->pass) print preg_replace('/./i','*',$object->pass);
//                 else
//                 {
//                     if ($user->admin) print $langs->trans("Crypted").': '.$object->pass_indatabase_crypted;
//                     else print $langs->trans("Hidden");
//                 }
//                 print "</td>";
//             }
//             print '</tr>'."\n";

//             // API key
//             if(! empty($conf->api->enabled) && $user->admin) {
//                 print '<tr><td>'.$langs->trans("ApiKey").'</td>';
//                 print '<td colspan="2">';
//                 if (! empty($object->api_key))
//                     print $langs->trans("Hidden");
//                 print '<td>';
//             }

//             // Administrator
//             print '<tr><td>'.$langs->trans("Administrator").'</td><td colspan="2">';
//             if (! empty($conf->multicompany->enabled) && $object->admin && ! $object->entity)
//             {
//                 print $form->textwithpicto(yn($object->admin),$langs->trans("SuperAdministratorDesc"),1,"superadmin");
//             }
//             else if ($object->admin)
//             {
//                 print $form->textwithpicto(yn($object->admin),$langs->trans("AdministratorDesc"),1,"admin");
//             }
//             else
//             {
//                 print yn($object->admin);
//             }
//             print '</td></tr>'."\n";

//             // Type
//             print '<tr><td>';
//             $text=$langs->trans("Type");
//             print $form->textwithpicto($text, $langs->trans("InternalExternalDesc"));
//             print '</td><td colspan="2">';
//             $type=$langs->trans("Internal");
//             if ($object->societe_id > 0) $type=$langs->trans("External");
// 			print $type;
//             if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
//             print '</td></tr>'."\n";

//             // Ldap sid
//             if ($object->ldap_sid)
//             {
//             	print '<tr><td>'.$langs->trans("Type").'</td><td colspan="2">';
//             	print $langs->trans("DomainUser",$ldap->domainFQDN);
//             	print '</td></tr>'."\n";
//             }

//             // Tel pro
//             print '<tr><td>'.$langs->trans("PhonePro").'</td>';
//             print '<td colspan="2">'.dol_print_phone($object->office_phone,'',0,0,1).'</td>';
//             print '</tr>'."\n";

//             // Tel mobile
//             print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
//             print '<td colspan="2">'.dol_print_phone($object->user_mobile,'',0,0,1).'</td>';
//             print '</tr>'."\n";

//             // Fax
//             print '<tr><td>'.$langs->trans("Fax").'</td>';
//             print '<td colspan="2">'.dol_print_phone($object->office_fax,'',0,0,1).'</td>';
//             print '</tr>'."\n";

//             // Skype
//             if (! empty($conf->skype->enabled))
//             {
// 				print '<tr><td>'.$langs->trans("Skype").'</td>';
//                 print '<td colspan="2">'.dol_print_skype($object->skype,0,0,1).'</td>';
//                 print "</tr>\n";
//             }

//             // EMail
//             print '<tr><td>'.$langs->trans("EMail").'</td>';
//             print '<td colspan="2">'.dol_print_email($object->email,0,0,1).'</td>';
//             print "</tr>\n";

//             // Signature
//             print '<tr><td class="tdtop">'.$langs->trans('Signature').'</td><td colspan="2">';
//             print dol_htmlentitiesbr($object->signature);
//             print "</td></tr>\n";

//             // Hierarchy
//             print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
//             print '<td colspan="2">';
//             if (empty($object->fk_user)) print $langs->trans("None");
//             else {
//             	$huser=new User($db);
//             	$huser->fetch($object->fk_user);
//             	print $huser->getNomUrl(1);
//             }
//             print '</td>';
//             print "</tr>\n";

//             if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read))
//             {
//             	$langs->load("salaries");

// 	            // THM
// 			    print '<tr><td>';
// 			    $text=$langs->trans("THM");
// 			    print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
// 			    print '</td>';
// 			    print '<td colspan="2">';
// 			    print ($object->thm!=''?price($object->thm,'',$langs,1,-1,-1,$conf->currency):'');
// 			    print '</td>';
// 			    print "</tr>\n";

// 	            // TJM
// 			    print '<tr><td>';
// 			    $text=$langs->trans("TJM");
// 			    print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classtjm');
// 			    print '</td>';
// 			    print '<td colspan="2">';
// 			    print ($object->tjm!=''?price($object->tjm,'',$langs,1,-1,-1,$conf->currency):'');
// 			    print '</td>';
// 			    print "</tr>\n";

// 			    // Salary
// 			    print '<tr><td>'.$langs->trans("Salary").'</td>';
// 			    print '<td colspan="2">';
// 			    print ($object->salary!=''?price($object->salary,'',$langs,1,-1,-1,$conf->currency):'');
// 			    print '</td>';
// 			    print "</tr>\n";
//             }

// 		    // Weeklyhours
// 		    print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
// 		    print '<td colspan="2">';
// 			print price2num($object->weeklyhours);
// 		    print '</td>';
// 		    print "</tr>\n";

// 			// Accountancy code
// 			if ($conf->salaries->enabled)
// 			{
// 				print '<tr><td>'.$langs->trans("AccountancyCode").'</td>';
// 				print '<td colspan="2">'.$object->accountancy_code.'</td>';
// 			}

// 			// Color user
// 			if (! empty($conf->agenda->enabled))
//             {
// 				print '<tr><td>'.$langs->trans("ColorUser").'</td>';
// 				print '<td colspan="2">';
// 				print $formother->showColor($object->color, '');
// 				print '</td>';
// 				print "</tr>\n";
// 			}

//             // Status
//             print '<tr><td>'.$langs->trans("Status").'</td>';
//             print '<td colspan="2">';
//             print $object->getLibStatut(4);
//             print '</td>';
//             print '</tr>'."\n";

//             print '<tr><td>'.$langs->trans("LastConnexion").'</td>';
//             print '<td colspan="2">'.dol_print_date($object->datelastlogin,"dayhour").'</td>';
//             print "</tr>\n";

//             print '<tr><td>'.$langs->trans("PreviousConnexion").'</td>';
//             print '<td colspan="2">'.dol_print_date($object->datepreviouslogin,"dayhour").'</td>';
//             print "</tr>\n";

//             if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER))
//             {
//                 print '<tr><td>'.$langs->trans("OpenIDURL").'</td>';
//                 print '<td colspan="2">'.$object->openid.'</td>';
//                 print "</tr>\n";
//             }

//             // Company / Contact
//             if (! empty($conf->societe->enabled))
//             {
//                 print '<tr><td>'.$langs->trans("LinkToCompanyContact").'</td>';
//                 print '<td colspan="2">';
//                 if (isset($object->societe_id) && $object->societe_id > 0)
//                 {
//                     $societe = new Societe($db);
//                     $societe->fetch($object->societe_id);
//                     print $societe->getNomUrl(1,'');
//                 }
//                 else
//                 {
//                     print $langs->trans("ThisUserIsNot");
//                 }
//                 if (! empty($object->contact_id))
//                 {
//                     $contact = new Contact($db);
//                     $contact->fetch($object->contact_id);
//                     if ($object->societe_id > 0) print ' / ';
//                     else print '<br>';
//                     print '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$object->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
//                 }
//                 print '</td>';
//                 print '</tr>'."\n";
//             }

//             // Module Adherent
//             if (! empty($conf->adherent->enabled))
//             {
//                 $langs->load("members");
//                 print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
//                 print '<td colspan="2">';
//                 if ($object->fk_member)
//                 {
//                     $adh=new Adherent($db);
//                     $adh->fetch($object->fk_member);
//                     $adh->ref=$adh->getFullname($langs);	// Force to show login instead of id
//                     print $adh->getNomUrl(1);
//                 }
//                 else
//                 {
//                     print $langs->trans("UserNotLinkedToMember");
//                 }
//                 print '</td>';
//                 print '</tr>'."\n";
//             }

//             // Multicompany
//             // TODO This should be done with hook formObjectOption
//             if (is_object($mc))
//             {
// 	            if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
// 	            {
// 	            	print '<tr><td>'.$langs->trans("Entity").'</td><td width="75%" class="valeur">';
// 	            	if (empty($object->entity))
// 	            	{
// 	            		print $langs->trans("AllEntities");
// 	            	}
// 	            	else
// 	            	{
// 	            		$mc->getInfo($object->entity);
// 	            		print $mc->label;
// 	            	}
// 	            	print "</td></tr>\n";
// 	            }
//             }

//           	// Other attributes
// 			$parameters=array('colspan' => ' colspan="2"');
// 			$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
// 			if (empty($reshook) && ! empty($extrafields->attribute_label))
// 			{
// 				print $object->showOptionals($extrafields);
// 			}

// 			print "</table>\n";

//             dol_fiche_end();


//             /*
//              * Buttons actions
//              */

//             print '<div class="tabsAction">';

//             if ($caneditfield && (empty($conf->multicompany->enabled) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
//             {
//                 if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED))
//                 {
//                     print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("DisabledInMonoUserMode")).'">'.$langs->trans("Modify").'</a></div>';
//                 }
//                 else
//                 {
//                     print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>';
//                 }
//             }
//             elseif ($caneditpassword && ! $object->ldap_sid &&
//             (empty($conf->multicompany->enabled) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
//             {
//                 print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("EditPassword").'</a></div>';
//             }

//             // Si on a un gestionnaire de generation de mot de passe actif
//             if ($conf->global->USER_PASSWORD_GENERATED != 'none')
//             {
// 				if ($object->statut == 0)
// 				{
// 	                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("UserDisabled")).'">'.$langs->trans("ReinitPassword").'</a></div>';
// 				}
//                 elseif (($user->id != $id && $caneditpassword) && $object->login && !$object->ldap_sid &&
//                 ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
//                 {
//                     print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=password">'.$langs->trans("ReinitPassword").'</a></div>';
//                 }

// 				if ($object->statut == 0)
// 				{
// 	                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("UserDisabled")).'">'.$langs->trans("SendNewPassword").'</a></div>';
// 				}
//                 else if (($user->id != $id && $caneditpassword) && $object->login && !$object->ldap_sid &&
//                 ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
//                 {
//                     if ($object->email) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=passwordsend">'.$langs->trans("SendNewPassword").'</a></div>';
//                     else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendNewPassword").'</a></div>';
//                 }
//             }

//             // Activer
//             if ($user->id <> $id && $candisableuser && $object->statut == 0 &&
//             ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
//             {
//                 print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=enable">'.$langs->trans("Reactivate").'</a></div>';
//             }
//             // Desactiver
//             if ($user->id <> $id && $candisableuser && $object->statut == 1 &&
//             ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
//             {
//                 print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=disable&amp;id='.$object->id.'">'.$langs->trans("DisableUser").'</a></div>';
//             }
//             // Delete
//             if ($user->id <> $id && $candisableuser &&
//             ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
//             {
//             	if ($user->admin || ! $object->admin) // If user edited is admin, delete is possible on for an admin
//             	{
//                 	print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("DeleteUser").'</a></div>';
//             	}
//             	else
//             	{
//             		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("MustBeAdminToDeleteOtherAdmin")).'">'.$langs->trans("DeleteUser").'</a></div>';
//             	}
//             }

//             print "</div>\n";
//             print "<br>\n";



//             /*
//              * Liste des groupes dans lequel est l'utilisateur
//              */

//             if ($canreadgroup)
//             {
//                 print_fiche_titre($langs->trans("ListOfGroupsForUser"),'','');

//                 // On selectionne les groupes auquel fait parti le user
//                 $exclude = array();

//                 $usergroup=new UserGroup($db);
//                 $groupslist = $usergroup->listGroupsForUser($object->id);

//                 if (! empty($groupslist))
//                 {
//                     if (! (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)))
//                     {
//                         foreach($groupslist as $groupforuser)
//                         {
//                             $exclude[]=$groupforuser->id;
//                         }
//                     }
//                 }

//                 if ($caneditgroup)
//                 {
//                     print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">'."\n";
//                     print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
//                     print '<input type="hidden" name="action" value="addgroup" />';
//                     print '<table class="noborder" width="100%">'."\n";
//                     print '<tr class="liste_titre"><th class="liste_titre" width="25%">'.$langs->trans("GroupsToAdd").'</th>'."\n";
//                     print '<th>';
//                     print $form->select_dolgroups('', 'group', 1, $exclude, 0, '', '', $object->entity);
//                     print ' &nbsp; ';
//                     // Multicompany
//                     if (! empty($conf->multicompany->enabled))
//                     {
//                         if ($conf->entity == 1 && $conf->multicompany->transverse_mode)
//                         {
//                             print '</td><td>'.$langs->trans("Entity").'</td>';
//                             print "<td>".$mc->select_entities($conf->entity);
//                         }
//                         else
//                         {
//                             print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
//                         }
//                     }
//                     else
//                     {
//                     	print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
//                     }
//                     print '<input type="submit" class="button" value="'.$langs->trans("Add").'" />';
//                     print '</th></tr>'."\n";
//                     print '</table></form>'."\n";

//                     print '<br>';
//                 }

//                 /*
//                  * Groups assigned to user
//                  */
//                 print '<table class="noborder" width="100%">';
//                 print '<tr class="liste_titre">';
//                 print '<td class="liste_titre" width="25%">'.$langs->trans("Groups").'</td>';
//                 if(! empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
//                 {
//                 	print '<td class="liste_titre" width="25%">'.$langs->trans("Entity").'</td>';
//                 }
//                 print "<td>&nbsp;</td></tr>\n";

//                 if (! empty($groupslist))
//                 {
//                     $var=true;

//                     foreach($groupslist as $group)
//                     {
//                         $var=!$var;

//                         print "<tr ".$bc[$var].">";
//                         print '<td>';
//                         if ($caneditgroup)
//                         {
//                             print '<a href="'.DOL_URL_ROOT.'/user/group/card.php?id='.$group->id.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$group->name.'</a>';
//                         }
//                         else
//                         {
//                             print img_object($langs->trans("ShowGroup"),"group").' '.$group->name;
//                         }
//                         print '</td>';
//                         if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
//                         {
//                         	print '<td class="valeur">';
//                         	if (! empty($group->usergroup_entity))
//                         	{
//                         		$nb=0;
//                         		foreach($group->usergroup_entity as $group_entity)
//                         		{
//                         			$mc->getInfo($group_entity);
//                         			print ($nb > 0 ? ', ' : '').$mc->label;
//                         			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'&amp;entity='.$group_entity.'">';
//                         			print img_delete($langs->trans("RemoveFromGroup"));
//                         			print '</a>';
//                         			$nb++;
//                         		}
//                         	}
//                         }
//                         print '<td align="right">';
//                         if ($caneditgroup && empty($conf->multicompany->transverse_mode))
//                         {
//                             print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'">';
//                             print img_delete($langs->trans("RemoveFromGroup"));
//                             print '</a>';
//                         }
//                         else
//                         {
//                             print "&nbsp;";
//                         }
//                         print "</td></tr>\n";
//                     }
//                 }
//                 else
//                 {
//                     print '<tr '.$bc[false].'><td colspan="3">'.$langs->trans("None").'</td></tr>';
//                 }

//                 print "</table>";
//                 print "<br>";
//             }
//         }

//         /*
//          * Fiche en mode edition
//          */
//         if ($action == 'edit' && ($canedituser || $caneditfield || $caneditpassword || ($user->id == $object->id)))
//         {
//         	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="updateuser" enctype="multipart/form-data">';
//             print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
//             print '<input type="hidden" name="action" value="update">';
//             print '<input type="hidden" name="entity" value="'.$object->entity.'">';

//             dol_fiche_head($head, 'user', $title, 0, 'user');

//         	$rowspan=17;
//             if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER)) $rowspan++;
//             if (! empty($conf->societe->enabled)) $rowspan++;
//             if (! empty($conf->adherent->enabled)) $rowspan++;
// 			if (! empty($conf->skype->enabled)) $rowspan++;
// 			if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read)) $rowspan = $rowspan+3;
// 			if (! empty($conf->agenda->enabled)) $rowspan++;

//             print '<table width="100%" class="border">';

// 			print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
//             print '<td colspan="2">';
//             print $object->id;
//             print '</td>';
//             print '</tr>';

//             // Lastname
//             print "<tr>";
//             print '<td class="fieldrequired">'.$langs->trans("Lastname").'</td>';
//             print '<td>';
//             if ($caneditfield && !$object->ldap_sid)
//             {
//                 print '<input size="30" type="text" class="flat" name="lastname" value="'.$object->lastname.'">';
//             }
//             else
//             {
//                 print '<input type="hidden" name="lastname" value="'.$object->lastname.'">';
//                 print $object->lastname;
//             }
//             print '</td>';

//             // Photo
//             print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
//             print $form->showphoto('userphoto',$object,100,0,$caneditfield);
//             print '</td>';

//             print '</tr>';

//             // Firstname
//             print "<tr>".'<td>'.$langs->trans("Firstname").'</td>';
//             print '<td>';
//             if ($caneditfield && !$object->ldap_sid)
//             {
//                 print '<input size="30" type="text" class="flat" name="firstname" value="'.$object->firstname.'">';
//             }
//             else
//             {
//                 print '<input type="hidden" name="firstname" value="'.$object->firstname.'">';
//                 print $object->firstname;
//             }
//             print '</td></tr>';

//             // Position/Job
//             print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
//             print '<td>';
//             if ($caneditfield)
//             {
//             	print '<input size="30" type="text" name="job" value="'.$object->job.'">';
//             }
//             else
// 			{
//                 print '<input type="hidden" name="job" value="'.$object->job.'">';
//           		print $object->job;
//             }
//             print '</td></tr>';

// 		    // Gender
//     		print '<tr><td>'.$langs->trans("Gender").'</td>';
//     		print '<td>';
//     		$arraygender=array('man'=>$langs->trans("Genderman"),'woman'=>$langs->trans("Genderwoman"));
//     		print $form->selectarray('gender', $arraygender, GETPOST('gender')?GETPOST('gender'):$object->gender, 1);
//     		print '</td></tr>';

//             // Login
//             print "<tr>".'<td><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
//             print '<td>';
//             if ($user->admin  && !$object->ldap_sid)
//             {
//                 print '<input size="12" maxlength="24" type="text" class="flat" name="login" value="'.$object->login.'">';
//             }
//             else
//             {
//                 print '<input type="hidden" name="login" value="'.$object->login.'">';
//                 print $object->login;
//             }
//             print '</td>';
//             print '</tr>';

//             // Pass
//             print '<tr><td>'.$langs->trans("Password").'</td>';
//             print '<td>';
//             if ($object->ldap_sid)
//             {
//                 $text=$langs->trans("DomainPassword");
//             }
//             else if ($caneditpassword)
//             {
//                 $text='<input size="12" maxlength="32" type="password" class="flat" name="password" value="'.$object->pass.'" autocomplete="off">';
//                 if ($dolibarr_main_authentication && $dolibarr_main_authentication == 'http')
//                 {
//                     $text=$form->textwithpicto($text,$langs->trans("DolibarrInHttpAuthenticationSoPasswordUseless",$dolibarr_main_authentication),1,'warning');
//                 }
//             }
//             else
//             {
//                 $text=preg_replace('/./i','*',$object->pass);
//             }
//             print $text;
//             print "</td></tr>\n";

//             // API key
//             if(! empty($conf->api->enabled) && $user->admin) {
//                 print '<tr><td>'.$langs->trans("ApiKey").'</td>';
//                 print '<td>';
//                 print '<input size="30" maxsize="32" type="text" id="api_key" name="api_key" value="'.$object->api_key.'" autocomplete="off">';
//                 if (! empty($conf->use_javascript_ajax))
//                     print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_api_key" class="linkobject"');
//                 print '</td></tr>';
//             }

//             // Administrator
//             print '<tr><td>'.$langs->trans("Administrator").'</td>';
//             if ($object->societe_id > 0)
//             {
//             	$langs->load("admin");
//                 print '<td>';
//                 print '<input type="hidden" name="admin" value="'.$object->admin.'">'.yn($object->admin);
//                 print ' ('.$langs->trans("ExternalUser").')';
//                 print '</td></tr>';
//             }
//             else
//             {
//                 print '<td>';
//                 $nbAdmin = $user->getNbOfUsers('active','',1);
//                 $nbSuperAdmin = $user->getNbOfUsers('active','superadmin',1);
//                 //var_dump($nbAdmin);
//                 //var_dump($nbSuperAdmin);
//                 if ($user->admin								// Need to be admin to allow downgrade of an admin
//                 && ($user->id != $object->id)                   // Don't downgrade ourself
//                 && (
//                 	(empty($conf->multicompany->enabled) && $nbAdmin > 1)
//                 	|| (! empty($conf->multicompany->enabled) && ($object->entity > 0 || $nbSuperAdmin > 1))    // Don't downgrade a superadmin if alone
//                 	)
//                 )
//                 {
//                     print $form->selectyesno('admin',$object->admin,1);

//                     if (! empty($conf->multicompany->enabled) && ! $user->entity && empty($conf->multicompany->transverse_mode))
//                     {
//                         if ($conf->use_javascript_ajax)
//                         {
//                             print '<script type="text/javascript">
// 									$(function() {
// 										var admin = $("select[name=admin]").val();
// 										if (admin == 0) {
// 											$("input[name=superadmin]")
// 													.prop("disabled", true)
// 													.prop("checked", false);
// 										}
// 										if ($("input[name=superadmin]").is(":checked")) {
// 											$("select[name=entity]")
// 													.prop("disabled", true);
// 										}
// 										$("select[name=admin]").change(function() {
// 											 if ( $(this).val() == 0 ) {
// 											 	$("input[name=superadmin]")
// 													.prop("disabled", true)
// 													.prop("checked", false);
// 											 	$("select[name=entity]")
// 													.prop("disabled", false);
// 											 } else {
// 											 	$("input[name=superadmin]")
// 													.prop("disabled", false);
// 											 }
// 										});
// 										$("input[name=superadmin]").change(function() {
// 											if ( $(this).is(":checked")) {
// 												$("select[name=entity]")
// 													.prop("disabled", true);
// 											} else {
// 												$("select[name=entity]")
// 													.prop("disabled", false);
// 											}
// 										});
// 									});
// 								</script>';
//                         }

//                         $checked=(($object->admin && ! $object->entity) ? ' checked' : '');
//                         print '<input type="checkbox" name="superadmin" value="1"'.$checked.' /> '.$langs->trans("SuperAdministrator");
//                     }
//                 }
//                 else
//                 {
//                     $yn = yn($object->admin);
//                     print '<input type="hidden" name="admin" value="'.$object->admin.'">';
//                     print '<input type="hidden" name="superadmin" value="'.(empty($object->entity) ? 1 : 0).'">';
//                     if (! empty($conf->multicompany->enabled) && empty($object->entity)) print $form->textwithpicto($yn,$langs->trans("DontDowngradeSuperAdmin"),1,'warning');
//                     else print $yn;
//                 }
//                 print '</td></tr>';
//             }

//            	// Type
//            	print '<tr><td width="25%">'.$langs->trans("Type").'</td>';
//            	print '<td>';
//            	if ($user->id == $object->id || ! $user->admin)
//            	{
// 	           	$type=$langs->trans("Internal");
//     	       	if ($object->societe_id) $type=$langs->trans("External");
//         	   	print $form->textwithpicto($type,$langs->trans("InternalExternalDesc"));
// 	           	if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
//            	}
//            	else
// 			{
// 				$type=0;
// 	            if ($object->contact_id) $type=$object->contact_id;
// 	            print $form->selectcontacts(0,$type,'contactid',2,'','',1,'',false,1);
// 	           	if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
//             }
//            	print '</td></tr>';

//             // Tel pro
//             print "<tr>".'<td>'.$langs->trans("PhonePro").'</td>';
//             print '<td>';
//             if ($caneditfield  && empty($object->ldap_sid))
//             {
//                 print '<input size="20" type="text" name="office_phone" class="flat" value="'.$object->office_phone.'">';
//             }
//             else
//             {
//                 print '<input type="hidden" name="office_phone" value="'.$object->office_phone.'">';
//                 print $object->office_phone;
//             }
//             print '</td></tr>';

//             // Tel mobile
//             print "<tr>".'<td>'.$langs->trans("PhoneMobile").'</td>';
//             print '<td>';
//             if ($caneditfield && empty($object->ldap_sid))
//             {
//                 print '<input size="20" type="text" name="user_mobile" class="flat" value="'.$object->user_mobile.'">';
//             }
//             else
//             {
//                 print '<input type="hidden" name="user_mobile" value="'.$object->user_mobile.'">';
//                 print $object->user_mobile;
//             }
//             print '</td></tr>';

//             // Fax
//             print "<tr>".'<td>'.$langs->trans("Fax").'</td>';
//             print '<td>';
//             if ($caneditfield  && empty($object->ldap_sid))
//             {
//                 print '<input size="20" type="text" name="office_fax" class="flat" value="'.$object->office_fax.'">';
//             }
//             else
//             {
//                 print '<input type="hidden" name="office_fax" value="'.$object->office_fax.'">';
//                 print $object->office_fax;
//             }
//             print '</td></tr>';

//             // Skype
//             if (! empty($conf->skype->enabled))
//             {
//                 print '<tr><td>'.$langs->trans("Skype").'</td>';
//                 print '<td>';
//                 if ($caneditfield  && empty($object->ldap_sid))
//                 {
//                     print '<input size="40" type="text" name="skype" class="flat" value="'.$object->skype.'">';
//                 }
//                 else
//                 {
//                     print '<input type="hidden" name="skype" value="'.$object->skype.'">';
//                     print $object->skype;
//                 }
//                 print '</td></tr>';
//             }

//             // EMail
//             print "<tr>".'<td'.(! empty($conf->global->USER_MAIL_REQUIRED)?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
//             print '<td>';
//             if ($caneditfield  && empty($object->ldap_sid))
//             {
//                 print '<input size="40" type="text" name="email" class="flat" value="'.$object->email.'">';
//             }
//             else
//             {
//                 print '<input type="hidden" name="email" value="'.$object->email.'">';
//                 print $object->email;
//             }
//             print '</td></tr>';

//             // Signature
//             print "<tr>".'<td class="tdtop">'.$langs->trans("Signature").'</td>';
//             print '<td>';
//             if ($caneditfield)
//             {
// 	            require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
// 	            $doleditor=new DolEditor('signature',$object->signature,'',138,'dolibarr_mailings','In',false,true,empty($conf->global->FCKEDITOR_ENABLE_USERSIGN)?0:1,ROWS_4,72);
// 	            print $doleditor->Create(1);
//             }
//             else
// 			{
//           		print dol_htmlentitiesbr($object->signature);
//             }
//             print '</td></tr>';

//             // OpenID url
//             if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER))
//             {
//                 print "<tr>".'<td>'.$langs->trans("OpenIDURL").'</td>';
//                 print '<td>';
//                 if ($caneditfield)
//                 {
//                     print '<input size="40" type="url" name="openid" class="flat" value="'.$object->openid.'">';
//                 }
//                 else
//               {
//                     print '<input type="hidden" name="openid" value="'.$object->openid.'">';
//                     print $object->openid;
//                 }
//                 print '</td></tr>';
//             }

//             // Hierarchy
//             print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
//             print '<td>';
//             if ($caneditfield)
//             {
//             	print $form->select_dolusers($object->fk_user,'fk_user',1,array($object->id),0,'',0,$object->entity);
//             }
//             else
//           {
//           		print '<input type="hidden" name="fk_user" value="'.$object->fk_user.'">';
//             	$huser=new User($db);
//             	$huser->fetch($object->fk_user);
//             	print $huser->getNomUrl(1);
//             }
//             print '</td>';
//             print "</tr>\n";

//             if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read))
//             {
//             	$langs->load("salaries");

//             	// THM
// 			    print '<tr><td>';
// 			    $text=$langs->trans("THM");
// 			    print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
// 			    print '</td>';
// 			    print '<td>';
// 			    print '<input size="8" type="text" name="thm" value="'.price2num(GETPOST('thm')?GETPOST('thm'):$object->thm).'">';
// 			    print '</td>';
// 			    print "</tr>\n";

// 			    // TJM
// 			    print '<tr><td>';
// 			    $text=$langs->trans("TJM");
// 			    print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classthm');
// 			    print '</td>';
// 			    print '<td>';
// 			    print '<input size="8" type="text" name="tjm" value="'.price2num(GETPOST('tjm')?GETPOST('tjm'):$object->tjm).'">';
// 			    print '</td>';
// 			    print "</tr>\n";

// 			    // Salary
// 			    print '<tr><td>'.$langs->trans("Salary").'</td>';
// 			    print '<td>';
// 			    print '<input size="8" type="text" name="salary" value="'.price2num(GETPOST('salary')?GETPOST('salary'):$object->salary).'">';
// 			    print '</td>';
// 			    print "</tr>\n";
//             }

// 		    // Weeklyhours
// 		    print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
// 		    print '<td>';
// 		    print '<input size="8" type="text" name="weeklyhours" value="'.price2num(GETPOST('weeklyhours')?GETPOST('weeklyhours'):$object->weeklyhours).'">';
// 		    print '</td>';
// 		    print "</tr>\n";

// 		    // Accountancy code
// 			if ($conf->salaries->enabled)
// 			{
// 				print "<tr>";
// 				print '<td>'.$langs->trans("AccountancyCode").'</td>';
// 				print '<td>';
// 				if ($caneditfield)
// 				{
// 					print '<input size="30" type="text" class="flat" name="accountancy_code" value="'.$object->accountancy_code.'">';
// 				}
// 				else
// 				{
// 					print '<input type="hidden" name="accountancy_code" value="'.$object->accountancy_code.'">';
// 					print $object->accountancy_code;
// 				}
// 				print '</td>';
// 				print "</tr>";
// 			}

// 			// User color
// 			if (! empty($conf->agenda->enabled))
//             {
// 				print '<tr><td>'.$langs->trans("ColorUser").'</td>';
// 				print '<td>';
// 				print $formother->selectColor(GETPOST('color')?GETPOST('color'):$object->color, 'color', null, 1, '', 'hideifnotset');
// 				print '</td></tr>';
// 			}

//             // Status
//             print '<tr><td>'.$langs->trans("Status").'</td>';
//             print '<td>';
//             print $object->getLibStatut(4);
//             print '</td></tr>';

//             // Company / Contact
//             if (! empty($conf->societe->enabled))
//             {
//                 print '<tr><td width="25%">'.$langs->trans("LinkToCompanyContact").'</td>';
//                 print '<td>';
//                 if ($object->societe_id > 0)
//                 {
//                     $societe = new Societe($db);
//                     $societe->fetch($object->societe_id);
//                     print $societe->getNomUrl(1,'');
//                     if ($object->contact_id)
//                     {
//                         $contact = new Contact($db);
//                         $contact->fetch($object->contact_id);
//                         print ' / <a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$object->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
//                     }
//                 }
//                 else
//                 {
//                     print $langs->trans("ThisUserIsNot");
//                 }
//                 print ' ('.$langs->trans("UseTypeFieldToChange").')';
//                 print '</td>';
//                 print "</tr>\n";
//             }

//             // Module Adherent
//             if (! empty($conf->adherent->enabled))
//             {
//                 $langs->load("members");
//                 print '<tr><td width="25%">'.$langs->trans("LinkedToDolibarrMember").'</td>';
//                 print '<td>';
//                 if ($object->fk_member)
//                 {
//                     $adh=new Adherent($db);
//                     $adh->fetch($object->fk_member);
//                     $adh->ref=$adh->login;	// Force to show login instead of id
//                     print $adh->getNomUrl(1);
//                 }
//                 else
//                 {
//                     print $langs->trans("UserNotLinkedToMember");
//                 }
//                 print '</td>';
//                 print "</tr>\n";
//             }

//             // Multicompany
//             // TODO check if user not linked with the current entity before change entity (thirdparty, invoice, etc.) !!
//             if (! empty($conf->multicompany->enabled) && is_object($mc))
//             {
//             	if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
//             	{
//             		print "<tr>".'<td>'.$langs->trans("Entity").'</td>';
//             		print "<td>".$mc->select_entities($object->entity, 'entity', '', 0, 1);		// last parameter 1 means, show also a choice 0=>'all entities'
//             		print "</td></tr>\n";
//             	}
//             	else
//             	{
//             		print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
//             	}
//             }

//             // Other attributes
//             $parameters=array('colspan' => ' colspan="2"');
//             $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
//             if (empty($reshook) && ! empty($extrafields->attribute_label))
//             {
//             	print $object->showOptionals($extrafields,'edit');
//             }

//             print '</table>';

//             dol_fiche_end();

//             print '<div align="center">';
//             print '<input value="'.$langs->trans("Save").'" class="button" type="submit" name="save">';
//             print '&nbsp; &nbsp; &nbsp;';
//             print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
//             print '</div>';

//             print '</form>';
//         }

// 		if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid)) $ldap->close;
//     }
// }


llxFooter();
$db->close();
