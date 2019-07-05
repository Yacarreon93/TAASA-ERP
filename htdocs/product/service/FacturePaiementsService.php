<?php

//require_once DOL_DOCUMENT_ROOT.'/product/dao/InventoryClosingDao.php';

class FacturePaiementsService {
	public function getFacturePayments($db, $factureId, $date) {
		$sql = "SELECT datep, p.amount
						FROM llx_paiement AS p
						LEFT JOIN llx_paiement_facture AS pf ON p.rowid = pf.fk_paiement
						JOIN llx_facture AS f ON f.rowid = pf.fk_facture
						WHERE f.rowid = ".$factureId."
						AND datep < DATE('".$date."')
						ORDER BY datep ASC";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
		    $data[] = array(
		        fecha_pago => $row->datep,
		        importe=> $row->amount
		    );
		}
		return $data;
	}

	public function getIVAToPay($db, $paymentDate, $factureId, $factureIVA) {
		$facturePayments = $this->getFacturePayments($db, $factureId, $paymentDate);
		$IVAPagado = 0;
		$IVARestante = $factureIVA;
		for($i = 0; $i < sizeof($facturePayments); $i++) {
			$importeConIVA = (($IVARestante * 100) / 16);
			$importePago = $facturePayments[$i]['importe'];
			if($importePago >= $importeConIVA) {
					$IVARestante = 0;
					break;
			} else {
				$abonoIVA = $importePago * 0.16;
				$IVARestante -= $abonoIVA;
			}
		}
		return $IVARestante;
	}

	public function getTotalFacturasSinIVAACredito($db, $month, $account) {
		$sql = "SELECT datef, SUM(total_ttc)
						FROM llx_facture
						WHERE fk_cond_reglement = 2 AND fk_account = ".$account." AND MONTH(datef) = ".$month." AND YEAR(DATEf) = YEAR(CURDATE()) AND tva = 0
						GROUP BY datef
						ORDER BY datef ASC";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
		    $data[] = array(
						fecha=>$row->datef,
		        total=> $row->total_ttc
		    );
		}
		return $data;
	}

	public function getTotalFacturasConIVAACredito($db, $month, $account) {
		$sql = "SELECT datef, SUM(total)
						FROM llx_facture
						WHERE fk_cond_reglement = 2 AND fk_account = ".$account." AND MONTH(datef) = ".$month." AND YEAR(DATEf) = YEAR(CURDATE()) AND tva != 0
						GROUP BY datef
						ORDER BY datef ASC";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
		    $data[] = array(
					fecha=>$row->datef,
					total=> $row->total
		    );
		}
		return $data;
	}

	public function getTotalIVAFacturasACredito($db, $month, $account) {
		$sql = "SELECT datef, SUM(tva)
						FROM llx_facture
						WHERE fk_cond_reglement = 2 AND fk_account = ".$account." AND MONTH(datef) = ".$month." AND YEAR(DATEf) = YEAR(CURDATE()) AND tva != 0
						GROUP BY datef
						ORDER BY datef ASC";
		$result = $db->query($sql);

		if (!$result) {
				echo 'Error: '.$db->lasterror;
				die;
		}

		while ($row = $db->fetch_object($result))
		{
				$data[] = array(
					fecha=>$row->datef,
					total=> $row->total
				);
		}
		return $data;
	}

	public function getTotalFacturasSinIVAAContado($db, $month, $account) {
		$sql = "SELECT datef, SUM(total_ttc)
						FROM llx_facture
						WHERE fk_cond_reglement = 1 AND fk_account = ".$account." AND MONTH(datef) = ".$month." AND YEAR(DATEf) = YEAR(CURDATE()) AND tva = 0
						GROUP BY datef
						ORDER BY datef ASC";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
		    $data[] = array(
						fecha=>$row->datef,
		        total=> $row->total_ttc
		    );
		}
		return $data;
	}

	public function getTotalFacturasConIVAAContado($db, $month, $account) {
		$sql = "SELECT datef, SUM(total)
						FROM llx_facture
						WHERE fk_cond_reglement = 1 AND fk_account = ".$account." AND MONTH(datef) = ".$month." AND YEAR(DATEf) = YEAR(CURDATE()) AND tva != 0
						GROUP BY datef
						ORDER BY datef ASC";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
		    $data[] = array(
					fecha=>$row->datef,
					total=> $row->total
		    );
		}
		return $data;
	}

	public function getTotalIVAFacturasContado($db, $month, $account) {
		$sql = "SELECT datef, SUM(tva)
						FROM llx_facture
						WHERE fk_cond_reglement = 1 AND fk_account = ".$account." AND MONTH(datef) = ".$month." AND YEAR(DATEf) = YEAR(CURDATE()) AND tva != 0
						GROUP BY datef
						ORDER BY datef ASC";
		$result = $db->query($sql);

		if (!$result) {
				echo 'Error: '.$db->lasterror;
				die;
		}

		while ($row = $db->fetch_object($result))
		{
				$data[] = array(
					fecha=>$row->datef,
					total=> $row->total
				);
		}
		return $data;
	}

}
