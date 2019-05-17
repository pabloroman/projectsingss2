<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Requests;
    use Illuminate\Http\Request;
    use Illuminate\Validation\Rule;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Storage;
    use Srmklive\PayPal\Services\AdaptivePayments;
    use Srmklive\PayPal\Services\ExpressCheckout;
    
    use App\Http\Controllers\Controller;
    class Employer extends Controller{
        
        /**
         * Create a new controller instance.
         *
         * @return void
         */
        protected $jwt;
        private $post;
        private $token;
        private $status;
        private $jsondata;
        private $status_code;
        private $prefix;

        public function __construct(Request $request){
            $this->jsondata     = (object)[];
            $this->message      = "M0000";
            $this->error_code   = "no_error_found";
            $this->status       = false;
            $this->status_code  = 200;
            $this->provider     = new ExpressCheckout();
            $this->prefix       = \DB::getTablePrefix();

            $json = json_decode(file_get_contents('php://input'),true);
            if(!empty($json)){
                $this->post = $json;
            }else{
                $this->post = $request->all();
            }

            if(empty($request->currency)){
                $this->post['currency'] = \Cache::get('default_currency');
            }else{
                $this->post['currency'] = $request->currency;
            }
            
            /*RECORDING API REQUEST IN TABLE*/
            \Models\Listings::record_api_request([
                'url' => $request->url(),
                'request' => json_encode($this->post),
                'type' => 'webservice',
                'created' => date('Y-m-d H:i:s')
            ],$request);

            $request->replace($this->post);
        }

        private function populateresponse($data){
            $data['message'] = (!empty($data['message']))?"":$this->message;
            $data['error'] = (empty($this->error))?trans(sprintf("general.%s",$data['message'])):$this->error; 
            $data['error_code'] = "";

            if(empty($data['status'])){
                $data['status'] = $this->status;
                $data['error_code'] = $this->message;
            }

            $data['status_code'] = $this->status_code;
            
            $data = json_decode(json_encode($data),true);

            array_walk_recursive($data, function(&$item,$key){
                if($key === 'default_card_detail'){
                    $item = (object)[];
                } else if (gettype($item) == 'integer' || gettype($item) == 'float' || gettype($item) == 'NULL'){
                    $item = trim($item);
                }
            });

            if(empty($data['data'])){
                $data['data'] = (object) $data['data'];
            }

            if(!empty(request()->user()->id_user)){
                $data['total_unread_notifications']   = (string)\Models\Notifications::unread_notifications(request()->user()->id_user);
                $data['proposal']       = (string)\Models\Notifications::unread_notifications(request()->user()->id_user,'proposals','talent');
            }else{
                $data['total_unread_notifications']   = (string)0;
                $data['proposal']       = (string)0;
            }

            if(strpos($data['message'], 'M') === 0 || strpos($data['message'], 'W') === 0){
                $data['message'] = trans('general.'.$data['message']);
            }

            return $data;
        }

        /**
         * [This method is used for profile view] 
         * @param  Request
         * @return Json Response
         */
        
        public function viewprofile(Request $request){
            $user = \Models\Employers::get_user($request->user());
            
            if(!empty($user)){
                $this->status = true;
            }

            $this->jsondata = [
                'user' => $user
            ];


            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for employer profile setup]
         * @param  Request
         * @return Json Response
         */

        public function step_one(Request $request){
            $validation_mobile = validation('phone_number'); unset($validation_mobile[0]);
            
            $validator = \Validator::make($request->all(), [
                'first_name'                => validation('first_name'),
                'last_name'                 => validation('last_name'),
                'email'                     => ['required','email',Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],
                'mobile'                    => array_merge([Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],validation('mobile')),
                'other_mobile'              => array_merge([Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],validation('mobile'),['different:mobile']),
                /*'website'                   => validation('website'),*/
                'address'                   => validation('address'),
                'country'                   => validation('country'),
                'state'                     => validation('state'),
                'postal_code'               => validation('postal_code'),
                'country_code'              => !empty($request->mobile) ? array_merge(['required'], validation('country_code')) : validation('country_code'),
                'other_country_code'        => !empty($request->other_mobile) ? array_merge(['required'], validation('country_code')) : validation('country_code'),
            ],[
                'first_name.required'           => 'M0006',
                'first_name.regex'              => 'M0007',
                'first_name.string'             => 'M0007',
                'first_name.max'                => 'M0020',
                'last_name.required'            => 'M0008',
                'last_name.regex'               => 'M0009',
                'last_name.string'              => 'M0009',
                'last_name.max'                 => 'M0019',
                'email.required'                => 'M0010',
                'email.email'                   => 'M0011',
                'email.unique'                  => 'M0047',
                'mobile.required'               => 'M0030',
                'mobile.regex'                  => 'M0031',
                'mobile.string'                 => 'M0031',
                'mobile.min'                    => 'M0032',
                'mobile.max'                    => 'M0033',
                'mobile.unique'                 => 'M0197',
                'website.string'                => 'M0114',
                'website.regex'                 => 'M0114',
                'website.max'                   => 'M0125',
                'address.string'                => 'M0057',
                'address.regex'                 => 'M0057',
                'address.max'                   => 'M0058',
                'country.integer'               => 'M0059',
                'state.integer'                 => 'M0060',
                'postal_code.string'            => 'M0061',
                'postal_code.regex'             => 'M0061',
                'postal_code.max'               => 'M0062',
                'postal_code.min'               => 'M0063',
                'country_code.required'         => 'M0164',
                'country_code.string'           => 'M0074',
                'other_country_code.required'   => 'M0432',
                'other_country_code.string'     => 'M0074',
                'other_mobile.required'         => 'M0030',
                'other_mobile.regex'            => 'M0031',
                'other_mobile.string'           => 'M0031',
                'other_mobile.min'              => 'M0032',
                'other_mobile.max'              => 'M0033',
                'other_mobile.different'        => 'M0127',
                'other_mobile.unique'           => 'M0197',
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                if(!empty($request->mobile) && empty($request->country_code)){
                    $this->message = 'M0164';
                }
                else if(!empty($request->other_mobile) && empty($request->other_country_code)){
                    $this->message = 'M0164';
                }else{
                    
                    $update = array_intersect_key(
                        json_decode(json_encode($request->all()),true), 
                        array_flip(
                            array(
                                'first_name',
                                'last_name',
                                'email',
                                'mobile',
                                'other_mobile',
                                'address',
                                'country',
                                'state',
                                'postal_code',
                                'country_code',
                                'other_country_code',
                            )
                        )
                    );

                    /*
                    *   REPLACING ALL BLANK STRING WITH 
                    *   NULL BECAUSE OF LARAVEL MYSQL 
                    *   DRIVER ASKING FOR INTEGER VALUE 
                    *   FOR INTEGER COLUMN TYPE
                    */
                    ___filter_null($update);
                    if($update['mobile'] != $request->user()->mobile){
                        $update['is_mobile_verified'] = DEFAULT_NO_VALUE;
                    }                
                    $isUpdated      = \Models\Employers::change($request->user()->id_user,$update);
                    
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'employer',
                        'action'            => 'webservice-employer-step-one',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));

                    $this->jsondata = [
                        'user' => \Models\Employers::get_user($request->user(),true)
                    ];
                    $this->status   = true;
                    $this->message  = "M0110";
                }
            }
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for profile picture setup]
         * @param  Request
         * @return Json Response
         */

        public function step_one_picture(Request $request){
            
            $validate = \Validator::make($request->all(), [
                "image"              => validation('image'),
            ],[
                'image.mimetypes'    => 'M0120',
            ]);

            if($validate->fails()){
                $this->message = $validate->messages()->first();
            }else{
                $folder = 'uploads/profile/';

                $uploaded_file = upload_file($request,'image',$folder,true);
                
                $data = [
                    'user_id'       => $request->user()->id_user,
                    'reference'     => 'users',
                    'filename'      => $uploaded_file['filename'],
                    'extension'     => $uploaded_file['extension'],
                    'folder'        => $folder,
                    'type'          => 'profile',
                    'size'          => $uploaded_file['size'],
                    'is_default'    => DEFAULT_NO_VALUE,
                    'created'       => date('Y-m-d H:i:s'),
                    'updated'       => date('Y-m-d H:i:s'),
                ];

                $isInserted = \Models\Talents::create_file($data,false,true);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'employer',
                    'action'            => 'webservice-employer-upload-profile',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));
                
                if(!empty($isInserted)){
                    if(!empty($isInserted['filename'])){
                        $isInserted['file_url'] = asset(sprintf("%s%s",$isInserted['folder'],$isInserted['filename']));
                    }
                    
                    $this->status = true;
                    $this->jsondata = $isInserted;
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for general information]
         * @param  Request
         * @return Json Response
         */

        public function step_two(Request $request){
            if(!empty($this->post['company_profile']) && !in_array($this->post['company_profile'], company_profile('key'))){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'company_profile');
            }else{
                if($this->post['company_profile'] == 'individual'){
                    $validator = \Validator::make($request->all(), [
                        'gender'                        => validation('gender'),
                        'company_name'                  => validation('company_name'),
                        'company_work_field'            => validation('company_work_field'),
                        /*'certificates'                  => validation('certificates'),*/
                    ],[
                        'gender.string'                 => 'M0056',
                        'company_work_field.integer'    => 'M0115',
                        'company_name.required'         => 'M0023',
                        'company_name.regex'            => 'M0024',
                        'company_name.string'           => 'M0024',
                        'company_name.max'              => 'M0025',
                    ]);
                }else if($this->post['company_profile'] == 'company'){
                    $validator = \Validator::make($request->all(), [
                        'company_name'                  => validation('company_name'),
                        'contact_person_name'           => validation('contact_person_name'),
                        'company_website'               => validation('website'),
                        'company_work_field'            => validation('company_work_field'),
                        'company_biography'             => validation('company_biography'),
                        /*'certificates'                  => validation('certificates'),*/
                    ],[
                        'company_name.required'         => 'M0023',
                        'company_name.regex'            => 'M0024',
                        'company_name.string'           => 'M0024',
                        'company_name.max'              => 'M0025',
                        'contact_person_name.required'  => 'M0040',
                        'contact_person_name.regex'     => 'M0041',
                        'contact_person_name.string'    => 'M0041',
                        'contact_person_name.max'       => 'M0042',
                        'company_website.string'        => 'M0114',
                        'company_website.regex'         => 'M0114',
                        'company_work_field.integer'    => 'M0115',
                        'company_biography.regex'       => 'M0116',
                        'company_biography.string'      => 'M0116',
                        'company_biography.max'         => 'M0117',
                        'company_biography.min'         => 'M0118',
                    ]);
                }

                if($validator->fails()){
                    $this->message = $validator->messages()->first();
                }else{
                    if(!empty($request->company_website)){
                        $request->request->add(['company_website'   => ___http($request->company_website)]);
                        $request->request->add(['website'           => ___http($request->company_website)]);
                    }
                    
                    $update = array_intersect_key(
                        json_decode(json_encode($request->all()),true), 
                        array_flip(
                            array(
                                'gender',
                                'company_work_field',
                                'company_profile',
                                'company_name',
                                'contact_person_name',
                                'website',
                                'company_website',
                                'company_biography',
                            )
                        )
                    );
                    /*
                    *   REPLACING ALL BLANK STRING WITH 
                    *   NULL BECAUSE OF LARAVEL MYSQL 
                    *   DRIVER ASKING FOR INTEGER VALUE 
                    *   FOR INTEGER COLUMN TYPE
                    */
                    ___filter_null($update);
                    
                    /*REMOVE AND ADD NEWLY SELECTED CERTIFICATES*/
                    /*\Models\Employers::update_certificate($request->user()->id_user,$this->post['certificates']);*/


                    $isUpdated      = \Models\Employers::change($request->user()->id_user,$update);
                    
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'employer',
                        'action'            => 'webservice-employer-step-two',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));

                    $this->jsondata = [
                        'user' => \Models\Employers::get_user($request->user(),true)
                    ];
                    $this->status   = true;
                    $this->message  = "M0110";
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for social connection] 
         * @param  Request
         * @return Json Response
         */

        public function step_three_social_connect(Request $request){
            
            if(empty($this->post['social_key']) || !in_array($this->post['social_key'], valid_social_keys())){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'social_key');
            }else if(empty($this->post['social_id'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'social_id');
            }else{
                $request->request->add([$request->social_key => $request->social_id]);
                $validator = \Validator::make($request->all(), [
                    $request->social_key    => [Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],
                ],[
                    sprintf('%s.unique',$request->social_key)   => 'M0126',
                ]);

                if($validator->fails()){
                    $this->message = $validator->messages()->first();
                }else{
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'employer',
                        'action'            => 'webservice-employer-step-three-social-connect',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));

                    $isUpdated = \Models\Employers::change($request->user()->id_user,[$request->social_key => $request->social_id, 'updated' => date('Y-m-d H:i:s')]);
                    $this->status = true;
                }
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for mobile change] 
         * @param  Request
         * @return Json Response
         */

        public function step_three_change_mobile(Request $request){
            
            $validator = \Validator::make($request->all(), [
                'mobile'                    => array_merge([Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],validation('phone_number')),
                'country_code'              => validation('country_code'),                
            ],[
                'country_code.string'       => 'M0074',
                'mobile.required'           => 'M0030',
                'mobile.regex'              => 'M0031',
                'mobile.string'             => 'M0031',
                'mobile.min'                => 'M0032',
                'mobile.max'                => 'M0033',
                'mobile.unique'             => 'M0197',

            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $created_date               = date('Y-m-d H:i:s');
                $otp_shuffle                = \Cache::get('configuration')['otp_shuffle'];
                $otp_length                 = \Cache::get('configuration')['otp_length'];
                $otp_expired                = \Cache::get('configuration')['otp_expired'];

                $otp_password               = substr(str_shuffle($otp_shuffle), 2, $otp_length);
                $otp_message                = sprintf(\Cache::get('configuration')['otp_message'],$otp_password);
                $otp_expired                = date('Y-m-d H:i:s',strtotime("+".$otp_expired." minutes", strtotime($created_date)));
                
                $isUpdated = \Models\Employers::change(
                    $request->user()->id_user,[
                        'country_code'          => $request->country_code, 
                        'mobile'                => $request->mobile, 
                        'otp_password'          => $otp_password,
                        'otp_created'           => $created_date,
                        'otp_expired'           => $otp_expired,
                        'is_mobile_verified'    => DEFAULT_NO_VALUE,
                        'updated'               => date('Y-m-d H:i:s')
                    ]
                );

                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'employer',
                    'action'            => 'webservice-employer-step-three-change-mobile',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));

                try{
                    $response = \Twilio::message(sprintf("%s%s",$request->country_code,$request->mobile), $otp_message);
                    $this->status = true;
                    $this->message = 'M0129';
                    $this->jsondata = [
                        'mobile' => $request->mobile,
                        'country_code' => $request->country_code,
                        'otp_password' => $otp_password,
                    ];

                    \Models\Listings::twilio_response([
                        'user_id' => $request->user()->id_user,
                        'twilio_response_json' => json_encode($response->client->last_response),
                        'created' => $created_date
                    ]);
                }catch ( \Services_Twilio_RestException $e ) {
                    $this->message = 'M0128';
                    \Models\Listings::twilio_response([
                        'user_id' => $request->user()->id_user,
                        'twilio_response_json' => json_encode(['body' => $e->getMessage()]),
                        'created' => $created_date
                    ]);
                } 
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to verify mobile] 
         * @param  Request
         * @return Json Response
         */

        public function step_three_verify_mobile(Request $request){
            
            $validator = \Validator::make($request->all(), [
                'otp_password'              => ['required']
            ],[
                'otp_password.required'     => 'M0130',
            ]);

            if($validator->fails()){
                $this->message              = $validator->messages()->first();
            }else{
                $result = (array) \Models\Employers::findById($request->user()->id_user,['otp_password']);

                if($result['otp_password'] == $request->otp_password){
                    $created_date               = date('Y-m-d H:i:s');
                    $otp_shuffle                = \Cache::get('configuration')['otp_shuffle'];
                    $otp_length                 = \Cache::get('configuration')['otp_length'];
                    $otp_expired                = \Cache::get('configuration')['otp_expired'];

                    $otp_password               = substr(str_shuffle($otp_shuffle), 2, $otp_length);
                    $otp_expired                = date('Y-m-d H:i:s',strtotime("+".$otp_expired." minutes", strtotime($created_date)));
                    
                    $this->message = 'M0132';
                    $this->status = true;
                    $isUpdated = \Models\Employers::change(
                        $request->user()->id_user,[
                            'otp_password'          => $otp_password,
                            'otp_created'           => $created_date,
                            'otp_expired'           => $otp_expired,
                            'is_mobile_verified'    => DEFAULT_YES_VALUE,
                            'updated'               => date('Y-m-d H:i:s')
                        ]
                    );

                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'employer',
                        'action'            => 'webservice-employer-step-three-verify-mobile',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));
                }else{
                    $this->message = 'M0131';
                }
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for rendering view of Employer job post] 
         * @param  null
         * @return \Illuminate\Http\Response
         */
        
        public function job_post(\Request $request, $step){
            if(!in_array($step, ['one','two','three','four','five'])){
                $this->message = "M0121";
            }

            $data['project']                        = \Models\Projects::draft(\Auth::user()->id_user);
            if($data['project']){
                $data['project']['startdate']       = !empty($data['project']['startdate']) ? date('Y-m-d',strtotime($data['project']['startdate'])) : '';
                $data['project']['enddate']         = !empty($data['project']['enddate']) ? date('Y-m-d',strtotime($data['project']['enddate'])) : '';
                $data['project']['industries']      = !empty($data['project']['industries']) ? array_column($data['project']['industries'], 'industries') : [];
                $data['project']['subindustries']   = !empty($data['project']['subindustries']) ? array_column($data['project']['subindustries'], 'subindustries') : [];
                $data['project']['skills']          = !empty($data['project']['skills']) ? array_column($data['project']['skills'], 'skills') : [];
                
                $this->jsondata                     = $data;
                if(__words_to_number($data['project']['step']) < __words_to_number($step) && $step != 'one'){
                    $this->message = "M0121";
                }else{
                    $this->status = true;
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            ); 
        }                

        /**
         * [This method is used for rendering view of Employer job post] 
         * @param  null
         * @return \Illuminate\Http\Response
         */
        
        public function job_post_process(Request $request, $step){
            $project_detail  = \Models\Projects::draft(\Auth::user()->id_user);

            if(!empty($project_detail)){
                $request->request->add(['id_project' => $project_detail['id_project']]);
            }

            if(__words_to_number($project_detail['step']) < __words_to_number($step) && $step != 'one'){
                $this->message = "M0121";
            }else{
                if(!empty($project_detail) || $step == 'one'){
                    switch ($step) {
                        case 'one':{
                            $validator = \Validator::make($request->all(), [
                                'title'                             => validation('jobtitle'),
                                'description'                       => validation('description'),
                                'agree'                             => validation('agree'),
                            ],[
                                'title.required'                    => 'M0090',
                                'title.string'                      => 'M0091',
                                'title.regex'                       => 'M0091',
                                'title.max'                         => 'M0092',
                                'title.min'                         => 'M0093',
                                'description.required'              => 'M0138',
                                'description.string'                => 'M0139',
                                'description.regex'                 => 'M0139',
                                'description.max'                   => 'M0140',
                                'description.min'                   => 'M0141',
                                'agree.required'                    => 'M0253',
                            ]);

                            if($validator->fails()){
                                $this->message              = $validator->messages()->first();
                            }else{
                                $postjob                = array_intersect_key(json_decode(json_encode($request->all()),true), array_flip(array('id_project','title','description')));
                                $postjob['talent_id']   = !empty($request->talent_id) ? $request->talent_id : '';
                                $postjob['step']        = 'two';
                                $postjob['status']      = 'draft';
                                $postjob['user_id']     = \Auth::user()->id_user;
                                $postjob['updated']     = date('Y-m-d H:i:s');
                                $postjob['created']     = date('Y-m-d H:i:s');

                                $isProjectSaved = \Models\Projects::postjob($postjob);
                                
                                if(!empty($isProjectSaved)){
                                    $this->status   = true;
                                }else{
                                    $this->message = 'M0356';
                                }
                            }

                            break;
                        }

                        case 'two':{
                            $validator = \Validator::make($request->all(), [
                                'industry'                          => array_merge(['required'],validation('industry')),
                                'required_skills'                   => validation('required_skills'),
                            ],[
                                'industry.array'                    => 'M0064',
                                'industry.required'                 => 'M0136',
                                'required_skills.required'          => 'M0137',
                                'required_skills.array'             => 'M0065',
                            ]);

                            if($validator->fails()){
                                $this->message              = $validator->messages()->first();
                            }else{
                                $isIndustryAdded        = \Models\Projects::saveindustry($request->id_project,$request->industry);
                                $isSkillSaved           = \Models\Employers::update_job_skills($request->id_project,$request->required_skills);

                                $postjob['id_project']  = $request->id_project;
                                $postjob['talent_id']   = !empty($request->talent_id) ? $request->talent_id : '';
                                $postjob['step']        = 'three';
                                $postjob['status']      = 'draft';
                                $postjob['user_id']     = \Auth::user()->id_user;
                                $postjob['updated']     = date('Y-m-d H:i:s');
                                $postjob['created']     = date('Y-m-d H:i:s');

                                $isProjectSaved = \Models\Projects::postjob($postjob);
                                
                                if(!empty($isProjectSaved)){
                                    $this->status   = true;
                                }else{
                                    $this->message = 'M0356';
                                }
                            }

                            break;
                        }

                        case 'three':{
                            $validator = \Validator::make($request->all(), [
                                'employment'                        => validation('employment'),
                                'price'                             => validation('price'),
                            ],[
                                'employment.required'               => 'M0133',
                                'employment.string'                 => 'M0134',
                                'price.required'                    => 'M0228',
                                'price.numeric'                     => 'M0229',
                                'price.max'                         => 'M0231',
                                'price.min'                         => 'M0230',
                            ]);

                            if($validator->fails()){
                                $this->message              = $validator->messages()->first();
                            }else{
                                $postjob['price']       = $request->price;
                                $postjob['price_unit']  = $request->currency;
                                $postjob['id_project']  = $request->id_project;
                                $postjob['talent_id']   = !empty($request->talent_id) ? $request->talent_id : '';
                                $postjob['employment']  = $request->employment;
                                $postjob['step']        = 'four';
                                $postjob['status']      = 'draft';
                                $postjob['user_id']     = \Auth::user()->id_user;
                                $postjob['updated']     = date('Y-m-d H:i:s');
                                $postjob['created']     = date('Y-m-d H:i:s');

                                $isProjectSaved = \Models\Projects::postjob($postjob);
                                
                                if(!empty($isProjectSaved)){
                                    $this->status   = true;
                                }else{
                                    $this->message = 'M0356';
                                }
                            }

                            break;
                        }

                        case 'four':{
                            $request->request->add(['employment' => $project_detail['employment']]);
                            $validator = \Validator::make($request->all(), [
                                'startdate'                         => array_merge(['required','validate_date','validate_start_date:'.$request->enddate],validation('birthday')),
                                'enddate'                           => array_merge(['required','validate_date','validate_date_type:'.$request->startdate.','.$request->employment],validation('birthday')),
                                'expected_hour'                     => ["required_time:".$project_detail['employment']],
                            ],[
                                'startdate.required'                => 'M0146',
                                'startdate.validate_date'           => 'M0434',
                                'startdate.validate_start_date'     => 'M0535',
                                'startdate.string'                  => 'M0147',
                                'startdate.regex'                   => 'M0147',
                                'enddate.required'                  => 'M0148',
                                'enddate.validate_date'             => 'M0435',
                                'enddate.string'                    => 'M0149',
                                'enddate.regex'                     => 'M0149',
                                'enddate.validate_date_type'        => 'M0472',
                                'expected_hour.required_time'       => 'M0584'
                            ]);

                            $daily_working_hours    = sprintf("%s:00:00",___cache('configuration')['daily_working_hours']);

                            if($validator->fails()){
                                $this->message = $validator->messages()->first();
                            }else if(strtotime($request->startdate) > strtotime($request->enddate)){
                                $this->message = 'M0190';
                            }elseif(strtotime($request->expected_hour) > strtotime($daily_working_hours)){
                                $this->message = 'M0524';
                            }else{
                                $postjob['id_project']      = $request->id_project;
                                $postjob['talent_id']       = !empty($request->talent_id) ? $request->talent_id : '';
                                $postjob['startdate']       = $request->startdate;
                                $postjob['expected_hour']   = $request->expected_hour;
                                $postjob['enddate']         = $request->enddate;
                                $postjob['step']            = 'five';
                                $postjob['status']          = 'draft';
                                $postjob['user_id']         = \Auth::user()->id_user;
                                $postjob['updated']         = date('Y-m-d H:i:s');
                                $postjob['created']         = date('Y-m-d H:i:s');

                                $isProjectSaved = \Models\Projects::postjob($postjob);
                                
                                if(!empty($isProjectSaved)){
                                    $this->status   = true;
                                }else{
                                    $this->message = 'M0356';
                                }
                            }

                            break;
                        }

                        case 'five':{
                            if(!empty($request->user()->company_name)){
                                /*$cards      = \Models\PaypalPayment::get_user_card(\Auth::user()->id_user,'','count',['*']);*/
                                if(1/*$cards > 0*/){
                                    $validator = \Validator::make($request->all(), [
                                        'industry_id'                       => ['required'],
                                        'subindustry'                       => array_merge(['required'],validation('subindustry')),
                                        'expertise'                         => array_merge(['required'],validation('expertise')),
                                        'other_perks'                       => validation('other_perks'),
                                    ],[
                                        'industry.required'                 => 'M0136',
                                        'subindustry.array'                 => 'M0142',
                                        'subindustry.required'              => 'M0334',
                                        'expertise.required'                => 'M0143',
                                        'expertise.string'                  => 'M0066',
                                        'other_perks.required'              => 'M0567',
                                        'other_perks.numeric'               => 'M0242',
                                        'other_perks.max'                   => 'M0243',
                                        'other_perks.min'                   => 'M0244',
                                        'other_perks.regex'                 => 'M0242'
                                    ]);

                                    if($validator->fails()){
                                        $this->message              = $validator->messages()->first();
                                    }else{
                                        $isSubIndustryAdded     = \Models\Projects::savesubindustry($request->id_project,$request->subindustry,$request->industry_id);
                                        
                                        if(!empty($isSubIndustryAdded)){
                                            $postjob['talent_id']   = !empty($request->talent_id) ? $request->talent_id : '';
                                            $postjob['status']      = 'active';
                                            $postjob['expertise']   = $request->expertise;
                                            $postjob['other_perks'] = $request->other_perks;
                                            $postjob['id_project']  = $request->id_project;
                                            $postjob['user_id']     = \Auth::user()->id_user;
                                            $postjob['updated']     = date('Y-m-d H:i:s');
                                            $postjob['created']     = date('Y-m-d H:i:s');

                                            $isProjectSaved = \Models\Projects::postjob($postjob);
                                            
                                            if(!empty($isProjectSaved)){
                                                if($postjob['talent_id']){
                                                    $isNotified = \Models\Notifications::notify(
                                                        $postjob['talent_id'],
                                                        $postjob['user_id'],
                                                        'JOB_INVITATION_SENT_BY_EMPLOYER',
                                                        json_encode([
                                                            "user_id"       => (string) $postjob['user_id'],
                                                            "talent_id"     => (string) $postjob['talent_id'],
                                                            "project_id"    => (string) $postjob['id_project']
                                                        ])
                                                    );
                                                }
                                                $this->status   = true;
                                            }else{
                                                $this->message = 'M0356';
                                            }
                                        }else{
                                            $this->message = 'M0583';
                                        }
                                    }
                                }else{
                                    $this->message = "M0572";
                                    $this->jsondata = [
                                        'type'          => 'confirm',
                                        'title'         => trans('general.M0043'),
                                        'messages'      => trans('general.M0572'),
                                        'button_one'    => trans('general.M0533'),
                                        'button_two'    => trans('general.M0534')
                                    ];                                
                                }
                            }else{
                                $this->message = "M0586";
                                $this->jsondata = [
                                    'type'          => 'confirm',
                                    'title'         => trans('general.M0043'),
                                    'messages'      => trans('general.M0586'),
                                    'button_one'    => trans('general.M0587'),
                                    'button_two'    => trans('general.M0534')
                                ];                                
                            }
                            break;
                        }
                    } 
                }else{
                    $this->message = 'M0121';
                }
            }            
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );    
        }

        /**
         * [This method is used for rendering view of Edit Employer job post] 
         * @param  null
         * @return \Illuminate\Http\Response
         */
        
        public function edit_job_post_process(Request $request, $step){
            $project_id = $request->project_id;

            if(!in_array($step, ['one','two','three','four','five']) && empty($project_id)){
                $this->message = "M0121";
            }else{
                $project_detail  = \Models\Projects::employer_jobs(\Auth::user())
                ->where('id_project',$project_id)->whereNotIn('projects.status',['draft','trashed'])->first();
                $project_detail = json_decode(json_encode($project_detail),true);
                if(!empty($project_detail)){
                    if($project_detail['awarded'] == DEFAULT_NO_VALUE){
                        switch ($step) {
                            case 'one':{
                                $validator = \Validator::make($request->all(), [
                                    'title'                             => validation('jobtitle'),
                                    'description'                       => validation('description'),
                                    'agree'                             => validation('agree'),
                                ],[
                                    'title.required'                    => 'M0090',
                                    'title.string'                      => 'M0091',
                                    'title.regex'                       => 'M0091',
                                    'title.max'                         => 'M0092',
                                    'title.min'                         => 'M0093',
                                    'description.required'              => 'M0138',
                                    'description.string'                => 'M0139',
                                    'description.regex'                 => 'M0139',
                                    'description.max'                   => 'M0140',
                                    'description.min'                   => 'M0141',
                                    'agree.required'                    => 'M0253',
                                ]);

                                if($validator->fails()){
                                    $this->message              = $validator->messages()->first();
                                }else{
                                    $postjob = array_intersect_key(json_decode(json_encode($request->all()),true), array_flip(array('title','description')));
                                    $postjob['updated']     = date('Y-m-d H:i:s');
                                    $postjob['id_project']  = $project_id;

                                    $isProjectSaved = \Models\Projects::postjob($postjob);
                                    
                                    if(!empty($isProjectSaved)){
                                        $this->status   = true;
                                    }else{
                                        $this->message = 'M0356';
                                    }
                                }

                                break;
                            }

                            case 'two':{
                                $validator = \Validator::make($request->all(), [
                                    'industry'                          => array_merge(['required'],validation('industry')),
                                    'required_skills'                   => validation('required_skills'),
                                ],[
                                    'industry.array'                    => 'M0064',
                                    'industry.required'                 => 'M0136',
                                    'required_skills.required'          => 'M0137',
                                    'required_skills.array'             => 'M0065',
                                ]);

                                if($validator->fails()){
                                    $this->message              = $validator->messages()->first();
                                }else{
                                    $isIndustryAdded        = \Models\Projects::saveindustry($project_id,$request->industry);
                                    $isSkillSaved           = \Models\Employers::update_job_skills($project_id,$request->required_skills);

                                    $postjob['id_project']  = $request->project_id;
                                    $postjob['updated']     = date('Y-m-d H:i:s');

                                    $isProjectSaved = \Models\Projects::postjob($postjob);
                                    
                                    if(!empty($isProjectSaved)){
                                        $this->status   = true;
                                    }else{
                                        $this->message = 'M0356';
                                    }
                                }

                                break;
                            }

                            case 'three':{
                                $validator = \Validator::make($request->all(), [
                                    'employment'                        => validation('employment'),
                                    'price'                             => validation('price'),
                                ],[
                                    'employment.required'               => 'M0133',
                                    'employment.string'                 => 'M0134',
                                    'price.required'                    => 'M0228',
                                    'price.numeric'                     => 'M0229',
                                    'price.max'                         => 'M0231',
                                    'price.min'                         => 'M0230',
                                ]);

                                if($validator->fails()){
                                    $this->message              = $validator->messages()->first();
                                }else{
                                    $postjob['price']       = $request->price;
                                    $postjob['price_unit']  = $request->currency;
                                    $postjob['id_project']  = $request->project_id;
                                    $postjob['employment']  = $request->employment;
                                    $postjob['updated']     = date('Y-m-d H:i:s');

                                    $isProjectSaved = \Models\Projects::postjob($postjob);
                                    
                                    if(!empty($isProjectSaved)){
                                        $this->status   = true;
                                    }else{
                                        $this->message = 'M0356';
                                    }
                                }

                                break;
                            }

                            case 'four':{
                                $request->request->add(['employment' => $project_detail['employment']]);
                                $validator = \Validator::make($request->all(), [
                                    'startdate'                         => array_merge(['required','validate_date','validate_start_date:'.$request->enddate],validation('birthday')),
                                    'enddate'                           => array_merge(['required','validate_date','validate_date_type:'.$request->startdate.','.$request->employment],validation('birthday')),
                                    'expected_hour'                     => ["required_time:".$project_detail['employment']],
                                ],[
                                    'startdate.required'                => 'M0146',
                                    'startdate.validate_date'           => 'M0434',
                                    'startdate.validate_start_date'     => 'M0535',
                                    'startdate.string'                  => 'M0147',
                                    'startdate.regex'                   => 'M0147',
                                    'enddate.required'                  => 'M0148',
                                    'enddate.validate_date'             => 'M0435',
                                    'enddate.string'                    => 'M0149',
                                    'enddate.regex'                     => 'M0149',
                                    'enddate.validate_date_type'        => 'M0472',
                                    'expected_hour.required_time'       => 'M0584'
                                ]);

                                $daily_working_hours    = sprintf("%s:00:00",___cache('configuration')['daily_working_hours']);
    
                                if($validator->fails()){
                                    $this->message              = $validator->messages()->first();
                                }else if(strtotime($request->startdate) > strtotime($request->enddate)){
                                    $this->message = 'M0190';
                                }else if(strtotime($request->expected_hour) > strtotime($daily_working_hours)){
                                    $this->message = 'M0524';
                                }else{
                                    $postjob['id_project']      = $request->project_id;
                                    $postjob['startdate']       = $request->startdate;
                                    $postjob['enddate']         = $request->enddate;
                                    $postjob['expected_hour']   = $request->expected_hour;
                                    $postjob['updated']         = date('Y-m-d H:i:s');

                                    $isProjectSaved = \Models\Projects::postjob($postjob);
                                    
                                    if(!empty($isProjectSaved)){
                                        $this->status   = true;
                                    }else{
                                        $this->message = 'M0356';
                                    }
                                }

                                break;
                            }

                            case 'five':{
                                $cards      = \Models\PaypalPayment::get_user_card(\Auth::user()->id_user,'','count',['*']);
                               // if($cards > 0) {
                                if(1) {
                                    $validator = \Validator::make($request->all(), [
                                        'industry_id'                       => ['required'],
                                        'subindustry'                       => array_merge(['required'],validation('subindustry')),
                                        'expertise'                         => array_merge(['required'],validation('expertise')),
                                        'other_perks'                       => validation('other_perks'),
                                    ],[
                                        'industry.required'                 => 'M0136',
                                        'subindustry.array'                 => 'M0142',
                                        'subindustry.required'              => 'M0334',
                                        'expertise.required'                => 'M0143',
                                        'expertise.string'                  => 'M0066',
                                        'other_perks.required'              => 'M0567',
                                        'other_perks.integer'               => 'M0242',
                                        'other_perks.max'                   => 'M0243',
                                        'other_perks.min'                   => 'M0244',
                                        'other_perks.regex'                 => 'M0242'
                                    ]);

                                    if($validator->fails()){
                                        $this->message              = $validator->messages()->first();
                                    }else{
                                        $isSubIndustryAdded     = \Models\Projects::savesubindustry($request->project_id,$request->subindustry,$request->industry_id);
                                    
                                        if(!empty($isSubIndustryAdded)){
                                            $postjob['expertise']   = $request->expertise;
                                            $postjob['other_perks'] = $request->other_perks;
                                            $postjob['id_project']  = $request->project_id;
                                            $postjob['updated']     = date('Y-m-d H:i:s');

                                            $isProjectSaved = \Models\Projects::postjob($postjob);
                                            
                                            if(!empty($isProjectSaved)){
                                                $this->status   = true;
                                                $proposals = \Models\Proposals::defaultKeys()->where('talent_proposals.status','!=','rejected')->where('project_id',$project_id)->get();
                                        
                                                if(!empty($proposals->count())){
                                                    foreach($proposals as $item){
                                                        $isNotified = \Models\Notifications::notify(
                                                            $item->user_id,
                                                            $request->user()->id_user,
                                                            'JOB_UPDATED_BY_EMPLOYER',
                                                            json_encode([
                                                                "employer_id"   => (string) $request->user()->id_user,
                                                                "talent_id"     => (string) $item->user_id,
                                                                "project_id"    => (string) $project_id,
                                                                "proposal"      => (string) $item->id_proposal,
                                                                "project_title" => (string) sprintf("#%'.0".JOBID_PREFIX."d",$project_id)
                                                            ])
                                                        );
                                                    }
                                                }
                                            }else{
                                                $this->message = 'M0356';
                                            }
                                        }else{
                                            $this->message = 'M0583';
                                        }
                                    }
                                }else{
                                    $this->message = "M0572";
                                    $this->jsondata = [
                                        'type'          => 'confirm',
                                        'title'         => trans('general.M0043'),
                                        'messages'      => trans('general.M0572'),
                                        'button_one'    => trans('general.M0533'),
                                        'button_two'    => trans('general.M0534')
                                    ];
                                }

                                break;
                            }
                        }
                    }else{
                        $this->message = 'M0526';
                    }
                }else{
                    $this->message = "M0121";
                }

                
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );    
        }        

        /**
         * [This method is used for Delete jobs] 
         * @param  Request
         * @return Json Response
         */        

        public function delete_job(Request $request){
            $project_id = $request->project_id;
            if(empty($project_id)){
                $this->message = "M0121";
            }else{
                $project_detail  = \Models\Projects::employer_jobs(\Auth::user())
                ->where('id_project',$project_id)
                ->whereNotIn('projects.status',['draft','trashed'])
                ->first();
                
                $project_detail = json_decode(json_encode($project_detail),true);
                if(!empty($project_detail)){
                    if($project_detail['awarded'] == DEFAULT_NO_VALUE){
                        $postjob = [
                            'id_project' => $project_id,
                            'status'     => 'trashed'
                        ];
                        
                        $isProjectSaved = \Models\Projects::postjob($postjob);
                        if(!empty($isProjectSaved)){
                            $this->status   = true;
                            $this->message  = 'M0527';
                        }
                    }else{
                        $this->message = 'M0526';
                    }
                }else{
                    $this->message = "M0527";
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );           
        }

        /**
         * [This method is used for cancel jobs] 
         * @param  Request
         * @return Json Response
         */        

        public function cancel_job(Request $request){
            $project_id = ___decrypt($request->project_id);
            if(empty($project_id)){
                $this->message = "M0121";
            }else{
                $project_detail  = \Models\Projects::defaultKeys()
                ->with([
                    'proposal' => function($q){
                        $q->defaultKeys()->where('talent_proposals.status','accepted')->with([
                            'talent' => function($q){
                                $q->defaultKeys()->country()->review()->with([
                                    'interests'
                                ]);
                            }
                        ]);
                    }
                ])
                ->where('id_project',$project_id)
                ->whereNotIn('projects.status',['draft','trashed'])
                ->first();
                
                $project_detail = json_decode(json_encode($project_detail),true);
                
                if(!empty($project_detail)){
                    if($project_detail['is_cancelled'] === DEFAULT_YES_VALUE){
                        $this->message = 'M0574';
                    }else if($project_detail['project_status'] !== 'pending'){
                        $this->message = 'M0575';
                    }else if($project_detail['awarded'] === DEFAULT_NO_VALUE){
                        $this->message = 'M0576';
                    }else if($project_detail['is_cancelable'] == DEFAULT_NO_VALUE){
                        $this->message = 'M0577';
                    }else{
                        $postjob = [
                            'id_project'    => $project_id,
                            'canceldate'    => date('Y-m-d H:i:s'),
                            'is_cancelled'  => DEFAULT_YES_VALUE
                        ];

                        $isProjectSaved = \Models\Projects::postjob($postjob);

                        if(!empty($isProjectSaved)){
                            $isRefunded = \Models\Payments::cancel_refund($project_detail['id_project'],$project_detail['company_id'],$project_detail['proposal']['id_proposal']);
                            $isNotified = \Models\Notifications::notify(
                                $project_detail['proposal']['talent']['id_user'],
                                $project_detail['company_id'],
                                'JOB_CANCELLED_BY_EMPLOYER',
                                json_encode([
                                    "employer_id"   => (string) $project_detail['company_id'],
                                    "talent_id"     => (string) $project_detail['proposal']['talent']['id_user'],
                                    "project_id"    => (string) $project_detail['id_project']
                                ])
                            );

                            $this->status   = true;
                            $this->message  = 'M0579';
                        }
                    }
                }else{
                    $this->message = 'M0121';
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );     
        }   

        /**
         * [This method is used for Employer jobs] 
         * @param  Request
         * @return Json Response
         */
        
        public function employer_jobs(Request $request, $type){
            
            $this->status   = true;
            $prefix         = DB::getTablePrefix();
            
            $projects = \Models\Projects::employer_jobs($request->user())->addSelect(\DB::Raw("'{$type}' as type"));

            if($type == 'current'){
                $projects->withCount([
                    'proposals',
                    'proposal' => function($q){
                        $q->where('status','accepted');
                    }
                ])
                ->having('proposal_count','>','0')
                ->havingRaw("(project_status = 'initiated' OR project_status = 'completed')")
                ->having('is_cancelled','=',DEFAULT_NO_VALUE);
            }else if($type == 'scheduled'){
                $projects->withCount([
                    'proposals',
                    'proposal' => function($q){
                        $q->where('status','accepted');
                    }
                ])
                ->having('proposal_count','>','0')
                ->having('project_status','=','pending')
                ->having('is_cancelled','=',DEFAULT_NO_VALUE);
            }else if($type == 'completed'){
                $projects->withCount([
                    'proposals',
                    'proposal' => function($q){
                        $q->where('status','accepted');
                    }
                ])
                ->having('proposal_count','>','0')
                ->havingRaw("(project_status = 'closed' OR is_cancelled = '".DEFAULT_YES_VALUE."' )");
            }else{
                $projects->withCount(['proposals'])->where('awarded','=',DEFAULT_NO_VALUE)
                ->having('is_cancelled','=',DEFAULT_NO_VALUE);
            }

            if(empty($request->sortby_filter)){
                $sort = "{$prefix}projects.id_project DESC";
            }else{
                $sort = sprintf("%s%s",$prefix,___decodefilter($request->sortby_filter));
            }

            if(empty($request->page)){
                $result = $projects->whereNotIn('projects.status',['draft','trashed'])->orderByRaw($sort)->groupBy(['projects.id_project'])->get();
            }else{
                $result = $projects->whereNotIn('projects.status',['draft','trashed'])->orderByRaw($sort)->groupBy(['projects.id_project'])->limit(DEFAULT_PAGING_LIMIT)->offset(($request->page - 1)*DEFAULT_PAGING_LIMIT)->get();
            }

            $this->jsondata = api_resonse_project($result,false,['created']);

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => [
                        'result' => $this->jsondata,
                        'count' => [
                            'current'   => (string) \Models\Projects::employer_jobs($request->user())->addSelect(\DB::Raw("'{$type}' as type"))->withCount(['proposal' => function($q){$q->where('status','accepted'); } ])->having('proposal_count','>','0')->havingRaw("(project_status = 'initiated' OR project_status = 'completed')")->whereNotIn('projects.status',['draft','trashed'])->having('is_cancelled','=',DEFAULT_NO_VALUE)->groupBy(['projects.id_project'])->get()->count(),
                            'scheduled' => (string) \Models\Projects::employer_jobs($request->user())->addSelect(\DB::Raw("'{$type}' as type"))->withCount(['proposal' => function($q){$q->where('status','accepted'); } ])->having('proposal_count','>','0')->having('project_status','=','pending')->whereNotIn('projects.status',['draft','trashed'])->having('is_cancelled','=',DEFAULT_NO_VALUE)->groupBy(['projects.id_project'])->get()->count(),
                            'completed' => (string) \Models\Projects::employer_jobs($request->user())->addSelect(\DB::Raw("'{$type}' as type"))->withCount(['proposal' => function($q){$q->where('status','accepted'); } ])->having('proposal_count','>','0')->havingRaw("(project_status = 'closed' OR is_cancelled = '".DEFAULT_YES_VALUE."')")->whereNotIn('projects.status',['draft','trashed'])->groupBy(['projects.id_project'])->get()->count(),
                            'submitted' => (string) \Models\Projects::employer_jobs($request->user())->addSelect(\DB::Raw("'{$type}' as type"))->where('awarded','=',DEFAULT_NO_VALUE)->whereNotIn('projects.status',['draft','trashed'])->having('is_cancelled','=',DEFAULT_NO_VALUE)->groupBy(['projects.id_project'])->get()->count()
                        ]
                    ]
                ])
            );
        }

        public function job_detail(Request $request){
            $language   = \App::getLocale();
            $prefix     = DB::getTablePrefix();
            
            if(!empty($this->post['id_project'])){
                $project_id     = $this->post['id_project'];
                $user           = $request->user();
                
                $project = \Models\Projects::employer_jobs($user)
                ->withCount([
                    'reviews' => function($q){
                        $q->where('sender_id',auth()->user()->id_user);
                    },
                    'proposal' => function($q){
                        $q->where('talent_proposals.status','!=','rejected');
                    }
                ])
                ->with([
                    'industries.industries' => function($q) use($language,$prefix){
                        $q->select(
                            'id_industry',
                            \DB::Raw("IF(({$language} != ''),`{$language}`, `en`) as name"),
                            'slug'
                        );
                    },
                    'subindustries.subindustries' => function($q) use($language,$prefix){
                        $q->select(
                            'id_industry',
                            \DB::Raw("IF(({$language} != ''),`{$language}`, `en`) as name")
                        );
                    },
                    'skills.skills' => function($q){
                        $q->select(
                            'id_skill',
                            'skill_name'
                        );
                    },
                    'dispute' => function($q){
                        $q->defaultKeys();
                    },
                    'projectlog' => function($q) use($user){
                        $q->select('project_id')->totalTiming()->groupBy(['project_id']);
                    },
                    'chat' => function($q){
                        $q->defaultKeys()->sender();
                    },
                    'proposal' => function($q) use($language){
                        $q->defaultKeys()->where('talent_proposals.status','accepted')->with([
                            'talent' => function($q) use($language){
                                $q->defaultKeys()->review()->with([
                                    'subindustries.subindustries' => function($q) use($language){
                                        $q->select(
                                            'id_industry',
                                            \DB::Raw("IF(({$language} != ''),`{$language}`, `en`) as name")
                                        );
                                    }
                                ]);
                            }
                        ]);
                    },
                    'proposals' => function($q){
                        $q->select('id_proposal','user_id','project_id');
                        $q->with([
                            'talent' => function($q){
                                $q->select('id_user')->companyLogo();
                            }
                        ]);
                    }

                ])
                ->acceptedTalentId($project_id)
                ->where('id_project',$project_id)->get()->first();

                if(!empty($project)){
                    $this->status   = true;
                    $project = json_decode(json_encode($project),true);

                    if(!empty($project['industries'])){
                        $job_category = $project['industries'][0]['industries']['slug'];
                    }else{
                        $job_category = 0;
                    }

                    $project['job_detail_public_url'] = url('project/show-details/category/'.$job_category.'/job_id/'.___encrypt($project_id));
                    
                    if(!empty($project['skills'])){ 
                        $project['skills'] = array_column($project['skills'], 'skills');
                    }

                    if(!empty($project['subindustries'])){
                        $project['subindustries'] = array_column($project['subindustries'], 'subindustries');
                    }

                    if(!empty($project['industries'])){
                        $project['industries'] = array_column($project['industries'], 'industries');
                    }

                    if(empty($project['projectlog'])){
                        $project['projectlog'] = (object)[
                            'project_id'          => $project['id_project'],
                            'total_working_hours' => ___hours('00:00')
                        ];
                    }else{
                        $project['projectlog']['total_working_hours'] = ___hours(substr($project['projectlog']['total_working_hours'], 0, -3));
                    }

                    if(!empty($project['chat'])){
                        $sender_id                              = $project['chat']['sender_id'];

                        $project['chat']['sender_id']           = $project['chat']['receiver_id'];
                        $project['chat']['receiver_id']         = $sender_id;       
                        $project['chat']['request_status']      = 'accepted';   
                        $project['chat']['receiver_picture']    = (string)get_file_url(\Models\Talents::get_file(sprintf(" type = 'profile' AND user_id = %s",$project['chat']['receiver_id']),'single',['filename','folder']));    
                    }

                    if($project['is_cancelled'] == DEFAULT_YES_VALUE){
                        $project['created'] = trans('general.M0578').' '.___ago($project['canceldate']);
                    }elseif($project['project_status'] == 'closed'){
                        $project['created'] = trans('general.M0520').' '.___ago($project['closedate']);
                    }else{
                        $project['created'] = trans('general.M0177').' '.___ago($project['created']);
                    }

                    $commission = ___cache('configuration')['cancellation_commission'];
                    $commission_type = ___cache('configuration')['cancellation_commission_type'];

                    if($commission_type == 'per'){
                        $calculated_commission=___format(round(((($project['price']*$commission)/100)),2)); 
                    }else{
                        $calculated_commission = ___format(round(($commission),2));
                    }

                    $refundable_amount = $project['price'] - $calculated_commission;
                    $project['show_cancellation_fee'] = ___format($refundable_amount,true,true);

                    $this->jsondata = api_resonse_common($project,false,['created']);
                }else{
                    $this->message = 'M0121';
                    $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_project');
                }
            }else{
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_project');
            } 
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for user's profile] 
         * @param  Request
         * @return Json Response
         */

        public function talentprofile(Request $request){
            if(!empty($this->post['talent_id'])){
                $user = \Models\Talents::get_user((object)['id_user' => $this->post['talent_id']],true);

                $viewed_talent                  = [
                    'employer_id'   => $request->user()->id_user,
                    'talent_id'     => $this->post['talent_id'],
                    'updated'       => date('Y-m-d H:i:s'),
                    'created'       => date('Y-m-d H:i:s')
                ];
                $user['last_viewed'] = ___ago(\Models\ViewedTalents::add_viewed_talent($viewed_talent));                
                $user['is_saved']     = \Models\Employers::is_talent_saved(\Auth::user()->id_user,$this->post['talent_id']);
                $user['is_saved'] = ($user['is_saved'] == true) ? DEFAULT_YES_VALUE : DEFAULT_NO_VALUE;
                if(!empty($user)){
                    $this->status = true;
                }
                
                $user['share_link'] = url('/showprofile/'.strtolower($user['first_name']).'-'.strtolower($user['last_name']).'/'.___encrypt($user['id_user']));

                $this->jsondata = [
                    'user' => $user,
                    'chat' => \Models\Employers::get_my_chat_list($request->user()->id_user,NULL,$this->post['talent_id'])
                ];
            }else{
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'talent_id');
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to find users] 
         * @param  Request
         * @return Json Response
         */

        public function find_talents(Request $request){
            $page       = 0;
            $search     = " 1 ";
            $having     = " 1 ";
            $sort       = "";
            
            $talents = \Models\Employers::find_talents(\Auth::user(),$request);
            $this->status = true;
            $this->jsondata = api_resonse_common($talents['result']);

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );            
        }

        /**
         * [This method is used for all proposals] 
         * @param  Request
         * @return Json Response
         */

        public function project_proposals(Request $request){
            
            $this->status   = true;
            $page           = 0;
            $search         = "";
            $sort           = "";
            $page           = !empty($request->page)?$request->page:1;

            
            $projects =  \Models\Projects::defaultKeys()
            ->withCount('proposals')
            ->where('user_id',\Auth::user()->id_user)
            ->where("projects.is_cancelled",DEFAULT_NO_VALUE)
            ->whereNotIn('projects.status',['draft','trashed']);

            
            if(!empty($request->search)){
                $projects = $projects->where('title','LIKE',"%{$request->search}%");
            }

            if(!empty($request->sort)) {
                $sort = explode(" ", ___decodefilter($request->sort));
                if(count($sort) == 2){
                    $projects = $projects->orderBy($sort[0],$sort[1]);
                }
            }else{
                $projects = $projects->orderBy('created',"DESC");
            }

            if(!empty($request->filter)){
                $projects = $projects->having('project_status','=',$request->filter);
            }

            $projects = $projects->orderBy('projects.id_project','DESC')->limit(DEFAULT_PAGING_LIMIT)
            ->offset((($page -1)*DEFAULT_PAGING_LIMIT))
            ->get();

            $this->jsondata = api_resonse_common($projects);
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );            
        }

        /**
         * [This method is used for proposals listing] 
         * @param  Request
         * @return Json Response
         */
        

        public function proposals_listing(Request $request){
            $this->status   = true;            

            if(!empty($request->id_project)){
                $project_id = $request->id_project;
                $page       = !empty($request->page)?$request->page:1;
                
                $project    = \Models\Projects::defaultKeys()->withCount([
                    'proposals',
                    'reviews' => function($q){
                        $q->where('sender_id',auth()->user()->id_user);
                    }
                ])->projectPrice()->where('id_project',$project_id)->first();

                $proposals = \Models\Talents::defaultKeys()
                ->with([
                    'chat' => function($q) use($project_id){
                        $q->where('project_id',$project_id)->defaultKeys()->sender();
                    }
                ])
                ->country()
                ->review()
                ->isTalentSavedEmployer(auth()->user()->id_user)
                ->isTalentViewedEmployer(auth()->user()->id_user)
                ->talentProposals($project_id)
                ->whereNotNull('id_proposal');

                if(!empty($request->sort)) {
                    $sort = explode(" ", ___decodefilter($request->sort));
                    if(count($sort) == 2){
                        $proposals = $proposals->orderBy($sort[0],$sort[1]);
                    }
                }

                if (!empty($request->filter)) {
                    if($request->filter == 'tagged_listing'){
                        $proposals = $proposals->havingRaw('(is_saved = "'.DEFAULT_YES_VALUE.'")');
                    }else if($request->filter == 'applied_proposal'){
                        $proposals = $proposals->havingRaw('(proposal_status = "applied")');
                    }else if($request->filter == 'accepted_proposal'){
                        $proposals = $proposals->havingRaw('(proposal_status = "accepted")');
                    }else if($request->filter == 'declined_proposal'){
                        $proposals = $proposals->havingRaw('(proposal_status = "rejected")');
                    } 
                }

                if(!empty($request->search)){
                    $proposals = $proposals->whereRaw("(name like '%{$request->search}%' OR comments like '%{$request->search}%' OR quoted_price like '%{$request->search}%')");
                }
                $project->proposals = $proposals->limit(DEFAULT_PAGING_LIMIT)
                ->offset((($page -1)*DEFAULT_PAGING_LIMIT))
                ->get();

                $commission = ___cache('configuration')['cancellation_commission'];
                $commission_type = ___cache('configuration')['cancellation_commission_type'];

                if($commission_type == 'per'){
                    $calculated_commission=___format(round(((($project->price*$commission)/100)),2)); 
                }else{
                    $calculated_commission = ___format(round(($commission),2));
                }

                $refundable_amount = $project->price - $calculated_commission;
                $project['show_cancellation_fee'] = ___format($refundable_amount,true,true);

                $this->jsondata = api_resonse_common($project);
                array_walk($this->jsondata['proposals'],function(&$item){
                    if(!empty($item['chat'])){
                        $sender_id = $item['chat']['sender_id'];
                        $item['chat']['sender_id'] = $item['chat']['receiver_id'];
                        $item['chat']['receiver_id'] = $sender_id;
                        $item['chat']['request_status'] = 'accepted';
                    }
                });

            }else{
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_project');
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for proposal acceptance] 
         * @param  Request
         * @return Json Response
         */
        
        public function accept_proposal(Request $request){
            
            if(empty($request->project_id)){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');   
            }else if(empty($request->proposal_id)){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'proposal_id');   
            }else{
                $isProposalAccepted =  \Models\Employers::accept_proposal($request->user()->id_user,$request->project_id,$request->proposal_id);
                $this->message      = $isProposalAccepted['message'];

                if(!empty($isProposalAccepted['status'])){

                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'employer',
                        'action'            => 'webservice-employer-accept-proposal',
                        'reference_type'    => 'projects',
                        'reference_id'      => $request->project_id
                    ]));
                    
                    $this->status   = true;
                }
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for proposal decline] 
         * @param  Request
         * @return Json Response
         */

        public function decline_proposal(Request $request){
            
            if(empty($request->project_id)){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');   
            }else if(empty($request->proposal_id)){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'proposal_id');   
            }else{
                $isProposalDeclined =  \Models\Employers::decline_proposal($request->user()->id_user,$request->project_id,$request->proposal_id);
                $this->message  = $isProposalDeclined['message'];

                if(!empty($isProposalDeclined['status'])){

                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'employer',
                        'action'            => 'webservice-employer-decline-proposal',
                        'reference_type'    => 'projects',
                        'reference_id'      => $request->project_id
                    ]));

                    $this->status   = true;
                }
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        } 

        /**
         * [This method is used for getting user's availability] 
         * @param  Request
         * @return Json Response
         */  

        public function get_talent_availability(Request $request){
            $this->status           = true;
            $availability_calendar  = [];
            
            if(!empty($request->talent_id)){
                $talent_id              = ___decrypt($request->talent_id);
                $talent_availability    = \Models\Talents::get_calendar_availability($talent_id,$request->date,true);

                if(!empty($talent_availability)){
                    foreach($talent_availability as &$item){
                        // $item['title'] = sprintf("%s\n%s\n%s %s",$item['title'],trans("website.{$item['type']}"),"until",___d($item['deadline']));
                        // if($item['type'] == 'weekly'){
                        //     $item['title'] = $item['title'];
                        // }
                        $item['type'] = trans("website.{$item['type']}");
                    }
                }

                $this->jsondata = $talent_availability;
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to save User's] 
         * @param  Request
         * @return Json Response
         */

        public function save_talent(Request $request){
            
            if(empty($request->talent_id)){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'talent_id');
            }else{
                $isUpdated  = \Models\Employers::save_talent($request->user()->id_user,$request->talent_id);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'employer',
                    'action'            => 'webservice-employer-save-talent',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->talent_id
                ]));
                $this->status   = $isUpdated['status'];
                
                if($isUpdated['action'] == 'deleted_saved_talent'){
                    $this->message  = 'M0220';               
                }else if($isUpdated['action'] == 'saved_talent'){
                    $this->message  = 'M0219';               
                }else{
                    $this->message  = 'M0022';
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );            
        }

        /**
         * [This method is used for employer chat request] 
         * @param  Request
         * @return Json Response
         */

        public function employer_chat_request(Request $request){
            
            if(empty($this->post['sender'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'sender');
            }else if(empty($this->post['receiver'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'receiver');
            }else if(empty($this->post['project_id'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
            }else {
                $isRequestSent = \Models\Chats::employer_chat_request($this->post['sender'],$this->post['receiver'],$this->post['project_id']);
                
                if(!empty($isRequestSent['status'])){
                    $this->status = $isRequestSent['status'];
                    $this->message = $isRequestSent['message'];
                }else{
                    $this->message = $isRequestSent['message'];
                    $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'receiver'); 
                }

                $this->jsondata = \Models\Employers::get_my_chat_list($this->post['sender'],NULL,$this->post['receiver']);                    
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used change password] 
         * @param  Request
         * @return Json Response
         */

        public function change_password(Request $request){

            $validator = \Validator::make($request->all(), [
                "old_password"              => validation('old_password'),
                "new_password"              => validation('new_password'),
            ],[
                'old_password.required'     => 'M0292',
                'old_password.old_password' => 'M0295',
                'new_password.different'    => 'M0300',
                'new_password.required'     => 'M0293',
                'new_password.regex'        => 'M0296',
                'new_password.max'          => 'M0297',
                'new_password.min'          => 'M0298',
            ]);

            if($validator->fails()){
                $this->message   = $validator->messages()->first();
            }else{
                $isUpdated      = \Models\Employers::change($request->user()->id_user,[
                    'password'          => bcrypt($request->new_password),
                    'api_token'         => bcrypt(__random_string()),
                    'updated'           => date('Y-m-d H:i:s')
                ]);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'employer',
                    'action'            => 'webservice-employer-change-password',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));
                
                $this->status   = true;
                $this->message  = 'M0301';
                $this->redirect = url(sprintf('%s/change-password',TALENT_ROLE_TYPE));
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for proposals tag] 
         * @param  Request
         * @return Json Response
         */

        public function proposals_tag(Request $request){
            
            if(empty($request->proposal_id)){
                $this->message = 'M0121';
                $this->message = sprintf(trans(sprintf('general.%s',$this->message)),'proposal_id');
            }else{
                $isProposalTagged       =  \Models\Employers::tag_proposal($request->user()->id_user,$request->proposal_id);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'employer',
                    'action'            => 'webservice-employer-proposal-tag',
                    'reference_type'    => 'talent_proposals',
                    'reference_id'      => $request->proposal_id
                ]));
                
                $this->message = $isProposalTagged['message'];
                $this->status  = true;
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        } 

        /**
         * [This method is used for tagged proposals] 
         * @param  Request
         * @return Json Response
         */  

        public function tagged_proposals(Request $request){
            
            if(empty($request->proposal_id)){
                $this->message      = 'M0121';
                $this->message      = sprintf(trans(sprintf('general.%s',$this->message)),'proposal_id');
            }else if(empty($request->project_id)){
                $this->message      = 'M0121';
                $this->message      = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
            }else{
                $proposals     = \Models\Employers::tagged_proposals($request->user()->id_user,$request->project_id,$request->proposal_id);

                array_walk($proposals, function(&$item) use($request){
                    $isChatConnection    = \Models\Employers::get_my_chat_list($request->user()->id_user,NULL,$item['user_id']);
                    
                    if(!empty($isChatConnection)){
                        $item['chat'] = $isChatConnection;
                    }else{
                        $item['chat'] = [];
                    }
                });

                $this->jsondata     = $proposals;
                $this->status       = true;
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }   
        
        /**
         * [This method is used for payment] 
         * @param  Request
         * @return Json Response
         */

        public function payments(Request $request){
            $currency = (!empty($request->currency)) ? $request->currency : DEFAULT_CURRENCY;
            $payment_summary = \Models\Payments::summary($request->user()->id_user,'employer');
            
            $this->jsondata = $payment_summary;
            
            $payments       = \Models\Payments::listing($request->user()->id_user,'employer',$request->type, false, $request->page,$request->sort,$request->search);

            if(!empty($payments->count())){
                $this->status   = true;
                $payments_list  = json_decode(json_encode($payments),true);
                array_walk($payments_list, function(&$item) use($request){
                    if($request->type == 'all'){
                        $item['transaction_subtotal'] = ___calculate_payment($item['employment'],$item['quoted_price']);
                    }
                    $item['transaction_date']       = ___d($item['transaction_date'],'jS F Y');
                    $item['transaction_subtotal']   = $item['currency'].___format($item['transaction_subtotal'],true,false);
                    $item['quoted_price']           = ___format($item['quoted_price'],true,true);
                });

                $this->jsondata['payments_list'] = $payments_list;
            }else{
                $this->status   = true;
                $this->jsondata['payments_list'] = [];
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for user's portfolio] 
         * @param  Request
         * @return Json Response
         */

        public function talent_portfolio(Request $request){
            $page = 1;
            
            if(!empty($request->page)){
                $page = $request->page;
            }            

            if(empty($request->talent_id)){
                $this->message = "M0121";
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'talent_id');
            }else{
                $data['isOwner']        = \Models\companyConnectedTalent::with(['user'])->where('id_user',$request->talent_id)->where('user_type','owner')->get()->first();
                $data['isOwner']        = json_decode(json_encode($data['isOwner']));

                if(!empty($data['isOwner'])){
                    $data['connected_user'] = \Models\companyConnectedTalent::select('id_user')->where('id_talent_company',$data['isOwner']->id_talent_company)->where('user_type','user')->get();
                    $data['connected_user'] = json_decode(json_encode($data['connected_user']),true);
                }else{
                    $data['connected_user'] = [];
                }
                $talent_ids[] = $request->talent_id;
                $user_ids = array_column($data['connected_user'], 'id_user');
                $user_ids = array_merge($user_ids,$talent_ids);

                $portfolioes = \Models\Portfolio::get_portfolio($user_ids,"","all",[],$page);

                if($portfolioes){
                    $this->status = true;
                    array_walk($portfolioes, function(&$item, $key){

                        if(!empty($item['created'])){
                            $item['created'] = ___dd($item['created'],'jS F Y');
                        }else{
                            $item['created'] = ___dd(date('Y-m-d H:i:s'),'jS F Y');
                        }

                        if(!empty($item['file'])){
                            foreach ($item['file'] as &$file) {
                                $file['file'] = asset(sprintf("%s%s",$file['folder'],$file['filename']));
                            }
                        }else{
                            $item['file'][] = [
                                'id_file' => 0,
                                'filename' => DEFAULT_AVATAR_IMAGE,
                                'folder' => 'images/',
                                'file' => url(sprintf('images/%s',DEFAULT_AVATAR_IMAGE))
                            ];
                        }
                    });
                    $this->jsondata = $portfolioes;
                }                
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );            
        }

        /**
         * [This method is used to add review] 
         * @param  Request
         * @return Json Response
         */

        public function add_review(Request $request){
            if(empty($request->receiver_id)){
                $this->message = "M0121";
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'receiver_id'); 
            }else if(empty($request->project_id)){
                $this->message = "M0121";
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id'); 
            }else{
                $validator = \Validator::make($request->all(), [
                    "category_one"          => validation('review_average'),
                    "category_two"          => validation('review_performance'),
                    "category_three"        => validation('review_punctuality'),
                    "category_four"         => validation('review_quality'),
                    "category_five"         => validation('review_skill'),
                    "category_six"          => validation('review_support'),
                    "description"           => validation('review_description'),
                ],[
                    "category_one.required"         => 'M0330',
                    "category_one.numeric"          => 'M0337',
                    "category_one.min"              => 'M0330',
                    "category_two.required"         => 'M0331',
                    "category_two.numeric"          => 'M0338',
                    "category_two.min"              => 'M0331',
                    "category_three.required"       => 'M0332',
                    "category_three.numeric"        => 'M0339',
                    "category_three.min"            => 'M0332',
                    "category_four.required"        => 'M0333',
                    "category_four.numeric"         => 'M0340',
                    "category_four.min"             => 'M0333',
                    "category_five.required"        => 'M0334',
                    "category_five.numeric"         => 'M0341',
                    "category_five.min"             => 'M0334',
                    "category_six.required"         => 'M0335',
                    "category_six.numeric"          => 'M0342',
                    "category_six.min"              => 'M0335',
                    "description.required"          => 'M0343',
                    "description.string"            => 'M0336',
                    "description.regex"             => 'M0336',
                    "description.max"               => 'M0327',
                    "description.min"               => 'M0328',
                ]);
                if($validator->fails()){
                    $this->message = $validator->messages()->first();                
                }else{
                    if(empty($project_data = json_decode(json_encode(\Models\Projects::where(['id_project' => $request->project_id,'user_id' => $request->user()->id_user])->get(),true)))){
                        $this->message = "M0121";
                        $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
                    }else if(empty($proposal_data = json_decode(json_encode(\Models\Proposals::where(['project_id' => $request->project_id, 'user_id' => $request->receiver_id, 'type' => 'proposal', 'status' => 'accepted'])->get(),true ) ) ) ){
                        $this->message = "M0121";
                        $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
                    }else if(!empty(json_decode(json_encode(\Models\Reviews::where(['project_id' => $request->project_id, 'sender_id' => $request->user()->id_user ])->get(),true)))){
                        $this->message = "M0329";
                        $this->error = trans(sprintf('general.%s',$this->message));                 
                    }else{
                        $total_average  = ($request->category_two+$request->category_three+$request->category_four+$request->category_five+$request->category_six)/5;
                        
                        $reviewArray = [
                            'project_id'            =>  $request->project_id,
                            'sender_id'             =>  $request->user()->id_user,
                            'receiver_id'           =>  $request->receiver_id,
                            'description'           =>  $request->description,
                            'review_average'        =>  $total_average,
                            'category_two'          =>  $request->category_two,    
                            'category_three'        =>  $request->category_three,
                            'category_four'         =>  $request->category_four,
                            'category_five'         =>  $request->category_five,
                            'category_six'          =>  $request->category_six,
                            'created'               =>  date('Y-m-d H:i:s'),
                            'updated'               =>  date('Y-m-d H:i:s'),
                        ];

                        $isInserted = \Models\Reviews::add_review($reviewArray);
                        
                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => $request->user()->id_user,
                            'user_type'         => 'employer',
                            'action'            => 'webservice-employer-add-review',
                            'reference_type'    => 'users',
                            'reference_id'      => $request->receiver_id
                        ]));

                        $isNotified = \Models\Notifications::notify(
                            $request->receiver_id,
                            $request->user()->id_user,
                            'JOB_REVIEW_REQUEST_BY_EMPLOYER',
                            json_encode([
                                'review_id'     => (string) $isInserted,
                                'project_id'    => (string) $request->project_id,
                            ])
                        );

                        if($isInserted){
                            $this->status   = true;
                            $this->message  = "M0326";
                        }
                    }
                }
            }


            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );                        
        }

        /**
         * [This method is used for profile review] 
         * @param  Request
         * @return Json Response
         */

        public function employer_reviews(Request $request){
            $this->status   = true;
            $page           = (!empty($request->page)) ? $request->page : 1;
            
            $reviews    = \Models\Reviews::defaultKeys()->with([
                'sender' => function($q){
                    $q->select(
                        'id_user'
                    )->name()->companyLogo();
                },
                'receiver' => function($q){
                    $q->select(
                        'id_user'
                    )->name()->companyLogo();
                }
            ])
            ->where('receiver_id',auth()->user()->id_user)
            ->orderBy('id_review','DESC')
            ->limit(DEFAULT_PAGING_LIMIT)
            ->offset(($request->page - 1)*DEFAULT_PAGING_LIMIT)
            ->get();

            $this->jsondata = api_resonse_common($reviews,true);
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );            
        }

        /**
         * [This method is used for reviewed job jist] 
         * @param  Request
         * @return Json Response
         */

        public function job_review_list(Request $request){
            $this->status   = true;
            $page           = (!empty($request->page)) ? $request->page : 1;
            $review_type    = ((!empty($request->review_type)) ? $request->review_type : "by_employers");
            $this->jsondata = \Models\Reviews::employer_reviews($request->user()->id_user,'','by_talents',$page);
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            ); 
        }

        /**
         * [This method is used for user's profile review] 
         * @param  Request
         * @return Json Response
         */

        public function talent_reviews(Request $request){
            $this->status   = true;
            $page           = (!empty($request->page)) ? $request->page : 1;
            
            if(!empty($request->talent_id)){
                $talent_id = $request->talent_id;
            }else{
                $talent_id = auth()->user()->id_user;
            }

            $data['isOwner']        = \Models\companyConnectedTalent::with(['user'])->where('id_user',$talent_id)->where('user_type','owner')->get()->first();
            $data['isOwner']        = json_decode(json_encode($data['isOwner']));

            if(!empty($data['isOwner'])){
                $data['connected_user'] = \Models\companyConnectedTalent::select('id_user')->where('id_talent_company',$data['isOwner']->id_talent_company)->where('user_type','user')->get();
                $data['connected_user'] = json_decode(json_encode($data['connected_user']),true);
            }else{
                $data['connected_user'] = [];
            }
            $talent_ids[] = $talent_id;
            $user_ids = array_column($data['connected_user'], 'id_user');
            $user_ids = array_merge($user_ids,$talent_ids);
            
            $reviews    = \Models\Reviews::defaultKeys()->with([
                'sender' => function($q){
                    $q->select(
                        'id_user'
                    )->name()->companyLogo();
                },
                'receiver' => function($q){
                    $q->select(
                        'id_user'
                    )->name()->companyLogo();
                }
            ])
            ->whereIn('receiver_id',$user_ids)
            ->orderBy('id_review','DESC')
            ->limit(DEFAULT_PAGING_LIMIT)
            ->offset(($request->page - 1)*DEFAULT_PAGING_LIMIT)
            ->get();

            $this->jsondata = api_resonse_common($reviews,true);
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to hire premium user's] 
         * @param  Request
         * @return Json Response
         */

        public function hire_premium_talents(Request $request){
            $this->status   = true;
            $language       = \App::getLocale();
            $search         = " 1 ";
            $html           = "";
            $page           = (!empty($request->page))?$request->page:1;
            $sort           = "";

            if(empty($request->experience_min_filter) && !empty($request->experience_min_filter)){
                $this->message = "M0374";
            }else if(!empty($request->experience_min_filter) && empty($request->experience_min_filter)){
                $this->message = "M0375";
            }else if($request->experience_min_filter > $request->experience_max_filter){
                $this->message = "M0376";
            }else {
                if($request->sortby_filter){
                    $sort       = ___decodefilter($request->sortby_filter);
                }

                if(empty($request->temporary_salary_low_filter)){
                    $request->temporary_salary_low_filter = 20;
                }

                if(empty($request->temporary_salary_high_filter)){
                    $request->temporary_salary_high_filter = 5000;
                }

                if(empty($request->permanent_salary_low_filter)){
                    $request->permanent_salary_low_filter = 0;
                }

                if(empty($request->permanent_salary_high_filter)){
                    $request->permanent_salary_high_filter = 15000000;
                }

                if(!empty($request->employment_type_filter)){
                    $search .= sprintf(" AND {$this->prefix}talent_interests.interest IN ('%s') ",implode("','", $request->employment_type_filter));
                }

                if(!empty($request->experience_min_filter) && !empty($request->experience_max_filter)){
                    $search .= sprintf(" AND CAST({$this->prefix}users.experience AS DECIMAL(10,6)) BETWEEN %s AND %s ",$request->experience_min_filter,$request->experience_max_filter);
                }

                $search .= sprintf(" AND (
                    (
                        {$this->prefix}users.expected_salary >= {$request->permanent_salary_low_filter}
                        AND
                        {$this->prefix}users.expected_salary <= {$request->permanent_salary_high_filter}
                    )
                )");

                if(!empty($request->permanent_salary_low_filter) && !empty($request->permanent_salary_high_filter)){
                    $search .= sprintf(" AND {$this->prefix}users.expected_salary >= {$request->permanent_salary_low_filter} AND {$this->prefix}users.expected_salary <= {$request->permanent_salary_high_filter} ");
                }

                if(!empty($request->expertise_filter)){
                    $search .= sprintf(" AND {$this->prefix}users.expertise IN ('%s') ", implode("','",$request->expertise_filter));
                }

                if(!empty($request->industry_filter)){
                    $search .= sprintf(" AND {$this->prefix}users.industry = {$request->industry_filter} ");
                }

                if(!empty($request->subindustry_filter)){
                    $search .= sprintf(" AND {$this->prefix}users.subindustry = {$request->subindustry_filter} ");
                }

                if(!empty($request->skills_filter)){
                    $search .= sprintf(" AND {$this->prefix}talent_skills.skill IN ('%s') ",implode("','", $request->skills_filter));
                }

                if(!empty($request->state_filter)){
                    $search .= sprintf(" AND {$this->prefix}users.city IN (%s) ",implode(",", $request->state_filter));
                }

                if(!empty($request->search)){
                    $search .= sprintf(" AND
                        (
                            {$this->prefix}users.name like '%%{$request->search}%%'
                            OR
                            {$this->prefix}talent_skills.skill like '%%{$request->search}%%'
                        )
                    ");
                }

                if(!empty($request->saved_talent_filter)){
                    $search .= sprintf(" AND {$this->prefix}saved_talent.id_saved IS NOT NULL");
                }

                if(!empty(trim($request->__search))){
                    $search .= sprintf(" AND
                        (
                            {$this->prefix}users.name like '%%{$request->__search}%%'
                            OR
                            {$this->prefix}talent_skills.skill like '%%{$request->__search}%%'
                        )
                    ");
                }

                $keys = [
                    'users.id_user',
                    'users.type',
                    \DB::raw("CONCAT(IFNULL({$this->prefix}users.first_name,''),' ',IFNULL({$this->prefix}users.last_name,'')) as name"),
                    'users.gender',
                    'users.country',
                    'users.workrate',
                    'users.experience',
                    \DB::Raw("IF((`{$this->prefix}countries`.`{$language}` != ''),`{$this->prefix}countries`.`{$language}`, `{$this->prefix}countries`.`en`) as country_name"),
                    \DB::Raw("IF(({$this->prefix}city.{$language} != ''),{$this->prefix}city.`{$language}`, {$this->prefix}city.`en`) as city_name"),
                    \DB::raw("CONCAT('".url('/')."/',{$this->prefix}files.folder,{$this->prefix}files.filename) as file"),
                    \DB::Raw("IF(({$this->prefix}industries.{$language} != ''),{$this->prefix}industries.`{$language}`, {$this->prefix}industries.`en`) as industry_name"),
                    \DB::Raw("IF(({$this->prefix}subindustries.{$language} != ''),{$this->prefix}subindustries.`{$language}`, {$this->prefix}subindustries.`en`) as subindustry"),
                    \DB::raw('"0" as job_completion'),
                    \DB::raw('"0" as availability_hours'),
                    'users.expertise',
                    \DB::raw("(SELECT GROUP_CONCAT(t.skill) FROM {$this->prefix}talent_skills as t WHERE t.user_id = {$this->prefix}users.id_user) as skills"),
                    \DB::Raw("IF({$this->prefix}saved_talent.id_saved IS NOT NULL,'".DEFAULT_YES_VALUE."','".DEFAULT_NO_VALUE."') as is_saved"),
                    \DB::raw('"0.0" as rating'),
                    \DB::raw('"0" as review'),
                ];

                $talents =  \Models\Employers::find_premium_talents($request->user(),'all',$search,$page,$sort,$keys);

                if(!empty($talents['result'])){
                    array_walk($talents['result'], function(&$item){
                        if(!empty($item['expertise'])){
                            $item['expertise'] = expertise_levels($item['expertise']);
                        }else{
                            $item['expertise'] = "";
                        }
                        
                        if(!empty($item['skills'])){
                            $item['skills'] = explode(',',$item['skills']);
                        }else{
                            $item['skills'] = [];
                        }

                        $item['gender'] = ucfirst($item['gender']);
                    });

                    $this->status = true;
                    $this->jsondata = $talents['result'];
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }  

        /**
         * [This method is used for user's work history] 
         * @param  Request
         * @return Json Response
         */ 

        public function talent_work_history(Request $request){
            
            if(empty($request->page)){
                $request->page = 1;
            }

            $talent     = (object)['id_user' => $request->talent_id];
            $projects   = \Models\Projects::talent_jobs($talent)->with(['proposal'])->whereHas('proposal',function($q) use($talent){
                $q->where('talent_proposals.status','accepted');
                $q->where('talent_proposals.user_id',$talent->id_user);
            })
            ->having('project_status','=','closed')
            ->orderBy("projects.created","DESC")
            ->groupBy(['projects.id_project'])
            ->limit(DEFAULT_PAGING_LIMIT)
            ->offset(($request->page - 1)*DEFAULT_PAGING_LIMIT)
            ->get();

            $this->jsondata = api_resonse_project($projects);
            $this->status   = true;
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used for raise dispute detail]
         * @param  Request
         * @return Json Response
         */

        public function raise_dispute_detail(Request $request){
            $project_id = $request->project_id;

            if(!empty($project_id)){
                $project     = \Models\Projects::defaultKeys()->with([
                    'proposal' => function($q){
                        $q->defaultKeys()->where('talent_proposals.status','accepted')->with([
                            'talent' => function($q){
                                $q->defaultKeys();
                            }
                        ]);
                    },
                    'dispute' => function($q){
                        $q->defaultKeys()->with([
                            'sender' => function($q){
                                $q->defaultKeys();
                            },
                            'concern' => function($q){
                                $q->defaultKeys();
                            },
                            'comments' => function($q){
                                $q->defaultKeys()->with([
                                    'files'  => function($q){
                                        $q->where('type','disputes');
                                    },
                                    'sender' => function($q){
                                        $q->defaultKeys();
                                    }, 
                                ]);
                            }, 
                        ]);
                    }
                ])
                ->where('id_project',$project_id)
                ->get()
                ->first();

                if(!empty($project->dispute)){
                    $raise_dispute_index            = array_search($project->dispute->type, \Models\Listings::raise_dispute_type_column());
                    $project->dispute->step = (string) $raise_dispute_index;
                    
                    if($project->dispute->last_commented_by == auth()->user()->id_user || $project->dispute->type == 'receiver-final-comment' || $project->dispute->type == 'closed'){
                        $project->dispute->can_reply    = DEFAULT_NO_VALUE;
                        $project->dispute->time_left    = "";
                    }else{
                        $project->dispute->can_reply    = DEFAULT_YES_VALUE;
                        $project->dispute->time_left    = time_difference(date("Y-m-d H:i:s"),date("Y-m-d H:i:s",strtotime($project->dispute->last_updated." +".constant("RAISE_DISPUTE_STEP_".($raise_dispute_index+1)."_HOURS_LIMIT")." hour")));
                    }

                    if($project->dispute->time_left === '00:00:00'){
                        $project->dispute->can_reply    = DEFAULT_NO_VALUE;
                    }

                    if(!empty($project->dispute) && !empty($project->dispute->comments)){
                        foreach ($project->dispute->comments as $comment) {
                            if(!empty($comment)){
                                foreach ($comment->files as $file) {
                                    $file->file_url = asset(sprintf("%s%s",$file->folder,$file->filename));
                                }
                            }
                        }
                    }
                }

                $this->jsondata = api_resonse_common($project);
                $this->status = true;
            }else{
                $this->message = 'M0121';
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for raise dispute] 
         * @param  Request
         * @return Json Response
         */

        public function raise_dispute(Request $request){
            $project_id         = $request->project_id;
            $sender_id          = auth()->user()->id_user;
            $sender_type        = auth()->user()->type;
            
            $user               = auth()->user();
            $project            = \Models\Projects::where('id_project',$project_id)->with([
                'proposal' => function($q) use($user){
                    $q->defaultKeys()->where('talent_proposals.status','=','accepted')->with([
                        'talent' => function($q){
                            $q->defaultKeys();
                        }
                    ]);
                },
            ])->defaultKeys()->get()->first();
            
            if(1/*$project->is_disputable == DEFAULT_YES_VALUE*/){
                $dispute_detail     = \Models\RaiseDispute::detail($project_id,$sender_id);
                
                $validator = \Validator::make($request->all(),[
                    'project_id'                => ['required'],
                    'comment'                   => validation('rasie_dispute_reason')
                ],[
                    'reason.required'           => 'M0539',
                    'comment.required'          => 'M0384',
                    'comment.string'            => 'M0385',
                    'comment.regex'             => 'M0385',
                    'comment.max'               => 'M0386',
                    'comment.min'               => 'M0387',
                    'project_id.required'       => 'M0121',
                ]);

                $validator->sometimes(['reason'], validation('rasie_dispute_reason_id'), function($input) use($dispute_detail){
                    return (!empty($dispute_detail) && $dispute_detail->next_type == 'sender-comment')?true:false;
                });

                $validator->after(function($validator) use($dispute_detail,$sender_id){
                    if(empty($validator->errors()->first()) && (strtotime($dispute_detail->duration) >= strtotime(date('Y-m-d')) && $dispute_detail->last_commented_by == $sender_id)){
                        $validator->errors()->add('comment','M0546');
                    }
                });

                if($validator->fails()){
                    $this->message = $validator->messages()->first();
                }else{
                    if(1){
                        $next_type = $dispute_detail->next_type;
                        if(empty($dispute_detail->id_raised_dispute)){
                            $raiseArray = [
                                'project_id'        => $project_id,
                                'disputed_by'       => $sender_id,
                                'disputed_by_type'  => $sender_type,
                                'last_commented_by' => $sender_id,
                                'last_updated'      => date('Y-m-d H:i:s'),
                                'reason'            => $request->reason,
                                'type'              => $dispute_detail->next_type,
                                'updated'           => date('Y-m-d H:i:s'),
                                'created'           => date('Y-m-d H:i:s'),
                            ];

                            $isDisputed     = \Models\RaiseDispute::submit($raiseArray);
                            $dispute_detail = \Models\RaiseDispute::detail($project_id,$sender_id);

                            $isNotified = \Models\Notifications::notify(
                                $project->proposal->talent->id_user,
                                $sender_id,
                                'JOB_RAISE_DISPUTE_RECEIVED',
                                json_encode([
                                    'project_id'    => (string) $project_id,
                                ])
                            );
                        }else{
                            $isUpdated = \Models\RaiseDispute::where('id_raised_dispute',$dispute_detail->id_raised_dispute)->update(['type' => $dispute_detail->next_type,'last_updated' => date('Y-m-d H:i:s'),'last_commented_by' => $sender_id,'updated' => date('Y-m-d H:i:s')]);  
                            $isDisputed = $dispute_detail->id_raised_dispute;

                            $isNotified = \Models\Notifications::notify(
                                $project->proposal->talent->id_user,
                                $sender_id,
                                'JOB_RAISE_DISPUTE_RECEIVED_REPLY',
                                json_encode([
                                    'project_id'    => (string) $project_id,
                                ])
                            );
                        }

                        $commentArray = [
                            'dispute_id'    => $isDisputed,
                            'sender_id'     => $sender_id,
                            'comment'       => $request->comment,
                            'type'          => $next_type,
                            'updated'       => date('Y-m-d H:i:s'),
                            'created'       => date('Y-m-d H:i:s'),
                        ];

                        $isCommentCreated = \Models\RaiseDisputeComments::submit($commentArray);
                        
                        if(!empty($request->dispute_documents)){
                            \Models\File::whereIn('id_file',explode(",", str_replace(" ", "", $request->dispute_documents)))->where('user_id',$sender_id)->where('type','disputes')->update(['record_id' => $isCommentCreated]);
                        }

                        if(!empty($isDisputed)){
                            $this->status   = true;
                            $this->message  = "M0484";
                            $this->redirect = url(sprintf("%s/project/dispute/details?job_id=%s",auth()->user()->type,___encrypt($project_id)));
                        }else{
                            $this->message = "M0121";
                        }

                        $this->jsondata = \Models\RaiseDispute::where('id_raised_dispute',$isDisputed);
                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => \Auth::user()->id_user,
                            'user_type'         => 'employer',
                            'action'            => 'raise-dispute',
                            'reference_type'    => 'project',
                            'reference_id'      => $isDisputed
                        ]));
                    }else{
                        $this->message = 'M0564';
                    }
                }
            }else{
                $this->message = 'M0540';
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );        
        }

        /**
         * [This method is used to add payment card] 
         * @param  Request
         * @return Json Response
         */

        public function payment_add_card(Request $request){
            $request->request->add(['number' => str_replace(" ", "", $request->number)]);

            if(empty($this->post['expiry_month'])){
                $this->post['expiry_month'] = "";
            }else{
                $this->post['expiry_month'] = (string)$this->post['expiry_month'];
            }

            $validator = \Validator::make($request->all(), [
                'card_type'        => validation('card_type'),
                'cardholder_name'  => validation('name'),
                'expiry_month'     => validation('expiration_month'),
                'expiry_year'      => validation('expiration_year'),
                'number'           => validation('card_number'),
                'cvv'              => validation('cvv')
            ],[
                'card_type.required'                        => 'M0547',
                'card_type.string'                          => 'M0548',
                'card_type.validate_card_type'              => 'M0549',
                'cardholder_name.required'                  => 'M0396',
                'cardholder_name.string'                    => 'M0401',
                'cardholder_name.regex'                     => 'M0401',
                'cardholder_name.max'                       => 'M0402',
                'number.required'                           => 'M0403',
                'number.string'                             => 'M0403',
                'number.regex'                              => 'M0403',
                'number.max'                                => 'M0404',
                'number.min'                                => 'M0405',
                'expiry_month.required'                     => 'M0398',
                'expiry_month.string'                       => 'M0406',
                'expiry_month.validate_expiry_month'        => 'M0498',
                'expiry_year.required'                      => 'M0399',
                'expiry_year.integer'                       => 'M0407',
                'cvv.required'                              => 'M0400',
                'cvv.string'                                => 'M0409',
                'cvv.regex'                                 => 'M0409',
                'cvv.max'                                   => 'M0410',
                'cvv.min'                                   => 'M0411',
            ]);
            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $creditCard = [
                    'card_type'         => $request->card_type,
                    'number'            => $request->number,
                    'expiry_month'      => $request->expiry_month,
                    'expiry_year'       => $request->expiry_year,
                    'cvv'               => $request->cvv,
                    'cardholder_name'   => $request->cardholder_name,
                ];

                if(!empty($request->save_card) && $request->save_card == 'off'){
                    $isCardCreated = \Models\PaypalPayment::create_credit_card($creditCard, false);
                    
                    if($isCardCreated['status'] == true){
                        \Session::set('card_token',$isCardCreated['card']);
                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => $request->user()->id_user,
                            'user_type'         => 'employer',
                            'action'            => 'webservice-employer-payment-add-card-without-save',
                            'reference_type'    => 'users',
                            'reference_id'      => $request->user()->id_user
                        ]));                    
                        $this->status   = $isCardCreated['status'];
                        $this->message  = $isCardCreated['message'];
                        $this->jsondata = $isCardCreated['card'];
                    }else{
                        $this->message = $isCardCreated['message'];
                    }
                }else{
                    $isCardCreated = \Models\PaypalPayment::create_credit_card($request,true);
                    if($isCardCreated['status'] == true){
                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => $request->user()->id_user,
                            'user_type'         => 'employer',
                            'action'            => 'webservice-employer-payment-add-card',
                            'reference_type'    => 'users',
                            'reference_id'      => $request->user()->id_user
                        ]));

                        $this->message  = $isCardCreated['message'];
                        $this->status   = $isCardCreated['status'];
                        $this->jsondata = $isCardCreated['card']; 
                    }else{
                        $this->message = $isCardCreated['message'];
                    }
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            ); 
        }

        /**
         * [This method is used to manage payment card] 
         * @param  Request
         * @return Json Response
         */

        public function payment_manage_card(Request $request){

            $data['cards']      = \Models\PaypalPayment::get_user_card($request->user()->id_user,"","array",[
                'user_card_paypal.id_card',
                'user_card_paypal.default',
                'user_card_paypal.type',
                \DB::raw("CONCAT('".asset('/')."','',{$this->prefix}card_type.image) as image_url"),
                'user_card_paypal.masked_number',
            ]);
            
            if(!empty($data['cards'])){
                $this->status       = true;
                $this->jsondata     = $data;
            }
            
            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );             
        }

        /**
         * [This method is used to select payment card] 
         * @param  Request
         * @return Json Response
         */

        public function payment_select_card(Request $request){

            if(empty($request->card_id)){
                $this->message = "M0121";
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'card_id');   
            }else{
                $isMadeDefault  = \Models\PaypalPayment::mark_card_default($request->user()->id_user,$request->card_id);
                if(!empty($isMadeDefault)){
                    
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'employer',
                        'action'            => 'webservice-employer-default-payment-card',
                        'reference_type'    => 'user_card',
                        'reference_id'      => $request->card_id
                    ]));

                    $this->status   = true;
                    $this->message  = "M0393";
                    $this->jsondata = $isMadeDefault;
                }else{
                    $this->status   = false;
                }
            }
            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to delete payment card] 
         * @param  Request
         * @return Json Response
         */

        public function payment_delete_card(Request $request){
            
            if(empty($request->card_id)){
                $this->message = "M0121";
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'card_id');   
            }else{
                $data['user_card']  = \Models\PaypalPayment::get_user_card($request->user()->id_user,$request->card_id,'first',['card_token']);
                if($data['user_card']){
                    $isDeleted      = \Models\PaypalPayment::delete_card($data['user_card']['card_token'],$request->card_id);
                    $isMadeDefault  = \Models\PaypalPayment::mark_card_default($request->user()->id_user);

                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'employer',
                        'action'            => 'webservice-employer-delete-payment-card',
                        'reference_type'    => 'user_card',
                        'reference_id'      => $request->card_id
                    ]));

                    if($isDeleted){
                        $this->status = true;
                        $this->message  = "M0419";
                    }
                }else{
                    $this->message = "M0121";
                    $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'card_id');   
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        } 

        /**
         * [This method is used for payment checkout] 
         * @param  Request
         * @return Json Response
         */

        public function payment_checkout(Request $request){

            $project                = \Models\Projects::defaultKeys()->companyName()->projectPrice()->where('id_project',$request->project_id)->get()->first();
            $proposal               = \Models\Employers::get_proposal($request->proposal_id,['quoted_price']);
            $number_of_days         = ___get_total_days($project['startdate'],$project['enddate']);
            $proposal_id            = ___decrypt($request->proposal_id);
            $default_card_detail    = \Models\PaypalPayment::get_user_default_card($request->user()->id_user,[
                'id_card',
                'default',
                \DB::raw("CONCAT('".asset('/')."','',{$this->prefix}card_type.image) as image"),
                'masked_number',
                'card_token',
                'paypal_payer_id'
            ]);
            $project = json_decode(json_encode($project),true);
            
            if($project['startdate'] >= date('Y-m-d')){
                $proposal['price_unit'] = \Cache::get('currencies')[\Cache::get('default_currency')];
                if($project['employment'] == 'hourly'){
                    $sub_total                  = $proposal['quoted_price']*$proposal['decimal_working_hours']*$number_of_days;
                }else if($project['employment'] == 'monthly'){
                    $sub_total                  = $proposal['quoted_price']*(($number_of_days/MONTH_DAYS));
                }else if($project['employment'] == 'fixed'){
                    $sub_total                  = $proposal['quoted_price'];
                }

                $commission                     = ___calculate_commission($sub_total,$request->user()->commission, $request->user()->commission_type);
                $paypal_commission              = ___calculate_paypal_commission($sub_total);
                $proposal['quoted_price']= ___format($proposal['quoted_price'],true,false);
                $proposal['quoted_price']       = ___format($proposal['quoted_price'],true,false);
                $project['price_unit']          = \Cache::get('currencies')[request()->currency];
                $project['price']               = ___format($project['price'],true,false);
                $project['created']             = ___d($project['created']);
                
                /*Check if accept escrow is true or false for this proposal*/
                $project['checkPayoutMgmt'] = (bool)0;
                $data_proposal           = \Models\Employers::get_proposal($proposal_id);
                $project['checkPayoutMgmt'] = $data_proposal['accept_escrow']=='no' ? true : false;

                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'employer',
                    'action'            => 'webservice-employer-payment-checkout',
                    'reference_type'    => 'projects',
                    'reference_id'      => $request->project_id
                ]));
                

                $this->status               = true;
                $this->jsondata             = [
                    'project'               => $project,
                    'proposal'              => $proposal,
                    'number_of_days'        => $number_of_days,
                    'default_card_detail'   => $default_card_detail,
                    'checkout'              => [
                        'total'             => ___format($sub_total+$commission+$paypal_commission,true,false),
                        'subtotal'          => ___format($sub_total,true,false),
                        'commission'        => $commission,
                        'paypal_commission' => $paypal_commission,
                    ]
                ];
            }else{
                $this->message = "M0351";
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        }  

        /**
         * [This method is used for payment confirmation] 
         * @param  Request
         * @return Json Response
         */

        public function payment_confirm(Request $request){
            if(!empty($request->project_id)){
                $project    = \Models\Projects::defaultKeys()->companyName()->projectPrice()->where('id_project',$request->project_id)->get()->first();
                if(!empty($project)){
                    $is_payment_already_captured = \Models\Payments::is_payment_already_escrowed($request->project_id);
                    if(empty($is_payment_already_captured)){
                        $proposal               = \Models\Employers::get_proposal($request->proposal_id,['quoted_price']);
                        $number_of_days         = ___get_total_days($project['startdate'],$project['enddate']);
                        
                        if(!empty($proposal)){
                            if($project['startdate'] >= date('Y-m-d')){
                                $is_recurring       = false;
                                $repeat_till_month  = 0;
                                if($project['employment'] == 'hourly'){
                                    $sub_total                  = $proposal['quoted_price']*$proposal['decimal_working_hours']*$number_of_days;
                                }else if($project['employment'] == 'monthly'){
                                    $sub_total                  = ($proposal['quoted_price']/MONTH_DAYS)*$number_of_days;
                                    $is_recurring               = ($number_of_days > MONTH_DAYS) ? true : false;
                                    $repeat_till_month          = ($number_of_days)/MONTH_DAYS;
                                }else if($project['employment'] == 'fixed'){
                                    $sub_total                  = $proposal['quoted_price'];
                                }

                                $commission                     = ___calculate_commission($sub_total,$request->user()->commission, $request->user()->commission_type);
                                $paypal_commission              = ___calculate_paypal_commission($sub_total);
                                $payment                       = [
                                    'transaction_user_id'               => (string) $request->user()->id_user,
                                    'transaction_company_id'            => (string) $request->user()->id_user,
                                    'transaction_user_type'             => $request->user()->type,
                                    'transaction_project_id'            => $request->project_id,
                                    'transaction_proposal_id'           => $request->proposal_id,
                                    'transaction_total'                 => $sub_total+$commission+$paypal_commission,
                                    'transaction_subtotal'              => $sub_total,
                                    'transaction_type'                  => 'debit',
                                    'transaction_date'                  => date('Y-m-d H:i:s'),
                                    'transaction_commission'            => $commission,
                                    'transaction_paypal_commission'     => $paypal_commission,
                                    'transaction_is_recurring'          => $is_recurring,
                                    'transaction_repeat_till_month'     => $repeat_till_month
                                ];

                                $payment['transaction_source']       = 'paypal';
                                $payment['transaction_reference_id'] =  '';
                                $payment['transaction_status']       = 'initiated';

                                $transaction = \Models\Payments::init_employer_payment($payment);

                                /*Redirect to mobile web view using this URL for Paypal EC*/
                                $this->status   = true; 
                                $this->jsondata = [
                                    'url'           => url(sprintf('mobile-express-checkout?token=%s&project_id=%s&proposal_id=%s&transaction_id=%s&user_id=%s&currency=%s',$request->user()->remember_token,___encrypt($request->project_id),___encrypt($request->proposal_id),___encrypt($transaction->id_transactions),___encrypt($request->user_id),request()->currency )),
                                    'payment' => $payment
                                ];

                                // if($request->payment_type == 'card_payment'){
                                //     if(empty($request->card_token)){
                                //         $card_details = \Models\PaypalPayment::get_user_default_card($request->user()->id_user,['card_token','paypal_payer_id']);
                                //         $card_token   = $card_details['card_token'];
                                //         $card_details = [
                                //             'card_token'        => $card_token,
                                //             'paypal_payer_id'   => auth()->user()->paypal_payer_id,
                                //             'amount'            => ___rounding($payment['transaction_total'])
                                //         ];
                                //     }else{
                                //         $card_details = [
                                //             'card_token'        => $request->card_token,
                                //             'paypal_payer_id'   => auth()->user()->paypal_payer_id,
                                //             'amount'            => ___rounding($payment['transaction_total'])
                                //         ]; 
                                //     }
                                //     if(!empty($card_details['card_token'] && !empty($card_details['paypal_payer_id']) && !empty($card_details['amount']))){
                                //         $result = \Models\PaypalPayment::payment_checkout($card_details,$is_recurring,$repeat_till_month,'mobile');
                                //         if($result['status'] == true){

                                //             if($result['transaction_type'] == "recurring"){

                                //                 $return_payments = $payment;

                                //                 $return_payments['transaction_type']=$result['transaction_type']; 
                                //                 $return_payments['redirect_link']=$result['redirect_link']; 
                                //                 $return_payments['recurrsive_success_url']=url(sprintf("/payment/paypal-billing-success")); 
                                //                 $return_payments['recurrsive_cancel_url']=url(sprintf("/payment/paypal-billing-cancel")); 

                                //                 $this->message      = 'this is test message';
                                //                 $this->status       = true;
                                //                 $this->jsondata     = $return_payments;

                                //             }else{
                                //                 $transaction_Data = (array)((array)array_column($result['payment_data']['transactions'], 'related_resources') [0] );
                                //                 $payment['transaction_source']          = 'paypal';
                                //                 $payment['transaction_reference_id']    = $transaction_Data[0]->sale->id;
                                //                 $payment['transaction_status']          = 'confirmed';
                                //             }

                                //         }else{
                                //             $payment['transaction_status']          = 'failed';
                                //         }
                                //         $transaction = \Models\Payments::init_employer_payment($payment);


                                //         /* RECORDING ACTIVITY LOG */
                                //         event(new \App\Events\Activity([
                                //             'user_id'           => $request->user()->id_user,
                                //             'user_type'         => 'employer',
                                //             'action'            => 'webservice-employer-payment-confirm',
                                //             'reference_type'    => 'projects',
                                //             'reference_id'      => $request->project_id
                                //         ]));

                                //         if(!empty($transaction_Data[0]->sale->parent_payment)){
                                //             $isProposalAccepted =  \Models\Employers::accept_proposal($request->user()->id_user,$payment['transaction_project_id'],$payment['transaction_proposal_id']);
                                //             $this->message      = $isProposalAccepted['message'];
                                //             $this->status       = true;
                                //             $this->jsondata     = $payment;
                                //         }else{
                                //             $this->message      = "M0550";
                                //         }
                                //     }else{
                                //         $this->message      = "M0595";
                                //     }
                                // }else if($request->payment_type == 'express_payment'){
                                //     $this->status   = true; 
                                //     $this->jsondata = [
                                //         'url'           => url(sprintf('paypal-express-checkout?token=%s&project_id=%s&proposal_id=%s',$request->user()->remember_token,___encrypt($request->project_id),___encrypt($request->proposal_id))),
                                //         'success_url'   => url(sprintf('paypal-payment-success')),
                                //         'cancel_url'   => url(sprintf('paypal-payment-cancel'))
                                //     ];
                                // }else{
                                //     $this->message      = "M0595";
                                // }

                            }else{
                                $this->message = "M0351";
                            }
                        }else{
                            $this->message = 'M0121';
                            $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'proposal_id');
                        }
                    }else{
                        $this->message = 'M0502';
                    }
                }else{
                    $this->message = 'M0121';
                    $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
                }
            }else{
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        }   

        /**
         * [This method is used for setting] 
         * @param  Request
         * @return Json Response
         */

        public function settings(Request $request){
            $this->status       = true;
            $this->jsondata     = \Models\Settings::fetch($request->user()->id_user,$request->user()->type);
            
            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        } 

        /**
         * [This method is used to save settings] 
         * @param  Request
         * @return Json Response
         */  

        public function savesettings(Request $request){

            if(!empty($request->email)){
                foreach ($request->email as $item) {
                    if(!empty($item['status'] == DEFAULT_YES_VALUE)){
                        $data['email'][] = $item['setting'];
                    }
                }
            }

            if(!empty($request->mobile)){
                foreach ($request->mobile as $item) {
                    if(!empty($item['status'] == DEFAULT_YES_VALUE)){
                        $data['mobile'][] = $item['setting'];
                    }
                }
            }

            if(!empty($data)){
                $setting    = \Models\Settings::fetch(auth()->user()->id_user,auth()->user()->type);
                $isUpdated      = \Models\Settings::add($request->user()->id_user,$data,$setting);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'employer',
                    'action'            => 'webservice-employer-save-settings',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));
                
                $this->status   = true;
                $this->message  = 'M0426';
            }
            
            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for plan features] 
         * @param  Request
         * @return Json Response
         */
        
        public function plan_features(Request $request){
            $language           = \App::getLocale();
            $data['plan']       = \Models\Plan::getPlanList();
            array_walk($data['plan']['plan'],function(&$item){
                $item['price'] = ___format($item['price'],true,true);
            });

            $keys = [
                'id_feature',
                'status',
                \DB::raw("IF({$language} != '',{$language},en) as name")
            ];
            $data['features']   = \Models\Plan::getFeatures('array',$keys);
            $this->status       = true;
            $this->jsondata     = $data;

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to upgrade member checkout] 
         * @param  Request
         * @return Json Response
         */

        public function upgrade_member_checkout(Request $request){
            $plan  = \Models\Plan::getPlanDetail($request->id_plan);
            $plan  = json_decode(json_encode($plan), true);

            $default_card_detail    = \Models\Payments::get_user_default_card($request->user()->id_user,[
                'id_card',
                'default',
                'image_url',
                'masked_number',
            ]);
            $plan_payment = [
                'transaction_user_id'       => (string) $request->user()->id_user,
                'transaction_user_type'     => $request->user()->type,
                'transaction_plan_id'       => $request->id_plan,
                'transaction_total'         => $plan['price'],
                'transaction_type'          => 'subscription',
                'transaction_date'          => date('Y-m-d H:i:s'),
                'price_unit'                => '$'
            ];

            if($default_card_detail){

                $this->status               = true;
                $this->jsondata             = [
                    'default_card_detail'   => $default_card_detail,
                    'plan'                  => $plan_payment
                ];

            }else{
                $this->message = "M0437";
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to upgrade member payment confirmation] 
         * @param  Request
         * @return Json Response
         */

        public function upgrade_member_payment_confirm(Request $request){

            if(!empty($request->id_plan)){
                $plan = \Models\Plan::getPlanDetail($request->id_plan);
                $plan = json_decode(json_encode($plan), true);
                if(!empty($plan)){
                    $payment                        = [
                        'transaction_user_id'       => (string) $request->user()->id_user,
                        'transaction_user_type'     => $request->user()->type,
                        'transaction_project_id'       => $request->id_plan,
                        'transaction_total'         => $plan['price'],
                        'transaction_type'          => 'subscription',
                        'transaction_date'          => date('Y-m-d H:i:s'),
                    ];
                    if(empty($request->card_token)){
                        $card_details = \Models\Payments::get_user_card($request->user()->id_user, $request->card_id, 'first',['token']);
                        $card_token   = $card_details['token'];
                    }else{
                        $card_token   = $request->card_token;
                    }

                    if(!empty($card_token)){
                        $result = \Braintree_Subscription::create([
                            'planId' => $plan['braintree_plan_id'],
                            'merchantAccountId' => env('BRAINTREE_MERCHANT_ACCOUNT_ID'),
                            'paymentMethodToken' => $card_token
                        ]);

                        if(!empty($result->success)){
                            $subscriptionData = [
                                'id_plan'                   => $request->id_plan,
                                'id_user'                   => $request->user()->id_user,
                                'balance'                   => $result->subscription->balance,
                                'billingDayOfMonth'         => $result->subscription->billingDayOfMonth,
                                'currentBillingCycle'       => $result->subscription->currentBillingCycle,
                                'daysPastDue'               => $result->subscription->daysPastDue,
                                'failureCount'              => $result->subscription->failureCount,
                                'firstBillingDate'          => $result->subscription->firstBillingDate->format('Y-m-d H:i:s'),
                                'id'                        => $result->subscription->id,
                                'merchantAccountId'         => $result->subscription->merchantAccountId,
                                'neverExpires'              => $result->subscription->neverExpires,
                                'nextBillAmount'            => $result->subscription->nextBillAmount,
                                'nextBillingPeriodAmount'   => $result->subscription->nextBillingPeriodAmount,
                                'nextBillingDate'           => $result->subscription->nextBillingDate->format('Y-m-d H:i:s'),
                                'numberOfBillingCycles'     => $result->subscription->numberOfBillingCycles,
                                'paidThroughDate'           => $result->subscription->paidThroughDate->format('Y-m-d H:i:s'),
                                'paymentMethodToken'        => $result->subscription->paymentMethodToken,
                                'planId'                    => $result->subscription->planId,
                                'price'                     => $result->subscription->price,
                                'status'                    => $result->subscription->status,
                                'trialDuration'             => $result->subscription->trialDuration,
                                'trialDurationUnit'         => $result->subscription->trialDurationUnit,
                                'trialPeriod'               => $result->subscription->trialPeriod,
                                'updated'                   => date('Y-m-d H:i:s'),
                                'created'                   => date('Y-m-d H:i:s'),
                            ];
                            \Models\Payments::subscriptionResponse($subscriptionData);
                            
                            /* RECORDING ACTIVITY LOG */
                            event(new \App\Events\Activity([
                                'user_id'           => $request->user()->id_user,
                                'user_type'         => 'employer',
                                'action'            => 'webservice-employer-upgrade-membership-subscription',
                                'reference_type'    => 'users',
                                'reference_id'      => $request->user()->id_user
                            ]));
                        }
                
                
                        if(!empty($result->success)){
                            $payment['transaction_source'] = 'braintree';
                            $payment['transaction_reference_id'] = $result->subscription->transactions[0]->id;
                            $payment['transaction_status'] = 'confirmed';
                        }else{
                            $payment['transaction_status'] = 'failed';
                        }

                        \Models\Payments::braintree_response([
                            'user_id'                   => $request->user()->id_user,
                            'braintree_response_json'   => json_encode((array)$result->subscription),
                            'status'                    => 'false',
                            'type'                      => 'sale',
                            'created'                   => date('Y-m-d H:i:s')
                        ]);

                        $transaction = \Models\Payments::init_employer_payment(
                            $payment,
                            $request->repeat
                        );

                        if(!empty($result->success)){
                            \Models\Users::change(
                                $request->user()->id_user,
                                [
                                'is_subscribed'=>'yes',
                                'braintree_subscription_id'=> $result->subscription->id,
                                ]
                            );

                            $this->message      = "M0501";
                            $this->status       = true;
                            $this->jsondata     = $payment;
                        }
                        else{
                            $this->message      = $result->errors->deepAll()[0]->code;
                        }
                    }else{
                        $this->message      = "M0420";
                    }
                }else{
                    $this->message = 'M0121';
                    $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
                }

            }else{
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for payment in detail] 
         * @param  Request
         * @return Json Response
         */

        public function payment_detail(Request $request){
            
            if(empty($request->id_project)){
                $this->message = "M0121";
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');   
            }else{
                $payment_detail = \Models\Payments::payment_detail($request->id_project);
                $talent_detail  = json_decode(json_encode(\Models\Talents::get_user((object)['id_user' => $payment_detail['talent_id']],true)),true);
                if(empty($payment_detail)){
                    $this->message = "M0121";
                    $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');   
                }else{
                    $payment_detail['price_max']        = ___format($payment_detail['price_max'],true,true);
                    $payment_detail['price']            = ___format($payment_detail['price'],true,true);
                    $payment_detail['amount_agreed']    = ___format($payment_detail['amount_agreed'],true,true);
                    $payment_detail['amount_paid']      = ___format($payment_detail['amount_paid'],true,true);
                    $payment_detail['folder']           = @(string)$payment_detail['folder'];
                    $payment_detail['filename']         = @(string)$payment_detail['filename'];
                    $payment_detail['image_url']        = get_file_url(['folder' => $payment_detail['folder'],"filename"=>$payment_detail['filename']]);


                    if(!empty($talent_detail)){
                        $talent_detail['workrate']          = ___format($talent_detail['workrate'],true,true);
                        $talent_detail['workrate_max']      = ___format($talent_detail['workrate_max'],true,true);
                        $talent_detail['expected_salary']   = ___format($talent_detail['expected_salary'],true,true);
                    }

                    $data['payment_detail']     = $payment_detail;
                    $data['talent_detail']      = $talent_detail;
                    $this->status               = true;
                    $this->jsondata             = $data; 
                }
            }
            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );            
        }

        /**
         * [This method is used to add thumb for device ] 
         * @param  Request
         * @return Json Response
         */

        public function add_thumb_device(Request $request){
            
            $validator = \Validator::make($request->all(), [
                'device_uuid'           => validation('touch_login'),
                'device_type'           => validation('touch_login'),
                'device_name'           => validation('touch_login'),
            ],[
                'device_uuid.required'  => 'M0430',
                'device_type.required'  => 'M0430',
                'device_name.required'  => 'M0430',
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $setting          = @(string)(!empty($this->post['setting']))?$this->post['setting']:'off';
                $device_uuid      = @(string)$this->post['device_uuid'];
                $device_type      = @(string)$this->post['device_type'];
                $device_name      = @(string)$this->post['device_name'];
                
                $isDeviceConfigured =\Models\ThumbDevices::add($request->user()->id_user, $device_uuid, $device_type, $device_name,$setting);
                
                if($request->setting == 'on'){
                    if(!empty($isDeviceConfigured)){
                        $this->message    = 'M0443';
                        $this->status     = true;
                        $this->jsondata   = [];
                    }else{
                        $this->status   = true;
                        $this->message  = 'M0429';
                    }
                }else{
                    $this->status   = true;
                    $this->message  = 'M0448';
                }

            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );  
        }

        /**
         * [This method is used for suggested job] 
         * @param  Request
         * @return Job Response
         */

        public function suggested_jobs(Request $request){
            $this->status = true;
            $this->jsondata = \Models\Projects::employer_jobs($request->user()->id_user,'suggested');
            
            if(empty($this->jsondata->count())){
                $this->status = false;
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );  
        }

        /**
         * [This method is used to hire user's] 
         * @param  Request
         * @return Json Response
         */

        public function hire_talent(Request $request){
            $validator = \Validator::make($request->all(),[
                'project_id'                        => ['required'],
                'hire_talent_message'               => validation('hire_talent_message')
            ],[
                'project_id.required'               => 'M0450',
                'hire_talent_message.required'      => 'M0444',
                'hire_talent_message.string'        => 'M0445',
                'hire_talent_message.regex'         => 'M0445',
                'hire_talent_message.max'           => 'M0446',
                'hire_talent_message.min'           => 'M0447'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $isInvitationSent   = \Models\ProjectInvitations::send($request->user()->id_user, [
                    'talent_id'     => $request->talent_id,
                    'employer_id'   => $request->user()->id_user,
                    'project_id'    => $request->project_id,
                    'message'       => $request->hire_talent_message,
                    'updated'       => date('Y-m-d H:i:s'),
                    'created'       => date('Y-m-d H:i:s')
                ]);

                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'employer',
                    'action'            => 'invite-for-job',
                    'reference_type'    => 'project',
                    'reference_id'      => $request->project_id
                ]));

                $this->status           = true;
                $this->message          = 'M0449';
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );  
        }

        /**
         * [This method is used to change currency] 
         * @param  Request
         * @return Json Response
         */

        public function change_currency(Request $request){
            $currency = !empty($this->post['currency']) ? $this->post['currency'] : DEFAULT_CURRENCY;
            $isUpdated = \Models\Employers::change($request->user()->id_user,[
                'currency' => $currency,
                'updated'  => date('Y-m-d H:i:s')
            ]);
            if($isUpdated){
                $this->status = true;
                $this->message  = "M0470";
            }


            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for job close]
         * @param  Request
         * @return Json Response
         */

        public function project_status(Request $request){
            $status = $request->status;
            $request->request->add(['currency' => \Session::get('site_currency')]);
            
            $project_id = ___decrypt($request->project_id);
            $project    = \Models\Projects::defaultKeys()
            ->with([
                'projectlog' => function($q){
                    $q->select('project_id')->totalTiming()->where('talent_id',auth()->user()->id_user)->groupBy(['project_id']);
                },
                'proposal' => function($q){
                    $q->defaultKeys()->where('talent_proposals.status','accepted');
                }
            ])
            ->where('id_project',(int)$project_id)
            ->get()
            ->first();

            if(empty($project)){
                $this->message = "M0121";
            }else if($status == 'close' && $project->project_status == 'closed' && !empty($project->closedate)){
                $this->message = "M0562";
            }else{
                switch($status){
                    case 'close': {
                        $isUpdated          = \Models\Projects::change([
                            'id_project'        => $project_id,
                            'project_status'    => 'closed'
                        ],[
                            'closedate'         => date('Y-m-d H:i:s'),
                            'updated'           => date('Y-m-d H:i:s')
                        ]);

                        if(!empty($isUpdated)){
                            /* RECORDING ACTIVITY LOG */
                            event(new \App\Events\Activity([
                                'user_id'           => auth()->user()->id_user,
                                'user_type'         => 'talent',
                                'action'            => 'talent-close-job',
                                'reference_type'    => 'projects',
                                'reference_id'      => $project_id
                            ]));

                            $isNotified = \Models\Notifications::notify(
                                $project->proposal->talent->id_user,
                                $project->company_id,
                                'JOB_COMPLETED_BY_EMPLOYER',
                                json_encode([
                                    "employer_id"   => (string) $project->company_id,
                                    "talent_id"     => (string) $project->proposal->talent->id_user,
                                    "project_id"    => (string) $project->id_project
                                ])
                            );

                            $this->status = true;
                        }
                        break;
                    }
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for document Curriculum Vitae ]
         * @param  Request
         * @return Json Response
         */
        
        public function raise_dispute_document(Request $request){
            $validator = \Validator::make($request->all(), [
                "file"            => validation('document'),
            ],[
                'file.validate_file_type'  => 'M0119',
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $folder = 'uploads/disputes/';
                $uploaded_file = upload_file($request,'file',$folder);
                
                $data = [
                    'user_id' => $request->user()->id_user,
                    'record_id' => NULL,
                    'reference' => 'users',
                    'filename' => $uploaded_file['filename'],
                    'extension' => $uploaded_file['extension'],
                    'folder' => $folder,
                    'type' => 'disputes',
                    'size' => $uploaded_file['size'],
                    'is_default' => DEFAULT_NO_VALUE,
                    'created' => date('Y-m-d H:i:s'),
                    'updated' => date('Y-m-d H:i:s'),
                ];

                $isInserted = \Models\Talents::create_file($data,true,true);
                
                if(!empty($isInserted)){
                    if(!empty($isInserted['folder'])){
                        $isInserted['file_url'] = asset(sprintf("%s/%s",$isInserted['folder'],$isInserted['filename']));
                    }

                    $this->jsondata = $isInserted;
                    $this->status   = true;
                    $this->message  = "M0589";
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used existing job]
         * @param  Request
         * @return \Illuminate\Http\Response
         */

        public function existingjob(Request $request){

            $validator = \Validator::make($request->all(), [
                'project_id'            => validation('record_id'),
                'talent_id'             => validation('talent_id')
            ],[
                'project_id.required'   => 'M0591',
                'talent_id.required'    => 'M0592'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{

                $project_id     = $request->project_id;
                $talent_id      = $request->talent_id;
                $employer_id    = $request->user()->id_user;

                $isRequestSent  =\Models\Chats::employer_chat_request($employer_id,$talent_id,$project_id);
                $isInvitationSent = \Models\Projects::is_invitation_sent($employer_id,$talent_id,$project_id);

                if(empty($isInvitationSent)){
                    $isNotified = \Models\Notifications::notify(
                        $talent_id,
                        $employer_id,
                        'JOB_INVITATION_SENT_BY_EMPLOYER',
                        json_encode([
                            'user_id'    => (string) $employer_id,
                            'talent_id'  => (string) $talent_id,
                            'project_id' => (string) $project_id,
                            'group_id'   => (string) $project_id
                        ])
                    );

                    $group_id       = \Models\Chats::getChatRoomGroupId($talent_id,$employer_id,$project_id);
                    $isSaved = \Models\Chats::addmessage([
                        'message'      => trans('website.W0836'),
                        'sender_id'    => $employer_id,
                        'receiver_id'  => $talent_id,
                        'message_type' => 'text',
                        'group_id'     => $group_id
                    ]);
                }

                $project = \Models\Projects::with([    
                    'chat' => function($q) use($talent_id){
                        $q->defaultKeys()->sender()->where('sender_id',$talent_id);
                    }
                ])
                ->where('id_project',$project_id)->get()->first();
                
                if(!empty($project->chat)){
                    $sender_id                          = $project->chat->sender_id;

                    $project->chat->sender_id           = $project->chat->receiver_id;
                    $project->chat->receiver_id         = $sender_id;       
                    $project->chat->request_status      = 'accepted';       
                    $project->chat->receiver_picture    = (string)get_file_url(\Models\Talents::get_file(sprintf(" type = 'profile' AND user_id = %s",$project->chat->receiver_id),'single',['filename','folder']));

                    $this->jsondata = [
                        'chat' => $project->chat
                    ];
                }

                $user_details = (array)\Models\Users::findById($talent_id);

                //Send email to talent
                $prefix = DB::getTablePrefix();
                $data['project_detail'] = \Models\Projects::defaultKeys()
                ->projectDescription()
                ->companyName()
                ->companyLogo()
                ->with([
                    'industries.industries' => function($q) use($prefix){
                        $q->select(
                            'id_industry',
                            'en as name'
                        );
                    },
                    'subindustries.subindustries' => function($q) use($prefix){
                        $q->select(
                            'id_industry',
                            'en as name'
                        );
                    },
                    'skills.skills' => function($q){
                        $q->select(
                            'id_skill',
                            'skill_name'
                        );
                    },
                    'employer' => function($q) use($prefix){
                        $q->select(
                            'id_user',
                            'company_name',
                            'company_biography',
                            \DB::Raw("YEAR({$prefix}users.created) as member_since"),
                            \DB::Raw("CONCAT({$prefix}users.first_name, ' ',{$prefix}users.last_name) AS name")
                        );
                        $q->companyLogo();
                        $q->city();
                        $q->review();
                        $q->totalHirings();
                        $q->withCount('projects');
                    },
                    'chat'
                ])->where('id_project', $project_id)->first();

                $project_detail = (json_decode(json_encode($data['project_detail']),true));

                if(!empty($project_detail)){
                    $emailData              = ___email_settings();
                    $emailData['name']      = $user_details['name'];
                    $emailData['email']     = $user_details['email'];

                    $emailData['project_type'] = employment_types('post_job',$project_detail['employment']);
                    $emailData['industry'] = ___tags(array_column(array_column($project_detail['industries'], 'industries'),'name'),'<span class="small-tags">%s</span>','');

                    $emailData['subindustry'] =  ___tags(array_column(array_column($project_detail['subindustries'], 'subindustries'),'name'),'<span class="small-tags">%s</span>','');

                    $emailData['required_skills'] =  ___tags(array_column(array_column($project_detail['skills'], 'skills'),'skill_name'),'<span class="small-tags">%s</span><br/>','');

                    $emailData['expertise_level'] = !empty($project_detail['expertise']) ? expertise_levels($project_detail['expertise']) : N_A;
                    $emailData['timeline'] = ___date_difference($project_detail['startdate'],$project_detail['enddate']);
                    $emailData['description'] = nl2br($project_detail['description']);

                    ___mail_sender($user_details['email'],sprintf('%s %s',$user_details['first_name'], $user_details['last_name']),'existing_job',$emailData);
                }

                $this->status = true;
                $this->message = 'M0590';
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used sending message]
         * @param  Request
         * @return \Illuminate\Http\Response
         */

        public function sendmessage(Request $request){
            $validator = \Validator::make($request->all(), [
                'talent_id'             => validation('record_id'),
                'message'               => 'required'
            ],[
                'talent_id.required'    => 'M0592',
                'message.required'      => 'M0604'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $employer_id            = $request->user()->id_user;
                $talent_id              = $request->talent_id;
                $is_chat_room_created   = \Models\Projects::is_chat_room_created($employer_id,$talent_id);
                $project_id             = \Models\Projects::create_dummp_job($employer_id,$talent_id);
                $isRequestSent          = \Models\Chats::employer_chat_request($employer_id,$talent_id,$project_id);
                
                $isChatStarted          = \Models\Projects::select('id_project')->where('title',JOB_TITLE)->where('talent_id',$talent_id)->where('user_id',$employer_id)->get();

                if(empty($is_chat_room_created)){
                    $group_id           = \Models\Chats::getChatRoomGroupId($talent_id,$employer_id,$project_id);

                    $isSaved = \Models\Chats::addmessage([
                        'message'      => $request->message,
                        'sender_id'    => $employer_id,
                        'receiver_id'  => $talent_id,
                        'message_type' => 'text',
                        'group_id'     => $group_id
                    ]);
                }

                $project = \Models\Projects::with([    
                    'chat' => function($q) use($talent_id){
                        $q->defaultKeys()->sender()->where('sender_id',$talent_id);
                    }
                ])
                ->where('id_project',$project_id)->get()->first();
                
                if(!empty($project->chat)){
                    $sender_id                          = $project->chat->sender_id;

                    $project->chat->sender_id           = $project->chat->receiver_id;
                    $project->chat->receiver_id         = $sender_id;       
                    $project->chat->request_status      = 'accepted';       
                    $project->chat->receiver_picture    = (string)get_file_url(\Models\Talents::get_file(sprintf(" type = 'profile' AND user_id = %s",$project->chat->receiver_id),'single',['filename','folder']));

                    $this->jsondata = [
                        'chat' => $project->chat
                    ];
                }

                $this->status = true;
                $this->message = 'M0590';
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /* When a Payout(Manual) type has been added by client, and the payment will be accepted manually (i.e. not paid by paypal). */
        public function accept_payout_mgmt(Request $request){

             if(!empty($request->project_id)){
                $project    = \Models\Projects::defaultKeys()->companyName()->projectPrice()->where('id_project',$request->project_id)->get()->first();
                if(!empty($project)){
                    $is_payment_already_captured = \Models\Payments::is_payment_already_escrowed($request->project_id);
                    if(empty($is_payment_already_captured)){
                        $proposal               = \Models\Employers::get_proposal($request->proposal_id,['quoted_price']);
                        $number_of_days         = ___get_total_days($project['startdate'],$project['enddate']);
                        
                        if(!empty($proposal)){
                            if($project['startdate'] >= date('Y-m-d')){
                                $is_recurring       = false;
                                $repeat_till_month  = 0;
                                if($project['employment'] == 'hourly'){
                                    $sub_total                  = $proposal['global_quoted_price']*$proposal['decimal_working_hours']*$number_of_days;
                                }else if($project['employment'] == 'monthly'){
                                    $sub_total                  = ($proposal['global_quoted_price']/MONTH_DAYS)*$number_of_days;
                                    $is_recurring               = ($number_of_days > MONTH_DAYS) ? true : false;
                                    $repeat_till_month          = ($number_of_days)/MONTH_DAYS;
                                }else if($project['employment'] == 'fixed'){
                                    $sub_total                  = $proposal['global_quoted_price'];
                                }

                                $commission                     = ___calculate_commission($sub_total,$request->user()->commission, $request->user()->commission_type);
                                $paypal_commission              = ___calculate_paypal_commission($sub_total);
                                $payment                       = [
                                    'transaction_user_id'               => (string) $request->user()->id_user,
                                    'transaction_company_id'            => (string) $request->user()->id_user,
                                    'transaction_user_type'             => $request->user()->type,
                                    'transaction_project_id'            => $request->project_id,
                                    'transaction_proposal_id'           => $request->proposal_id,
                                    'transaction_total'                 => $sub_total+$commission+$paypal_commission,
                                    'transaction_subtotal'              => $sub_total,
                                    'transaction_type'                  => 'debit',
                                    'transaction_date'                  => date('Y-m-d H:i:s'),
                                    'transaction_commission'            => $commission,
                                    'transaction_paypal_commission'     => $paypal_commission,
                                    'transaction_is_recurring'          => $is_recurring,
                                    'transaction_repeat_till_month'     => $repeat_till_month
                                ];

                                $payment['transaction_source']       = 'paypal';
                                $payment['transaction_reference_id'] =  '';
                                $payment['transaction_status']       = 'initiated';

                                $transaction = \Models\Payments::init_employer_payment($payment);

                            }
                        }
                    }
                }
            }    
            /*Update project payment type to 'Manual' */
            $payment_where = ['id_project' => $payment['transaction_project_id'] ];
            $payment_data  = ['payment_type' => 'manual' ];
            $payment_type_project = \Models\Projects::change($payment_where,$payment_data);

            $isUpdated = \Models\Payments::update_transaction(
                            $transaction->id_transactions,
                            [
                                'transaction_reference_id' => '-', 
                                'transaction_status'       => 'confirmed', 
                                'transaction_type'         => 'manual',
                                'transaction_comment'      => 'This Job\'s payment will be done outside the system.',
                                'updated'                  => date('Y-m-d H:i:s')
                            ]
                        );

            if(!empty($isUpdated)){
                $isProposalAccepted =  \Models\Employers::accept_proposal(\Auth::user()->id_user,$payment['transaction_project_id'],$payment['transaction_proposal_id']);
            }

            $this->status = true;
            $this->message = trans('general.'.$isProposalAccepted['message']);
            $redirect_url = url(sprintf('%s/project/proposals/talent?proposal_id=%s&project_id=%s',EMPLOYER_ROLE_TYPE,___encrypt($payment['transaction_proposal_id']),___encrypt($payment['transaction_project_id'])));

            \Session::forget('payment');

            return response()->json([
                'status'       => $this->status,
                'message'      => $this->message,
                'redirect_url' => $redirect_url,
            ]);

        }
     
    }