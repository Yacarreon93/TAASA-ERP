<?php

class TrasladoDAO {

    var $db;    

    function __construct($db) {
        $this->db = $db;
    }

    private function ExecuteQuery($sql) {
        $resql = $this->db->query($sql);
        return $resql;
    }

    public function GetTrasladoById($trasladoId) {
        $sql = "SELECT * FROM cfdi_traslado WHERE rowid = '".$trasladoId."'";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->id;
    }

    public function GetTrasladosResult() {
        $sql = "SELECT * FROM cfdi_traslado";
        $result = $this->ExecuteQuery($sql);
        return $result;
    }

    public function GetTraslados() {
        $sql = "SELECT * FROM cfdi_traslado";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }
}
