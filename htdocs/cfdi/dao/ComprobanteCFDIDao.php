<?php

/**
 *  \file       htdocs/product/list.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 */
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("stocks");
$langs->load("products");
$langs->load("suppliers");

$page     = GETPOST('page', 'int');
$action   = GETPOST('action');
$column   = GETPOST('column', 'int');
$stock_id = GETPOST('stock_id', 'int');

if (!$page) $page = 1;
if (!$stock_id) $stock_id = 1;

// @TODO: Security check

define("CFDI_COMPROBANTE", "cfdi_comprobante");
define("CFDI_COMPROBANTE_RELACIONADOS", "cfdi_comprobante_relacionados");
define("CFDI_CONCEPTOS", "cfdi_conceptos");
define("CFDI_CONCEPTOS_PARTE", "cfdi_conceptos_parte");
define("CFDI_CONCEPTOS_TIPO_IMPUESTO", "cfdi_conceptos_tipo_impuesto");
define("CFDI_IMPUESTOS_TOTALES", "cfdi_impuestos_totales");
define("CFDI_IMPUESTOS_GLOBALES", "cfdi_impuestos_globales");
define("CFDI_COMPLEMENTO_PAGO", "cfdi_complemento_pago");
define("CFDI_DOC_RELACIONADO", "cfdi_doc_relacionado");


class ComprobanteCFDIDao {

	var $db;

	function __construct($db) {
		$this->db = $db;
	}

	private function ExecuteQuery($sql) {
		$resql = $this->db->query($sql);
		return $resql;
	}

	public function GetLastInsertedId() {
		$sql = "SELECT id FROM cfdi_comprobante ORDER BY id DESC LIMIT 1";
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->id;
	}

	public function GetComprobanteIdByFactureId($factureId) {
		$sql = "SELECT id FROM cfdi_comprobante WHERE fk_comprobante = '".$factureId."'";
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->id;
	}

	public function GetComprobanteIdByPaymentId($paymentId) {
		$sql = "SELECT id FROM cfdi_comprobante WHERE fk_payment = '".$paymentId."'";
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->id;
	}

	private function getDb() {
		return $this->db;
	}

	public function GetVendorAddress($vendor_id) {
		$sql = "SELECT zip FROM llx_user WHERE rowid = '".$vendor_id."'";
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->zip;
	}
	public function GetSocIdByFactureId($factureId) {
		$sql = "SELECT fk_soc FROM llx_facture WHERE rowid = '".$factureId."'";
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->fk_soc;
	}

	public function GetVendorAddressByFactureId($factureId) {
		$sql = "SELECT
		zip
		FROM llx_facture AS f
		JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
		JOIN llx_user AS u ON fe.vendor = u.rowid
		WHERE f.rowid = '".$factureId."'";
		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row->zip;
	}

