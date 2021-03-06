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

    function monthName($month) {

        setlocale(LC_TIME, 'spanish');  
        $month_name = strftime("%B",mktime(0, 0, 0, $month, 1, 2000)); 
        $month_name = ucfirst($month_name);
        return $month_name;

    } 

    $action = GETPOST('action', 'alpha');
    $syear = GETPOST("reyear")?GETPOST("reyear"):date("Y", time());
    $cmonth = GETPOST("remonth")?GETPOST("remonth"):date("n", time());
    $fromDate = GETPOST("fromDate")?GETPOST("fromDate"):'';
    if($fromDate) {
        $fromDate = strtotime($fromDate);
        $fromDate = date("Y-m-d", $fromDate);
    }
    $toDate = GETPOST("toDate")?GETPOST("toDate"):'';
    if($toDate) {
        $toDate = strtotime($toDate);
        $toDate = date("Y-m-d", $toDate);
    }

    require_once '../../Class/PHPExcel.php';
    $objPHPExcel = new PHPExcel();

    $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:F1')->getFont()->setBold(true);
    $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:F1')->getFont()->setSize(14);
    $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:F1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:B1');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'REPORTE POR AGENTE');    
    $objPHPExcel->setActiveSheetIndex(0)->mergeCells('C1:F1');
    if($fromDate && $toDate) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', 'DEL '.$fromDate.' AL '.$toDate);
    } else {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C1', 'MES DE '.strtoupper(monthName($cmonth)).' DEL '.$syear);
    }

    $objPHPExcel->setActiveSheetIndex(0)->getStyle('A3:F3')->getFont()->setBold(true);
    $objPHPExcel->setActiveSheetIndex(0)->getStyle('A3:B3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'ID AGENTE');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'NOMBRE');
    $objPHPExcel->setActiveSheetIndex(0)->getStyle('C3:F3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'VENTAS');  
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'COBRADO');  
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', 'SALDO');  
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F3', 'VENCIDO');  

    $sql_vendors =  " SELECT * FROM ".MAIN_DB_PREFIX."user u ";
    $sql_vendors .= " JOIN ".MAIN_DB_PREFIX."user_extrafields ue ON ue.fk_object = u.rowid ";
    $sql_vendors .= " WHERE ue.rol = 1";

    $row_index = 4;

    $resql_vendors = $db->query($sql_vendors);

    if($resql_vendors) {

        $total_sales = 0;
        $total_amount = 0;
        $total_balance = 0;
        $total_due_balance = 0;
        
        while($vendor = $db->fetch_object($resql_vendors)) {

            $sales = 0;
            $amount = 0;
            $balance = 0;
            $due_balance = 0;

            // Colum 1

            $sql_fac =  " SELECT * FROM ".MAIN_DB_PREFIX."facture f";
            $sql_fac .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = f.rowid ";
            $sql_fac .= " WHERE ";
            $sql_fac .= " f.fk_statut <> 0";    // Not a draft
            $sql_fac .= " AND f.fk_statut <> 3";    // Not abandonned
            $sql_fac .= " AND fe.vendor = ".$vendor->rowid;
            if ($fromDate && $toDate) {
                $sql_fac.= " AND f.datef BETWEEN '".$fromDate."' AND '".$toDate."'";
            } else if($cmonth && $syear) {
                $sql_fac.= " AND YEAR(f.datef) = ".$syear." AND MONTH(f.datef) = ".$cmonth;
            }

            $resql_fac = $db->query($sql_fac);
            if($resql_fac) {
                while($invoice = $db->fetch_object($resql_fac)) {
                    $sales += $invoice->total_ttc;              
                }
            }

            $total_sales += $sales;

            // Column 2

            $sql_pay  = " SELECT * FROM ".MAIN_DB_PREFIX."paiement p ";
            $sql_pay .= " JOIN ".MAIN_DB_PREFIX."paiement_facture pf ON pf.fk_paiement = p.rowid ";
            $sql_pay .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = pf.fk_facture ";
            $sql_pay .= " WHERE fe.vendor = ".$vendor->rowid;
            if ($fromDate && $toDate) {
                $sql_pay.= " AND p.datep BETWEEN '".$fromDate."' AND '".$toDate."'";
            } else if($cmonth && $syear) {
                $sql_pay.= " AND YEAR(p.datep) = ".$syear." AND MONTH(p.datep) = ".$cmonth;
            }

            $resql_pay = $db->query($sql_pay);
            if($resql_pay) {                                    
                while($payment = $db->fetch_object($resql_pay)) {
                    $amount += $payment->amount;
                }
            }  

            $total_amount += $amount; 

            if(!$toDate) {
                $toDate = $syear.'-'.$cmonth.'-01';
                $toDate = date("Y-m-t", strtotime($toDate));
            }           

            // Column 3
                
            $sql_fac =  " SELECT DISTINCT f.fk_soc FROM ".MAIN_DB_PREFIX."facture f";
            $sql_fac .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = f.rowid ";
            $sql_fac .= " WHERE fe.vendor = ".$vendor->rowid;
            if ($toDate) {
                $sql_fac.= " AND f.datef <= '".$toDate."'";
            }

            $resql_fac = $db->query($sql_fac);
            if($resql_fac) {
                while($invoice = $db->fetch_object($resql_fac)) {
                    $soc = new Societe($db);
                    if ($invoice->fk_soc > 0)
                        $res = $soc->fetch($invoice->fk_soc);

                    if($res) {                                  
                        $balance += $soc->get_OutstandingBill($toDate);         
                    }
                }
            }

            $total_balance += $balance;

            // Column 4

            $sql_fac  = "SELECT f.rowid, f.total_ttc FROM ".MAIN_DB_PREFIX."facture as f";
            $sql_fac .= " JOIN ".MAIN_DB_PREFIX."facture_extrafields fe ON fe.fk_object = f.rowid ";
            $sql_fac .= " WHERE ";
            $sql_fac .= " f.paye = 0";
            $sql_fac .= " AND f.fk_statut <> 0";    // Not a draft
            $sql_fac .= " AND f.fk_statut <> 3";    // Not abandonned
            $sql_fac .= " AND f.fk_statut <> 2";
            $sql_fac .= " AND fe.vendor = ".$vendor->rowid;
            if ($toDate) {
                $sql_fac.= " AND f.date_lim_reglement < '".$toDate."'";
            }           

            $resql_fac = $db->query($sql_fac);
            if($resql_fac) {
                
                require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
                $facturestatic = new Facture($db);

                while($invoice = $db->fetch_object($resql_fac)) {

                    $facturestatic->id = $invoice->rowid;
                    $paiement = $facturestatic->getSommePaiement($toDate);

                    $due_balance += $invoice->total_ttc - $paiement;                    
                }
            }

            $total_due_balance += $due_balance; 

            $user = new User($db);
            $user->fetch($vendor->rowid);

           $objPHPExcel->setActiveSheetIndex(0)->getStyle('A'.$row_index.':B'.$row_index)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$row_index, $user->id);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$row_index, $user->firstname.' '.$user->lastname); 
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('C'.$row_index.':F'.$row_index)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); 
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$row_index, $sales);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$row_index, $amount);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$row_index, $balance);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$row_index, $due_balance);                

            $row_index++;

        }

        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A'.$row_index.':F'.$row_index)->getFont()->setBold(true);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$row_index.':F'.$row_index)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$row_index, 'TOTALES');  
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$row_index, $total_sales);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$row_index, $total_amount);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$row_index, $total_balance);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F'.$row_index, $total_due_balance);  

    }
        
    if($fromDate && $toDate) {
        $fromDate = date('d-m-y', strtotime($fromDate));
        $toDate = date('d-m-y', strtotime($toDate));
        $filename = 'reporte_agente_'.$fromDate.'_'.$toDate;
    } else {
        $filename = 'reporte_agente_'.strtolower(monthName($cmonth));
    }

    header("Content-Type:           application/vnd.ms-excel;   charset=utf-8");
    header("Content-Disposition:    attachment;                 filename=$filename.xls");
    header('Cache-Control:          max-age=0');      
    $objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
    $objWriter->save('php://output');
    exit;
?>