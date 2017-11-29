INSERT IGNORE INTO `llx_cfdimx_domicilios_receptor` (`receptor_rfc`, `tpdomicilio`, `receptor_delompio`, `receptor_colonia`, 
`receptor_calle`, `receptor_noext`, `receptor_noint`, `receptor_id`, `entity_id`,`determinado`) SELECT `receptor_rfc`,'Domicilio Fiscal',receptor_delompio, receptor_colonia, receptor_calle, receptor_noext, 
	`receptor_noint`,`receptor_id`, `entity_id`,'1' FROM `llx_cfdimx_receptor_datacomp`
 WHERE `receptor_rfc` NOT IN (SELECT `receptor_rfc` FROM `llx_cfdimx_domicilios_receptor`);


INSERT IGNORE INTO `llx_cfdimx_catalog_retenciones` (`impuesto`) VALUES('IVA');

INSERT IGNORE INTO `llx_cfdimx_catalog_retenciones` (`impuesto`) VALUES('ISR');
