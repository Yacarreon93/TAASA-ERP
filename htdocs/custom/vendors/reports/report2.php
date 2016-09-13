<?php

/**
 *      \file       htdocs/user/index.php
 *      \ingroup    core
 *      \brief      Page of users
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

if (! $user->rights->user->user->lire && ! $user->admin)
    accessforbidden();

$langs->load("users");
$langs->load("companies");

$action = GETPOST('action', 'alpha');

/*
 * View
 */

$formother=new FormOther($db);

llxHeader();

$titre = "Reporte de ventas por zona";
print_fiche_titre($titre,'','title_accountancy.png');

// Formulaire de generation
print '<form method="get" action="">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="report">';

$syear = GETPOST("reyear")?GETPOST("reyear"):date("Y", time());
$cmonth = GETPOST("remonth")?GETPOST("remonth"):date("n", time());
$fromDate = GETPOST("fromDate")?GETPOST("fromDate"):'';
$toDate = GETPOST("toDate")?GETPOST("toDate"):'';

print $formother->select_year($syear,'reyear');
print $formother->select_month($cmonth,'remonth');
print ' <select id="reperiod" name="reperiod"></select>';
print ' <input type="submit" class="button" value="Generar">';
print ' <div id="theHidden"></div> ';
print '</form>';

echo '<script>';
echo '	jQuery(document).ready(function(){
			jQuery.post("../ajax/getRanges.php", {month: jQuery("#remonth").val(), year: jQuery("#reyear").val() }, function (data) {
		        var obj = JSON.parse(data);
		        $("#reperiod").empty();
		        $("#reperiod").append($("<option>", {
		        value: 0,
		        text: ""
		    	}));
		        obj.forEach(fillDateRanges);
		    });
		});

		jQuery("#remonth").change(function() {
		    jQuery.post("../ajax/getRanges.php", {month: jQuery("#remonth").val(), year: jQuery("#reyear").val() }, function (data) {
		        var obj = JSON.parse(data);
		        $("#reperiod").empty();
		        $("#reperiod").append($("<option>", {
		        value: 0,
		        text: ""
		    	}));
		        obj.forEach(fillDateRanges);
		    });
		});

		function fillDateRanges(item) {    
		    $("#reperiod").append($("<option>", {
		        value: item.from+"/"+item.to,
		        text: "del "+item.from+" al "+item.to
		    }));
		}

		jQuery("#reperiod").change(function() {
			dates = $(this).val().split("/");
			$("#theHidden").empty();
			$("#theHidden").append($("<input>", {
			    type: "hidden",
			    name: "fromDate",
			    value: dates[0]
			}));
			$("#theHidden").append($("<input>", {
			    type: "hidden",
			    name: "toDate",
			    value: dates[1]
			}));
		});

		$("#theHidden").append($("<input>", {
		    type: "hidden",
		    value: dates[0]
		}));';
print '</script>';   
print '<br>';

clearstatcache();

// List of payments
if($action == 'report') {

	if($fromDate && $toDate) {

	    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	    print '<table class="noborder" width="100%">';
	    print '<tr class="liste_titre">';
	    print_liste_field_titre("Zona",$_SERVER["PHP_SELF"],"p.rowid","",$paramlist,"",$sortfield,$sortorder);
	    print_liste_field_titre("Ventas",$_SERVER["PHP_SELF"],"dp","",$paramlist,'align="center"',$sortfield,$sortorder);
	    print_liste_field_titre("Cobrado",$_SERVER["PHP_SELF"],"dp","",$paramlist,'align="center"',$sortfield,$sortorder);
	    print_liste_field_titre("Saldo",$_SERVER["PHP_SELF"],"s.nom","",$paramlist,"",$sortfield,$sortorder);
	    print_liste_field_titre("Vencido",$_SERVER["PHP_SELF"],"s.nom","",$paramlist,"",$sortfield,$sortorder);

		$sql_zones =  " SELECT * FROM ".MAIN_DB_PREFIX."c_zones z WHERE z.rowid IN (SELECT DISTINCT se.fk_zone FROM ".MAIN_DB_PREFIX."societe_extrafields se)";

		$resql_zones = $db->query($sql_zones);
		if($resql_zones) {
			$var=true;
			while ($zone = $db->fetch_object($resql_zones)) {

				$sales = 0;
				$amount = 0;
				$debit = 0;

				$sql_fac =  " SELECT * FROM ".MAIN_DB_PREFIX."facture f";
				$sql_fac .= " JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = f.fk_soc ";
				$sql_fac .= " JOIN ".MAIN_DB_PREFIX."societe_extrafields se ON se.fk_object = s.rowid ";
				$sql_fac .= " WHERE se.fk_zone = ".$zone->rowid;

				$resql_fac = $db->query($sql_fac);

				echo $sql_fac;

				if($resql_fac) {

					if($resql_fac->num_rows <= 0) {
						continue;
					} 

					while($invoice = $db->fetch_object($resql_fac)) {

						$sales += $invoice->total_ttc;

						$sql =  " SELECT * FROM ".MAIN_DB_PREFIX."paiement p ";
						$sql .= " JOIN ".MAIN_DB_PREFIX."paiement_facture pf ON pf.fk_paiement = p.rowid ";
						$sql .= " WHERE pf.fk_facture = ".$invoice->rowid;
						if ($fromDate && $toDate) {
			                $sql.= " AND p.datep BETWEEN '".$fromDate."' AND '".$toDate."'";
			            }

						$resql = $db->query($sql);
						if($resql) {									
							while($payment = $db->fetch_object($sql)) {
								$amount += $payment->amount;
							}
						}
					}

					$debit = $sales - $amount;

					$var=!$var;
					print "<tr ".$bc[$var].">";
					print '<td>';	        
			        print $zone->nom;
			        print '</td>';
			        print '<td>';
			        print $sales;
			        print '</td>';
			        print '<td>';
			        print $amount;
			        print '</td>';
			        print '<td>';
			        print $debit;
			        print '</td>';
			        print '<td>';
			        print $unknown;
			        print '</td>';
				}									
			}
		}

		print "</table>\n";
	    print "</form>\n";

	    print '<br>';

	    print '<form action="exports/zone_report.php" method="post">';
        foreach($_GET as $key => $val) {        
        	print '<input type="hidden" name="'.htmlspecialchars($key, ENT_COMPAT, 'UTF-8').'" ';
        	print 'value="'.htmlspecialchars($val, ENT_COMPAT, 'UTF-8').'">';  
        }                     
        print '<input type="submit" class="button" value="Generar reporte" style="float:right">';
        print '</form>';

	}
	
}


llxFooter();

$db->close();
