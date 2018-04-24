<?php
/**
 * Weeb
 */
class Bot extends CI_Controller
{
    /**
     * summary
     */

    private $token_facebook;
    private $token_acceso;
    private $url;
    private $hub_verify_token;

    public function __construct()
    {
        parent::__construct();

        //token de acceso pagina de facebook
        $this->token_facebook = 'EAACx9QdRbA4BAKw8LdhY9GfuYEOTNKAsAUVbRGJr51GVHQXvxeCdew1HNcTbUJPAUkVMQhRmc7Eg7LizoSV7OnU5t7yWJhQFtN639srvHdgUpZCqicr12hb3UUfy27XnjqsFZAxMJDnrpdLS1xb4ZCj7aXFDxawfE1FnoCEAdd9mjkrakB1';//'EAACJE1W6IkoBAC57pd7wQrdTSR3nG88eoMpAI85bphGNbtcqm5aFg2GkghD7jZBcTjXQZAxc7Ran6coRKeSx4RmDaw6BeEwXqv6cgZADEH9ZBYf8O3opNQ8QCLd2sz7sZCpGnqMvUvovqZBDw9KYi6IOZC76wtU1hecxdKkWubNGgZDZD';
        //token de acceso privado
        $this->token_acceso = 'smartcdmx';
        //url API_Facebook
        $this->url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$this->token_facebook.'';

    }

