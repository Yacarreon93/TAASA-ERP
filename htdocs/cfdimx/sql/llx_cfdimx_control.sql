CREATE TABLE `llx_cfdmix_control` 
(  
	`control_id` int(11) NOT NULL AUTO_INCREMENT,  
	`factura_id` int(11) DEFAULT NULL,
	`tipo_timbrado` int(11) DEFAULT NULL,  
	`estatus` int(11) DEFAULT NULL,  
	`factura_serie` varchar(50) DEFAULT NULL,  
	`factura_folio` varchar(50) DEFAULT NULL,  
	`factura_seriefolio` varchar(50) DEFAULT NULL,  
	`fk_facture` varchar(50) DEFAULT NULL,  
	`fecha_emision` date DEFAULT NULL,  
	`hora_emision` time DEFAULT NULL,  
	`entity_id` int(11) DEFAULT NULL,  
	PRIMARY KEY (`control_id`)
);