CREATE TABLE `llx_cfdimx_emisores_datacom` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `rfc` text NOT NULL,
  `regimen` text,
  `razon_social` text,
  `pais` text,
  `estado` text,
  `codigo_postal` text,
  `delompio` varchar(250) DEFAULT NULL,
  `colonia` varchar(250) DEFAULT NULL,
  `calle` varchar(250) DEFAULT NULL,
  `noext` varchar(200) DEFAULT NULL,
  `noint` varchar(200) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `password_timbrado` varchar(100) DEFAULT NULL,
  `password_timbrado_txt` varchar(100) DEFAULT NULL,
  `formato_cfdi` varchar(50) DEFAULT NULL,
  `modo_timbrado` varchar(50) DEFAULT NULL,
  `config_seriefolio` varchar(50) DEFAULT NULL,
  `status_conf` varchar(2) DEFAULT NULL,
  `predeterminado` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rowid`)
);
