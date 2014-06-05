<?php

$nzshpcrt_gateways[$num] = array(
	'name' => 'Unicredit PagOnline',
	'display_name' => 'Unicredit PagOnline',	
	'internalname' => 'pagonline',
	'form' => 'form_pagonline',
	'submit_function' => 'submit_pagonline',
	'class_name' => 'wpsc_merchant_pagonline',
	'api_version' => 2.0
);

class wpsc_merchant_pagonline extends wpsc_merchant 
{
	function __construct($purchase_id = null, $is_receiving = false) 
	{
		parent::__construct($purchase_id, $is_receiving);
	}

	function submit() 
	{
		global $wpdb;

		$this->set_purchase_processed_by_purchid(2);
		$log = $wpdb->get_row('SELECT * FROM `' . WPSC_TABLE_PURCHASE_LOGS . '` WHERE `sessionid` = ' . $this->cart_data['session_id'] . ' LIMIT 1');

		$totale_price = $log->totalprice;
		$totale_price = str_replace(get_option('wpsc_decimal_separator'), '', $totale_price);
		$totale_price = str_replace(get_option('wpsc_thousands_separator'), '', $totale_price);

		$query = 'numeroCommerciante=' . get_option('pagonline_numeroCommerciante') .
		'&userID=' . get_option('pagonline_userID') .
		'&password=' . get_option('pagonline_password') .
		'&numeroOrdine=' . $log->id .
		'&totaleOrdine=' . $totale_price .
		'&valuta=978' .
		'&flagDeposito=' . get_option('pagonline_flagDeposito') .
		'&urlOk=' . get_option('pagonline_urlOk') .
		'&urlKo=' . get_option('pagonline_urlKo') .
		'&tipoRispostaApv=' . get_option('pagonline_tipoRispostaApv') .
		'&flagRiciclaOrdine=' . get_option('pagonline_flagRiciclaOrdine') .
		'&stabilimento=' . get_option('pagonline_stabilimento');

		$string_to_digest = $query . '&' . get_option('pagonline_stringaSegreta');
		$mac = hash('md5', $string_to_digest, true);
		$mac_encoded = base64_encode($mac);
		$mac_encoded = substr($mac_encoded, 0, 24);

		$query = str_replace(get_option('pagonline_urlOk'), urlencode(get_option('pagonline_urlOk')), $query);
		$query = str_replace(get_option('pagonline_urlKo'), urlencode(get_option('pagonline_urlKo')), $query);
		$query .= '&mac=' . urlencode($mac_encoded);

		wp_redirect('https://pagamenti.unicredito.it/initInsert.do?' . $query);

		exit();
	}
}

function form_pagonline()
{
	$output = '
		<tr>
			<td>
				Numero Commerciante
			</td>
			<td>
				<input type="text" name="numeroCommerciante" value="' . get_option('pagonline_numeroCommerciante') . '">
				<p class="description">
					Codice identificativo del merchant
				</p>
			</td>
		</tr>
		<tr>
			<td>
				Stabilimento
			</td>
			<td>
				<input type="text" name="stabilimento" value="' . get_option('pagonline_stabilimento') . '">
				<p class="description">
					Codice identificativo del punto vendita
				</p>
			</td>
		</tr>
		<tr>
			<td>
				User ID
			</td>
			<td>
				<input type="text" name="userID" value="' . get_option('pagonline_userID') . '">
				<p class="description">
					Nome utente per l\'accesso al sistema di pagamento
				</p>
			</td>
		</tr>
		<tr>
			<td>
				Password
			</td>
			<td>
				<input type="text" name="password" value="' . get_option('pagonline_password') . '">
				<p class="description">
					Password per l\'accesso al sistema di pagamento
				</p>
			</td>
		</tr>
		<tr>
			<td>
				Deposito
			</td>
			<td>
				<input type="checkbox" name="flagDeposito" ' . (get_option('pagonline_flagDeposito') === 'Y' ? 'checked' : '') . '>
				<p class="description">
					Modalità di deposito automatico o manuale
				</p>
			</td>
		</tr>
		<tr>
			<td>
				Url Ok
			</td>
			<td>
				<input type="text" name="urlOk" value="' . get_option('pagonline_urlOk') . '">
				<p class="description">
					Indirizzo dell\'esercente a cui verrà indirizzato il compratore in caso di transazione con esito positivo
				</p>
			</td>
		</tr>
		<tr>
			<td>
				Url Ko
			</td>
			<td>
				<input type="text" name="urlKo" value="' . get_option('pagonline_urlKo') . '">
				<p class="description">
					Indirizzo dell\'esercente a cui verrà indirizzato il compratore in caso di transazione con esito negativo
				</p>
			</td>
		</tr>
		<tr>
			<td>
				Tipo Risposta
			</td>
			<td>
				<select name="tipoRispostaApv">
					<option value="click" ' . (get_option('pagonline_tipoRispostaApv') === 'click' ? 'selected' : '') . '>
						Click
					</option>
					<option value="wait" ' . (get_option('pagonline_tipoRispostaApv') === 'wait' ? 'selected' : '') . '>
						Wait
					</option>
				</select>
				<p class="description">
					Modalità manuale o automatica per indirizzare il cliente da PagOnline verso il sito dell\'esercente
				</p>
			</td>
		</tr>
		<tr>
			<td>
				Ricicla Ordine
			</td>
			<td>
				<input type="checkbox" name="flagRiciclaOrdine" ' . (get_option('pagonline_flagRiciclaOrdine') === 'Y' ? 'checked' : '') . '>
				<p class="description">
					Indica se si intende riutilizzare un identificativo ordine che fa riferimento ad un precedente ordine abbandonato
				</p>
			</td>
		</tr>
		<tr>
			<td>
				Stringa segreta
			</td>
			<td>
				<input type="text" name="stringaSegreta" value="' . get_option('pagonline_stringaSegreta') . '">
			</td>
		</tr>
	';

	return $output;
}

function submit_pagonline()
{
	if (isset($_POST['numeroCommerciante'])) 
	{
		update_option('pagonline_numeroCommerciante', $_POST['numeroCommerciante']);
	}
	if (isset($_POST['stabilimento'])) 
	{
		update_option('pagonline_stabilimento', $_POST['stabilimento']);
	}
	if (isset($_POST['userID'])) 
	{
		update_option('pagonline_userID', $_POST['userID']);
	}
	if (isset($_POST['password'])) 
	{
		update_option('pagonline_password', $_POST['password']);
	}
	if (isset($_POST['flagDeposito'])) 
	{
		update_option('pagonline_flagDeposito', "Y");
	}
	else
	{
		update_option('pagonline_flagDeposito', "N");
	}
	if (isset($_POST['urlOk'])) 
	{
		update_option('pagonline_urlOk', $_POST['urlOk']);
	}
	if (isset($_POST['urlKo'])) 
	{
		update_option('pagonline_urlKo', $_POST['urlKo']);
	}
	if (isset($_POST['tipoRispostaApv'])) 
	{
		update_option('pagonline_tipoRispostaApv', $_POST['tipoRispostaApv']);
	}
	if (isset($_POST['flagRiciclaOrdine'])) 
	{
		update_option('pagonline_flagRiciclaOrdine', "Y");
	}
	else
	{
		update_option('pagonline_flagRiciclaOrdine', "N");
	}
	if (isset($_POST['stringaSegreta'])) 
	{
		update_option('pagonline_stringaSegreta', $_POST['stringaSegreta']);
	}

	return true;
}

?>