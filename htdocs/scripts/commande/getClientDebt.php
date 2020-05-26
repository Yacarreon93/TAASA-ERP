<?php

require_once("../../master.inc.php");

$db->begin();
$error = 0;

$socid = $_POST['socid'];


$sql = " SELECT llx_societe.rowid, llx_societe.nom AS nom, sum(total_ttc) AS total, fk_account AS account, llx_societe.outstanding_limit as credit_limit
		FROM llx_facture AS f
		JOIN llx_societe ON f.fk_soc = llx_societe.rowid
		JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
		WHERE f.fk_soc = ".$socid; 
$sql.= " AND f.paye = 0
		AND f.fk_statut = 1
		AND f.entity = 1
		AND DATE(datef) < now()";

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
				$resultado["debt"]= $obj->total;
				$resultado["credit_limit"]= $obj->credit_limit;
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