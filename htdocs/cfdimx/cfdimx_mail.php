<?php 

class cfdimx_mail {
	function __construct(){
		
	}
	/*
	 * $to 			define the receiver of the email
	 * $from        define the emisor of the email	 
	 * $subject     define the subject of the email
	 * $message     define the message to be sent. Each line should be separated with \n
	 * 
	 * 
	 * $result
	 * 
	 */
	function simple_mail($to='', $from='', $subject='', $message='') {
		
		$headers = "From: ".$from."\r\nReply-To: ".$from;
		//send the email
		ini_set("SMTP","mail.auribox.com");
		ini_set("smtp_port","587");	
		
		$mail_sent = mail( $to, $subject, $message, $headers );
		//if the message is sent successfully print "Mail sent". Otherwise print "Mail failed" 
		return $mail_sent ? "Mail sent" : "Mail failed";
	}
   /*
	* $to 			define the receiver of the email
	* $from        define the emisor of the email
	* $subject     define the subject of the email
	* $message     define the message to be sent. Each line should be separated with \n
	*
	*
	* $result
	*
	*/	
	function html_mail($to='', $from='', $subject='', $message='') {

		//create a boundary string. It must be unique
		//so we use the MD5 algorithm to generate a random hash
		$random_hash = md5(date('r', time()));
		//define the headers we want passed. Note that they are separated with \r\n
		$headers = "From: ".$from."\r\nReply-To: ".$from;
		//add boundary string and mime type specification
		$headers .= "\r\nContent-Type: multipart/alternative; boundary=\"PHP-alt-".$random_hash."\"";
		//define the body of the message.
		ob_start(); //Turn on output buffering
		?>
		--PHP-alt-<?php echo $random_hash; ?> 
		Content-Type: text/plain; charset="iso-8859-1"
		Content-Transfer-Encoding: 7bit
		
		Hello World!!! 
		This is simple text email message. 
		
		--PHP-alt-<?php echo $random_hash; ?> 
		Content-Type: text/html; charset="iso-8859-1"
		Content-Transfer-Encoding: 7bit
		
		<h2>Hello World!</h2>
		<p>This is something with <b>HTML</b> formatting.</p>
		
		--PHP-alt-<?php echo $random_hash; ?>--
		<?
		//copy current buffer contents into $message variable and delete current output buffer
		$message = ob_get_clean();
		//send the email
		$mail_sent = @mail( $to, $subject, $message, $headers );
		//if the message is sent successfully print "Mail sent". Otherwise print "Mail failed" 
		echo $mail_sent ? "Mail sent" : "Mail failed";
	}
	/*
	 * $to 			define the receiver of the email
	* $from        define the emisor of the email
	* $subject     define the subject of the email
	* $message     define the message to be sent. Each line should be separated with \n
	*
	*
	* $result
	*
	*/	
	function attach_mail($to='', $from='', $subject='', $message='') {

		//create a boundary string. It must be unique
		//so we use the MD5 algorithm to generate a random hash
		$random_hash = md5(date('r', time()));
		//define the headers we want passed. Note that they are separated with \r\n
		$headers = "From: ".$from."\r\nReply-To: ".$from;
		//add boundary string and mime type specification
		$headers .= "\r\nContent-Type: multipart/mixed; boundary=\"PHP-mixed-".$random_hash."\"";
		//read the atachment file contents into a string,
		//encode it with MIME base64,
		//and split it into smaller chunks
		$attachment = chunk_split(base64_encode(file_get_contents('attachment.zip')));
		//define the body of the message.
		ob_start(); //Turn on output buffering
		?>
		--PHP-mixed-<?php echo $random_hash; ?> 
		Content-Type: multipart/alternative; boundary="PHP-alt-<?php echo $random_hash; ?>"
		
		--PHP-alt-<?php echo $random_hash; ?> 
		Content-Type: text/plain; charset="iso-8859-1"
		Content-Transfer-Encoding: 7bit
		
		Hello World!!!
		This is simple text email message.
		
		--PHP-alt-<?php echo $random_hash; ?> 
		Content-Type: text/html; charset="iso-8859-1"
		Content-Transfer-Encoding: 7bit
		
		<h2>Hello World!</h2>
		<p>This is something with <b>HTML</b> formatting.</p>
		
		--PHP-alt-<?php echo $random_hash; ?>--
		
		--PHP-mixed-<?php echo $random_hash; ?> 
		Content-Type: application/zip; name="attachment.zip" 
		Content-Transfer-Encoding: base64 
		Content-Disposition: attachment 
		
		<?php echo $attachment; ?>
		--PHP-mixed-<?php echo $random_hash; ?>-- 
		<?php 
		//copy current buffer contents into $message variable and delete current output buffer
		$message = ob_get_clean();
		//send the email
		$mail_sent = @mail( $to, $subject, $message, $headers );
		//if the message is sent successfully print "Mail sent". Otherwise print "Mail failed"
		echo $mail_sent ? "Mail sent" : "Mail failed";
		
	}	
	
