<?php

    namespace App\Http\Controllers\Api;

    use App\Http\Requests;
    use Illuminate\Http\Request;
    use Illuminate\Validation\Rule;
    use Illuminate\Support\Facades\DB;

    use App\Http\Controllers\Controller;
    use App\Models\File;

    use Voucherify\VoucherifyClient;
    use Voucherify\ClientException;

    class Talent extends Controller{
        
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
            if(strpos($request->url(), 'notification') === false){
                \Models\Listings::record_api_request([
                    'url' => $request->url(),
                    'request' => json_encode($this->post),
                    'type' => 'webservice',
                    'created' => date('Y-m-d H:i:s')
                ],$request);
            }

            $request->replace($this->post);
        }

        private function populateresponse($data){
            $data['message'] = (!empty($data['message']))?"":$this->message;
            
            if(empty($this->error)){
                $data['error'] = trans(sprintf("general.%s",$data['message']));     
            }else{
                $data['error'] = $this->error;
            }

            $data['error_code'] = "";

            if(empty($data['status'])){
                $data['status'] = $this->status;
                $data['error_code'] = $this->message;
            }
            
            $data['status_code'] = $this->status_code;
            
            $data = json_decode(json_encode($data),true);

            array_walk_recursive($data, function(&$item,$key){
                if($key === 'invitation'){
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
         * [This method is used to profile view] 
         * @param  Request
         * @return Json Response
         */

        public function viewprofile(Request $request){
            $user = \Models\Talents::get_user($request->user(),true);
            
            $talent_country_id         = !empty(\Auth::user()->country) ? \Auth::user()->country : 0;
            $talent_industry_id        = \Models\TalentIndustries::get_talent_industry_by_userID(\Auth::user()->id_user);

            $user['identification_no_check'] = \Models\Payout_mgmt::talentCheckIdentificationNo($talent_country_id,$talent_industry_id);

            $user['firm_jurisdiction']      = \Models\FirmJurisdiction::get_firm_jurisdiction(\Auth::user()->id_user);

            $countries = [];
            if(!empty($user['firm_jurisdiction'])){
                foreach ($user['firm_jurisdiction'] as $key => $value) {
                    $user['firm_jurisdiction'][$key]['country_name'] = \Cache::get('countries')[$value['country_id']];
                }
            }

            $shareUrl = '';
            if(!empty($user['first_name']) && !empty($user['id_user'])){
                $shareUrl = url('/showprofile/'.strtolower($user['first_name']).'-'.strtolower($user['last_name']).'/'.$user['id_user']);
                $user['share_link'] = $shareUrl;
            } 
            
            if(!empty($user['industry'])){
                $user['payout_mgmt_is_registered'] = \Models\Payout_mgmt::userCheckIsRegistered($talent_country_id,$talent_industry_id);
            }else{
                $user['payout_mgmt_is_registered'] = 'no';
            }

            foreach ($user['industry'] as $key => $value) {
                $user['industry'][$key]['check'] = $user['identification_no_check']==true ?'Y':'N';
                // if(!empty($talent_industry_id )){
                    $user['industry'][$key]['payout_mgmt_is_registered'] = $user['payout_mgmt_is_registered']=='yes' ?'yes':'no';
                /*}
                else{
                    $user['industry'][$key]['payout_mgmt_is_registered'] = '';
                }*/
            }

            if($user['company_profile']=='company'){
                $companydata  = \DB::table('company_connected_talent')->leftjoin('talent_company','talent_company.talent_company_id','=','company_connected_talent.id_talent_company')->select('company_name','company_website','company_biography')->where('id_user','=',\Auth::user()->id_user)->first();

                $user['company_name'] = !empty($companydata->company_name)?$companydata->company_name:'';
                $user['company_website'] = !empty($companydata->company_website)?$companydata->company_website:'';
                $user['company_biography'] = !empty($companydata->company_biography)?$companydata->company_biography:'';
            }else{
                $user['companydata']  = '';
            }

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
         * [This method is used for randering view of Employer job post] 
         * @param  null
         * @return \Illuminate\Http\Response
         */
        
        public function profile_step_process(Request $request, $step){
            $request->process = "";
            if(!empty($request->process)){
                $request->process = $request->process.'/';
            }

            switch ($step) {
                case 'one':{
                    if(!empty($request->birthyear)  && !empty($request->birthmonth) && !empty($request->birthdate)){
                        $request->request->add(['birthday'=>sprintf('%s-%s-%s',$request->birthyear, $request->birthmonth, $request->birthdate)]);
                    }

                    $user = \Models\Talents::get_user(\Auth::user());
                    
                    $validator = \Validator::make($request->all(), [
                        'first_name'                => validation('first_name'),
                        'last_name'                 => validation('last_name'),
                        'email'                     => ['required','email',Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],
                        'birthday'                  => array_merge(['min_age:14'],validation('birthday')),
                        'gender'                    => validation('gender'),
                        'mobile'                    => array_merge([Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],validation('mobile')),
                        'address'                   => validation('address'),
                        'country'                   => validation('country'),
                        'country_code'              => $request->mobile ? array_merge(['required'], validation('country_code')) : validation('country_code'),
                        'state'                     => validation('state'),
                        'city'                      => validation('city'),
                        'postal_code'               => validation('postal_code'),
                        'agree'                     => validation('agree'),
                        'company_profile'                 => 'required',
                    ],[
                        'first_name.required'       => 'M0006',
                        'first_name.regex'          => 'M0007',
                        'first_name.string'         => 'M0007',
                        'first_name.max'            => 'M0020',
                        'last_name.required'        => 'M0008',
                        'last_name.regex'           => 'M0009',
                        'last_name.string'          => 'M0009',
                        'last_name.max'             => 'M0019',
                        'email.required'            => 'M0010',
                        'email.email'               => 'M0011',
                        'email.unique'              => 'M0047',            
                        'birthday.string'           => 'M0054',
                        'birthday.regex'            => 'M0054',
                        'birthday.min_age'          => 'M0055',
                        'birthday.min_age'          => 'M0055',
                        'birthday.validate_date'    => 'M0506',
                        'gender.string'             => 'M0056',
                        'mobile.required'           => 'M0030',
                        'mobile.regex'              => 'M0031',
                        'mobile.string'             => 'M0031',
                        'mobile.min'                => 'M0032',
                        'mobile.max'                => 'M0033',
                        'mobile.unique'             => 'M0197',
                        'address.string'            => 'M0057',
                        'address.regex'             => 'M0057',
                        'address.max'               => 'M0058',
                        'country.integer'           => 'M0059',
                        'country_code.string'       => 'M0074',
                        'country_code.required'     => 'M0164',
                        'state.integer'             => 'M0060',
                        'city.integer'              => 'M0254',
                        'postal_code.string'        => 'M0061',
                        'agree.required'            => 'M0253',
                        'company_profile.required'  => 'M0676',
                    ]);

                    if($request->company_profile=='company'){
                        $validator->after(function ($validator) use($request) {
                            if(empty($request->company_name)) {
                                $validator->errors()->add('company_name', trans('general.M0660'));
                            }
                        });
                    }

                    if($validator->fails()){
                        $this->message = $validator->messages()->first();
                    }else{
                        $update = array_intersect_key(
                            json_decode(json_encode($request->all()),true), 
                            array_flip(
                                array(
                                    'first_name', 
                                    'last_name', 
                                    'email', 
                                    'birthday', 
                                    'gender', 
                                    'mobile', 
                                    'address', 
                                    'country', 
                                    'country_code', 
                                    'state', 
                                    'city', 
                                    'postal_code',
                                    'company_profile',
                                    'company_name',
                                    'agree', 
                                )
                            )
                        );

                        ___filter_null($update);

                        if($update['mobile'] != \Auth::user()->mobile){
                            $update['is_mobile_verified'] = DEFAULT_NO_VALUE;
                        }
                        $code = '';
                        if($request->email != $user['email']){
                            $code = bcrypt(__random_string());
                            $update['remember_token'] = $code;
                            $update['is_email_verified'] = DEFAULT_NO_VALUE;
                        }

                        /*if($request->work_type=='company'){
                            $update['company_profile'] = $update['work_type'];
                            unset($update['work_type']);
                        }*/

                        /*Save First & Last Name in ucfirst*/
                        $update['first_name'] = ucfirst(strtolower($request->first_name));
                        $update['last_name'] = ucfirst(strtolower($request->last_name));

                        /*Update in name field in users table*/
                        $update['name'] = $update['first_name'].' '.$update['last_name'];

                        $id = \Auth::user()->id_user;
                        $talentcompanydataId = \DB::table('company_connected_talent')->select('id_talent_company')->where('id_user','=',$id)->first();

                        if($update['company_profile']=='company' && !empty($talentcompanydataId)){
                            $talentcompanydata['company_name'] = $update['company_name'];
                            $talentcompanydata['company_website'] = $request->company_website;
                            $talentcompanydata['company_biography'] = $request->company_biography;
                            $talentcompanydata['created'] = date('Y-m-d H:i:s');
                            $talentcompanydata['updated'] = date('Y-m-d H:i:s');
                            $isCompanyUpdated      = \Models\TalentCompany::updateTalentCompany($talentcompanydata,$talentcompanydataId->id_talent_company);
                        }
                        if($update['company_profile']=='company' && empty($talentcompanydataId)){
                            $talentcompanydata['company_name'] = $request->company_name;
                            $talentcompanydata['company_website'] = $request->company_website;
                            $talentcompanydata['company_biography'] = $request->company_biography;
                            $talentcompanydata['created'] = date('Y-m-d H:i:s');
                            $talentcompanydata['updated'] = date('Y-m-d H:i:s');
                            $isTalentCompanydId      = \Models\TalentCompany::saveTalentCompany($talentcompanydata);
                            $isCreated = \DB::table('company_connected_talent')->insert(['id_talent_company'=>$isTalentCompanydId,'id_user'=>\Auth::user()->id_user,'user_type'=>'owner','updated'=> date('Y-m-d H:i:s'),'created'=> date('Y-m-d H:i:s')]);
                        }

                        // dd($request->all(),'zzz',$update);
                        $isUpdated      = \Models\Talents::change(\Auth::user()->id_user,$update);

                        if($request->email != $user['email']){
                            if(!empty($request->email)){
                                $emailData              = ___email_settings();
                                $emailData['email']     = $request->email;
                                $emailData['name']      = $request->first_name;
                                $emailData['link']      = url(sprintf("emailverify/account?token=%s",$code));

                                ___mail_sender($request->email,sprintf("%s %s",$request->first_name,$request->last_name),"update_email_verification",$emailData);
                            }
                        }
                        
                        $this->status   = true;
                        $this->message  = 'M0110';
                        
                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => \Auth::user()->id_user,
                            'user_type'         => 'talent',
                            'action'            => 'talent-step-one',
                            'reference_type'    => 'users',
                            'reference_id'      => \Auth::user()->id_user
                        ]));
                    }
                    break;
                }
                case 'two':{
                    $validator = \Validator::make($request->all(), [
                        'industry'                          => validation('industry'),
                        'skills'                            => validation('skills'),
                        'expertise'                         => validation('expertise'),
                        'experience'                        => validation('experience'),
                    ],[
                        'industry.array'                    => 'M0511',
                        'skills.array'                      => 'M0142',
                        'expertise.string'                  => 'M0066',
                        'experience.numeric'                => 'M0067',
                        'experience.max'                    => 'M0068',
                        'experience.min'                    => 'M0069',
                        'experience.regex'                  => 'M0067',                        
                    ]);

                    if($validator->fails()){
                        $this->message = $validator->messages()->first();                
                    }else{
                        $country_id = !empty(\Auth::user()->country) ? \Auth::user()->country : 0;
                        /*REMOVE AND ADD NEWLY SELECTED INDUSTRY*/
                        \Models\Talents::update_industry(\Auth::user()->id_user,$request->industry);    
                        
                        /*REMOVE AND ADD NEWLY SELECTED SKILLS*/
                        \Models\Talents::update_skill(\Auth::user()->id_user,$request->skills);
                        
                        $is_register = !empty($request->is_register) ? $request->is_register : 'N';

                        $is_identification_no = ($is_register=='Y') ? $request->identification_no : '' ; 
                        /*UPDATING PROFILE*/
                        \Models\Talents::change(\Auth::user()->id_user,['expertise' => $request->expertise, 'experience' => $request->experience,'identification_no' => $is_identification_no,'is_register' => $is_register, 'updated' => date('Y-m-d H:i:s')]);                        

                        $this->status   = true;
                        $this->message  = 'M0110';
                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => \Auth::user()->id_user,
                            'user_type'         => 'talent',
                            'action'            => 'talent-step-two',
                            'reference_type'    => 'users',
                            'reference_id'      => \Auth::user()->id_user
                        ]));
                    }
                    break;
                }
                case 'three':{
                    $validator = \Validator::make($request->all(), [
                        'industry_id'                       => ['required'],
                        'subindustry'                       => validation('subindustry'),
                    ],[                        
                        'industry_id.required'              => 'M0136', 
                        'subindustry.array'                 => 'M0065', 
                    ]);
                    if($validator->fails()){
                        $this->message = $validator->messages()->first();                
                    }else{
                        /*REMOVE AND ADD NEWLY SELECTED SUBINDUCTRY*/
                        $isSubindustryInserted = \Models\Talents::update_subindustry(\Auth::user()->id_user,$request->subindustry,$request->industry);
                        
                        if(!empty($isSubindustryInserted)){                                
                            $this->status   = true;
                            $this->message  = 'M0110';
                            
                            /* RECORDING ACTIVITY LOG */
                            event(new \App\Events\Activity([
                                'user_id'           => \Auth::user()->id_user,
                                'user_type'         => 'talent',
                                'action'            => 'talent-step-three',
                                'reference_type'    => 'users',
                                'reference_id'      => \Auth::user()->id_user
                            ]));
                        }else{
                            $this->message = 'M0583';
                        }
                    }
                    break;
                }
                case 'four':{
                    $interests  = array_filter(array_column((array)$request->remuneration, 'interest'));
                    $workrate   = array_filter(array_column((array)$request->remuneration, 'workrate'));
                    $request->request->add([
                        'interests' => $interests,
                        'workrate' => $workrate,
                        'workrate_information' => strip_tags($request->workrate_information)
                    ]);
                    $validator = \Validator::make($request->all(), [
                        'interests'                         => validation('industry'),
                        'workrate'                          => validation('workrate'),
                        "workrate.0"                        => validation('workrate.0'),
                        "workrate.1"                        => validation('workrate.1'),
                        "workrate.2"                        => validation('workrate.2'),
                        'workrate_information'              => validation('workrate_information'),
                    ],[
                        'interests.array'                   => 'M0512',
                        'workrate.*.numeric'                => 'M0256',
                        'workrate.*.min'                    => 'M0261',
                        'workrate.*.max'                    => 'M0262',
                        "workrate.0.required_with"          => 'M0070',
                        "workrate.1.required_with"          => 'M0070',
                        "workrate.2.required_with"          => 'M0070',
                        'workrate_information.string'       => 'M0071',
                        'workrate_information.regex'        => 'M0071',
                        'workrate_information.max'          => 'M0072',
                        'workrate_information.min'          => 'M0073',
                    ]);

                    if($validator->fails()){
                        $this->message = $validator->messages()->first();                
                    }else{
                        /*REMOVE AND ADD NEWLY SELECTED INTERESTS*/
                        \Models\Talents::update_interest($request->user()->id_user,$request->interests,$request->workrate,$request->currency);

                        /*UPDATING WORK INFORMATION*/
                        \Models\Talents::change(\Auth::user()->id_user,['workrate_information' => $request->workrate_information, 'updated' => date('Y-m-d H:i:s')]);
                        
                        $this->status   = true;
                        $this->message  = 'M0110';
                        
                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => \Auth::user()->id_user,
                            'user_type'         => 'talent',
                            'action'            => 'talent-step-four',
                            'reference_type'    => 'users',
                            'reference_id'      => \Auth::user()->id_user
                        ]));
                    }
                    break;
                }
                case 'five':{
                    break;
                }
            }

            $this->jsondata = \Models\Talents::get_user($request->user());

            if($step=='two'){ 
                $talent_industry_id = \Models\TalentIndustries::get_talent_industry_by_userID(\Auth::user()->id_user);
                $this->jsondata['country_id'] = $country_id;
                $this->jsondata['payout_management_list'] = \Models\Payout_mgmt::userCheckIdentificationNumber($country_id);
                $this->jsondata['payout_mgmt_is_registered'] = \Models\Payout_mgmt::userCheckIsRegistered($country_id,$talent_industry_id);
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }        


        /**
         * [This method is used for Personal Information] 
         * @param  Request
         * @return Json Response
         */

        public function step_one(Request $request){
            
            $validation_mobile = validation('phone_number'); unset($validation_mobile[0]);
            
            $validate = \Validator::make($request->all(), [
                'first_name'                => validation('first_name'),
                'last_name'                 => validation('last_name'),
                'email'                     => ['required','email',Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],
                'birthday'                  => array_merge(['min_age:14'],validation('birthday')),
                'gender'                    => validation('gender'),
                'mobile'                    => array_merge([Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],validation('mobile')),
                'address'                   => validation('address'),
                'country'                   => validation('country'),
                'country_code'              => $request->mobile ? array_merge(['required'], validation('country_code')) : validation('country_code'),
                'state'                     => validation('state'),
                'city'                      => validation('city'),
                'postal_code'               => validation('postal_code'),
            ],[
                'first_name.required'       => 'M0006',
                'first_name.regex'          => 'M0007',
                'first_name.string'         => 'M0007',
                'first_name.max'            => 'M0020',
                'last_name.required'        => 'M0008',
                'last_name.regex'           => 'M0009',
                'last_name.string'          => 'M0009',
                'last_name.max'             => 'M0019',
                'email.required'            => 'M0010',
                'email.email'               => 'M0011',
                'email.unique'              => 'M0047',
                'birthday.regex'            => 'M0054',
                'birthday.min_age'          => 'M0055',
                'birthday.validate_date'    => 'M0506',
                'gender.string'             => 'M0056',
                'mobile.required'           => 'M0030',
                'mobile.regex'              => 'M0031',
                'mobile.string'             => 'M0031',
                'mobile.min'                => 'M0032',
                'mobile.max'                => 'M0033',
                'mobile.unique'             => 'M0197',
                'address.string'            => 'M0057',
                'address.regex'             => 'M0057',
                'address.max'               => 'M0058',
                'country.integer'           => 'M0059',
                'state.integer'             => 'M0060',
                'city.integer'              => 'M0254',
                'postal_code.string'        => 'M0061',
                'postal_code.regex'         => 'M0061',
                'postal_code.max'           => 'M0062',
                'postal_code.min'           => 'M0063',
                'country_code.string'       => 'M0074',
                'country_code.required'     => 'M0074',
            ]);

            // $validator->after(function($v) use($request){
            //     $res = validatePayPalEmail($request->email);
            //     if($res == 'Failure'){
            //         $v->errors()->add('email',trans('general.valid_paypal_email'));
            //     }
            // });            

            if($validate->fails()){
                $this->message = $validate->messages()->first();                
            }else{
                $update = array_intersect_key(
                    json_decode(json_encode($this->post),true), 
                    array_flip(
                        array(
                            'gender',
                            'first_name',
                            'last_name',
                            'email',
                            'birthday',
                            'country_code',
                            'mobile',
                            'country',
                            'state',
                            'city',
                            'address',
                            'postal_code',
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
                if(empty($update['agree'])){
                    $update['agree'] = DEFAULT_YES_VALUE;
                }

                if(empty($update['agree_pricing'])){
                    $update['agree_pricing'] = DEFAULT_YES_VALUE;
                }

                if($update['mobile'] != $request->user()->mobile){
                    $update['is_mobile_verified'] = DEFAULT_NO_VALUE;
                }
                
                $isUpdated = \Models\Talents::change($request->user()->id_user,$update);

                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-step-one',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));
                
                $this->status = true;
                $this->jsondata = [
                    'user' => \Models\Talents::get_user($request->user(),true)
                ];
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used personal picture information] 
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
                    'user_id' => $request->user()->id_user,
                    'reference' => 'users',
                    'filename' => $uploaded_file['filename'],
                    'extension' => $uploaded_file['extension'],
                    'folder' => $folder,
                    'type' => 'profile',
                    'size' => $uploaded_file['size'],
                    'is_default' => DEFAULT_NO_VALUE,
                    'created' => date('Y-m-d H:i:s'),
                    'updated' => date('Y-m-d H:i:s'),
                ];

                $isInserted = \Models\Talents::create_file($data,false,true);

                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-set-profile-picture',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));
                
                if(!empty($isInserted)){
                    $isInserted['file_url'] = asset(sprintf("%s%s",$isInserted['folder'],$isInserted['filename']));
                    
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
         * [This method is used for Industry and Skills ]
         * @param  Request
         * @return Json Response
         */

        public function step_two(Request $request){
            if(empty($this->post['workrate'])){
                $this->post['workrate'] = "";
            }
            
            if(empty($this->post['expected_salary'])){
                $this->post['expected_salary'] = "";
            }

            

            $validate = \Validator::make($request->all(), [
                'interests'                     => validation('interests'),
                'expected_salary'               => validation('expected_salary'),
                'other_expectations'            => validation('other_expectations'),
                'agree'                         => validation('agree_pricing'),
                'industry'                      => validation('industry'),
                'subindustry'                   => validation('subindustry'),
                'skills'                        => validation('skills'),
                'expertise'                     => validation('expertise'),
                'experience'                    => validation('experience'),
                'workrate'                      => validation('workrate'),
                'workrate_max'                  => validation('workrate_max'),
                'workrate_unit'                 => validation('workrate_unit'),
                'workrate_information'          => validation('workrate_information'),
                'certificates'                  => validation('certificates'),
            ],[
                'expected_salary.required_range'=> 'M0264',
                'expected_salary.integer'       => 'M0049',
                'expected_salary.max'           => 'M0050',
                'expected_salary.min'           => 'M0051',
                'other_expectations.max'        => 'M0052',
                'other_expectations.min'        => 'M0053',
                'agree.required_range'          => 'M0253',
                'agree.required'                => 'M0253',
                'industry.integer'              => 'M0064',
                'subindustry.integer'           => 'M0065',
                'expertise.string'              => 'M0066',
                'experience.numeric'            => 'M0067',
                'experience.max'                => 'M0068',
                'experience.min'                => 'M0069',
                'workrate.min'                  => 'M0261',
                'workrate.numeric'              => 'M0070',
                'workrate.numeric_range'        => 'M0258',
                'workrate.different'            => 'M0263',
                'workrate.required_range'       => 'M0259',
                'workrate.required_having'      => 'M0259',
                'workrate_max.numeric'          => 'M0256',
                'workrate_max.min'              => 'M0262',
                'workrate_unit.string'          => 'M0257',
                'workrate_unit.required_with'   => 'M0260',
                'workrate_information.string'   => 'M0071',
                'workrate_information.regex'    => 'M0071',
                'workrate_information.max'      => 'M0072',
                'workrate_information.min'      => 'M0073',
            ]);
            
            if($validate->fails()){
                $this->message = $validate->messages()->first();
            }else{
                $update = array_intersect_key(
                    json_decode(json_encode($this->post),true), 
                    array_flip(
                        array(
                            'expected_salary',
                            'other_expectations',
                            'agree',
                            'industry',
                            'subindustry',
                            'expertise',
                            'experience',
                            'workrate',
                            'workrate_max',
                            'workrate_unit',
                            'workrate_information',
                        )
                    )
                );

                /*
                *   REPLACING ALL BLANK STRING WITH 
                *   NULL BECAUSE OF LARAVEL MYSQL 
                *   DRIVER ASKING FOR INTEGER VALUE 
                *   FOR INTEGER COLUMN TYPE
                */

                /*if(!empty($update['agree'])){
                    $update['agree_pricing'] = $update['agree'];
                }

                if(!empty($request->identification_no_check)){
                    $update['identification_no_check'] = $request->identification_no_check==true ? $request->identification_no : NULL;
                }*/
                if(!empty($request->is_register)){
                    $update['is_register'] = $request->is_register==Y ? $request->is_register : N;
                }

                ___filter_null($update);
                
                $isUpdated = \Models\Talents::change($request->user()->id_user,$update);

                /*REMOVE AND ADD NEWLY SELECTED INTERESTES*/
                \Models\Talents::update_interest($request->user()->id_user,$this->post['interests']);    

                /*REMOVE AND ADD NEWLY SELECTED SKILLS*/
                \Models\Talents::update_skill($request->user()->id_user,$this->post['skills'],$update['subindustry']);

                /*REMOVE AND ADD NEWLY SELECTED CERTIFICATES*/
                \Models\Talents::update_certificate($request->user()->id_user,$this->post['certificates']);

                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-step-two',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));
                
                $this->status = true;
                $this->jsondata = [
                    'user' => \Models\Talents::get_user($request->user(),true)
                ];
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used for Curriculum Vitae ]
         * @param  Request
         * @return Json Response
         */

        public function step_three(Request $request){
            

            $validate = \Validator::make($request->all(), [
                'cover_letter_description'          => validation('cover_letter_description'),
            ],[
                'cover_letter_description.string'   => 'M0075',
                'cover_letter_description.regex'    => 'M0075',
                'cover_letter_description.max'      => 'M0076',
                'cover_letter_description.min'      => 'M0077',
            ]);

            if($validate->fails()){
                $this->message = $validate->messages()->first();
            }else{
                $update = array_intersect_key(
                    json_decode(json_encode($this->post),true), 
                    array_flip(
                        array(
                            'cover_letter_description',
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
                $isUpdated = \Models\Talents::change($request->user()->id_user,$update);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-step-three',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));
                
                $this->status = true;
                $this->jsondata = [
                    'user' => \Models\Talents::get_user($request->user(),true)
                ];
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used for education Curriculum Vitae ]
         * @param  Request
         * @return Json Response
         */

        public function step_three_education(Request $request){
            

            $validate = \Validator::make($request->all(), [
                "college"                           => validation('college'),
                "degree"                            => validation('degree'),
                "passing_year"                      => validation('passing_year'),
                "area_of_study"                     => validation('area_of_study'),
                // "degree_status"                     => validation('degree_status'),
                // "degree_country"                    => array_merge(['required'],validation('country')),
            ],[
                'college.required'                  => 'M0078',
                'college.string'                    => 'M0079',
                'college.regex'                     => 'M0079',
                'college.max'                       => 'M0080',
                'degree.required'                   => 'M0081',
                'degree.integer'                    => 'M0082',
                'passing_year.required'             => 'M0083',
                'passing_year.integer'              => 'M0084',
                'area_of_study.required'            => 'M0085',
                'area_of_study.integer'             => 'M0086',
                // 'degree_status.required'            => 'M0087',
                // 'degree_status.integer'             => 'M0088',
                // 'degree_country.required'           => 'M0089',
                // 'degree_country.integer'            => 'M0059',
            ]);

            if($validate->fails()){
                $this->message = $validate->messages()->first();
            }else{
                $update = array_intersect_key(
                    json_decode(json_encode($this->post),true), 
                    array_flip(
                        array(
                            'college',
                            'degree',
                            'passing_year',
                            'area_of_study',
                            // 'degree_status',
                            // 'degree_country',
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
                $isInserted = \Models\Talents::add_education($request->user()->id_user,$update);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-add-education',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));
                
                $this->status = true;
                $this->jsondata = [
                    'educations' => \Models\Talents::get_education(sprintf(" id_education = %s ",$isInserted))
                ];
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used for education Curriculum Vitae edit ]
         * @param  Request
         * @return Json Response
         */

        public function step_three_education_edit(Request $request){
            

            if(empty($this->post['id_education'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_education');
            }else if(empty(\Models\Talents::get_education(sprintf(" id_education = %s AND user_id = %s ",$this->post['id_education'], $request->user()->id_user),'count'))){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_education');
            }else{

                $validate = \Validator::make($request->all(), [
                    "college"                           => validation('college'),
                    "degree"                            => validation('degree'),
                    "passing_year"                      => validation('passing_year'),
                    "area_of_study"                     => validation('area_of_study'),
                    // "degree_status"                     => validation('degree_status'),
                    // "degree_country"                    => array_merge(['required'],validation('country')),
                ],[
                    'college.required'                  => 'M0078',
                    'college.string'                    => 'M0079',
                    'college.regex'                     => 'M0079',
                    'college.max'                       => 'M0080',
                    'degree.required'                   => 'M0081',
                    'degree.integer'                    => 'M0082',
                    'passing_year.required'             => 'M0083',
                    'passing_year.integer'              => 'M0084',
                    'area_of_study.required'            => 'M0085',
                    'area_of_study.integer'             => 'M0086',
                    // 'degree_status.required'            => 'M0087',
                    // 'degree_status.integer'             => 'M0088',
                    // 'degree_country.required'           => 'M0089',
                    // 'degree_country.integer'            => 'M0059',
                ]);

                if($validate->fails()){
                    $this->message = $validate->messages()->first();
                }else{
                    $update = array_intersect_key(
                        json_decode(json_encode($this->post),true), 
                        array_flip(
                            array(
                                'college',
                                'degree',
                                'passing_year',
                                'area_of_study',
                                // 'degree_status',
                                // 'degree_country',
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
                    \Models\Talents::update_education($this->post['id_education'],$update);
                
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-update-education',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));
                    
                    $this->status = true;
                    $this->jsondata = [
                        'educations' => \Models\Talents::get_education(sprintf(" id_education = %s ",$this->post['id_education']))
                    ];
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
         * [This method is used for education Curriculum Vitae deletion ]
         * @param  Request
         * @return Json Response
         */

        public function step_three_education_delete(Request $request){
            
            
            if(empty($this->post['id_education'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_education');
            }else if(empty(\Models\Talents::get_education(sprintf(" id_education = %s AND user_id = %s ",$this->post['id_education'], $request->user()->id_user),'count'))){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_education');
            }else{
                $isDeleted = \Models\Talents::delete_education(sprintf(" id_education = %s AND user_id = %s ",$this->post['id_education'], $request->user()->id_user));

                if(!empty($isDeleted)){
                
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-delete-education',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));
                    
                    $this->status = true;
                    $this->message = 'M0124';
                }else{
                    $this->message = 'M0048'; 
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
         * [This method is used for work experience ]
         * @param  Request
         * @return Json Response
         */

        public function step_three_work_experience(Request $request){
            
            $request->request->add(['startdate' => sprintf("%s-%s",$request->joining_year,$request->joining_month)]);

            if($request->is_currently_working == 'yes'){
                $request->request->remove('relieving_month', 'relieving_year');
            }

            
            if(!empty($request->relieving_year) && !empty($request->relieving_month)){
                $request->request->add(['enddate' => sprintf("%s-%s",$request->relieving_year,$request->relieving_month)]);
            }



            $validation_reliving_month  = validation('relieving_month'); unset($validation_reliving_month[0]);
            $validation_reliving_year   = validation('relieving_year'); unset($validation_reliving_year[0]);
            $validate = \Validator::make($request->all(), [
                "jobtitle"                          => validation('jobtitle'),
                "company_name"                      => validation('company_name'),
                "joining_month"                     => validation('joining_month'),
                "joining_year"                      => validation('joining_year'),
                "is_currently_working"              => validation('is_currently_working'),
                "job_type"                          => validation('job_type'),
                "relieving_month"                   => array_merge(['required_unless:is_currently_working,'.DEFAULT_YES_VALUE],$validation_reliving_month),
                "relieving_year"                    => array_merge(['required_unless:is_currently_working,'.DEFAULT_YES_VALUE],$validation_reliving_year),
                "country"                           => array_merge(['required'],validation('country')),
                "state"                             => validation('state'),
            ],[
                'jobtitle.required'                 => 'M0090',
                'jobtitle.string'                   => 'M0091',
                'jobtitle.regex'                    => 'M0091',
                'jobtitle.max'                      => 'M0092',
                'jobtitle.min'                      => 'M0093',
                'company_name.required'             => 'M0023',
                'company_name.regex'                => 'M0024',
                'company_name.string'               => 'M0024',
                'company_name.max'                  => 'M0025',
                'joining_month.required'            => 'M0094',
                'joining_month.string'              => 'M0095',
                'joining_month.max'                 => 'M0544',
                'joining_year.required'             => 'M0096',
                'joining_year.string'               => 'M0097',
                'joining_year.max'                  => 'M0543',
                'is_currently_working.required'     => 'M0098',
                'is_currently_working.string'       => 'M0099',
                'job_type.required'                 => 'M0100',
                'job_type.string'                   => 'M0101',
                'relieving_month.required_unless'   => 'M0102',
                'relieving_month.string'            => 'M0103',
                'relieving_month.max'               => 'M0545',
                'relieving_year.required_unless'    => 'M0104',
                'relieving_year.string'             => 'M0105',
                'relieving_year.min'                => 'M0541',
                'relieving_year.max'                => 'M0542',
                'country.required'                  => 'M0106',
                'country.integer'                   => 'M0059',
                'state.integer'                     => 'M0060',
                'state.required'                    => 'M0107',
            ]);

            // $validate->sometimes(['relieving_month','relieving_year'], 'required', function($input){
            //     return ($input->is_currently_working == DEFAULT_YES_VALUE);
            // });         

            if($validate->fails()){
                $this->message = $validate->messages()->first();
            }else{
                if(!empty($request->startdate) && !empty($request->enddate) && (strtotime($request->startdate) > strtotime($request->enddate))){
                    $this->message = 'M0190';
                }else if(!empty($request->is_currently_working) && abs($request->joining_month) > 12){
                    $this->message = 'M0544';
                }else if(!empty($request->is_currently_working) && abs($request->relieving_month) > 12){
                    $this->message = 'M0545';
                }else if(!empty($request->is_currently_working) && abs($request->relieving_year) > date('Y')){
                    $this->message = 'M0542';
                }else if(!empty($request->is_currently_working) && $request->is_currently_working == DEFAULT_NO_VALUE && (strtotime($request->startdate) > strtotime(date('Y-m')) || strtotime($request->enddate) > strtotime(date('Y-m')))){
                    $this->message = 'M0557';
                }else{
                    $request->joining_month     = sprintf("%'.02d",$request->joining_month);
                    $request->relieving_month   = sprintf("%'.02d",$request->relieving_month);
                    
                    $update = array_intersect_key(
                        json_decode(json_encode($this->post),true), 
                        array_flip(
                            array(
                                "jobtitle",
                                "company_name",
                                "joining_month",
                                "joining_year",
                                "is_currently_working",
                                "job_type",
                                "relieving_month",
                                "relieving_year",
                                "country",
                                "state",
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

                    if($request->is_currently_working == DEFAULT_YES_VALUE){
                        unset($update['relieving_month']);
                        unset($update['relieving_year']);
                    }                    
                    $isInserted = \Models\Talents::add_experience($request->user()->id_user,$update);
                                    
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-step-three-work-experience',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));
                    
                    $this->status = true;
                    $this->jsondata = [
                        'work_experiences' => \Models\Talents::get_experience(sprintf(" id_experience = %s ",$isInserted))
                    ];
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
         * [This method is used for work experience edit ]
         * @param  Request
         * @return Json Response
         */

        public function step_three_work_experience_edit(Request $request){
            $request->request->add(['startdate' => sprintf("%s-%s",$request->joining_year,$request->joining_month)]);
            if(!empty($request->relieving_year) && !empty($request->relieving_month)){
                $request->request->add(['enddate' => sprintf("%s-%s",$request->relieving_year,$request->relieving_month)]);
            }
            
            if(empty($this->post['id_experience'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_experience');
            }else if(empty(\Models\Talents::get_experience(sprintf(" id_experience = %s AND user_id = %s ",$this->post['id_experience'], $request->user()->id_user),'count'))){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_experience');
            }else{
                $validation_reliving_month  = validation('relieving_month'); unset($validation_reliving_month[0]);
                $validation_reliving_year   = validation('relieving_year'); unset($validation_reliving_year[0]);
                $validate = \Validator::make($request->all(), [
                    "jobtitle"                          => validation('jobtitle'),
                    "company_name"                      => validation('company_name'),
                    "joining_month"                     => validation('joining_month'),
                    "joining_year"                      => validation('joining_year'),
                    "is_currently_working"              => validation('is_currently_working'),
                    "job_type"                          => validation('job_type'),
                    "relieving_month"                   => array_merge(['required_unless:is_currently_working,'.DEFAULT_YES_VALUE,'sometimes'],$validation_reliving_month),
                    "relieving_year"                    => array_merge(['required_unless:is_currently_working,'.DEFAULT_YES_VALUE,'sometimes'],$validation_reliving_year),
                    "country"                           => array_merge(['required'],validation('country')),
                    "state"                             => validation('state'),
                ],[
                    'jobtitle.required'                 => 'M0090',
                    'jobtitle.string'                   => 'M0091',
                    'jobtitle.regex'                    => 'M0091',
                    'jobtitle.max'                      => 'M0092',
                    'jobtitle.min'                      => 'M0093',
                    'company_name.required'             => 'M0023',
                    'company_name.regex'                => 'M0024',
                    'company_name.string'               => 'M0024',
                    'company_name.max'                  => 'M0025',
                    'joining_month.required'            => 'M0094',
                    'joining_month.string'              => 'M0095',
                    'joining_month.max'                 => 'M0544',
                    'joining_year.required'             => 'M0096',
                    'joining_year.string'               => 'M0097',
                    'joining_year.max'                  => 'M0543',
                    'is_currently_working.required'     => 'M0098',
                    'is_currently_working.string'       => 'M0099',
                    'job_type.required'                 => 'M0100',
                    'job_type.string'                   => 'M0101',
                    'relieving_month.required_unless'   => 'M0102',
                    'relieving_month.string'            => 'M0103',
                    'relieving_month.max'               => 'M0545',
                    'relieving_year.required_unless'    => 'M0104',
                    'relieving_year.integer'            => 'M0105',
                    'relieving_year.min'                => 'M0541',
                    'relieving_year.max'                => 'M0542',                    
                    'country.required'                  => 'M0106',
                    'country.integer'                   => 'M0059',
                    'state.integer'                     => 'M0060',
                    'state.required'                    => 'M0107',
                ]);

                if($validate->fails()){
                    $this->message = $validate->messages()->first();
                }else{
                    if(!empty($request->startdate) && !empty($request->enddate) && (strtotime($request->startdate) > strtotime($request->enddate))){
                    $this->message = 'M0190';
                    }else{
                        $update = array_intersect_key(
                            json_decode(json_encode($this->post),true), 
                            array_flip(
                                array(
                                    "jobtitle",
                                    "company_name",
                                    "joining_month",
                                    "joining_year",
                                    "is_currently_working",
                                    "job_type",
                                    "relieving_month",
                                    "relieving_year",
                                    "country",
                                    "state",
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
                        \Models\Talents::update_experience($this->post['id_experience'],$update);

                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => $request->user()->id_user,
                            'user_type'         => 'talent',
                            'action'            => 'webservice-talent-update-work-experience',
                            'reference_type'    => 'users',
                            'reference_id'      => $request->user()->id_user
                        ]));
                        
                        $this->status = true;
                        $this->jsondata = [
                            'work_experiences' => \Models\Talents::get_experience(sprintf(" id_experience = %s ",$this->post['id_experience']))
                        ];
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
         * [This method is used for work experience deletion ]
         * @param  Request
         * @return Json Response
         */

        public function step_three_work_experience_delete(Request $request){
            
            
            if(empty($this->post['id_experience'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_experience');
            }else if(empty(\Models\Talents::get_experience(sprintf(" id_experience = %s AND user_id = %s ",$this->post['id_experience'], $request->user()->id_user),'count'))){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_experience');
            }else{
                $isDeleted = \Models\Talents::delete_experience(sprintf(" id_experience = %s AND user_id = %s ",$this->post['id_experience'], $request->user()->id_user));

                if(!empty($isDeleted)){
                                    
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-delete-work-experience',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));  
                                      
                    $this->status = true;
                    $this->message = 'M0123';
                }else{
                    $this->message = 'M0048'; 
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

        public function step_three_document(Request $request){
            
            $validate = \Validator::make($request->all(), [
                "file"                              => validation('document'),
            ],[
                'file.validate_file_type'           => 'M0119',
            ]);

            if($validate->fails()){
                $this->message = $validate->messages()->first();
            }else{
                $certificates  = \Models\Talents::get_user($request->user())['certificate_attachments'];
                
                if( count($certificates) >= 20){
                    $this->message = 'M0563';
                }else{
                    $folder = 'uploads/certificates/';

                    $uploaded_file = upload_file($request,'file',$folder);
                    $data = [
                        'user_id'       => $request->user()->id_user,
                        'reference'     => 'users',
                        'filename'      => $uploaded_file['filename'],
                        'extension'     => $uploaded_file['extension'],
                        'folder'        => $folder,
                        'type'          => 'certificates',
                        'size'          => $uploaded_file['size'],
                        'is_default'    => DEFAULT_NO_VALUE,
                        'created'       => date('Y-m-d H:i:s'),
                        'updated'       => date('Y-m-d H:i:s'),
                    ];

                    $isInserted = \Models\Talents::create_file($data,true,true);
                                        
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-step-three-document',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));

                    if(!empty($isInserted)){
                        if(!empty($isInserted['folder'])){
                            $isInserted['file_url'] = asset(sprintf("%s/%s",$isInserted['folder'],$isInserted['filename']));
                        }
                        
                        $this->status = true;
                        $this->jsondata = $isInserted;
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
         * [This method is used for document Curriculum Vitae deletion]
         * @param  Request
         * @return Json Response
         */

        public function step_three_document_delete(Request $request){
            
            
            if(empty($this->post['id_file'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_file');
            }else if(empty(\Models\Talents::get_file(sprintf(" id_file = %s AND user_id = %s ",$this->post['id_file'], $request->user()->id_user),'count'))){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_file');
            }else{
                $isDeleted = \Models\Talents::delete_file(sprintf(" id_file = %s AND user_id = %s ",$this->post['id_file'], $request->user()->id_user));
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-step-three-document-delete',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));

                if(!empty($isDeleted)){
                    $this->status = true;
                    $this->message = 'M0122';
                }else{
                    $this->message = 'M0048'; 
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
         * [This method is used for setting user's availability ]
         * @param  Request
         * @return Json Response
         */

        public function step_four_set_availability(Request $request){
            
            
            $validate = \Validator::make($request->all(), [
                "availability_type"             => array_merge(['required']),
                "availability_date"             => array_merge(['required'],validation('birthday')),
                "from_time"                     => array_merge(['required'],validation('time')),
                "to_time"                       => array_merge(['required'],validation('time')),
                "repeat"                        => validation('repeat'),
                "deadline"                      => validation('birthday'),
            ],[
                'availability_type.required'    => 'M0473',
                'availability_date.required'    => 'M0155',
                'availability_date.string'      => 'M0156',
                'availability_date.regex'       => 'M0156',
                "from_time.required"            => 'M0159',
                "from_time.string"              => 'M0160',
                "from_time.regex"               => 'M0160',
                "to_time.required"              => 'M0161',
                "to_time.string"                => 'M0162',
                "to_time.regex"                 => 'M0162',
                "repeat.string"                 => 'M0163',
                'deadline.required'             => 'M0157',
                'deadline.string'               => 'M0158',
                'deadline.regex'                => 'M0158',
            ]);

            if($validate->fails()){
                $this->message = $validate->messages()->first();
            }else{
                if(empty($request->deadline)){
                    $request->request->add(['deadline' => $request->availability_date]);
                }
                    
                $valid_employment_types = employment_types('talent_availability','keys');
                
                if(!in_array($request->repeat, $valid_employment_types)){
                    $this->message = 'M0169';
                    $this->jsondata = (object)[];
                }else{
                    {
                            
                        $current_date = ___d(date('Y-m-d H:i:s'));
                        if(strtotime("$request->availability_date $request->from_time") < strtotime($current_date)){
                            $this->message = 'M0436';
                        }elseif($request->deadline < $request->availability_date){
                            $this->message = 'M0173';  
                        }else{
                            $availability_id = NULL;
                            
                            if(!empty($request->id_availability)){
                                $availability_id = $request->id_availability;
                            }

                            if($request->availability_type == 'unavailable'){
                                $isAvailable = true;
                            }else{
                                $isAvailable = \Models\Talents::check_availability($request->user()->id_user,$request->availability_date,$request->from_time,$request->to_time,$request->deadline,$request->availability_day,$request->repeat,$availability_id);
                            }
                            
                            if($isAvailable === true){
                                $table_talent_availability = DB::table('talent_availability');
                                $max_repeat_group = (int)$table_talent_availability->max('repeat_group')+1;
                                $data = [];
                                if($request->repeat == 'daily' || $request->repeat == 'monthly'){
                                    $begin = new \DateTime( $request->availability_date );

                                    $endDate = date('Y-m-d', strtotime("+1 day", strtotime($request->deadline)));

                                    $end = new \DateTime( $endDate );

                                    if($request->repeat == 'daily'){
                                        $repeat_type = '1 day';
                                    }elseif($request->repeat == 'monthly'){
                                        $repeat_type = '1 month';
                                    }
                                    $interval = \DateInterval::createFromDateString($repeat_type);
                                    $period = new \DatePeriod($begin, $interval, $end);

                                    foreach ( $period as $dt ){
                                        $data[] = [
                                            'user_id' => \Auth::user()->id_user,
                                            'availability_type' => $request->availability_type,
                                            'availability_date' => $dt->format( "Y-m-d" ),
                                            'from_time' => $request->from_time,
                                            'to_time' => $request->to_time,
                                            'repeat' => $request->repeat,
                                            'deadline' => $request->deadline,
                                            'repeat_group' => $availability_id ? $availability_id : $max_repeat_group,
                                            'created' => date('Y-m-d H:i:s'),
                                            'updated' => date('Y-m-d H:i:s'),
                                        ];
                                    }
                                }elseif($request->repeat == 'weekly'){
                                    $date = ___days_between($request->availability_date, $request->deadline, $request->availability_day);

                                    foreach ($date as $d) {
                                        $data[] = [
                                            'user_id' => \Auth::user()->id_user,
                                            'availability_type' => $request->availability_type,
                                            'availability_date' => $d,
                                            'from_time' => $request->from_time,
                                            'to_time' => $request->to_time,
                                            'repeat' => $request->repeat,
                                            'deadline' => $request->deadline,
                                            'repeat_group' => $availability_id ? $availability_id : $max_repeat_group,
                                            'availability_day' => date('l', strtotime($d)),
                                            'created' => date('Y-m-d H:i:s'),
                                            'updated' => date('Y-m-d H:i:s'),
                                        ];
                                    }
                                }

                                if(!empty($data)){
                                    $isInserted = \Models\Talents::setTalentAvailability(\Auth::user()->id_user, $max_repeat_group, $data, $availability_id, $request->availability_date, $request->deadline, $request->availability_type);
                                }

                                /* RECORDING ACTIVITY LOG */
                                event(new \App\Events\Activity([
                                    'user_id'           => $request->user()->id_user,
                                    'user_type'         => 'talent',
                                    'action'            => 'webservice-talent-set-availability',
                                    'reference_type'    => 'users',
                                    'reference_id'      => $request->user()->id_user
                                ]));

                                if(!empty($isInserted)){
                                    $this->status = true;
                                    $this->message = 'M0000';
                                    $this->jsondata = ___availability_list($isInserted);
                                }

                                if(!empty($isInserted)){
                                    $this->status = true;
                                    $this->jsondata = $isInserted;
                                }
                            }else{
                                $this->jsondata = (object)[];
                                $this->message = 'M0172';
                            }
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
         * [This method is used to delete setting user's availability ]
         * @param  Request
         * @return Json Response
         */

        public function step_four_delete_availability(Request $request){
            
            
            if(empty($this->post['id_availability'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_availability');
            }else{    
                $isInserted = \Models\Talents::delete_availability($request->user()->id_user,$this->post['id_availability']);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-step-four-delete-availability',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));
                
                $this->status = true;    
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

        public function step_five_social_connect(Request $request){
            
            
            if(empty($this->post['social_key']) || !in_array($this->post['social_key'], valid_social_keys())){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'social_key');
            }/*else if(empty($this->post['social_id'])){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'social_id');
            }*/else{
                $request->request->add([$request->social_key => $request->social_id]);
                $validator = \Validator::make($request->all(), [
                    $request->social_key    => [Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],
                ],[
                    sprintf('%s.unique',$request->social_key)   => 'M0126',
                ]);

                if($validator->fails()){
                    $this->message = $validator->messages()->first();
                }else{
                    $isUpdated = \Models\Talents::change($request->user()->id_user,[$request->social_key => $request->social_id, 'updated' => date('Y-m-d H:i:s')]);
                    
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-step-five-social-connect',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));
                                        
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
         * [This method is used to change mobile ]
         * @param  Request
         * @return Json Response
         */

        public function step_five_change_mobile(Request $request){
            
            
            $validator = \Validator::make($request->all(), [
                'mobile'                    => array_merge([Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],validation('phone_number')),
                'country_code'              => array_merge(['required'],validation('country_code')),                
            ],[
                'country_code.string'       => 'M0074',
                'country_code.required'     => 'M0164',
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
                
                $isUpdated = \Models\Talents::change(
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
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-step-five-change-mobile',
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
         * [This method is used for mobile verification]
         * @param  Request
         * @return Json Response
         */

        public function step_five_verify_mobile(Request $request){
            
            
            $validator = \Validator::make($request->all(), [
                'otp_password'              => ['required']
            ],[
                'otp_password.required'     => 'M0130',
            ]);

            if($validator->fails()){
                $this->message              = $validator->messages()->first();
            }else{
                $result = (array) \Models\Talents::findById($request->user()->id_user,['otp_password']);

                if($result['otp_password'] == $request->otp_password){
                    $created_date               = date('Y-m-d H:i:s');
                    $otp_shuffle                = \Cache::get('configuration')['otp_shuffle'];
                    $otp_length                 = \Cache::get('configuration')['otp_length'];
                    $otp_expired                = \Cache::get('configuration')['otp_expired'];

                    $otp_password               = substr(str_shuffle($otp_shuffle), 2, $otp_length);
                    $otp_expired                = date('Y-m-d H:i:s',strtotime("+".$otp_expired." minutes", strtotime($created_date)));
                    
                    $this->message = 'M0132';
                    $this->status = true;
                    $isUpdated = \Models\Talents::change(
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
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-step-five-verify-mobile',
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
         * [This method is used for job finding ]
         * @param  Request
         * @return Json Response
         */
        
        public function find_jobs(Request $request){
            
            
            $request['language']    = \App::getLocale();
            
            $prefix                 = DB::getTablePrefix();
            $this->status           = true;
            $html                   = "";
            $page                   = (!empty($request->page))?$request->page:1;
            $limit                  = DEFAULT_PAGING_LIMIT;
            $offset                 = ($page-1)*DEFAULT_PAGING_LIMIT;
            $search                 = !empty($request->search)? $request->search : '';

            if(empty($request->sortby_filter)){
                $sort = "FIELD(project_status,'pending','initiated','closed'), {$prefix}projects.id_project DESC";
            }else{
                $sort = sprintf("%s%s",$prefix,___decodefilter($request->sortby_filter));
            }

            $projects =  \Models\Projects::talent_jobs(\Auth::user())->proposalStatus(\Auth::user()->id_user);
            
            if(!empty($request->employment_type_filter)){
                $projects->where('projects.employment',$request->employment_type_filter);
            }

            if(!empty($request->price_min_filter) && empty($request->price_max_filter)){
                $projects->havingRaw("(price >= $request->price_min_filter)");
            }else if(empty($request->price_min_filter) && !empty($request->price_max_filter)){
                $projects->havingRaw("(price <= $request->price_max_filter )");
            }else if(!empty($request->price_min_filter) && !empty($request->price_max_filter)){
                $projects->havingRaw("(price >= $request->price_min_filter AND price <= $request->price_max_filter )");
            }

            if(!empty($request->industry_filter)){
                $projects->when($request->industry_filter,function($q) use ($request){
                    $q->whereHas('industries.industries',function($q) use($request){
                        $q->whereIn('projects_industries.industry_id',$request->industry_filter);
                    });    
                });
            }    

            if(!empty($request->skills_filter)){
                $projects->when($request->skills_filter,function($q) use ($request){
                    $q->whereHas('skills.skills',function($q) use($request){
                        $q->whereIn('project_required_skills.skill_id',$request->skills_filter);
                    });    
                });
            }

            if(!empty($request->startdate_filter) && empty($request->enddate_filter)){
                $projects->when($request->startdate_filter,function($q) use ($request,$prefix){
                    $q->whereRaw(sprintf("(DATE({$prefix}projects.startdate) >= '%s')",___convert_date($request->startdate_filter,'MYSQL')));    
                });
            }else if(empty($request->startdate_filter) && !empty($request->enddate_filter)){
                $projects->when($request->startdate_filter,function($q) use ($request,$prefix){
                    $q->whereRaw(sprintf("(DATE({$prefix}projects.enddate) >= '%s')",___convert_date($request->endate_filter,'MYSQL')));    
                });
            }else if(!empty($request->startdate_filter) && !empty($request->enddate_filter)){
                $projects->when($request->startdate_filter,function($q) use ($request,$prefix){
                    $q->whereRaw(sprintf("(DATE({$prefix}projects.startdate) >= '%s' AND DATE({$prefix}projects.enddate) <= '%s')",___convert_date($request->startdate_filter,'MYSQL'),___convert_date($request->enddate_filter,'MYSQL')));    
                });
            }

            if(!empty($request->expertise_filter)){
                $projects->when($request->expertise_filter,function($q) use ($request,$prefix){
                    $q->whereIn("projects.expertise",$request->expertise_filter);
                });
            }

            if(!empty(trim($search))){
                $search = trim($search);
                $projects->havingRaw("(
                    title LIKE '%$search%' 
                    OR
                    description LIKE '%$search%' 
                    OR
                    company_name LIKE '%$search%' 
                    OR
                    expertise LIKE '%$search%' 
                    OR
                    employment LIKE '%$search%' 
                    OR
                    other_perks LIKE '%$search%' 
                    OR
                    price LIKE '%$search%' 
                    OR
                    description LIKE '%$search%' 
                    OR
                    description LIKE '%$search%'
                )");  
            }                

            $projects->where("projects.is_cancelled",DEFAULT_NO_VALUE);
            $projects->whereNotIn("projects.status",['draft','trash']);
            $projects->havingRaw("(
                (awarded = '".DEFAULT_NO_VALUE."' AND proposal_status = 'applied')
                OR
                (awarded = '".DEFAULT_NO_VALUE."' AND project_status = 'pending' AND DATE(startdate) >= '".date('Y-m-d')."') 
                OR 
                (proposal_status = 'accepted' AND project_status IN('pending','initiated','closed','completed'))
            )");
            $projects->groupBy(['projects.id_project']);
            $projects->orderByRaw($sort);

            $projects                = $projects->limit($limit)->offset($offset)->get();

            $proposal_count = 0;
            $proposal_count = \Models\Proposals::get_talent_proposal_count(\Auth::user()->id_user);

            if(!empty($projects->count())){
                $this->jsondata = api_resonse_project($projects);
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata,
                    'talent_proposal_count' => $proposal_count
                ])
            );           
        }        

        /**
         * [This method is used for job in detail]
         * @param  Request
         * @return Json Response
         */

        public function job_detail(Request $request){
            $language   = \App::getLocale();
            $prefix     = DB::getTablePrefix();
            
            if(!empty($this->post['id_project'])){
                $project_id     = $this->post['id_project'];
                $user           = $request->user();
                
                $project = \Models\Projects::talent_jobs($user)->withCount([
                    'reviews' => function($q){
                        $q->where('sender_id',auth()->user()->id_user);
                    }
                ])
                ->with([
                    'industries.industries' => function($q) use($language,$prefix){
                        $q->select(
                            'id_industry',
                            \DB::Raw("IF(({$language} != ''),`{$language}`, `en`) as name")
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
                    'proposal' => function($q) use($user){
                        $q->defaultKeys()->with([
                            'talent' => function($q){
                                $q->defaultKeys();
                            }
                        ])
                        ->where('user_id',$user->id_user)
                        ->where('talent_proposals.status','!=','rejected');
                    },
                    'projectlog' => function($q) use($user){
                        $q->select('project_id')->totalTiming()->where('talent_id',$user->id_user)->groupBy(['project_id']);
                    },
                    'employer' => function($q) use($language,$prefix,$user){
                        $q->select(
                            'id_user',
                            'company_name',
                            'company_biography',
                            'company_profile',
                            'company_work_field',
                            'contact_person_name',
                            'company_website',
                            \DB::Raw("YEAR({$prefix}users.created) as member_since"),
                            \DB::Raw("CONCAT({$prefix}users.first_name, ' ',{$prefix}users.last_name) AS name")
                        );

                        $q->isTalentSavedEmployer($user->id_user);
                        $q->companyLogo();
                        $q->city();
                        $q->review();
                        $q->totalHirings();
                        $q->withCount(['projects']);
                        $q->with([
                            'transaction' => function($q) use($prefix){
                                $q->select(
                                    'id_transactions',
                                    'transaction_user_id'
                                )->totalPaidByEmployer();
                                $q->groupBy('transaction_user_id');
                            },
                        ]);
                    },
                    'dispute' => function($q){
                        $q->defaultKeys();
                    },
                    'chat' => function($q){
                        $q->defaultKeys()->receiver()->where('sender_id',auth()->user()->id_user);
                    }
                ])->where('id_project',$project_id)->get()->first();

                if(!empty($project)){
                    $this->status   = true;

                    if(!empty($project['employer'])){
                        $project['employer']->field_name = !empty($project['employer']->company_work_field) ? ___cache("work_fields",$project['employer']->company_work_field) : N_A;
                    }

                    $userData           = \Models\Talents::get_user($request->user());
                    $subindustriesID    = array_column($userData['subindustry'], 'id_industry');
                    $skillsID           = array_column($userData['skills'], 'id_skill');

                    $similarjobs = \Models\Projects::defaultKeys()
                    ->projectPrice()
                    ->companyName()
                    ->companyLogo()
                    ->having('project_status','=','pending')
                    ->where('id_project','!=',$project_id)
                    ->where(function($q) use($subindustriesID,$skillsID){
                        $q->whereHas('subindustries.subindustries',function($q) use($subindustriesID){
                            $q->whereIn('subindustry_id',$subindustriesID);
                        });
                        $q->orWhereHas('skills.skills',function($q) use($skillsID){
                            $q->whereIn('skill_id',$skillsID);
                        });
                    })
                    ->where("projects.is_cancelled",DEFAULT_NO_VALUE)
                    ->where('projects.user_id','!=',$project->company_id)
                    ->whereNotIn('projects.status',['draft','trashed'])
                    ->orderBy('id_project','DESC')
                    ->limit(SIMILAR_JOBS_LIMIT)
                    ->get();

                    $otherjobs = \Models\Projects::defaultKeys()
                    ->projectPrice()
                    ->companyName()
                    ->companyLogo()
                    ->where(function($q) use($subindustriesID,$skillsID){
                        $q->whereHas('subindustries.subindustries',function($q) use($subindustriesID){
                            $q->whereIn('subindustry_id',$subindustriesID);
                        });
                        $q->orWhereHas('skills.skills',function($q) use($skillsID){
                            $q->whereIn('skill_id',$skillsID);
                        });
                    })
                    ->having('project_status','=','pending')
                    ->where('id_project','!=',$project_id)
                    ->where('projects.user_id','=',$project->company_id)
                    ->where("projects.is_cancelled",DEFAULT_NO_VALUE)
                    ->whereNotIn('projects.status',['draft','trashed'])
                    ->orderBy('id_project','DESC')
                    ->limit(EMPLOYER_OTHER_JOBS_LIMIT)
                    ->get();

                    $project->similarjobs = api_resonse_project($similarjobs);
                    $project->otherjobs = api_resonse_project($otherjobs);
                    $project = json_decode(json_encode($project),true);

                    $project['created'] = ___ago($project['created']);
                    
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

                    if($project['is_cancelled'] == DEFAULT_YES_VALUE){
                        $project['created'] = trans('general.M0578').' '.___ago($project['canceldate']);
                    }elseif($project['project_status'] == 'closed'){
                        $project['created'] = trans('general.M0520').' '.___ago($project['closedate']);
                    }else{
                        $project['created'] = trans('general.M0177').' '.___ago($project['created']);
                    }

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
         * [This method is used to view employer's review]
         * @param  Request
         * @return Json Response
         */

        public function employer_reviews(Request $request){
            $page = (!empty($request->page))?$request->page:1;

            if(!empty($request->employer_id)){
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
                ->where('receiver_id',$request->employer_id)
                ->orderBy('id_review','DESC')
                ->limit(DEFAULT_PAGING_LIMIT)
                ->offset(($request->page - 1)*DEFAULT_PAGING_LIMIT)
                ->get();
                
                $this->status = true;
                $this->jsondata = api_resonse_common($reviews,true);
            }else{
                $this->message = "M0121";
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }        

        /**
         * [This method is used to save and unsave job]
         * @param  Request
         * @return Json Response
         */

        public function job_save_unsave(Request $request){
            
     
            $my_job = \Models\Talents::get_job($request->user(),sprintf(" {$this->prefix}projects.id_project = %s ",$this->post['id_project']),'count',['projects.id_project']);

            if(!empty($my_job)){
                $isSaved = \Models\Talents::save_job($request->user()->id_user,$request->id_project);
                
                if(!empty($isSaved['status'])){
                    if($isSaved['action'] == 'deleted_saved_job'){
                    
                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => $request->user()->id_user,
                            'user_type'         => 'talent',
                            'action'            => 'webservice-talent-delete-saved-job',
                            'reference_type'    => 'projects',
                            'reference_id'      => $request->id_project
                        ]));

                        $this->status = true;
                        $this->message = 'M0168';
                    }else if($isSaved['action'] == 'saved_job'){
                    
                        /* RECORDING ACTIVITY LOG */
                        event(new \App\Events\Activity([
                            'user_id'           => $request->user()->id_user,
                            'user_type'         => 'talent',
                            'action'            => 'webservice-talent-save-job',
                            'reference_type'    => 'projects',
                            'reference_id'      => $request->id_project
                        ]));

                        $this->status = true;
                        $this->message = 'M0167';
                    }else{
                        $this->message = 'M0022';
                    }
                }else{
                    $this->message = 'M0022';
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
         * [This method is used for proposal submission ]
         * @param  Request
         * @return Json Response
         */

        /******* For Submitting Proposals *******/
        public function submit_proposal(Request $request){
            
            $fileinsertarr          = [];
            $isInserted             = [];
            
            $project_id             = $request->project_id;
            $project                = \Models\Projects::where('id_project',$project_id)->select(['awarded','employment'])->get()->first();

            if($project->awarded == DEFAULT_NO_VALUE){
                $userInfo = \Models\Talents::get_user(\Auth::user(),true);
                // dd($userInfo['industry'],\Auth::user()->country);
                if(empty(\Auth::user()->country)
                 || empty($userInfo['industry'])){
                    $this->status = false;
                    $this->message = trans("website.W0967");
                    $this->jsondata = [
                        'type'          => 'editprofile',
                        'title'         => trans('general.M0043'),
                        'messages'      => trans('website.W0967'),
                        'button_one'    => trans('general.M0533'),
                        'button_two'    => trans('general.M0534')
                    ];
                }
                else{
                    $industry = $userInfo['industry'][0]['id_industry'];
                    $commConfig = \Models\Payout_mgmt::toGetPayoutDetails($userInfo['country'], $industry);
                    
                    if($userInfo['is_register'] == 'Y' && $commConfig['accept_escrow']=='yes' && empty($userInfo['paypal_id'])){
                        
                        #$this->status = false;
                        #$this->message = trans("website.W0718");
                        $this->jsondata = [
                            'type'          => 'confirm',
                            'title'         => trans('general.M0043'),
                            'messages'      => trans('general.M0532'),
                            'button_one'    => trans('general.M0533'),
                            'button_two'    => trans('general.M0534')
                        ];
                    }
                    elseif($userInfo['is_register'] == 'N' && $commConfig['non_reg_accept_escrow']=='yes' && empty($userInfo['paypal_id'])){
                        #$this->status = false;
                        #$this->message = trans("website.W0718");
                        $this->jsondata = [
                            'type'          => 'confirm',
                            'title'         => trans('general.M0043'),
                            'messages'      => trans('general.M0532'),
                            'button_one'    => trans('general.M0533'),
                            'button_two'    => trans('general.M0534')
                        ];
                    }
                    else{
                        // if(!empty($request->user()->paypal_id)){
                            $total_proposals_count  = \Models\Proposals::select()->where([
                                'project_id'    => $project_id, 
                                'user_id'       => \Auth::user()->id_user
                            ])->get()->count();

                            if($total_proposals_count <= TALENT_SUBMIT_PROPOSAL_LIMIT || !empty($request->id_proposal)){
                                if(1){
                                    $validation_comments = validation('description'); unset($validation_comments[0]);
                                    if(empty($request->from_time)){
                                        $request->request->add(['from_time' => '']);
                                    }else if($request->from_time == '00:00:00'){
                                        $request->request->add(['from_time' => '']);
                                    }

                                    if(empty($request->to_time)){
                                        $request->request->add(['to_time' => '']);
                                    }else if($request->to_time == '00:00:00'){
                                        $request->request->add(['to_time' => '']);
                                    }

                                    if($project->employment == 'hourly'){
                                        $validation                     = [
                                            "from_time"                 => array_merge(['required'],validation('time')),
                                            "to_time"                   => array_merge(['required','different:from_time','invalid_time_range:from_time'],validation('time')),       
                                            "quoted_price"              => validation('quoted_price'),
                                            "comments"                  => $validation_comments,
                                            "project_id"                => validation('project_id'),
                                            "file"                      => array_merge(validation('document'),['max:'.PROPOSAL_DOCUMENT_MAX_SIZE]),
                                        ];
                                    }else{
                                        $validation                     = [
                                            "quoted_price"              => validation('quoted_price'),
                                            "comments"                  => $validation_comments,
                                            "project_id"                => validation('project_id'),
                                            "file"                      => array_merge(validation('document'),['max:'.PROPOSAL_DOCUMENT_MAX_SIZE]),
                                        ];
                                    }

                                    $validator = \Validator::make($request->all(), $validation,[
                                        'quoted_price.numeric'          => 'M0199',
                                        'quoted_price.required'         => 'M0200',
                                        'quoted_price.min'              => 'M0438',
                                        'quoted_price.max'              => 'M0500',
                                        'project_id.required'           => 'M0201',
                                        'file.validate_file_type'       => 'M0119',
                                        'file.max'                      => 'M0499',        
                                        'comments.string'               => 'M0204',         
                                        'comments.regex'                => 'M0204',         
                                        'comments.max'                  => 'M0205',         
                                        'comments.min'                  => 'M0206',
                                        "from_time.required"            => 'M0159',
                                        "from_time.string"              => 'M0160',
                                        "from_time.regex"               => 'M0160',
                                        "to_time.required"              => 'M0161',
                                        "to_time.string"                => 'M0162',
                                        "to_time.regex"                 => 'M0162',
                                        "to_time.different"             => 'M0222',
                                        "to_time.one_hour_difference"   => 'M0223',
                                        "to_time.invalid_time_range"    => 'M0224',
                                    ]);

                                    $validator->after(function($v) use($request){
                                        if(!empty($request->input('coupon_code'))){

                                        $apiID  = "c9ce23b8-0c52-4095-b416-d92c49be9c3b";
                                        $apiKey = "4bfd2a38-1c28-41de-aebd-59c3c088b4af";
                                        $client = new VoucherifyClient($apiID, $apiKey);

                                        try{
                                            $get_voucher = $client->vouchers->get($request->input('coupon_code'));
                                            $validate_voucher = $client->validations->validateVoucher($request->input('coupon_code'));
                                        }catch(ClientException $exception){
                                            $v->errors()->add('coupon_code', 'Entered coupon code is invalid');
                                        }

                                        if(!empty($validate_voucher) && $validate_voucher->valid == true){

                                            try{
                                                $redeem_voucher = $client->redemptions->redeem($request->input('coupon_code'));
                                                $request->request->add(['api_coupon_response' => $redeem_voucher]);
                                                $coupon_code_id = \DB::table('coupon')->select('*')->where('code','=',$request->input('coupon_code'))->first();

                                                $set_coupon_code_id = $coupon_code_id->id;
                                                $couponStatus = \Models\Coupon::validateCoupon($set_coupon_code_id, \Auth::user()->id_user);

                                                $currentTime = strtotime(date('Y-m-d H:i:s'));
                                                #dd(strtotime($coupon_code_id->start_date) .'>='. $currentTime .'&&'. strtotime($coupon_code_id->expiration_date) .'<='. $currentTime);
                                                if(strtotime($coupon_code_id->start_date) >= $currentTime || strtotime($coupon_code_id->expiration_date) <= $currentTime){
                                                    $v->errors()->add('coupon_code', 'Entered coupon code is expired.');
                                                }
                                                /*elseif($couponStatus){
                                                    $v->errors()->add('coupon_code', 'Entered coupon code is already in use.');
                                                }*/
                                                
                                                $request->request->add(['coupon_id' => $set_coupon_code_id]);

                                            }catch(ClientException $exception){
                                                $v->errors()->add('coupon_code', 'Entered coupon code could not be redeemed');
                                            }
                                        }
                                        elseif(!empty($validate_voucher) && $validate_voucher->valid == false){
                                            $v->errors()->add('coupon_code', 'Entered coupon code is expired or invalid');
                                        }
                                    }
                                    });
                                    
                                    if($validator->fails()){
                                        $this->message   = $validator->messages()->first();
                                    }else{
                                        $working_hours = time_difference($request->from_time, $request->to_time);
                                        $daily_working_hours    = sprintf("%s:00:00",___configuration(['daily_working_hours'])['daily_working_hours']);

                                        $request->request->add(['working_hours' => $working_hours]);
                                        if((empty($request->working_hours) || $request->working_hours === '00:00') && $project->employment == 'hourly'){
                                            $this->message = 'M0523';
                                        }else if($project->employment == 'hourly' && strtotime($request->working_hours) > strtotime($daily_working_hours)){
                                            $this->message = sprintf(trans('general.M0524'),substr($daily_working_hours, 0,-3));
                                        }else{
                                            if(empty($request->id_proposal)){
                                                $folder          = 'uploads/proposals/';
                                                $project_id      = $request->project_id;
                                                
                                                $insertArr = [
                                                    'project_id'     => $project_id,
                                                    'user_id'        => \Auth::user()->id_user,
                                                    'price_unit'     => $request->currency,
                                                    'submission_fee' => NULL,
                                                    'quoted_price'   => $request->quoted_price,
                                                    'working_hours'  => $request->working_hours,
                                                    'from_time'      => (!empty($request->from_time))?$request->from_time:'00:00:00',
                                                    'to_time'        => (!empty($request->to_time))?$request->to_time:'00:00:00',
                                                    'comments'       => $request->comments,
                                                    'status'         => 'applied',
                                                    'created'        => date('Y-m-d H:i:s'),
                                                    'updated'        => date('Y-m-d H:i:s')
                                                ];

                                                if(!empty($request->coupon_id)){
                                                    $insertArr['coupon_id'] = $request->coupon_id;
                                                }

                                                /*Check for Admin Manual payout. Add escrow type & pay commision(if present)*/
                                                $job_industry_id = \Models\ProjectsIndustries::get_industry_by_jobID($project_id);
                                                $talent_country_id = !empty(\Auth::user()->country) ?\Auth::user()->country:0;
                                                $payout_det = \Models\Payout_mgmt::toGetPayoutDetails($talent_country_id,$job_industry_id);
                                                
                                                if(!empty($payout_det)){
                                                    if(\Auth::user()->is_register == 'Y' && $payout_det['accept_escrow'] == 'yes'){
                                                        $insertArr['accept_escrow'] = $payout_det['accept_escrow'];
                                                        $insertArr['pay_commision_percent'] = $payout_det['pay_commision_percent'];   
                                                    }
                                                    elseif(\Auth::user()->is_register == 'N' && $payout_det['non_reg_accept_escrow'] == 'yes'){
                                                        $insertArr['accept_escrow'] = $payout_det['non_reg_accept_escrow'];
                                                        $insertArr['pay_commision_percent'] = $payout_det['pay_commision_percent'];   
                                                    }
                                                    else{
                                                        $insertArr['accept_escrow'] = 'no';
                                                        $insertArr['pay_commision_percent'] = '0.00';
                                                    }
                                                }
                                                else{
                                                    $insertArr['accept_escrow'] = 'no';
                                                    $insertArr['pay_commision_percent'] = '0.00';
                                                }

                                                $proposal = \Models\Proposals::create($insertArr);

                                                if(!empty($request->file)){
                                                    $folder = 'uploads/proposals/';                    
                                                    $uploaded_file = upload_file($request,'file',$folder);
                                                    $fileinsertarr = [
                                                        'user_id'       => $request->user()->id_user,
                                                        'record_id'     => $proposal->id_proposal,
                                                        'reference'     => 'proposal',
                                                        'filename'      => $uploaded_file['filename'],
                                                        'extension'     => $uploaded_file['extension'],
                                                        'folder'        => $folder,
                                                        'type'          => 'proposal',
                                                        'reference'     => 'proposal',
                                                        'size'          => $uploaded_file['size'],
                                                        'is_default'    => DEFAULT_NO_VALUE,
                                                        'created'       => date('Y-m-d H:i:s'),
                                                        'updated'       => date('Y-m-d H:i:s'),
                                                    ];   
                                                    
                                                    $isInserted[] = \Models\Talents::create_file($fileinsertarr,true,true);
                                                }
                                            }else{
                                                if(!empty($request->file)){
                                                    $folder = 'uploads/proposals/';                    
                                                    $uploaded_file = upload_file($request,'file',$folder);
                                                    
                                                    $fileUpdate = [
                                                        'filename'      => $uploaded_file['filename'],
                                                        'extension'     => $uploaded_file['extension'],
                                                        'folder'        => $folder,
                                                        'size'          => $uploaded_file['size']
                                                    ];   
                                                    
                                                    $proposal_file = \Models\File::where('user_id',$request->user()->id_user)
                                                    ->where('record_id',$request->id_proposal)
                                                    ->where('type','proposal');

                                                    if(!empty($proposal_file->get()->count())){
                                                        $proposal_file->update($fileUpdate);
                                                    }else{
                                                        $updateArr = [
                                                            'user_id'       => $request->user()->id_user,
                                                            'record_id'     => $request->id_proposal,
                                                            'reference'     => 'proposal',
                                                            'filename'      => $uploaded_file['filename'],
                                                            'extension'     => $uploaded_file['extension'],
                                                            'folder'        => $folder,
                                                            'type'          => 'proposal',
                                                            'reference'     => 'proposal',
                                                            'size'          => $uploaded_file['size'],
                                                            'is_default'    => DEFAULT_NO_VALUE,
                                                            'created'       => date('Y-m-d H:i:s'),
                                                            'updated'       => date('Y-m-d H:i:s'),
                                                        ];
                                                        
                                                        $proposal_file->insert($updateArr);
                                                    }
                                                }

                                                $updateData  = [
                                                    'price_unit'     => $request->currency,
                                                    'quoted_price'   => $request->quoted_price,
                                                    'comments'       => $request->comments,
                                                    'working_hours'  => $request->working_hours,
                                                    'from_time'      => (!empty($request->from_time))?$request->from_time:'00:00:00',
                                                    'to_time'        => (!empty($request->to_time))?$request->to_time:'00:00:00',
                                                    'status'         => 'applied',
                                                    'updated'        => date('Y-m-d H:i:s')
                                                ];

                                                if(!empty($request->coupon_id)){
                                                    $updateData['coupon_id'] = $request->coupon_id;
                                                }
                                                else{
                                                    $updateData['coupon_id'] = 0;
                                                }

                                                /*Check for Admin Manual payout. Add escrow type & pay commision(if present)*/
                                                $job_industry_id = \Models\ProjectsIndustries::get_industry_by_jobID($project_id);
                                                $talent_country_id = !empty(\Auth::user()->country) ?\Auth::user()->country:0;
                                                $payout_det = \Models\Payout_mgmt::toGetPayoutDetails($talent_country_id,$job_industry_id);

                                                if(!empty($payout_det)){
                                                    if(\Auth::user()->is_register == 'Y' && $payout_det['accept_escrow'] == 'yes'){
                                                        $updateData['accept_escrow'] = $payout_det['accept_escrow'];
                                                        $updateData['pay_commision_percent'] = $payout_det['pay_commision_percent'];   
                                                    }elseif(\Auth::user()->is_register == 'N' && $payout_det['non_reg_accept_escrow'] == 'yes'){
                                                        $updateData['accept_escrow'] = $payout_det['non_reg_accept_escrow'];
                                                        $updateData['pay_commision_percent'] = $payout_det['pay_commision_percent'];   
                                                    }else{
                                                        $updateData['accept_escrow'] = 'no';
                                                        $updateData['pay_commision_percent'] = '0.00';
                                                    }
                                                }else{
                                                    $updateData['accept_escrow'] = 'no';
                                                    $updateData['pay_commision_percent'] = '0.00';
                                                }

                                                \Models\Proposals::where('id_proposal',$request->id_proposal)->update($updateData);
                                                \Models\Notifications::where('notification','JOB_UPDATED_BY_EMPLOYER')->where('notify',$request->user()->id_user)->delete();
                                                $proposal = (object)['id_proposal' => $request->id_proposal];
                                            }  
                                            
                                            $company_id = \Models\Projects::where('id_project',$project_id)->select(['user_id'])->get()->first()->user_id;
                                            
                                            if(empty($request->id_proposal)){
                                                \Models\Talents::send_chat_request($request->user()->id_user,$company_id,$project_id,$proposal->id_proposal);                              
                                            }else{
                                                \Models\Talents::send_chat_request($request->user()->id_user,$company_id,$project_id,$proposal->id_proposal,NULL,true);                              
                                            }        

                                            /* RECORDING ACTIVITY LOG */
                                            event(new \App\Events\Activity([
                                                'user_id'           => $request->user()->id_user,
                                                'user_type'         => 'talent',
                                                'action'            => 'webservice-talent-submit-proposals',
                                                'reference_type'    => 'talent_proposals',
                                                'reference_id'      => $proposal->id_proposal
                                            ]));

                                            $this->status = true;
                                            $this->message = "M0369";
                                        }
                                    }
                                }else{
                                    $this->message = "M0515";                    
                                }
                            }else{
                                $this->message = "M0516";                    
                            }
                        /*}else{
                            $this->message = "M0532";
                            $this->jsondata = [
                                'type'          => 'confirm',
                                'title'         => trans('general.M0043'),
                                'messages'      => trans('general.M0532'),
                                'button_one'    => trans('general.M0533'),
                                'button_two'    => trans('general.M0534')
                            ];
                        }*/
                    }
                }
                
            }else{
                $this->message = "M0558";
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for active proposals ]
         * @param  Request
         * @return Json Response
         */

        public function proposals(Request $request, $type = 'active'){
            
            $this->status = true;
            $page = (!empty($request->page))?$request->page:1;

            $proposals = \Models\Proposals::talents($type, \Auth::user()->id_user,$page);
            
            if(!empty($proposals)){
                $this->jsondata = api_resonse_common($proposals);
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }
        
        /**
         * [This method is used for proposals in detail]
         * @param  Request
         * @return Json Response
         */

        public function proposal_details(Request $request){
            if(!empty($request->id_proposal)){   
                $proposal = \Models\Proposals::defaultKeys()->quotedPrice()->where('user_id',auth()
                ->user()->id_user)
                ->where('id_proposal',$request->id_proposal)
                ->with([
                    'file' => function($q){
                        $q->defaultKeys();
                    },
                    'project' => function($q){
                        $q->defaultKeys()
                        ->projectPrice()
                        ->companyName()
                        ->companyLogo()
                        ->isProjectSaved(auth()->user()->id_user);
                    }
                ])
                ->get()
                ->first();

                if(!empty($proposal)){
                    $proposal = json_decode(json_encode($proposal),true);
                    
                    $proposal['created']                = ___d($proposal['created']);
                    $proposal['quoted_price']           = ___format($proposal['quoted_price'],false,false,false);
                    $proposal['price_unit']             = ___cache('currencies')[$proposal['price_unit']];
                    
                    $proposal['project']['created']     = ___d($proposal['project']['created']);
                    $proposal['project']['timeline']    = ___date_difference($proposal['project']['startdate'],$proposal['project']['enddate']);
                    $proposal['project']['price_unit']  = ___cache('currencies')[request()->currency];
                    $proposal['project']['price']       = ___format($proposal['project']['price'],true,false);

                    $coupon_detail = \Models\Proposals::getCouponDetailByProposalId($request->id_proposal);
                    if(!empty($coupon_detail)){
                        $proposal['coupon_code'] = $coupon_detail['code'];
                    }
                    else{
                        $proposal['coupon_code'] = '';
                    }

                    $proposal = api_resonse_common($proposal,false,['created']);
                    $this->jsondata = $proposal;
                    $this->status = true;
                }else{
                    $this->message = 'M0121';
                    $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_proposal');
                }
            }else{
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'id_proposal');
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }   

        /**
         * [This method is used for finding a user's saved,current,scheduled and past job]
         * @param  Request
         * @return Json Response
         */ 

        public function myjobs(Request $request, $type="saved"){
            $this->status   = true;
            $prefix         = DB::getTablePrefix();
            
            
            if(empty($request->page)){
                $request->page  = 1;
            }

            $projects = \Models\Projects::talent_jobs($request->user())->addSelect(\DB::Raw("'{$type}' as type"));
            $user = auth()->user(); 
            
            if($type == 'saved'){
                $projects->whereRaw("{$prefix}saved_jobs.id_saved IS NOT NULL");
            }else if($type == 'current'){
                $projects->with([
                    'projectlog' => function($q) use($user){
                        $q->select('project_id')->totalTiming()->where('talent_id',$user->id_user)->groupBy(['project_id']);
                    },
                    'proposal' => function($q){
                        $q->defaultKeys();
                    }
                ])->whereHas('proposal',function($q) use($user){
                    $q->where('talent_proposals.status','accepted');
                    $q->where('user_id',request()->user()->id_user);
                })
                ->havingRaw("(project_status = 'initiated' OR project_status = 'completed')")    
                ->having('is_cancelled','=',DEFAULT_NO_VALUE);
            }else if($type == 'scheduled'){
                $projects->with([
                    'proposal' => function($q){
                        $q->defaultKeys();
                    }
                ])->whereHas('proposal',function($q) use($user){
                    $q->where('talent_proposals.status','accepted');
                    $q->where('user_id',request()->user()->id_user);
                })
                ->having('project_status','=','pending')
                ->having('is_cancelled','=',DEFAULT_NO_VALUE);
            }else if($type == 'history'){
                $projects->with([
                    'proposal' => function($q){
                        $q->defaultKeys();
                    }
                ])->whereHas('proposal',function($q) use($user){
                    $q->where('talent_proposals.status','accepted');
                    $q->where('user_id',request()->user()->id_user);
                })->havingRaw("(project_status = 'closed' OR is_cancelled = '".DEFAULT_YES_VALUE."')");
            }

            if(empty($request->sortby_filter)){
                $sort = "{$prefix}projects.id_project DESC";
            }else{
                $sort = sprintf("%s%s",$prefix,___decodefilter($request->sortby_filter));
            }

            $result = $projects->orderByRaw($sort)->groupBy(['projects.id_project'])->limit(DEFAULT_PAGING_LIMIT)->offset(($request->page - 1)*DEFAULT_PAGING_LIMIT)->get();
                     
            $this->jsondata = api_resonse_project($result);

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => [
                        'result' => $this->jsondata,
                        'count' => [
                            'current'   => (string) \Models\Projects::talent_jobs($request->user())->addSelect(\DB::Raw("'{$type}' as type"))->with(['projectlog' => function($q) use($user){$q->select('project_id')->totalTiming()->where('talent_id',$user->id_user)->groupBy(['project_id']); }, 'proposal' => function($q){$q->defaultKeys(); } ])->whereHas('proposal',function($q) use($user){$q->where('talent_proposals.status','accepted'); $q->where('user_id',request()->user()->id_user); })->havingRaw("(project_status = 'initiated' OR project_status = 'completed')")->having('is_cancelled','=',DEFAULT_NO_VALUE)->groupBy(['projects.id_project'])->get()->count(),
                            'scheduled' => (string) \Models\Projects::talent_jobs($request->user())->addSelect(\DB::Raw("'{$type}' as type"))->with(['proposal' => function($q){$q->defaultKeys(); } ])->whereHas('proposal',function($q) use($user){$q->where('talent_proposals.status','accepted'); $q->where('user_id',request()->user()->id_user); })->having('project_status','=','pending')->having('is_cancelled','=',DEFAULT_NO_VALUE)->groupBy(['projects.id_project'])->get()->count(),
                            'completed' => (string) \Models\Projects::talent_jobs($request->user())->addSelect(\DB::Raw("'{$type}' as type"))->with(['proposal' => function($q){$q->defaultKeys(); } ])->whereHas('proposal',function($q) use($user){$q->where('talent_proposals.status','accepted'); $q->where('user_id',request()->user()->id_user); })->havingRaw("(project_status = 'closed' OR is_cancelled = '".DEFAULT_YES_VALUE."')")->groupBy(['projects.id_project'])->get()->count(),
                        ]
                    ]
                ])
            );
        }

        /**
         * [This method is used for applied job]
         * @param  Request
         * @return Json Response
         */

        public function apply_job(Request $request){
            
            if(empty($request->project_id)){
                $this->message = 'M0121';
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
            }else{
                $application_data = \Models\Proposals::select()->where(['project_id' => $request->project_id, 'user_id' => $request->user()->id_user, 'type' => 'application'])->get()->count();
                if(empty($application_data)){
                    $insertArr = [
                        'project_id' => $request->project_id,
                        'user_id'    => $request->user()->id_user,
                        'type'       => 'application',
                        'created'    => date('Y-m-d H:i:s'),
                        'updated'    => date('Y-m-d H:i:s')
                    ];
                    $proposaldata = \Models\Proposals::create($insertArr);

                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-apply-job',
                        'reference_type'    => 'projects',
                        'reference_id'      => $request->project_id
                    ]));

                    $this->status = true;
                    $this->message = "M0251";
                }else{
                    $this->message = "M0252";
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
         * [This method is used for initiate chat request]
         * @param  Request
         * @return Json Response
         */

        public function initiate_chat_request(Request $request){
            
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
                $isRequestSent = \Models\Chats::initiate_chat_request($request->sender,$request->receiver,$request->project_id);
                if(!empty($isRequestSent['status'])){
                    $this->status = $isRequestSent['status'];
                    $this->message = $isRequestSent['message'];
                    $this->jsondata = $isRequestSent;
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
         * [This method is used for notification listing]
         * @param  Request
         * @return Json Response
         */

        public function notification_list(Request $request){
            if(empty($this->post['user_id'])){
                $this->post['user_id'] = $request->user()->id_user;
            }

            if(empty($this->post['page'])){
                $notifications = \Models\Notifications::lists($this->post['user_id'],1,DEFAULT_NOTIFICATION_LIMIT);
            }else{
                $notifications = \Models\Notifications::lists($this->post['user_id'],$this->post['page']);
            }
            
            if(!empty($notifications['result'])){
                $this->status   = true;
                $this->jsondata = $notifications;
            }
        
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for notification listing]
         * @param  Request
         * @return Json Response
         */

        public function notification_count(Request $request){
            if(empty($this->post['user_id'])){
                $this->post['user_id'] = $request->user()->id_user;
            }

            $notifications = \Models\Notifications::count($this->post['user_id']);
            
            if(!empty($notifications['total_unread_notifications'])){
                $this->status   = true;
                $this->jsondata = $notifications;
            }
        
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to read marked notification]
         * @param  Request
         * @return Json Response
         */

        public function mark_read_notification(Request $request){
            

            $isMarkedRead = \Models\Notifications::markread($request->notification_id,$request->user()->id_user);
            
            /* RECORDING ACTIVITY LOG */
            event(new \App\Events\Activity([
                'user_id'           => $request->user()->id_user,
                'user_type'         => 'talent',
                'action'            => 'webservice-talent-mark-read-notification',
                'reference_type'    => 'notifications',
                'reference_id'      => $request->notification_id
            ]));

            if(!empty($isMarkedRead)){
                $this->status = $isMarkedRead['status'];
                $this->jsondata = [
                    'total_unread_proposal_notifications'   => $isMarkedRead['total_unread_proposal_notifications'],
                    'total_unread_notifications'            => $isMarkedRead['total_unread_notifications']
                ];
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used to count total chat]
         * @param  Request
         * @return Json Response
         */

        public function total_chat_count(Request $request){
            $this->status = true;
            
            if(empty($request->user_id)){
                return [];
            }

            $count = \Models\Talents::get_chat_count($request->user_id);
            
            if(!empty($count)){
                $this->jsondata = ['count' => $count];
            }else{
                $this->jsondata = ['count' => ''];
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used to change password]
         * @param  Request
         * @return Json Response
         */

        public function change_password(Request $request){
            $old_password = validation('old_password');
            $new_password = validation('old_password');
            unset($old_password[0]);
            unset($new_password[2]);

            $validator = \Validator::make($request->all(), [
                "old_password"              => array_merge(['sometimes'],$old_password),
                "new_password"              => array_merge(['sometimes'],$new_password),
            ],[
                'old_password.required'     => 'M0292',
                'old_password.old_password' => 'M0295',
                'new_password.different'    => 'M0300',
                'new_password.required'     => 'M0293',
                'new_password.regex'        => 'M0296',
                'new_password.max'          => 'M0297',
                'new_password.min'          => 'M0298',
            ]);

            $validator->sometimes(['old_password'], 'required', function($request){
                return ($request->user()->social_account !== DEFAULT_YES_VALUE);
            });

            $validator->sometimes(['new_password'], 'different:old_password', function($request){
                return ($request->user()->social_account !== DEFAULT_YES_VALUE);
            });

            if($validator->fails()){
                $this->message   = $validator->messages()->first();
            }else{
                if(empty($request->user()->email)){
                    $this->message = 'M0568';
                }else{
                    $isUpdated      = \Models\Talents::change(\Auth::user()->id_user,[
                        'social_account'    => 'changed',
                        'password'          => bcrypt($request->new_password),
                        'api_token'         => bcrypt(__random_string()),
                        'updated'           => date('Y-m-d H:i:s')
                    ]);

                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-change-password',
                        'reference_type'    => 'notifications',
                        'reference_id'      => $request->user()->id_user
                    ]));

                    $this->status   = true;
                    $this->message  = 'M0301';
                    $this->redirect = url(sprintf('%s/change-password',TALENT_ROLE_TYPE));
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
         * [This method is used to change password]
         * @param  Request
         * @return Json Response
         */

        public function paypal_configuration(Request $request){
            if($request->user()->paypal_id != $request->paypal_id){
                $validator = \Validator::make($request->all(), [
                    "paypal_id"                         => ['required','email',Rule::unique('users')->ignore('trashed','status')->where(function($query) use($request){$query->where('id_user','!=',$request->user()->id_user);})],
                ],[
                    'paypal_id.required'                => 'M0010',
                    'paypal_id.email'                   => 'M0011',
                    'paypal_id.unique'                  => 'M0528',
                    // 'paypal_id.validate_paypal_email'   => 'M0529',
                ]);

                if($validator->fails()){
                    $this->message   = $validator->messages()->first();
                }else{

                    $this->jsondata = validatePayPalEmail_mobile($request->paypal_id,$request->user_id);
                    $this->status = true;

                    // $isUpdated      = \Models\Talents::change(\Auth::user()->id_user,[
                    //     'paypal_id' => $request->paypal_id,
                    //     'updated'   => date('Y-m-d H:i:s')
                    // ]);
                    
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-paypal-configuration',
                        'reference_type'    => 'notifications',
                        'reference_id'      => $request->user()->id_user
                    ]));

                    $this->status   = true;
                    $this->message  = "M0530";
                }
            }else{
                $this->message  = "M0531";
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used for image portfolio]
         * @param  Request
         * @return Json Response
         */

        public function image_portfolio(Request $request){
            $validator = \Validator::make($request->all(), [
                "file"                      => array_merge(validation('document'),['required']),
            ],[
                'file.validate_file_type'   => 'M0119',
                'file.required'             => 'M0310',
            ]);

            if($validator->fails()){
                $this->message   = $validator->messages()->first();
            }else{
                $folder = 'uploads/portfolio/';
                $uploaded_file = upload_file($request,'file',$folder);
                
                $data = [
                    'user_id'       => $request->user()->id_user,
                    'record_id'     => "",
                    'reference'     => 'users',
                    'filename'      => $uploaded_file['filename'],
                    'extension'     => $uploaded_file['extension'],
                    'folder'        => $folder,
                    'type'          => 'portfolio',
                    'size'          => $uploaded_file['size'],
                    'is_default'    => DEFAULT_NO_VALUE,
                    'created'       => date('Y-m-d H:i:s'),
                    'updated'       => date('Y-m-d H:i:s'),
                ];

                $isInserted = \Models\Talents::create_file($data,true,true);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-image-portfolio',
                    'reference_type'    => 'users',
                    'reference_id'      => $request->user()->id_user
                ]));

                if(!empty($isInserted)){
                    $this->jsondata = $isInserted;
                    $this->status = true;
                    $this->message  = "M0431";
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
         * [This method is used to add portfolio]
         * @param  Request
         * @return Json Response
         */

        public function add_portfolio(Request $request){
            

            $validator = \Validator::make($request->all(), [
                "portfolio"         => validation('portfolio'),
                "description"       => validation('portfolio_description'),
                "portfolio_docs"    => ['required'],
            ],[
                'portfolio.required'        => 'M0312',
                'portfolio.string'          => 'M0304',
                'portfolio.regex'           => 'M0304',
                'portfolio.max'             => 'M0305',
                'portfolio.min'             => 'M0306',
                'description.required'      => 'M0303',
                'description.string'        => 'M0307',
                'description.regex'         => 'M0307',
                'description.max'           => 'M0308',
                'description.min'           => 'M0309',
                'portfolio_docs.required'   => 'M0310',
            ]);

            if($validator->fails()){
                $this->message   = $validator->messages()->first();
            }else{

                $insertArr = [
                    'user_id' => \Auth::user()->id_user,
                    'portfolio' => $request->portfolio,
                    'description' => $request->description,
                    'created' => date('Y-m-d H:i:s'),
                    'updated' => date('Y-m-d H:i:s')
                ];
                
                if(!empty($request->portfolio_id)){
                    $portfolio_id = \Models\Portfolio::save_portfolio($insertArr,$request->portfolio_id);
                    
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-update-portfolio',
                        'reference_type'    => 'talent_portfolio',
                        'reference_id'      => $request->portfolio_id
                    ]));

                }else{
                    $portfolio_id = \Models\Portfolio::save_portfolio($insertArr);
                    
                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-add-portfolio',
                        'reference_type'    => 'users',
                        'reference_id'      => $request->user()->id_user
                    ]));

                }

                $files          = (array) explode(",",$request->portfolio_docs);
                
                if(!empty($portfolio_id)){
                    \Models\File::update_file($files,['record_id' => $portfolio_id]);
                    \Models\Talents::delete_file(
                        sprintf(
                            " record_id = 0 AND type = 'portfolio' AND  user_id = %s", 
                            \Auth::user()->id_user
                        )
                    );

                    if(!empty($request->removed_portfolio)){
                        \Models\Talents::delete_file(sprintf(" id_file IN(%s) AND  user_id = %s",$request->removed_portfolio,\Auth::user()->id_user));                        
                    }
                }


                
                $this->status   = true;
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used to delete portfolio]
         * @param  Request
         * @return Json Response
         */

        public function delete_portfolio(Request $request){
            $isDeleted = \Models\Portfolio::delete_portfolio($this->post['id_portfolio'], $request->user()->id_user);
            
            if($isDeleted){
                $this->status = true;
                $this->message  = "M0313";

                \Models\Talents::delete_file(
                    sprintf(
                        " record_id = %s AND type = 'portfolio' AND  user_id = %s", 
                        $this->post['id_portfolio'],
                        \Auth::user()->id_user
                    )
                );
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-delete-portfolio',
                    'reference_type'    => 'portfolio',
                    'reference_id'      => $this->post['id_portfolio']
                ]));

            }else{
                $this->message  = "M0121";
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        } 

        /**
         * [This method is used for portfolio listing]
         * @param  Request
         * @return Json Response
         */ 

        public function list_portfolio(Request $request){
            
            $page = 1;
            
            if(!empty($request->page)){
                $page = $request->page;
            }

            $portfolioes = \Models\Portfolio::get_portfolio($request->user()->id_user,"","all",[],$page);
            
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
                            'file' => url(sprintf('images/%s',DEFAULT_AVATAR_IMAGE)),
                        ];
                    }
                });

                $this->jsondata = $portfolioes;
            }
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used for portfolio image deletion]
         * @param  Request
         * @return Json Response
         */

        public function delete_portfolio_image(Request $request){
            
            $isDeleted = \Models\Talents::delete_file(sprintf(" id_file = %s AND user_id = %s ",$this->post['id_file'], $request->user()->id_user));

            if($isDeleted){
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-delete-portfolio-image',
                    'reference_type'    => 'files',
                    'reference_id'      => $this->post['id_file']
                ]));

                $this->status = true;
                $this->message  = "M0314";
            }else{
                $this->message  = "M0121";
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for job start / mark completed]
         * @param  Request
         * @return Json Response
         */

        public function project_status(Request $request){
            $status = $request->status;
            $project_id = $request->project_id;
            $project    = \Models\Projects::defaultKeys()
            ->withCount(['dispute'])
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
            }else if($status == 'start' && $project->project_status == 'initiated'){
                $this->message = "M0559";
            }else if($status == 'start' && (strtotime($project->enddate) < strtotime(date('Y-m-d')))){
                $this->message = "M0565";
            }else if($status == 'start' && (strtotime($project->startdate) > strtotime(date('Y-m-d')))){
                $this->message = "M0582";
            }else if($status == 'start' && !empty($project->dispute_count)){
                $this->message = "M0566";
            }else if($status == 'start' && $project->status == 'trashed'){
                $this->message = "M0580";
            }else if($status == 'start' && $project->is_cancelled == DEFAULT_YES_VALUE){
                $this->message = "M0581";
            }else if($status == 'close' && $project->project_status == 'closed'){
                $this->message = "M0560";
            }else if($status == 'close' && !empty($project->dispute_count)){
                $this->message = "M0566";
            }/*else if($status == 'close' && empty($project->projectlog)){
                $this->message = "M0561";
            }*/else{
                switch($status){
                    case 'start': {
                        $this->status = true;
                        $isUpdated          = \Models\Projects::change([
                            'id_project'        => $project_id,
                            'project_status'    => 'pending'
                        ],[
                            'project_status'    => 'initiated',
                            'updated'           => date('Y-m-d H:i:s')
                        ]);

                        if(!empty($isUpdated)){
                            /* RECORDING ACTIVITY LOG */
                            event(new \App\Events\Activity([
                                'user_id'           => auth()->user()->id_user,
                                'user_type'         => 'talent',
                                'action'            => 'talent-start-job',
                                'reference_type'    => 'projects',
                                'reference_id'      => $project_id
                            ]));

                            # $isNotified = \Models\Notifications::notify(
                            #     $project->company_id,
                            #     $project->proposal->talent->id_user,
                            #     'JOB_STARTED_BY_TALENT',
                            #     json_encode([
                            #         "employer_id"   => (string) $project->company_id,
                            #         "talent_id"     => (string) $project->proposal->talent->id_user,
                            #         "project_id"    => (string) $project->id_project
                            #     ])
                            # );

                        }
                        break;
                    }
                    case 'close': {
                        $this->status = true;
                        $isUpdated          = \Models\Projects::change([
                            'id_project'        => $project_id,
                            'project_status'    => 'initiated'
                        ],[
                            'project_status'    => 'closed',
                            'completedate'      => date('Y-m-d H:i:s'),
                            'updated'           => date('Y-m-d H:i:s')
                        ]);

                        if(!empty($isUpdated)){
                            /* RECORDING ACTIVITY LOG */
                            event(new \App\Events\Activity([
                                'user_id'           => auth()->user()->id_user,
                                'user_type'         => 'talent',
                                'action'            => 'talent-completed-job',
                                'reference_type'    => 'projects',
                                'reference_id'      => $project_id
                            ]));

                            $isNotified = \Models\Notifications::notify(
                                $project->company_id,
                                $project->proposal->talent->id_user,
                                'JOB_COMPLETED_BY_TALENT',
                                json_encode([
                                    "employer_id"   => (string) $project->company_id,
                                    "talent_id"     => (string) $project->proposal->talent->id_user,
                                    "project_id"    => (string) $project->id_project
                                ])
                            );

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
         * [This method is used for wallet]
         * @param  Request
         * @return Json Response
         */

        public function wallet(Request $request){
            

            $this->status   = true;
            $this->jsondata = \Models\Payments::summary($request->user()->id_user,'talent');
            $payments       = \Models\Payments::listing($request->user()->id_user,'talent',$request->type, false, $request->page,$request->sort,$request->search);

            if(!empty($payments->count())){
                $payments_list = json_decode(json_encode($payments),true);
                array_walk($payments_list, function(&$item) use($request){
                    if($request->type == 'all' || $request->type == 'disputed'){
                        $item['transaction_subtotal'] = ___calculate_payment($item['employment'],$item['quoted_price']);
                    }

                    $item['transaction_subtotal']   = $item['currency'].___format($item['transaction_subtotal'],true,false);
                    $item['transaction_date']       = ___dd($item['transaction_date'],'jS F Y');
                    if($request->type == 'all'){
                        $item['end_date']           = ___dd(date ('Y-m-d H:i:s',strtotime('+1 day', strtotime($item['enddate']))),'jS F Y'); 
                    }
                    $item['quoted_price']           = ___format($item['quoted_price'],true,true);
                });

                $this->jsondata['payments_list'] = $payments_list;
            }else{
                $this->jsondata['payments_list'] = [];
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }


        /**
         * [This method is used payout request]
         * @param  Request
         * @return Json Response
         */

        public function payout_request(Request $request){
            

            if(empty($request->project_id)){
                $this->message = "M0121";
                $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
            }else{
                $project_id  = $request->project_id;
                $job_detail = \Models\Projects::findById($project_id);
                if(empty($job_detail)){
                    $this->message = "M0121";
                    $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
                }else{
                    $updateData = [
                        'request_payout'    => 'yes',
                        'updated'           => date('Y-m-d H:i:s')
                    ];
                    
                    $isUpdated      = \Models\ProjectLogs::where(['project_id' => $project_id, 'close' => 'pending'])->update($updateData);

                    $isNotified = \Models\Notifications::notify(
                        $job_detail['user_id'],
                        \Auth::user()->id_user,
                        'JOB_REQUEST_PAYOUT_BY_TALENT',
                        json_encode([
                            "user_id" => (string) $job_detail['user_id'],
                            "project_id" => (string) $project_id
                        ])
                    );

                    /* RECORDING ACTIVITY LOG */
                    event(new \App\Events\Activity([
                        'user_id'           => $request->user()->id_user,
                        'user_type'         => 'talent',
                        'action'            => 'webservice-talent-payout-request',
                        'reference_type'    => 'projects',
                        'reference_id'      => $project_id
                    ]));

                    $this->status   = true;
                    $this->message  = 'M0428';
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
                    "category_two"          => validation('review_feedback'),
                    "category_three"        => validation('review_communication'),
                    "category_four"         => validation('review_deadlines'),
                    "category_five"         => validation('review_learning'),
                    "category_six"          => validation('review_support'),
                    "description"           => validation('review_description'),
                ],[
                    "category_one.required"         => 'M0330',
                    "category_one.numeric"          => 'M0337',
                    "category_one.min"              => 'M0330',

                    "category_two.required"         => 'M0606',
                    "category_two.numeric"          => 'M0607',
                    "category_two.min"              => 'M0606',

                    "category_three.required"       => 'M0608',
                    "category_three.numeric"        => 'M0609',
                    "category_three.min"            => 'M0608',

                    "category_four.required"        => 'M0610',
                    "category_four.numeric"         => 'M0611',
                    "category_four.min"             => 'M0610',

                    "category_five.required"        => 'M0612',
                    "category_five.numeric"         => 'M0613',
                    "category_five.min"             => 'M0612',

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
                     if(empty($project_data = json_decode(json_encode(\Models\Projects::where(['id_project' => $request->project_id])->get(),true)))){
                        $this->message = "M0121";
                        $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
                    }else if(empty($proposal_data = json_decode(json_encode(\Models\Proposals::where(['project_id' => $request->project_id, 'user_id' => \Auth::user()->id_user, 'type' => 'proposal', 'status' => 'accepted'])->get(),true ) ) ) ){
                        $this->message = "M0121";
                        $this->error = sprintf(trans(sprintf('general.%s',$this->message)),'project_id');
                    }else if(!empty(json_decode(json_encode(\Models\Reviews::where(['project_id' => $request->project_id, 'sender_id' => \Auth::user()->id_user ])->get(),true)))){
                        $this->message = "M0329";
                        $this->error = trans(sprintf('general.%s',$this->message));                 
                    }else{
                        $total_average  = ($request->category_two+$request->category_three+$request->category_four+$request->category_five+$request->category_six)/5;
                        
                        $reviewArray = [
                            'project_id'            =>  $request->project_id,
                            'sender_id'             =>  \Auth::user()->id_user,
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
                            'user_type'         => 'talent',
                            'action'            => 'webservice-talent-add-review',
                            'reference_type'    => 'users',
                            'reference_id'      => $request->receiver_id
                        ]));

                        $isNotified = \Models\Notifications::notify(
                            $request->receiver_id,
                            $request->user()->id_user,
                            'JOB_REVIEW_REQUEST_BY_TALENT',
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

        public function talent_reviews(Request $request){
            $this->status   = true;
            $page           = (!empty($request->page)) ? $request->page : 1;
            
            if(empty($request->talent_id)){
                $talent_id = auth()->user()->id_user;
            }

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
            ->where('receiver_id',$talent_id)
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
         * [This method is used for raise dispute detail]
         * @param  Request
         * @return Json Response
         */

        
        public function raise_dispute_detail(Request $request){
            $project_id = $request->project_id;

            if(!empty($project_id)){
                $project     = \Models\Projects::defaultKeys()->with([
                    'employer' => function($q){
                        $q->defaultKeys();
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
            $project            = \Models\Projects::where('id_project',$project_id)->defaultKeys()->get()->first();
            
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
                    'receiver_id.required'      => 'M0121',
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
                                $project->company_id,
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
                                $project->company_id,
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
         * [This method is used for setting]
         * @param  Request
         * @return Json Response
         */

        public function settings(Request $request){
            $this->status       = true;
            $this->jsondata     = \Models\Settings::fetch(\Auth::user()->id_user,\Auth::user()->type);
            
            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        } 


        /**
         * [This method is used to save setting]
         * @param  Request
         * @return Json Response
         */  

        public function savesettings(Request $request){
            
            $data = [];
            $validator = \Validator::make($request->all(), [
            ],[
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
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

                $setting    = \Models\Settings::fetch(auth()->user()->id_user,auth()->user()->type);
                $isUpdated          = \Models\Settings::add(\Auth::user()->id_user,$data,$setting);
                
                /* RECORDING ACTIVITY LOG */
                event(new \App\Events\Activity([
                    'user_id'           => $request->user()->id_user,
                    'user_type'         => 'talent',
                    'action'            => 'webservice-talent-save-settings',
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
         * [This method is used to get availability]
         * @param  Request
         * @return Json Response
         */

        public function get_my_availability(Request $request){
            if(!empty($request->user()->id_user)){
                $this->status       = true;
                $talent_availability = \Models\Talents::get_availability($request->user()->id_user,NULL,NULL,'other',$request->date);

                if(!empty($talent_availability)){
                    foreach ($talent_availability as &$value) {
                        $value['start'] = $value['availability_date'].'T'.$value['from_time'];
                        $value['end'] = $value['availability_date'].'T'.$value['to_time'];
                        $value['type'] = $value['repeat'];
                        $value['description'] = sprintf("%s - %s",___t($value['from_time']),___t($value['to_time']));
                        if($value['repeat'] == 'daily'){
                            $value['title'] = sprintf("%s - %s\n%s",___t($value['from_time']),___t($value['to_time']),sprintf("%s %s",'Daily', sprintf("\nuntil %s",___d($value['deadline']))));

                        }
                        elseif($value['repeat'] == 'weekly'){
                            $value['title'] = sprintf(
                                "%s - %s\n%s",
                                ___t($value['from_time']),
                                ___t($value['to_time']),
                                sprintf(
                                    "WEEKLY ON %s %s",
                                    ___replace_last(
                                        ',',
                                        " AND ",
                                        str_replace(
                                            array_values(days()),
                                            array_keys(days()),
                                            implode(", ", $value['availability_day'])
                                        )
                                    ),
                                    sprintf("\nuntil %s",___d($value['deadline']))
                                )
                            );
                        }
                        elseif($value['repeat'] == 'monthly'){
                            $value['title'] = sprintf("%s - %s\n%s",___t($value['from_time']),___t($value['to_time']),sprintf("%s %s",'Monthly', sprintf("\nuntil %s",___d($value['deadline']))));
                        }
                    }
                    #$get_scalar_availability = ___get_scalar_availability($talent_availability);
                    #dd($talent_availability);
                    if(!empty($talent_availability)){
                        $this->jsondata = $talent_availability;
                    }
                }
            }
            else{
                $this->message = 'M0121';
            }

            return response()->json(
                $this->populateresponse([
                    'status'    => $this->status,
                    'data'      => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to add thumb for device]
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
         * [This method is used for currency change]
         * @param  Request
         * @return Json Response
         */

        public function change_currency(Request $request){
            $currency = !empty($this->post['currency']) ? $this->post['currency'] : DEFAULT_CURRENCY;
            $isUpdated = \Models\Talents::change($request->user()->id_user,[
                'currency' => $currency,
                'updated'  => date('Y-m-d H:i:s')
            ]);
            \Models\Talents::update_interest_currency($request->user()->id_user,$currency);
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

        public function verify_invite_talent(Request $request){
            $validator = \Validator::make($request->all(),[
                'code' => validation('invite_code')
            ],[
                'code.required' => 'M0517',
                'code.string'   => 'M0518'
            ]);

            $invite_talent = json_decode(json_encode(\Models\InviteTalent::where([
                'talent_id' => $request->user()->id_user,
                'code'      => $request->code
            ])->first()),true);

            $validator->after(function($validator) use($invite_talent){
                if(empty($invite_talent)){
                    $validator->errors()->add('code','M0518');
                }
            });

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $is_invite_updated      = \Models\InviteTalent::where('id_invite',$invite_talent['id_invite'])->update(['code' => strtoupper(__random_string(4)), 'status'=>'active', 'updated' => date('Y-m-d H:i:s')]);
                $this->status = true;
                $this->message = 'M0519';
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );            
        }

        /**
         * [This method is used for submitting working hours]
         * @param  Request
         * @return Json Response
         */
        
        public function save_working_hours(Request $request){
            $validator = \Validator::make($request->all(), [
                'project_id' => validation('record_id')
            ],[
                'project_id.required' => 'M0121'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $project = \Models\Projects::defaultKeys()->withCount('dispute')->where('id_project',$request->project_id)->get()->first();

                if(empty($project->dispute_count)){    
                    $project                = \Models\Projects::defaultKeys()->withCount('dispute')->where('id_project',$request->project_id)->get()->first();
                    $is_proposal_accepted   = \Models\Proposals::where('project_id',$request->project_id)->where('user_id',auth()->user()->id_user)->where('status','accepted')->defaultKeys()->orderBy('id_proposal','DESC')->get()->first();
                    $total_working_hours    = \Models\ProjectLogs::where('project_id',$request->project_id)->where('workdate',date('Y-m-d'))->totalTiming()->groupBy(['project_id'])->get()->first();
                    $daily_working_hours    = ($project->employment == 'hourly' ? $is_proposal_accepted->working_hours :sprintf("%s:00:00",___configuration(['daily_working_hours'])['daily_working_hours']));

                    if(!empty($total_working_hours)){
                        $total_working_hours = \DB::Select("SELECT IFNULL(SEC_TO_TIME(TIME_TO_SEC('{$total_working_hours->total_working_hours}') + TIME_TO_SEC('{$request->working_hours}')),'00:00:00') as total_working_hours");
                        if(!empty($total_working_hours[0])){
                            $total_working_hours = $total_working_hours[0];
                        }
                    }

                    if(empty($request->working_hours) || $request->working_hours == '00:00'){
                        $this->message = 'M0525';
                        $this->jsondata = (object)[
                            'working_hours' => trans('general.M0525')
                        ];
                    }else if(empty(strtotime($request->working_hours))){
                        $this->message = 'M0523';
                    }else if(empty($project)){
                        $this->message = 'M0121';
                    }else if(empty($is_proposal_accepted)){
                        $this->message = 'M0521';
                    }else if(strtotime($request->working_hours) > strtotime($daily_working_hours)){
                        $this->message = 'M0524';                
                    }else if((!empty($total_working_hours) && empty(strtotime($total_working_hours->total_working_hours))) || (!empty($total_working_hours->total_working_hours) && strtotime($total_working_hours->total_working_hours) > strtotime($daily_working_hours))){
                        $this->message = 'M0524';
                    }else if($project->project_status != 'initiated'){
                        $this->message = 'M0522';
                    }else if($project->status == 'trashed'){
                        $this->message = 'M0580';
                    }else if($project->is_cancelled == DEFAULT_YES_VALUE){
                        $this->message = 'M0581';
                    }else if(!empty($project->dispute_count)){
                        $this->message = 'M0571';
                    }else{
                        $insert_data = [    
                            'project_id'                => $request->project_id,    
                            'talent_id'                 => auth()->user()->id_user, 
                            'employer_id'               => $project->company_id,    
                            'worktime'                  => $request->working_hours, 
                            'workdate'                  => date('Y-m-d'),   
                            'created'                   => date('Y-m-d H:i:s'), 
                            'updated'                   => date('Y-m-d H:i:s'), 
                        ];

                        $isSaved = \Models\ProjectLogs::save_project_log($insert_data);

                        if(!empty($isSaved)){
                            $this->status = true;
                            $total_working_hours = \Models\ProjectLogs::where('project_id',$request->project_id)->totalTiming()->groupBy(['project_id'])->get()->first();
                            
                            $this->jsondata = [
                                'total_working_hours'      => ___hours(substr($total_working_hours->total_working_hours, 0, -3)),
                            ];
                        }else{
                            $this->message = 'M0356';
                        }
                    } 
                }else{
                    $this->message = 'M0571';
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
         * [This method is used for portfolio listing]
         * @param  Request
         * @return Json Response
         */ 

        public function industry_listing(Request $request){

            $language   = \App::getLocale();
            $industries = \Models\Industries::allindustries("array"," parent = '0' AND status = 'active' ",['id_industry',\DB::Raw("IF(({$language} != ''),`{$language}`, `en`) as name"),'parent'],'');

            $country_id = !empty(\Auth::user()->country) ? \Auth::user()->country : 0;

            foreach ($industries as $key => $value) {
                $show_identification_check = \Models\Payout_mgmt::talentCheckIdentificationNo($country_id,$value['id_industry']);
                $show_is_registered = \Models\Payout_mgmt::userCheckIsRegistered($country_id,$value['id_industry']);
                $industries[$key]['check'] = $show_identification_check==true?'Y':'N';  
                $industries[$key]['payout_mgmt_is_registered'] = $show_is_registered=='yes'?'yes':'no';              
            }

            $this->status = true;
            $this->jsondata = $industries;
            $this->message  = 'M0448';
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to add community forum question]
         * @param  Request
         * @return Json Response
         */
        
        public function community_forum_add_question(Request $request){
            $validator = \Validator::make($request->all(), [
                'question_description'            => ['required'],
                'type'                            => ['required']
            ],[
                'question_description.required'   => 'M0625',
                'type.required'                   => 'M0626'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $insertArr = [
                    'id_user'              => \Auth::user()->id_user,
                    'question_description' => $request->question_description,
                    'status'               => 'open',
                    'type'                 => $request->type,
                    'approve_date'         => date('Y-m-d H:i:s'),
                    'created'              => date('Y-m-d H:i:s'),
                    'updated'              => date('Y-m-d H:i:s')
                ];

                \Models\Forum::saveQuestion($insertArr);

                $this->status = true;
                $this->message = 'M0627';
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for community forum]
         * @param  Request
         * @return \Illuminate\Http\Response
         */
        
        public function community_forum(Request $request){
            $request['language']    = \App::getLocale();   
            $prefix                 = DB::getTablePrefix();
            $this->status           = true;
            $html                   = "";
            $page                   = (!empty($request->page))?$request->page:1;
            $limit                  = DEFAULT_PAGING_LIMIT;
            $offset                 = ($page-1)*DEFAULT_PAGING_LIMIT;
            $search                 = !empty($request->search)? $request->search : '';

            $question = \Models\Forum::getQuestionApi();

            // /*get linkedin pofile url*/
            // $filedata['filename'] = $getdata['article']['user_img'];
            // $filedata['folder'] = $getdata['article']['folder'];
            // $getdata['article']['user_img'] = get_file_url($filedata);

            // foreach ($question as $key => $value) {
            //     $question['linkedinurl'] = url("/network/community/forum/question/".___encrypt($value['id_question']));
            // }
            // $this->status = true;
            // $this->jsondata = $question;
            // $this->message  = 'M0448';

            if(!empty(trim($search))){
                $search = trim($search);
                $question->havingRaw("(
                    question_description LIKE '%$search%' 
                )");  
            } 


            $question       = $question->limit($limit)->offset($offset)->get();

            // $question = json_decode(json_encode($question,true));
            foreach ($question as $key => &$value) {
                $value->share_link = url("/network/community/forum/question/".___encrypt($value->id_question));
                $value->created = ___ago($value->approve_date);
                $filedata['filename'] = $value->filename;
                $filedata['folder'] = $value->folder;
                $value->filename = get_file_url($filedata);
            }
            $this->jsondata = $question;

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            ); 

        }

        public function ques_user_follow(Request $request){

            if(!empty($request['user_id'])){
                $isUpdated = \Models\Forum::question_save_user(\Auth::user()->id_user,$request['user_id']);
                if($isUpdated['status']){
                    $this->status   = true;
                    $this->message  = 'Action successful.';
                    $this->jsondata =  ['user_follow' => $isUpdated['send_text'] ];              
                }else{
                    $this->status   = false;
                    $this->message  = 'Something went wrong.';
                    $this->jsondata = [];
                }
            }else{
                $this->status   = false;
                $this->message  = 'Something went wrong.';
                $this->jsondata = [];
            }

            return response()->json(
                $this->populateresponse([
                    'data'    => $this->jsondata,
                    'status'  => $this->status,
                    'message' => $this->message,
                    'user_id' => $request['user_id']
                ])
            );
        }

        /**
         * [This method is used for community forum question]
         * @param  Request
         * @return \Illuminate\Http\Response
         */
        
        public function community_forum_question(Request $request){
            $validator = \Validator::make($request->all(), [
                'id_question'            => ['required']
            ],[
                'id_question.required'   => 'M0628'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $data['id_question'] = $request->id_question;
                $id_question = ___decrypt($request->id_question);

                // $data['related_question'] = \Models\Forum::latestQuestion();
                $data['question'] = \Models\Forum::getQuestionFrontApi($id_question);
                $data['answer'] = \Models\Forum::getAnswerFrontByQuesIdApi($id_question);

                $data['question']['created'] =  ___ago($data['question']['created']);
                $data['question']['share_link'] =  url("/network/community/forum/question/".$id_question);

                foreach ($data['answer'] as $key => &$value) {                    
                    $value->created = ___ago($value->created);
                    $filedata['filename'] = $value->user_img;
                    $filedata['folder'] = $value->folder;
                    $value->filename = get_file_url($filedata);

                    if(!empty($value->has_child_answer)){
                        foreach ($value->has_child_answer as $k => &$v) {  
                            $v->created = ___ago($v->created);  
                            $filedata['filename'] = $v->user_img;
                            $filedata['folder'] = $v->folder;
                            $v->filename = get_file_url($filedata); 
                        }
                    }
                }
                $this->status = true;
                $this->jsondata =  $data; 
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used to add community forum answer]
         * @param  Request
         * @return Json Response
         */
        
        public function community_forum_add_answer(Request $request){
            $id_question = ___decrypt($request->id_question);
            $validator = \Validator::make($request->all(), [
                'id_parent'              => ['required'],
                'type'                   => ['required'],
                'answer_description'     => ['required'],
                'id_question'            => ['required']
            ],[
                'id_parent.required'            => 'M0630',
                'type.required'                 => 'M0626',
                'answer_description.required'   => 'M0629',
                'id_question.required'          => 'M0625'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $insertArr = [
                    'id_user'            => \Auth::user()->id_user,
                    'id_question'        => $id_question,
                    'answer_description' => $request->answer_description,
                    'id_parent'          => $request->id_parent,
                    'status'             => 'approve',
                    'type'               => $request->type,
                    'created'            => date('Y-m-d H:i:s'),
                    'approve_date'       => date('Y-m-d H:i:s'),
                    'updated'            => date('Y-m-d H:i:s')
                ];
                // dd($insertArr);
                \Models\Forum::saveAnswer($insertArr);

                $this->status = true;
                $this->message = 'Your answer has been successfully added.';
            }

            return response()->json(
                $this->populateresponse([
                    'data'      => $this->jsondata,
                    'status'    => $this->status
                ])
            );
        }

        public function community_forum_vote(Request $request){

            $user_id = !empty(\Auth::user()->id_user) ? \Auth::user()->id_user : 0;
            $base_url       = ___image_base_url();

            $validator = \Validator::make($request->all(), [
                'answer_id'              => ['required'],
                'updown_vote'                   => ['required']
            ],[
                'answer_id.required'            => 'M0632',
                'updown_vote.required'                 => 'M0623'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $type = ($request->updown_vote=='upvote') ? 'upvote':'downvote';
                if(!empty($request->answer_id)){

                    $cb_forum_answer_vote = DB::table('forum_answer_vote');
                    $cb_forum_answer_vote->select('vote');
                    $cb_forum_answer_vote->where(['user_id' => \Auth::user()->id_user, 'forum_answer_id' => $request->answer_id]);

                    if(!empty($cb_forum_answer_vote->get()->count())){
                        $cb_forum_answer_vote = $cb_forum_answer_vote->first();
                        if($cb_forum_answer_vote->vote != $type){
                            $isUpdated = \DB::table('forum_answer_vote')
                                        ->where(['user_id' => \Auth::user()->id_user, 
                                                'forum_answer_id' => $request->answer_id
                                                ])
                                        ->update(['vote'=>$type]);
                        }else{
                            $isUpdated = \DB::table('forum_answer_vote')
                                        ->where(['user_id' => \Auth::user()->id_user, 
                                                'forum_answer_id' => $request->answer_id
                                                ])
                                        ->delete();
                        }
                    }else{
                        $insertArr = [
                            'forum_answer_id' => $request->answer_id,
                            'user_id'         => \Auth::user()->id_user,
                            'vote'            => $type,
                            'created'         => date('Y-m-d H:i:s'),
                            'updated'         => date('Y-m-d H:i:s'),
                        ];

                        $isUpdated = \DB::table('forum_answer_vote')->insert($insertArr);                
                    }

                    $this->status   = true;
                    $this->message  = 'M0638';         

                }else{
                    $this->status   = true;
                    $this->message  = 'Something went wrong.';
                }
                /*Send vote count in the end*/
                $this->jsondata = \Models\Forum_answer_vote::count_votes($request->answer_id);
            }

            DB::table('forum_answer');

            $prefix = DB::getTablePrefix();
            $answer = DB::table('forum_answer');
            $answer->select([
                'forum_answer.id_answer',
                'forum_answer.id_question',
                'forum_answer.id_user',
                'forum_answer.answer_description',
                'forum_answer.up_counter',
                'forum_answer.id_parent',
                'forum_answer.approve_date',
                \DB::raw('DATE_FORMAT('.$prefix.'forum_answer.created, "%d-%m-%Y") AS created'),
                \DB::raw('DATE_FORMAT('.$prefix.'forum_answer.updated, "%d-%m-%Y") AS updated'),
                \DB::raw('CONCAT(UCASE(LEFT('.$prefix.'forum_answer.status, 1)),SUBSTRING('.$prefix.'forum_answer.status, 2)) AS status'),
                \DB::Raw("(SELECT count(*) FROM {$prefix}forum_answer_vote WHERE {$prefix}forum_answer_vote.forum_answer_id = {$prefix}forum_answer.id_answer AND {$prefix}forum_answer_vote.user_id ='".$user_id."') AS saved_answer"),
                \DB::Raw("IFNULL((SELECT {$prefix}forum_answer_vote.vote FROM {$prefix}forum_answer_vote WHERE {$prefix}forum_answer_vote.forum_answer_id = {$prefix}forum_answer.id_answer AND {$prefix}forum_answer_vote.user_id ='".$user_id."'),'none') AS saved_answer_vote"),
                \DB::Raw("(SELECT count({$prefix}forum_answer_vote.id) FROM {$prefix}forum_answer_vote WHERE {$prefix}forum_answer_vote.forum_answer_id = {$prefix}forum_answer.id_answer and {$prefix}forum_answer_vote.vote='upvote') AS answer_upvote_count"),
                \DB::Raw("(SELECT count({$prefix}forum_answer_vote.id) FROM {$prefix}forum_answer_vote WHERE {$prefix}forum_answer_vote.forum_answer_id = {$prefix}forum_answer.id_answer and {$prefix}forum_answer_vote.vote='downvote') AS answer_downvote_count"),

                \DB::Raw("(SELECT count(*) FROM {$prefix}network_user_save WHERE {$prefix}network_user_save.save_user_id = {$prefix}forum_answer.id_user and {$prefix}network_user_save.user_id='".$user_id."' and {$prefix}network_user_save.section='forum_question') AS is_following"),

            ]);
            $answer->where('forum_answer.id_answer',$request->answer_id)->groupBy('forum_answer.id_question');
            $answer = $answer->first();

            $this->jsondata['saved_answer_vote'] = $answer->saved_answer_vote;

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for community article]
         * @param  Request
         * @return \Illuminate\Http\Response
         */
        
        public function community_article(Request $request){
            $request['language']    = \App::getLocale();   
            $prefix                 = DB::getTablePrefix();
            $this->status           = true;
            $html                   = "";
            $page                   = (!empty($request->page))?$request->page:1;
            $limit                  = DEFAULT_PAGING_LIMIT;
            $offset                 = ($page-1)*DEFAULT_PAGING_LIMIT;
            $search                 = !empty($request->search)? $request->search : '';
            $base_url               = ___image_base_url();

            $user_id                = !empty(\Auth::user()->id_user) ? \Auth::user()->id_user : 0;

            // $question = \Models\Forum::getQuestionApi();
            $related_article = \Models\Article::leftjoin('users','users.id_user','=','article.id_user')
                        ->leftJoin('files as user_profile',function($leftjoin){
                            $leftjoin->on('user_profile.user_id','=','article.id_user');
                            $leftjoin->on('user_profile.type','=',\DB::Raw('"profile"'));
                        })
                        ->leftJoin('files as article_img',function($leftjoin){
                            $leftjoin->on('article_img.record_id','=','article.article_id');
                            $leftjoin->on('article_img.type','=',\DB::Raw('"article"'));
                        })
                        ->select('article.article_id',
                                'article.id_user',
                                'article.title',
                                'article.description',
                                'users.name',
                                'article.updated as updated_at',
                                \DB::raw('(SELECT COUNT(id_article_answer) FROM '.$prefix.'article_answer WHERE '.$prefix.'article_answer.article_id = '.$prefix.'article.article_id) AS total_reply'),
                                // \DB::Raw("
                                //         IF(
                                //             {$prefix}user_profile.filename IS NOT NULL,
                                //             CONCAT('{$base_url}',{$prefix}user_profile.folder,{$prefix}user_profile.filename),

                                //             CONCAT('{$base_url}','images/','".DEFAULT_AVATAR_IMAGE."')
                                //         ) as user_img
                                //     "),
                                \DB::Raw("
                                    {$prefix}user_profile.filename as user_img
                                "),
                                \DB::Raw("
                                        {$prefix}user_profile.folder as folder
                                    "),
                                \DB::Raw("
                                        IF(
                                            {$prefix}article_img.filename IS NOT NULL,
                                            CONCAT('{$base_url}',{$prefix}article_img.folder,{$prefix}article_img.filename),
                                            'none'
                                        ) as article_img
                                    "),

                                \DB::Raw("(SELECT count(*) FROM {$prefix}network_user_save WHERE {$prefix}network_user_save.save_user_id = {$prefix}article.id_user and {$prefix}network_user_save.user_id='".$user_id."' and {$prefix}network_user_save.section='user') AS is_following"),

                                'article.type',
                                \DB::Raw("IF(({$prefix}article.type = 'firm'),{$prefix}users.company_name, 'N/A') as firm_name")
                        )->orderBy('article.article_id','DESC');
            // \Models\Article::related_article_api();

            // $this->status = true;
            // $this->jsondata = $question;
            // $this->message  = 'M0448';

            if(!empty(trim($search))){
                $search = trim($search);
                $related_article->havingRaw("(
                    title LIKE '%$search%' 
                )");  
            } 

            $related_article       = $related_article->limit($limit)->offset($offset)->get();
// dd($related_article);
            foreach ($related_article as $key => &$value) {
                $value->share_link = url("/network/article/detail/".___encrypt($value->article_id));
                $value->created = ___ago($value->updated_at);
                /*get linkedin pofile url*/
                $filedata['filename'] = $value->user_img;
                $filedata['folder'] = $value->folder;
                $value->user_img = get_file_url($filedata);
            }

            $this->jsondata = $related_article;

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            ); 

        }

        /**
         * [This method is used to article forum answer]
         * @param  Request
         * @return Json Response
         */
        
        public function community_article_add_answer(Request $request){

            $id_answer = ___decrypt($request->id_answer);
            $validator = \Validator::make($request->all(), [
                'id_parent'              => ['required'],
                'type'                   => ['required'],
                'answer_description'     => ['required'],
                'id_answer'              => ['required']
            ],[
                'id_parent.required'            => 'M0630',
                'type.required'                 => 'M0626',
                'answer_description.required'   => 'M0629',
                'id_answer.required'            => 'M0625'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $insertArr = [
                    'article_id'  => $id_answer,
                    'user_id'     => \Auth::user()->id_user,
                    'id_parent'   => $request->id_parent,
                    'answer_desp' => $request->answer_description,
                    'type'        => $request->type,
                    'created'     => date('Y-m-d H:i:s'),
                    'updated'     => date('Y-m-d H:i:s')
                ];
                \Models\Article::saveComment($insertArr);

                $this->status = true;
                $this->message = 'Your comment has been successfully added.';
            }

            return response()->json(
                $this->populateresponse([
                    'data'      => $this->jsondata,
                    'status'    => $this->status
                ])
            );
        }

        /**
         * [This method is used for showing article detail page] 
         * @param  null
         * @return \Illuminate\Http\Response
         */
        
        public function show_article_details(Request $request){

            $validator = \Validator::make($request->all(), [
                'id_article'            => ['required']
            ],[
                'id_article.required'   => 'M0632'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $data['id_article'] = $request->id_article;
                $id_article = ___decrypt($request->id_article);
                /*For article view*/
                // $article_view = \Models\Article::countView(___decrypt($hashid));

                // $data['id_article'] = ___decrypt($hashid);

                // $id_article = ___decrypt($hashid);
                // $data['related_article'] = \Models\Article::related_article();

                $data['article'] = \Models\Article::getArticleDetail($id_article);
                $data['article']['share_link'] =  url("/network/article/detail/".$id_article);
                $data['article']['created'] = ___ago($data['article']['created']);
                // dd($data['article']);

                $data['answer'] = \Models\Article::getAnswerByQuesIdApi($id_article);

                foreach ($data['answer'] as $key => &$value) {                    
                    $value->created = ___ago($value->created);
                    $filedata['filename'] = $value->user_img;
                    $filedata['folder'] = $value->folder;
                    $value->filename = get_file_url($filedata);

                    if(!empty($value->has_child_answer)){
                        foreach ($value->has_child_answer as $k => &$v) {  
                            $v->created = ___ago($v->created);   
                            $filedata['filename'] = $v->user_img;
                            $filedata['folder'] = $v->folder;
                            $v->filename = get_file_url($filedata);
                        }
                    }
                }
                $this->status = true;
                $this->jsondata =  $data;
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
            
        }

        /**
         * [This method is used to article add submit]
         * @param  Request
         * @return Json Response
         */
        
        public function articles_add_submit(Request $request){
            $validator = \Validator::make($request->all(), [
                'title'            => ['required'],
                'description'      => ['required']
                // 'file'             => ['mimetypes:image/png,image/jpeg,image/pjpeg,image/gif']
            ],[
                'title.required'         => 'M0633',
                'description.required'   => 'M0634'
                // 'file.mimetypes'         => 'The attached file is invalid, file should be in jpg,png format.'
            ]);

            // $validate = \Validator::make($request->all(), [
            //     "image"              => validation('image'),
            // ],[
            //     'image.mimetypes'    => 'M0120',
            // ]);

            $validator->after(function($v) use($request){
                if(!empty($request->file)){
                    if(!in_array($request->file->getClientOriginalExtension(), ['jpg','JPEG','png','PNG','jpeg'])){
                        $v->errors()->add('file', "The attached file is invalid, file should be in jpg,png format.");
                    }
                }
            });

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{

                $insertArr = [
                    'id_user'     => \Auth::user()->id_user,
                    'title'       => $request->title,
                    'description' => $request->description,
                    'type'        => $request->type,
                    'created'     => date('Y-m-d H:i:s'),
                    'updated'     => date('Y-m-d H:i:s')
                ];

                $article_data = \Models\Article::saveArticle($insertArr);

                if(!empty($request->file)){
                    $folder = 'uploads/article/';
                    $uploaded_file = upload_file($request,'file',$folder);
                    
                    $data = [
                        'user_id' => \Auth::user()->id_user,
                        'record_id' => $article_data,
                        'reference' => 'article',
                        'filename' => $uploaded_file['filename'],
                        'extension' => $uploaded_file['extension'],
                        'folder' => $folder,
                        'type' => 'article',
                        'size' => $uploaded_file['size'],
                        'is_default' => DEFAULT_NO_VALUE,
                        'created' => date('Y-m-d H:i:s'),
                        'updated' => date('Y-m-d H:i:s'),
                    ];

                    $isInserted = \Models\Talents::create_file($data,true,true);
                }
                


                // $insertArr = [
                //     'id_user'     => \Auth::user()->id_user,
                //     'title'       => $request->title,
                //     'description' => $request->description,
                //     'type'        => $request->type,
                //     'created'     => date('Y-m-d H:i:s'),
                //     'updated'     => date('Y-m-d H:i:s')
                // ];
                // dd($insertArr,'zzzzzzzzzzz');
                // $article_data = \Models\Article::saveArticle($insertArr);

                // $file               = $request->file($request->imagename);
                // $folder             = ltrim($request->SGCreator_folder, '/');
                // $destination        = public_path($folder);
                // $extension          = $file->getClientOriginalExtension();
                // $file_size          = get_file_size($file->getClientSize(),'KB');
                // $filename           = file_name($file->getClientOriginalName(),'jpeg');

                // $crop = new \App\Lib\Crop();
                // $crop->initialize(
                //     array(
                //         'src'           => null,
                //         'data'          => $request->SGCreator_data,
                //         'dst'           => $destination,
                //         'file'          => $_FILES[$request->imagename],
                //         'targetFile'    => $filename
                //     )
                // );

                // $isUploaded = $crop->getMsg();

                // $data = [
                //     'user_id' => empty($request->user_id) ? \Auth::user()->id_user : $request->user_id,
                //     'reference' => 'users',
                //     'filename' => $filename,
                //     'extension' => $extension,
                //     'folder' => $folder,
                //     'type' => 'profile',
                //     'size' => $file_size,
                //     'is_default' => DEFAULT_NO_VALUE,
                //     'created' => date('Y-m-d H:i:s'),
                //     'updated' => date('Y-m-d H:i:s'),
                // ];


                // if($request->type=='article'){
                //     $data['reference'] = 'article';
                //     $data['type'] = 'article';
                // }
                // $isInserted = \Models\Talents::create_file($data,false,true);

                // if(!empty($request->file_id)){
                //     $updateArr = [
                //         'record_id'          => $article_data,
                //         'updated'            => date('Y-m-d H:i:s')
                //     ];
                //     $file_data = \Models\File::update_file([$request->file_id],$updateArr);
                // }

                $this->status = true;
                $this->message = 'Your article has been successfully added.';
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for community home]
         * @param  Request
         * @return \Illuminate\Http\Response
         */
        
        public function community_home(Request $request){
            $request['language']    = \App::getLocale();   
            $prefix                 = DB::getTablePrefix();
            $this->status           = true;
            $html                   = "";
            $page                   = (!empty($request->page))?$request->page:1;
            $limit                  = DEFAULT_PAGING_LIMIT;
            $offset                 = ($page-1)*DEFAULT_PAGING_LIMIT;
            $search                 = !empty($request->search)? $request->search : '';

            $forum   = collect();
            $article = collect();
            $events  = collect();

            $search = !empty($request->search) ? $request->search :'';
            // $option = !empty($request->listing_radio) ? $request->listing_radio :'';

            $group_id = !empty($request->get_group_id) ? $request->get_group_id :'';

            $group_members = [];
            if(!empty($group_id)){ 
                $group_members = \Models\GroupMember::getGroupMembersById($group_id);
                $group_members = array_column($group_members, 'user_id');
            }

            $forum   = \Models\Forum::getAll($search, $limit, $offset, $group_members); 
            $article = \Models\Article::getAll($search, $limit, $offset, $group_members);
            $events  = \Models\Events::getAll($search, $limit, $offset, $group_members);

            $collection = new \Illuminate\Support\Collection();
            $sortedCollection = $collection->merge($forum)->merge($article)->merge($events)->sortBy('created');

            $sortedCollection = json_decode(json_encode($sortedCollection),true);
            $sortedCollection = array_reverse($sortedCollection);

            // $list_data = [
            //     'result'                => $sortedCollection,
            //     'total'                 => count($sortedCollection),
            //     'total_filtered_result' => $forum->count() + $article->count() + $events->count(),
            // ]; 
            
            $data = $sortedCollection;
            // dd($data,'zzzzz');
            $getdata = [];
            if(!empty($data)){
                foreach($data as $keys => $vdata){
                    if($vdata['list_type'] == 'article'){

                        $getdata[$keys]['article'] = \Models\Article::getHomeArticleDetail($vdata['article_id'],'apidata');
                        $getdata[$keys]['article_last_comment'] = \Models\Article::getLastCommentApi($vdata['article_id']);
                        $keys++;
                    }if(!empty(\Auth::user()) && \Auth::user()->type == "talent" && $vdata['list_type'] == 'event'){
                        $getdata[$keys]['event'] = \Models\Events::getHomeEventDetail($vdata['id_events'],'apidata');
                        $getdata[$keys]['userDetails'] = \Models\Events::userDetailsForEvent($vdata['id_events']);
                    }if(!empty(\Auth::user()) && $vdata['list_type'] == 'event'){
                        $getdata[$keys]['event'] = \Models\Events::getHomeEventDetail($vdata['id_events'],'apidata');
                        $getdata[$keys]['userDetails'] = \Models\Events::userDetailsForEvent($vdata['id_events']);
                        $getdata[$keys]['event']['event_date'] = date('dS F Y',strtotime($getdata[$keys]['event']['event_date']));
                        if(!empty($getdata[$keys]['event']['event_time'])){
                            $getdata[$keys]['event']['event_time'] = date('h:i A',strtotime($getdata[$keys]['event']['event_time']));
                        }else{
                            $getdata[$keys]['event']['event_time'] = '';
                        }


                    }if($vdata['list_type'] == 'question'){
                        $getdata[$keys]['question'] = \Models\Forum::getHomeQuestionFront($vdata['id_question'],'apidata');
                    }
                }
            }else{
                $getdata = [];
            }
            // dd((array)$getdata);
            $this->jsondata = $getdata;

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            ); 

        }

        /**
         * [This method is used for community home get group list]
         * @param  Request
         * @return \Illuminate\Http\Response
         */
        
        public function home_get_group(Request $request){

            $groupdata['grouped'] = \Models\GroupMember::getGroupMemberList(\Auth::user()->id_user);

            // $groupdata['user'] = \Models\Talents::get_user((object)['id_user' => \Auth::user()->id_user],true);

            $groupdata['user'] = \Models\Talents::get_user(\Auth::user(),true);
            $groupdata['user']['sign'] = ___cache('currencies')[$groupdata['user']['currency']];

            
            $this->status = true;
            $this->jsondata = $groupdata;
            $this->message  = 'M0649';
            
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        public function addRsvp(Request $request){
            $user_id = \Auth::user()->id_user;

            $base_url       = ___image_base_url();
            $prefix         = DB::getTablePrefix();
            $language       = \App::getLocale();

            $events         = DB::table('events');

            $validator = \Validator::make($request->all(), [
                'event_id'            => ['required']
            ],[
                'event_id.required'         => 'M0635'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                //check if full is reached
                $count = \Models\Events_rsvp::getGoingCount($request->event_id);
                $get_max_attendee = \Models\Events::getEventById($request->event_id); 
                $max_attendee = (int)$get_max_attendee['maximum_attendees'];

                if($max_attendee == $count){
                    $this->status   = false;
                    $this->message  = 'M0636';
                }else{
                    $count = \Models\Events_rsvp::updateOrAddRsvp($request->event_id,\Auth::user()->email);
                    $this->status   = true;
                    $this->message  = 'M0637';
                }
            }

            if(!empty($request['event_id'])){
                $events->select([
                    DB::Raw("IFNULL((SELECT {$prefix}events_rsvp.status FROM {$prefix}events_rsvp WHERE {$prefix}events_rsvp.event_id = {$prefix}events.id_events AND {$prefix}events_rsvp.email ='".\Auth::user()->email."'),'no') AS rsvp_response_status"),       
                    DB::Raw("(SELECT count(*) FROM {$prefix}events_rsvp WHERE {$prefix}events_rsvp.event_id = {$prefix}events.id_events AND {$prefix}events_rsvp.status ='yes') AS total_attending")
                ]);

                $events->leftJoin('members', function($join) use($user_id) {
                    $join->on('members.user_id','=',\DB::Raw("$user_id"));
                    $join->on('members.member_id','=','events.posted_by');
                });

                $events->leftjoin('users','users.id_user','=','members.member_id');

                $events->whereRaw("({$prefix}events.visibility = 'public' OR ({$prefix}events.visibility = 'circle' and {$prefix}members.id is NOT NULL) OR {$prefix}events.posted_by = '$user_id') ");

                $events->whereNotIn('events.status',['deleted','draft']);
                $events->where('events.id_events',$request->event_id);
                $events->orderBy('events.id_events','DESC');
                $events->groupBy('events.id_events');
                $events = $events->first();

                $this->jsondata->rsvp_response_status = $events->rsvp_response_status;
                $this->jsondata->total_attending = $events->total_attending;

            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );

        }

        public function follow_this_ques(Request $request){
            $validator = \Validator::make($request->all(), [
                'id_question'            => ['required']
            ],[
                'id_question.required'         => 'M0628'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                if(!empty($request['id_question'])){
                    $data['post_id'] = $request['id_question'];
                    $data['section'] = 'question';

                    $isUpdated = \Models\Article::follow_this_article(\Auth::user()->id_user,$data);

                    if($isUpdated['status']){
                        $this->status   = true;
                        $this->message  = $isUpdated['send_text'];
                        $this->jsondata->is_ques_following =  $isUpdated['send_text'];              
                    }else{
                        $this->status   = false;
                        $this->message  = 'Something went wrong.';
                        $this->jsondata = [];
                    }
                }else{
                    $this->status   = false;
                    $this->message  = 'Something went wrong.';
                    $this->jsondata = [];
                }
            }

            return response()->json(
                $this->populateresponse([
                    'data'    => $this->jsondata,
                    'status'  => $this->status,
                ])
            );
        }

        public function follow_this_article(Request $request){

            $validator = \Validator::make($request->all(), [
                'article_id'            => ['required']
            ],[
                'article_id.required'         => 'M0651'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                if(!empty($request['article_id'])){
                    $data['post_id'] = $request['article_id'];
                    $data['section'] = 'article';

                    $isUpdated = \Models\Article::follow_this_article(\Auth::user()->id_user,$data);
                    if($isUpdated['status']){
                        $this->status   = true;
                        $this->message  = $isUpdated['send_text'];
                        $this->jsondata->is_article_following =  $isUpdated['send_text'];              
                    }else{
                        $this->status   = false;
                        $this->message  = 'Something went wrong.';
                        $this->jsondata = [];
                    }
                }else{
                    $this->status   = false;
                    $this->message  = 'Something went wrong.';
                    $this->jsondata = [];
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        public function mark_event_favorite(Request $request){

            $user_id = \Auth::user()->id_user;

            $base_url       = ___image_base_url();
            $prefix         = DB::getTablePrefix();
            $language       = \App::getLocale();

            $events         = DB::table('events');

            $validator = \Validator::make($request->all(), [
                'event_id'            => ['required']
            ],[
                'event_id.required'         => 'M0652'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                if(!empty($request['event_id'])){
                    $isUpdated = \Models\Events::save_fav_event(\Auth::user()->id_user,$request['event_id']);

                    if($isUpdated['status']){
                        $this->status   = true;
                        $this->message  = ($isUpdated['action']=='saved_event') ? 'Event Bookmarked' : 'Event UnBookmarked';
                        $this->jsondata =  [];              
                    }else{
                        $this->status   = false;
                        $this->message  = 'Something went wrong.';
                        $this->jsondata = [];
                    }
                }else{
                    $this->status   = false;
                    $this->message  = 'Something went wrong.';
                    $this->jsondata = [];
                }
            }

            if(!empty($request['event_id'])){
                $events->select([
                    DB::Raw("(SELECT count(*) FROM {$prefix}saved_event WHERE {$prefix}saved_event.event_id = {$prefix}events.id_events AND {$prefix}saved_event.user_id ='".\Auth::user()->id_user."') AS saved_bookmark"),
                ]);

                $events->leftJoin('members', function($join) use($user_id) {
                    $join->on('members.user_id','=',\DB::Raw("$user_id"));
                    $join->on('members.member_id','=','events.posted_by');
                });

                $events->leftjoin('users','users.id_user','=','members.member_id');

                $events->whereRaw("({$prefix}events.visibility = 'public' OR ({$prefix}events.visibility = 'circle' and {$prefix}members.id is NOT NULL) OR {$prefix}events.posted_by = '$user_id') ");

                $events->whereNotIn('events.status',['deleted','draft']);
                $events->where('events.id_events',$request->event_id);
                $events->orderBy('events.id_events','DESC');
                $events->groupBy('events.id_events');
                $events = $events->first();

                $this->jsondata['saved_bookmark'] = $events->saved_bookmark;

            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );

        }

        public function get_invite_emails(Request $request){

            $where = 'status = "active"';
            if(!empty($request->search)){
                $where .= " AND first_name LIKE '%{$request->search}%'";
            }
            $emails = \Models\Talents::get_talent_email('array',$where,['email as id', 'first_name as text']);

            $this->status   = true;
            $this->jsondata = $emails;

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data'    => $emails,
                    /*'pagination' => [
                        "more" => true
                    ]*/
                ])
            );
        }

        public function post_invite_member(Request $request){

            if($request->invite_from == 'from_circle'){
                $validator = \Validator::make($request->all(), [
                    'invite_from'                  => 'required',
                    'event_id'                     => 'required',
                    'talent_emails'                => 'required',
                ],[
                    'invite_from.required'         => 'M0656',
                    'event_id.required'            => 'M0652',
                    'talent_emails.required'       => 'M0653',
                ]);
            }else{
                $validator = \Validator::make($request->all(), [
                    'invite_from'                  => 'required',
                    'event_id'                     => 'required',
                    'outside_emails'                => 'required',
                ],[
                    'invite_from.required'         => 'M0656',
                    'event_id.required'            => 'M0652',
                    'outside_emails.required'       => 'M0654',
                ]);
            }

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                // dd($request->all(),'zzz',$request->input('talent_emails'));
                if($request->invite_from == 'from_circle'){
                    $get_rsvp_emails = \Models\Events_rsvp::getEmailsById($request->event_id);
                    foreach ($request->talent_emails as $key => $value){

                        //Check if this member is already invited
                        if(!in_array($value, $get_rsvp_emails)){
                            $data1 = [
                                'event_id'   => $request->event_id,
                                'email'      => $value['email'],
                                'status'     => 'no',
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];

                            $insertId = \Models\Events_rsvp::add_rsvp($data1);
                            $code = ___encrypt($insertId);

                            $talent_name = \Models\Talents::get_talent_name($value['email']);
                            if(!empty($talent_name)){
                                $show_talent_name = $talent_name['first_name'];
                            }

                            $emailData              = ___email_settings();
                            $emailData['email']     = $value['email'];
                            $emailData['name']      = $show_talent_name;
                            $emailData['link']      = url(sprintf("accept/event?token=%s",$code));

                            ___mail_sender($value['email'],'',"accept_event",$emailData);

                        }else{
                            /*As the email is already invite, get its record and send email only*/
                            $getRecordId = \Models\Events_rsvp::getRecordByEmail($value['email'],$request->event_id);

                            if(!empty($getRecordId)){
                                $code1 = ___encrypt($getRecordId->id);

                                $show_talent_name1 = '';
                                $talent_name1 = \Models\Talents::get_talent_name($value['email']);
                                if(!empty($talent_name1)){
                                    $show_talent_name1 = $talent_name1['first_name'];
                                }

                                $emailData              = ___email_settings();
                                $emailData['email']     = $value['email'];
                                $emailData['name']      = $show_talent_name1;
                                $emailData['link']      = url(sprintf("accept/event?token=%s",$code1));

                                ___mail_sender($value['email'],'',"accept_event",$emailData);
                            }

                        }
                    }//end foreach

                }else{

                    $get_rsvp_emails = \Models\Events_rsvp::getEmailsById($request->event_id);

                    foreach ($request->outside_emails as $key1 => $value1){

                        //Check if this email is already invited
                        if(!in_array($value1, $get_rsvp_emails)){
                            $data1 = [
                                'event_id'   => $request->event_id,
                                'email'      => $value1['email'],
                                'status'     => 'no',
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];

                            $insertId = \Models\Events_rsvp::add_rsvp($data1);
                            $code = ___encrypt($insertId);

                            // $show_talent_name = explode('@', $value1)[0];
                            $show_talent_name = $value1['name'];

                            $emailData              = ___email_settings();
                            $emailData['email']     = $value1['email'];
                            $emailData['name']      = $show_talent_name;
                            $emailData['link']      = url(sprintf("accept/event?token=%s",$code));

                            // dd($emailData);

                            ___mail_sender($value1['email'],'',"accept_event",$emailData);
                        }
                    }//end foreach

                }//end else

                $this->status   = true;
                $this->message  = 'M0655';

                /*if(!empty($request->ret_page) && $request->ret_page == 'home'){
                    $this->redirect = url("/network/home");
                }else{
                    $this->redirect = url(sprintf("%s/network/events",TALENT_ROLE_TYPE));
                }*/


            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );

        }

        /**
         * [This method is used for events listing]
         * @param  Request
         * @return \Illuminate\Http\Response
         */
        
        public function events(Request $request){
            $request['language']    = \App::getLocale();   
            $prefix                 = DB::getTablePrefix();
            $this->status           = true;
            $html                   = "";
            $page                   = (!empty($request->page))?$request->page:1;
            $limit                  = DEFAULT_PAGING_LIMIT;
            $offset                 = ($page-1)*DEFAULT_PAGING_LIMIT;
            $search                 = !empty($request->search)? $request->search : '';
            // $data['user']           = \Models\Talents::get_user(\Auth::user());   
            // $base_url               = ___image_base_url();

            // $user_id                = !empty(\Auth::user()->id_user) ? \Auth::user()->id_user : 0;

            // // $question = \Models\Forum::getQuestionApi();
            // $related_article = \Models\Article::leftjoin('users','users.id_user','=','article.id_user')
            //             ->leftJoin('files as user_profile',function($leftjoin){
            //                 $leftjoin->on('user_profile.user_id','=','article.id_user');
            //                 $leftjoin->on('user_profile.type','=',\DB::Raw('"profile"'));
            //             })
            //             ->leftJoin('files as article_img',function($leftjoin){
            //                 $leftjoin->on('article_img.record_id','=','article.article_id');
            //                 $leftjoin->on('article_img.type','=',\DB::Raw('"article"'));
            //             })
            //             ->select('article.article_id',
            //                     'article.id_user',
            //                     'article.title',
            //                     'article.description',
            //                     'users.name',
            //                     'article.updated as updated_at',
            //                     \DB::raw('(SELECT COUNT(id_article_answer) FROM '.$prefix.'article_answer WHERE '.$prefix.'article_answer.article_id = '.$prefix.'article.article_id) AS total_reply'),
            //                     \DB::Raw("
            //                             IF(
            //                                 {$prefix}user_profile.filename IS NOT NULL,
            //                                 CONCAT('{$base_url}',{$prefix}user_profile.folder,{$prefix}user_profile.filename),

            //                                 CONCAT('{$base_url}','images/','".DEFAULT_AVATAR_IMAGE."')
            //                             ) as user_img
            //                         "),
            //                     \DB::Raw("
            //                             IF(
            //                                 {$prefix}article_img.filename IS NOT NULL,
            //                                 CONCAT('{$base_url}',{$prefix}article_img.folder,{$prefix}article_img.filename),
            //                                 'none'
            //                             ) as article_img
            //                         "),

            //                     \DB::Raw("(SELECT count(*) FROM {$prefix}network_user_save WHERE {$prefix}network_user_save.save_user_id = {$prefix}article.id_user and {$prefix}network_user_save.user_id='".$user_id."' and {$prefix}network_user_save.section='article') AS is_following"),

            //                     'article.type',
            //                     \DB::Raw("IF(({$prefix}article.type = 'firm'),{$prefix}users.company_name, 'N/A') as firm_name")
            //             )->orderBy('article.article_id','DESC');
            // // \Models\Article::related_article_api();

            // // $this->status = true;
            // // $this->jsondata = $question;
            // // $this->message  = 'M0448';

            // if(!empty(trim($search))){
            //     $search = trim($search);
            //     $related_article->havingRaw("(
            //         title LIKE '%$search%' 
            //     )");  
            // } 

            // $related_article       = $related_article->limit($limit)->offset($offset)->get();

            // foreach ($related_article as $key => &$value) {
            //     $value->share_link = url("/network/article/detail/".___encrypt($value->article_id));
            //     $value->created = ___ago($value->updated_at);
            // }

            $inCheck =  array();
            $inCheck = $request->input('check');
            $inEvent_date = $request->input('event_date');
            $events = \Models\Events::getEventListApi($inCheck,$inEvent_date);

            if(!empty(trim($search))){
                $search = trim($search);
                $events->where('events.event_title','LIKE', '%'.$search.'%');
            }

            $events = $events->limit($limit)->offset($offset)->get();

            $tempArr = [];
            
            foreach ($events as $key => $value) {
                $tempArr[$key]['userDetails'] =  \Models\Events::userDetailsForEvent($value->id_events);
                $tempArr[$key]['event'] = $value;
                $tempArr[$key]['event']->share_link = url("/mynetworks/eventsdetail/".$value->id_events);
                $tempArr[$key]['event']->created = ___ago($value->created);
                $tempArr[$key]['event']->event_date = date('dS F Y',strtotime($value->event_date));
                $tempArr[$key]['event']->event_time = date('h:i A',strtotime($value->event_time));
            }

            $this->jsondata = (object)$tempArr;

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
        
        public function viewTalentConnect(){
            
            $data['user']           = \Models\Talents::get_user(\Auth::user());
            $data['invited_user']   = \Models\connectedTalent::where('is_email_sent','0')->where('is_connected','!=','1')->where('send_by',\Auth::user()->id_user)->get();
        
            $this->status = true;
            $this->jsondata = $data['invited_user'];

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );

            
        }

        public function viewTalentAddedMember(Request $request){
            
            $validator = \Validator::make($request->all(), [
                'user_id'            => ['required']
            ],[
                'user_id.required'         => 'M0646'
            ]);
            $user_id = $request->user_id;

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $data['isOwner']        = \Models\companyConnectedTalent::with(['user'])->where('id_user',$user_id)->where('user_type','owner')->get()->first();
                $data['isOwner']        = json_decode(json_encode($data['isOwner']));

                if(!empty($data['isOwner'])){
                    $data['connected_user'] = \Models\companyConnectedTalent::with(['user'])
                                                ->where('id_talent_company',$data['isOwner']->id_talent_company)
                                                ->where('user_type','user')
                                                ->get();
                    foreach ($data['connected_user'] as $key => &$value) {
                        // dd($value->user->id_user);
                        $value->user->industry                           = \Models\Talents::industry($value->id_user);
                        $value->user->profile_url = get_file_url(\Models\companyConnectedTalent::get_file(sprintf(" type = 'profile' AND user_id = %s",$value->user->id_user),'single',['filename','folder']));
                    }
                    $data['connected_user'] = json_decode(json_encode($data['connected_user']));
                }else{
                    
                    $data['connected_user'] = [];
                }
                
                $this->status = true;
                $this->jsondata = $data['connected_user'];
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

        /**
         * [This method is used for storing invited talents data ]
         * @param  Request
         * @return Json Response
         */
        public function storeTalentConnect(Request $request){
            $validator = \Validator::make($request->all(), [
                "name"              => 'required',
                "email"             => 'required|email',
            ],[
                'name.required'     => 'M0667',
                'email.required'    => 'M0665',
                'email.email'       => 'M0666',
            ]);

            $validator->after(function ($validator) use($request) {
                $user_id        = \Models\Users::select('id_user')->where('email',$request->email)->first();
                $ownerDetails   = \Models\companyConnectedTalent::where('id_user',\Auth::User()->id_user)->first();
                $isAlreadyExist = \Models\companyConnectedTalent::where('id_user',$user_id['id_user'])->where('id_talent_company',$ownerDetails->id_talent_company)->first();

                if(count($isAlreadyExist) > 0){
                    $validator->errors()->add('email', trans('general.M0639'));
                }


                $isInvited = \Models\connectedTalent::where('send_to_email',$request->email)->where('is_email_sent','0')->first();
                if(count($isInvited) > 0){
                    $validator->errors()->add('email', trans('general.M0640'));
                }

            });

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                // dd('11');
                $isAlreadyExist = \Models\connectedTalent::where('send_to_email',$request->email)->where('send_by',\Auth::user()->id_user)->first();
                $data = [
                    'send_by'           => \Auth::user()->id_user,
                    'send_to_name'      => $request->name,
                    'send_to_email'     => $request->email,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'updated_at'        => date('Y-m-d H:i:s'),
                ];
                if(count($isAlreadyExist) == 0){
                    $isInserted = \Models\connectedTalent::create($data);
                }else{
                    $isInserted = \Models\connectedTalent::where('send_to_email',$request->email)->where('send_by',\Auth::user()->id_user)->update(['is_email_sent'=>'0']);
                    $isInserted = $isAlreadyExist;
                }

                if($isInserted){
                    $this->status       = true;
                    $this->jsondata     = $isInserted;
                    $this->message      = "M0668";
                }else{
                    $this->status       = false;
                    $this->message      = "M0669";
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );

        }

        public function removeTalentConnect(Request $request){
            
            $validator = \Validator::make($request->all(), [
                "talent_id"              => 'required',
            ],[
                'talent_id.required'     => 'M0670'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $talent_id = $request->talent_id;
                if($talent_id){
                    $isDeleted = \Models\connectedTalent::where('id_connect',$talent_id)->delete();
                }
                if($isDeleted){
                    $this->status       = true;
                    $this->message      = "M0671";
                }else{
                    $this->status       = false;
                    $this->message      = "M0672";
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );

        }


        public function sendInviteToTalent(Request $request){
            $validator = \Validator::make($request->all(), [
                "send_to"              => 'required',
            ],[
                'send_to.required'     => 'M0641'
            ]);

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                
                $talent_ids = ($request->send_to);      

                $talent_Data = \Models\connectedTalent::whereIn('id_connect',$talent_ids)->get();
                $talent_Data = json_decode(json_encode($talent_Data),true);

                foreach ($talent_Data as $key => $value) {
                    $inviteCode = _get_couponCode('6');
                    $emailData                      = ___email_settings();
                    $emailData['email']             = $value['send_to_email'];
                    $emailData['name']              = $value['send_to_name'];
                    $emailData['link']              = url('/');
                    $emailData['invited_by']        = \Auth::user()->name;
                    $emailData['invited_code']      = $inviteCode;


                    $isUpdated = \Models\connectedTalent::where('id_connect',$value['id_connect'])->where('send_by',\Auth::User()->id_user)->update(['is_email_sent'=>'1','invite_code'=>$inviteCode]);
                    ___mail_sender($value['send_to_email'],$value['send_to_name'],"invite_talent",$emailData);
                }
                $this->status = true;
                $this->message = 'Inivitation sent successfully';
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );

        }

        public function unlinkConnectedTalent(Request $request)
        {
            $validator = \Validator::make($request->all(), [
                "user_id"              => 'required',
            ],[
                'user_id.required'     => 'M0670'
            ]);

            $validator->after(function ($validator) use($request) {

                $projectDetail = \Models\Proposals::join('projects','projects.id_project','talent_proposals.project_id')
                                                    ->where('talent_proposals.user_id',$request->user_id)
                                                    ->where('projects.project_status','initiated')
                                                    ->count();
                
                if($projectDetail > 0){
                    $validator->errors()->add('user_id', trans('general.M0657'));
                }
            });

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $user_id = $request->user_id;
                $response = \Models\companyConnectedTalent::where('id_user',$user_id)->delete();
                if($response){
                    $this->status   = true;
                    $this->message  = 'M0673';
                }else{
                    $this->status   = false;
                    $this->message  = 'M0672';
                }
                
            }
            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );

        }

        public function connectTalentByInviteCode(Request $request){

            $validator = \Validator::make($request->all(), [
                "invite_code" => 'required',
            ],[
                'invite_code.required' => 'M0674'
            ]);

            $validator->after(function ($validator) use($request) {
                $projectDetail = \Models\Proposals::join('projects','projects.id_project','talent_proposals.project_id')
                                                    ->where('talent_proposals.user_id',\Auth::User()->id_user)
                                                    ->where('projects.project_status','initiated')
                                                    ->count();
                
                if($projectDetail > 0){
                    $validator->errors()->add('invite_code', trans('general.M0658'));
                }

                $isInviteCode = \Models\connectedTalent::where('invite_code',$request->invite_code)->where('send_to_email',\Auth::User()->email)->first();
                if($request->invite_code != ''){
                    if(($isInviteCode)== null){
                        $validator->errors()->add('invite_code', trans('general.M0645'));
                    }
                }
                $already_connected = \Models\companyConnectedTalent::where('id_user',\Auth::User()->id_user)->first();
                /*if(count($already_connected) > 0){
                    $validator->errors()->add('invite_code', trans('general.M0650'));
                }*/
            });

            if($validator->fails()){
                $this->message = $validator->messages()->first();
            }else{
                $isInviteCode   = \Models\connectedTalent::where('invite_code',$request->invite_code)->first();

                $companyOwnerId = $isInviteCode->send_by;
                $companyDetail  = \Models\companyConnectedTalent::where('id_user',$companyOwnerId)->where('user_type','owner')->first();

                $talentData     = [
                    'id_talent_company' => $companyDetail->id_talent_company,
                    'id_user'           => \Auth::user()->id_user,
                    'user_type'         => 'user',
                    'created'           => date('Y-m-d H:i:s'),
                    'updated'           => date('Y-m-d H:i:s'),
                ];

                \Models\companyConnectedTalent::where('id_user',\Auth::user()->id_user)->delete();

                $isInserted = \Models\companyConnectedTalent::insert($talentData);
                
                \Models\connectedTalent::where('invite_code',$request->invite_code)->update(['invite_code' => null]);

                if($isInserted){
                    $this->status = true;
                    $this->message = 'M0675';
                }
            }

            return response()->json(
                $this->populateresponse([
                    'status' => $this->status,
                    'data' => $this->jsondata
                ])
            );
        }

    }
