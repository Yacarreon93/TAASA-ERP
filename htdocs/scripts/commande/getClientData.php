<?php

require_once("../../master.inc.php");

$db->begin();
$error = 0;

$socid = $_POST['socid'];

$sql = " SELECT s.rowid, s.mode_reglement, s.cond_reglement, se.vendor, se.currency, se.cash_desk, s.outstanding_limit as credit_limit";
$sql.= " FROM ".MAIN_DB_PREFIX."societe s ";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields se ON se.fk_object = s.rowid";
$sql.= " WHERE s.rowid = '".$socid."' LIMIT 1";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				if ( $obj->mode_reglement) {
					$resultado["mode"]= $obj->mode_reglement;
				}else{
					$resultado["mode"]= "";
				}
				if ($obj->cond_reglement) {
					$resultado["cond"]= $obj->cond_reglement;
				}else{
					$resultado["cond"]= "";
				}
				if ($obj->vendor) {
					$resultado["vendor"]= $obj->vendor;
				}else{
					$resultado["vendor"]= "";
				}
				if ($obj->currency) {
					$resultado["currency"]= $obj->currency;
				}else{
					$resultado["currency"]= "";
				}
				if ($obj->cash_desk) {
					$resultado["cash_desk"]= $obj->cash_desk;
				}else{
					$resultado["cash_desk"]= "";
				}
				if ($obj->credit_limit) {
					$resultado["credit_limit"]= $obj->credit_limit;
				}else{
					$resultado["credit_limit"]= "";
				}
			}
			$i++;
		}
	}
}
else
{
	$error++;
	dol_print_error($db);
}

$sql = "SELECT
		SUM(f.total_ttc) as total,
		(
			SELECT
				SUM(pf.amount) AS abonado
			FROM
				`llx_facture` AS fa
			LEFT JOIN llx_paiement_facture AS pf ON pf.fk_facture = fa.rowid
			WHERE
				fk_soc =".$socid." 
			AND fk_statut = 1
		) as abonado
	FROM
		`llx_facture` AS f
	WHERE
		fk_soc =".$socid." 
	AND f.paye = 0
			AND f.fk_statut = 1
			AND f.entity = 1";
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num)
	{
		$obj = $db->fetch_object($resql);
		if ($obj)
		{
			if ( $obj->total) 
			{
				$resultado["debt"]= $obj->total - $obj->abonado;
			}

		}
	}
	echo json_encode($resultado);
}
else
{
	$error++;
	dol_print_error($db);
}

if (! $error)
{
	$db->commit();
}
else
{
	$db->rollback();
}

$db->close();

?>