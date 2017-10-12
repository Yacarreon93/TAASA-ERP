<?php
class ws_formselect {
	var $db;

	var $main_db_prefix;
	var $emisorrfc;
	var $modo;
	var $wsprod;
	var $wspruebas;
	var $wsstatusconf;
	var $entity;
	var $wsvalue;

	public function __construct($db){
		$this->db = $db;
	}
	function fetch() {
		unset($sql);
		$sql  = ' SELECT * ';
		$sql .= ' FROM '.$this->main_db_prefix.'cfdimx_config_ws ';
		$sql .= ' WHERE entity_id ='.$this->entity;
		$res = $this->db->query($sql);
		if($this->db->num_rows($res)>0){
			$obj = $this->db->fetch_object($res);

			$this->emisorrfc = $obj->emisor_rfc;
			$this->modo = $obj->ws_modo_timbrado;
			$this->wsprod = $obj->ws_produccion;
			$this->wspruebas = $obj->ws_pruebas;
			return 1;
		}else{
			return 0;
		}
	}
	function insert() {

		$this->emisorrfc = isset($this->emisorrfc)?$this->emisorrfc:null;
		$this->modo = isset($this->modo)?$this->modo:null;
		$this->wspruebas = isset($this->wspruebas)?$this->wspruebas:null;
		$this->wsprod = isset($this->wsprod)?$this->wsprod:null;
		$this->wsstatusconf = isset($this->wsstatusconf)?$this->wsstatusconf:null;
		$this->entity = isset($this->entity)?$this->entity:null;

		unset($sql);
		$sql  = 'INSERT INTO ';
		$sql .= $this->main_db_prefix.'cfdimx_config_ws ';
		$sql .= ' (emisor_rfc, ws_modo_timbrado, ws_pruebas ';
		$sql .= '  ,ws_produccion, ws_status_conf, entity_id)';
		$sql .= ' VALUES("'.$this->emisorrfc.'", "'.$this->modo.'", "'.$this->wspruebas.'"';
		$sql .= ' ,"'.$this->wsprod.'", "'.$this->wsstatusconf.'", "'.$this->entity.'" )';
		$res = $this->db->query($sql);

		if($res){
			return 1;
		}else{
			print $sql;
			return 0;
		}
	}
	function delete() {

	}
	function update() {
		$this->emisorrfc = isset($this->emisorrfc)?$this->emisorrfc:null;
		$modo = isset($this->modo)?$this->modo:null;
		$wspruebas = isset($this->wspruebas)?$this->wspruebas:null;
		$wsprod = isset($this->wsprod)?$this->wsprod:null;
		$wsstatusconf = isset($this->wsstatusconf)?$this->wsstatusconf:null;
		$entity = isset($this->entity)?$this->entity:null;

		$res = $this->fetch();
		if($res){
			//if 1 update
			unset($sql);
			$sql  = ' UPDATE '.$this->main_db_prefix.'cfdimx_config_ws SET';
			$sql .= ' ws_modo_timbrado = "'.$modo.'"';
			$sql .= ' ,ws_pruebas = "'.$wspruebas.'"';
			$sql .= ' ,ws_produccion = "'.$wsprod.'"';
			$sql .= ' ,ws_status_conf = "'.$wsstatusconf.'"';
			$sql .= ' WHERE entity_id = "'.$entity.'"';
			$res = $this->db->query($sql);
			if($res){
				return 1;
			}else{
				print $sql;
				return 0;
			}
		}else{
			//if 0 insert
			$res = $this->insert();
			if ($res) {
				return 1;
			}else{
				return 0;
			}
		}
	}
	function fetch_const() {

		$sql  = ' SELECT * ';
		$sql .= ' FROM '.$this->main_db_prefix.'const' ;
		$sql .= ' WHERE entity = '.$this->entity;
		$sql .= ' AND name = "MAIN_MODULE_CFDIMX_WS"';
		$res = $this->db->query($sql);
		$num = $this->db->num_rows($res);
		if ($num){
			return 1;
		}else{
			return 0;
		}
	}
	function update_const() {
		//print 'Busca const<br>';

		$res = $this->fetch_const();
		if ($this->modo == 1) {
			 $this->wsvalue = $this->wsprod;
		}elseif($this->modo == 2){
			 $this->wsvalue = $this->wspruebas;
		}
		if($res){
			//update
			//print 'Actualiza const<br>';
			$sql  = ' UPDATE '.$this->main_db_prefix.'const SET ';
			$sql .= ' value = "'. $this->wsvalue.'"';
			$sql .= ' WHERE name = "MAIN_MODULE_CFDIMX_WS"';
			$sql .= ' AND entity = '. $this->entity;
			$res = $this->db->query($sql);
			if($res){
				return $res;
			}else{
				return $res;
			}
		}else{
			//insert
			//print 'Inserta const<br>';
			$res = $this->insert_const();
			if($res){
				return $res;
			}else{
				return $res;
			}
		}
	}
	function insert_const() {
		$sql  = 'INSERT INTO '.$this->main_db_prefix.'const ';
		$sql .= ' (name, entity, value';
		$sql .= '  , type, visible, note)';
		$sql .= ' VALUES("MAIN_MODULE_CFDIMX_WS", "'.$this->entity.'", "'.$this->wsvalue.'"';
		$sql .= ' , "chaine", "1", "ws cfdimx")';

		$res = $this->db->query($sql);
		if($res){
			return $res;
		}else{
			return $res;
		}
	}
}

?>