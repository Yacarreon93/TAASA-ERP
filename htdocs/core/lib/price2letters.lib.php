<?php

function price2letters($price, $fem = false, $dec = true) { 
    
    $matuni[2]  = "dos"; 
    $matuni[3]  = "tres"; 
    $matuni[4]  = "cuatro"; 
    $matuni[5]  = "cinco"; 
    $matuni[6]  = "seis"; 
    $matuni[7]  = "siete"; 
    $matuni[8]  = "ocho"; 
    $matuni[9]  = "nueve"; 
    $matuni[10] = "diez"; 
    $matuni[11] = "once"; 
    $matuni[12] = "doce"; 
    $matuni[13] = "trece"; 
    $matuni[14] = "catorce"; 
    $matuni[15] = "quince"; 
    $matuni[16] = "dieciseis"; 
    $matuni[17] = "diecisiete"; 
    $matuni[18] = "dieciocho"; 
    $matuni[19] = "diecinueve"; 
    $matuni[20] = "veinte"; 
    $matunisub[2] = "dos"; 
    $matunisub[3] = "tres"; 
    $matunisub[4] = "cuatro"; 
    $matunisub[5] = "quin"; 
    $matunisub[6] = "seis"; 
    $matunisub[7] = "sete"; 
    $matunisub[8] = "ocho"; 
    $matunisub[9] = "nove"; 

    $matdec[2] = "veint"; 
    $matdec[3] = "treinta"; 
    $matdec[4] = "cuarenta"; 
    $matdec[5] = "cincuenta"; 
    $matdec[6] = "sesenta"; 
    $matdec[7] = "setenta"; 
    $matdec[8] = "ochenta"; 
    $matdec[9] = "noventa"; 
    $matsub[3]  = 'mill'; 
    $matsub[5]  = 'bill'; 
    $matsub[7]  = 'mill'; 
    $matsub[9]  = 'trill'; 
    $matsub[11] = 'mill'; 
    $matsub[13] = 'bill'; 
    $matsub[15] = 'mill'; 
    $matmil[4]  = 'millones'; 
    $matmil[6]  = 'billones'; 
    $matmil[7]  = 'de billones'; 
    $matmil[8]  = 'millones de billones'; 
    $matmil[10] = 'trillones'; 
    $matmil[11] = 'de trillones'; 
    $matmil[12] = 'millones de trillones'; 
    $matmil[13] = 'de trillones'; 
    $matmil[14] = 'billones de trillones'; 
    $matmil[15] = 'de billones de trillones'; 
    $matmil[16] = 'millones de billones de trillones'; 

   $price = trim((string)@$price);

    if ($price[0] == '-') { 
        $neg = 'menos '; 
        $price = substr($price, 1); 
    } else {
        $neg = '';
    }

    while ($price[0] == '0') {
        $price = substr($price, 1);
    }

    if ($price[0] < '1' or $price[0] > 9) { 
        $price = '0'.$price; 
    }

    $zeros = true; 
    $punt = false; 
    $ent = ''; 
    $fra = '';

    for ($c = 0; $c < strlen($price); $c++) { 
        $n = $price[$c]; 
        if (!(strpos(".", $n) === false)) { 
            if ($punt) break;
            else {
                $punt = true;
                continue;
            }
        } else if (!(strpos('0123456789', $n) === false)) {
            if ($punt) {
                if ($n !== '0') $zeros = false;
                $fra .= $n;
            } else {
                $ent .= $n;
            }
        } else if (!(strpos(",", $n) === false)) {
            continue;
        } else break;
    }

    $ent = '     '.$ent;

    $fin = $fra.'/100 M.N.';
    
    if ((int)$ent === 0) return 'Cero Pesos '.$fin; 
    
    $tex = ''; 
    $sub = 0; 
    $mils = 0; 
    $neutro = false; 
    
    while (($price = substr($ent, -3)) != '   ') { 
        $ent = substr($ent, 0, -3);
        if (++$sub < 3 and $fem) { 
            $matuni[1] = 'una'; 
            $subcent = 'as'; 
        } else { 
            $matuni[1] = $neutro ? 'un' : 'uno'; 
            $subcent = 'os'; 
        } 
        $t = ''; 
        $n2 = substr($price, 1); 
        if ($n2 == '00') { 
        } else if ($n2 < 21) {
            $t = ' '.$matuni[(int)$n2];
        }
        else if ($n2 < 30) { 
            $n3 = $price[2]; 
            if ($n3 != 0) $t = 'i'.$matuni[$n3]; 
            $n2 = $price[1]; 
            $t = ' '.$matdec[$n2].$t; 
        } else { 
            $n3 = $price[2]; 
            if ($n3 != 0) $t = ' y '.$matuni[$n3]; 
            $n2 = $price[1];
            $t = ' '.$matdec[$n2].$t; 
        } 
        $n = $price[0]; 
        if ($n == 1) { 
            $t = ' ciento'.$t; 
        } else if ($n == 5) { 
            $t = ' '.$matunisub[$n].'ient'.$subcent.$t; 
        } else if ($n != 0) { 
            $t = ' '.$matunisub[$n].'cient'.$subcent.$t; 
        } 
        if ($sub == 1) { 
        } else if (! isset($matsub[$sub])) { 
            if ($price == 1) { 
                $t = ' mil'; 
            } else if ($price > 1) { 
                $t .= ' mil'; 
            } 
        } else if ($price == 1) { 
            $t .= ' '.$matsub[$sub].'ón'; 
        } else if ($price > 1) { 
            $t .= ' '.$matsub[$sub].'ones'; 
        }   
        if ($price == '000') $mils ++; 
        else if ($mils != 0) { 
            if (isset($matmil[$sub])) $t .= ' '.$matmil[$sub]; 
            $mils = 0; 
        } 
        $neutro = true; 
        $tex = $t.$tex; 
    } 

    $tex = $neg.substr($tex, 1).' pesos '.$fin; 
    
    return ucfirst($tex); 
}
