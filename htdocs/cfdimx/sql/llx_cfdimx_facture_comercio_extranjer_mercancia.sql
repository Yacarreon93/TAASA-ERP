CREATE TABLE `llx_cfdimx_facture_comercio_extranjero_mercancia` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facture` int(11) NOT NULL DEFAULT '0',
  `fk_facturedet` int(11) NOT NULL DEFAULT '0',
  `preciousd` double,
  `noidentificacion` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);
