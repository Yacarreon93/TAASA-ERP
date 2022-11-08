<?php

class TrasladoDAO {

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

	public function GetLastProductInsertedId() {
        $sql = "SELECT rowid FROM cfdi_movimiento_almacen ORDER BY rowid DESC LIMIT 1";
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
        $sql = "SELECT * FROM cfdi_traslado WHERE fk_facture IS NULL";
        $result = $this->ExecuteQuery($sql);
        return $result;
    }

    public function GetTraslados() {
        $sql = "SELECT * FROM cfdi_traslado";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

	public function InsertTraslado($object) {
        $sql = "INSERT INTO cfdi_traslado (fk_ubicacion_origen, fk_ubicacion_destino, fecha_salida, fecha_llegada, distancia_recorrida, fk_transporte, fk_operador) 
        VALUES ('".$object["fk_ubicacion_origen"]."', '".$object["fk_ubicacion_destino"]."', '".$object["fecha_salida"]."', '".$object["fecha_llegada"]."', '".$object["distancia_recorrida"]."', '".$object["fk_transporte"]."', '".$object["fk_operador"]."')";
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

	public function DeleteTraslado($id) {
        $sql = "DELETE FROM cfdi_traslado WHERE rowid = ".$id;
        $result = $this->db->query($sql);
        return $result;
        
    }

	public function ValidateTraslado($id) {
        $sql = "UPDATE cfdi_traslado SET state =1
		WHERE rowid = ".$id;
        $result = $this->db->query($sql);
        return $result;
        
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

	public function FetchEstadoById($id) {
        $sql = "SELECT * FROM taasatsc_dolibarr.cfdi_cod_estados 
        WHERE rowid = ".$id; 
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->name;
    }

	public function GetComprobanteIdFromFactureId($trasladoId) {
		$sql = "SELECT id FROM cfdi_comprobante WHERE fk_traslado = '".$trasladoId."'";
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->id;
	}

	public function FetchConceptosDataCFDI($id) {
		$sql = "SELECT
		p.rowid AS id_concepto,
		qty AS cantidad,
		umed AS unidad,
		p.label AS descripcion,
		claveprodserv AS clave_prod_serv,
		umed AS clave_unidad,
		NULL AS descuento
		FROM
			cfdi_movimiento_almacen AS m
		JOIN llx_product AS p ON p.rowid = m.fk_product
		JOIN llx_product_extrafields AS pe ON pe.fk_object = p.rowid
		WHERE
		m.fk_traslado = ".$id;
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

	public function FetchMercanciasData($id) {
		$sql = "SELECT
		p.rowid AS id_concepto,
		qty AS cantidad,
		p.label AS descripcion,
		claveprodserv AS bienesTransp,
		umed AS claveUnidad,
		pesoenkg AS pesoEnKg,
		valor_mercancia AS valorMercancia
		FROM
			cfdi_movimiento_almacen AS m
		JOIN llx_product AS p ON p.rowid = m.fk_product
		JOIN llx_product_extrafields AS pe ON pe.fk_object = p.rowid
		WHERE
		m.fk_traslado = ".$id;
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
					Moneda=>"MXN",
                    MaterialPeligroso=>"No"
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

	public function UpdateUUID($fk_traslado, $array_data)
    {
        $sql = "UPDATE cfdi_traslado SET status = 1, UUID = '".$array_data['Uuid']."' WHERE rowid= ".$fk_traslado;
		$result = $this->ExecuteQuery($sql);
		return $result;
    }

	public function CheckForDuplicate($fk_traslado) {
		$sql = "INSERT INTO duplicate_avoid2 (fk_traslado) VALUES ('".$fk_traslado."')";
		$result = $this->ExecuteQuery($sql);
		
		return $result;
	}

	public function GetCFDIId($fk_traslado) {
		$sql = "SELECT Folio FROM cfdi_control_table WHERE fk_traslado =".$fk_traslado;
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->Folio;
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

	public function InsertProductLine($object) {
        $sql = "INSERT INTO cfdi_movimiento_almacen (fk_traslado, fk_product, description, qty, pesoenkg, valor_mercancia) 
        VALUES ('".$object[0]["fk_traslado"]."', '".$object[0]["fk_product"]."', '".$object[0]["description"]."', '".$object[0]["qty"]."', '".$object[0]["pesoenkg"]."', ".$object[0]["valor_mercancia"].")";
        $result = $this->db->query($sql);
        if($result) {
            return $this->GetLastProductInsertedId();
        }
        
    }

	public function DeleteProducts($id) {
        $sql = "DELETE FROM cfdi_movimiento_almacen WHERE fk_traslado = ".$id;
        $result = $this->db->query($sql);
        return $result;
    }

	public function DeleteProductLine($id, $lineid) {
        $sql = "DELETE FROM cfdi_movimiento_almacen WHERE rowid = ".$lineid;
        $result = $this->db->query($sql);
        return $result;
        
    }


    public function PrintObjectLines($langs, $fk_traslado)
	{
		$selected=0;
		$dateSelector=0;
		//global $conf, $hookmanager, $inputalsopricewithtax, $usemargins, $langs, $user;

		// Define usemargins
		$usemargins=1;

		print '<tr class="liste_titre nodrag nodrop">';

		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td align="center" width="5">&nbsp;</td>';

		// Description
		print '<td>'.$langs->trans('Description').'</td>';

		// if ($this->element == 'askpricesupplier')
		// {
		// 	print '<td align="right"><span id="title_fourn_ref">'.$langs->trans("AskPriceSupplierRefFourn").'</span></td>';
		// }

		// Price HT
		print '<td align="right" width="80" id="PriceUHT">'.$langs->trans('PriceUHT').'</td>';

		if ($inputalsopricewithtax) print '<td align="right" width="80">'.$langs->trans('PriceUTTC').'</td>';

		// Qty
		print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';

		if($conf->global->PRODUCT_USE_UNITS)
		{
			print '<td align="left" width="50">'.$langs->trans('Unit').'</td>';
		}

		if ($usemargins && ! empty($conf->margin->enabled) )
		{
			if ($conf->global->MARGIN_TYPE == "1")
				print '<td align="right" class="margininfos" width="80">'.$langs->trans('BuyingPrice').'</td>';
			else
				print '<td align="right" class="margininfos" width="80">'.$langs->trans('CostPrice').'</td>';

			if (! empty($conf->global->DISPLAY_MARGIN_RATES) && $user->rights->margins->liretous)
				print '<td align="right" class="margininfos" width="50">'.$langs->trans('MarginRate').'</td>';
			if (! empty($conf->global->DISPLAY_MARK_RATES) && $user->rights->margins->liretous)
				print '<td align="right" class="margininfos" width="50">'.$langs->trans('MarkRate').'</td>';
		}

		// Total HT
		print '<td align="right" width="50" id="TotalHT">Valor</td>';

		print '<td></td>';  // No width to allow autodim

		print '<td width="10"></td>';

		print '<td width="10"></td>';

		print "</tr>\n";

        $sql = "SELECT * FROM cfdi_movimiento_almacen WHERE fk_traslado=".$fk_traslado;
		$result = $this->ExecuteQuery($sql);
        while ($row =  $this->db->fetch_object($result))
		{
				$data[] = array(
                    rowid=>$row->rowid,
                    fk_traslado=>$row->fk_traslado,
					fk_product=>$row->fk_product,
                    description=>$row->description,
                    qty=>$row->qty,
					pesoenkg=>$row->pesoenkg,
                    valor_mercancia=>$row->valor_mercancia
			);
		}

		
		$num = count($data);
		$var = true;
		$i	 = 0;

		// //Line extrafield
		// require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		// $extrafieldsline = new ExtraFields($this->db);
		// $extralabelslines=$extrafieldsline->fetch_name_optionals_label($this->table_element_line);

		for ($i=0; $i < $num; $i ++)
        {
            $prod = new Product($this->db);
			$objectProduct=$prod->fetch($data[$i]["fk_product"]);
            print '<tr id="row-'.$data[$i]["rowid"].'" class="impair drag drop">';
            print '<td><a class="classfortooltip" href="/product/card.php?id='.$data[$i]["fk_product"].'">';
            print '<img class="classfortooltip" src="/theme/eldy/img/object_product.png"';
            print '</a>';
            print '<a class="classfortooltip" href="/product/card.php?id='.$data[$i]["fk_product"].'">';
            print $objectProduct->ref;
            print '</a>';
            print $data[$i]["description"];
            print '</td>';
			print '<td class="nowrap" align="right">'.$data[$i]["valor_mercancia"].'</td>';
			print '<td class="nowrap" align="right">'.$data[$i]["qty"].'</td>';
			print '<td class="nowrap" align="right">'.($data[$i]["qty"]*$data[$i]["valor_mercancia"]).'</td>';
			print '<td><a href="/custom/traslado/traslado/card.php?action=deleteline&id='.$fk_traslado.'&lineid='.$data[$i]["rowid"].'">';
            print '<img src="/theme/eldy/img/delete.png"';
            print '</a>';
            print '</td>';
            print '</tr>';
        }

	}
}
