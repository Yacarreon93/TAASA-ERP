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

$titre = "Reporte de ventas por agente";
print_fiche_titre($titre,'','title_accountancy.png');

// Formulaire de generation
print '<form method="get" action="">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="report">';

$syear = GETPOST("reyear")?GETPOST("reyear"):date("Y", time());
$cmonth = GETPOST("remonth")?GETPOST("remonth"):date("n", time());
$fromDate = GETPOST("fromDate")?GETPOST("fromDate"):'';
	$fromDate = strtotime($fromDate);
	$fromDate = date("Y-m-d", $fromDate);
$toDate = GETPOST("toDate")?GETPOST("toDate"):'';
	$toDate = strtotime($toDate);
	$toDate = date("Y-m-d", $toDate);

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

if($fromDate && $toDate) echo "<div class='titre' style='margin-bottom:10px'>Periodo: del ".$fromDate." al ".$toDate."</div>";

// List of payments
if($action == 'report') {

	if($fromDate && $toDate) {

	    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	    print '<table class="noborder" width="100%">';
	    print '<tr class="liste_titre">';
	    print_liste_field_titre("Vendedor",$_SERVER["PHP_SELF"],"p.rowid","",$paramlist,"",$sortfield,$sortorder);
	    print_liste_field_titre("Ventas",$_SERVER["PHP_SELF"],"dp","",$paramlist,'align="center"',$sortfield,$sortorder);
	    print_liste_field_titre("Cobrado",$_SERVER["PHP_SELF"],"dp","",$paramlist,'align="center"',$sortfield,$sortorder);
	    print_liste_field_titre("Saldo",$_SERVER["PHP_SELF"],"s.nom","",$paramlist,"",$sortfield,$sortorder);
	    print_liste_field_titre("Vencido",$_SERVER["PHP_SELF"],"s.nom","",$paramlist,"",$sortfield,$sortorder);

		$sql_vendors =  " SELECT * FROM ".MAIN_DB_PREFIX."user u ";
		$sql_vendors .= " JOIN ".MAIN_DB_PREFIX."user_extrafields ue ON ue.fk_object = u.rowid ";
		$sql_vendors .= " WHERE ue.rol = 1";

		$resql_vendors = $db->query($sql_vendors);
		if($sql_vendors) {
			$var=true;
			while ($vendor = $db->fetch_object($resql_vendors)) {

				$sales = 0;
				$amount = 0;
				$balance = 0;
				$due_balance = 0;

				// Colum 1

				$sql_fac =  " SELECT * FROM ".MAIN_DB_PREFIX."facture f";
				$sql_fac .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = f.rowid ";
				$sql_fac .= " WHERE fe.vendor = ".$vendor->rowid;
				if ($fromDate && $toDate) {
	                $sql_fac.= " AND f.datef BETWEEN '".$fromDate."' AND '".$toDate."'";
	            }

				$resql_fac = $db->query($sql_fac);
				if($resql_fac) {
					while($invoice = $db->fetch_object($resql_fac)) {
						$sales += $invoice->total_ttc;				
					}
				}

				// Column 2

				$sql_pay  = " SELECT * FROM ".MAIN_DB_PREFIX."paiement p ";
				$sql_pay .= " JOIN ".MAIN_DB_PREFIX."paiement_facture pf ON pf.fk_paiement = p.rowid ";
				$sql_pay .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = pf.fk_facture ";
				$sql_pay .= " WHERE fe.vendor = ".$vendor->rowid;
				if ($fromDate && $toDate) {
	                $sql_pay.= " AND p.datep BETWEEN '".$fromDate."' AND '".$toDate."'";
	            }

	            $resql_pay = $db->query($sql_pay);
				if($resql_pay) {									
					while($payment = $db->fetch_object($resql_pay)) {
						$amount += $payment->amount;
					}
				}				

				// Column 3

				$sql_fac =  " SELECT DISTINCT f.fk_soc FROM ".MAIN_DB_PREFIX."facture f";
				$sql_fac .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = f.rowid ";
				$sql_fac .= " WHERE fe.vendor = ".$vendor->rowid;
				if ($fromDate && $toDate) {
	                $sql_fac.= " AND f.datef <= '".$toDate."'";
	            }     

				$resql_fac = $db->query($sql_fac);
				if($resql_fac) {
					while($invoice = $db->fetch_object($resql_fac)) {
						$soc = new Societe($db);
						if ($invoice->fk_soc > 0)
							$res = $soc->fetch($invoice->fk_soc);
						if($res)
							$balance += $soc->get_OutstandingBill();			
					}
				}

				// Column 4

				$sql_fac =  " SELECT DISTINCT f.fk_soc FROM ".MAIN_DB_PREFIX."facture f";
				$sql_fac .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = f.rowid ";
				$sql_fac .= " WHERE fe.vendor = ".$vendor->rowid;
				if ($fromDate && $toDate) {
	                $sql_fac.= " AND f.date_lim_reglement < '".$toDate."'";
	            }

				$resql_fac = $db->query($sql_fac);
				if($resql_fac) {
					while($invoice = $db->fetch_object($resql_fac)) {
						$soc = new Societe($db);
						if ($invoice->fk_soc > 0)
							$res = $soc->fetch($invoice->fk_soc);
						if($res)
							$due_balance += $soc->get_OutstandingBill();			
					}
				}

				$user = new User($db);
		        $user->fetch($vendor->rowid);

				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td>';	        
		        print $user->getNomUrl(1);
		        print '</td>';
		        print '<td>';
		        print $sales;
		        print '</td>';
		        print '<td>';
		        print $amount;
		        print '</td>';
		        print '<td>';
		        print $balance;
		        print '</td>';
		        print '<td>';
		        print $due_balance;
		        print '</td>';
												
			}
		}

		print "</table>\n";
	    print "</form>\n";

	    print '<br>';

	    print '<form action="exports/agent_report.php" method="post">';
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