	public function FetchConceptosData($id) {
		$sql = "SELECT
			p.rowid AS id_concepto,
			qty AS cantidad,
			umed AS unidad,
			p.label AS descripcion,
			subprice AS valor_unitario,
			total_ht AS importe,
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
					id_concepto=>$row->id_concepto,
					cantidad=> $row->cantidad,
					unidad=>$row->unidad,
					no_identificacion=>NULL,
					descripcion=> $row->descripcion,
					valor_unitario=>$row->valor_unitario,
					importe=> $row->importe,
					num_cuenta_predial=>NULL,
					clave_prod_serv=>$row->clave_prod_serv,
					clave_unidad=>$row->clave_unidad,
					descuento=> 0,
					fk_comprobante=>$id
			);
		}
		return $data;
	}

	public function FetchImpuestosData($id) {
		$sql = "SELECT
			p.rowid AS id_concepto,
			'T' AS tipo_impuesto_federal,
			total_tva AS importe,
			'002' AS impuesto,
			f.tva_tx AS tasa_o_cuota,
			'Tasa' AS tipo_factor,
			total_ht AS base
		FROM
			llx_facturedet AS f
		JOIN llx_product AS p ON fk_product = p.rowid
		WHERE
			fk_facture = ".$id;
		$result = $this->ExecuteQuery($sql);
		$impuestosFlag = false;

		while ($row =  $this->db->fetch_object($result))
		{
				$tasa_o_cuota = ($row->tasa_o_cuota / 100);
				if($tasa_o_cuota == 0.16) {
					$string_tasa_o_cuota = "0.160000";
				} else {
					$string_tasa_o_cuota = "0.000000";
				}
				$data[] = array(
					id_concepto=>$row->id_concepto,
					tipo_impuesto_federal=> $row->tipo_impuesto_federal,
					importe=>$row->importe,
					impuesto=>$row->impuesto,
					tasa_o_cuota=>$string_tasa_o_cuota,
					tipo_factor=>$row->tipo_factor,
					base=> $row->base,
					fk_comprobante=>$id
			);
		}
		return $data;
	}

	public function InsertIntoConceptosComprobante($array_data, $lastId) {
		$sql = 'INSERT INTO '.CFDI_CONCEPTOS .' (
		fk_cfdi,
		fk_comprobante,
		id_concepto,
		cantidad,
		unidad,
		no_identificacion,
		descripcion,
		valor_unitario,
		importe,
		num_cuenta_predial,
		clave_prod_serv,
		clave_unidad) 
		VALUES ';
		for($i=0; $i < sizeof($array_data); $i++) {
			$sql.="(".$lastId.", ";
			$sql.=$array_data[$i]['fk_comprobante'].", ";
			$sql.="'".$array_data[$i]['id_concepto']."'".", ";
			$sql.=$array_data[$i]['cantidad'].", ";
			$sql.="'".$array_data[$i]['unidad']."'".", ";
			$sql.="'', ";
			$sql.="'".$array_data[$i]['descripcion']."'".", ";
			$sql.=$array_data[$i]['valor_unitario'].", ";
			$sql.=$array_data[$i]['importe'].", ";
			$sql.="'', ";
			$sql.="'".$array_data[$i]['clave_prod_serv']."'".", ";
			$sql.="'".$array_data[$i]['clave_unidad']."')"; 
			if($i < (count($array_data)-1)) {
				$sql.= ',';
			}  
		}
		$this->ExecuteQuery($sql);
	}	

	public function FetchComprobanteData($id) {
		$sql = "SELECT f.rowid, facnumber AS serie, f.rowid AS folio, p.accountancy_code AS forma_pago, pt.traduccion AS condiciones_pago, total AS subtotal, 0 AS descuento, NULL AS motivo_descuento, currency AS moneda, total_ttc AS total, NULL AS total_con_letra, formpagcfdi AS metodo_pago, NULL AS num_cuenta_pago, vendor, fk_soc, usocfdi AS uso_cfdi 
			FROM llx_facture AS f
			JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
			LEFT JOIN llx_c_paiement AS p ON f.fk_mode_reglement = p.id
			LEFT JOIN llx_c_payment_term AS pt ON f.fk_cond_reglement = pt.rowid
			WHERE f.rowid = ".$id;
		$result = $this->ExecuteQuery($sql);

		while ($row = $this->db->fetch_object($result))
		{
				$moneda = $row->moneda;
				if($moneda == "MXN") {
					$tipo_cambio= 0; 
				} else {
					$tipo_cambio= 19.5;
				}
				$lugar_de_expedicion = $this->GetVendorAddress($row->vendor);
				$data[] = array(
					serie=>$row->serie,
					folio=> $row->folio,
					forma_pago=>$row->forma_pago,
					condiciones_pago=> $row->condiciones_pago,
					subtotal=>$row->subtotal,
					descuento=> 0,
					motivo_descuento=>$row->motivo_descuento,
					moneda=>$moneda,
					tipo_cambio=>$tipo_cambio,
					total=> $row->total,
					total_con_letra=>$row->total_con_letra,
					metodo_pago=> $row->metodo_pago,
					tipo_comprobante=>'FA',
					diseno=> NULL,
					status=> 0,
					lugar_de_expedicion=> $lugar_de_expedicion,
					num_cuenta_pago=> NULL,
					version=> 3.3,
					confirmacion=> NULL,
					fk_comprobante=>$id,
					fk_soc=>$row->fk_soc,
					uso_cfdi=>$row->uso_cfdi
			);
		}
		return $data;
	}

	public function InsertIntoCFDIComprobante($array_data) {
		$sql = 'INSERT INTO '.CFDI_COMPROBANTE .' (
		serie,
		folio,
		forma_pago,
		condiciones_pago,
		subtotal,
		tipo_cambio,
		moneda,
		total,
		total_con_letra,
		metodo_pago,
		tipo_comprobante,
		diseno,
		status,
		lugar_de_expedicion,
		num_cuenta_pago,
		version,
		fk_comprobante,
		fk_soc,
		uso_cfdi) 
		VALUES (';
		$sql.="'".$array_data[0]['serie']."'".", ";
		$sql.="'".$array_data[0]['folio']."'".", ";
		$sql.="'".$array_data[0]['forma_pago']."'".", ";
		$sql.="'".$array_data[0]['condiciones_pago']."'".", ";
		$sql.=$array_data[0]['subtotal'].", ";
		$sql.=$array_data[0]['tipo_cambio'].", ";
		$sql.="'".$array_data[0]['moneda']."'".", ";
		$sql.=$array_data[0]['total'].", ";
		$sql.="'".$array_data[0]['total_con_letra']."'".", ";
		$sql.="'".$array_data[0]['metodo_pago']."'".", ";
		$sql.="'".$array_data[0]['tipo_comprobante']."'".", ";
		$sql.="'".$array_data[0]['diseno']."'".", ";
		$sql.=$array_data[0]['status'].", ";
		$sql.="'".$array_data[0]['lugar_de_expedicion']."'".", ";
		$sql.="'".$array_data[0]['num_cuenta_pago']."'".", ";
		$sql.="'".$array_data[0]['version']."'".", ";
		$sql.=$array_data[0]['fk_comprobante'].", ";
		$sql.=$array_data[0]['fk_soc'].", ";
		$sql.="'".$array_data[0]['uso_cfdi']."'";
		$sql.= ')';
		$this->ExecuteQuery($sql);
	}

	public function InsertIntoCFDIMXComprobante($array_data) {
		$sql = "
				INSERT INTO ".MAIN_DB_PREFIX."cfdimx (
					factura_serie,
					factura_folio,
					factura_seriefolio,
					xml,
					cadena,
					version,
					selloCFD,
					fechaTimbrado,
					uuid,
					certificado,
					sello,
					certEmisor,
					cancelado,
					u4dig,
					fk_facture,
					fecha_emision,
					hora_emision,
					fecha_timbrado,
					hora_timbrado,
					tipo_timbrado,
					divisa,
					entity_id
				) VALUES (
					'".$array_data[0]['serie']."',
					'".$array_data[0]['folio']."',
					'".$array_data[0]['serie']."-".$array_data[0]['folio']."',
					'Pendiente',
					'Pendiente',
					'".$array_data[0]['version']."',
					'Pendiente',
					'".date("Y-m-d H:i:s")."',
					'Pendiente',
					'Pendiente',
					'Pendiente',
					'Pendiente',
					'0',
					'0',
					'".$array_data[0]['fk_comprobante']."',
					'".date("Y-m-d")."',
					'".date("H:i:s")."',
					'".date("Y-m-d")."',
					'".date("H:i:s")."',
					'1',
					'MXN',
					'".$_SESSION['dol_entity']."'
				)";
		$this->ExecuteQuery($sql);
	}

	public function InsertIntoComprobanteRelacionados($array_data) {
		$sql = 'INSERT INTO '.CFDI_COMPROBANTE_RELACIONADOS .' (
		fk_comprobante,
		tipo_relacion) 
		VALUES ';
		$sql.='(';
		$sql.=$array_data[0]['fk_comprobante'].", ";
		$sql.="'".$array_data[0]['tipo_relacion']."'";
		$sql.=')'; 
		$this->ExecuteQuery($sql);
	}

		public function InsertIntoConceptosTipoImpuesto($array_data, $lastId) {
		$sql = 'INSERT INTO '.CFDI_CONCEPTOS_TIPO_IMPUESTO .' (
		fk_cfdi,
		fk_comprobante,
		id_concepto,
		tipo_impuesto_federal,
		importe,
		impuesto,
		tasa_o_cuota,
		tipo_factor,
		base) 
		VALUES';

		$total = 0;
		$totalTasa0 = 0;
		$totalTasa16 = 0;
		for($i=0; $i < sizeof($array_data); $i++) {
			if($array_data[$i]['tasa_o_cuota'] == "0.000000") {
				$totalTasa0 += 1;
			} else {
				$totalTasa16 += $array_data[$i]['importe'];
			}
			$total+=$array_data[$i]['importe'];
			$sql.='(';
			$sql.=$lastId.', ';
			$sql.=$array_data[$i]['fk_comprobante'].', ';
			$sql.="'".$array_data[$i]['id_concepto']."'".', ';
			$sql.="'".$array_data[$i]['tipo_impuesto_federal']."'".', ';
			$sql.=$array_data[$i]['importe'].', ';
			$sql.="'".$array_data[$i]['impuesto']."'".', ';
			$sql.=$array_data[$i]['tasa_o_cuota'].', ';
			$sql.="'".$array_data[$i]['tipo_factor']."'".', ';
			$sql.=$array_data[$i]['base'].")";
			if($i < (count($array_data)-1)) {
				$sql.= ',';
			}  
		} 

		$this->ExecuteQuery($sql);
		$arrayTotal = array();
		$arrayTotal['fk_comprobante'] = $array_data[0]['fk_comprobante'];
		$arrayTotal['impuestos_trasladados'] = $total;
		$arrayTotal['impuestos_trasladados_tasa0'] = $totalTasa0;
		$arrayTotal['impuestos_trasladados_tasa16'] = $totalTasa16;
		$arrayTotal['impuestos_retenidos'] = 0;
		$this->InsertIntoImpuestosTotales($arrayTotal, $lastId);
		$this->InsertIntoImpuestosGlobales($arrayTotal, $lastId);
	}

	public function InsertIntoImpuestosTotales($array_data, $lastId) {
		$sql = 'INSERT INTO '.CFDI_IMPUESTOS_TOTALES.' (
		fk_cfdi,
		fk_comprobante,
		impuestos_trasladados,
		impuestos_retenidos) 
		VALUES (';
		$sql.=$lastId.', ';
		$sql.=$array_data['fk_comprobante'].', ';
		$sql.=$array_data['impuestos_trasladados'].', ';
		$sql.=$array_data['impuestos_retenidos'].' ) ';
		$this->ExecuteQuery($sql);
	}

	public function InsertIntoImpuestosGlobales($array_data, $lastId) {
		if($array_data['impuestos_trasladados_tasa0'] > 0) {
			$sql = 'INSERT INTO '.CFDI_IMPUESTOS_GLOBALES .' (
				fk_cfdi,
				fk_comprobante,
				tipo_impuesto_federal,
				importe,
				impuesto,
				tasa_o_cuota,
				tipo_factor) 
				VALUES (';
				$sql.=$lastId.', ';
				$sql.=$array_data['fk_comprobante'].', ';
				$sql.="'T', ";
				$sql.='0,';
				$sql.="'002', ";
				$sql.="'0.000000', ";		
				$sql.="'Tasa' )";
				$this->ExecuteQuery($sql);
		}
		if($array_data['impuestos_trasladados_tasa16'] > 0) {
			$sql = 'INSERT INTO '.CFDI_IMPUESTOS_GLOBALES .' (
				fk_cfdi,
				fk_comprobante,
				tipo_impuesto_federal,
				importe,
				impuesto,
				tasa_o_cuota,
				tipo_factor) 
				VALUES (';
				$sql.=$lastId.', ';
				$sql.=$array_data['fk_comprobante'].', ';
				$sql.="'T', ";
				$sql.=$array_data['impuestos_trasladados_tasa16'].',';
				$sql.="'002', ";
				$sql.="'0.160000', ";		
				$sql.="'Tasa' )";
				$this->ExecuteQuery($sql);
		}
	}

	public function fetchPaymentData() {

	}

	public function InsertIntoCFDIComprobantePago($factureId, $array_data) {
		$lugar_de_expedicion = $this->GetVendorAddressByFactureId($factureId);
		$fk_soc = $this->GetSocIdByFactureId($array_data['facid']);
		$sql = 'INSERT INTO '.CFDI_COMPROBANTE .' (
		serie,
		folio,
		subtotal,
		moneda,
		total,
		tipo_comprobante,
		lugar_de_expedicion,
		status,
		version,
		fk_comprobante,
		fk_payment,
		fk_soc,
		uso_cfdi) 
		VALUES (';
		$sql.="'".'PA-'.$array_data['pagcid']."', ";
		$sql.="'".$array_data['pagcid']."'".", ";
		$sql.="0, ";
		$sql.="'XXX', ";
		$sql.="0, ";
		$sql.="'PP', ";
		$sql.="'".$lugar_de_expedicion."'".", ";
		$sql.="5, ";
		$sql.="'3.3', ";
		$sql.="'".$array_data['facid']."'".", ";
		$sql.="'".$array_data['pagcid']."'".", ";
		$sql.=$fk_soc.", ";
		$sql.="'P01'";
		$sql.= ')';
		$this->ExecuteQuery($sql);
	}

	public function InsertIntoCFDIRelacionados($array_data, $comprobantePagoId) {
		$sql = 'INSERT INTO '.CFDI_COMPROBANTE_RELACIONADOS .' (
		fk_cfdi,
		fk_comprobante,
		id_documento,
		id_pago,
		serie,
		folio,
		monedaDR,
		tipocambiodr,
		metodoPDR,
		numparcialidaddr,
		impSaldoAnterior,
		impPagadodr,
		impSaldoInsoluto) 
		VALUES (';
		$sql.=$comprobantePagoId.', ';
		$sql.=$array_data['facid'].', ';
		$sql.=$array_data['idDocumento'].", ";
		$sql.=$array_data['pagcid'].', ';
		$sql.="'".'PA-'.$array_data['pagcid']."', ";
		$sql.="'".$array_data['pagcid']."'".", ";
		$sql.=$array_data['monedaDR'].", ";
		$sql.=$array_data['tipocambiodr'].', ';
		$sql.=$array_data['metodoPDR'].", ";
		$sql.=$array_data['numparcialidaddr'].', ';
		$sql.=$array_data['impSaldoAnterior'].", ";
		$sql.=$array_data['impPagadodr'].", ";
		$sql.=$array_data['impSaldoInsoluto'];
		$sql.= ')';
		$this->ExecuteQuery($sql);
	}

	public function InsertIntoCFDIRelacionadosFromFacture($array_data, $comprobanteId) {
		$sql = 'INSERT INTO '.CFDI_COMPROBANTE_RELACIONADOS .' (
		fk_cfdi,
		fk_comprobante) 
		VALUES';
		$sql.='(';
		$sql.=$comprobanteId.', ';
		$sql.=$array_data[0]['fk_comprobante'].')';
		//print_r($sql);
		//die();
		$this->ExecuteQuery($sql);
	}

		public function InsertIntoConceptosPago($array_data, $comprobantePagoId) {
		$sql = 'INSERT INTO '.CFDI_CONCEPTOS .' (
		fk_cfdi,
		fk_comprobante,
		cantidad,
		descripcion,
		valor_unitario,
		importe,
		clave_prod_serv,
		clave_unidad) 
		VALUES ';
		$sql.="(".$comprobantePagoId.", ";
		$sql.=$array_data['facid'].", ";
		$sql.="1, ";
		$sql.="'Pago', ";
		$sql.="0, ";
		$sql.="0, ";
		$sql.="'84111506', ";
		$sql.="'ACT'";
		$sql.=" )";
		$this->ExecuteQuery($sql);
	}	

	public function InsertIntoCFDIComplementoPago($array_data, $comprobantePagoId) {
		$sql = 'INSERT INTO '.CFDI_COMPLEMENTO_PAGO .' (
		fk_cfdi,
		fk_comprobante,
		id_pago,
		forma_pago,
		moneda,
		tipo_cambio,
		monto,
		num_operacion,
		emisor_rfc,
		nombre_banco_ordenante,
		cuenta_ordenante,
		cuenta_beneficiario,
		tipo_cadena_pago,
		certificado_pago,
		cadena_pago,
		sello_pago,
		rfc_emisor_cuenta_beneficiario) 
		VALUES (';
		$sql.=$comprobantePagoId.', ';
		$sql.=$array_data['facid'].', ';
		$sql.=$array_data['pagcid'].', ';
		$sql.=$array_data['formpago'].", ";
		$sql.=$array_data['monedapago'].", ";
		$sql.=$array_data['tipocambio'].',';
		$sql.=$array_data['montop'].',';
		$sql.="'".$array_data['numoperacion']."', ";
		$sql.="'".$array_data['rfcemisorctaorigen']."', ";
		$sql.="'".$array_data['nombancoordenante']."', ";
		$sql.="'".$array_data['ctaordenante']."', ";
		$sql.="'".$array_data['ctabeneficiario']."', ";
		$sql.="'".$array_data['tipocadenapago']."', ";
		$sql.="'".$array_data['certificadopago']."', ";
		$sql.="'".$array_data['cadenaoriginal']."', ";
		$sql.="'".$array_data['sellopago']."', ";
		$sql.="'".$array_data['rfcemisorctabeneficiario']."'";
		$sql.=")";
		$this->ExecuteQuery($sql);
	}

	public function InsertIntoCFDIComplementoPagoFromFacture($array_data, $comprobanteId) {
		$sql = 'INSERT INTO '.CFDI_COMPLEMENTO_PAGO .' (
		fk_cfdi,
		fk_comprobante) 
		VALUES';
			$sql.='(';
			$sql.=$comprobanteId.', ';
			$sql.=$array_data[0]['fk_comprobante'].')';
			//print_r($sql);
			$this->ExecuteQuery($sql);
	}

	public function InsertIntoCFDIDocRelacionado($array_data, $lastId) {
		$sql = 'INSERT INTO '.CFDI_DOC_RELACIONADO .' (
		fk_cfdi,
		fk_comprobante,
		id_documento,
		id_pago,
		serie,
		folio,
		moneda,
		tipo_cambio,
		metodo_pago,
		num_parcialidad,
		importe_saldo_anterior,
		importe_pagado,
		importe_saldo_insoluto) 
		VALUES (';
		$sql.=$lastId.', ';
		$sql.=$array_data['fk_comprobante'].', ';
		$sql.="'".$array_data['id_documento']."', ";
		$sql.="'".$array_data['id_pago']."', ";
		$sql.="'".$array_data['serie']."', ";
		$sql.="'".$array_data['folio']."', ";
		$sql.="'".$array_data['moneda']."', ";
		$sql.=$array_data['tipo_cambio'].',';
		$sql.="'PPD, ";
		$sql.=$array_data['num_parcialidad'].',';
		$sql.=$array_data['importe_saldo_anterior'].',';
		$sql.=$array_data['importe_pagado'].',';
		$sql.=$array_data['importe_saldo_insoluto'];
		$sql.=")";
		$this->ExecuteQuery($sql);
	}

	public function InsertIntoConceptosTipoImpuestoPago($array_data, $comprobantePagoId) {
		$sql = 'INSERT INTO '.CFDI_CONCEPTOS_TIPO_IMPUESTO .' (
		fk_cfdi,
		fk_comprobante) 
		VALUES';
			$sql.='(';
			$sql.=$comprobantePagoId.', ';
			$sql.=$array_data['facid'].')';
			$this->ExecuteQuery($sql);
	}
	
	public function InsertIntoImpuestosTotalesPago($array_data, $comprobantePagoId) {
		$sql = 'INSERT INTO '.CFDI_IMPUESTOS_TOTALES.' (
		fk_cfdi,
		fk_comprobante) 
		VALUES';
			$sql.='(';
			$sql.=$comprobantePagoId.', ';
			$sql.=$array_data['facid'].')';
			$this->ExecuteQuery($sql);
	}

	public function InsertIntoImpuestosGlobalesPago($array_data, $comprobantePagoId) {
			$sql = 'INSERT INTO '.CFDI_IMPUESTOS_GLOBALES .' (
				fk_cfdi,
				fk_comprobante) 
				VALUES';
			$sql.='(';
			$sql.=$comprobantePagoId.', ';
			$sql.=$array_data['facid'].')';
			$this->ExecuteQuery($sql);
	}

	public function CheckIfExists($fk_comprobante) {
		$sql = "SELECT fk_comprobante FROM cfdi_comprobante WHERE fk_comprobante =".$fk_comprobante;
		$result = $this->ExecuteQuery($sql);
		return $result;
	}
}