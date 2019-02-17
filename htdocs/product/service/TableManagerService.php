<?php

require_once DOL_DOCUMENT_ROOT.'/product/dao/InventoryClosingDao.php';


class TableManagerService {

	public function ClearTable($db) {
		$inventoryDao = new InventoryClosingDao($db);
		$inventoryDao->ClearTable();
	}

	public function CreateTemporaryTable($db) {
		$inventoryDao = new InventoryClosingDao($db);
		$inventoryDao->CreateTable();
	}

	public function SaveInventoryClosing($db) {
		$inventoryDao = new InventoryClosingDao($db);
		$inventoryDao->CopyTableContent();
	}

	/*Elimina los valores no enviados del arreglo*/
	private function CleanArray($data_array) {
		foreach($data_array as $key => $value) {
			$counter = 0;
			if(!$data_array[$key]) {
				unset($data_array[$key]);
			} 
		}
		return $data_array;
	}

	/*Guarda espacios en blanco*/
	private function SaveBlankSpaces($data_array) {
		$counter = 0;
		foreach($data_array as $key => $value) {
			if(!$data_array[$key]) {
				$blankSpaceCounter[$counter++] = $key;
			} 
		}
		return $blankSpaceCounter;
	}

	public function UpdateProductColumn($product, $fk_entrepot, $db) {
		$inventoryDao = new InventoryClosingDao($db);
		$savedIds = $inventoryDao->GetSavedIds();
		$blankSpaceArray = $this->SaveBlankSpaces($product);
		$data_array = $this->CleanArray($product);
		$counter = 0;
		while ($row = $db->fetch_object($savedIds)) {
			$ids[$counter++] = $row->rowid;
		}
		if(!$savedIds->num_rows) {
			$inventoryDao->Delete($blankSpaceArray);
			$inventoryDao->Insert($data_array, $fk_entrepot);
		} else {
			$counter = 0;
			foreach($data_array as $key => $value) {
				foreach($ids as $id) {
					if($key == $id) {
						$recordsToDelete[$counter++] = $key;
					}
				}
			}
			$inventoryDao->Delete($blankSpaceArray);
			$inventoryDao->Delete($recordsToDelete);
			$inventoryDao->Insert($data_array, $fk_entrepot);
		}
	}
}