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
      <option value='1'>Aguascalientes</option>
  <option value='5'>Leon</option>
  <option value='3'>Lagos</option>
  </select></td>
  </tr>";
   //Seleccion de mes
   print "<tr style='background-color:#7C8398; color:white'>
   <td  style='width:50%'>Mes<br></td>
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
   </tr>";
  //Cuentas por cobrar
  print "<form action='../product/reports/UnpaidClientBillsTotalReportPerAccount.php' target='_blank'>";
  print "<tr colspan='2'>
  <td><br><br></td>";
  print '<input type="hidden" id="monthCuentasPorCobrar" name="month" value="1">';
  print '<input type="hidden" id="stockCuentasPorCobrar" name="account" value="1">';
  print "<td><button type=submit class='butAction'>Cuentas por cobrar</button></td></tr>";
  print "</form>";
  //Reporte de ventas
  print "<form action='../product/reports/SalesReport.php' target='_blank'>";
  print "<tr><td><br><br></td>";
  print '<input type="hidden" id="stockReporteVentas" name="stockId" value="1">';
  print '<input type="hidden" id="monthReporteVentas" name="month" value="1">';
  print "<td><button type=submit class='butAction'>Reporte de ventas</button></td></tr>";
  print "</form>";
  print "</table><br>";

  $currentYear = date('Y');
  $year_options = [];
  $oldest_year = 2020;

  for ($i = $currentYear + 5; $i >= $oldest_year ; $i--) { 
    array_push($year_options, $i);
  }
  
  //reportes generales
  print '<table class="noborder nohover" border="1" style="width:50%; border: 1px solid #ddd">';
  print "<tr class=\"liste_titre\">";
  print '<td colspan="3">Reportes de contabilidad generales</td></tr>';
  //Seleccion de año
  print "<tr style='background-color:#7C8398; color:white'>
  <td style='width:50%'>Año<br></td>
  <td>
  <select id='general-reports-year'>";
  foreach ($year_options as $year) {
    $selected = $year == $currentYear ? 'selected' : '';
    print "<option value='$year' $selected>$year</option>";
  }   
  print "</select>
  </td>
  </tr>";
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
  </select></td></tr>";
  //Antiguedad de saldos
  print "<tr colspan='2'>";
  print "<form action='../product/reports/BillsToPayReport.php' target='_blank'>";
  print "<td><br><br></td>";
  print '<input type="hidden" id="monthBillsToPay" name="month" value="1">';
  print "<td><button type=submit class='butAction'>Antiguedad de Saldos</button></td>";
  print "</form></tr>";
  //Cuentas por cobrar Totales
  print "<tr colspan='2'>";
  print "<form action='../product/reports/UnpaidClientBillsTotalReport.php' target='_blank'>";
  print "<td><br><br></td>";
  print '<input type="hidden" id="monthUnpaidClientBillsTotal" name="month" value="1">';
  print "<td><button type=submit class='butAction'>Cuentas por cobrar Totales</button></td>";
  print "</form></tr>";
   //Cuentas por cobrar
   print "<tr colspan='2'>";
   print "<form action='../product/reports/UnpaidClientBillsReport.php' target='_blank'>";
   print "<td><br><br></td>";
   print '<input type="hidden" id="monthUnpaidClientBills" name="month" value="1">';
   print "<td><button type=submit class='butAction'>Cuentas por cobrar Detalle</button></td>";
   print "</form></tr>";
  //Reporte de ventas general
  print "<tr colspan='2'>";
  print "<form action='../product/reports/SalesReport.php' target='_blank'>";
  print "<td><br><br></td>";
  print '<input type="hidden" id="monthGeneralSalesReport" name="month" value="1">';
  print "<td><button disabled='disabled' type=submit class='butAction'>Reporte de ventas general</button></td>";
  print "</form></tr>";
  //top 10 productos
  print "<tr colspan='2'>";
  print "<form action='../product/reports/topProductos.php' target='_blank'>";
  print "<td>";
  print '<select id="weekSelector" class="flat" style="width:100px; margin-left: 10px;">';
  print '</select>';
  print '<input type="hidden" value="" name="month_week" id="month_week"></td>';
  print '<input type="hidden" name="fromDate" id="fromDate">';
  print '<input type="hidden" name="toDate" id="toDate">';
  print "<td><button type=submit class='butAction'>Top 10 productos</button>";
  print '<input type="hidden" id="month10Products" name="month" value="1"></td>';
  print "</form>";
  print "</tr>";
  print "</table><br>";
  //Inventario
    print '<table class="noborder nohover" border="1" style="width:50%; border: 1px solid #ddd">';
    print '<tr class="liste_titre">';
    print '<td colspan="2">Reportes de almacen</td></tr>';
    //Seleccion de almacen
    print "<tr style='background-color:#7C8398; color:white'>
    <td  style='width:50%'>Almacen<br></td>
    <td><select id='almacen_dynamic_select'>
    <option value='1'>Aguascalientes Bodega</option>
    <option value='2'>Aguascalientes Produccion</option>
    <option value='3'>Leon</option>
    <option value='4'>Lagos</option>
    </select></td>
    </tr>";
  //Cierre de inventario
  print "<tr>";
  print "<form action='../product/reports/cierre_de_inventario.php' target='_blank'>";
  print "<tr><td><br><br></td>";
  print '<input type="hidden" id="stockCierreInventario" name="stock_id" value="1">';
  print "<td><button type=submit class='butAction'>Ir a Cierre de inventario</button></td></form></tr>";
    //Existencias en almacen
    print "<form action='../product/reports/currentStockReport.php' target='_blank'>";
    print "<tr><td><br><br></td>";
    print '<input type="hidden" id="stockExistenciasAlmacen" name="stockId" value="1">';
    print "<td><button type=submit class='butAction'>Existencias en Almacen</button></td>";
    print "</form>";
    print "</td></tr></table><br>";

  //Vendedores
  print '<table class="noborder nohover" border="1" style="width:50%; border: 1px solid #ddd">';
  print "<tr class=\"liste_titre\">";
  print '<td colspan="2">Vendedores</td></tr>';

   //Seleccion de vendedor
  $sql = "SELECT rowid, firstname FROM llx_user WHERE job = 'Vendedor'";
   $resVendor = $db->query($sql) or die('ERROR en la consulta: '.$sql);

   print "<tr style='background-color:#7C8398; color:white'>
   <td  style='width:50%'>Vendedor<br></td>";
   print "<td  style='width:50%'><select id='vendor_reports_dynamic_select'>";
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
   print "</select></td><tr/>";

  //Total de cuentas por cobrar
  print "<tr colspan='2'>";
  print "<form action='../product/reports/UnpaidClientBillsTotalReportPerVendor.php' target='_blank'>";
  print "<tr><td><br><br></td>";
  print '<input type="hidden" id="vendorUnpaidClientBillsPerVendor" name="vendor" value="13">';
  print "<td><button type=submit class='butAction'>Totales de cuentas por cobrar</button><td/><tr/>";
  print "</form>";

  // Clientes por Venedor
  print "<tr colspan='2'>";
  print "<form action='../product/reports/ClientsPerVendorReport.php' target='_blank'>";
  print "<tr><td><br><br></td>";
  print '<input type="hidden" id="vendorClientsPerVendor" name="vendor" value="13">';
  print "<td><button type=submit class='butAction'>Clientes por Vendedor</button><td/><tr/>";
  print "</form>";

  // Cartera vencida
  print "<tr colspan='2'>";
  print "<form action='../product/reports/carteraVencida.php' target='_blank'>";
  print "<tr><td><br><br></td>";
  print '<input type="hidden" id="vendorCarteraVencida" name="vendor" value="13">';
  print "<td><button type=submit class='butAction'>Cartera vencida</button><td/><tr/>";
  print "</form>";

  print "</table><br>";

