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

    public function GetTransporteById($transporteId) {
        $sql = "SELECT * FROM cfdi_transporte WHERE rowid = '".$ransporteId."'";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->id;
    }

    public function GetTransportesResult() {
        $sql = "SELECT * FROM cfdi_transporte";
        $result = $this->ExecuteQuery($sql);
        return $result;
    }

    public function GetTransportes() {
        $sql = "SELECT * FROM cfdi_transporte";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

}

