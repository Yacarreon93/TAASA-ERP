<?php

class OrigenDAO {

    var $db;

    function __construct($db) {
        $this->db = $db;
    }

    private function ExecuteQuery($sql) {
        $resql = $this->db->query($sql);
        return $resql;
    }

    public function GetLastInsertedId() {
        $sql = "SELECT rowid FROM cfdi_ubicaciones ORDER BY rowid DESC LIMIT 1";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->rowid;
    }

    public function GetOrigenById($OrigenId) {
        $sql = "SELECT * FROM cfdi_ubicaciones WHERE rowid = '".$OrigenId."'";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

    public function GetOrigenesResult() {
        $sql = "SELECT * FROM cfdi_ubicaciones";
        $result = $this->ExecuteQuery($sql);
        return $result;
    }

    public function GetOrigenes() {
        $sql = "SELECT * FROM cfdi_ubicaciones";
        $result = $this->ExecuteQuery($sql);
        while ($row =  $this->db->fetch_object($result))
		{
				$data[] = array(
                    rowid=>$row->rowid,
					id_ubicacion=>$row->id_ubicacion,
					RFC=>$row->RFC,
					alias=> $row->alias
			);
		}
		return $data;
    }

    public function InsertOrigen($object) {
        $sql = "INSERT INTO cfdi_ubicaciones (nombre, rfc, num_licencia) VALUES ('".$object["nombre"]."', '".$object["rfc"]."', '".$object["num_licencia"]."')";
        $result = $this->db->query($sql);
        if($result) {
            return $this->GetLastInsertedId();
        }
        
    }

    public function UpdateOrigen($id, $object) {
        $sql = "UPDATE cfdi_ubicaciones SET nombre = '".$object->nombre."', RFC ='".$object->RFC."', num_licencia= '".$object->num_licencia."'
        WHERE rowid = ".$id;
        $result = $this->db->query($sql);
        return $result;
        
    }

}