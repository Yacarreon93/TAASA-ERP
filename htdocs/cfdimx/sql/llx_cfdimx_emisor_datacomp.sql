CREATE TABLE IF NOT EXISTS `llx_cfdimx_emisor_datacomp` (
  `emisor_rfc` varchar(50) DEFAULT NULL,
  `emisor_delompio` varchar(250) DEFAULT NULL,
  `emisor_colonia` varchar(250) DEFAULT NULL,
  `emisor_calle` varchar(250) DEFAULT NULL,
  `emisor_noext` varchar(200) DEFAULT NULL,
  `emisor_noint` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`emisor_rfc`)
);
ALTER TABLE `llx_cfdimx_emisor_datacomp` DROP PRIMARY KEY;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `emisor_id` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `entity_id` INTEGER;
ALTER TABLE `llx_cfdimx_emisor_datacomp` ADD COLUMN `cod_municipio` varchar(50) DEFAULT NULL;
