<?php

class TransporteDAO {

    var $db;

    function __construct($db) {
        $this->db = $db;
    }

    private function ExecuteQuery($sql) {
        $resql = $this->db->query($sql);
        return $resql;
    }

    public function GetLastInsertedId() {
        $sql = "SELECT rowid FROM cfdi_transporte ORDER BY rowid DESC LIMIT 1";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->rowid;
    }

    public function GetTransporteById($transporteId) {
        $sql = "SELECT * FROM cfdi_transporte WHERE rowid = '".$transporteId."'";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

    public function GetTransportesResult() {
        $sql = "SELECT * FROM cfdi_transporte";
        $result = $this->ExecuteQuery($sql);
        return $result;
    }

    public function GetTransportes() {
        $sql = "SELECT * FROM cfdi_transporte";
        $result = $this->ExecuteQuery($sql);
        while ($row =  $this->db->fetch_object($result))
		{
				$data[] = array(
                    rowid=>$row->rowid,
					nombre=>$row->nombre,
					config_vehicular=>$row->config_vehicular,
					placas=> $row->placas,
					anio=>$row->anio,
					aseguradora=>$row->aseguradora,
					poliza=>$row->poliza
			);
		}
		return $data;
    }

    public function InsertTransporte($object) {
        $sql = "INSERT INTO cfdi_transporte (nombre, config_vehicular, placas, anio, aseguradora, poliza) 
        VALUES ('".$object["nombre"]."', '".$object["config_vehicular"]."', '".$object["placas"]."', '".$object["anio"]."', '".$object["aseguradora"]."', '".$object["poliza"]."')";
        $result = $this->db->query($sql);
        if($result) {
            return $this->GetLastInsertedId();
        }
        
    }

    public function UpdateTransporte($id, $object) {
        $sql = "UPDATE cfdi_transporte SET nombre = '".$object->nombre."', config_vehicular ='".$object->config_vehicular."', placas= '".$object->placas."', anio ='".$object->anio."', aseguradora ='".$object->aseguradora."', poliza ='".$object->poliza."'
        WHERE rowid = ".$id;
        $result = $this->db->query($sql);
        return $result;
        
    }

}

