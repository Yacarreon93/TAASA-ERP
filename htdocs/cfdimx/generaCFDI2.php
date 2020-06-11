<?php

	require_once DOL_DOCUMENT_ROOT.'/cfdi/service/comprobantecfdiservice.php';

	$service = new ComprobanteCFDIService();
	$service->SaveAllFactures($db, $id);
					
	print '<script>
		location.href="facture.php?facid='.$_REQUEST["facid"].'&cfdi_commit=100";
		</script>'			

?>
