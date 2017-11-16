<?php

require_once("../../htdocs/master.inc.php");

$db->begin();
$error = 0;

$socid = $_POST['socid'];

$sql = " SELECT s.rowid, s.mode_reglement, s.cond_reglement, se.currency ";
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
				if ($obj->currency) {
					$resultado["currency"]= $obj->currency;
				}else{
					$resultado["currency"]= "";
				}
				echo json_encode($resultado);
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