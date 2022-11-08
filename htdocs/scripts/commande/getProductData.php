<?php

require_once("../../master.inc.php");

$db->begin();
$error = 0;

$productId = $_POST['productId'];

$sql = "SELECT
pe.claveprodserv AS bienesTransp,
umed AS umed,
p.label AS descripcion,
p.price AS price,
peso_kg AS pesoEnKg
FROM llx_product AS p
JOIN llx_product_extrafields AS pe
ON p.rowid = pe.fk_object
WHERE fk_object = '".$productId."' LIMIT 1";

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
				if ($obj->bienesTransp) {
					$resultado["bienesTransp"]= $obj->bienesTransp;
				}
				if ($obj->umed) {
					$resultado["umed"]= $obj->umed;
				}
                if ($obj->descripcion) {
					$resultado["descripcion"]= $obj->descripcion;
				}
                if ($obj->price) {
					$resultado["valor_mercancia"]= $obj->price;
				}
				if ($obj->pesoEnKg) {
					$resultado["peso_kg"]= $obj->pesoEnKg;
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