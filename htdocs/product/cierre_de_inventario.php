<?php

/**
 *  \file       htdocs/product/list.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("products");
$langs->load("stocks");
$langs->load("suppliers");

$action = GETPOST('action');
$page = GETPOST('page', 'int');

$limit = $conf->liste_limit;

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas=GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db,$action);
    $objcanvas->getCanvas('product','list',$canvas);
}

// Security check
if ($type=='0') $result=restrictedArea($user,'produit','','','','','',$objcanvas);
else if ($type=='1') $result=restrictedArea($user,'service','','','','','',$objcanvas);
else $result=restrictedArea($user,'produit|service','','','','','',$objcanvas);


/*
 * Actions
 */


/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);
    
$title = 'Cierre de inventario';

// TEMP:
$page = 2;
$stock_id = 1;

$numero_de_columnas = 3;
$productos_por_columna = 10;
$limit = $productos_por_columna * $numero_de_columnas;
$offset = $limit * ($page - 1);

$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label';
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps ON ps.fk_product = p.rowid';
$sql.= ' WHERE fk_entrepot = '.$stock_id;
$sql.= $db->plimit($limit, $offset);

// echo $sql; die;

$resql = $db->query($sql);
$product_static = new Product($db);

if ($resql)
{
    llxHeader('', $title, '', '');
    
    $num = $db->num_rows($resql);
    $count = 0;

    // print_barre_liste($texte, $page, "list.php", $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords,'title_products.png');
    
    print '<div style="display:flex;">';

    for ($c = 0; $c < $numero_de_columnas; $c++)
    {
        print '<div style="flex-grow:1; '.($c==0?'margin-right:5px':($c==$numero_de_columnas-1?'margin-left:5px':'margin:0 5px')).'">';

        print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="list">';

        print '<table class="liste" >';

        print '<tr class="liste_titre">';
        print '<th class="liste_titre" align="center">'.'Ref.'.'</th>';
        print '<th class="liste_titre" align="center">'.'Etiqueta'.'</th>';
        print '<th class="liste_titre" align="center">'.'Stock físico'.'</th>';
        print "</tr>\n";

        $var = true;
        $stop = false;

        for ($p = 0; $p < $productos_por_columna; $p++)
        {
            $objp = $db->fetch_object($resql);
            
            if (!$objp) break;

            $var = !$var;
            print '<tr '.$bc[$var].'>';

            // Ref
            print '<td class="nowrap">';
            $product_static->id = $objp->rowid;
            $product_static->ref = $objp->ref;
            $product_static->label = $objp->label;
            $product_static->type = $objp->fk_product_type;
            print $product_static->getNomUrl(1,'',24);
            print "</td>\n";

            // Label
            print '<td>'.dol_trunc($objp->label, 40).'</td>';

            // @Y: 
            print '<td>';
            print '<input class="flat" type="text" name="sref" size="8" value="">';
            print '</td>';

            print "</tr>\n";

            $count++;
        }
        
        print "</table>";
        print '</form>';
        
        print '</div>';
        if ($count === $num) break;
        
    }

    print '</div>';
    $db->free($resql);
}
else
{
    dol_print_error($db);
}

llxFooter();
$db->close();
