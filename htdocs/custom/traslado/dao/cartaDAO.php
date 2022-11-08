<?php

class CartaDAO {

    var $db;

    function __construct($db) {
        $this->db = $db;
    }

    private function ExecuteQuery($sql) {
        $resql = $this->db->query($sql);
        return $resql;
    }

    public function GetLastInsertedId() {
        $sql = "SELECT rowid FROM cfdi_traslado ORDER BY rowid DESC LIMIT 1";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->rowid;
    }

    public function GetTrasladoById($trasladoId) {
        $sql = "SELECT * FROM cfdi_traslado WHERE rowid = '".$trasladoId."'";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

    public function GetTrasladosResult() {
        $sql = "SELECT * FROM cfdi_traslado WHERE fk_facture IS NOT NULL";
        $result = $this->ExecuteQuery($sql);
        return $result;
    }

    public function GetTraslados() {
        $sql = "SELECT * FROM cfdi_traslado  WHERE IS NOT NULL(fk_facture)";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

    public function InsertTraslado($object) {
        $sql = "INSERT INTO cfdi_traslado (fk_facture, fk_ubicacion_origen, fk_cliente, fecha_salida, fecha_llegada, distancia_recorrida, fk_transporte, fk_operador) 
        VALUES ('".$object["fk_facture"]."', '".$object["fk_ubicacion_origen"]."', '".$object["fk_cliente"]."', '".$object["fecha_salida"]."', '".$object["fecha_llegada"]."', '".$object["distancia_recorrida"]."', '".$object["fk_transporte"]."', '".$object["fk_operador"]."')";
        $result = $this->db->query($sql);
        if($result) {
            return $this->GetLastInsertedId();
        }
        
    }

    public function UpdateTraslado($id, $object) {
        $sql = "UPDATE cfdi_traslado SET fk_ubicacion_origen ='".$object->fk_ubicacion_origen."', fecha_salida= '".$object->fecha_salida."', fecha_llegada= '".$object->fecha_llegada."', distancia_recorrida= '".$object->distancia_recorrida."', fk_transporte= '".$object->fk_transporte."', fk_operador= '".$object->fk_operador."'
        WHERE rowid = ".$id;
        $result = $this->db->query($sql);
        return $result;
        
    }

    public function FetchDomicilioCliente($id) {
        $sql = "SELECT * FROM taasatsc_dolibarr.llx_cfdimx_domicilios_receptor 
        WHERE receptor_id = ".$id; 
        $sql.=" AND determinado = 1";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

	public function FetchEstadoById($id) {
        $sql = "SELECT * FROM taasatsc_dolibarr.cfdi_cod_estados 
        WHERE rowid = ".$id; 
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->name;
    }

    public function FetchConceptosDataCFDI($id) {
		$sql = "SELECT
			p.rowid AS id_concepto,
			qty AS cantidad,
			umed AS unidad,
			p.label AS descripcion,
			subprice AS valor_unitario,
			total_ht AS importe,
			total_ttc AS total,
			claveprodserv AS clave_prod_serv,
			umed AS clave_unidad,
			NULL AS descuento
		FROM
			llx_facturedet AS f
		JOIN llx_facturedet_extrafields AS fe ON f.rowid = fe.fk_object
		JOIN llx_product AS p ON fk_product = p.rowid
		WHERE
		fk_facture = ".$id;
		$result = $this->ExecuteQuery($sql);

		if (!$result) {
				echo 'Error: '. $this->db->lasterror;
				die;
		}

		while ($row =  $this->db->fetch_object($result))
		{
				$data[] = array(
                    Quantity=> $row->cantidad,
					ProductCode=>$row->clave_prod_serv,
                    UnitCode=>$row->clave_unidad,
                    Unit=>$row->unidad,
                    Description=> $row->descripcion,
					IdentificationNumber=>$row->id_concepto,
					UnitPrice=>0,
					Subtotal=> 0,
					Total=> 0
			);
		}
		return $data;
	}

    public function FetchMercanciasData($id, $origen, $destino) {
		$sql = "SELECT
			qty AS cantidad,
            pe.claveprodserv AS bienesTransp,
			p.label AS descripcion,
            'KGM' AS claveUnidad,
			peso_kg AS pesoEnKg,
			total_ttc AS valorMercancia,
            'MXN' AS moneda
		FROM
			llx_facturedet AS f
		JOIN llx_facturedet_extrafields AS fe ON f.rowid = fe.fk_object
		JOIN llx_product AS p ON fk_product = p.rowid
        JOIN llx_product_extrafields AS pe ON pe.fk_object = p.rowid
		WHERE
		fk_facture = ".$id;
		$result = $this->ExecuteQuery($sql);

		if (!$result) {
				echo 'Error: '. $this->db->lasterror;
				die;
		}

		while ($row =  $this->db->fetch_object($result))
		{
				$data[] = array(
                    Cantidad=> $row->cantidad,
					BienesTransp=>$row->bienesTransp,
                    Descripcion=>$row->descripcion,
                    ClaveUnidad=>$row->claveUnidad,
                    PesoEnKg=> $row->pesoEnKg,
					ValorMercancia=>round($row->valorMercancia, 2),
					Moneda=>$row->moneda,
                    MaterialPeligroso=>"No"
			);
		}
		return $data;
	}

    public function GetSocDataByFactureId($factureId) {
		$sql = "SELECT nom, siren, email FROM llx_facture as f JOIN llx_societe as s ON f.fk_soc = s.rowid WHERE f.rowid = '".$factureId."'";
		$result = $this->ExecuteQuery($sql);
		while ($row =  $this->db->fetch_object($result))
		{
				$data[] = array(
					name=>$row->nom,
					email=>$row->email,
					rfc=> $row->siren
			);
		}
		return $data;
	}

    public function UpdateControlTable($fk_traslado, $array_data)
    {
        $sql = 'INSERT INTO cfdi_control_table (
			generated_id,
			cfdi_type,
			Folio,
			fk_traslado,
			date,
			cert_number,
			receiver_rfc,
			uuid,
			cfdi_sign,
			sat_cert_number,
			sat_sign,
			rfc_prov_cert,
			status,
			original_string)
			VALUES';
		$sql.='(';
		$sql.="'".$array_data['Id']."', ";
		$sql.="'".$array_data['CfdiType']."', ";
		$sql.="'".$array_data['Folio']."', ";
		$sql.=$fk_traslado.', ';
		$sql.="'".$array_data['Date']."', ";
		$sql.="'".$array_data['CertNumber']."', ";
		$sql.="'".$array_data['Receiver']['Rfc']."', ";
		$sql.="'".$array_data['Complement']['TaxStamp']['Uuid']."', ";
		$sql.="'".$array_data['Complement']['TaxStamp']['CfdiSign']."', ";
		$sql.="'".$array_data['Complement']['TaxStamp']['SatCertNumber']."', ";
		$sql.="'".$array_data['Complement']['TaxStamp']['SatSign']."', ";
		$sql.="'".$array_data['Complement']['TaxStamp']['RfcProvCertif']."', ";
		$sql.="'".$array_data['Status']."', ";
		$sql.="'".$array_data['OriginalString']."')";
		$this->ExecuteQuery($sql);
    }
    
    public function InsertIntoCFDIComprobante($array_data) {
		$sql = 'INSERT INTO cfdi_comprobante (
        serie,    
		folio,
		tipo_comprobante,
        fk_traslado) 
		VALUES (';
        $sql.="'".$array_data[0]['serie']."'".", ";
		$sql.="'".$array_data[0]['folio']."'".", ";
		$sql.="'".$array_data[0]['tipo_comprobante']."'".", ";
		$sql.=$array_data[0]['fk_traslado'];
		$sql.= ')';
		$this->ExecuteQuery($sql);
	}

    public function GetComprobanteIdFromFactureId($trasladoId) {
		$sql = "SELECT id FROM cfdi_comprobante WHERE fk_traslado = '".$trasladoId."'";
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->id;
	}

    public function SearchForFacture($factureName) {
		$sql = "SELECT rowid FROM llx_facture WHERE facnumber LIKE '%".$factureName."%' LIMIT 1";
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->rowid;
	}

    public function UpdateUUID($fk_traslado, $array_data)
    {
        $sql = "UPDATE cfdi_traslado SET status = 1, UUID = '".$array_data['Uuid']."' WHERE rowid= ".$fk_traslado;
		$result = $this->ExecuteQuery($sql);
		return $result;
    }

    public function CheckForDuplicate($fk_traslado) {
		$sql = "SELECT * FROM cfdi_comprobante WHERE fk_traslado = ".$fk_traslado." LIMIT 1";
		$result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
		return $row;
	}

	public function GetEstados() {
		$sql = "SELECT * FROM cfdi_cod_estados";
		$result = $this->ExecuteQuery($sql);
        while ($row =  $this->db->fetch_object($result))
		{
				$data[] = array(
                    rowid=>$row->rowid,
					name=>$row->name
			);
		}
		return $data;
	}

	public function GetEstadoById($estadoId) {
		$sql = "SELECT name FROM cfdi_cod_estados WHERE rowid =".$estadoId;
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row;
	}
	
	public function GetCFDIId($fk_traslado) {
		$sql = "SELECT Folio FROM cfdi_control_table WHERE fk_traslado =".$fk_traslado;
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->Folio;
	}

	public function CheckForDuplicate($fk_traslado) {
		$sql = "INSERT INTO duplicate_avoid2 (fk_traslado) VALUES ('".$fk_traslado."')";
		$result = $this->ExecuteQuery($sql);
		return $result;
	}

	public function SendCFDI($cfdiId, $email) {
		//$sendUrl = 'https://api.facturama.mx/cfdi?cfdiType=issued&cfdiId='.$cfdiId.'&email='.$email;
		$sendUrl = 'https://apisandbox.facturama.mx/cfdi?cfdiType=issued&cfdiId='.$cfdiId.'&email='.$email;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_POST, 1);
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $sendUrl);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization: Basic bG1pcmExOTpMdWlzYXp1bF8xOQ==',
		'Content-Type: application/json',
		'Content-Length: 0'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        $result = curl_exec($curl);
        if(!$result){die("Connection Failure");}
		curl_close($curl);
        return $result;
	}
}