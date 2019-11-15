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



class ComprobanteCFDIDao {

	var $db;

	function __construct($db) {
		$this->db = $db;
	}

	private function ExecuteQuery($sql) {
		$resql = $this->db->query($sql);
		return $resql;
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

	public function FetchConceptosData($id) {
		$sql = "SELECT
			ref AS id_concepto,
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
			p.ref AS id_concepto,
			'T' AS tipo_impuesto_federal,
			total_tva AS importe,
			002 AS impuesto,
			f.tva_tx AS tasa_o_cuota,
			'Tasa' AS tipo_factor,
			subprice AS base
		FROM
			llx_facturedet AS f
		JOIN llx_product AS p ON fk_product = p.rowid
		WHERE
			fk_facture = ".$id;
		$result = $this->ExecuteQuery($sql);

		while ($row =  $this->db->fetch_object($result))
		{
				$tasa_o_cuota = ($tasa_o_cuota / 100);
				$data[] = array(
					id_concepto=>$row->id_concepto,
					tipo_impuesto_federal=> $row->tipo_impuesto_federal,
					importe=>$row->importe,
					impuesto=>impuesto,
					tasa_o_cuota=> $tasa_o_cuota,
					tipo_factor=>$row->tipo_factor,
					base=> $row->base,
					fk_comprobante=>$id
			);
		}
		return $data;
	}

	public function InsertIntoConceptosComprobante($array_data) {
		$sql = 'INSERT INTO '.CFDI_CONCEPTOS .' (
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
		clave_unidad,
		descuento) 
		VALUES ';
		for($i=0; $i < sizeof($array_data); $i++) {
			$sql.="( ".$array_data[$i]['fk_comprobante'].", ";
			$sql.="'".$array_data[$i]['id_concepto']."'".", ";
			$sql.=$array_data[$i]['cantidad'].", ";
			$sql.="'".$array_data[$i]['unidad']."'".", ";
			$sql.="'', ";
			$sql.="'".$array_data[$i]['descripcion']."'".", ";
			$sql.=$array_data[$i]['valor_unitario'].", ";
			$sql.=$array_data[$i]['importe'].", ";
			$sql.="'', ";
			$sql.="'".$array_data[$i]['clave_prod_serv']."'".", ";
			$sql.="'".$array_data[$i]['clave_unidad']."'".", ";
			$sql.=$array_data[$i]['descuento']." )";
			if($i < (count($array_data)-1)) {
				$sql.= ',';
			}  
		}
		$this->ExecuteQuery($sql);
	}	

	public function FetchComprobanteData($id) {
		$sql = "SELECT f.rowid, facnumber AS serie, facnumber AS folio, p.accountancy_code AS forma_pago, pt.traduccion AS condiciones_pago, total AS subtotal, 0 AS descuento, NULL AS motivo_descuento, currency AS moneda, total_ttc AS total, NULL AS total_con_letra, formpagcfdi AS metodo_pago, NULL AS num_cuenta_pago, vendor
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
				$lugar_de_expedicion = $this->GetVendorAddress($vendor);
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
					fk_comprobante=>$id
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
		descuento,
		motivo_descuento,
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
		fk_comprobante) 
		VALUES (';
		$sql.="'".$array_data[0]['serie']."'".", ";
		$sql.="'".$array_data[0]['folio']."'".", ";
		$sql.="'".$array_data[0]['forma_pago']."'".", ";
		$sql.="'".$array_data[0]['condiciones_pago']."'".", ";
		$sql.=$array_data[0]['subtotal'].", ";
		$sql.=$array_data[0]['descuento'].", ";
		$sql.="'".$array_data[0]['motivo_descuento']."'".", ";
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
		$sql.=$array_data[0]['fk_comprobante'];
		$sql.= ')';
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

		public function InsertIntoConceptosTipoImpuesto($array_data) {
		$sql = 'INSERT INTO '.CFDI_CONCEPTOS_TIPO_IMPUESTO .' (
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
		for($i=0; $i < sizeof($array_data); $i++) {
			$total+=$array_data[$i]['importe'];
			$sql.='(';
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
		$arrayTotal['impuestos_retenidos'] = 0;
		$this->InsertIntoImpuestosTotales($arrayTotal);
		$this->InsertIntoImpuestosGlobales($arrayTotal);
	}

	public function InsertIntoImpuestosTotales($array_data) {
		$sql = 'INSERT INTO '.CFDI_IMPUESTOS_TOTALES.' (
		fk_comprobante,
		impuestos_trasladados,
		impuestos_retenidos) 
		VALUES (';
		$sql.=$array_data['fk_comprobante'].', ';
		$sql.=$array_data['impuestos_trasladados'].', ';
		$sql.=$array_data['impuestos_retenidos'].' ) ';
		$this->ExecuteQuery($sql);
	}

	public function InsertIntoImpuestosGlobales($array_data) {
		$sql = 'INSERT INTO '.CFDI_IMPUESTOS_GLOBALES .' (
		fk_comprobante,
		tipo_impuesto_federal,
		importe,
		impuesto,
		tasa_o_cuota,
		tipo_factor) 
		VALUES (';
		$sql.=$array_data['fk_comprobante'].', ';
		$sql.="'T', ";
		$sql.=$array_data['impuestos_trasladados'].',';
		$sql.="'002', ";
		$sql.=0.160000.',';		
		$sql.="'Tasa' )";
		$this->ExecuteQuery($sql);
	}
}