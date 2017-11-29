<?php
/**
 *	Class to offer components to list and upload files
 */
class FormFileCfdiMx
{
	var $db;
	var $error;

	var $numoffiles;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->numoffiles=0;
		return 1;
	}
	
	/**
	 *      Return a string to show the box with list of available documents for object.
	 *      This also set the property $this->numoffiles
	 *
	 *      @param      string				$modulepart         propal, facture, facture_fourn, ...
	 *      @param      string				$filename           Sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if $filedir is already complete)
	 *      @param      string				$filedir            Directory to scan
	 *      @param      string				$urlsource          Url of origin page (for return)
	 *      @param      int					$genallowed         Generation is allowed (1/0 or array list of templates)
	 *      @param      int					$delallowed         Remove is allowed (1/0)
	 *      @param      string				$modelselected      Model to preselect by default
	 *      @param      string				$allowgenifempty	Allow generation even if list of template ($genallowed) is empty (show however a warning)
	 *      @param      string				$forcenomultilang	Do not show language option (even if MAIN_MULTILANGS defined)
	 *      @param      int					$iconPDF            Obsolete, see getDocumentsLink
	 * 		@param		int					$maxfilenamelength	Max length for filename shown
	 * 		@param		string				$noform				Do not output html form tags
	 * 		@param		string				$param				More param on http links
	 * 		@param		string				$title				Title to show on top of form
	 * 		@param		string				$buttonlabel		Label on submit button
	 * 		@param		string				$codelang			Default language code to use on lang combo box if multilang is enabled
	 * 		@param		HookManager			$hookmanager		Object hookmanager with instance of external modules hook classes
	 * 		@return		string              					Output string with HTML array of documents (might be empty string)
	 */
	function showdocuments($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$allowgenifempty=1,$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28,$noform=0,$param='',$title='',$buttonlabel='',$codelang='',$hookmanager=false,$cfdi_tot)
	{
		// filedir = conf->...dir_ouput."/".get_exdir(id)
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	
		global $langs,$bc,$conf;
	
		// For backward compatibility
		if (! empty($iconPDF)) {
			return $this->getDocumentsLink($modulepart, $filename, $filedir);
		}
	
		$forname='builddoc';
		$out='';
		$var=true;
	
		//$filename = dol_sanitizeFileName($filename);    //Must be sanitized before calling show_documents
		$headershown=0;
		$showempty=0;
		$i=0;
	
		$titletoshow=$langs->trans("Documents");
		if (! empty($title)) $titletoshow=$title;
	
		$out.= "\n".'<!-- Start show_document -->'."\n";
		//print 'filedir='.$filedir;
	
		// Affiche en-tete tableau
		if ($genallowed)
		{
			$modellist=array();
	
			if ($modulepart == 'facture')
			{
				$showempty=1;
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
					$modellist=ModelePDFFactures::liste_modeles($this->db);
				}
			}
			else
			{
				// Generic feature, for external modules
				$file=dol_buildpath('/core/modules/'.$modulepart.'/modules_'.$modulepart.'.php',0);
				if (file_exists($file))
				{
					$res=include_once $file;
				}
				$class='Modele'.ucfirst($modulepart);
				if (class_exists($class))
				{
					$modellist=call_user_func($class.'::liste_modeles',$this->db);
				}
				else
				{
					dol_print_error($this->db,'Bad value for modulepart');
					return -1;
				}
			}
	
			$headershown=1;
	
			$form = new Form($this->db);
			$buttonlabeltoshow=$buttonlabel;
			if (empty($buttonlabel)) $buttonlabel=$langs->trans('Generate');
	
			if (empty($noform)) $out.= '<form action="'.$urlsource.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc').'" name="'.$forname.'" id="'.$forname.'_form" method="post">';
			$out.= '<input type="hidden" name="action" value="builddoc">';
			$out.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	
			$out.= '<div class="titre">'.$titletoshow;
		if(1){	
			$out.= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input class="button" id="'.$forname.'_generatebutton"';
			$out.= ' type="submit" value="'.$buttonlabel.'"';
			if (! $allowgenifempty && ! is_array($modellist) && empty($modellist)) $out.= ' disabled="disabled"';
			$out.= '>';
			if ($allowgenifempty && ! is_array($modellist) && empty($modellist) && $modulepart != 'unpaid')
			{
				$langs->load("errors");
				$out.= ' '.img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
			}
			$tpdomic=str_replace(' ','-',$_SESSION['tpdomic']);
			$urlsourcePDF = str_replace("facture", "regenpdf", $urlsource);
			$urlsourcePDF=$urlsourcePDF.'&tpdomicilio='.$tpdomic;
			$urlsourceXML = str_replace('facture', 'regenxml', $urlsource);
			$urlsourcePrePDF = str_replace('facture', 'regenpdf', $urlsource);
			$urlsourcePrePDF = $urlsourcePrePDF.'&tpdomicilio='.$tpdomic;
			$genArchPDF =1;
			$genArchXML =1;
			$genPrevPDF =1;
			$idx= $_REQUEST["facid"];
			if($cfdi_tot>0){
				if($genArchPDF==1 and $this->numoffiles == 0){
					$out.= '<input type="button" onClick=window.open("'.$urlsourcePDF.'&band=0","mywindow2","scrollbars=1,width=500,height=250"); id="regenpdf" name="regenpdf" class="button" value="Regenerar PDF">';
					$out.= '&nbsp;';
				}
				if($genArchXML==1 and $this->numoffiles == 0){
					$out.= '<input type="button" onClick=window.open("'.$urlsourceXML.'&band=0","mywindow2","scrollbars=1,width=500,height=250"); id="regenxml" name="regenxml" class="button" value="Regenerar XML">';
				}
			}
			if($genPrevPDF==1 and $this->numoffiles==0){
				$urlsourcePrePDF .= '&previewpdf=previewpdf';
				$out.='<input type="button" onClick=window.open("'.$urlsourcePrePDF.'&band=0","mywindow2","scrollbars=1,width=500,height=250"); id="regenPrePDF" name="regenPrePDF" class="button" value="Previsualizar PDF">';
			}
		}
			$out.='</div>';
			$out.= '<table class="liste formdoc noborder" summary="listofdocumentstable" width="100%">';
	
			$out.= '<tr class="liste_titre">';
	
			// Model
			if (! empty($modellist))
			{
				$out.= '<th align="center" class="formdoc liste_titre">';
				$out.= $langs->trans('Model').' ';
				if (is_array($modellist) && count($modellist) == 1)    // If there is only one element
				{
					$arraykeys=array_keys($modellist);
					$modelselected=$arraykeys[0];
				}
				$out.= $form->selectarray('model',$modellist,$modelselected,$showempty,0,0);
				$out.= '</th>';
			}
			else
			{
				$out.= '<th align="left" class="formdoc liste_titre">';
				$out.= $langs->trans("Files");
				$out.= '</th>';
			}
	
			// Language code (if multilang)
			$out.= '<th align="center" class="formdoc liste_titre">';
			if (($allowgenifempty || (is_array($modellist) && count($modellist) > 0)) && $conf->global->MAIN_MULTILANGS && ! $forcenomultilang)
			{
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
				$formadmin=new FormAdmin($this->db);
				$defaultlang=$codelang?$codelang:$langs->getDefaultLang();
				$out.= $formadmin->select_language($defaultlang);
			}
			else
			{
				$out.= '&nbsp;';
			}
			$out.= '</th>';
	
			// Button
			$out.= '<th align="center" colspan="'.($delallowed?'2':'1').'" class="formdocbutton liste_titre">';
// 			$out.= '<input class="button" id="'.$forname.'_generatebutton"';
// 			$out.= ' type="submit" value="'.$buttonlabel.'"';
// 			if (! $allowgenifempty && ! is_array($modellist) && empty($modellist)) $out.= ' disabled="disabled"';
// 			$out.= '>';
// 			if ($allowgenifempty && ! is_array($modellist) && empty($modellist) && $modulepart != 'unpaid')
// 			{
// 				$langs->load("errors");
// 				$out.= ' '.img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
// 			}
			
// 			//print $filename;
// 			//print '<br>';
// 			//print $filedir;
// 			//print '<br>';			
// 			//print $urlsource;
// 			//print '<br>';			
// 			//session_start();
// 			$tpdomic=str_replace(' ','-',$_SESSION['tpdomic']);
// 			$urlsourcePDF = str_replace("facture", "regenpdf", $urlsource);
// 			$urlsourcePDF=$urlsourcePDF.'&tpdomicilio='.$tpdomic;
// 			//print $urlsourcePDF;
// 			//print '<br>';			
// 			$urlsourceXML = str_replace('facture', 'regenxml', $urlsource);
// 			//print '<br>';			
// 			$urlsourcePrePDF = str_replace('facture', 'regenpdf', $urlsource);
// 			$urlsourcePrePDF = $urlsourcePrePDF.'&tpdomicilio='.$tpdomic;
// 			//print '<br>';			
// 			/////////////////////////////////////////////
// 			/////////////////////////////////////////////
// 			/////////////////////////////////////////////
// 			$genArchPDF =1;
// 			$genArchXML =1;
// 			$genPrevPDF =1;			
// 			$idx= $_REQUEST["facid"];
// 			if($cfdi_tot>0){
// 			if($genArchPDF==1 and $this->numoffiles == 0){
// 				$out.= '<input type="button" onClick=window.open("'.$urlsourcePDF.'&band=0","mywindow2","scrollbars=1,width=500,height=250"); id="regenpdf" name="regenpdf" class="button" value="PDF">';
// 				$out.= '&nbsp;';
// 			}
// 			if($genArchXML==1 and $this->numoffiles == 0){			
// 				$out.= '<input type="button" onClick=window.open("'.$urlsourceXML.'&band=0","mywindow2","scrollbars=1,width=500,height=250"); id="regenxml" name="regenxml" class="button" value="XML">';
// 			}
// 			}
// 			if($genPrevPDF==1 and $this->numoffiles==0){
// 				$urlsourcePrePDF .= '&previewpdf=previewpdf';
// 				$out.='<input type="button" onClick=window.open("'.$urlsourcePrePDF.'&band=0","mywindow2","scrollbars=1,width=500,height=250"); id="regenPrePDF" name="regenPrePDF" class="button" value="Preview PDF">';
// 			}			
			/////////////////////////////////////////////
			/////////////////////////////////////////////
			/////////////////////////////////////////////
			
			$out.= '</th>';
	
			$out.= '</tr>';
	
			// Execute hooks
			$parameters=array('socid'=>(isset($GLOBALS['socid'])?$GLOBALS['socid']:''),'id'=>(isset($GLOBALS['id'])?$GLOBALS['id']:''),'modulepart'=>$modulepart);
			if (is_object($hookmanager)) $out.= $hookmanager->executeHooks('formBuilddocOptions',$parameters,$GLOBALS['object']);
		}
	
		// Get list of files
		if (! empty($filedir))
		{
			$file_list=dol_dir_list($filedir,'files',0,'','\.meta$','date',SORT_DESC);
	
			// Affiche en-tete tableau si non deja affiche
			if (! empty($file_list) && ! $headershown)
			{
				$headershown=1;
				$out.= '<div class="titre">'.$titletoshow.'</div>';
				$out.= '<table class="border" summary="listofdocumentstable" width="100%">';
			}
	
			// Loop on each file found
			if (is_array($file_list))
			{
				foreach($file_list as $file)
				{
					$var=!$var;
	
					// Define relative path for download link (depends on module)
					$relativepath=$file["name"];								// Cas general
					if ($filename) $relativepath=$filename."/".$file["name"];	// Cas propal, facture...
					// Autre cas
					if ($modulepart == 'donation')            { $relativepath = get_exdir($filename,2).$file["name"]; }
					if ($modulepart == 'export')              { $relativepath = $file["name"]; }
	
					$out.= "<tr ".$bc[$var].">";
	
					// Show file name with link to download
					$out.= '<td nowrap="nowrap">';
					$out.= '<a href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'"';
					$mime=dol_mimetype($relativepath,'',0);
					if (preg_match('/text/',$mime)) $out.= ' target="_blank"';
					$out.= '>';
					$out.= img_mime($file["name"],$langs->trans("File").': '.$file["name"]).' '.dol_trunc($file["name"],$maxfilenamelength);
					$out.= '</a>'."\n";
					$out.= '</td>';
	
					// Show file size
					$size=(! empty($file['size'])?$file['size']:dol_filesize($filedir."/".$file["name"]));
					$out.= '<td align="right" nowrap="nowrap">'.dol_print_size($size).'</td>';
	
					// Show file date
					$date=(! empty($file['date'])?$file['date']:dol_filemtime($filedir."/".$file["name"]));
					$out.= '<td align="right" nowrap="nowrap">'.dol_print_date($date, 'dayhour').'</td>';
	
					if ($delallowed)
					{
						$out.= '<td align="right">';
						$out.= '<a href="'.$urlsource.(strpos($urlsource,'?')?'&':'?').'action=remove_file&file='.urlencode($relativepath);
						$out.= ($param?'&'.$param:'');
						//$out.= '&modulepart='.$modulepart; // TODO obsolete ?
						//$out.= '&urlsource='.urlencode($urlsource); // TODO obsolete ?
						$out.= '">'.img_delete().'</a></td>';
						////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						// Solo si se necesita uno por uno
						// 1 visible, 0 no visible
						$regenArch =0;
						if($regenArch==1){
							$band=substr($file["name"],37,3);
							if($band=="pdf")
							{
								$idx= $_REQUEST["facid"];
								$out.= '<td align="center"> ';
								$out.= '<input type="button" onClick=window.open("/dolibarr-3.3.2-dev/htdocs/cfdimx/regenera.php?band=0&facidx='.$idx.'","mywindow2","scrollbars=1,width=500,height=250"); id="regenpdf" name="regenpdf" class="button" value="Regenera PDF">';
								//$out.= '<a href="/dolibarr_eg/dolibarr-3.2.2/htdocs/cfdimx/regenera.php?band=0&facidx='.$idx.'"> Regenera PDF </a>';
								$out.= '</td>';
							}
							else if($band=="xml")
							{
								$idx= $_REQUEST["facid"];
								$out.= '<td align="center"> ';
								$out.= '<input type="button" onClick=window.open("/dolibarr-3.3.2-dev/htdocs/cfdimx/regenxml.php?band=0&facidx='.$idx.'","mywindow2","scrollbars=1,width=500,height=250"); id="regenxml" name="regenxml" class="button" value="Regenera XML">';
								//$out.= '<a href="/dolibarr_eg/dolibarr-3.2.2/htdocs/cfdimx/regenxml.php?band=0&facidx='.$idx.'"> Regenera XML </a>';
								$out.= '</td>';
							}
						}
						////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////						
					}
				}
	
				$out.= '</tr>';
	
				$this->numoffiles++;
			}
		}
	
		if ($headershown)
		{
			// Affiche pied du tableau
			$out.= "</table>\n";
			if ($genallowed)
			{
				if (empty($noform)) $out.= '</form>'."\n";
			}
		}
		$out.= '<!-- End show_document -->'."\n";
		//return ($i?$i:$headershown);
		return $out;
	}
	
	/**
	 *	Show only Document icon with link
	 *
	 *	@param	string	$modulepart		propal, facture, facture_fourn, ...
	 *	@param	string	$filename		Sub-directory to scan (Example: '0/1/10', 'FA/DD/MM/YY/9999'). Use '' if $filedir is already complete)
	 *	@param	string	$filedir		Directory to scan
	 *	@return	string              	Output string with HTML link of documents (might be empty string)
	 */
	function getDocumentsLink($modulepart, $filename, $filedir)
	{
		if (! function_exists('dol_dir_list')) {
			include DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		}
	
		$out='';
	
		$this->numoffiles=0;
	
		$file_list=dol_dir_list($filedir, 'files', 0, $filename.'.pdf', '\.meta$|\.png$');
	
		// For ajax treatment
		$out.= '<div id="gen_pdf_'.$filename.'" class="linkobject hideobject">'.img_picto('', 'refresh').'</div>'."\n";
	
		if (! empty($file_list))
		{
			// Loop on each file found
			foreach($file_list as $file)
			{
				// Define relative path for download link (depends on module)
				$relativepath=$file["name"];								// Cas general
				if ($filename) $relativepath=$filename."/".$file["name"];	// Cas propal, facture...
				// Autre cas
				if ($modulepart == 'donation')            {
					$relativepath = get_exdir($filename,2).$file["name"];
				}
				if ($modulepart == 'export')              {
					$relativepath = $file["name"];
				}
	
				// Show file name with link to download
				$out.= '<a href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'"';
				$mime=dol_mimetype($relativepath,'',0);
				if (preg_match('/text/',$mime)) $out.= ' target="_blank"';
				$out.= '>';
				$out.= img_pdf($file["name"],2);
				$out.= '</a>'."\n";
	
				$this->numoffiles++;
			}
		}
	
		return $out;
	}	
}
?>