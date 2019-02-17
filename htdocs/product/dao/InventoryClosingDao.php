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

define("INVENTORY_CLOSING_TABLE", "llx_inventory_closing_temp");
define("INVENTORY_CLOSING_FINAL_TABLE", "llx_inventory_closing");

class InventoryClosingDao {

	var $db;

	function __construct($db) {
		$this->db = $db;
	}

	private function ExecuteQuery($sql) {
		$resql = $this->db->query($sql);
		return $resql;
	}

	public function CreateTable() {
		$sql = 'CREATE TABLE IF NOT EXISTS'.INVENTORY_CLOSING_TABLE .'(
	  rowid int(11) NOT NULL AUTO_INCREMENT,
	  tms timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	  fk_product int(11) NOT NULL,
	  reel double DEFAULT NULL,
	  fk_entrepot int(11) DEFAULT NULL,
	  PRIMARY KEY (rowid))';
	  $this->ExecuteQuery($sql);
	}

	public function GetCount() {
		$sql = 'SELECT
		count(*)
		FROM'.INVENTORY_CLOSING_TABLE;
		$this->ExecuteQuery($sql);
	}

	public function Insert($array_data, $fk_entrepot) {
		$sql = 'INSERT INTO '.INVENTORY_CLOSING_TABLE .' (
		fk_product,
		reel,
		fk_entrepot)
		VALUES';
		$i = 0;
		foreach($array_data as $key => $value) {
			$i++;
			$sql.='(';
			$sql.=$key.', ';
			$sql.=$value.', ';
			$sql.=$fk_entrepot.')';
			if($i < count($array_data)) {
				$sql.= ',';
			}  
		}
		$this->ExecuteQuery($sql);
	}

	public function Delete($array_data) {
		$sql = 'DELETE FROM '.INVENTORY_CLOSING_TABLE .'
		WHERE fk_product in ';
		$i = 0;
	    $sql.='(';
		foreach($array_data as $data) {
			$i++;
			$sql.=$data;
			if($i < count($array_data)) {
				$sql.= ',';
			}  
		}
		$sql.=')';
		$this->ExecuteQuery($sql);
	}

	public function ClearTable() {
		$sql = 'DELETE FROM '.INVENTORY_CLOSING_TABLE;
		$this->ExecuteQuery($sql);
	}

	public function GetSavedIds() {
		$sql = 'SELECT DISTINCT p.rowid
		FROM llx_product AS p
		JOIN llx_product_stock AS ps ON ps.fk_product = p.rowid
 		JOIN llx_inventory_closing_temp ON llx_inventory_closing_temp.fk_product = p.rowid
		WHERE
		ps.fk_entrepot=1';
		 $result = $this->ExecuteQuery($sql);
		 return $result;
	}

	function CopyTableContent() {
		$sql = 'INSERT INTO '.INVENTORY_CLOSING_FINAL_TABLE .' (
		fk_product, reel, fk_entrepot)
		SELECT
		fk_product, reel, fk_entrepot
		FROM '.INVENTORY_CLOSING_TABLE;
		$this->ExecuteQuery($sql);
	}
}