<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client; 
use Ixudra\Curl\Facades\Curl;
use Helper;
use Session;

class LoginController extends Controller
{
	
	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {		
		return view('login.login');		
    }
   /**
     * Landing a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexAction(Request $request)
    {		
		return view('master.index');		
    }
    
  /**
     * Store a newly created resource in storage.
     * @Post Method for Login
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email',
				 'password' => 'required|min:6'
            ], 
            [
                'email.required' => 'Email Field is required',
                'password.required' => 'Password is required min 6 charecter'
            ]
          );
		  
		  
		    $baseUrl = Helper::BaseUrl();		   
			$loginURL = $baseUrl."/login";
			
			try {
			
				$username	= $request->input('email');
				$password 	= $request->input('password');
				
				$loginHeaders = array(
						"accept: application/json",
						"authorization: Basic ".base64_encode($username.":".$password),
						"cache-control: no-cache",
						"content-type: application/json",
					);
				$postLoginMethod = 'POST';
				
				$response = Helper::AuthLogin($loginURL,$loginHeaders,$postLoginMethod);
				
				$result = json_decode($response);
				//dd($result);
				if(empty($result))
				{
					return redirect()->route('login')->with('error','Something went wrong on the server.');
				}else{
						if($result->status->response==200)
						{
							
							/*Final URL*/
							/*$url = $baseUrl."/".$result->data->org_uuid."/account?uuid=".$result->data->account_uuid;
							$method ='GET';				 
							$headers = array(
								"Content-Type: application/json",
								"authorization: Basic ".base64_encode($result->data->login_token.":".$result->data->license_token),
								"cache-control: no-cache",
								"x-access-token: ".$result->data->login_token,
								"license-token: ".$result->data->license_token,
							);	
									
							$requestData = [
									'org_uuid'	 => $result->data->org_uuid
									
							];
							
							$response1 = Helper::xaqsisHgttpCurl($url,$headers,$method,$requestData);			
							$result1 = json_decode($response1);	*/						
							 
							$request->session()->put('username', $result->data->first_name);
							$request->session()->put('account_name', $result->data->account_name);
							$request->session()->put('account_uuid', $result->data->account_uuid);
							$request->session()->put('org_uuid', $result->data->org_uuid);
							$request->session()->put('org_name', $result->data->org_name);
							$request->session()->put('access_token', $result->data->login_token);
							$request->session()->put('license_token', $result->data->license_token);
							$request->session()->put('success', $result->status->message);
							$request->session()->put('igLoggedIn', true);
							//$request->session()->put('expiration', $result->status->expiration);
							//print_r($result); die();
							
							/* Start API Method for Audit account */
							
							$auditUrl = $baseUrl."/".$result->data->org_uuid."/audit?account_uuid=".$result->data->account_uuid;
							
							//echo  "<pre>"; 
							$auditMethod = "POST";
							$auditHeaders = array(
								"Content-Type: application/json",
								"authorization: Basic ".base64_encode($result->data->login_token.":".$result->data->license_token),
								"cache-control: no-cache",
								"x-access-token: ".$result->data->login_token,
								"license-token: ".$result->data->license_token,
							);	
							
							
							// prints the current time in date format 
							//echo date("Y-m-d H:m:s", strtotime("now"))."\n";
							$auditTime = strtotime("now");
							//echo date("Y-m-d H:m:s", $auditTime)."\n";
							 
							$sourceMetaData = array('useragent'=>$request->server('HTTP_USER_AGENT'),'ip'=>$request->ip());
							//echo json_encode($sourceMetaData);die();
							
							$auditPostData = array(
													"account_uuid" 	=> $result->data->account_uuid,
													"login"			=> $auditTime,
													"logout"		=> 0,
													"source_metadata" => $sourceMetaData,
													"modified_by"   => $result->data->account_uuid
												);
							//echo "<pre>";			
							//print_r(json_encode($auditPostData));
							$auditResponse = Helper::xaqsisHgttpCurl($auditUrl,$auditHeaders,$auditMethod,$auditPostData);			
							$auditResult = json_decode($auditResponse);
							//dd($auditResult);
							
