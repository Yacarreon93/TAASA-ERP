<?php
    require '../../main.inc.php';
    // Change this following line to use the correct relative path from htdocs
    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
    require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

    $id         = GETPOST('id','int');
    $action     = GETPOST('action','alpha');
    $confirm    = GETPOST('confirm','alpha');
    $subaction  = GETPOST('subaction','alpha');
    $group      = GETPOST("group","int",3);

    $sortfield = GETPOST("sortfield",'alpha');
    $sortorder = GETPOST("sortorder",'alpha');
    $page = GETPOST("page",'int');
    if ($page == -1) {
        $page = 0;
    }
    $offset = $conf->liste_limit * $page;
    if (! $sortorder) $sortorder='DESC';
    if (! $sortfield) $sortfield='f.datef';
    $limit = $conf->liste_limit;

    $pageprev = $page - 1;
    $pagenext = $page + 1;

    $search_user = GETPOST('search_user','int');
    $search_sale = GETPOST('search_sale','int');
    $search_type = GETPOST('range','int');
    $day    = GETPOST('day','int');
    $month  = GETPOST('month','int');
    $year   = GETPOST('year','int');
    $month_general  = GETPOST('month_general','int');
    $year_general   = GETPOST('year_general','int');
    if($month_general != '') $month = $month_general;
    if($year_general != '') $year = $year_general;
    $monthSelected =  GETPOST('monthSelected','int');
$fromDate = GETPOST('fromDate');
$fromDate2 = GETPOST('fromDate');
$toDate = GETPOST('toDate');
$toDate2 = GETPOST('toDate');
if($search_type == 1) { //Search type refers to the date selected range (general, montlhy, weekly)
    $year = 2016;
    $month = "";
}
else if($search_type == 2) {
    if($month_general != '') $month = $month_general;
    if($year_general != '') $year = $year_general;
}