	/*
	 * 
	 * 
	 * 
	 */
	
		function enviar_correo($to='', $from='', $subject='', $adjunto )
		{
			$boundary= md5(time()); //valor boundary
			$htmlalt_boundary= $boundary. "_htmlalt"; //boundary suplementario
			$subject; //titulo del correo
			 
			//cabeceras para enviar correo en formato HTML
			$headers = "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: multipart/mixed; boundary=\"". $boundary. "\"\r\n"; //datos mixteados
			$headers .= "From: ". TITULO_WEB. "<robot@sie-group.net>\r\n"; //correo del que lo envia
			 
			//incia cuerpo del mensaje que se visualiza
			$message="--". $boundary. "\r\n";
			$message .= "Content-Type: multipart/alternative; boundary=\"". $htmlalt_boundary. "\"\r\n\r\n"; //contenido alternativo: texto o html
			$message .= "--". $htmlalt_boundary. "\r\n";
			//modo de contenido del cuerpo del mensaje a mostrar
			//if( !strcmp($modo_envio, "texto") ) //modo texto plano
			// {
			// $message .= "Content-Type: text/plain; charset=iso-8859-1\r\n";
			// $message .= "Content-Transfer-Encoding: 8bits\r\n\r\n";
			// $message .= strip_tags(str_replace("<br>", "\n", substr($_POST["email_contenido"], (strpos($_POST["email_contenido"], "<body>")+6)))). "\r\n\r\n";
			// }
			//else //modo html
			// {
			$message .= "Content-Type: text/html; charset=iso-8859-1\r\n";
			$message .= "Content-Transfer-Encoding: 8bits\r\n\r\n";
			// }
			 
			 
			$message .= "\r\n\r\n";
			$message .= "--". $htmlalt_boundary. "--\r\n\r\n"; //fin cuerpo mensaje a mostrar
			 
			//archivos adjuntos
			if( strcmp($adjunto, "0") && strcmp($adjunto, "vacio")  )
			{
				set_time_limit(600);
				$archivo= $adjunto;
				$buf_type= obtener_extencion_stream_archivo($adjunto); //obtenemos tipo archivo
				 
				$fp= fopen( "uploads/".$archivo, "r" ); //abrimos archivo
				$buf= fread( $fp, filesize("uploads/".$archivo) ); //leemos archivo completamente
				fclose($fp); //cerramos apuntador;
				 
				$message .= "--". $boundary. "\r\n";
				$message .= "Content-Type: ". $buf_type. "; name=\"". $archivo. "\"\r\n"; //envio directo de datos
				$message .= "Content-Transfer-Encoding: base64\r\n";
				$message .= "Content-Disposition: attachment; filename=\"". $archivo. "\"\r\n\r\n";
				$message .= base64_encode($buf). "\r\n\r\n";
			}
			$message .= "--". $boundary. "--\r\n\r\n";
			 
			//funcion para enviar correo
			set_time_limit(600);
			//if the message is sent successfully print "Mail sent". Otherwise print "Mail failed"
			$mail_sent = @mail( $to, $subject, $message, $headers );
			echo $mail_sent ? "Mail sent" : "Mail failed";
		}
}
?>