<?php
/**
 * Weeb
 */
class Bot extends CI_Controller
{
    /**
     * summary
     */
    public function __construct()
    {
        parent::__construct();
    }




    public function response(){
    	 $acces_token = "EAACJE1W6IkoBAC57pd7wQrdTSR3nG88eoMpAI85bphGNbtcqm5aFg2GkghD7jZBcTjXQZAxc7Ran6coRKeSx4RmDaw6BeEwXqv6cgZADEH9ZBYf8O3opNQ8QCLd2sz7sZCpGnqMvUvovqZBDw9KYi6IOZC76wtU1hecxdKkWubNGgZDZD";
    	 $url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$acces_token.'';

    	$token_acceso = 'smartcdmx';
    	if ($this->input->  get('hub_challenge')){
    		$challenge = $this->input->get('hub_challenge');
    		$hub_verify_token = $this->input->get('hub_verify_token');
    		
    	}

    	if ($hub_verify_token === $token_acceso) {
    		echo $challenge;
    	}else{
    		echo "No puedes acceder debido al token";
		}
    	$input = json_decode(file_get_contents('php://input'),true);
    	$sender = $sender = $input['entry'][0]['messaging'][0]['sender']['id'];

    	$nombre_usr = $this->obtener_perfil($sender,$acces_token);

    	$contenido = $this->evaluar_evento($sender,$input,$nombre_usr);
    	$this->mandar_respuesta($contenido, $url);


    }

    function evaluar_evento($sender,$input, $nombre_usr){
      foreach ($input['entry'][0]['messaging'] as $key => $value) {
        //print("<pre>".print_r($value,true)."</pre>");
        end($value);
        $llave = key($value);

        switch ($llave) {
          case 'message':
          $texto_mensaje = $value[$llave]['text'];
          $cadena = str_replace(" ","%20",$texto_mensaje);
          //$llamada = $this->conectar_Witai($cadena);
          $res_f = $this->respuesta_mensaje($sender, $cadena);
          return $res_f;
            break;

          case 'postback':
          $evento = $value[$llave]['payload'];
          if ($evento === 'comienzo') {
          	$respuesta = $this->respuesta_postback($sender,$nombre_usr);
          	return $respuesta;
          }
          
            break;
            
          default:
            break;
        }
      }
    }

    public function respuesta_postback($sender,$nombre_usr){
        $mensajeData = '{
                          "recipient":{
                            "id":"'.$sender.'"
                          },
                          "message":{
                            "text": "¡Hola '.$nombre_usr.'!\n\nAquí encontraras información relacionada al pronóstico de la calidad del aire y esperamos poderte brindar la mejor ayuda que necesites, no dudes en preguntarme. \n\nEspero que tengas una de las mejores experiencias :)",
    
                          }
                        }';
      return $mensajeData;
  	}

   public function respuesta_mensaje ($sender,$texto_mensaje){
    $mensajeData = '{
                          "recipient":{
                            "id":"'.$sender.'"
                          },
                          "message":{
                            "text": "Tu preguntaste: '.$texto_mensaje.'"
    
                          }
                        }';
      return $mensajeData;
   }

   public function conectar_Witai($mensaje){
      
      $token = 'GBK4AOOB2NXGLMJQM3QOU2BFRQZPTQZD';

      $curl = curl_init();

      curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.wit.ai/message?v=28/02/2018&q='.$mensaje.'',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_HTTPHEADER => array("Authorization: Bearer ".$token)
      ));

      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);

      //echo $response;

      return $response;

      
    }

    public function mandar_respuesta($mensajeData,$url){
      $curl = curl_init();

      curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $mensajeData,
          CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
      ));

      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);
  }

  public function obtener_perfil ($sender,$access_token){
    $curl = curl_init();

      curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://graph.facebook.com/v2.6/'.$sender.'?fields=first_name,last_name,profile_pic&access_token='.$access_token,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array("cache-control: no-cache"),
      ));

      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);

      $json = json_decode($response,true);

      $nombre = $json['first_name'];
    
    return $nombre;
  }


    
}

  ?>