<?php

class CartaDAO {

    var $db;

    function __construct($db) {
        $this->db = $db;
    }

    private function ExecuteQuery($sql) {
        $resql = $this->db->query($sql);
        return $resql;
    }

    public function GetLastInsertedId() {
        $sql = "SELECT rowid FROM cfdi_traslado ORDER BY rowid DESC LIMIT 1";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row->rowid;
    }

    public function GetTrasladoById($trasladoId) {
        $sql = "SELECT * FROM cfdi_traslado WHERE rowid = '".$trasladoId."'";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

    public function GetTrasladosResult() {
        $sql = "SELECT * FROM cfdi_traslado";
        $result = $this->ExecuteQuery($sql);
        return $result;
    }

    public function GetTraslados() {
        $sql = "SELECT * FROM cfdi_traslado  WHERE IS NOT NULL(fk_facture)";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

    public function InsertTraslado($object) {
        $sql = "INSERT INTO cfdi_traslado (fk_facture, fk_ubicacion_origen, fk_cliente, fecha_salida, fecha_llegada, distancia_recorrida, fk_transporte, fk_operador) 
        VALUES ('".$object["fk_facture"]."', '".$object["fk_ubicacion_origen"]."', '".$object["fk_cliente"]."', '".$object["fecha_salida"]."', '".$object["fecha_llegada"]."', '".$object["distancia_recorrida"]."', '".$object["fk_transporte"]."', '".$object["fk_operador"]."')";
        $result = $this->db->query($sql);
        if($result) {
            return $this->GetLastInsertedId();
        }
        
    }

    public function UpdateTraslado($id, $object) {
        $sql = "UPDATE cfdi_traslado SET fk_ubicacion_origen ='".$object->fk_ubicacion_origen."', fecha_salida= '".$object->fecha_salida."', fecha_llegada= '".$object->fecha_llegada."', distancia_recorrida= '".$object->distancia_recorrida."', fk_transporte= '".$object->fk_transporte."', fk_operador= '".$object->fk_operador."'
        WHERE rowid = ".$id;
        $result = $this->db->query($sql);
        return $result;
        
    }

    public function GetFactureById($operadorId) {
        $sql = "SELECT * FROM cfdi_traslado WHERE rowid = '".$operadorId."' AND IS NOT NULL(fk_facture)";
        $result = $this->ExecuteQuery($sql);
        $row =  $this->db->fetch_object($result);
        return $row;
    }

}