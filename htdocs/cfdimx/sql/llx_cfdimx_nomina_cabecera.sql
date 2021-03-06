CREATE TABLE `llx_cfdimx_nomina_cabecera` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `fk_facnomina` int(11) DEFAULT NULL,
  `RegistroPatronal` varchar(255) DEFAULT NULL,
  `NumEmpleado` varchar(255) DEFAULT NULL,
  `CURP` varchar(255) DEFAULT NULL,
  `TipoRegimen` varchar(255) DEFAULT NULL,
  `NumSeguridadSocial` varchar(255) DEFAULT NULL,
  `FechaPago` date DEFAULT NULL,
  `FechaInicialPago` date DEFAULT NULL,
  `FechaFinalPago` date DEFAULT NULL,
  `NumDiasPagados` varchar(255) DEFAULT NULL,
  `Departamento` varchar(255) DEFAULT NULL,
  `FechaInicioRelLaboral` date DEFAULT NULL,
  `Antiguedad` varchar(255) DEFAULT NULL,
  `Puesto` varchar(255) DEFAULT NULL,
  `TipoContrato` varchar(255) DEFAULT NULL,
  `TipoJornada` varchar(255) DEFAULT NULL,
  `PeriodicidadPago` varchar(255) DEFAULT NULL,
  `SalarioBaseCotApor` double DEFAULT NULL,
  `RiesgoPuesto` varchar(255) DEFAULT NULL,
  `SalarioDiarioIntegrado` double DEFAULT NULL,
  `LugarExpedicion` varchar(255) DEFAULT NULL,
  `formaDePago` varchar(255) DEFAULT NULL,
  `subTotal` double DEFAULT NULL,
  `total` double DEFAULT NULL,
  `metodoDePago` varchar(255) DEFAULT NULL,
  `tipoDeComprobante` varchar(255) DEFAULT NULL,
  `rrfc` varchar(255) DEFAULT NULL,
  `rnombre` varchar(255) DEFAULT NULL,
  `rpais` varchar(255) DEFAULT NULL,
  `rcalle` varchar(255) DEFAULT NULL,
  `rnoExterior` varchar(255) DEFAULT NULL,
  `rnoInterior` varchar(255) DEFAULT NULL,
  `rcolonia` varchar(255) DEFAULT NULL,
  `rmunicipio` varchar(255) DEFAULT NULL,
  `rcodigoPostal` varchar(255) DEFAULT NULL,
  `ridEmpleado` varchar(255) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `restado` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`rowid`)
);