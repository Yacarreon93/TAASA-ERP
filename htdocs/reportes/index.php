<?php

/**
 *      \file       htdocs/user/index.php
 *      \ingroup    core
 *      \brief      Page of users
 */

require '../main.inc.php';
if (! empty($conf->multicompany->enabled))
    dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');


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

llxHeader('',"Reportes");

$buttonviewhierarchy='<form action="'.DOL_URL_ROOT.'/user/hierarchy.php'.(($search_statut != '' && $search_statut >= 0) ? '?search_statut='.$search_statut : '').'" method="POST"><input type="submit" class="button" style="width:120px" name="viewcal" value="'.dol_escape_htmltag($langs->trans("HierarchicView")).'"></form>';

print_fiche_titre("Reportes", $buttonviewhierarchy);

if($user->id == '1' || $user->id == '18' || $user->id == '19') {
  //reportes por sucursal
print '<table class="noborder nohover" border="1" style="width:50%; border: 1px solid #ddd">';
print "<tr class=\"liste_titre\">";
print '<td colspan="2">Reportes de contabilidad por sucursal</td></tr>';
//Seleccion de cuenta
print "<tr style='background-color:#7C8398; color:white'>
<td  style='width:50%'>Sucursal<br></td>
<td><select id='account_dynamic_select'>
    <option value='1'>Aguascalientes Bodega</option>
<option value='2'>Aguascalientes Produccion</option>
<option value='3'>Leon</option>
<option value='4'>Lagos</option>
</select></td>
</tr>'";
//Cuentas por cobrar
print "<tr colspan='2'>
<td><br><br></td>
<td><a id='general_unpaid_client_bills_link' target='_blank' href='../product/reports/UnpaidClientBillsReport.php?month=1'>Cuentas por cobrar</a><br></td></tr>'";
//Existencias en almacen
print "<tr><td><br><br></td>
<td><a id='currentReportLink' target='_blank' href='../product/reports/currentStockReport.php?stockId=1'>Existencias en almacén</a><br></td>'";
//Reporte de ventas
print "<tr>
<td><select id='sales_report_dynamic_select'>
    <option value='1'>Enero</option>
<option value='2'>Febrero</option>
<option value='3'>Marzo</option>
<option value='4'>Abril</option>
<option value='5'>Mayo</option>
<option value='6'>Junio</option>
<option value='7'>Julio</option>
<option value='8'>Agosto</option>
<option value='9'>Septiembre</option>
<option value='10'>Octubre</option>
<option value='11'>Noviembre</option>
<option value='12'>Diciembre</option>
</select></td>
<td><a id='salesReportLink' target='_blank' href='../product/reports/SalesReport.php'>Reporte de ventas</a></td></tr></table><br>";

//reportes generales
print '<table class="noborder nohover" border="1" style="width:50%; border: 1px solid #ddd">';
print "<tr class=\"liste_titre\">";
print '<td colspan="3">Reportes de contabilidad generales</td></tr>';
//Seleccion de mes
print "<tr style='background-color:#7C8398; color:white'>
<td  style='width:50%'>Mes<br></td>
<td><select id='general_reports_dynamic_select'>
    <option value='1'>Enero</option>
<option value='2'>Febrero</option>
<option value='3'>Marzo</option>
<option value='4'>Abril</option>
<option value='5'>Mayo</option>
<option value='6'>Junio</option>
<option value='7'>Julio</option>
<option value='8'>Agosto</option>
<option value='9'>Septiembre</option>
<option value='10'>Octubre</option>
<option value='11'>Noviembre</option>
<option value='12'>Diciembre</option>
</select></td>";
//Antiguedad de saldos
print "<tr colspan='2'>
<td><a id='general_bills_to_pay_link' target='_blank' href='../product/reports/BillsToPayReport.php?month=1'>Antigüedad de saldos</a><br></td></tr>'";
//Cuentas por cobrar
print "<tr colspan='2'>
<td><a id='general_unpaid_client_bills_link' target='_blank' href='../product/reports/UnpaidClientBillsReport.php?month=1'>Cuentas por cobrar</a><br></td>'";
//Reporte de ventas general
print "<tr colspan='2'>
<td><a id='general_sales_report_link' target='_blank' href='../product/reports/SalesReport.php?month=1'>Reporte de ventas general</a></td></tr>'";
//top 10 productos
print "<tr colspan='2'>
<td><a id='top_products_link' target='_blank' href='../product/reports/topProductos.php?month=1'>Top 10 productos</a><br></td>'";
print "</table></form><br>";


print '<table class="noborder nohover" border="1" style="width:50%; border: 1px solid #ddd">';
print '<tr class="liste_titre">';
print '<td colspan="2">Cierre de inventario</td></tr>';
//Cierre de inventario
print "<tr>
<td  style='width:50%'><select id='stock_closing_dynamic_select'>
    <option value='1'>Aguascalientes Bodega</option>
<option value='2'>Aguascalientes Produccion</option>
<option value='3'>Leon</option>
<option value='4'>Lagos</option>
</select></td>
<td><a id='inventoryClosingLink' target='_blank' href='../product/cierre_de_inventario.php?stock_id=1'>Ir a Cierre de inventario</a><br></td>'";
print "</table></form><br>";

//Vendedores
print '<table class="noborder nohover" border="1" style="width:50%; border: 1px solid #ddd">';
print "<tr class=\"liste_titre\">";
print '<td colspan="2">Vendedores</td></tr>';


$sql = "SELECT rowid, firstname FROM llx_user WHERE job = 'Vendedor'";
$resVendor = $db->query($sql) or die('ERROR en la consulta: '.$sql);

//Total de cuentas por cobrar
print "<tr>
<td  style='width:50%'><select id='unpaid_client_bills_total_select'>
<option value='1'>General</option>";
$vendor_array = array();
$k = 0;
while ($row = $db->fetch_object($resVendor))
{
  $vendor_array[$k]["id"] = $row->rowid;
  $vendor_array[$k]["firstname"] = $row->firstname;
  $vendor_id = $row->rowid;
  $vendedor = $row->firstname;
  print "<option value='".$vendor_id."'>".$vendedor."</option>";
  $k++;
}
print "</select></td>
<td><a id='unpaid_client_bills_total_link' target='_blank' href='../product/reports/UnpaidClientBillsTotalReport.php'>Totales de cuentas por cobrar</a><br></td></tr>";

// Clientes por Venedor
print "<tr>
<td><select id='clients_per_vendor_select'>";
for($i = 0; $i < sizeof($vendor_array); $i++)
{
  $vendor_id =  $vendor_array[$i]["id"];
  $vendedor = $vendor_array[$i]["firstname"];
  print "<option value='".$vendor_id."'>".$vendedor."</option>";
}
print "</select></td>
<td><a id='clients_per_vendor_link' target='_blank' href='../product/reports/ClientsPerVendorReport.php?vendor=13'>Clientes por Vendedor</a><br></td></tr>";

// Cartera vencida
print "<tr>";
print "<form action='../product/reports/carteraVencida.php' target='_blank'>";
print "<td><select name='vendor'>";
for($i = 0; $i < sizeof($vendor_array); $i++)
{
  $vendor_id =  $vendor_array[$i]["id"];
  $vendedor = $vendor_array[$i]["firstname"];
  print "<option value='".$vendor_id."'>".$vendedor."</option>";
}
print "</select></td>";
print "<td><button type=submit class='butAction'>Cartera vencida</button></td>";
print "</form>";

print "</table><br>";

print "<script>
    $(function(){
      // changes current stock link dinamicly
      $('#account_dynamic_select').on('change', function () {
         var stockId = document.getElementById('account_dynamic_select').value
         //changes current stock report
          if (stockId) { // require a URL
              var firstLinkPart = '../product/reports/currentStockReport.php?stockId=';
              var finalReportLink = firstLinkPart.concat(stockId);
              document.getElementById('currentReportLink').href = finalReportLink;
          }
         var month = document.getElementById('sales_report_dynamic_select').value
        if (month) {
              var firstLinkPart = '../product/reports/SalesReport.php?month=';
              var finalReportLink = firstLinkPart.concat(month);
              document.getElementById('salesReportLink').href = finalReportLink;
          }
          if (stockId) {
              var firstLinkPart = '&account=';
              var finalReportLink = finalReportLink.concat(firstLinkPart.concat(stockId));
              document.getElementById('salesReportLink').href = finalReportLink;
          }
          return false;
      });

      //changes sales report link dinamicly
       $('#sales_report_dynamic_select').on('change', function () {
         var stockId = document.getElementById('account_dynamic_select').value
         var month = document.getElementById('sales_report_dynamic_select').value
        if (month) {
              var firstLinkPart = '../product/reports/SalesReport.php?month=';
              var finalReportLink = firstLinkPart.concat(month);
              document.getElementById('salesReportLink').href = finalReportLink;
          }
          if (stockId) {
              var firstLinkPart = '&account=';
              var finalReportLink = finalReportLink.concat(firstLinkPart.concat(stockId));
              document.getElementById('salesReportLink').href = finalReportLink;
          }
          return false;
      });

      //changes general reports month dinamically
       $('#general_reports_dynamic_select').on('change', function () {
         var month =  $(this).val(); // get selected value
        if (month) {
              document.getElementById('general_bills_to_pay_link').href = document.getElementById('general_bills_to_pay_link').href.replace(/.$/, month);
              document.getElementById('general_unpaid_client_bills_link').href = document.getElementById('general_unpaid_client_bills_link').href.replace(/.$/, month);
              document.getElementById('general_sales_report_link').href = document.getElementById('general_sales_report_link').href.replace(/.$/, month);
              document.getElementById('top_products_link').href = document.getElementById('top_products_link').href.replace(/.$/, month);
          }
          return false;
      });

      //changes inventory closing link dinamicly
       $('#stock_closing_dynamic_select').on('change', function () {
          var stockId = $(this).val(); // get selected value
          if (stockId) { // require a URL
              var firstLinkPart = '../product/cierre_de_inventario.php?stock_id=';
              var finalReportLink = firstLinkPart.concat(stockId);
              document.getElementById('inventoryClosingLink').href = finalReportLink;
          }
          return false;
      });

      //changes unpaid client bills report link dinamically
       $('#unpaid_client_bills_total_select').on('change', function () {
          var vendor = $(this).val(); // get selected value
          if (vendor) {
              if(vendor == 1) {
                var finalReportLink = '../product/reports/UnpaidClientBillsTotalReport.php';
              } else {
                var firstLinkPart = '../product/reports/UnpaidClientBillsTotalReportPerVendor.php?vendor=';
                var finalReportLink = firstLinkPart.concat(vendor);
              }
              document.getElementById('unpaid_client_bills_total_link').href = finalReportLink;
          }
          return false;
        });

        //changes clients per vendor report link dinamically
       $('#clients_per_vendor_select').on('change', function () {
          var vendor = $(this).val(); // get selected value
          if (vendor) {
              var firstLinkPart = '../product/reports/ClientsPerVendorReport.php?vendor=';
              var finalReportLink = firstLinkPart.concat(vendor);
              document.getElementById('clients_per_vendor_link').href = finalReportLink;
          }
          return false;
        });

    });
</script>";
  } else {
    print'<p>olooo</p>';
  }



llxFooter();

$db->close();
