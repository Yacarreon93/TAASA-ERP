<?php

class OperadorDAO {

    var $db;

    function __construct($db) {
        $this->db = $db;
    }

    private function ExecuteQuery($sql) {
        $resql = $this->db->query($sql);
        return $resql;
    }

    public function GetLastInsertedId() {
        $sql = "SELECT rowid FROM cfdi_operador ORDER BY rowid DESC LIMIT 1";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->rowid;
    }

    public function GetOperadorById($operadorId) {
        $sql = "SELECT * FROM cfdi_operador WHERE rowid = '".$operadorId."'";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

    public function GetOperadoresResult() {
        $sql = "SELECT * FROM cfdi_operador";
        $result = $this->ExecuteQuery($sql);
        return $result;
    }

    public function GetOperadores() {
        $sql = "SELECT * FROM cfdi_operador";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

    public function InsertOperador($object) {
        $sql = "INSERT INTO cfdi_operador (nombre, rfc, num_licencia) VALUES ('".$object["nombre"]."', '".$object["rfc"]."', '".$object["num_licencia"]."')";
        $result = $this->db->query($sql);
        if($result) {
            return $this->GetLastInsertedId();
        }
        
    }

    public function UpdateOperador($id, $object) {
        $sql = "UPDATE cfdi_operador SET nombre = '".$object->nombre."', RFC ='".$object->RFC."', num_licencia= '".$object->num_licencia."'
        WHERE rowid = ".$id;
        $result = $this->db->query($sql);
        return $result;
        
    }

}