<?php
	namespace App\Helper;

	/*
	*
	API NAME: Masspay.php
	DESCRIPTION: This file uses the constants.php to get parameters needed to make an API call and calls the server.if you want use your own credentials, you have to change the constants.php
	*
	*/
	
	class Masspay {
		public function __construct(){
			$this->mode 			= \Config::get("paypal.mode");
			$this->API_UserName 	= \Config::get("paypal.{$this->mode}.username");
			$this->API_Password 	= \Config::get("paypal.{$this->mode}.password");
			$this->API_Signature 	= \Config::get("paypal.{$this->mode}.secret");
			$this->API_Endpoint  	= \Config::get("paypal.{$this->mode}.endpoint");
			$this->version 			= '65.1';
			
			$this->AUTH_token 		= \Config::get("paypal.{$this->mode}.auth_token");
			$this->AUTH_signature 	= \Config::get("paypal.{$this->mode}.auth_signature");
			$this->AUTH_timestamp 	= \Config::get("paypal.{$this->mode}.auth_timestamp");

		}	

		public function nvpHeader($subject = NULL){
			$nvpHeaderStr = "";

			if(defined('AUTH_MODE')) {
				//$AuthMode = "3TOKEN"; //Merchant's API 3-TOKEN Credential is required to make API Call.
				//$AuthMode = "FIRSTPARTY"; //Only merchant Email is required to make EC Calls.
				//$AuthMode = "THIRDPARTY";Partner's API Credential and Merchant Email as Subject are required.
				$AuthMode = "AUTH_MODE"; 
			}else{	
				if((!empty($this->API_UserName)) && (!empty($this->API_Password)) && (!empty($this->API_Signature)) && (!empty($subject))) {
					$AuthMode = "THIRDPARTY";
				}else if((!empty($this->API_UserName)) && (!empty($this->API_Password)) && (!empty($this->API_Signature))) {
					$AuthMode = "3TOKEN";
				}elseif (!empty($this->AUTH_token) && !empty($this->AUTH_signature) && !empty($this->AUTH_timestamp)) {
					$AuthMode = "PERMISSION";
				}
			    elseif(!empty($subject)) {
					$AuthMode = "FIRSTPARTY";
				}
			}

			switch($AuthMode) {
				
				case "3TOKEN" : 
						$nvpHeaderStr = "&PWD=".urlencode($this->API_Password)."&USER=".urlencode($this->API_UserName)."&SIGNATURE=".urlencode($this->API_Signature);
						break;
				case "FIRSTPARTY" :
						$nvpHeaderStr = "&SUBJECT=".urlencode($subject);
						break;
				case "THIRDPARTY" :
						$nvpHeaderStr = "&PWD=".urlencode($this->API_Password)."&USER=".urlencode($this->API_UserName)."&SIGNATURE=".urlencode($this->API_Signature)."&SUBJECT=".urlencode($subject);
						break;		
				case "PERMISSION" :
					    $nvpHeaderStr = $this->formAutorization($this->AUTH_token,$this->AUTH_signature,$this->AUTH_timestamp);
					    break;
			}
			return $nvpHeaderStr;
		}

		/**
		  * hash_call: Function to perform the API call to PayPal using API signature
		  * @methodName is name of API  method.
		  * @nvpStr is nvp string.
		  * returns an associtive array containing the response from the server.
		*/


		public static function hash_call($methodName,$nvpStr,$subject){
			/*DECLARING OF GLOBAL VARIABLES*/
			$_this = new static();
			
			/*FORM HEADER STRING*/
			$nvpheader = $_this->nvpHeader();
			
			/*SETTING THE CURL PARAMETERS.*/
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$_this->API_Endpoint);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);

			/*TURNING OFF THE SERVER AND PEER VERIFICATION(TRUSTMANAGER CONCEPT).*/
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST, 1);
			
			/*IN CASE OF PERMISSION APIS SEND HEADERS AS HTTPHEDERS*/
			if(!empty($_this->AUTH_token) && !empty($_this->AUTH_signature) && !empty($_this->AUTH_timestamp)){
				$headers_array[] = "X-PP-AUTHORIZATION: ".$nvpheader;
		  
		    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_array);
		    	curl_setopt($ch, CURLOPT_HEADER, false);
			}else {
				$nvpStr=$nvpheader.$nvpStr;
			}
		    
		    /*IF USE_PROXY CONSTANT SET TO TRUE IN CONSTANTS.PHP, THEN ONLY PROXY WILL BE ENABLED.*/
		   	/*SET PROXY NAME TO PROXY_HOST AND PORT NUMBER TO PROXY_PORT IN CONSTANTS.PHP */
			if(USE_PROXY){
				curl_setopt ($ch, CURLOPT_PROXY, PROXY_HOST.":".PROXY_PORT); 
			}

			/*CHECK IF VERSION IS INCLUDED IN $NVPSTR ELSE INCLUDE THE VERSION.*/
			if(strlen(str_replace('VERSION=', '', strtoupper($nvpStr))) == strlen($nvpStr)) {
				$nvpStr = "&VERSION=" . urlencode($_this->version) . $nvpStr;	
			}
			
			$nvpreq="METHOD=".urlencode($methodName).$nvpStr;
			
			/*SETTING THE NVPREQ AS POST FIELD TO CURL*/
			curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

			/*GETTING RESPONSE FROM SERVER*/
			$response = curl_exec($ch);

			/*CONVRTING NVPRESPONSE TO AN ASSOCIATIVE ARRAY*/
			$nvpResArray = $_this->deformatNVP($response);
			$nvpReqArray = $_this->deformatNVP($nvpreq);
			$_SESSION['nvpReqArray']=$nvpReqArray;

			if (curl_errno($ch)) {
				/*MOVING TO DISPLAY PAGE TO DISPLAY CURL ERRORS*/
			  	return [
			  		'status' => false,
			  		'data' => [],
			  		'code' => curl_errno($ch),
			  		'message' => curl_error($ch),
			  	];
			} else {
				/*CLOSING THE CURL*/
				curl_close($ch);
				
				if($nvpResArray['ACK'] == "Success"){
					return [
				  		'status' => true,
				  		'data' => $nvpResArray,
				  		'code' => 'M0421',
				  		'message' => trans('general.M0421'),
					];
				}else{
					return [
				  		'status' => false,
				  		'data' => $nvpResArray,
				  		'code' => $nvpResArray['L_ERRORCODE0'],
				  		'message' => $nvpResArray['L_SHORTMESSAGE0'],
					];
				}
		  	}

		}

		/** This function will take NVPString and convert it to an Associative Array and it will decode the response.
		  * It is usefull to search for a particular key and displaying arrays.
		  * @nvpstr is NVPString.
		  * @nvpArray is Associative Array.
		  */

		public function deformatNVP($nvpstr){

			$intial=0;
		 	$nvpArray = array();


			while(strlen($nvpstr)){
				//postion of Key
				$keypos= strpos($nvpstr,'=');
				//position of value
				$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

				/*getting the Key and Value values and storing in a Associative Array*/
				$keyval=substr($nvpstr,$intial,$keypos);
				$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
				//decoding the respose
				$nvpArray[urldecode($keyval)] =urldecode( $valval);
				$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
		     }
			return $nvpArray;
		}

		public function formAutorization($auth_token,$auth_signature,$auth_timestamp){
			$authString="token=".$auth_token.",signature=".$auth_signature.",timestamp=".$auth_timestamp ;
			return $authString;
		}
	}