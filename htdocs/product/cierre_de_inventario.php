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
require_once DOL_DOCUMENT_ROOT.'/product/service/TableManagerService.php';

$langs->load("stocks");
$langs->load("products");
$langs->load("suppliers");

$page           = GETPOST('page', 'int');
$action         = GETPOST('action');
$column         = GETPOST('column', 'int');
$stock_id       = GETPOST('stock_id', 'int');
$product_ids    = GETPOST('product_ids');
$stock          = GETPOST('stock');

if (!$page) $page = 1;
if (!$stock_id) $stock_id = 1;
if (!$action) $action = 'crear_tabla';

// @TODO: Security check

/*
 * Actions
 */
if ($action === 'crear_tabla')
{
     $tableManager = new TableManagerService();
     $tableManager->CreateTemporaryTable($db);
}
else if ($action === 'limpiar_tabla')
{
     $tableManager = new TableManagerService();
     $tableManager->ClearTable($db);
}
else if ($action === 'guardar_entradas')
{
    $contador = 0;
    $datos = array();
    foreach ($product_ids as $product_id)
    {
        $datos[$product_id] = $stock[$contador++];
    }

    $tableManager = new TableManagerService();
    $tableManager->UpdateProductColumn($datos, $stock_id, $db);
}
else if ($action === 'cierre_de_inventario')
{
    $tableManager = new TableManagerService();
    $tableManager->SaveInventoryClosing($db);
    print'<script>

    window.onload = function(){
         window.open("reports/inventoryDifferencesReport.php?stockId='.$stock_id.'", "_blank");
    }
    </script>';
}

/*
 * View
 */

$htmlother=new FormOther($db);
$form=new Form($db);
    
$title = 'Cierre de inventario';

$numero_de_columnas = 3;
$productos_por_columna = 15;
$limit = $productos_por_columna * $numero_de_columnas;
$offset = $limit * ($page - 1);

$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, llx_inventory_closing_temp.reel as reel';
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
$sql.= ' JOIN '.MAIN_DB_PREFIX.'product_stock as ps ON ps.fk_product = p.rowid';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'inventory_closing_temp ON '.MAIN_DB_PREFIX.'inventory_closing_temp.fk_product = p.rowid';
$sql.= ' WHERE ps.fk_entrepot = '.$stock_id;

$_resql = $db->query($sql);

$sql.= $db->plimit($limit, $offset);

$resql = $db->query($sql);

$product_static = new Product($db);

if ($_resql && $resql)
{
    llxHeader('', $title, '', '');

    $num = $db->num_rows($_resql);
    $local_num = $db->num_rows($resql);
    $count = $offset;
    $local_count = 0;

    print_fiche_titre($title, '', 'title_products.png');

    print '<div style="display:flex;">';

    for ($c = 0; $c < $numero_de_columnas; $c++)
    {
        print '<div style="flex-grow:1; '.($c==0?'margin-right:10px':($local_count+$productos_por_columna==$local_num?'margin-left:10px':'margin:0 10px')).'">';

        print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="guardar_entradas">';
        
        print '<input type="hidden" name="stock_id" value="'.$stock_id.'">';
        print '<input type="hidden" name="column" value="'.$c.'">';
        print '<input type="hidden" name="page" value="'.$page.'">';

        print '<table class="liste" >';

        print '<tr class="liste_titre">';
        print '<th class="liste_titre" align="center">'.'Ref.'.'</th>';
        print '<th class="liste_titre" align="center">'.'Etiqueta'.'</th>';
        print '<th class="liste_titre" align="center">'.'Stock físico'.'</th>';
        print "</tr>\n";

        $var = true;

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
            $product_static->reel = $objp->reel;
            print $product_static->getNomUrl(1,'',24);
            print "</td>\n";

            // Label
            print '<td>'.dol_trunc($objp->label, 40).'</td>';

            // @Y: 
            print '<td style="text-align:center;">';
            print '<input type="hidden" name="product_ids[]" value="'.$product_static->id.'">';
            print '<input class="flat" autocomplete="off" style="min-width:80%;" type="text" name="stock[]" size="8" value="'.$product_static->reel.'">';
            print '</td>';

            print "</tr>\n";

            $count++;
            $local_count++;
        }
        
        print "</table>";
        print '<input class="button" style="float:right;margin-top:10px;" type="submit" value="Guardar">';
        print '</form>';
            
        print '</div>';
        if ($count >= $num) break;
    }

    print '</div>';
    print '<div style="display:flex;flex-direction:row;float:right">';

    if ($page > 1) {
        print '<form action="'.$_SERVER["PHP_SELF"].'" method="get" name="formulaire">';
        print '<input type="hidden" name="stock_id" value="'.$stock_id.'">';
        print '<input type="hidden" name="page" value="'.($page - 1).'">';
        print '<input class="button" style="float:right;margin:10px 10px 0 0;" type="submit" value="Página '.($page - 1).' <">';
        print '</form>';
    }

    if (($offset + $limit) >= $num)
    {
        print '<form action="'.$_SERVER["PHP_SELF"].'" method="get" name="formulaire">';
        print '<input type="hidden" name="action" value="cierre_de_inventario">';
        print '<input type="hidden" name="stock_id" value="'.$stock_id.'">';
        print '<input class="button" style="float:right;margin-top:10px;" type="submit" value="Terminar cierre de inventario">';
        print '</form>';
    }
    else
    {
        print '<form action="'.$_SERVER["PHP_SELF"].'" method="get" name="formulaire">';
        print '<input type="hidden" name="stock_id" value="'.$stock_id.'">';
        print '<input type="hidden" name="page" value="'.($page + 1).'">';
        print '<input class="button" style="float:right;margin-top:10px;" type="submit" value="> Página '.($page + 1).'">';
        print '</form>';
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
