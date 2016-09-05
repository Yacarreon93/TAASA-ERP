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

$titre = "Reporte general de ventas";
print_fiche_titre($titre,'','title_accountancy.png');

// Formulaire de generation
print '<form method="post" action="">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="report">';

$cmonth = GETPOST("remonth")?GETPOST("remonth"):date("n", time());
$syear = GETPOST("reyear")?GETPOST("reyear"):date("Y", time());

print $formother->select_year($syear,'reyear');
print $formother->select_month($cmonth,'remonth');
print ' <select id="reperiod" name="reperiod"></select> ';

print '<input type="submit" class="button" value="Generar">';
print '</form>';
print '<br>';

clearstatcache();

// List of payments
if($action == 'report') {

    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre("Vendedor",$_SERVER["PHP_SELF"],"p.rowid","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre("Importe",$_SERVER["PHP_SELF"],"dp","",$paramlist,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre("Adeudo",$_SERVER["PHP_SELF"],"s.nom","",$paramlist,"",$sortfield,$sortorder);

	$sql_vendors =  " SELECT * FROM ".MAIN_DB_PREFIX."user u ";
	$sql_vendors .= " JOIN ".MAIN_DB_PREFIX."user_extrafields ue ON ue.fk_object = u.rowid ";
	$sql_vendors .= " WHERE ue.rol = 1";

	$resql_vendors = $db->query($sql_vendors);
	if($sql_vendors) {
		$var=true;
		while ($vendor = $db->fetch_object($resql_vendors)) {

			$amount = 0;
			$debit = 0;

			$sql_fac =  " SELECT * FROM ".MAIN_DB_PREFIX."facture f";
			$sql_fac .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = f.rowid ";
			$sql_fac .= " WHERE fe.vendor = ".$vendor->rowid;

			$resql_fac = $db->query($sql_fac);
			if($sql_fac) {
				while($invoice = $db->fetch_object($resql_fac)) {

					$debit = $invoice->total_ttc;

					$sql =  " SELECT * FROM ".MAIN_DB_PREFIX."paiement p ";
					$sql .= " JOIN ".MAIN_DB_PREFIX."paiement_facture pf ON pf.fk_paiement = p.rowid ";
					$sql .= " WHERE pf.fk_facture = ".$invoice->rowid;

					$resql = $db->query($sql);
					if($resql) {									
						while($payment = $db->fetch_object($sql)) {
							$amount += $payment->amount;
							$debit -= $amount;
						}
					}
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
	        print $amount;
	        print '</td>';
	        print '<td>';
	        print $debit;
	        print '</td>';
					
		}
	}

	print "</table>\n";
    print "</form>\n";
	
}


llxFooter();

$db->close();