else if($search_type == 3) { //Seting dates to correct sql format
    if($month_week != '') $month = $month_week;
    if($year_week != '') $year = $year_week;
    $fromDate = $fromDate[6] . $fromDate[7] . $fromDate[8] . $fromDate[9] . $fromDate[3] . $fromDate[4] . $fromDate[0] . $fromDate[1] . "000000";
    $toDate = $toDate[6] . $toDate[7] . $toDate[8] . $toDate[9] . $toDate[3] . $toDate[4] . $toDate[0] . $toDate[1] . "235900";
}
else {
    $fromDate = "";
    $toDate = "";
}

    $day_lim    = GETPOST('day_lim','int');
    $month_lim  = GETPOST('month_lim','int');
    $year_lim   = GETPOST('year_lim','int');
    $filtre = GETPOST('filtre');

    //TODO: jalar nombres de vendedores de db

     $sql = 'SELECT * FROM llx_user WHERE rowid = '.$id;
     $res = $db->query($sql) or die('ERROR en la consulta: '.$sql);
     $row = $db->fetch_object($res);
     $vendedor = $row->firstname;

    // Incluir la librería
    require_once 'class/PHPExcel.php';
    $objPHPExcel = new PHPExcel();
    /////
    //Titulo
   $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'REPORTE '.$vendedor.'  DEL '. $fromDate2 . ' AL '. $toDate2);
    // Encabezado
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'VENTAS ');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B3', 'FACTURA');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C3', 'FECHA');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D3', 'TERCERO');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E3', 'IMPORTE');
    /////

    $sql = 'SELECT';
            if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
            $sql.= ' f.rowid as facid, f.facnumber, f.ref_client, f.type, f.note_private, f.increment, f.total as total_ht, f.tva as total_tva, f.total_ttc,';
            $sql.= ' f.datef as df, f.date_lim_reglement as datelimite,';
            $sql.= ' f.paye as paye, f.fk_statut,';
            $sql.= ' s.nom as name, s.rowid as socid, s.code_client, s.client ';
            if (! $sall) $sql.= ', SUM(pf.amount) as am';   // To be able to sort on status
            $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
            $sql.= ', '.MAIN_DB_PREFIX.'facture as f';
            if (! $sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
            else $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as fd ON fd.fk_facture = f.rowid';
            if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet as pd ON f.rowid=pd.fk_facture';
            if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
            // We'll need this table joined to the select in order to filter by sale
            if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            if ($search_user > 0)
            {
                $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
                $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
            }
            $sql.= ' JOIN '.MAIN_DB_PREFIX.'facture_extrafields as ef ON ef.fk_object = f.rowid';
            $sql.= ' WHERE f.fk_soc = s.rowid';
            $sql.= ' AND ef.vendor = '.$id;
            $sql.= ' AND f.fk_statut != 3';
            $sql.= " AND f.entity = ".$conf->entity;
            if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
            if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
            if ($userid)
            {
                if ($userid == -1) $sql.=' AND f.fk_user_author IS NULL';
                else $sql.=' AND f.fk_user_author = '.$userid;
            }
            if ($filtre)
            {
                $aFilter = explode(',', $filtre);
                foreach ($aFilter as $filter)
                {
                    $filt = explode(':', $filter);
                    $sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
                }
            }
            if ($search_ref) $sql .= natural_search('f.facnumber', $search_ref);
            if ($search_refcustomer) $sql .= natural_search('f.ref_client', $search_refcustomer);
            if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
            if ($search_montant_ht != '') $sql.= natural_search('f.total', $search_montant_ht, 1);
            if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
            if ($search_status != '' && $search_status >= 0) $sql.= " AND f.fk_statut = ".$db->escape($search_status);
            if ($fromDate && $toDate) {
                $sql.= " AND f.datef BETWEEN '".$fromDate."' AND '".$toDate."'";
            }
            else if ($month > 0)
            {
                if ($year > 0 && empty($day))
                $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
                else if ($year > 0 && ! empty($day))
                $sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
                else
                $sql.= " AND date_format(f.datef, '%m') = '".$month."'";
            }
            else if ($year > 0)
            {
                $sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
            }
            if ($month_lim > 0)
            {
                if ($year_lim > 0 && empty($day_lim))
                    $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,$month_lim,false))."' AND '".$db->idate(dol_get_last_day($year_lim,$month_lim,false))."'";
                else if ($year_lim > 0 && ! empty($day_lim))
                    $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_lim, $day_lim, $year_lim))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_lim, $day_lim, $year_lim))."'";
                else
                    $sql.= " AND date_format(f.date_lim_reglement, '%m') = '".$month_lim."'";
            }
            else if ($year_lim > 0)
            {
                $sql.= " AND f.date_lim_reglement BETWEEN '".$db->idate(dol_get_first_day($year_lim,1,false))."' AND '".$db->idate(dol_get_last_day($year_lim,12,false))."'";
            }
            if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
            if ($search_user > 0)
            {
                $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='facture' AND tc.source='internal' AND ec.element_id = f.rowid AND ec.fk_socpeople = ".$search_user;
            }
            if (! $sall)
            {
                $sql.= ' GROUP BY f.rowid, f.facnumber, ref_client, f.type, f.note_private, f.increment, f.total, f.tva, f.total_ttc,';
                $sql.= ' f.datef, f.date_lim_reglement,';
                $sql.= ' f.paye, f.fk_statut,';
                $sql.= ' s.nom, s.rowid, s.code_client, s.client';
            }
            else
            {
                $sql .= natural_search(array('s.nom', 'f.facnumber', 'f.note_public', 'fd.description'), $sall);
            }
            $sql.= ' ORDER BY ';
            $listfield=explode(',',$sortfield);
            foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
            $sql.= ' f.rowid DESC ';

    $res = $db->query($sql) or die('ERROR en la consulta: '.$sql);
    $rows = $db->num_rows($res);

    if ($rows > 0)
    {

        $facturestatic=new Facture($db);
        $i = 4;
        $importeTotalVentas = 0;

        while($row = $db->fetch_object($res))
        {
            $importeTotalVentas += $row->total_ttc;
            $datelimit=$db->jdate($objp->datelimite);

            $facturestatic->id=$objp->facid;
            $facturestatic->ref=$objp->facnumber;
            $facturestatic->type=$objp->type;
            $notetoshow=dol_string_nohtmltag(($user->societe_id>0?$objp->note_public:$objp->note),1);
            $paiement = $facturestatic->getSommePaiement();

            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, $row->facnumber);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, dol_print_date($db->jdate($row->df),'day'));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, $row->name);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, $row->total_ttc);

            $i++;
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, 'TOTAL');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, $importeTotalVentas);
        $i++;
        $i++;

    }

    //COBRANZA
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$i, 'COBRANZA');
    $i++;
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, 'FACTURA');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, 'FECHA');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, 'TERCERO');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, 'IMPORTE');
    $i++;

    $sql = 'SELECT f.facnumber, b.datec, s.nom, pf.amount FROM llx_bank AS b 
    JOIN llx_paiement AS p ON p.fk_bank = b.rowid
    JOIN llx_paiement_facture AS pf ON pf.fk_paiement = p.rowid
    JOIN llx_facture AS f ON f.rowid = pf.fk_facture
    JOIN llx_facture_extrafields AS fe ON f.rowid = fe.fk_object
    JOIN llx_societe AS s ON f.fk_soc = s.rowid
    JOIN llx_user_relations AS ur ON ur.fk_user = vendor';
    if($id == 15) { //Ventas Gerardo Lagos (id=3)
        $sql.= ' WHERE (b.fk_account = ur.fk_bank_account OR b.fk_account = 3)';
    } else {
         $sql.= ' WHERE b.fk_account = ur.fk_bank_account';
    }
    
     if ($fromDate && $toDate) {
        $sql.= " AND b.datec BETWEEN '".$fromDate."' AND '".$toDate."'";
    }
    else if ($month > 0)
    {
        if ($year > 0 && empty($day))
            $sql.= " AND b.datec BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
        else if ($year > 0 && ! empty($day))
            $sql.= " AND b.datec BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
        else
            $sql.= " AND date_format(b.datec, '%m') = '".$month."'";
    }
    else if ($year > 0)
    {
        $sql.= " AND b.datec BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
    }
        $sql.= ' AND label != "Retiro por corte de caja"
        AND vendor = '.$id.'
        GROUP BY pf.rowid';
        $sql.= ' ORDER BY datec DESC,';
        $sql.= ' f.rowid DESC ';

    $res = $db->query($sql) or die('ERROR en la consulta: '.$sql);
    $rows = $db->num_rows($res);

    //print_r($sql);
    //die();

    if ($rows > 0)
    {
        $importeTotalCobranza = 0;

        while($row = $db->fetch_object($res))
        {
            $importeTotalCobranza += $row->amount;
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, $row->facnumber);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C'.$i, dol_print_date($db->jdate($row->datec),'day'));
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D'.$i, $row->nom);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, $row->amount);

            $i++;
        }
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$i, 'TOTAL');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$i, $importeTotalCobranza);
        $i++;

    }

     $db->free($res);
     $db->close();


    $date = getdate();
    $nom_archivo = 'inv_'.$date['mday'].'-'.$date['mon'].'_'.$date['hours'].'-'.$date['minutes'];
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$nom_archivo.xls");
    header('Cache-Control: max-age=0');
    $objWriter=PHPExcel_IOFactory::createWriter($objPHPExcel,'Excel2007');
    $objWriter->save('php://output');
    exit;
?>
