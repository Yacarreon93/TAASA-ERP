<?php

require_once("../../master.inc.php");

$db->begin();
$error = 0;

$factureId = $_POST['factureId'];

$sql = " SELECT f.facnumber, f.fk_soc, s.nom FROM taasatsc_dolibarr.llx_facture as f 
JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
JOIN llx_societe AS s ON f.fk_soc = s.rowid
WHERE f.rowid = '".$factureId."' LIMIT 1";

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
				if ( $obj->facnumber) {
					$resultado["facnumber"]= $obj->facnumber;
				}else{
					$resultado["facnumber"]= "";
				}
				if ($obj->fk_soc) {
					$resultado["fk_soc"]= $obj->fk_soc;
				}else{
					$resultado["fk_soc"]= "";
				}
				if ($obj->nom) {
					$resultado["nom"]= $obj->nom;
				}else{
					$resultado["nom"]= "";
				}
			}
			$i++;
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