							if($auditResult->status->response==200)
							{
								//echo $auditResult->data->login;
								$request->session()->put('logintime', $auditResult->data->login);
							} 
							//dd($auditResult);
							/* End API code for aduit method account */
							return redirect()->route('dashboard')->with('success',$result->status->message);
						}else{
							/*Error Message Showing*/
							$code 		= $result->status->response;
							$message	=  $result->status->message;					 
							return redirect()->route('login')->with('error',$message);	
						}	
					}
				
				
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                       
			return redirect()->route('login')->with('error','Something went wrong on the server.');
        }	
    }
	 /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function register()
    {	
       return view('register.register'); 	   
    }
	
     /**
     * Store a newly created resource in storage.
     * @Post Method for Create Account\Register
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createRegister(Request $request)
    {
        $request->validate(
            [
				'firstname'=>'required',
				'lastname'=>'required',
                'email' => 'required|email',
				'password' => 'required|min:6'
            ], 
            [
				'firstname.required' => 'First name Field is required',
				'lastname.required' => 'Last name Field is required',
                'email.required' => 'Email Field is required',
                'password.required' => 'Password is required min 6 charecter'
            ]
          );
		
		try {
			
			
			
			$client = new \GuzzleHttp\Client();
			$baseUrl = Helper::BaseUrl();
			$method ='POST';
			$headers = array(
				'Content-Type: application/json'
			);		

			$orgurl = $baseUrl."/org";			 
			 
			$orgData['name'] 		= $request->input('firstname');			
			$orgData['email'] 		= $request->input('email');
			$orgData['is_system'] 	= True;
			$orgData['is_active'] 	= True;
			
			$orgResponse = Helper::xaqsisHgttpCurl($orgurl,$headers,$method,$orgData);			
			$orgResult = json_decode($orgResponse);
			
			 
			if($orgResult->status->response==404)
			{
				 $message = $orgResult->status->message; 
				return redirect()->route('register')->with('error',$message);
				
			}
			else if($orgResult->status->response==500)
			{
				 $message = $orgResult->status->message;  
				return redirect()->route('register')->with('error',$message);
				
			}else{
				 
				 
				$org_uuid = $orgResult->data->uuid;
				$url = $baseUrl."/".$org_uuid."/account";
				
				$postData['org_uuid'] 	= $org_uuid;
				$postData['password'] 	= $request->input('password');
				$postData['first_name'] = $request->input('firstname');
				$postData['last_name'] 	= $request->input('lastname');
				$postData['email'] 		= $request->input('email');	
				$postData['modified_by'] = $org_uuid;
				
						 
				//$params = json_encode($postData);
				$response = Helper::xaqsisHgttpCurl($url,$headers,$method,$postData);			
				$result = json_decode($response);
				
				if($result->status->response==500)
				{
					$message = $result->status->message;
					return redirect()->route('register')->with('error',$message);
				}else{
					//dd($result);
					$message = $result->status->message; 
					//die();
					return redirect()->route('login')->with('success',$message);
				}
			}	
		

		} catch (\Exception $e) {

			//return $e->getMessage();
			return back()->withError($e->getMessage())->withInput();
		}
    }
	/**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function forgotPassword(Request $request)
    {
        return view('password.recovery-password');
    }
	
	/**
     * Reset password a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function resetPassword(Request $request)
    { 
			$baseUrl = Helper::BaseUrl();
			$method ='POST';
			$org_uuid = "326bc8a0-da83-45d5-b588-821ac9cadbe9";
			$url = $baseUrl."/resetaccountpassword";
			$headers = array(
				'Content-Type: application/json'
			);		
			$requestData['email']= $request->input('email');
			 
			$response = Helper::xaqsisHgttpCurl($url,$headers,$method,$requestData);			
			$result   = json_decode($response);		 
			 //dd($result);
			
			if($result->status->response==500)
			{
				$message = $result->status->message;
				return redirect()->route('forgotpassword')->with('error1',$message);
			}else{
					$message = $result->status->message;
				 	return redirect()->route('forgotpassword')->with('success1',$message);
				}
				 
	}
			
   
	/**
     * Reset password a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request)
    {	
			$baseUrl = Helper::BaseUrl();
			$method ='PUT';
			$org_uuid = "326bc8a0-da83-45d5-b588-821ac9cadbe9";
			$url = $baseUrl."/resetaccountpassword";
			
			$headers = array(
				'Content-Type: application/json'
			);		
			/*
			$requestData['email']			= $request->input('username');
			$requestData['otp']				= $request->input('otp');
			$requestData['new_password']	= $request->input('password');
			*/
			$otp = $request->input('otp');
			$requestData = [
						"email"	 		=> $request->input('username'),
						"otp" 			=> (int)$otp,
						"new_password"	=> $request->input('password')
									
			];
			 
			 
			$response 	= Helper::xaqsisHgttpCurl($url,$headers,$method,$requestData);			
			$result 	= json_decode($response);	
				
			
			 
			if($result->status->response==500)
			{
				//dd($result);
				$message = $result->status->message;
				return redirect()->route('forgotpassword')->with('error',$message);
			}else if($result->status->response==404){
				//dd($result);
				$message = $result->status->message;
				return redirect()->route('forgotpassword')->with('error',$message);
			}
			else{
				//dd($result);
				$message = $result->status->message;				 
				return redirect()->route('forgotpassword')->with('success',$message); 
			}
    }
	
	/**
     * Create Account invite of the resource.
     *
     * @return \Illuminate\Http\Response
     */
	public function createAccountByInvitation(Request $request)
	{
		$org_uuid = $request->get('org_uuid'); 
		$name =  $request->get('name'); 
		$lname =  $request->get('lname'); 
		$email =  $request->get('email'); 
		return view('register.inviteuser', compact('org_uuid','name','email','lname'));
	}
	/**
     * Create Account invite of the resource.
     *
     * @return \Illuminate\Http\Response
     */
	public function createAccountInviteAction(Request $request)
	{
		$org_uuid 	= $request->input('org_uuid');
		$url 		= $baseUrl."/".$org_uuid."/account";

		$postData['org_uuid'] 	= $org_uuid;
		$postData['password'] 	= $request->input('password');
		$postData['first_name'] = $request->input('firstname');
		$postData['last_name'] 	= $request->input('lastname');
		$postData['email'] 		= $request->input('email');	
		$postData['modified_by'] = $org_uuid;

			 
		//$params = json_encode($postData);
		$response = Helper::xaqsisHgttpCurl($url,$headers,$method,$postData);			
		$result = json_decode($response);

		if($result->status->response==500)
		{
			$message = $result->status->message;
			return redirect()->route('register')->with('error',$message);
		}else{
		//dd($result);
			$message = $result->status->message; 
			//die();
			return redirect()->route('login')->with('success',$message);
		} 
	}
}