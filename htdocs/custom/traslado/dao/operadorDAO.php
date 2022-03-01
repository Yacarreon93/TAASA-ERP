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

    public function GetOperadorById($operadorId) {
        $sql = "SELECT * FROM cfdi_operador WHERE rowid = '".$operadorId."'";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->id;
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

}