print "<script>
    $(function(){
      // changes current stock link dinamicly
      $('#sales_report_dynamic_select').on('change', function () {
         var month = document.getElementById('sales_report_dynamic_select').value
        if (month) {
              document.getElementById('monthCuentasPorCobrar').value = month;
              document.getElementById('monthReporteVentas').value = month;
          }
      });

      //changes sales report link dinamicly
       $('#account_dynamic_select').on('change', function () {
        var stockId = document.getElementById('account_dynamic_select').value
        //changes current stock report
         if (stockId) { 
             document.getElementById('stockReporteVentas').value = stockId;
             document.getElementById('stockCuentasPorCobrar').value = stockId;
         }
      });

      //changes almacen report link dinamicly
      $('#almacen_dynamic_select').on('change', function () {
       var stockId = document.getElementById('almacen_dynamic_select').value
       //changes current stock report
        if (stockId) { 
            document.getElementById('stockExistenciasAlmacen').value = stockId;
            document.getElementById('stockCierreInventario').value = stockId;
        }
     });

      //changes general reports month dinamically
       $('#general_reports_dynamic_select').on('change', function () {
         var month =  $(this).val(); // get selected value
        if (month) {
              document.getElementById('monthBillsToPay').value = month;
              document.getElementById('monthUnpaidClientBillsTotal').value = month; 
              document.getElementById('monthUnpaidClientBills').value = month; 
              document.getElementById('monthGeneralSalesReport').value = month;
              document.getElementById('month10Products').value = month;
          }
      });

      //changes vendor reports month dinamically
      $('#vendor_reports_dynamic_select').on('change', function () {
        var vendor =  $(this).val(); // get selected value
       if (vendor) {
             document.getElementById('vendorUnpaidClientBillsPerVendor').value = vendor;
             document.getElementById('vendorClientsPerVendor').value = vendor; 
             document.getElementById('vendorCarteraVencida').value = vendor; 
         }
     });
    });
  </script>";

  echo '<script>
  jQuery("#general_reports_dynamic_select").change(function(){
    var year = new Date().getFullYear();
      jQuery.post("ajax/getRanges.php", {month: jQuery("#general_reports_dynamic_select").val(), year}, function (data) {
          var obj = JSON.parse(data);
          $("#weekSelector").empty();
          $("#weekSelector").append($("<option>", {
          value: 0,
          text: ""
      }));
          obj.forEach(myFunction);
      });
   });

  var contador = 0;

  function myFunction(item) {

      $("#weekSelector").append($("<option>", {
          value: item.from+"/"+item.to,
          text: "del "+item.from+" al "+item.to
      }));
   }

   jQuery("#weekSelector").change(function(){
      dates = $(this).val().split("/");

      $("#fromDate").val(dates[0]);
      $("#toDate").val(dates[1]);

   });

  </script>';
  } else {
    print'<p>No estas autorizado para ver este modulo.</p>';
  }



llxFooter();

$db->close();
