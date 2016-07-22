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
if($month_general != '') $month = $month_general;
if($year_general != '') $year = $year_general;
$day_lim    = GETPOST('day_lim','int');
$month_lim	= GETPOST('month_lim','int');
$year_lim	= GETPOST('year_lim','int');
$filtre	= GETPOST('filtre');

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

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('usercard','globalcard'));


/**
 * Actions
 */

if ($action == 'confirm_disable' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $object->fetch($id);
        $object->setstatus(0);
        header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        exit;
    }
}
if ($action == 'confirm_enable' && $confirm == "yes" && $candisableuser)
{
	$error = 0;

    if ($id <> $user->id)
    {
        $object->fetch($id);

        if (!empty($conf->file->main_limit_users))
        {
            $nb = $object->getNbOfUsers("active");
            if ($nb >= $conf->file->main_limit_users)
            {
	            $error++;
                setEventMessage($langs->trans("YourQuotaOfUsersIsReached"), 'errors');
            }
        }

        if (! $error)
        {
            $object->setstatus(1);
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
    }
}

if ($action == 'confirm_delete' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $object = new User($db);
        $object->id=$id;
        $result = $object->delete();
        if ($result < 0)
        {
            $langs->load("errors");
            setEventMessage($langs->trans("ErrorUserCannotBeDelete"), 'errors');
        }
        else
        {
            header("Location: index.php");
            exit;
        }
    }
}

