<?php
	
	function validation($key){
		$validation = [

			/*
			|--------------------------------------------------------------------------
			| Field Validation Common File
			|--------------------------------------------------------------------------
			|
			| The following language lines are used during talent actions for various
			| messages that we need to display to the user. You are free to modify
			| these language lines according to your application's requirements.
			|
			*/

			'name'						=> ['required','string','regex:/(^[A-Za-z0-9 \\.\\_]+$)+/','max:'.FULL_NAME_MAX_LENGTH],
			'email'						=> ['required','email'],
			'first_name'				=> ['required','string','regex:/(^[A-Za-z0-9 \\.\\_]+$)+/','max:'.NAME_MAX_LENGTH],
			'last_name'					=> ['string','regex:/(^[A-Za-z0-9 \\.\\_]+$)+/','max:'.NAME_MAX_LENGTH],
			'password'					=> ['required','string','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/','min:'.PASSWORD_MIN_LENGTH,'max:'.PASSWORD_MAX_LENGTH],
			'confirm_password'			=> ['required','same:password'],
			'recaptcha'					=> ['required'],/*,'recaptcha'*/
			'agree'						=> ['required'],
			'agree_pricing'				=> ['required_range:interests,fulltime'],
			'company_name'				=> ['required','string','regex:/(^[A-Za-z0-9 .()\\’\\\']+$)+/','max:'.COMPANY_NAME_MAX_LENGTH],
			'phone_number'				=> ['required','string','regex:/(^[0-9]+$)+/','max:'.PHONE_NUMBER_MAX_LENGTH,'min:'.PHONE_NUMBER_MIN_LENGTH],
			'mobile'					=> ['string','regex:/(^[0-9]+$)+/','max:'.PHONE_NUMBER_MAX_LENGTH,'min:'.PHONE_NUMBER_MIN_LENGTH],
			'message'					=> ['required','string','max:'.DESCRIPTION_MAX_LENGTH,'min:'.DESCRIPTION_MIN_LENGTH],
			
			/*TALENT STEP ONE*/
			'interests'       			=> ['array'],
			'expected_salary'       	=> ['numeric','required_range:interests,fulltime','max:'.EXPECTED_SALARY_MAX_LENGTH,'min:'.EXPECTED_SALARY_MIN_LENGTH],
			'other_expectations'    	=> ['string','regex:/^([0-9]+ )?[0-9a-zA-Z \\r\\n\\/\\,\\’\\$\\–\\,\'\\#\\(\\)\\.\\?\\!\\-]+$/','max:'.DESCRIPTION_MAX_LENGTH,'min:'.DESCRIPTION_MIN_LENGTH],
			'birthday'             		=> ['string','regex:/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/','validate_date'],
			'gender'             		=> ['string'],
			'address'             		=> ['string','regex:/^([0-9]+ )?[0-9a-zA-Z \\r\\n\\/\\,\\#\\(\\)\\.\\-]+$/','max:'.DESCRIPTION_MAX_LENGTH],
			'country'             		=> ['integer'],
			'country_code'          	=> ['string'],
			'state'             		=> ['integer'],
			'city'             			=> ['integer'],
			'postal_code'          		=> ['string','regex:/(^[0-9]+$)+/','max:'.POSTAL_CODE_MAX_LENGTH,'min:'.POSTAL_CODE_MIN_LENGTH],
			
			/*TALENT STEP TWO*/
			'industry'          		=> ['array'],
			'subindustry'          		=> ['array'],
			'skills'          			=> ['array'],
			'expertise'          		=> ['string'],
			'experience'          		=> ['numeric','max:'.EXPERIENCE_MAX_LENGTH,'min:'.EXPERIENCE_MIN_LENGTH,'regex:/^(?=.+)(?:[1-9]\d*|0)?(?:\.\d+)?$/'],
			'workrate'          		=> ['array'],
			'workrate.0'          		=> ['required_with:interests.0','numeric','min:0','max:'.WORKRATE_MAX],
			'workrate.1'          		=> ['required_with:interests.1','numeric','min:0','max:'.WORKRATE_MAX],
			'workrate.2'          		=> ['required_with:interests.2','numeric','min:0','max:'.WORKRATE_MAX],
			'workrate_max'          	=> ['numeric','min:0'],
			'workrate_unit'          	=> ['string','required_with:workrate'],
			'workrate_information'  	=> ['string','max:'.DESCRIPTION_MAX_LENGTH,'min:'.DESCRIPTION_MIN_LENGTH],
			'certificates'  			=> ['array'],
			
			/*TALENT STEP THREE*/
			'cover_letter_description'  => ['string',"regex:/^([0-9]+ )?[0-9a-zA-Z \\!\\r\\n\\;\\:\\'\\’\\–\\+\\@\\/\\,\\#\\(\\)\\.\\-\\?\\!]+$/",'max:'.SHORT_DESCRIPTION_MAX_LENGTH,'min:'.SHORT_DESCRIPTION_MIN_LENGTH],
			
			/*EDUCATION*/
			'college'  					=> ['required','string',"regex:/^([0-9]+ )?[0-9a-zA-Z \\r\\n\\/\\,\\$\\–\\,\'\\#\\(\\)\\.\\-\\?\\!]+$/",'max:'.DESCRIPTION_MAX_LENGTH],
			'degree'  					=> ['required','integer'],
			'passing_year'  			=> ['required','integer'],
			'area_of_study'  			=> ['required','string',"regex:/^([0-9]+ )?[0-9a-zA-Z \\r\\n\\/\\,\\$\\–\\,\'\\#\\(\\)\\.\\-\\?\\!]+$/",'max:'.DESCRIPTION_MAX_LENGTH],
			'degree_status'  			=> ['required','string'],
			
			/*WORK-EXPERIENCE*/
			'jobtitle'  				=> ['required','string','min:'.JOB_TITLE_MIN_LENGTH,'max:'.JOB_TITLE_MAX_LENGTH],

			'joining_month'  			=> ['required','string','max:12'],
			'joining_year'  			=> ['required','integer','max:'.MAXIMUM_JOINING_YEAR],
			'is_currently_working'  	=> ['required','string'],
			'job_type'  				=> ['required','string'],
			'relieving_month'  			=> ['required','string','max:12'],
			'relieving_year'  			=> ['required','integer','max:'.MAXIMUM_RELIEVING_YEAR],
			
			/*ADD DOCUMENT*/
			'document'  				=> ['validate_file_type'],
			//'document'  				=> ['mimetypes:application/pdf,application/x-excel,application/excel,application/vnd.ms-excel,application/x-excel,application/x-msexcel,image/png,image/jpeg,image/pjpeg,image/gif,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain'],
			'image'  					=> ['mimetypes:image/png,image/jpeg,image/pjpeg,image/gif'],
			
			/*EMPLOYER STEP ONE*/
			'website'  					=> ['string',"regex:/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:\/~\+#]*[\w\-\@?^=%&amp;\/~\+#])?/",'max:'.DESCRIPTION_MAX_LENGTH],
			
			/*EMPLOYER STEP TWO*/
			'company_work_field'  		=> ['integer'],
			'company_biography'  		=> ['string','regex:/^([0-9]+ )?[0-9a-zA-Z \\r\\n\\/\\,\\$\\–\\,\'\\#\\(\\)\\.\\-\\?\\!]+$/','max:'.DESCRIPTION_MAX_LENGTH,'min:'.DESCRIPTION_MIN_LENGTH],
			'contact_person_name'  		=> ['string','regex:/(^[A-Za-z0-9 \\.\\_]+$)+/','max:'.FULL_NAME_MAX_LENGTH],
			'company_industry'  	    => ['required'],

			/*POST JOB*/
			'employment'  				=> ['required','string'],
			'description'  				=> ['required','string','max:'.DESCRIPTION_MAX_LENGTH,'min:'.DESCRIPTION_MIN_LENGTH],//,"regex:/^([0-9]+ )?[0-9a-zA-Z \\r\\n\\;\\:\\'\\’\\–\\+\\@\\/\\,\\#\\(\\)\\.\\-]+$/"
			'budget'  					=> ['required','numeric','max:'.EXPECTED_SALARY_MAX_LENGTH,'min:'.EXPECTED_SALARY_MIN_LENGTH],
			'price'  					=> ['required','numeric','min:1','max:'.WORKRATE_MAX],
			'price_max'  				=> ['numeric','min:1'],
			'salary'  					=> ['required','numeric','numeric_range:price,price_max','required_with:price_max','different:price_max','min:1'],
			'salary_max'  				=> ['numeric','min:1'],
			'required_skills'  			=> ['required','array'],
			'required_qualifications'  	=> ['array'],
			'other_perks'  				=> ['required','numeric','max:'.EXPERIENCE_MAX_LENGTH,'min:'.EXPERIENCE_MIN_LENGTH,'regex:/^(?=.+)(?:[1-9]\d*|0)?(?:\.\d+)?$/'],
			'location'             		=> ['required','integer'],
			'time'  					=> ['string','regex:/^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/'],
			'repeat'  					=> ['required','string'],
			'availability_day'  		=> ['required','array'],
			'job_id'  					=> ['integer'],
			'talent_id'  				=> ['integer'],

			/*SUBMIT PROPOSAL*/
			'quoted_price'				=> ['numeric','required','min:1','max:999999'],
			'working_hours'				=> ['string','required'],
			'project_id'				=> ['required'],
			'submission_fee'			=> ['required'],
			'expiration_month'  		=> ['required','string','validate_expiry_month:expiry_year'],
			'expiration_year'  			=> ['required','integer'],
			'card_type'  				=> ['required','string','validate_card_type'],
			'card_number'  				=> ['required','string','regex:/(^[0-9]+$)+/','max:'.CARD_NUMBER_MAX_LENGTH,'min:'.CARD_NUMBER_MIN_LENGTH],
			'cvv'  				=> ['required','string','regex:/(^[0-9]+$)+/','max:'.CVV_MAX_LENGTH,'min:'.CVV_MIN_LENGTH],

			/*CHANGE PASSWORD*/
			'old_password'             	=> ['required','string','old_password:'.((!empty(\Auth::user()->password))?\Auth::user()->password:"")],
			'new_password'             	=> ['required','string','different:old_password','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/','min:'.PASSWORD_MIN_LENGTH,'max:'.PASSWORD_MAX_LENGTH],
			'new_confirm_password'      => ['required','same:new_password'],
			'portfolio'          		=> ['required','string','regex:/^([0-9]+ )?[0-9a-zA-Z \\r\\n\\;\\:\\\'\\\"\\’\\–\\+\\@\\/\\,\\#\\(\\)\\.\\-\\?\\!\\”\\’\\“]+$/'],
			'portfolio_description'  	=> ['required','string',"regex:/^([0-9]+ )?[0-9a-zA-Z \\r\\n\\;\\:\\'\\\"\\’\\–\\+\\@\\/\\,\\#\\(\\)\\.\\-\\?\\!\\”\\’\\“\\&]+$/",'max:'.PORTFOLIO_DESCRIPTION_MAX_LENGTH,'min:'.PORTFOLIO_DESCRIPTION_MIN_LENGTH],
			
			/*REVIEW*/
			'review_average'		=> ['required','numeric','min:0.5'],	
			'review_performance'	=> ['required','numeric','min:0.5'],	
			'review_punctuality'	=> ['required','numeric','min:0.5'],
			'review_quality'		=> ['required','numeric','min:0.5'],
			'review_skill'			=> ['required','numeric','min:0.5'],
			'review_support'		=> ['required','numeric','min:0.5'],


			'review_feedback'		=> ['required','numeric','min:0.5'],	
			'review_communication'	=> ['required','numeric','min:0.5'],
			'review_deadlines'		=> ['required','numeric','min:0.5'],
			'review_learning'		=> ['required','numeric','min:0.5'],
			'review_support'		=> ['required','numeric','min:0.5'],


			'review_description'  	=> ['required','string',"regex:/^([0-9]+ )?[0-9a-zA-Z \\r\\n\\;\\:\\'\\’\\–\\+\\@\\/\\,\\#\\(\\)\\.\\-\\?\\!]+$/",'max:'.REVIEW_DESCRIPTION_MAX_LENGTH,'min:'.REVIEW_DESCRIPTION_MIN_LENGTH],

			'res_rate'            	=> ['required'],
			'rasie_dispute_reason'  => ['required','string',"regex:/^([0-9]+ )?[0-9a-zA-Z \\/\\-\\&\\*\\%\\(\\)\\{\\}\\$\\!\\r\\n\\;\\:\\'\\’\\–\\+\\@\\/\\,\\#\\(\\)\\.\\-\\?\\!]+$/",'max:'.RAISE_DISPUTE_REASON_MAX_LENGTH,'min:'.RAISE_DISPUTE_REASON_MIN_LENGTH],
			'rasie_dispute_reason_id'  => ['required'],
			'report_abuse_reason'  	=> ['required','string',"regex:/^([0-9]+ )?[0-9a-zA-Z \\!\\r\\n\\;\\:\\'\\’\\–\\+\\@\\/\\,\\#\\(\\)\\.\\-\\?\\!]+$/",'max:'.REPORT_ABUSE_REASON_MAX_LENGTH,'min:'.REPORT_ABUSE_REASON_MIN_LENGTH],
			'newsletter_subscribed' => ['required','string'],
			'touch_login'			=> ['required'], 
			'hire_talent_message'	=> ['required'], 
			'record_id'				=> ['required'], 
			'hire_talent_message'  	=> ['required','string',"regex:/^([0-9]+ )?[0-9a-zA-Z \\!\\r\\n\\;\\:\\'\\’\\–\\+\\@\\/\\,\\#\\(\\)\\.\\-\\?\\!]+$/",'max:'.HIRE_TALENT_MESSAGE_MAX_LENGTH,'min:'.HIRE_TALENT_MESSAGE_MIN_LENGTH],
			'invite_code'			=> ['required','string'],
			/*ADMIN VALIDATION*/
			'features'				=> ['required'],
			'question_type'			=> ['required'],
			'admin_country'			=> ['required','integer'],
			'admin_state_name'		=> ['required','string'],
			'admin_iso_code'		=> ['required','string'],
			'admin_state'			=> ['required','integer'],
			'admin_city_name'		=> ['required','string'],
			'admin_industry_name'	=> ['required','string'],
			'admin_industry'		=> ['required','integer'],
			'admin_abusive_words'	=> ['required','string'],
			'admin_degree'			=> ['required','string'],
			'admin_certificate'		=> ['required','string'],
			'admin_college'			=> ['required','string'],
			'admin_company'			=> ['required','string'],
			'admin_skill'			=> ['required','string'],
			'admin_feature_name'	=> ['required','string'],
			'admin_faq_title'		=> ['required','string'],
			'admin_faq_description' => ['required','string'],
			'admin_faq_topic' 		=> ['required','integer'],
			'admin_faq_category' 	=> ['required','integer'],
			'admin_dispute_concern' => ['required','string'],
			'admin_workfield'		=> ['required','string'],
			'coupon_code'			=> ['nullable','exists:coupon,code'],
			'invite_code'			=> ['required'],
			
		];

		return $validation[$key];
	}
