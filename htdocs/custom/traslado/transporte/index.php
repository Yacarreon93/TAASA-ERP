<?php

/**
 *      \file       htdocs/user/index.php
 *      \ingroup    core
 *      \brief      Page of users
 */

require '../../../main.inc.php';
if (! empty($conf->multicompany->enabled))
    dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');

require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/transporteDAO.php';


if (! $user->rights->user->user->lire && ! $user->admin)
    accessforbidden();

$langs->load("users");
$langs->load("companies");

// Security check (for external users)
$socid=0;
if ($user->societe_id > 0)
    $socid = $user->societe_id;

$sall=GETPOST('sall','alpha');
$search_user=GETPOST('search_user','alpha');
$search_login=GETPOST('search_login','alpha');
$search_lastname=GETPOST('search_lastname','alpha');
$search_firstname=GETPOST('search_firstname','alpha');
$search_statut=GETPOST('search_statut','alpha');
$search_thirdparty=GETPOST('search_thirdparty','alpha');

if ($search_statut == '') $search_statut='1';

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortfield) $sortfield="u.login";
if (! $sortorder) $sortorder="ASC";

$userstatic=new User($db);
$companystatic = new Societe($db);
$form = new Form($db);

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_user="";
    $search_login="";
    $search_lastname="";
    $search_firstname="";
    $search_statut="";
    $search_thirdparty="";
}


/*
 * View
 */

llxHeader('','Listado de transportes');

$buttonviewhierarchy='<form action="'.DOL_URL_ROOT.'/user/hierarchy.php'.(($search_statut != '' && $search_statut >= 0) ? '?search_statut='.$search_statut : '').'" method="POST"><input type="submit" class="button" style="width:120px" name="viewcal" value="'.dol_escape_htmltag($langs->trans("HierarchicView")).'"></form>';

print_fiche_titre("Transportes");

$transporteDao = new TransporteDAO($db);
$result = $transporteDao->GetTransportesResult();

// if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && (! empty($conf->multicompany->transverse_mode) || (! empty($user->admin) && empty($user->entity))))
// {
//     $sql.= " WHERE u.entity IS NOT NULL";
// }
// else
// {
//     $sql.= " WHERE u.entity IN (".getEntity('user',1).")";
// }
// if ($socid > 0) $sql.= " AND u.fk_soc = ".$socid;
// if ($search_user != '') $sql.=natural_search(array('u.login', 'u.lastname', 'u.firstname'), $search_user);
// if ($search_thirdparty != '') $sql.=natural_search(array('s.nom'), $search_thirdparty);
// if ($search_login != '') $sql.= natural_search("u.login", $search_login);
// if ($search_lastname != '') $sql.= natural_search("u.lastname", $search_lastname);
// if ($search_firstname != '') $sql.= natural_search("u.firstname", $search_firstname);
// if ($search_statut != '' && $search_statut >= 0) $sql.= " AND (u.statut=".$search_statut.")";
// if ($sall) $sql.= natural_search(array('u.login', 'u.lastname', 'u.firstname', 'u.email', 'u.note'), $sall);

// $sql.= " AND ef.rol = 1";

// $sql.=$db->order($sortfield,$sortorder);

//$result = $db->query($sql);
if ($result)
{
     $num = $db->num_rows($result);
     $i = 0;

    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

    $param="search_user=".$search_user."&sall=".$sall;
    $param.="&search_statut=".$search_statut;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Nombre"),$_SERVER['PHP_SELF'],"u.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Config Vehicular"),$_SERVER['PHP_SELF'],"u.lastname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Placas"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Año"),$_SERVER['PHP_SELF'],"u.lastname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Aseguradora"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Poliza"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
  
    print "</tr>\n";

//     // Search bar
//     $colspan=3;
//     if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode)) $colspan++;
//     print '<tr class="liste_titre">';
//     print '<td><input type="text" name="search_login" size="6" value="'.$search_login.'"></td>';
//     print '<td><input type="text" name="search_lastname" size="6" value="'.$search_lastname.'"></td>';
//     print '<td><input type="text" name="search_firstname" size="6" value="'.$search_firstname.'"></td>';
//     print '<td><input type="text" name="search_thirdparty" size="6" value="'.$search_thirdparty.'"></td>';
//     print '<td colspan="'.$colspan.'">&nbsp;</td>';

//     // Status
//     print '<td align="right">';
//     print $form->selectarray('search_statut', array('-1'=>'','0'=>$langs->trans('Disabled'),'1'=>$langs->trans('Enabled')),$search_statut);
//     print '</td>';

//     print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
//     print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
//     print '</td>';

//     print "</tr>\n";

//     $user2=new User($db);

//     $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        print '<tr>';
        print '<td>'.ucfirst($obj->nombre).'</td>';
        print '<td>'.ucfirst($obj->RFC).'</td>';
        print '<td>'.ucfirst($obj->num_licencia).'</td>';
        print '<td>&nbsp;</td>';
        print "</tr>\n";
        $i++;
    }
    print "</table>";
    print "</form>\n";
}
else
{
    dol_print_error($db);
}

llxFooter();

$db->close();
