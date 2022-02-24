<?php

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

	public function getTotalFacturasSinIVAACredito($db, $month, $year, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.total_ttc
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(total_ttc) AS total_ttc
		FROM
			llx_facture
		WHERE
			fk_cond_reglement = 2
		AND fk_account = ".$account."
			AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = '".$year."' ";
		$sql.= "AND tva = 0
		GROUP BY
			datef
		ORDER BY
			datef ASC
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = '".$year."'";
		$result = $db->query($sql);	

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->total_ttc) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->total_ttc;
			}
		    $data[] = array(
				fecha=>$row->datef,
		        total=> $totalTemp
		    );
		}
		return $data;
	}

	public function getTotalSoloTicketsSinIVAACredito($db, $month, $year, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.total_ttc
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(total_ttc) AS total_ttc
		FROM
			llx_facture as f
		JOIN
			llx_facture_extrafields as fe
        ON f.rowid = fe.fk_object
		WHERE
			fk_cond_reglement = 2
		AND fk_account = ".$account."
		AND fe.isticket = 1
		AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = '".$year."' ";
		$sql.= "AND tva = 0
		GROUP BY
			datef
		ORDER BY
			datef ASC
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = '".$year."'";
		$result = $db->query($sql);	

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->total_ttc) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->total_ttc;
			}
		    $data[] = array(
				fecha=>$row->datef,
		        total=> $totalTemp
		    );
		}
		return $data;
	}

	public function getTotalSoloFacturasSinIVAACredito($db, $month, $year, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.total_ttc
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(total_ttc) AS total_ttc
		FROM
			llx_facture as f
		JOIN
			llx_facture_extrafields as fe
        ON f.rowid = fe.fk_object
		WHERE
			fk_cond_reglement = 2
		AND fk_account = ".$account."
		AND (isticket != 1 OR ISNULL(isticket))
		AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = '".$year."' ";
		$sql.= "AND tva = 0
		GROUP BY
			datef
		ORDER BY
			datef ASC
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = '".$year."'";
		$result = $db->query($sql);	

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->total_ttc) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->total_ttc;
			}
		    $data[] = array(
				fecha=>$row->datef,
		        total=> $totalTemp
		    );
		}
		return $data;
	}

	public function getTotalFacturasConIVAACredito($db, $month, $year, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.total
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(total) AS total
		FROM
			llx_facture
		WHERE
			fk_cond_reglement = 2
		AND fk_account = ".$account."
			AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = ".$year;
		$sql.= " AND tva != 0
		GROUP BY
			datef
		ORDER BY
			datef ASC
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = '".$year."'";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->total) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->total;
			}
		    $data[] = array(
					fecha=>$row->datef,
					total=> $totalTemp
		    );
		}
		return $data;
	}

	public function getTotalSoloTicketsConIVAACredito($db, $month, $year, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.total
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(total) AS total
		FROM
			llx_facture as f
		JOIN
			llx_facture_extrafields as fe
        ON f.rowid = fe.fk_object
		WHERE
			fk_cond_reglement = 2
		AND fk_account = ".$account."
		AND fe.isticket = 1
		AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = ".$year;
		$sql.= " AND tva != 0
		GROUP BY
			datef
		ORDER BY
			datef ASC
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = '".$year."'";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->total) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->total;
			}
		    $data[] = array(
					fecha=>$row->datef,
					total=> $totalTemp
		    );
		}
		return $data;
	}

	public function getTotalSoloFacturasConIVAACredito($db, $month, $year, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.total
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(total) AS total
		FROM
			llx_facture as f
		JOIN
			llx_facture_extrafields as fe
        ON f.rowid = fe.fk_object
		WHERE
			fk_cond_reglement = 2
		AND fk_account = ".$account."
		AND (isticket != 1 OR ISNULL(isticket))
		AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = ".$year;
		$sql.= " AND tva != 0
		GROUP BY
			datef
		ORDER BY
			datef ASC
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = '".$year."'";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->total) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->total;
			}
		    $data[] = array(
					fecha=>$row->datef,
					total=> $totalTemp
		    );
		}
		return $data;
	}

	public function getTotalIVAFacturasACredito($db, $month, $year, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.tva
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(tva) AS tva
		FROM
			llx_facture
		WHERE
			fk_cond_reglement = 2
		AND fk_account = ".$account."
			AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = ".$year;
	$sql.= " AND tva != 0
		GROUP BY
			datef
		ORDER BY
			datef ASC
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = '".$year."'";

		$result = $db->query($sql);

		if (!$result) {
				echo 'Error: '.$db->lasterror;
				die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->tva) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->tva;
			}
				$data[] = array(
					fecha=>$row->datef,
					total=> $totalTemp
				);
		}
		return $data;
	}

	public function getTotalIVASoloTicketsACredito($db, $month, $year, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.tva
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(tva) AS tva
		FROM
			llx_facture as f
			JOIN
			llx_facture_extrafields as fe
        ON f.rowid = fe.fk_object
		WHERE
			fk_cond_reglement = 2
		AND fk_account = ".$account."
		AND fe.isticket = 1
			AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = ".$year;
	$sql.= " AND tva != 0
		GROUP BY
			datef
		ORDER BY
			datef ASC
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = '".$year."'";

		$result = $db->query($sql);

		if (!$result) {
				echo 'Error: '.$db->lasterror;
				die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->tva) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->tva;
			}
				$data[] = array(
					fecha=>$row->datef,
					total=> $totalTemp
				);
		}
		return $data;
	}

	public function getTotalIVASoloFacturasACredito($db, $month, $year, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.tva
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(tva) AS tva
		FROM
			llx_facture as f
			JOIN
			llx_facture_extrafields as fe
        ON f.rowid = fe.fk_object
		WHERE
			fk_cond_reglement = 2
		AND fk_account = ".$account."
		AND (isticket != 1 OR ISNULL(isticket))
			AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = ".$year;
	$sql.= " AND tva != 0
		GROUP BY
			datef
		ORDER BY
			datef ASC
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = '".$year."'";

		$result = $db->query($sql);

		if (!$result) {
				echo 'Error: '.$db->lasterror;
				die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->tva) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->tva;
			}
				$data[] = array(
					fecha=>$row->datef,
					total=> $totalTemp
				);
		}
		return $data;
	}

	public function getTotalFacturasSinIVAAContado($dateArray, $db, $month, $account) {
		$sql = "SELECT DISTINCT
		dateo,
		t1.total_ttc
	FROM
		llx_bank
	LEFT OUTER JOIN (
		SELECT
			datef,
			SUM(total_ttc) AS total_ttc
		FROM
			llx_facture
		WHERE
			fk_cond_reglement = 1
		AND fk_account = ".$account."
		AND MONTH (datef) = ".$month."
		AND YEAR (DATEf) = YEAR (CURDATE())
		AND tva = 0
		GROUP BY
			datef
	) t1 ON t1.datef = dateo
	WHERE
		MONTH (dateo) = ".$month."
	AND YEAR (dateo) = YEAR (CURDATE())";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}
		$i = 0;

		while ($row = $db->fetch_object($result))
		{
			for($j = 0; $j < sizeof($dateArray); $j++) {

			}
			if(!$row->total_ttc) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->total_ttc;
			}
		    $data[] = array(
				fecha=>$row->datef,
		        total=> $totalTemp
		    );
		}
		return $data;
	}

	public function getTotalFacturasConIVAAContado($db, $month, $account) {
		$sql = "SELECT DISTINCT
	dateo,
	t1.total
FROM
	llx_bank
LEFT OUTER JOIN (
	SELECT
		datef,
		SUM(total) AS total
	FROM
		llx_facture
	WHERE
		fk_cond_reglement = 1
	AND fk_account = ".$account."
	AND MONTH (datef) = ".$month."
	AND YEAR (DATEf) = YEAR (CURDATE())
	AND tva != 0
	GROUP BY
		datef
) t1 ON t1.datef = dateo
WHERE
	MONTH (dateo) = ".$month."
AND YEAR (dateo) = YEAR (CURDATE())";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->total) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->total;
			}
		    $data[] = array(
					fecha=>$row->datef,
					total=> $totalTemp
		    );
		}
		return $data;
	}

	public function getTotalIVAFacturasContado($db, $month, $account) {
		$sql = "SELECT DISTINCT
    	dateo,
    	t1.tva
        FROM
        	llx_bank
        LEFT OUTER JOIN (
        	SELECT
        		datef,
        		SUM(tva) AS tva
        	FROM
        		llx_facture
        	WHERE
        		fk_cond_reglement = 1
        	AND fk_account = ".$account."
        	AND MONTH (datef) = ".$month."
        	AND YEAR (DATEf) = YEAR (CURDATE())
        	AND tva != 0
        	GROUP BY
        		datef
        	ORDER BY
        		datef ASC
        ) t1 ON t1.datef = dateo
        WHERE
        	MONTH (dateo) = ".$month."
        AND YEAR (dateo) = YEAR (CURDATE())";
		$result = $db->query($sql);

		if (!$result) {
				echo 'Error: '.$db->lasterror;
				die;
		}

		while ($row = $db->fetch_object($result))
		{
			if(!$row->tva) {
				$totalTemp = 0;
			} else {
				$totalTemp = $row->tva;
			}
				$data[] = array(
					fecha=>$row->datef,
					total=> $totalTemp
				);
		}
		return $data;
	}

    public function GetTotalVendidoPorDia($db, $datef, $account) {
        $sql = "
        SELECT SUM(total_ttc) as vendido
        FROM llx_facture AS f
        WHERE f.datef = '".$datef."'"; 
        $sql.=" AND fk_statut != 3 
        AND f.fk_account = ".$account;
        $sql.=" AND date_valid IS NOT NULL";
        $result = $db->query($sql);

        if (!$result) {
            echo 'Error: '.$db->lasterror;
            die;
        }
        $row = $db->fetch_object($result);
        return $row->vendido;
    }

	public function getTotalIVACobrado($db, $month, $year, $account) {
		$sql = "SELECT b.dateo, pf.amount, b.fk_account, f.rowid, f.facnumber, f.total, f.total_ttc, f.tva
			FROM
			llx_bank AS b
			JOIN
			llx_paiement AS p ON p.fk_bank = b.rowid
			JOIN
			llx_paiement_facture as pf ON pf.fk_paiement = p.rowid
			JOIN
			llx_facture as f ON f.rowid = pf.fk_facture
			WHERE
			b.fk_account = ".$account."
			AND MONTH(b.dateo) = ".$month."
			AND YEAR(b.dateo) = ".$year;
		$sql.= " AND f.tva != 0";
		$sql.= " AND f.fk_cond_reglement != 1";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		$fechaTemp = '';
		$iva = array_fill(0,31,0);
		$date = array();
		$ivaArray = array();
		$i = -1;

		while ($row = $db->fetch_object($result))
		{
			if($fechaTemp != $row->dateo) {
				$fechaTemp = $row->dateo;
				$i++;
			}
			$percentage = (($row->amount * 100) / $row->total_ttc);
			$ivapagado = (($percentage / 100 ) * $row->tva);
			$date[$i] =  $fechaTemp;
			$iva[$i] +=  $ivapagado;
		}
		$ivaArray["iva"] = $iva;
		$ivaArray["fecha"] = $date;
		return $ivaArray;
	}

	public function getTotalCobradoCreditoPerAccount($db, $month, $year, $account) {
		$sql = "SELECT b.dateo, b.rowid AS id_pago, pf.amount, b.fk_account, f.rowid, f.facnumber, f.total, f.total_ttc, f.tva
			FROM
			llx_bank AS b
			JOIN
			llx_paiement AS p ON p.fk_bank = b.rowid
			JOIN
			llx_paiement_facture as pf ON pf.fk_paiement = p.rowid
			JOIN
			llx_facture as f ON f.rowid = pf.fk_facture
			WHERE
			b.fk_account = ".$account."
			AND MONTH(b.dateo) = ".$month."
			AND YEAR(b.dateo) = ".$year;
		$sql.= " AND f.fk_cond_reglement != 1";
		$result = $db->query($sql);

		if (!$result) {
		    echo 'Error: '.$db->lasterror;
		    die;
		}

		$fechaTemp = '';
		$amountTotal = 0;
		$i = -1;

		while ($row = $db->fetch_object($result))
		{
			if($fechaTemp != $row->dateo) {
				$fechaTemp = $row->dateo;
				$i++;
			}
			$amountTotal += $row->amount;
		}

		return $amountTotal;
	}


	

}