    public function response(){

      if ($this->input->get('hub_challenge')){
        $challenge = $this->input->get('hub_challenge');
        $this->hub_verify_token = $this->input->get('hub_verify_token');
      }

      if ($this->hub_verify_token === $this->token_acceso) {
        echo $challenge;
      }else{
        echo "No puedes acceder debido al token";
      }

       $input = json_decode(file_get_contents('php://input'),true);
      $sender = $sender = $input['entry'][0]['messaging'][0]['sender']['id'];

      //echo $sender;

      if (is_array($input)){
        foreach ($input['entry'][0]['messaging'] as $key => $value) {
          end($value);
          $llave = key($value);

          switch ($llave) {
            case 'message':
              $texto_mensaje = $value[$llave]['text'];
              $cadena = str_replace(" ","%20",$texto_mensaje);
              echo $cadena;
              $respuesta_wit = $this->conectar_Witai($cadena);
              $evaluar_respuesta_wit = $this->evaluar_respuesta_wit($sender, $respuesta_wit);

              $prueba = '{
                          "recipient":{
                            "id":"'.$sender.'"
                          },
                          "sender_action":"typing_on"
                        }';
                        $this->mostrar_accion($prueba, $this->url);

              $responder = $this->mandar_respuesta($evaluar_respuesta_wit, $this->url);
              
              break;

            case 'postback':
                $mensajeData = '{    
                          "messasing_type": "RESPONSE",
                          "recipient":{
                            "id":"'.$sender.'"
                          },
                          "message":{
                            "text": "Hola, en que te puedo ayudar? :)",
    
                          }
                        }';

                $this->mandar_respuesta($mensajeData, $this->url);
              break;
            
            default:
              // code...
              break;
          }  
        }
      }

    }

  public function conectar_Witai($mensaje){
      
      $token = '72QZOIAHSFMCQ4MKPULORJIP6HSLAO6N';

      $curl = curl_init();

      curl_setopt_array($curl, array(
          CURLOPT_URL =>  'https://api.wit.ai/message?v=12/03/2018&q='.$mensaje.'',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 10,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_HTTPHEADER => array("Authorization: Bearer ".$token)
      ));

      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);

      return $response;
    }


  public function evaluar_respuesta_wit($sender,$respuesta){

      $mensajeData = json_decode($respuesta,true);

      if (is_array($mensajeData)){

        if (array_key_exists('intent', $mensajeData['entities'])) {
          $intencion = $mensajeData['entities']['intent'][0]['value'];

          switch ($intencion) {
          case 'I_Identidad':
            //print("<pre>".print_r($mensajeData,true)."</pre>");

            break;

          case 'I_CalidadAire':

            //print("<pre>".print_r($mensajeData,true)."</pre>");
            if (array_key_exists('ubicaciones', $mensajeData['entities'])) {
              $localidad = $mensajeData['entities']['ubicaciones'][0]['value'];
              $localidad = strtoupper($localidad);
              $conexion_api = $this->api_calidad_aire($localidad);
              $armar_respuesta = $this->respuesta_mensaje($sender,$conexion_api);
              return $armar_respuesta;

              //print_r($conexion_api);
            }else{
              $localidad = 'CDMX';
              $conexion_api = $this->api_calidad_aire($localidad);
              $armar_respuesta = $this->respuesta_mensaje($sender,$conexion_api);
              return $armar_respuesta;
              //print_r($conexion_api);
            }
            break;
          
          
          default:
            // code...
            break;
          }
            //return $intencion;
            //$sacar_localidad = $this->evaluar_intenciones($intencion, $mensajeData);
            //return $sacar_localidad;
        }else{
          $cadena = "“Lo siento”, pero no puedo entender que me quieres decir :(";
          $armar_respuesta = $this->respuesta_mensaje($sender,$cadena);
          return $armar_respuesta;

        }
    }

  }  

  function respuesta_mensaje($sender, $calidad){
     $mensaje_texto = '{    
                          "messasing_type": "RESPONSE",
                          "recipient":{
                            "id":"'.$sender.'"
                          },
                          "message":{
                            "text": "'.$calidad.'",
    
                          }
                        }';
      return $mensaje_texto;
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

  public function mostrar_accion($accion,$url){

      $curl = curl_init();

      curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $accion,
          CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
      ));

      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);
  } 

   

  

  public function api_calidad_aire($localidad){
      //echo $localidad;
      $json = '{
                "pollutionMeasurements":{
                    "city": "Ciudad de M\u00e9xico",
                    "cityCode": "MEX",
                    "country": "M\u00e9xico",
                    "mesurementAgency": "SIMAT",
                    "URL": "http://www.aire.cdmx.gob.mx/",
                    "timeStamp": "2017-11-22",
                "report":"09",
                "delegations": [

                  {
                    "name": "CDMX",
                    "shortName": "CM",
                    "imecaPoints": "45",
                    "pollutant": "O\u2083",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",
                    "color": "#97CA03"

                  },
                  {
                    "name": "ALVARO OBREGON",
                    "shortName": "ALO",
                    "imecaPoints": "21",
                    "pollutant": "O\u2083",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",
                    "color": "#97CA03"

                  },{
                    "name": "AZCAPOTZALCO",
                    "shortName": "AZC",
                    "imecaPoints": "15",
                    "pollutant": "NO\u2082",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"

                  },{
                    "name": "BENITO JUAREZ",
                    "shortName": "BEJ",
                    "imecaPoints": "74",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "COYOACAN",
                    "shortName": "COY",
                    "imecaPoints": "14",
                    "pollutant": "O\u2083",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"

                  },{
                    "name": "CUAJIMALPA DE MORELOS",
                    "shortName": "CUA",
                    "imecaPoints": "51",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "CUAUHTEMOC",
                    "shortName": "CUH",
                    "imecaPoints": "77",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"


            },{
                    "name": "GUSTAVO A MADERO",
                    "shortName": "GAM",
                    "imecaPoints": "24",
                    "pollutant": "O\u2083",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"
                  },{
                    "name": "IZTACALCO",
                    "shortName": "IZT",
                    "imecaPoints": "90",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "IZTAPALAPA",
                    "shortName": "IZP",
                    "imecaPoints": "-99",
                    "pollutant": "",
                    "indice": "SIN COBERTURA",
                    "riesgo": "",
                    "recomendacion": "",
                    "recomendacionaireuno":"",
                    "recomendacionairedos":"",
                    "recomendacionairetres":"",

                    "color": "#CCCCCC"

                  },{
                    "name": "LA MAGDALENA CONTRERAS",
                    "shortName": "MAC",
                    "imecaPoints": "21",
                    "pollutant": "O\u2083",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"

                  },{
                    "name": "MIGUEL HIDALGO",
                    "shortName": "MIH",
                    "imecaPoints": "64",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "MILPA ALTA",
                    "shortName": "MIA",
                    "imecaPoints": "-99",
                    "pollutant": "",
                    "indice": "SIN COBERTURA",
                    "riesgo": "",
                    "recomendacion": "",
                    "recomendacionaireuno":"",
                    "recomendacionairedos":"",
                    "recomendacionairetres":"",

                    "color": "#CCCCCC"

                  },{
                    "name": "TLAHUAC",
                    "shortName": "TAH",
                    "imecaPoints": "77",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

            },{
                    "name": "TLALPAN",
                    "shortName": "TPN",
                    "imecaPoints": "55",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "VENUSTIANO CARRANZA",
                    "shortName": "VEC",
                    "imecaPoints": "101",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

                    "color": "#FF9900"

                  },{
                    "name": "XOCHIMILCO",
                    "shortName": "XOC",
                    "imecaPoints": "14",
                    "pollutant": "O\u2083",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"

                  },{
                    "name": "CHALCO",
                    "shortName": "CHO",
                    "imecaPoints": "102",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

                    "color": "#FF9900"

                  },{
                    "name": "VALLE DE CHALCO",
                    "shortName": "VAC",
                    "imecaPoints": "102",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

                    "color": "#FF9900"

                  },{
                    "name": "IXTAPALUCA",
                    "shortName": "IXT",
                    "imecaPoints": "102",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

            "color": "#FF9900"
                  },{
                    "name": "LA PAZ",
                    "shortName": "LAP",
                    "imecaPoints": "13",
                    "pollutant": "NO\u2082",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"

                  },{
                    "name": "NEZAHUALCOYOTL",
                    "shortName": "NEZ",
                    "imecaPoints": "13",
                    "pollutant": "NO\u2082",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"
                  },{
                    "name": "CHIMALHUACAN",
                    "shortName": "CHI",
                    "imecaPoints": "13",
                    "pollutant": "NO\u2082",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"

                  },{
                    "name": "CHICOLOAPAN",
                    "shortName": "CHC",
                    "imecaPoints": "-99",
                    "pollutant": "",
                    "indice": "SIN COBERTURA",
                    "riesgo": "",
                    "recomendacion": "",
                    "recomendacionaireuno":"",
                    "recomendacionairedos":"",
                    "recomendacionairetres":"",

                    "color": "#CCCCCC"
                  },{
                    "name": "ATENCO",
                    "shortName": "ATE",
                    "imecaPoints": "105",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

                    "color": "#FF9900"

                  },{
                    "name": "ACOLMAN",
                    "shortName": "ACO",
                    "imecaPoints": "-99",
                    "pollutant": "",
                    "indice": "SIN COBERTURA",
                    "riesgo": "",
                    "recomendacion": "",
                    "recomendacionaireuno":"",
                    "recomendacionairedos":"",
                    "recomendacionairetres":"",

                    "color": "#CCCCCC"
                  },{
                    "name": "ECATEPEC",
                    "shortName": "ECA",
                    "imecaPoints": "107",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

                    "color": "#FF9900"

                  },{
                    "name": "TECAMAC",
                    "shortName": "TEC",
                    "imecaPoints": "-99",
                    "pollutant": "",
                    "indice": "SIN COBERTURA",
                    "riesgo": "",
                    "recomendacion": "",
                    "recomendacionaireuno":"",
                    "recomendacionairedos":"",
                    "recomendacionairetres":"",

            "color": "#CCCCCC"
                  },{
                    "name": "COACALCO",
                    "shortName": "COA",
                    "imecaPoints": "105",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

                    "color": "#FF9900"

                  },{
                    "name": "TULTITLAN ANEXO",
                    "shortName": "TU1",
                    "imecaPoints": "105",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

                    "color": "#FF9900"
                  },{
                    "name": "TULTITLAN",
                    "shortName": "T21",
                    "imecaPoints": "99",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "JALTENCO",
                    "shortName": "JAL",
                    "imecaPoints": "105",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

                    "color": "#FF9900"

                  },{
                    "name": "TONANITLA",
                    "shortName": "TON",
                    "imecaPoints": "105",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

                    "color": "#FF9900"

                  },{
                    "name": "NEXTLALPAN",
                    "shortName": "NEX",
                    "imecaPoints": "105",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Limita las actividades al aire libre",
                    "recomendacionairedos":"Limita el tiempo para ejercitarte al aire libre",
                    "recomendacionairetres":"Grupos sensibles permanecer en interiores",

            "color": "#FF9900"
                  },{
                    "name": "TEOLOYUCAN",
                    "shortName": "TEO",
                    "imecaPoints": "88",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "MELCHOR OCAMPO",
                    "shortName": "MEL",
                    "imecaPoints": "88",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "CUAUTITLAN",
                    "shortName": "CUU",
                    "imecaPoints": "88",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"
                  },{
                    "name": "CUAUTITLAN IZCALLI",
                    "shortName": "CUI",
                    "imecaPoints": "88",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "TULTEPEC",
                    "shortName": "TUL",
                    "imecaPoints": "105",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "MALA",
                    "riesgo": "Da\u00f1ina a la salud en grupos sensibles",
                    "recomendacion": "Ni\u00f1os, adultos mayores, quienes realicen actividad f\u00edsica intensa o con enfermedades respiratorias y cardiovasculares: limitar esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"",
                    "recomendacionairedos":"",
                    "recomendacionairetres":"",

                    "color": "#FF9900"
                  },{
                    "name": "TEPOTZOTLAN",
                    "shortName": "TEP",
                    "imecaPoints": "88",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

            },{
                    "name": "TLALNEPANTLA",
                    "shortName": "TL1",
                    "imecaPoints": "71",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "REGULAR",
                    "riesgo": "Aceptable",
                    "recomendacion": "Personas extraordinariamente sensibles a la contaminaci\u00f3n: consideren limitar los esfuerzos prolongados al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":" Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Personas extremadamente sensibles limitar actividades al aire libre",

                    "color": "#FFFF00"

                  },{
                    "name": "TLALNEPANTLA ANEXO",
                    "shortName": "TL2",
                    "imecaPoints": "19",
                    "pollutant": "O\u2083",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"

                  },{
                    "name": "ATIZAPAN DE ZARAGOZA",
                    "shortName": "ATI",
                    "imecaPoints": "19",
                    "pollutant": "O\u2083",
                    "indice": "BUENA",
                    "riesgo": "Sin riesgo",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",

                    "color": "#97CA03"

                  },{
                    "name": "NAUCALPAN",
                    "shortName": "NAU",
                    "imecaPoints": "-99",
                    "pollutant": "",
                    "indice": "SIN COBERTURA",
                    "riesgo": "",
                    "recomendacion": "",
                    "recomendacionaireuno":"",
                    "recomendacionairedos":"",
                    "recomendacionairetres":"",

                    "color": "#CCCCCC"
                  },{
                    "name": "HUIXQUILUCAN",
                    "shortName": "HUI",
                    "imecaPoints": "43",
                    "pollutant": "PM\u2081\u2080",
                    "indice": "BUENA",
                    "riesgo": "",
                    "recomendacion": "Se puede realizar cualquier actividad al aire libre.",
                    "recomendacionaireuno":"Puedes realizar actividades al aire libre",
                    "recomendacionairedos":"Puedes ejercitarte al aire libre",
                    "recomendacionairetres":"Sin riesgo para grupos sensibles",
                    "color": "#97CA03"

            },{
                    "name": "TEXCOCO",
                    "shortName": "TEX",
                    "imecaPoints": "-99",
                    "pollutant": "",
                    "indice": "SIN COBERTURA",
                    "riesgo": "",
                    "recomendacion": "",
                    "recomendacionaireuno":"",
                    "recomendacionairedos":"",
                    "recomendacionairetres":"",
                    "color": "#CCCCCC"
                  },{
                    "name": "OCOYOACAC",
                    "shortName": "OCO",
                    "imecaPoints": "-99",
                    "pollutant": "",
                    "indice": "SIN COBERTURA",
                    "riesgo": "",
                    "recomendacion": "",
                    "recomendacionaireuno":"",
                    "recomendacionairedos":"",
                    "recomendacionairetres":"",
                    "color": "#CCCCCC"
                  }      
                ]     
              }
            }';

             $array = json_decode($json,true);
    
        foreach ($array['pollutionMeasurements']['delegations'] as $key => $value) {
          //print("<pre>".print_r($value,true)."</pre>");
          if (in_array($localidad, $value)) {

            $mensaje_respuesta = "Puntos imecas: ".$value['imecaPoints']. "Calidad del aire: ".$value['indice'].' Riesgo: '.$value['riesgo']. " :)";
            return $mensaje_respuesta;
          }
        }
    } 

}

  ?>