// Action Add user
if ($action == 'add' && $canadduser)
{
	$error = 0;

    if (! $_POST["lastname"])
    {
	    $error++;
        setEventMessage($langs->trans("NameNotDefined"), 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["login"])
    {
	    $error++;
	    setEventMessage($langs->trans("LoginNotDefined"), 'errors');
        $action="create";       // Go back to create page
    }

    if (! empty($conf->file->main_limit_users)) // If option to limit users is set
    {
        $nb = $object->getNbOfUsers("active");
        if ($nb >= $conf->file->main_limit_users)
        {
	        $error++;
	        setEventMessage($langs->trans("YourQuotaOfUsersIsReached"), 'errors');
            $action="create";       // Go back to create page
        }
    }

    if (!$error)
    {
        $object->lastname		= GETPOST("lastname",'alpha');
        $object->firstname	    = GETPOST("firstname",'alpha');
        $object->login		    = GETPOST("login",'alpha');
        $object->api_key		= GETPOST("api_key",'alpha');
        $object->gender		    = GETPOST("gender",'alpha');
        $object->admin		    = GETPOST("admin",'alpha');
        $object->office_phone	= GETPOST("office_phone",'alpha');
        $object->office_fax	    = GETPOST("office_fax",'alpha');
        $object->user_mobile	= GETPOST("user_mobile");
        $object->skype          = GETPOST("skype");
        $object->email		    = GETPOST("email",'alpha');
        $object->job			= GETPOST("job",'alpha');
        $object->signature	    = GETPOST("signature");
        $object->accountancy_code = GETPOST("accountancy_code");
        $object->note			= GETPOST("note");
        $object->ldap_sid		= GETPOST("ldap_sid");
        $object->fk_user        = GETPOST("fk_user")>0?GETPOST("fk_user"):0;

        $object->thm            = GETPOST("thm")!=''?GETPOST("thm"):'';
        $object->tjm            = GETPOST("tjm")!=''?GETPOST("tjm"):'';
        $object->salary         = GETPOST("salary")!=''?GETPOST("salary"):'';
        $object->salaryextra    = GETPOST("salaryextra")!=''?GETPOST("salaryextra"):'';
        $object->weeklyhours    = GETPOST("weeklyhours")!=''?GETPOST("weeklyhours"):'';

		$object->color			= GETPOST("color")!=''?GETPOST("color"):'';

        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

        // Set entity property
        $entity=GETPOST('entity','int');
        if (! empty($conf->multicompany->enabled))
        {
        	if (! empty($_POST["superadmin"]))
        	{
        		$object->entity = 0;
        	}
        	else if ($conf->multicompany->transverse_mode)
        	{
        		$object->entity = 1; // all users are forced into master entity
        	}
        	else
        	{
        		$object->entity = ($entity == '' ? 1 : $entity);
        	}
        }
        else
		{
        	$object->entity = ($entity == '' ? 1 : $entity);
        	/*if ($user->admin && $user->entity == 0 && GETPOST("admin",'alpha'))
        	{
        	}*/
        }

        $db->begin();

        $id = $object->create($user);
        if ($id > 0)
        {
            if (isset($_POST['password']) && trim($_POST['password']))
            {
                $object->setPassword($user,trim($_POST['password']));
            }

            $db->commit();

            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
        else
        {
            $langs->load("errors");
            $db->rollback();
            if (is_array($object->errors) && count($object->errors)) setEventMessage($object->errors,'errors');
            else setEventMessage($object->error, 'errors');
            $action="create";       // Go back to create page
        }

    }
}

// Action add usergroup
if (($action == 'addgroup' || $action == 'removegroup') && $caneditfield)
{
    if ($group)
    {
        $editgroup = new UserGroup($db);
        $editgroup->fetch($group);
        $editgroup->oldcopy=clone($editgroup);

        $object->fetch($id);
        if ($action == 'addgroup')    $object->SetInGroup($group,($conf->multicompany->transverse_mode?GETPOST("entity"):$editgroup->entity));
        if ($action == 'removegroup') $object->RemoveFromGroup($group,($conf->multicompany->transverse_mode?GETPOST("entity"):$editgroup->entity));

        if ($result > 0)
        {
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
        else
        {
            setEventMessage($object->error, 'errors');
        }
    }
}

if ($action == 'update' && ! $_POST["cancel"])
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    if ($caneditfield)	// Case we can edit all field
    {
        $error=0;

    	if (! $_POST["lastname"])
        {
	        setEventMessage($langs->trans("NameNotDefined"), 'errors');
            $action="edit";       // Go back to create page
            $error++;
        }
        if (! $_POST["login"])
        {
	        setEventMessage($langs->trans("LoginNotDefined"), 'errors');
            $action="edit";       // Go back to create page
            $error++;
        }

        if (! $error)
        {
            $object->fetch($id);

            // Test if new login
            if (GETPOST("login") && GETPOST("login") != $object->login)
            {
				dol_syslog("New login ".$object->login." is requested. We test it does not exists.");
				$tmpuser=new User($db);
				$result=$tmpuser->fetch(0, GETPOST("login"));
				if ($result > 0)
				{
					setEventMessage($langs->trans("ErrorLoginAlreadyExists", GETPOST('login')), 'errors');
					$action="edit";       // Go back to create page
					$error++;
				}
            }
       }

       if (! $error)
       {
            $db->begin();

            $object->oldcopy=clone($object);

            $object->lastname	= GETPOST("lastname",'alpha');
            $object->firstname	= GETPOST("firstname",'alpha');
            $object->login		= GETPOST("login",'alpha');
            $object->gender		= GETPOST("gender",'alpha');
            $object->pass		= GETPOST("password");
            $object->api_key    = (GETPOST("api_key", 'alpha'))?GETPOST("api_key", 'alpha'):$object->api_key;
            $object->admin		= empty($user->admin)?0:GETPOST("admin"); // A user can only be set admin by an admin
            $object->office_phone=GETPOST("office_phone",'alpha');
            $object->office_fax	= GETPOST("office_fax",'alpha');
            $object->user_mobile= GETPOST("user_mobile");
            $object->skype    	= GETPOST("skype");
            $object->email		= GETPOST("email",'alpha');
            $object->job		= GETPOST("job",'alpha');
            $object->signature	= GETPOST("signature");
            $object->accountancy_code	= GETPOST("accountancy_code");
            $object->openid		= GETPOST("openid");
            $object->fk_user    = GETPOST("fk_user")>0?GETPOST("fk_user"):0;

	        $object->thm            = GETPOST("thm")!=''?GETPOST("thm"):'';
	        $object->tjm            = GETPOST("tjm")!=''?GETPOST("tjm"):'';
	        $object->salary         = GETPOST("salary")!=''?GETPOST("salary"):'';
	        $object->salaryextra    = GETPOST("salaryextra")!=''?GETPOST("salaryextra"):'';
	        $object->weeklyhours    = GETPOST("weeklyhours")!=''?GETPOST("weeklyhours"):'';

			$object->color    	= GETPOST("color")!=''?GETPOST("color"):'';

            // Fill array 'array_options' with data from add form
        	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0) $error++;

            if (! empty($conf->multicompany->enabled))
            {
            	if (! empty($_POST["superadmin"]))
            	{
            		$object->entity = 0;
            	}
            	else if ($conf->multicompany->transverse_mode)
            	{
            		$object->entity = 1; // all users in master entity
            	}
            	else
            	{
            		$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
            	}
            }
            else
            {
            	$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
            }

            if (GETPOST('deletephoto')) $object->photo='';
            if (! empty($_FILES['photo']['name'])) $object->photo = dol_sanitizeFileName($_FILES['photo']['name']);

            if (! $error)
            {
	            $ret=$object->update($user);
	            if ($ret < 0)
	            {
	            	$error++;
	                if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
	                {
	                    $langs->load("errors");
		                setEventMessage($langs->trans("ErrorLoginAlreadyExists",$object->login), 'errors');
	                }
	                else
	              {
		            	setEventMessages($object->error, $object->errors, 'errors');
	                }
	            }
            }

            if (! $error && isset($_POST['contactid']))
            {
            	$contactid=GETPOST('contactid');

            	if ($contactid > 0)
            	{
	            	$contact=new Contact($db);
	            	$contact->fetch($contactid);

	            	$sql = "UPDATE ".MAIN_DB_PREFIX."user";
	            	$sql.= " SET fk_socpeople=".$db->escape($contactid);
	            	if ($contact->socid) $sql.=", fk_soc=".$db->escape($contact->socid);
	            	$sql.= " WHERE rowid=".$object->id;
            	}
            	else
            	{
            		$sql = "UPDATE ".MAIN_DB_PREFIX."user";
            		$sql.= " SET fk_socpeople=NULL, fk_soc=NULL";
            		$sql.= " WHERE rowid=".$object->id;
            	}
	            dol_syslog("fiche::update", LOG_DEBUG);
            	$resql=$db->query($sql);
            	if (! $resql)
            	{
            		$error++;
            		setEventMessage($db->lasterror(), 'errors');
            	}
            }

            if (! $error && ! count($object->errors))
            {
                if (GETPOST('deletephoto') && $object->photo)
                {
                    $fileimg=$conf->user->dir_output.'/'.get_exdir($object->id,2,0,1,$object,'user').'/logos/'.$object->photo;
                    $dirthumbs=$conf->user->dir_output.'/'.get_exdir($object->id,2,0,1,$object,'user').'/logos/thumbs';
                    dol_delete_file($fileimg);
                    dol_delete_dir_recursive($dirthumbs);
                }

                if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name']))
                {
                    $dir= $conf->user->dir_output . '/' . get_exdir($object->id,2,0,1,$object,'user');

                    dol_mkdir($dir);

                    if (@is_dir($dir))
                    {
                        $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                        $result=dol_move_uploaded_file($_FILES['photo']['tmp_name'],$newfile,1,0,$_FILES['photo']['error']);

                        if (! $result > 0)
                        {
	                        setEventMessage($langs->trans("ErrorFailedToSaveFile"), 'errors');
                        }
                        else
                        {
                            // Create small thumbs for company (Ratio is near 16/9)
                            // Used on logon for example
                            $imgThumbSmall = vignette($newfile, $maxwidthsmall, $maxheightsmall, '_small', $quality);

                            // Create mini thumbs for company (Ratio is near 16/9)
                            // Used on menu or for setup page for example
                            $imgThumbMini = vignette($newfile, $maxwidthmini, $maxheightmini, '_mini', $quality);
                        }
                    }
                    else
                    {
                    	$error++;
                    	$langs->load("errors");
                    	setEventMessages($langs->transnoentitiesnoconv("ErrorFailedToCreateDir", $dir), $mesgs, 'errors');
                    }
                }
            }

            if (! $error && ! count($object->errors))
            {
	            setEventMessage($langs->trans("UserModified"));
                $db->commit();

                $login=$_SESSION["dol_login"];
                if ($login && $login == $object->oldcopy->login && $object->oldcopy->login != $object->login)	// Current user has changed its login
                {
                	$_SESSION["dol_login"]=$object->login;	// Set new login to avoid disconnect at next page
                }
            }
            else
            {
                $db->rollback();
            }
        }
    }
    else if ($caneditpassword)	// Case we can edit only password
    {
        $object->fetch($id);

        $object->oldcopy=clone($object);

        $ret=$object->setPassword($user,$_POST["password"]);
        if ($ret < 0)
        {
	        setEventMessage($object->error, 'errors');
        }
    }
}

// Change password with a new generated one
if ((($action == 'confirm_password' && $confirm == 'yes')
|| ($action == 'confirm_passwordsend' && $confirm == 'yes')) && $caneditpassword)
{
    $object->fetch($id);

    $newpassword=$object->setPassword($user,'');
    if ($newpassword < 0)
    {
        // Echec
        setEventMessage($langs->trans("ErrorFailedToSetNewPassword"), 'errors');
    }
    else
    {
        // Succes
        if ($action == 'confirm_passwordsend' && $confirm == 'yes')
        {
            if ($object->send_password($user,$newpassword) > 0)
            {
                setEventMessage($langs->trans("PasswordChangedAndSentTo",$object->email));
            }
            else
            {
	            setEventMessage($object->error, 'errors');
            }
        }
        else
        {
	        setEventMessage($langs->trans("PasswordChangedTo",$newpassword), 'errors');
        }
    }
}

// Action initialisation donnees depuis record LDAP
if ($action == 'adduserldap')
{
    $selecteduser = $_POST['users'];

    $required_fields = array(
	$conf->global->LDAP_KEY_USERS,
    $conf->global->LDAP_FIELD_NAME,
    $conf->global->LDAP_FIELD_FIRSTNAME,
    $conf->global->LDAP_FIELD_LOGIN,
    $conf->global->LDAP_FIELD_LOGIN_SAMBA,
    $conf->global->LDAP_FIELD_PASSWORD,
    $conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
    $conf->global->LDAP_FIELD_PHONE,
    $conf->global->LDAP_FIELD_FAX,
    $conf->global->LDAP_FIELD_MOBILE,
    $conf->global->LDAP_FIELD_SKYPE,
    $conf->global->LDAP_FIELD_MAIL,
    $conf->global->LDAP_FIELD_TITLE,
	$conf->global->LDAP_FIELD_DESCRIPTION,
    $conf->global->LDAP_FIELD_SID);

    $ldap = new Ldap();
    $result = $ldap->connect_bind();
    if ($result >= 0)
    {
        // Remove from required_fields all entries not configured in LDAP (empty) and duplicated
        $required_fields=array_unique(array_values(array_filter($required_fields, "dol_validElement")));

        $ldapusers = $ldap->getRecords($selecteduser, $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, $required_fields);
        //print_r($ldapusers);

        if (is_array($ldapusers))
        {
            foreach ($ldapusers as $key => $attribute)
            {
                $ldap_lastname		= $attribute[$conf->global->LDAP_FIELD_NAME];
                $ldap_firstname		= $attribute[$conf->global->LDAP_FIELD_FIRSTNAME];
                $ldap_login			= $attribute[$conf->global->LDAP_FIELD_LOGIN];
                $ldap_loginsmb		= $attribute[$conf->global->LDAP_FIELD_LOGIN_SAMBA];
                $ldap_pass			= $attribute[$conf->global->LDAP_FIELD_PASSWORD];
                $ldap_pass_crypted	= $attribute[$conf->global->LDAP_FIELD_PASSWORD_CRYPTED];
                $ldap_phone			= $attribute[$conf->global->LDAP_FIELD_PHONE];
                $ldap_fax			= $attribute[$conf->global->LDAP_FIELD_FAX];
                $ldap_mobile		= $attribute[$conf->global->LDAP_FIELD_MOBILE];
                $ldap_skype			= $attribute[$conf->global->LDAP_FIELD_SKYPE];
                $ldap_mail			= $attribute[$conf->global->LDAP_FIELD_MAIL];
                $ldap_sid			= $attribute[$conf->global->LDAP_FIELD_SID];
            }
        }
    }
    else
    {
        setEventMessage($ldap->error, 'errors');
    }
}



/*
 * View
 */

$form = new Form($db);
$formother=new FormOther($db);

llxHeader('',$langs->trans("UserCard"));

if (($action == 'create') || ($action == 'adduserldap'))
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Affichage fiche en mode creation                                           */
    /*                                                                            */
    /* ************************************************************************** */

    print_fiche_titre($langs->trans("NewUser"));

    print $langs->trans("CreateInternalUserDesc")."<br>\n";
    print "<br>";


    if (! empty($conf->ldap->enabled) && (isset($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr'))
    {
        /*
         * Affiche formulaire d'ajout d'un compte depuis LDAP
         * si on est en synchro LDAP vers Dolibarr
         */

        $ldap = new Ldap();
        $result = $ldap->connect_bind();
        if ($result >= 0)
        {
            $required_fields=array(
				$conf->global->LDAP_KEY_USERS,
	            $conf->global->LDAP_FIELD_FULLNAME,
				$conf->global->LDAP_FIELD_NAME,
				$conf->global->LDAP_FIELD_FIRSTNAME,
				$conf->global->LDAP_FIELD_LOGIN,
				$conf->global->LDAP_FIELD_LOGIN_SAMBA,
				$conf->global->LDAP_FIELD_PASSWORD,
				$conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
				$conf->global->LDAP_FIELD_PHONE,
				$conf->global->LDAP_FIELD_FAX,
				$conf->global->LDAP_FIELD_MOBILE,
				$conf->global->LDAP_FIELD_SKYPE,
				$conf->global->LDAP_FIELD_MAIL,
				$conf->global->LDAP_FIELD_TITLE,
				$conf->global->LDAP_FIELD_DESCRIPTION,
            	$conf->global->LDAP_FIELD_SID
            );

            // Remove from required_fields all entries not configured in LDAP (empty) and duplicated
            $required_fields=array_unique(array_values(array_filter($required_fields, "dol_validElement")));

            // Get from LDAP database an array of results
            $ldapusers = $ldap->getRecords('*', $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, $required_fields, 1);

            if (is_array($ldapusers))
            {
                $liste=array();
                foreach ($ldapusers as $key => $ldapuser)
                {
                    // Define the label string for this user
                    $label='';
                    foreach ($required_fields as $value)
                    {
                        if ($value)
                        {
                            $label.=$value."=".$ldapuser[$value]." ";
                        }
                    }
                    $liste[$key] = $label;
                }

            }
            else
            {
                setEventMessage($ldap->error, 'errors');
            }
        }
        else
        {
	        setEventMessage($ldap->error, 'errors');
        }

        // If user list is full, we show drop-down list
       	print "\n\n<!-- Form liste LDAP debut -->\n";

       	print '<form name="add_user_ldap" action="'.$_SERVER["PHP_SELF"].'" method="post">';
       	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
       	print '<table width="100%" class="border"><tr>';
       	print '<td width="160">';
       	print $langs->trans("LDAPUsers");
       	print '</td>';
       	print '<td>';
       	print '<input type="hidden" name="action" value="adduserldap">';
        if (is_array($liste) && count($liste))
        {
        	print $form->selectarray('users', $liste, '', 1);
        }
       	print '</td><td align="center">';
       	print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Get')).'"'.(count($liste)?'':' disabled').'>';
       	print '</td></tr></table>';
       	print '</form>';

       	print "\n<!-- Form liste LDAP fin -->\n\n";
       	print '<br>';
    }


    print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createuser">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    if (! empty($ldap_sid)) print '<input type="hidden" name="ldap_sid" value="'.dol_escape_htmltag($ldap_sid).'">';
    print '<input type="hidden" name="entity" value="'.$conf->entity.'">';

    dol_fiche_head('', '', '', 0, '');

    print dol_set_focus('#lastname');

    print '<table class="border" width="100%">';

    print '<tr>';

    // Lastname
    print '<td width="160"><span class="fieldrequired">'.$langs->trans("Lastname").'</span></td>';
    print '<td>';
    if (! empty($ldap_lastname))
    {
        print '<input type="hidden" id="lastname" name="lastname" value="'.$ldap_lastname.'">';
        print $ldap_lastname;
    }
    else
    {
        print '<input size="30" type="text" id="lastname" name="lastname" value="'.GETPOST('lastname').'">';
    }
    print '</td></tr>';

    // Firstname
    print '<tr><td>'.$langs->trans("Firstname").'</td>';
    print '<td>';
    if (! empty($ldap_firstname))
    {
        print '<input type="hidden" name="firstname" value="'.$ldap_firstname.'">';
        print $ldap_firstname;
    }
    else
    {
        print '<input size="30" type="text" name="firstname" value="'.GETPOST('firstname').'">';
    }
    print '</td></tr>';

    // Position/Job
    print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
    print '<td>';
    print '<input size="30" type="text" name="job" value="'.GETPOST('job').'">';
    print '</td></tr>';

    // Gender
    print '<tr><td>'.$langs->trans("Gender").'</td>';
    print '<td>';
    $arraygender=array('man'=>$langs->trans("Genderman"),'woman'=>$langs->trans("Genderwoman"));
    print $form->selectarray('gender', $arraygender, GETPOST('gender'), 1);
    print '</td></tr>';

    // Login
    print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
    print '<td>';
    if (! empty($ldap_login))
    {
        print '<input type="hidden" name="login" value="'.$ldap_login.'">';
        print $ldap_login;
    }
    elseif (! empty($ldap_loginsmb))
    {
        print '<input type="hidden" name="login" value="'.$ldap_loginsmb.'">';
        print $ldap_loginsmb;
    }
    else
    {
        print '<input size="20" maxsize="24" type="text" name="login" value="'.GETPOST('login').'">';
    }
    print '</td></tr>';

    $generated_password='';
    if (empty($ldap_sid))    // ldap_sid is for activedirectory
    {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
        $generated_password=getRandomPassword(false);
    }
    $password=$generated_password;

    // Password
    print '<tr><td class="fieldrequired">'.$langs->trans("Password").'</td>';
    print '<td>';
    if (! empty($ldap_sid))
    {
        print 'Mot de passe du domaine';
    }
    else
    {
        if (! empty($ldap_pass))
        {
            print '<input type="hidden" name="password" value="'.$ldap_pass.'">';
            print preg_replace('/./i','*',$ldap_pass);
        }
        else
        {
            // We do not use a field password but a field text to show new password to use.
            print '<input size="30" maxsize="32" type="text" name="password" value="'.$password.'" autocomplete="off">';
        }
    }
    print '</td></tr>';

    if(! empty($conf->api->enabled))
    {
        // API key
        $generated_api_key = '';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
            $generated_password=getRandomPassword(false);
        print '<tr><td>'.$langs->trans("ApiKey").'</td>';
        print '<td>';
        print '<input size="30" maxsize="32" type="text" id="api_key" name="api_key" value="'.$api_key.'" autocomplete="off">';
        if (! empty($conf->use_javascript_ajax))
            print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_api_key" class="linkobject"');
        print '</td></tr>';
    }
    else
    {
        // PARTIAL WORKAROUND
        $generated_fake_api_key=getRandomPassword(false);
        print '<input type="hidden" name="api_key" value="'.$generated_fake_api_key.'">';
    }

    // Administrator
    if (! empty($user->admin))
    {
        print '<tr><td>'.$langs->trans("Administrator").'</td>';
        print '<td>';
        print $form->selectyesno('admin',GETPOST('admin'),1);

        if (! empty($conf->multicompany->enabled) && ! $user->entity && empty($conf->multicompany->transverse_mode))
        {
            if (! empty($conf->use_javascript_ajax))
            {
                print '<script type="text/javascript">
                            $(function() {
                                $("select[name=admin]").change(function() {
                                     if ( $(this).val() == 0 ) {
                                        $("input[name=superadmin]")
                                            .prop("disabled", true)
                                            .prop("checked", false);
                                        $("select[name=entity]")
                                            .prop("disabled", false);
                                     } else {
                                        $("input[name=superadmin]")
                                            .prop("disabled", false);
                                     }
                                });
                                $("input[name=superadmin]").change(function() {
                                    if ( $(this).is(":checked") ) {
                                        $("select[name=entity]")
                                            .prop("disabled", true);
                                    } else {
                                        $("select[name=entity]")
                                            .prop("disabled", false);
                                    }
                                });
                            });
                    </script>';
            }
            $checked=($_POST["superadmin"]?' checked':'');
            $disabled=($_POST["superadmin"]?'':' disabled');
            print '<input type="checkbox" name="superadmin" value="1"'.$checked.$disabled.' /> '.$langs->trans("SuperAdministrator");
        }
        print "</td></tr>\n";
    }

    // Type
    print '<tr><td>'.$langs->trans("Type").'</td>';
    print '<td>';
    print $form->textwithpicto($langs->trans("Internal"),$langs->trans("InternalExternalDesc"), 1, 'help', '', 0, 2);
    print '</td></tr>';

    // Tel
    print '<tr><td>'.$langs->trans("PhonePro").'</td>';
    print '<td>';
    if (! empty($ldap_phone))
    {
        print '<input type="hidden" name="office_phone" value="'.$ldap_phone.'">';
        print $ldap_phone;
    }
    else
    {
        print '<input size="20" type="text" name="office_phone" value="'.GETPOST('office_phone').'">';
    }
    print '</td></tr>';

    // Tel portable
    print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
    print '<td>';
    if (! empty($ldap_mobile))
    {
        print '<input type="hidden" name="user_mobile" value="'.$ldap_mobile.'">';
        print $ldap_mobile;
    }
    else
    {
        print '<input size="20" type="text" name="user_mobile" value="'.GETPOST('user_mobile').'">';
    }
    print '</td></tr>';

    // Fax
    print '<tr><td>'.$langs->trans("Fax").'</td>';
    print '<td>';
    if (! empty($ldap_fax))
    {
        print '<input type="hidden" name="office_fax" value="'.$ldap_fax.'">';
        print $ldap_fax;
    }
    else
    {
        print '<input size="20" type="text" name="office_fax" value="'.GETPOST('office_fax').'">';
    }
    print '</td></tr>';

    // Skype
    if (! empty($conf->skype->enabled))
    {
        print '<tr><td>'.$langs->trans("Skype").'</td>';
        print '<td>';
        if (! empty($ldap_skype))
        {
            print '<input type="hidden" name="skype" value="'.$ldap_skype.'">';
            print $ldap_skype;
        }
        else
        {
            print '<input size="40" type="text" name="skype" value="'.GETPOST('skype').'">';
        }
        print '</td></tr>';
    }

    // EMail
    print '<tr><td'.(! empty($conf->global->USER_MAIL_REQUIRED)?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
    print '<td>';
    if (! empty($ldap_mail))
    {
        print '<input type="hidden" name="email" value="'.$ldap_mail.'">';
        print $ldap_mail;
    }
    else
    {
        print '<input size="40" type="text" name="email" value="'.GETPOST('email').'">';
    }
    print '</td></tr>';

    // Signature
    print '<tr><td class="tdtop">'.$langs->trans("Signature").'</td>';
    print '<td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('signature',GETPOST('signature'),'',138,'dolibarr_mailings','In',true,true,empty($conf->global->FCKEDITOR_ENABLE_USERSIGN)?0:1,ROWS_4,90);
    print $doleditor->Create(1);
    print '</td></tr>';

    // Multicompany
    if (! empty($conf->multicompany->enabled))
    {
        if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity && is_object($mc))
        {
            print "<tr>".'<td>'.$langs->trans("Entity").'</td>';
            print "<td>".$mc->select_entities($conf->entity);
            print "</td></tr>\n";
        }
        else
        {
            print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
        }
    }

    // Hierarchy
    print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
    print '<td>';
    print $form->select_dolusers($object->fk_user,'fk_user',1,array($object->id),0,'',0,$conf->entity);
    print '</td>';
    print "</tr>\n";

	if ($conf->salaries->enabled && ! empty($user->rights->salaries->read))
	{
		$langs->load("salaries");

	    // THM
	    print '<tr><td>'.$langs->trans("THM").'</td>';
	    print '<td>';
	    print '<input size="8" type="text" name="thm" value="'.GETPOST('thm').'">';
	    print '</td>';
	    print "</tr>\n";

	    // TJM
	    print '<tr><td>'.$langs->trans("TJM").'</td>';
	    print '<td>';
	    print '<input size="8" type="text" name="tjm" value="'.GETPOST('tjm').'">';
	    print '</td>';
	    print "</tr>\n";

	    // Salary
	    print '<tr><td>'.$langs->trans("Salary").'</td>';
	    print '<td>';
	    print '<input size="8" type="text" name="salary" value="'.GETPOST('salary').'">';
	    print '</td>';
	    print "</tr>\n";
	}

    // Weeklyhours
    print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
    print '<td>';
    print '<input size="8" type="text" name="weeklyhours" value="'.GETPOST('weeklyhours').'">';
    print '</td>';
    print "</tr>\n";

	// Accountancy code
	if ($conf->salaries->enabled)
	{
		print '<tr><td>'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		print '<input size="30" type="text" name="accountancy_code" value="'.GETPOST('accountancy_code').'">';
		print '</td></tr>';
	}

	// User color
	if (! empty($conf->agenda->enabled))
	{
		print '<tr><td>'.$langs->trans("ColorUser").'</td>';
		print '<td>';
		print $formother->selectColor(GETPOST('color')?GETPOST('color'):$object->color, 'color', null, 1, '', 'hideifnotset');
		print '</td></tr>';
	}

    // Note
    print '<tr><td class="tdtop">';
    print $langs->trans("Note");
    print '</td><td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('note','','',180,'dolibarr_notes','',false,true,$conf->global->FCKEDITOR_ENABLE_SOCIETE,ROWS_4,90);
    $doleditor->Create();
    print "</td></tr>\n";

    // Other attributes
    $parameters=array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
    	print $object->showOptionals($extrafields,'edit');
    }

 	print "</table>\n";

 	dol_fiche_end();

    print '<div align="center">';
    print '<input class="button" value="'.$langs->trans("CreateUser").'" name="create" type="submit">';
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
        $object->fetch($id);
        if ($res < 0) { dol_print_error($db,$object->error); exit; }
        $res=$object->fetch_optionals($object->id,$extralabels);

        // Connexion ldap
        // pour recuperer passDoNotExpire et userChangePassNextLogon
        if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid))
        {
            $ldap = new Ldap();
            $result=$ldap->connect_bind();
            if ($result > 0)
            {
                $userSearchFilter = '('.$conf->global->LDAP_FILTER_CONNECTION.'('.$ldap->getUserIdentifier().'='.$object->login.'))';
                $entries = $ldap->fetch($object->login,$userSearchFilter);
                if (! $entries)
                {
                    setEventMessage($ldap->error, 'errors');
                }

                $passDoNotExpire = 0;
                $userChangePassNextLogon = 0;
                $userDisabled = 0;
                $statutUACF = '';

                // Check options of user account
                if (count($ldap->uacf) > 0)
                {
                    foreach ($ldap->uacf as $key => $statut)
                    {
                        if ($key == 65536)
                        {
                            $passDoNotExpire = 1;
                            $statutUACF = $statut;
                        }
                    }
                }
                else
                {
                    $userDisabled = 1;
                    $statutUACF = "ACCOUNTDISABLE";
                }

                if ($ldap->pwdlastset == 0)
                {
                    $userChangePassNextLogon = 1;
                }
            }
        }

        // Show tabs
        $head = user_prepare_head_for_vendors($object);
        $title = "Vendedores";

        /*
         * Confirmation reinitialisation mot de passe
         */
        if ($action == 'password')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("ReinitPassword"),$langs->trans("ConfirmReinitPassword",$object->login),"confirm_password", '', 0, 1);
        }

        /*
         * Confirmation envoi mot de passe
         */
        if ($action == 'passwordsend')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("SendNewPassword"),$langs->trans("ConfirmSendNewPassword",$object->login),"confirm_passwordsend", '', 0, 1);
        }

        /*
         * Confirm deactivation
         */
        if ($action == 'disable')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("DisableAUser"),$langs->trans("ConfirmDisableUser",$object->login),"confirm_disable", '', 0, 1);
        }

        /*
         * Confirm activation
         */
        if ($action == 'enable')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("EnableAUser"),$langs->trans("ConfirmEnableUser",$object->login),"confirm_enable", '', 0, 1);
        }

        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("DeleteAUser"),$langs->trans("ConfirmDeleteUser",$object->login),"confirm_delete", '', 0, 1);
        }  

        /*
         * Fiche en mode visu
         */
        if ($action != 'edit')
        {
			dol_fiche_head($head, 'user', $title, 0, 'user');

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
			//print $sql;
            //die();

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
			    print_barre_liste("Facturas a clientes".' '.($socid?' '.$soc->name:''),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_accountancy.png');

			    $i = 0;
			    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">'."\n";

                echo "<input type='hidden' name='id' value='".$id."'>";

                echo '<div style="margin-bottom: 10px">';
                echo '<input type="radio" name="range" value="all" style="margin-right: 3px" checked>Todas las facturas';
                echo '<input type="radio" id="monthly" name="range" value="monthly" style="margin-left:10px; margin-right: 3px">Facturas por mes';
                echo '<div style="display:inline-block">';
                echo '<select id="selectMonth" onchange="setMonthValue()">
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
                echo '<input type="hidden" value="1" name="month_general" id="month_general">';
                //echo '<input class="flat" type="text" size="1" maxlength="2" name="month_general" value="'.$month.'" style="margin-left:10px;">';
                $formother->select_year($year?$year:-1,'year_general',1, 20, 5);
                echo '<input type="submit" class="button" value="Buscar">';
                echo '</div>';
                echo '<input type="radio" id="weekly" name="range" value="weekly" style="margin-left:10px; margin-right: 3px">Facturas por semana';
                echo '<div style="display:inline-block">';
                echo '<input class="flat" type="text" size="1" maxlength="2" name="month_general" value="'.$month.'" style="margin-left:10px;">';
                echo '<input class="flat" type="text" size="1" maxlength="2" name="week_general" value="'.$week.'">';
                $formother->select_year($year?$year:-1,'year_general',1, 20, 5);
                echo '<input type="submit" class="button" value="Buscar">';
                echo '</div>';
                echo '</div>';

                echo    '<script>
                            function setMonthValue() {
                            var x = document.getElementById("selectMonth").value;
                            document.getElementById("month_general").value = x;
                            };
                        </script>';

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
				print_liste_field_titre($langs->trans('RefCustomer'),$_SERVER["PHP_SELF"],'f.ref_client','',$param,'',$sortfield,$sortorder);
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
				print '<td class="liste_titre">';
				print '<input class="flat" size="6" type="text" name="search_refcustomer" value="'.$search_refcustomer.'">';
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

			        while ($i < min($num,$limit))
			        {
			            $objp = $db->fetch_object($resql);
			            $var=!$var;

			            $datelimit=$db->jdate($objp->datelimite);

			            print '<tr '.$bc[$var].'>';
			            print '<td class="nowrap">';

			            $facturestatic->id=$objp->facid;
			            $facturestatic->ref=$objp->facnumber;
			            $facturestatic->type=$objp->type;
			            $notetoshow=dol_string_nohtmltag(($user->societe_id>0?$objp->note_public:$objp->note),1);
			            $paiement = $facturestatic->getSommePaiement();

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

						// Customer ref
						print '<td class="nowrap">';
						print $objp->ref_client;
						print '</td>';

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
			    print "</form>\n";
			    $db->free($resql);
			}
			else
			{
			    dol_print_error($db);
			}         	          
           
        }
	}	
}

llxFooter();
$db->close();
