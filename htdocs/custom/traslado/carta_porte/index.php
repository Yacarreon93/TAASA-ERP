<?php

/**
 *      \file       htdocs/user/index.php
 *      \ingroup    core
 *      \brief      Page of users
 */

require '../../../main.inc.php';
if (! empty($conf->multicompany->enabled))
    dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');

    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/cartaDAO.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/transporteDAO.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/operadorDAO.php';
    require_once DOL_DOCUMENT_ROOT.'/custom/traslado/dao/origenDAO.php';
    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';


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

llxHeader('','Listado de Carta Porte');

$buttonviewhierarchy='<form action="'.DOL_URL_ROOT.'/user/hierarchy.php'.(($search_statut != '' && $search_statut >= 0) ? '?search_statut='.$search_statut : '').'" method="POST"><input type="submit" class="button" style="width:120px" name="viewcal" value="'.dol_escape_htmltag($langs->trans("HierarchicView")).'"></form>';

print_fiche_titre("Carta Porte");

$cartaDAO = new CartaDAO($db);
$result = $cartaDAO->GetTrasladosResult();
$objectFacture = new Facture($db);
$transporteDAO = new TransporteDAO($db);
$operadorDAO = new OperadorDAO($db);
$origenDAO = new OrigenDAO($db);


if ($result)
{
     $num = $db->num_rows($result);
     $i = 0;

    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";

    $param="search_user=".$search_user."&sall=".$sall;
    $param.="&search_statut=".$search_statut;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Ref"),$_SERVER['PHP_SELF'],"u.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Factura Relacionada"),$_SERVER['PHP_SELF'],"u.login",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Ubicacion Origen"),$_SERVER['PHP_SELF'],"u.lastname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Cliente"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Transporte"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Operador"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Timbre"),$_SERVER['PHP_SELF'],"u.firstname",$param,"","",$sortfield,$sortorder);
  
    print "</tr>\n";

    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        if($obj->fk_facture) 
        {
            $objectFacture->fetch($obj->fk_facture);
            $soc = new Societe($db);
            $soc->fetch($objectFacture->socid);	
        }
        $ubicacion = $origenDAO->GetOrigenById($obj->fk_ubicacion_origen);
        $transporte = $transporteDAO->GetTransporteById($obj->fk_transporte);
        $operador =  $operadorDAO->GetOperadorById($obj->fk_operador);


        print '<tr>';
        print '<td><a href="/custom/traslado/carta_porte/card.php?id='.$obj->rowid.'">'.ucfirst($obj->rowid).'</a></td>';
        print '<td>'.ucfirst($obj->fk_facture).'</td>';
        print '<td>'.ucfirst($ubicacion->alias).'</td>';
        //print '<td>'.ucfirst($obj->fk_ubicacion_origen).'</td>';        
        print '<td>'.ucfirst($soc->nom).'</td>';
        // print '<td>'.ucfirst($obj->fk_transporte).'</td>';
        // print '<td>'.ucfirst($obj->fk_operador).'</td>';
        print '<td>'.ucfirst($transporte->nombre).'</td>';
        print '<td>'.ucfirst($operador->nombre).'</td>';
        print '<td>'.ucfirst($obj->UUID).'</td>';
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
