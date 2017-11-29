CREATE TABLE `llx_cfdimx_receptor_datacomp` 
(
	`receptor_rfc` varchar (50) NOT NULL, 
	`receptor_delompio` varchar (250), 
	`receptor_colonia` varchar (250),
	`receptor_calle` varchar (250), 
	`receptor_noext` varchar (200), 
	`receptor_noint` varchar (200)
);
ALTER TABLE `llx_cfdimx_receptor_datacomp` DROP PRIMARY KEY;
ALTER TABLE `llx_cfdimx_receptor_datacomp` ADD COLUMN `receptor_id` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT;
ALTER TABLE `llx_cfdimx_receptor_datacomp` ADD COLUMN `entity_id` INTEGER;
