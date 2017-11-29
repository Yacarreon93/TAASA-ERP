<?php
class Actionscfdimx
{ 
	
	
	function addMoreActionsButtons( $parameters, &$object, &$action, $hookmanager ){
		 if (in_array('invoicecard', explode(':', $parameters['context']))) {
		 	global $db;
		 	if($object->statut==0){
				//print $object->id;
			}else{
				$url = DOL_URL_ROOT.'/cfdimx/facture.php?facid='.$object->id;
		 		$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture = ". $object->id;
		 		$resql=$db->query($sql);
		 		if ($resql){
		 			$num = $db->num_rows($resql);
		 			$i = 0;
		 			if ($num){
		 				while ($i < $num){
		 					$obj = $db->fetch_object($resql);
		 					if ($obj){
		 						//$aux= '<a href="'.DOL_URL_ROOT.'/cfdimx/facture.php?facid='.$object->id.'">UUID: '. $obj->uuid .'</a>';
		 					}
		 					$i++;
		 				}
		 			}else{
		 				if( $object->statut==1 || $object->statut==2 ){
		 					$sql = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE rowid = " . $object->id . " AND datef >  NOW() - INTERVAL 72 HOUR";
		 					$resql=$db->query($sql);
		 					if ($resql){
		 						$num = $db->num_rows($resql);
		 						$i = 0;
		 						if ($num){ $aux= '<a href="'.$url.'" class="butAction" >Generar CFDI</a>'; 
		 						print "".$aux."";
		 						}else{ $aux= "Fuera de fecha de timbrado"; }
		 					}
		 				}else{
		 					//$aux= "N/A";
		 				}
		 			}
		 		}else{
		 			//$aux= "N/A";
		 		}
		 		//print "<button class='butAction'>".$aux."</button>";
		 		/***/
			}
		}
	}
	function formObjectOptions( $parameters, &$object, &$action, $hookmanager ){
		if (in_array('invoicecard', explode(':', $parameters['context']))) {
	$posicion = strpos($_SERVER["PHP_SELF"], 'cfdimx');
	if($posicion==false){
		global $db, $conf;
		if($object->statut==0){
			//print $object->id;
		}else{
			$url = DOL_URL_ROOT.'/cfdimx/facture.php?facid='.$object->id;
			$sql = "SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx WHERE fk_facture = ". $object->id;
			$resql=$db->query($sql);
			if ($resql){
				$num = $db->num_rows($resql);
				$i = 0;
				if ($num){
					while ($i < $num){
						$obj = $db->fetch_object($resql);
						if ($obj){
							//$aux= '<a href="'.DOL_URL_ROOT.'/cfdimx/facture.php?
							//facid='.$object->id.'">UUID: '. $obj->uuid .'</a>';
							print "<tr class='liste_titre'><td colspan='2'>Informaci&oacute;n Fiscal</td></tr>";
							$sql="SELECT IFNULL(tipo_document,NULL) as tipo_document
								FROM ".MAIN_DB_PREFIX."cfdimx_type_document
								WHERE fk_facture=".$object->id;
							$resp=$db->query($sql);
							$respp=$db->fetch_object($resp);
							print "<tr><td>Tipo de documento</td><td>";
							if($respp->tipo_document!=NULL){
								if($respp->tipo_document==1){print "Factura Estandar";}
								if($respp->tipo_document==2){print "Recibo de Honorarios";}
								if($respp->tipo_document==3){print "Recibo de Arrendamiento";}
								if($respp->tipo_document==4){print "Nota de Credito";}
								if($respp->tipo_document==5){print "Factura de Fletes";}
							}else{
								$sql="SELECT type FROM ".MAIN_DB_PREFIX."facture WHERE rowid=".$object->id;
								$resp=$db->query($sql);
								$respp=$db->fetch_object($resp);
								if($respp->type==2){print "Nota de Credito";
								}else{ print "Factura Estandar";}
							}
							print "</td></tr>";
							print "<tr><td>UUID</td><td><a href='".DOL_URL_ROOT.'/cfdimx/facture.php?facid='.$object->id."'>".$obj->uuid ."</a></td></tr>";
							print "<tr><td>Fecha y Hora de Timbrado</td><td>";
							print $obj->fecha_timbrado." ".$obj->hora_timbrado."</td></tr>";
							$sql="SELECT round(total,2) as total ,round(tva,2) as tva,round(total_ttc,2) as total_ttc
									FROM ".MAIN_DB_PREFIX."facture
									WHERE rowid=".$object->id;
							if($conf->global->MAIN_MODULE_MULTICURRENCY){
								$sql="SELECT round(multicurrency_total_ht,2) as total ,round(multicurrency_total_tva,2) as tva,round(multicurrency_total_ttc,2) as total_ttc
									FROM ".MAIN_DB_PREFIX."facture
									WHERE rowid=".$object->id;
							}
							//print $sql;
							$rt=$db->query($sql);
							$rtt=$db->fetch_object($rt);
							print "<tr>
		            			<td>Subtotal</td>
		            			<td align='right'>".number_format($rtt->total,2)."</td>
		            		   </tr>";
							print "<tr>
		            			<td>IVA</td>
		            			<td align='right'>".number_format($rtt->tva,2)."</td>
		            		   </tr>";
// 							/**ISH**/
							$sql="SHOW COLUMNS FROM ".MAIN_DB_PREFIX."product_extrafields LIKE 'prodcfish'";
							$resql=$db->query($sql);
							$existe_ish = $db->num_rows($resql);
							if( $existe_ish > 0 ){
								$sql="SELECT count(*) as exist,importe  FROM ".MAIN_DB_PREFIX."cfdimx_facturedet WHERE fk_facture=".$object->id." AND impuesto='ISH'";
								$ass=$db->query($sql);
								$asd=$db->fetch_object($ass);
								if($asd->exist>0){
									print "<tr>
	 		            			        <td>ISH</td>
	 		            			        <td align='right' >".number_format($asd->importe,2)."</td>
	 		            		          </tr>";
								}
							} 
// 							/**ISH**/
							$total_res=$rtt->total_ttc;
							if(1/* $respp->tipo_document==2 || $respp->tipo_document==3 */){
								$sql="SELECT impuesto,importe
								FROM ".MAIN_DB_PREFIX."cfdimx_retenciones
								WHERE fk_facture=".$object->id;
								//print $sql;
										$rsq=$db->query($sql);
										$restar=0;
										while($rsqq=$db->fetch_object($rsq)){
											$restar=$restar+$rsqq->importe;
														print "<tr>
					            			<td>Retencion de ".$rsqq->impuesto."</td>
					            			<td align='right'>".number_format($rsqq->importe,2)."</td>
					            		   </tr>";
								}
								$total_res=$rtt->total_ttc-$restar;
							}
							//Retenciones locales
							$sqm="SELECT COUNT(*) AS count FROM information_schema.tables
							 WHERE table_schema = '".$db->database_name."'
					 			 AND table_name = '".MAIN_DB_PREFIX."cfdimx_config_retenciones_locales'";
							$rqm=$db->query($sqm);
							$rqsm=$db->fetch_object($rqm);
							$total_retlocal=0;
							if($rqsm>0){
								$resqm=$db->query("SELECT * FROM  ".MAIN_DB_PREFIX."cfdimx_retenciones_locales WHERE fk_facture = " . $object->id);
								if ($resqm){
									$cfdi_m = $db->num_rows($resqm);
									$m = 0;
									if ($cfdi_m>0){
										while ($m < $cfdi_m){
											$obm = $db->fetch_object($resqm);
											print "<tr>
						            			<td>Ret. ".$obm->codigo."</td>
						            			<td align='right'>".number_format($obm->importe,2)."</td>
						            		   </tr>"; 
											$m++;
										}
									}
								}
							}
							$arc=$total_res;
							print "<tr>
		            			<td>Total</td>
		            			<td align='right'>".number_format(round($arc,2),2)."</td>
		            		   </tr>";
							
						}
						$i++;
					}
				}else{
					if( $object->statut==1 || $object->statut==2 ){
						$sql = "SELECT * FROM ".MAIN_DB_PREFIX."facture WHERE rowid = " . $object->id . " AND datef >  NOW() - INTERVAL 72 HOUR";
						$resql=$db->query($sql);
						if ($resql){
							$num = $db->num_rows($resql);
							$i = 0;
							/* if ($num){ $aux= '<a href="'.$url.'">Generar CFDI</a>';
							print "<button class='butAction'>".$aux."</button>";
							}else{ $aux= "Fuera de fecha de timbrado"; } */
						}
					}else{
						//$aux= "N/A";
					}
				}
			}else{
				//$aux= "N/A";
			}
			//print "<button class='butAction'>".$aux."</button>";
			/***/
		}
		}
		}
	}
	
	
	
}
?>