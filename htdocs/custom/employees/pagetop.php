<?php
// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
dol_include_once('/employees/class/skeleton_class.class.php');

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');

$search_field1=GETPOST("search_field1");
$search_field2=GETPOST("search_field2");

if($action == "") $action = "list";

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

if (empty($action) && empty($id) && empty($ref)) $action='list';

// Load object if id or ref is provided as parameter
/*
$object=new User($db);
if (($id > 0 || ! empty($ref)) && $action != 'add')
{
	$result=$object->fetch($id,$ref);
	if ($result < 0) dol_print_error($db);
}
*/

// Initialize technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('skeleton'));
$extrafields = new ExtraFields($db);

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

llxHeader('','Vendedores','');


// Part to show a list
if ($action == 'list')
{
	// Put here content of your page
	print load_fiche_titre('PageTitle');
    
	$sql = "SELECT";
    $sql.= " u.rowid,";
    $sql.= " u.lastname,";
    $sql.= " u.firstname";
	// Add fields for extrafields
	foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
	// Add fields from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
    $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
    $sql.= " JOIN ".MAIN_DB_PREFIX."user_extrafields as ef ON ef.fk_object = u.rowid";
    $sql.= " WHERE ef.rol = 1";

    // if ($search_field1) $sql.= natural_search("field1",$search_field1);
    // if ($search_field2) $sql.= natural_search("field2",$search_field2);
    
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;

    // Count total nb of records
    $nbtotalofrecords = 0;
    if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
    {
    	$result = $db->query($sql);
    	$nbtotalofrecords = $db->num_rows($result);
    }	
	
    $sql.= $db->order($sortfield, $sortorder);
	$sql.= $db->plimit($conf->liste_limit+1, $offset);
    

    dol_syslog($script_file, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        
        $params='';
    	$params.= '&amp;search_field1='.urlencode($search_field1);
    	$params.= '&amp;search_field2='.urlencode($search_field2);
        
        print_barre_liste($title, $page, $_SERVER["PHP_SELF"],$params,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_companies');
        
    
    	print '<form method="GET" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    
    	if (! empty($moreforfilter))
    	{
    		print '<div class="liste_titre">';
    		print $moreforfilter;
        	$parameters=array();
        	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    	    print $hookmanager->resPrint;
    	    print '</div>';
    	}
    
    	print '<table class="noborder">'."\n";
    
        // Fields title
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans('Nombre'),$_SERVER['PHP_SELF'],'t.field1','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Apellido'),$_SERVER['PHP_SELF'],'t.field2','',$param,'',$sortfield,$sortorder);
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        print '</tr>'."\n";
    
        // Fields title search
    	print '<tr class="liste_titre">';
    	print '<td class="liste_titre"><input type="text" class="flat" name="search_field1" value="'.$search_field1.'" size="10"></td>';
    	print '<td class="liste_titre"><input type="text" class="flat" name="search_field2" value="'.$search_field2.'" size="10"></td>';
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        print '</tr>'."\n";
            
        
        $i = 0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            if ($obj)
            {

                $user = new User($db);
                $user->fetch($obj->rowid);                        
                // You can use here results
                print '<tr>';
                print '<td>'.$user->getNomUrl(1).'</td>';
                print '<td>'.$user->lastname.'</td>';
		        $parameters=array('obj' => $obj);
        		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;
        		print '</tr>';
            }
            $i++;
        }
        
        $db->free($resql);
    
    	$parameters=array('sql' => $sql);
    	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
    	print $hookmanager->resPrint;
    
    	print "</table>\n";
    	print "</form>\n";
        
    }
    else
	{
        $error++;
        dol_print_error($db);
    }
}

// End of page
llxFooter();
$db->close();
