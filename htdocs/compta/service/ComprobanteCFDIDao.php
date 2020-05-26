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


class ClientUtilsService {

	var $db;

	function __construct($db) {
		$this->db = $db;
	}

	private function ExecuteQuery($sql) {
		$resql = $this->db->query($sql);
		return $resql;
	}

	public function GetClientDebt($socid) {
		$sql = " SELECT llx_societe.rowid, llx_societe.nom AS nom, sum(total_ttc) AS total, fk_account AS account, llx_societe.outstanding_limit as credit_limit
		FROM llx_facture AS f
		JOIN llx_societe ON f.fk_soc = llx_societe.rowid
		JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
		WHERE f.fk_soc = ".$socid; 
		$sql.= " AND f.paye = 0
		AND f.fk_statut = 1
		AND f.entity = 1
		AND DATE(datef) < now()";

		$result = $this->ExecuteQuery($sql);
		$row =  $this->db->fetch_object($result);
		return $row;
	}
}