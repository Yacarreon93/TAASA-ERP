<?php
    // Change this following line to use the correct relative path (../, ../../, etc)
    $res=0;
    if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';                  // to work if your module directory is into dolibarr root htdocs directory
    if (! $res && file_exists("../../../../main.inc.php")) $res=@include '../../../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
    if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
    if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
    if (! $res) die("Include of main fails");
    // Change this following line to use the correct relative path from htdocs
    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
    require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

    $action     = GETPOST('action','alpha');
    $reyear     = GETPOST('reyear','alpha');
    $remonth    = GETPOST('remonth','alpha');
    $fromDate   = GETPOST('fromDate','alpha');
    $toDate     = GETPOST('toDate','alpha');

    require_once '../../Class/PHPExcel.php';
    $objPHPExcel = new PHPExcel();

    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'AGENTE');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B1', 'NOMBRE');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', 'VENTAS');  
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D1', 'COBRADO');  
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E1', 'SALDO');  
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F1', 'VENCIDO');  

    $sql_vendors =  " SELECT * FROM ".MAIN_DB_PREFIX."user u ";
    $sql_vendors .= " JOIN ".MAIN_DB_PREFIX."user_extrafields ue ON ue.fk_object = u.rowid ";
    $sql_vendors .= " WHERE ue.rol = 1";

    $row_index = 2;

    $resql_vendors = $db->query($sql_vendors);
    if($sql_vendors) {

        while ($vendor = $db->fetch_object($resql_vendors)) {

            if($vendor->rowid > 0) {

                $sales = 0;
                $amount = 0;
                $debit = 0;

                $sql_fac =  " SELECT * FROM ".MAIN_DB_PREFIX."facture f";
                $sql_fac .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = f.rowid ";
                $sql_fac .= " WHERE fe.vendor = ".$vendor->rowid;

                $resql_fac = $db->query($sql_fac);
                if($sql_fac) {
                    while($invoice = $db->fetch_object($resql_fac)) {

                        $sales += $invoice->total_ttc;

                        $sql =  " SELECT * FROM ".MAIN_DB_PREFIX."paiement p ";
                        $sql .= " JOIN ".MAIN_DB_PREFIX."paiement_facture pf ON pf.fk_paiement = p.rowid ";
                        $sql .= " WHERE pf.fk_facture = ".$invoice->rowid;
                        if ($fromDate && $toDate) {
                            $sql.= " AND p.datep BETWEEN '".$fromDate."' AND '".$toDate."'";
                        }

                        $resql = $db->query($sql);
                        if($resql) {                                    
                            while($payment = $db->fetch_object($sql)) {
                                $amount += $payment->amount;
                            }
                        }
                    }
                }   

                $debit = $sales - $amount;

                $user = new User($db);
                $user->fetch($vendor->rowid);

                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$row_index, $user->id);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$row_index, $user->firstname + $user->lastname);  
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$row_index, $sales);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$row_index, $amount);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$row_index, $debit);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$row_index, $unknown);                

                $row_index++;

            }                   
        }
    }
    
    $date = getdate();
    // $filename = 'inv_'.$date['mday'].'-'.$date['mon'].'_'.$date['hours'].'-'.$date['minutes'];
    $filename = 'agent_report';
    header("Content-Type:           application/vnd.ms-excel;   charset=utf-8");
    header("Content-Disposition:    attachment;                 filename=$filename.xls");
    header('Cache-Control:          max-age=0');      
    $objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
    $objWriter->save('php://output');
    exit;
?>