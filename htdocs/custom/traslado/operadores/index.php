<?php

/**
 *      \file       htdocs/user/index.php
 *      \ingroup    core
 *      \brief      Page of users
 */

require '../../../main.inc.php';
if (! empty($conf->multicompany->enabled))
    dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');

require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/operadorDAO.php';


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

llxHeader('','Listado de operadores');

$buttonviewhierarchy='<form action="'.DOL_URL_ROOT.'/user/hierarchy.php'.(($search_statut != '' && $search_statut >= 0) ? '?search_statut='.$search_statut : '').'" method="POST"><input type="submit" class="button" style="width:120px" name="viewcal" value="'.dol_escape_htmltag($langs->trans("HierarchicView")).'"></form>';

print_fiche_titre("Operadores");

$operadorDao = new OperadorDAO($db);
$result = $operadorDao->GetOperadoresResult();

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
    print_liste_field_titre($langs->trans("RFC"),$_SERVER['PHP_SELF'],"u.lastname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Licencia"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
  
    print "</tr>\n";

    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        print '<tr>';
        print '<td><a href="/custom/traslado/operadores/card.php?id='.$obj->rowid.'">'.ucfirst($obj->nombre).'</a></td>';
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
