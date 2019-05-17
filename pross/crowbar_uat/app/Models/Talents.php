<?php

    namespace Models; 

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Crypt;
    use Illuminate\Support\Facades\Mail;

    class Talents extends Model{
        protected $table = 'users';
        protected $primaryKey = 'id_user';
        
        const CREATED_AT = 'created';
        const UPDATED_AT = 'updated';

        protected $fillable = [
            'type',
            'name',
            'first_name',
            'last_name',
            'email',
            'gender',
            'password',
            'status',
            'last_login',
        ];

        protected $hidden = [
            'password', 'remember_token',
        ]; 

        /**
         * [This method is for relating chat request] 
         * @return Boolean
         */

        public function chat(){
            return $this->hasOne('\Models\ChatRequests','sender_id','id_user');
        }   

        /**
         * [This method is for relating chat request] 
         * @return Boolean
         */

        public function chats(){
            return $this->hasOne('\Models\ChatRequests','receiver_id','id_user');
        }  
      
        /**
         * [This method is for relating applied proposal] 
         * @return Boolean
         */

        public function applied(){
            return $this->hasMany('\Models\Proposals','user_id','id_user');
        }
      
        /**
         * [This method is for relating awarded proposal] 
         * @return Boolean
         */

        public function awarded(){
            return $this->hasMany('\Models\Proposals','user_id','id_user');
        }
      
        /**
         * [This method is for relating awarded proposal] 
         * @return Boolean
         */

        public function completed(){
            return $this->hasMany('\Models\Proposals','user_id','id_user');
        }

        /**
         * [This method is for scope for default keys] 
         * @return Boolean
         */

        public function scopeDefaultKeys($query){
            $prefix         = DB::getTablePrefix();
            
            $query->addSelect([
                'users.id_user',
                'users.first_name',
                'users.expertise',
                'users.gender',
                'users.experience',
                \DB::Raw("YEAR({$prefix}users.created) as member_since"),
            ])->name()->companyLogo();

            return $query;
        }

        /**
         * [This method is for scope for user name] 
         * @return Boolean
         */

        public function scopeName($query){
            $prefix         = DB::getTablePrefix();
            
            $query->addSelect([
                \DB::Raw("TRIM(IF({$prefix}users.last_name IS NULL, {$prefix}users.first_name, CONCAT({$prefix}users.first_name,' ',{$prefix}users.last_name))) as name")
            ]);

            return $query;
        }   

        /**
         * [This method is for scope for talent saved by employer] 
         * @return Boolean
         */

        public function ScopeIsTalentSavedEmployer($query, $employer_id){
            $prefix         = DB::getTablePrefix();
            
            $query->leftJoin('saved_talent',function($leftjoin) use($employer_id){
                $leftjoin->on('saved_talent.user_id','=',\DB::Raw($employer_id));
                $leftjoin->on('saved_talent.talent_id','=','users.id_user');
            })->addSelect([
                \DB::Raw("
                    IF(
                        {$prefix}saved_talent.id_saved IS NOT NULL,
                        '".DEFAULT_YES_VALUE."',
                        '".DEFAULT_NO_VALUE."'
                    ) as is_saved
                "),
            ]);

            return $query;
        }  

        /**
         * [This method is for scope for talent viewed by employer] 
         * @return Boolean
         */

        public function ScopeIsTalentViewedEmployer($query, $employer_id){
            $prefix         = DB::getTablePrefix();
            
            $query->leftJoin('viewed_talent',function($leftjoin) use($employer_id){
                $leftjoin->on('viewed_talent.employer_id','=',\DB::Raw($employer_id));
                $leftjoin->on('viewed_talent.talent_id','=','users.id_user');
            })->addSelect([
                \DB::Raw("
                    IF({$prefix}viewed_talent.id_viewed_talent IS NOT NULL,{$prefix}viewed_talent.updated,'') as last_viewed
                "),
            ]);

            return $query;
        } 


        /**
         * [This method is for scope for talent's proposals] 
         * @return Boolean
         */

        public function ScopeTalentProposals($query, $project_id){
            $prefix         = DB::getTablePrefix();
            $base_url       = ___image_base_url();
                
            $query->leftJoin('talent_proposals',function($leftjoin) use($project_id){
                $leftjoin->on('talent_proposals.project_id','=',\DB::Raw($project_id));
                $leftjoin->on('talent_proposals.user_id','=','users.id_user');
            })->leftJoin('files as proposal_document',function($leftjoin){
                $leftjoin->on('proposal_document.record_id','=','talent_proposals.id_proposal');
                $leftjoin->on('proposal_document.type','=',\DB::Raw("'proposal'"));
            })->addSelect([
                \DB::Raw('`CONVERT_PRICE`('.$prefix.'talent_proposals.quoted_price, '.$prefix.'talent_proposals.price_unit, "'.request()->currency.'") AS quoted_price'),
                \DB::Raw("'".___cache('currencies')[request()->currency]."' as price_unit"),
                'talent_proposals.user_id as talent_id',
                'talent_proposals.created as proposal_sent',
                'talent_proposals.project_id',
                'talent_proposals.id_proposal as proposal_id',
                'talent_proposals.comments',
                'talent_proposals.working_hours',
                'talent_proposals.status as proposal_status',
                \DB::Raw("
                    IF(
                        ({$prefix}talent_proposals.status = 'applied'),
                        0,
                        1
                    ) as proposals_count
                "),
                \DB::Raw("
                    IF(
                        ({$prefix}talent_proposals.status = 'accepted'),
                        '".trans('website.W0781')."',
                        IF(
                            ({$prefix}talent_proposals.status = 'applied'),
                            '".trans('website.W0782')."',
                            IF(
                                ({$prefix}talent_proposals.status = 'rejected'),
                                '".trans('website.W0783')."',
                                '".N_A."'
                            )
                        )
                    ) as current_proposals_status
                "),
                'proposal_document.filename',
                'proposal_document.extension',
                \DB::Raw("CONCAT('{$base_url}',{$prefix}proposal_document.folder,{$prefix}proposal_document.filename) as file_url"),
            ]);

            return $query;
        }

        /**
         * [This method is for scope for talent's proposals] 
         * @return Boolean
         */

        public function ScopeTalentProject($query, $project_id){
            $prefix         = DB::getTablePrefix();
            $current_date   = date('Y-m-d H:i:s');
            
            $query->leftJoin('projects',function($leftjoin) use($project_id){
                $leftjoin->on('projects.id_project','=',\DB::Raw($project_id));
            })->addSelect([
                'projects.user_id as company_id',
                'projects.employment',
                \DB::Raw("
                    IF(
                        DATE({$prefix}projects.enddate) < DATE('{$current_date}'),
                        'closed',
                        IF(
                            ({$prefix}projects.project_status = 'closed' && {$prefix}projects.closedate IS NULL),
                            'completed',
                            {$prefix}projects.project_status
                        )
                    ) as project_status
                ")
            ]);

            return $query;
        }

        /**
         * [This method is used to add_new] 
         * @param [Boolean]$insert_data [Used for insert data]
         * @return Boolean
         */ 

        public static function add_new($insert_data){
            if(empty($insert_data)){
                return (bool) false;
            }else{
                $insert_data['commission'] = ___cache('commission');
            }

            return self::insertGetId($insert_data);
        }

        /**
         * [This method is for scope for total reviews] 
         * @return Boolean
         */

        public function scopeReview($query){
            $prefix         = DB::getTablePrefix();
            $query->addSelect([
                \DB::Raw("
                    (
                        SELECT COUNT(id_review) 
                        FROM {$prefix}reviews 
                        WHERE {$prefix}reviews.receiver_id = {$prefix}users.id_user
                    ) AS total_review
                "),
                \DB::Raw("
                    (
                        SELECT IFNULL(ROUND(AVG(review_average), 1), '0.0') 
                        FROM {$prefix}reviews 
                        WHERE {$prefix}reviews.receiver_id = {$prefix}users.id_user
                    ) AS rating
                ")
            ]);

            return $query;
        }

        public function scopeCompanyLogo($query){
            $base_url       = ___image_base_url();
            $prefix         = DB::getTablePrefix();
            
            $query->leftJoin('files as files',function($leftjoin){
                $leftjoin->on('files.user_id','=','users.id_user');
                $leftjoin->on('files.type','=',\DB::Raw('"profile"'));
            })->addSelect([
                \DB::Raw("
                    IF(
                        {$prefix}files.filename IS NOT NULL,
                        CONCAT('{$base_url}',{$prefix}files.folder,{$prefix}files.filename),
                        CONCAT('{$base_url}','images/','".DEFAULT_AVATAR_IMAGE."')
                    ) as company_logo
                "),
            ]);

            return $query;
        }


        /**
         * [This method is for scope for country] 
         * @return Boolean
         */

        public function scopeCountry($query){
            $language       = \App::getLocale();
        
            $query->leftjoin('countries','countries.id_country','users.country')->addSelect([
                \DB::Raw("IF(({$language} != ''),`{$language}`, `en`) as country_name")
            ]);

            return $query;
        }         
        /**
         * [This method is used to findById] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$keys [Used for keys]
         * @return Data Response
         */

        public static function findById($userID,$keys = ['*']){
            $table_user = DB::table((new static)->getTable());

            if($index = array_search('status',$keys)){
                unset($keys[$index]);
                $keys[$index] = 'users.status';
            }
            
            if(!empty($keys)){
                $table_user->select($keys);
            }
            $table_user->leftjoin('countries as country_code','country_code.phone_country_code','=','users.country_code');
            $table_user->leftjoin('countries','countries.id_country','=','users.country');
            $table_user->leftjoin('state','state.id_state','=','users.state');
            $table_user->leftjoin('city','city.id_city','=','users.city');

            return $table_user->where(
                array(
                    'id_user' => $userID,
                )
            )->whereNotIn('users.status',['trashed'])->first();
        }

        /**
         * [This method is used to findByEmail] 
         * @param [Varchar]$email[Used for email]
         * @param [Varchar]$keys [Used for keys]
         * @return Data Response
         */ 

        public static function findByEmail($email,$keys = ['*']){
            $table_user = DB::table((new static)->getTable());

            if(!empty($keys)){
                $table_user->select($keys);
            }

            return $table_user->where(
                array(
                    'email' => $email,
                )
            )->whereNotIn('status',['trashed'])->first();
        }

        /**
         * [This method is used to findBySocialId ]
         * @param [Varchar]$social_key[Used for Social key]
         * @param [Integer]$social_id [Used for social id]
         * @param [Varchar]$keys [Used for keys]
         * @return Data Response
         */ 

        public static function findBySocialId($social_key,$social_id,$keys = ['*']){
            $table_user = DB::table((new static)->getTable());

            if(!empty($keys)){
                $table_user->select($keys);
            }

            return $table_user->where(
                array(
                    $social_key => $social_id,
                )
            )->whereNotIn('status',['trashed'])->first();
        }

        /**
         * [This method is used for interested_in] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public static function interested_in($user_id){
            $table_user = DB::table('talent_interests');

            $data = $table_user->where(
                array(
                    'user_id' => $user_id,
                )
            )->get();
            
            $interests = (array) json_decode(json_encode($data),true);
            $interest = array_column($interests, 'interest');
            $workrate = array_column($interests, 'workrate');

            return array_combine($interest, $workrate);
        }

        /**
         * [This method is used for skills] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public static function jobdetails($user_id){
            $result = Talents::select('id_user')->withCount([
                'applied',
                'awarded' => function($q){
                    $q->where('status','accepted');
                },
                'completed' => function($q){
                    $q->leftJoin('projects','projects.id_project','=','talent_proposals.project_id')
                    ->isProjectClosed()
                    ->where('talent_proposals.status','accepted');
                }
            ])->where('id_user',$user_id)->get()->first();

            return json_decode(json_encode($result),true);
        }

        /**
         * [This method is used for interested_in] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public static function remuneration($user_id){

            $prefix = DB::getTablePrefix();

            $table_user = DB::table('talent_interests');

            $keys = [
                'talent_interests.user_id',
                'talent_interests.interest',
                'talent_interests.workrate',
                'talent_interests.currency',
                \DB::Raw('`CONVERT_PRICE`('.$prefix.'talent_interests.workrate, '.$prefix.'talent_interests.currency, "'.request()->currency.'") AS converted_price')
                ];

            $data = $table_user->addSelect($keys)->where(
                array(
                    'user_id' => $user_id,
                )
            )->get();
            
            $interests = (array) json_decode(json_encode($data),true);
            array_walk($interests, function(&$item){
                unset($item['user_id']);
            });

            return $interests;
        }

        /**
         * [This method is used for skills] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public static function skills($user_id){
            $result = Talents::where('id_user',$user_id)->select('id_user')->with([
                'skill.skills' => function($q){
                    $q->select(
                        'id_skill',
                        'skill_name'
                    );
                }
            ])->get()->first();

            return array_column(json_decode(json_encode($result->skill),true), 'skills');
        }

        /**
         * [This method is used for interests] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public function interests(){
            return $this->hasMany('\Models\TalentInterests','user_id','id_user');
        }

        /**
         * [This method is used for interests] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public function skill(){
            return $this->hasMany('\Models\TalentSkills','user_id','id_user');
        }

        /**
         * [This method is used for subindustries] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public function subindustries(){
            return $this->hasMany('\Models\TalentSubindustries','user_id','id_user');
        }

        /**
         * [This method is used for interests] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public function jurisdiction(){
            // return $this->hasOne('\Models\companyConnectedTalent','id_user','id_user');
            return $this->belongsTo('\Models\companyConnectedTalent','id_user','id_user')->where('user_type','=','owner');
        }

        /**
         * [This method is used for subindustry] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public static function subindustry($user_id){
            $language       = \App::getLocale();
            $prefix         = DB::getTablePrefix();
            
            $result = Talents::where('id_user',$user_id)->select('id_user')->with([
                'subindustries.subindustries' => function($q) use($language,$prefix){
                    $q->select(
                        'id_industry',
                        \DB::Raw("IF(({$language} != ''),`{$language}`, `en`) as name")
                    );
                }
            ])->get()->first();

            return array_column(json_decode(json_encode($result->subindustries),true), 'subindustries');
        }

        /**
         * [This method is used for industries] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public function industries(){
            return $this->hasMany('\Models\TalentIndustries','user_id','id_user');
        }

        /**
         * [This method is used for industry] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public static function industry($user_id){
            $language       = \App::getLocale();
            $prefix         = DB::getTablePrefix();
            
            $result = Talents::where('id_user',$user_id)->select('id_user')->with([
                'industries.industries' => function($q) use($language,$prefix){
                    $q->select(
                        'id_industry',
                        \DB::Raw("IF(({$language} != ''),`{$language}`, `en`) as name")
                    );
                }
            ])->get()->first();

            return array_filter(array_column(json_decode(json_encode($result->industries),true), 'industries'));
        }

        /**
         * [This method is used for certificates] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public static function certificates($user_id){
            $table_user = DB::table('talent_certificates');

            $data = $table_user->where(
                array(
                    'user_id' => $user_id,
                )
            )->get();
            
            $certificates = (array) json_decode(json_encode($data),true);
            return array_column($certificates, 'certificate');
        }

        /**
         * [This method is used for educations] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$keys [Used for keys]
         * @return Data Response
         */ 

        public static function educations($user_id,$education_id = NULL){
            $table_user = DB::table('talent_educations as talent_educations');
            
            if(!empty($education_id)){
                $table_user->where('id_education',$education_id);
            }

            $data = $table_user->select([
                'talent_educations.id_education as id_education',
                'talent_educations.user_id as user_id',
                'college.college_name as college',
                'talent_educations.degree as degree',
                'talent_educations.passing_year as passing_year',
                'talent_educations.area_of_study as area_of_study',
                // 'talent_educations.degree_status as degree_status',
                // 'talent_educations.degree_country as degree_country',
                'college.image',
            ])
            ->where(
                array(
                    'user_id' => $user_id,
                )
            )
            ->leftjoin("college as college","college.id_college", "=", "talent_educations.college")
            ->orderBy('passing_year','ASC')->get();
            
            $education =  (array) json_decode(json_encode($data),true);
            if(!empty($education)){
                array_walk($education,function(&$item){
                    $item['degree_name']           = ___cache('degree_name')[$item['degree']]; 
                    // $item['degree_country_name']   = ___cache('countries')[$item['degree_country']];
                    $item['logo']                  = (!empty($item['image']))?asset(str_replace('/college', '/college/thumbnail', $item['image'])):'';
                });
            }

            return $education;
        }

        /**
         * [This method is used for work experiences] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$keys [Used for keys]
         * @return Data Response
         */ 

        public static function work_experiences($user_id,$experience_id = NULL){
            $table_talent_work_experiences = DB::table('talent_work_experiences');
            $table_talent_work_experiences->select([
                'talent_work_experiences.id_experience',
                'talent_work_experiences.jobtitle',
                'company.id_company',
                'company.company_name',
                'talent_work_experiences.joining_month',
                'talent_work_experiences.joining_year',
                'talent_work_experiences.is_currently_working',
                'talent_work_experiences.job_type',
                'talent_work_experiences.relieving_month',
                'talent_work_experiences.relieving_year',
                'talent_work_experiences.country',
                'talent_work_experiences.state',
                'company.image',
            ]);

            if(!empty($experience_id)){
                $table_talent_work_experiences->where('id_experience',$experience_id);
            }

            $data = $table_talent_work_experiences->where(
                array(
                    'user_id' => $user_id,
                )
            )
            ->leftjoin("company as company","company.id_company", "=", "talent_work_experiences.company_name")
            ->orderByRaw("(CASE WHEN relieving_year IS NULL THEN 1 ELSE 0 END),relieving_year")->get();
            
            $experiences = (array) json_decode(json_encode($data),true);
            array_walk($experiences, function(&$item, $key){
                $item['joining']        = sprintf("%s %s",date('F',strtotime(sprintf("2017-%s-01",$item['joining_month']))),$item['joining_year']);
                $item['relieving']      = sprintf("%s %s",date('F',strtotime(sprintf("2017-%s-01",$item['relieving_month']))),$item['relieving_year']);
                $item['country_name']   = ___cache('countries')[$item['country']];
                $item['state_name']     = (!empty($item['state']))?___cache('states')[$item['state']]:N_A;
                $item['logo']           = (!empty($item['image']))?asset(str_replace('/company', '/company/thumbnail', $item['image'])):'';
            });

            return $experiences;
        }

        /**
         * [This method is used to update interest] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$interest[USed for interest]
         * @return Data Response
         */ 

        public static function update_interest($user_id,$interests,$workrates,$currency){
            $table_talent_interest = DB::table('talent_interests');

            if(!empty($interests)){
                $interests = array_filter($interests);
                $workrates = array_filter($workrates);
                $insert = [];
                array_walk($interests, function($i,$key) use($user_id,$workrates,$currency,&$insert){ 
                    $insert[] = array(
                        'interest' => $i,
                        'workrate' => $workrates[$key],
                        'user_id'  => $user_id,
                        'currency' => $currency,
                    ); 
                });
            }

            $table_talent_interest->where('user_id',$user_id);
            $table_talent_interest->delete();
            
            if(!empty($insert)){
                return $table_talent_interest->insert($insert);
            }else{
                return false;
            }
        }
        /**
         * [This method is used to update interest] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$interest[USed for interest]
         * @return Data Response
         */ 

        public static function update_interest_currency($user_id,$currency){
            $table_talent_interest = DB::table('talent_interests');

            $is_workrate = $table_talent_interest->where('user_id','=',$user_id)->get();
            if(!empty($is_workrate)){
                $is_update = $table_talent_interest->where('user_id','=',$user_id)->update(['currency' => $currency]);
            }
            return true;
        }

        /**
         * [This method is used to update subindustry] 
         * @param [Integer]$user_id [Used for user id]
         * @param [VArchar]$subindustry[Used for subindustry]
         * @return Data Response
         */ 

        public static function update_subindustry($user_id,$subindustry,$industry_id){
            $table_talent_subindustry = DB::table('talent_subindustries');
            $table_talent_subindustry->where('user_id',$user_id);
            $table_talent_subindustry->delete();
            if(!empty($subindustry) && is_array($subindustry)){
                $subindustries_list = array_map(
                    function($i) use($user_id,$industry_id){
                        if(!in_array(strtolower($i), (array)array_keys(\Cache::get('abusive_words')))){
                            return array(
                                'en'                => $i,
                                'parent'            => $industry_id,
                                'created'           => date('Y-m-d H:i:s'),
                                'updated'           => date('Y-m-d H:i:s')
                            ); 
                        }
                    }, 
                    $subindustry
                );

                $subindustries_list = array_filter($subindustries_list);
                if(empty($subindustry)){
                    return true;
                }else if(count($subindustries_list) === count($subindustry)){
                    if(!empty($subindustries_list)){   
                        foreach ($subindustries_list as $key) {
                            $table_industries = DB::table('industries');
                            $inserted_industry = $table_industries->select('id_industry')->where(['en' => $key['en']])->first();
                            
                            if(!empty($inserted_industry->id_industry)){
                                $subindustry_id = $inserted_industry->id_industry;
                            }else{
                                $subindustry_id    = $table_industries->insertGetId($key);
                                \Cache::forget('subindustries_name');
                            }

                            $subindustries[] = [
                                'user_id'           => $user_id,
                                'subindustry_id'    => $subindustry_id
                            ];
                        }
                        return $table_talent_subindustry->insert($subindustries);
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }else{
                return true;
            }
        }

        /**
         * [This method is used to update industry] 
         * @param [Integer]$user_id [Used for user id]
         * @param [VArchar]$industry[Used for industry]
         * @return Data Response
         */ 

        public static function update_industry($user_id,$industry){
            $user_details = self::get_user((object)['id_user' => $user_id]); 
            
            if(current(array_column($user_details['industry'], 'id_industry')) != (int)current($industry)){
                $table_talent_subindustry = DB::table('talent_subindustries');
                $table_talent_subindustry->where('user_id',$user_id);
                $table_talent_subindustry->delete();
            }

            $table_talent_industries = DB::table('talent_industries');

            if(!empty($industry) && is_array($industry)){
                $industries = array_map(
                    function($i) use($user_id){ 
                        return array(
                            'industry_id'    => $i,
                            'user_id'           => $user_id
                        ); 
                    }, 
                    $industry
                );
            }

            $table_talent_industries->where('user_id',$user_id);
            $table_talent_industries->delete();
            if(!empty($industries)){
                return  $table_talent_industries->insert($industries);
            }else{
                return false;
            }
        }

        /**
         * [This method is used to update skill] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$skill[Used for skill]
         * @param [VArchar]$subindustry[Used for subindustry]
         * @return Data Response
         */

        public static function update_skill($user_id,$skill,$subindustry = ""){
            $table_talent_skill = DB::table('talent_skills');
            $table_talent_skill->where('user_id',$user_id);
            $table_talent_skill->delete();
            if(!empty($skill) && is_array($skill)){
                $skills_list = array_map(
                    function($i) use($user_id){
                        return array(
                            'skill_name'        => $i,
                            'created'           => date('Y-m-d H:i:s'),
                            'updated'           => date('Y-m-d H:i:s')
                        ); 
                    }, 
                    $skill
                );

                
                foreach ($skills_list as $key) {
                    $table_skill = DB::table('skill');
                    $inserted_skill = $table_skill->select('id_skill')->where('skill_name',$key['skill_name'])->first();
                    if(!empty($inserted_skill->id_skill)){
                        $skill_id = $inserted_skill->id_skill;
                    }else{
                        $skill_id    = $table_skill->insertGetId($key);
                        \Cache::forget('skills');
                    }

                    $skills[] = [
                        'user_id'   => $user_id,
                        'skill_id'  => $skill_id
                    ];
                }
                return $table_talent_skill->insert($skills);
            }else{
                return false;
            }
        }        

        /**
         * [This method is used to update certificate] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$certificate[Used for certificate]
         * @return Data Response
         */ 

        public static function update_certificate($user_id,$certificate){
            $table_talent_certificate = DB::table('talent_certificates');
            $table_certificate = DB::table('certificate');
            if(is_array($certificate)){
                $certificate_lists = array_map(
                    function($i){ 
                        return array(
                            'certificate_name'  => $i,
                            'created'           => date('Y-m-d H:i:s'),
                            'updated'           => date('Y-m-d H:i:s')
                        ); 
                    }, 
                    $certificate
                );

                $certificates = array_map(
                    function($i) use($user_id){ 
                        return array(
                            'certificate' => $i,
                            'user_id' => $user_id
                        ); 
                    }, 
                    $certificate
                );
            }

            if(!empty($certificate_lists)){
                DB::statement(\Models\Customs::insertIgnoreQuery($certificate_lists,'cb_certificate'));
                \Cache::forget('certificates');
            }

            $table_talent_certificate->where('user_id',$user_id);
            $table_talent_certificate->delete();
            
            if(!empty($certificates)){
                return $table_talent_certificate->insert($certificates);
            }else{
                return false;
            }
        }

        /**
         * [This method is used to update work experience] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$work_experience[Used for work experience]
         * @return Data Response
         */ 

        public static function update_work_experience($user_id,$work_experience){
            $table_talent_certificate = DB::table('talent_certificates');

            $certificates = array_map(
                function($i) use($user_id){ 
                    return array(
                        'certificate' => $i,
                        'user_id' => $user_id
                    ); 
                }, 
                $certificate
            );

            $table_talent_certificate->where('user_id',$user_id);
            $table_talent_certificate->delete();

            return $table_talent_certificate->insert($certificates);
        }

        /**
         * [This method is used for adding education] 
         * @param [Integer]$id_education [Used for education id]
         * @param [Varchar]$educations[Used for education]
         * @return Data Response
         */ 

        public static function add_education($user_id,$educations){
            /*ADD SCHOOL OR COLLEGE */
            $college = \DB::table('college')->where('college_name',$educations['college'])->get()->first();

            if(!empty($college)){
                $educations['college'] = $college->id_college;
            }else{
                $educations['college'] = \DB::table('college')->insertGetId([
                    'college_name' => $educations['college'],
                    'created' => date('Y-m-d H:i:s'),
                    'updated' => date('Y-m-d H:i:s'),
                ]);
                
                \Cache::forget('colleges');
            }
            
            $educations['user_id'] = $user_id;
            $educations['created'] = date('Y-m-d H:i:s');
            $educations['updated'] = date('Y-m-d H:i:s');

            return \DB::table('talent_educations')->insertGetId($educations);
        }

        /**
         * [This method is used to update education] 
         * @param [Integer]$id_education [Used for education id]
         * @param [Varchar]$educations[Used for education]
         * @return Data Response
         */ 

        public static function update_education($id_education,$educations){
            $college = \DB::table('college')->where('college_name',$educations['college'])->get()->first();

            if(!empty($college)){
                $educations['college'] = $college->id_college;
            }else{
                $educations['college'] = \DB::table('college')->insertGetId([
                    'college_name' => $educations['college'],
                    'created' => date('Y-m-d H:i:s'),
                    'updated' => date('Y-m-d H:i:s'),
                ]);
                
                \Cache::forget('colleges');
            }

            $educations['updated'] = date('Y-m-d H:i:s');
            return DB::table('talent_educations')->where('id_education',$id_education)->update($educations);
        }

        /**
         * [This method is used for getting education] 
         * @param [type]$where[Used for where clause]
         * @param [Fetch]$fetch[Used for fetching]
         * @return Data Response
         */ 
        
        public static function get_education($where = "",$fetch = 'all'){
            $table_talent_educations = DB::table('talent_educations as talent_educations');
            $table_talent_educations->leftjoin("college as college","college.id_college","=","talent_educations.college");
            
            $table_talent_educations->select([
                'talent_educations.area_of_study',
                'talent_educations.created',
                'talent_educations.degree',
                'talent_educations.degree_country',
                'talent_educations.degree_status',
                'talent_educations.id_education',
                'talent_educations.passing_year',
                'talent_educations.updated',
                'talent_educations.user_id',
                'college.image',
                'college.id_college',
                'college.college_name',
                'college.college_status',
                'college.college_name as college'
            ]);

            if(!empty($where)){
                $table_talent_educations->whereRaw($where);
            }

            if($fetch == 'count'){
                return $table_talent_educations->get()->count();
            }else if($fetch == 'single'){
                return (array) $table_talent_educations->get()->first();
            }else if($fetch == 'all'){
                return json_decode(json_encode($table_talent_educations->get()),true);
            }else{
                return $table_talent_educations->get();
            }
        }

        /**
         * [This method is used to delete education] 
         * @param [type]$where[Used for where clause]
         * @return Data Response
         */ 

        public static function delete_education($where = ""){
            $table_talent_educations = DB::table('talent_educations');

            if(!empty($where)){
                $table_talent_educations->whereRaw($where);
            }

            return $table_talent_educations->delete();
        }

        /**
         * [This method is used to add experience] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$experiences[Used for experience]
         * @return Data Response
         */ 

        public static function add_experience($user_id,$experiences){
            /*ADD   COMPANY */
            $company = \DB::table('company')->where('company_name',$experiences['company_name'])->get()->first();

            if(!empty($company)){
                $experiences['company_name'] = $company->id_company;
            }else{
                $experiences['company_name'] = \DB::table('company')->insertGetId([
                    'company_name' => $experiences['company_name'],
                    'updated' => date('Y-m-d H:i:s'),
                    'created' => date('Y-m-d H:i:s')
                ]);

                \Cache::forget('companies');
            }

            $table_talent_experience = DB::table('talent_work_experiences');
            $experiences['user_id'] = $user_id;
            $experiences['created'] = date('Y-m-d H:i:s');
            $experiences['updated'] = date('Y-m-d H:i:s');
                        
            $isInserted = $table_talent_experience->insertGetId($experiences);

            if($experiences['is_currently_working'] == DEFAULT_YES_VALUE){
                $table_talent_experience = DB::table('talent_work_experiences');
                $table_talent_experience->where('id_experience','!=',$isInserted);
                $isUpdated = $table_talent_experience->update(['is_currently_working' => DEFAULT_NO_VALUE,'updated' => date('Y-m-d H:i:s')]);                
            }

            return $isInserted;
        }

        /**
         * [This method is used to update experience] 
         * @param [Integer]$id_experience[Used for experience id]
         * @param [Varchar]$experiences[Used for experience]
         * @return Data Response
         */ 

        public static function update_experience($id_experience,$experiences){
            $company = \DB::table('company')->where('company_name',$experiences['company_name'])->get()->first();

            if(!empty($company)){
                $experiences['company_name'] = $company->id_company;
            }else{
                $experiences['company_name'] = \DB::table('company')->insertGetId([
                    'company_name' => $experiences['company_name'],
                    'updated' => date('Y-m-d H:i:s'),
                    'created' => date('Y-m-d H:i:s')
                ]);

                \Cache::forget('companies');
            }

            $table_talent_experience = DB::table('talent_work_experiences');
            $table_talent_experience->where('id_experience',$id_experience);
            
            $isUpdated = $table_talent_experience->update($experiences);

            if($experiences['is_currently_working'] == DEFAULT_YES_VALUE){
                $table_talent_experience = DB::table('talent_work_experiences');
                $table_talent_experience->where('id_experience','!=',$id_experience);
                $isUpdated = $table_talent_experience->update(['is_currently_working' => DEFAULT_NO_VALUE,'updated' => date('Y-m-d H:i:s')]);                
            }

            return $isUpdated;
        }

        /**
         * [This method is used to get experience] 
         * @param [type]$where [Used for where clause]
         * @param [Fetch]$fetch[Used for fetching]
         * @return Data Response
         */ 

        public static function get_experience($where = "",$fetch = 'all'){
            $table_talent_work_experiences = DB::table('talent_work_experiences');

            if(!empty($where)){
                $table_talent_work_experiences->whereRaw($where);
            }

            $table_talent_work_experiences->leftJoin('company','company.id_company','=','talent_work_experiences.company_name');
            $table_talent_work_experiences->select([
                'company.company_name',
                'talent_work_experiences.country',
                'talent_work_experiences.created',
                'talent_work_experiences.id_experience',
                'talent_work_experiences.is_currently_working',
                'talent_work_experiences.job_type',
                'talent_work_experiences.jobtitle',
                'talent_work_experiences.joining_month',
                'talent_work_experiences.joining_year',
                'talent_work_experiences.relieving_month',
                'talent_work_experiences.relieving_year',
                'talent_work_experiences.state',
                'talent_work_experiences.updated',
                'talent_work_experiences.user_id',
            ]);

            if($fetch == 'count'){
                return $table_talent_work_experiences->get()->count();
            }else if($fetch == 'single'){
                return (array) $table_talent_work_experiences->get()->first();
            }else if($fetch == 'all'){
                return json_decode(json_encode($table_talent_work_experiences->get()),true);
            }else{
                return $table_talent_work_experiences->get();
            }
        }

        /**
         * [This method is used to delete experience] 
         * @param [type]$where[Used for where clause]
         * @return Data Response
         */ 

        public static function delete_experience($where = ""){
            $table_talent_work_experiences = DB::table('talent_work_experiences');

            if(!empty($where)){
                $table_talent_work_experiences->whereRaw($where);
            }

            return $table_talent_work_experiences->delete();
        }

        /**
         * [This method is used for social_connectivity] 
         * @param [Varchar]$social_key [Used for social key]
         * @param [Integer]$social_id [Used for social id]
         * @param [type]$email[Used for Email]
         * @return Data Response
         */ 

        public static function social_connectivity($social_key,$social_id,$email){
            $table_user = DB::table((new static)->getTable());
            
            if(!empty($email) && empty($social_id)){
                return (object) $table_user->where(
                    array(
                        'email' => $email,
                    )
                )->whereNotIn('status',['trashed'])->first();
            }else if(!empty($social_id) && !empty($social_key) && empty($email)){
                return $table_user->where([
                    $social_key => $social_id
                ])->whereNotIn('status',['trashed'])->first();
            }else if(!empty($social_id) && !empty($social_key) && !empty($email)){
                return $table_user->whereRaw("( {$social_key} = '{$social_id}' OR email = '{$email}' )")
                ->whereNotIn('status',['trashed'])
                ->first();
            }else{
                return (object) array();
            }
        }

        /**
         * [This method is used for already socialy connected with other] 
         * @param [Varchar]$social_key [Used for social key]
         * @param [Integer]$social_id [Used for social id]
         * @param [type]$email[Used for Email]
         * @return Data Response
         */ 

        public static function is_already_socialy_connected_with_other($social_key,$social_id,$email){
            $table_user = DB::table((new static)->getTable());
            
            return (int) $table_user->where($social_key,$social_id)
            ->where('email','<>',$email)
            ->whereNotIn('status',['inactive','trashed','suspended'])
            ->count();
        }

        /**
         * [This method is used to update social connection] 
         * @param [Varchar]$social_key [Used for social key]
         * @param [Integer]$social_id[Used for user id]
         * @param [Varchar]$email[Used for Email]
         * @return Boolean
         */ 

        public static function update_social_connection($social_key,$social_id,$email){
            $table_user = DB::table((new static)->getTable());
            
            return (bool) $table_user->where('email',$email)->whereNotIn('status',['inactive','trashed','suspended'])->update([$social_key => $social_id,'status' => 'active','updated' => date('Y-m-d H:i:s')]);
        }

        /**
         * [This method is used for change] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$data[Used for data]
         * @return Boolean
         */ 

        public static function change($userId,$data){
            $table_user = DB::table((new static)->getTable());
            
            return (bool) $table_user->where('id_user',$userId)->update($data);
        }

        /**
         * [This method is used for file creation] 
         * @param [Varchar]$data[Used for data]
         * @param [type]$multiple[used for multiple]
         * @param [AVrchar]$return [used for return]
         * @return Json Response
         */ 
        
        public static function create_file($data,$multiple = true, $return = false){
            $table_files = DB::table('files');

            if(empty($multiple)){
                if($table_files->where('user_id',$data['user_id'])->where('type',$data['type'])->get()->count()){
                    $isInserted = $table_files->where('user_id',$data['user_id'])->update($data);
                }else{
                    $isInserted = $table_files->insertGetId($data);
                }
                
                if(!empty($return)){
                    return json_decode(
                        json_encode(
                            $table_files->select(['*','extension as type'])->where('user_id',$data['user_id'])
                            ->whereNotIn('status',['trashed'])
                            ->get()
                            ->first()
                        ),true
                    );
                }else{
                    return 1;
                }
            }else{
                $isInserted = $table_files->insertGetId($data);
                if(!empty($return)){
                    return json_decode(
                        json_encode(
                            $table_files->select(['*','extension as type'])->where('id_file',$isInserted)
                            ->whereNotIn('status',['trashed'])
                            ->get()
                            ->first()
                        ),true
                    );
                }else{
                    return $isInserted;
                }
            }
        }

        /**
         * [This method is used to get file] 
         * @param [type]$where [Used for where clause]
         * @param [Fetch]$fetch[Used for fetching]
         * @param [Varchar]$keys[Used for keys]
         * @return Data Response
         */ 

        public static function get_file($where = "",$fetch = 'all',$keys = ['*']){
            $table_files = DB::table('files');
            $table_files->select($keys);

            if(!empty($where)){
                $table_files->whereRaw($where);
            }

            if($fetch == 'count'){
                return $table_files->get()->count();
            }else if($fetch == 'single'){
                return (array) $table_files->get()->first();
            }else if($fetch == 'all'){
                $result = json_decode(json_encode($table_files->get()),true);
                
                foreach ($result as &$item) {
                    $item['file_url'] = asset(sprintf('%s%s',$item['folder'],$item['filename']));
                    $item['filename'] = $item['filename'];
                }
                
                return $result;
            }else{
                return $table_files->get();
            }
        }

        /**
         * [This method is used for file deletion] 
         * @param [type]$where [Used for where clause]
         * @return Data Response
         */ 

        public static function delete_file($where = ""){
            $table_files = DB::table('files');

            if(!empty($where)){
                $table_files->whereRaw($where);
            }

            return $table_files->delete();
        }

        /**
         * [This method is used to handle login] 
         * @param [Varchar]$social [Used for social]
         * @return Data Response
         */ 

        public static function __dologin($social){
            \Session::put('social',$social);
            $status = false; $message = ""; $redirect = "";

            $field          = ['id_user','type','first_name','last_name','name','email','status'];
            $email          = (!empty($social['social_email']))?$social['social_email']:"";

            if(!empty($social['social_id']) && !empty($social['social_key'])){
                $social_id      = (string) trim($social['social_id']);
                $social_key     = (string) trim($social['social_key']);
            }

            if(!empty($social_key) && !empty($social_id) && !empty($email)){
                $result         = (array) \Models\Talents::findByEmail(trim($email),$field);
            }

            if(empty($result) && !empty($social_key) && !empty($social_id)){
                $result         = (array) \Models\Talents::findBySocialId($social_key,$social_id,$field);
            }
            
            if(empty($result)){
                $request = [
                    'first_name'    => $social['social_first_name'],
                    'last_name'     => $social['social_last_name'],
                    'email'         => $social['social_email'],
                    'social_id'     => $social['social_id'],
                    'social_key'    => $social['social_key'],
                ];

                if(!empty($social['social_key'])){
                    $validator = self::validate_social_signup($request);
                }else{
                    $validator = self::validate_normal_signup($request);
                }

                if(empty($validator->errors()->all())){
                    if(!empty($email)){
                        $result = (array) \Models\Talents::findByEmail($email,$field);
                    }

                    if(!empty($result['email']) && !empty($email) && ($result['email'] != $email)){
                        $message = 'M0039';
                    }else if(!empty($result) && !empty($request['mobile']) && $result['mobile'] != $request['mobile']){
                        $message = 'M0039';
                    }else if(!empty($result['mobile']) && !empty($social_id)){
                        if($result['status'] == 'inactive'){
                            $message = 'M0002';
                        }elseif($result['status'] == 'suspended'){
                            $message = "M0003";
                        }else{
                            $updated_data = array(
                                $social_key     => $social_id,
                                'status'        => 'active'
                            );

                            if(empty($result['email'])){
                                $updated_data['email'] = $email;
                            }

                            \Models\Talents::change($result['id_user'],$updated_data);
                            \Auth::loginUsingId($result['id_user']);
                            $redirect = sprintf('/%s/profile/step/one',TALENT_ROLE_TYPE);
                            $message = 'M0005';
                            $status = true;
                        }
                    }else{
                        $dosignup = \Models\Talents::__dosignup((object)$request);
                        
                        if(!empty($dosignup['status'])){
                            $talent = \Models\Talents::findById($dosignup['signup_user_id'],$field);
                            if(!empty($talent) && $talent->status == 'pending'){
                                
                                if(!empty($email)){
                                    $code                   = bcrypt(__random_string());
                                    $emailData              = ___email_settings();
                                    $emailData['email']     = $email;
                                    $emailData['name']      = $request['first_name'];
                                    $emailData['link']      = url(sprintf("activate/account?token=%s",$code));
                                    
                                    \Models\Talents::change($talent->id_user,['remember_token' => $code,'updated' => date('Y-m-d H:i:s')]);

                                    ___mail_sender($email,sprintf("%s %s",$request['first_name'],$request['last_name']),"talent_signup_verification",$emailData);
                                }
                                $message    = $dosignup['message'];
                            }else{
                                if(!empty($email)){
                                    $emailData              = ___email_settings();
                                    $emailData['email']     = $email;
                                    $emailData['name']      = $request['first_name'];

                                    ___mail_sender($email,sprintf("%s %s",$request['first_name'],$request['last_name']),"talent_signup",$emailData);
                                }

                                \Auth::loginUsingId($dosignup['signup_user_id']);
                                $redirect = sprintf('/%s/profile/step/one',TALENT_ROLE_TYPE);
                                $message = 'M0005';
                                $status = true;
                            }
                        }else{
                            $message = $dosignup['message'];
                        }
                    }
                }else if($message == 'M0039'){
                    
                }
            }else if($result['type'] == TALENT_ROLE_TYPE){
                if($result['status'] == 'inactive'){
                    $message = trans(sprintf('general.%s',"M0002"));
                }elseif($result['status'] == 'suspended'){
                    $message = trans(sprintf('general.%s',"M0003"));
                }else{
                    $updated_data = array(
                        $social_key     => $social_id,
                        'status'        => 'active'
                    );

                    if(empty($result['email'])){
                        $updated_data['email'] = $email;
                    }

                    \Models\Talents::change($result['id_user'],$updated_data);
                    \Auth::loginUsingId($result['id_user']);
                    $message = sprintf(ALERT_SUCCESS,trans(sprintf('general.%s',"M0005")));
                    $redirect = sprintf('/%s/profile/step/one',TALENT_ROLE_TYPE);
                    $status = true;
                }
            }else{
                \Session::forget('social');
                $message = sprintf(ALERT_DANGER,trans(sprintf('general.%s',"M0108")));
            }

            return [
                'status' => $status,
                'message' => $message,
                'redirect' => $redirect,
                'validator' => !(empty($validator))?$validator:'',
            ];
        }

        /**
         * [This method is used to validate social signup] 
         * @param Request
         * @return Data Response
         */ 

        public static function validate_social_signup($request){
            $message = false;

            $validate = \Validator::make($request, [
                'first_name'        => validation('first_name'),
                'last_name'         => validation('last_name'),
                'email'             => ['email',\Illuminate\Validation\Rule::unique('users')->ignore('trashed','status')],
            ],[
                'first_name.required'       => trans('general.M0006'),
                'first_name.regex'          => trans('general.M0007'),
                'first_name.string'         => trans('general.M0007'),
                'first_name.max'            => trans('general.M0020'),
                'last_name.required'        => trans('general.M0008'),
                'last_name.regex'           => trans('general.M0009'),
                'last_name.string'          => trans('general.M0009'),
                'last_name.max'             => trans('general.M0019'),
                'email.required'            => trans('general.M0010'),
                'email.email'               => trans('general.M0011'),
                'email.unique'              => trans('general.M0012'),
            ]);

            if($validate->passes()){
                
            }

            return $validate;
        }

        /**
         * [This method is used to validate normal signup] 
         * @param Request
         * @return Data Response
         */ 

        public function validate_normal_signup($request){
            $message = false;

            $validate = \Validator::make($request, [
                'first_name'        => validation('first_name'),
                'last_name'         => validation('last_name'),
                'email'             => ['required','email',Rule::unique('users')->ignore('trashed','status')],
                'password'          => validation('password'),
                'agree'             => validation('agree'),
            ],[
                'first_name.required'       => trans('general.M0006'),
                'first_name.regex'          => trans('general.M0007'),
                'first_name.string'         => trans('general.M0007'),
                'first_name.max'            => trans('general.M0020'),
                'last_name.required'        => trans('general.M0008'),
                'last_name.regex'           => trans('general.M0009'),
                'last_name.string'          => trans('general.M0009'),
                'last_name.max'             => trans('general.M0019'),
                'email.required'            => trans('general.M0010'),
                'email.email'               => trans('general.M0011'),
                'email.unique'              => trans('general.M0012'),
                'password.required'         => trans('general.M0013'),
                'password.regex'            => trans('general.M0014'),
                'password.string'           => trans('general.M0013'),
                'password.min'              => trans('general.M0014'),
                'password.max'              => trans('general.M0018'),
                'agree.required'            => trans('general.M0017'),
            ]);

            if($validate->fails()){
                $message = $validate->messages()->first();
            }

            return $message;
        }


        /**
         * [This method is used to handle signup] 
         * @param [Varchar]$data[Used for varchar]
         * @return Data Response
         */  

        public static function __dosignupnone($data){
            $status         = 'pending';
            $token          = bcrypt(__random_string());

            if(!empty($data->social_key)){
                // dd($data->response['positions']['values'][0]['company']['name'],$data->response['pictureUrls']['values'][0],'z00',$data->response);
                $insert_data = [
                    'type'                          => NONE_ROLE_TYPE,
                    'company_profile'               => (!empty($data->work_type))?$data->work_type:'individual',
                    'company_name'                  => ($data->work_type == 'company')?$data->company_name:'',
                    'name'                          => (string)sprintf("%s %s",$data->first_name,$data->last_name),
                    'first_name'                    => (string)ucwords($data->first_name),
                    'last_name'                     => (string)ucwords($data->last_name),
                    'email'                         => (string)(!empty($data->email))?$data->email:"",
                    'picture'                       => (string)(!empty($data->picture))?$data->picture:DEFAULT_AVATAR_IMAGE,
                    'password'                      => bcrypt(__random_string()),
                    'coupon_id'                     => (!empty($data->coupon_id))?$data->coupon_id:0,
                    'status'                        => 'active',
                    'api_token'                     => $token,
                    'agree'                         => 'no',
                    'newsletter_subscribed'         => (!empty($data->newsletter))?'yes':'no',
                    'remember_token'                => __random_string(),
                    'percentage_default'            => TALENT_DEFAULT_PROFILE_PERCENTAGE,
                    'registration_device'           => !empty($data->device_type) ? $data->device_type : 'website',
                    'last_login'                    => date('Y-m-d H:i:s'),
                    'updated'                       => date('Y-m-d H:i:s'),
                    'created'                       => date('Y-m-d H:i:s'),
                    $data->social_key               => $data->social_id,
                ];
            }else{
                $insert_data = [
                    'type'                          => NONE_ROLE_TYPE,
                    'company_profile'               => (!empty($data->work_type))?$data->work_type:'individual',
                    'company_name'                  => ($data->work_type == 'company')?$data->company_name:'',
                    'name'                          => (string)sprintf("%s %s",$data->first_name,$data->last_name),
                    'first_name'                    => (string)ucwords($data->first_name),
                    'last_name'                     => (string)ucwords($data->last_name),
                    'email'                         => (string)$data->email,
                    'picture'                       => (string)(!empty($data->social_picture))?$data->social_picture:DEFAULT_AVATAR_IMAGE,
                    'password'                      => bcrypt($data->password),
                    'coupon_id'                     => (!empty($data->coupon_id))?$data->coupon_id:0,
                    'status'                        => $status,
                    'api_token'                     => $token,
                    'agree'                         => 'no',
                    'newsletter_subscribed'         => (!empty($data->newsletter))?'yes':'no',
                    'remember_token'                => __random_string(),
                    'percentage_default'            => TALENT_DEFAULT_PROFILE_PERCENTAGE,
                    'registration_device'           => !empty($data->device_type) ? $data->device_type : 'website',
                    'last_login'                    => date('Y-m-d H:i:s'),
                    'updated'                       => date('Y-m-d H:i:s'),
                    'created'                       => date('Y-m-d H:i:s'),
                ];
            }

            if(!empty($data->social_key)){
                $insert_data[$data->social_key] = $data->social_id;
                $insert_data['social_account']  = DEFAULT_YES_VALUE;
                $insert_data['social_picture']  = $data->picture;
            }

            $isInserted = self::add_new($insert_data);

            if(isset($insert_data['social_picture']) && $insert_data['social_picture'] != ''){
                \Models\File::insert([
                    'user_id'   => $isInserted,
                    'reference' => 'user',
                    'filename'  => $insert_data['social_picture'],
                    'extension' => '',
                    'type'      => 'profile'
                ]);
                
            }

            if(!empty($isInserted)){ 
                return [
                    'status' => true,
                    'message' => 'M0021',
                    'signup_user_id' => $isInserted,
                ];
            }else{
                return [
                    'status' => false,
                    'message' => 'M0022',
                    'signup_user_id' => false,
                ];
            }
        }

        /**
         * [This method is used to handle signup] 
         * @param [Varchar]$data[Used for varchar]
         * @return Data Response
         */  

        public static function __dosignup($data){
            $status         = 'pending';
            $token          = bcrypt(__random_string());
            if(!empty($data->social_key)){
                $insert_data = [
                    'type'                          => TALENT_ROLE_TYPE,
                    'company_profile'               => (!empty($data->work_type))?$data->work_type:'individual',
                    'company_name'                  => ($data->work_type == 'company')?$data->company_name:'',
                    'name'                          => (string)sprintf("%s %s",$data->first_name,$data->last_name),
                    'first_name'                    => (string)ucwords($data->first_name),
                    'last_name'                     => (string)ucwords($data->last_name),
                    'email'                         => (string)(!empty($data->email))?$data->email:"",
                    'picture'                       => (string)(!empty($data->social_picture))?$data->social_picture:DEFAULT_AVATAR_IMAGE,
                    'password'                      => bcrypt(__random_string()),
                    'coupon_id'                     => (!empty($data->coupon_id))?$data->coupon_id:0,
                    'status'                        => 'active',
                    'api_token'                     => $token,
                    'agree'                         => 'no',
                    'newsletter_subscribed'         => (!empty($data->newsletter))?'yes':'no',
                    'remember_token'                => __random_string(),
                    'percentage_default'            => TALENT_DEFAULT_PROFILE_PERCENTAGE,
                    'registration_device'           => !empty($data->device_type) ? $data->device_type : 'website',
                    'last_login'                    => date('Y-m-d H:i:s'),
                    'updated'                       => date('Y-m-d H:i:s'),
                    'created'                       => date('Y-m-d H:i:s'),
                    $data->social_key               => $data->social_id,
                ];
            }else{
                $insert_data = [
                    'type'                          => TALENT_ROLE_TYPE,
                    'company_profile'               => (!empty($data->work_type))?$data->work_type:'individual',
                    'company_name'                  => ($data->work_type == 'company')?$data->company_name:'',
                    'name'                          => (string)sprintf("%s %s",$data->first_name,$data->last_name),
                    'first_name'                    => (string)ucwords($data->first_name),
                    'last_name'                     => (string)ucwords($data->last_name),
                    'email'                         => (string)$data->email,
                    'picture'                       => (string)(!empty($data->social_picture))?$data->social_picture:DEFAULT_AVATAR_IMAGE,
                    'password'                      => bcrypt($data->password),
                    'coupon_id'                     => (!empty($data->coupon_id))?$data->coupon_id:0,
                    'status'                        => $status,
                    'api_token'                     => $token,
                    'agree'                         => 'no',
                    'newsletter_subscribed'         => (!empty($data->newsletter))?'yes':'no',
                    'remember_token'                => __random_string(),
                    'percentage_default'            => TALENT_DEFAULT_PROFILE_PERCENTAGE,
                    'registration_device'           => !empty($data->device_type) ? $data->device_type : 'website',
                    'last_login'                    => date('Y-m-d H:i:s'),
                    'updated'                       => date('Y-m-d H:i:s'),
                    'created'                       => date('Y-m-d H:i:s'),
                ];
            }

            if(!empty($data->social_key)){
                $insert_data[$data->social_key] = $data->social_id;
                $insert_data['social_account']  = DEFAULT_YES_VALUE;
                $insert_data['social_picture']  = $data->picture;
            }else{
                $insert_data['social_picture']  = '';
            }
            $isInserted = self::add_new($insert_data);

            if($insert_data['social_picture'] != ''){
                \Models\File::insert([
                    'user_id'   => $isInserted,
                    'reference' => 'user',
                    'filename'  => $insert_data['social_picture'],
                    'extension' => '',
                    'type'      => 'profile'
                ]);
                
            }

            if(!empty($isInserted)){ 
                return [
                    'status' => true,
                    'message' => 'M0021',
                    'signup_user_id' => $isInserted,
                ];
            }else{
                return [
                    'status' => false,
                    'message' => 'M0022',
                    'signup_user_id' => false,
                ];
            }
        }

        /**
         * [This method is used to handle signup] 
         * @param [type]$data[<description>]
         * @return Data Response
         */ 

        public static function ___dosignup($data){
            $status         = 'pending';
            $confirm_code   = md5(__random_string());

            if(!empty($data->email)){
                $talent = self::row($data->email);

                if(!empty($talent)){
                    if(in_array($talent->status, ['active','pending']) && !empty($data->social_id)){
                        $isUpdatedSocialConnection = self::update_social_connection($data->social_key,$data->social_id,$data->email);

                        \Auth::loginUsingId($talent->id_user);
                        \Session::forget('social');

                        return [
                            'status' => true,
                            'message' => sprintf(ALERT_SUCCESS,trans(sprintf('general.successfully_loggedin'))),
                        ];
                    }else if(in_array($talent->status, ['active','pending']) && empty($data->social_id)){
                        return [
                            'status' => false,
                            'message' => sprintf(ALERT_WARNING,trans(sprintf('general.email_already_exists'))),
                        ];
                    }else if($talent->status === 'inactive'){
                        return [
                            'status' => false,
                            'message' => sprintf(ALERT_WARNING,trans(sprintf('general.account_inactive'))),
                        ];
                    }else if($talent->status === 'suspended'){
                        return [
                            'status' => false,
                            'message' => sprintf(ALERT_WARNING,trans(sprintf('general.account_suspended'))),
                        ];
                    }
                }
            }


            if(!empty($data->social_id)){
                $status = 'active';
            }

            $insert_data = [
                'type'                  => TALENT_ROLE_TYPE,
                'name'                  => (string)(!empty($data->name))?$data->name:trim(sprintf("%s %s",(string)$data->first_name,(string)$data->last_name)),
                'first_name'            => (string)$data->first_name,
                'last_name'             => (string)$data->last_name,
                'email'                 => (string)$data->email,
                'gender'                => (string)'',
                'picture'               => (string)'avatar.png',
                'password'              => (!empty($data->password))?$data->password:$confirm_code,
                'status'                => $status,
                'last_login'            => date('Y-m-d H:i:s'),
                'updated'               => date('Y-m-d H:i:s'),
                'created'               => date('Y-m-d H:i:s'),
                'newsletter_subscribed' => ($data->newsletter)?'yes':'no',
            ];

            if(!empty($data->social_key)){
                $insert_data[$data->social_key] = $data->social_id;
            }

            $isInserted = self::add_new($insert_data);
            
            if(!empty($isInserted)){
                if($status === 'active'){
                    $talent = self::findById($isInserted);

                    $session = [
                        'login_id' => $talent->id_user,
                        'name' => $talent->name,
                        'email' => $talent->email,
                        'type' => $talent->type,
                    ];
                    
                    \Session::put('front_login', $session);
                    \Session::forget('social', $session);
                    return [
                        'status' => true,
                        'message' => sprintf(ALERT_SUCCESS,trans(sprintf('general.successfully_loggedin'))),
                    ];
                }else{
                    return [
                        'status' => false,
                        'message' => sprintf(ALERT_SUCCESS,trans(sprintf('general.successfully_created_account'))),
                    ];
                }
            }else{
                return [
                    'status' => false,
                    'message' => sprintf(ALERT_DANGER,trans(sprintf('general.something_wrong')))
                ];
            }
        }

        /**
         * [This method is used for step keys] 
         * @param [type]$step[<description>]
         * @return Data Response
         */ 

        public static function step_keys($step){
            switch ($step) {
                case 'one':{   
                    return [
                        
                    ];
                    break;
                }
                case 'two':{   
                    return [
                        'industry',
                        'subindustry',
                        'skills',
                        'expertise',
                        'experience',
                        'workrate',
                        'workrate_information',
                        'agree_pricing',
                        'certificates',
                    ];
                    break;
                }
                case 'three':{   
                    return [
                        'educations',
                        'work_experiences',
                        'cover_letter_description',
                    ];
                    break;
                }
            }
        }

        /**
         * [This method is used for getting availability] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$availability_group [Used for availability group]
         * @param [Varchar]$keys[Used for keys]
         * @param [Enum]$option[Used for option]
         * @return Data Response
         */ 

        public static function get_availability($user_id, $availability_group = NULL, $keys = NULL, $option = 'other', $date = NULL){
            $table_talent_availability = DB::table('talent_availability');

            if(empty($keys)){
                $table_talent_availability->select([
                    'id_availability',
                    DB::Raw('repeat_group as id_availability'),
                    'availability_date',
                    #'availability_day',
                    #DB::Raw('IFNULL(GROUP_CONCAT( DISTINCT availability_day ORDER BY availability_day ), "") as availability_day'),
                    'from_time',
                    'to_time',
                    'repeat',
                    'repeat_group',
                    'deadline',
                    'availability_type'
                ]);
            }

            if($option == 'other'){
                $table_talent_availability->addSelect(['availability_day']);
            }
            elseif($option == 'self'){
                $table_talent_availability->addSelect([
                    DB::Raw('IFNULL(GROUP_CONCAT( DISTINCT availability_day ORDER BY availability_day ), "") as availability_day')
                ]);
            }

            $table_talent_availability->where('deadline', '>=',date('Y-m-d'));
            

            if(!empty($availability_group)){
                $table_talent_availability->where('repeat_group',$availability_group);
            }else{
                if(empty($date)){
                    $date = date('Y-m-d');
                }else{
                    $date = date('Y-m-d',strtotime(str_replace("/", "-", $date)));
                }
                $previous_calendar_date = date('Y-m-d', strtotime("-7 Days", strtotime(date('Y-m-01',strtotime($date)))));
                $next_calendar_date     = date('Y-m-d', strtotime("+7 Days", strtotime(date('Y-m-t',strtotime($date)))));
                
                $table_talent_availability->where('availability_date', '>=', $previous_calendar_date);
                $table_talent_availability->where('availability_date', '<=', $next_calendar_date);
            }
            
            if($option == 'other'){
                $availability = json_decode(json_encode($table_talent_availability->where(
                    array(
                        'user_id' => $user_id,
                    )
                )->get()),true);
            }
            elseif($option == 'self'){
                $availability = json_decode(json_encode($table_talent_availability->where(
                    array(
                        'user_id' => $user_id,
                    )
                )->groupBy('repeat_group')->get()),true);
            }
            array_walk($availability, function(&$item){
                if($item['repeat'] == 'weekly'){
                    $item['availability_day'] = explode(",",str_replace(array_values(days()),array_keys(days()),$item['availability_day']));
                }else{
                    $item['availability_day'] = [];
                }
            });
            
            return $availability;
        }

        /**
         * [This method is used to handle get availablity] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Integer]$availability_id[Used for availability id]
         * @param [Enum]$type[Used for type]
         * @return Data Response
         */ 

        public static function __get_availability($user_id, $availability_id = NULL, $type = 'listing'){
            $table_talent_availability = DB::table('talent_availability');

            if($type == 'listing'){
                $table_talent_availability->select([
                    'id_availability',
                    'availability_date',
                    DB::Raw('IFNULL(GROUP_CONCAT(availability_day), "") as availability_day'),
                    'from_time',
                    'to_time',
                    'repeat',
                    'deadline'
                ]);
            }

            if(!empty($availability_id)){
                $table_talent_availability->where('id_availability',$availability_id);
            }

            $availability = json_decode(json_encode($table_talent_availability->where(
                array(
                    'user_id' => $user_id,
                )
            )->groupBy('repeat')->get()),true);

            if($type == 'single'){
                array_walk($availability, function(&$item){
                    if($item['repeat'] == 'weekly'){
                        $item['availability_day'] = sprintf("WEEKLY ON %s",___replace_last(',',' AND ',str_replace(array_values(days()),array_keys(days()),strtoupper($item['availability_day']))));
                        $item['deadline'] = sprintf("until %s",___d($item['deadline']));
                    }else if($item['repeat']){
                        $item['availability_day'] = 'MONTHLY';
                        $item['deadline'] = sprintf("until %s",___d($item['deadline']));
                    }else{
                        $item['availability_day'] = 'DAILY';
                        $item['deadline'] = sprintf("until %s",___d($item['deadline']));
                    }
                }); 
            }else{
                array_walk($availability, function(&$item){
                    if($item['repeat'] == 'weekly'){
                        $item['availability_day'] = explode(",",str_replace(array_values(days()),array_keys(days()),$item['availability_day']));
                    }else{
                        $item['availability_day'] = [];
                    }
                });
            }
            
            return $availability;
        }

        /**
         * [This method is used to set availablity] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$availabilit[Used for availability]
         * @param [Enum]$type[Used for type]
         * @return Data Response
         */ 

        public static function set_availability($user_id,$availability,$type = 'listing'){
            $result = [];
            $table_talent_availability = DB::table('talent_availability');
            $max_repeat_group = (int)$table_talent_availability->max('repeat_group')+1;

            if((count($availability) != count($availability, COUNT_RECURSIVE))){
                $availability = \App\Lib\Dash::insert($availability,'{n}.repeat_group',$max_repeat_group);
                
                $isInserted = $table_talent_availability->insert($availability);

                if(!empty($isInserted)){
                    $result = self::get_availability($user_id,$max_repeat_group);
                }
            }else{
                $availability['repeat_group'] = $max_repeat_group;
                $isInserted = $table_talent_availability->insertGetId($availability);
                
                if(!empty($isInserted)){
                    $result = self::get_availability($user_id,$max_repeat_group);
                }
            }

            return $result;
        }

        /**
         * [This method is used to update availability] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Integer]$availability_id[Used for availability id]
         * @param [Varchar]$availabilit[Used for availability]
         * @return Data Response
         */ 

        public static function update_availability($user_id,$availability_id,$availability){
            $table_talent_availability = DB::table('talent_availability');
            
            $table_talent_availability->where([
                'repeat_group' => $availability_id,
                'user_id' => $user_id
            ]);

            $table_talent_availability->delete();
            
            return self::set_availability($user_id,$availability);
        }

        /**
         * [This method is used for availability deletion] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Integer]$availability_id[Used for availability id]
         * @return Data Response
         */  

        public static function delete_availability($user_id,$availability_id){
            $table_talent_availability = DB::table('talent_availability');
            
            /*$table_talent_availability->where([
                'repeat_group' => $availability_id,
                'availability_date', '>=', date('Y-m-d'),
                'user_id' => $user_id
            ]);*/
            $table_talent_availability->whereRaw("
                repeat_group = ".$availability_id."
                AND availability_date >= '".date('Y-m-d')."'
                AND user_id = ".$user_id."
            ");
            
            return $table_talent_availability->delete();
        } 

        /**
         * [This method is used to handle availability check] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Datetime]$availability_date[Used for availability date]
         * @param [Datetime]$from_time[Used for from_time]
         * @param [Datetime]$to_time[Used for to_time]
         * @param [Varchar]$deadline[Used for deadline]
         * @param [Varchar]$availability_day[Used for availability day]
         * @param [Varchar]$repeat[Used for]
         * @param [Integer]$availability_id[Used for availability id]
         * @return Data Response
         */ 

        public static function __check_availability($user_id,$availability_date,$from_time,$to_time,$deadline,$availability_day,$repeat,$availability_id = NULL){
            $table_talent_availability = DB::table('talent_availability');
            
            if(!empty($availability_day)){
                $availability_day = '"'.implode('","',$availability_day).'"';
            }else{
                $availability_day = '""';
            }

            $table_talent_availability->whereRaw("
                user_id = {$user_id} 
                AND IF(
                    (`repeat` = 'daily' && ( ('{$availability_date}' >= availability_date OR '{$availability_date}' >= deadline) OR ('{$deadline}' <= availability_date OR '{$deadline}' <= deadline))),
                    IF( 
                        (from_time = '{$from_time}'),
                        true,
                        IF(
                            (to_time > '{$from_time}' && to_time < '{$to_time}'),
                            true,
                            IF(
                                (from_time > '{$from_time}' && from_time < '{$to_time}'),
                                true,
                                IF(
                                    (from_time < '{$from_time}' && to_time > '{$to_time}'),
                                    true,
                                    false
                                )
                            )
                        )
                    ),
                    IF( 
                        (`repeat` = 'weekly' && (availability_date <= '{$availability_date}' || deadline >= '{$deadline}') &&  availability_day IN ($availability_day)),
                        IF( 
                            (from_time = '{$from_time}'),
                            true,
                            IF(
                                (to_time > '{$from_time}' && to_time < '{$to_time}'),
                                true,
                                IF(
                                    (from_time > '{$from_time}' && from_time < '{$to_time}'),
                                    true,
                                    IF(
                                        (from_time < '{$from_time}' && to_time > '{$to_time}'),
                                        true,
                                        false
                                    )
                                )
                            )
                        ),
                        IF( 
                            (`repeat` = 'monthly' &&  DATE(availability_date) = DATE('{$availability_date}') && MONTH(deadline) > MONTH('{$deadline}') && YEAR(deadline) >= YEAR('{$deadline}')),
                            IF( 
                                (from_time = '{$from_time}'),
                                true,
                                IF(
                                    (to_time > '{$from_time}' && to_time < '{$to_time}'),
                                    true,
                                    IF(
                                        (from_time > '{$from_time}' && from_time < '{$to_time}'),
                                        true,
                                        IF(
                                            (from_time < '{$from_time}' && to_time > '{$to_time}'),
                                            true,
                                            false
                                        )
                                    )
                                )
                            ),
                            false
                        ) 
                    ) 
                )  
            ");

            $availability = json_decode(json_encode($table_talent_availability->get()->first()),true);

            if(!empty($availability)){
                if($availability['repeat_group'] != $availability_id){
                    return false;
                }else{
                    DB::table('talent_availability')->where('repeat_group',$availability_id)->delete();
                    return true;
                }
            }else{
                return true;
            }
        } 

        /**
         * [This method is used for remove] 
         * @param [Varchar]$user[Used for user]
         * @param [type]$where[Used for where clause]
         * @param [Fetch]$fetch[Used for fetching]
         * @param [Varchar]$keys[Used for key]
         * @param [Integer]$page[Used for paging]
         * @param [Integer]$limit[Used for limit]
         * @return Data Response
         */ 

        public static function get_job($user,$where = "",$fetch = 'all',$keys = NULL,$page = 0, $limit = DEFAULT_PAGING_LIMIT){
            $table_projects = DB::table('projects as projects');
            $prefix         = DB::getTablePrefix();
            $language       = \App::getLocale();

            /*Check for converted description exist or not*/
            $translator = new \Dedicated\GoogleTranslate\Translator;
            $project_language = DB::table('project_language')
            ->where('project_id', request()->project_id)
            ->where('language', $language)
            ->count();
            if($project_language <= 0){
                $project_language = DB::table('project_language')
                ->where('project_id', request()->project_id)
                ->where('language', ___cache('default_language'))
                ->get()
                ->first();

                if(!empty($project_language)){
                    if(___configuration(['google_translate_enabled'])['google_translate_enabled'] == 'Y'){
                        try{
                            $convertLang = $translator->setTargetLang(request()->language)->translate($project_language->description);
                        }
                        catch(\Exception $e){
                            $convertLang = false;
                        }

                        if($convertLang){
                            $projectLang = [
                                'project_id' => request()->project_id,
                                'language' => $language,
                                'description' => $convertLang,
                                'created' => date('Y-m-d H:i:s'),
                                'updated' => date('Y-m-d H:i:s')
                            ];
                            DB::table('project_language')
                            ->insert($projectLang);
                        }
                    }else{
                        $projectLang = [
                            'project_id' => request()->project_id,
                            'language' => $language,
                            'description' => $project_language->description,
                            'created' => date('Y-m-d H:i:s'),
                            'updated' => date('Y-m-d H:i:s')
                        ];
                        DB::table('project_language')
                        ->insert($projectLang);
                    }
                }
            }

            $job = DB::table('projects as projects')->select(['employment'])->whereRaw($where)->get()->first();

            $prefix = DB::getTablePrefix();
            $offset = 0;

            if(!empty($page)){
                $offset = ($page - 1)*$limit;
            }

            if($fetch != 'rows' && !empty($limit)){
                $table_projects->offset($offset);
                $table_projects->limit($limit);
            }

            if(empty($keys)){
                $keys = [
                    'projects.id_project',
                    'projects.user_id as company_id',
                    'projects.title',
                    'project_language.description',
                    \DB::Raw("TRIM(CONCAT({$prefix}users.first_name,' ',{$prefix}users.last_name)) as company_person_name"),
                    'users.company_name',
                    'projects.industry',
                    'projects.location',
                    'projects.created',
                    'projects.other_perks',
                    \DB::Raw("IF(({$prefix}industry.{$language} != ''),{$prefix}industry.`{$language}`, {$prefix}industry.`en`) as industry_name"),
                    #'projects.price',
                    #'projects.price_max',
                    #'projects.bonus',
                    \DB::Raw('`CONVERT_PRICE`('.$prefix.'projects.price, '.$prefix.'projects.price_unit, "'.request()->currency.'") AS price'),
                    \DB::Raw('`CONVERT_PRICE`('.$prefix.'projects.price_max, '.$prefix.'projects.price_unit, "'.request()->currency.'") AS price_max'),
                    \DB::Raw('`CONVERT_PRICE`('.$prefix.'projects.bonus, '.$prefix.'projects.price_unit, "'.request()->currency.'") AS bonus'),

                    'projects.budget_type',
                    'projects.price_type',
                    'projects.price_unit',
                    'projects.employment',
                    'projects.expertise',
                    \DB::Raw("
                        IFNULL(
                            IF(
                                ({$prefix}city.`{$language}` != ''),
                                {$prefix}city.`{$language}`,
                                {$prefix}city.`en`
                            ),
                            ''
                        ) as location_name"
                    ),
                    'projects.project_status',
                    'projects.created',
                    \DB::Raw("GROUP_CONCAT({$prefix}qualifications.qualification) as required_qualifications"),
                    \DB::Raw("DATE({$prefix}projects.startdate) as startdate"),
                    \DB::Raw("DATE({$prefix}projects.enddate) as enddate"),
                    \DB::Raw("IF({$prefix}saved_jobs.id_saved IS NOT NULL,'".DEFAULT_YES_VALUE."','".DEFAULT_NO_VALUE."') as is_saved"),
                    \DB::Raw("GROUP_CONCAT({$prefix}skills.skill) as skills"),
                    \DB::Raw("{$prefix}proposals.user_id as accepted_talent_id"),
                    'chat_requests.chat_initiated',
                    'chat_requests.request_status as chat_request_status',
                    "chat_requests.request_status"

                ];
            }

            $table_projects->select($keys);
            $table_projects->leftJoin('project_language', function ($join) {
                $join->on('projects.id_project', '=', 'project_language.project_id')
                    ->where('project_language.language', request()->language);
            });
            $table_projects->leftJoin('users as users','users.id_user','=','projects.user_id');
            $table_projects->leftJoin('files as files','files.user_id','=','projects.user_id');
            $table_projects->leftJoin('industries as industry','industry.id_industry','=','projects.industry');
            $table_projects->leftJoin('industries as sub_industry','sub_industry.id_industry','=','projects.subindustry');
            $table_projects->leftJoin('project_required_skills as skills','skills.project_id','=','projects.id_project');
            $table_projects->leftJoin('project_required_qualifications as qualifications','qualifications.project_id','=','projects.id_project');            
            $table_projects->leftJoin('city as city','city.id_city','=','projects.location');
            $table_projects->leftJoin('chat_requests as chat_requests',function($leftjoin) use($user){
                $leftjoin->on('chat_requests.sender_id','=',\DB::Raw($user->id_user));
                $leftjoin->on('chat_requests.receiver_id','=','projects.user_id');
            });
            $table_projects->leftJoin('talent_proposals as proposals',function($leftjoin) use($user){
                $leftjoin->on('proposals.project_id','=','projects.id_project'); 
                $leftjoin->on('proposals.user_id','=',\DB::Raw($user->id_user)); 
            });
            
            $table_projects->leftJoin('saved_jobs as saved_jobs',function($leftjoin) use($user){
                $leftjoin->on('saved_jobs.job_id','=','projects.id_project');
                $leftjoin->where('saved_jobs.user_id','=',$user->id_user);
            });
            
            $table_projects->leftJoin('project_log',function($leftjoin) use($user,$prefix,$job){
                if($job->employment == 'daily' || $job->employment == 'hourly'){
                    $leftjoin->on('project_log.project_id','=','projects.id_project');
                    $leftjoin->on(\DB::Raw("DATE({$prefix}project_log.created)"),'=',\DB::Raw("'".date('Y-m-d')."'"));
                }else if($job->employment == 'weekly'){
                    $leftjoin->on('project_log.project_id','=','projects.id_project');
                    $leftjoin->on(\DB::Raw("WEEK({$prefix}project_log.created)"),'=',\DB::Raw("'".date('W')."'"));
                }else if($job->employment == 'monthly'){
                    $leftjoin->on('project_log.project_id','=','projects.id_project');
                    $leftjoin->on(\DB::Raw("MONTH({$prefix}project_log.created)"),'=',\DB::Raw("'".date('n')."'"));
                }else if($job->employment == 'fixed'){
                    $leftjoin->on('project_log.project_id','=','projects.id_project');
                }else{
                    $leftjoin->on('project_log.project_id','=','projects.id_project');
                }
            });


            if(!empty($where)){
                $table_projects->whereRaw($where);
            }

            if($fetch == 'count'){
                return $table_projects->get()->count();
            }else if($fetch == 'single'){
                $job_details = (array) $table_projects->get()->first();
                $company_logo = \Models\Talents::get_file(sprintf(" type = 'profile' AND user_id = %s",$job_details['company_id']),'single',['filename','folder']);
                $job_details['expertise'] = ucfirst($job_details['expertise']);
                if($job_details['employment'] !== 'fulltime'){
                    $job_details['timeline'] = ___date_difference($job_details['startdate'],$job_details['enddate']);
                    $job_details['price_type'] = job_types($job_details['price_type']);
                }else{
                    $job_details['price_type'] = trans('website.W0039');
                    $job_details['timeline'] = trans('website.W0039');
                }

                if(!empty($company_logo)){
                    $job_details['company_logo'] = get_file_url($company_logo);
                }else{
                    $job_details['company_logo'] = asset(sprintf('images/%s',DEFAULT_AVATAR_IMAGE));
                }
                
                $job_details['created'] = sprintf("%s %s",trans('general.M0177'),___ago($job_details['created']));

                $job_details['required_qualifications'] = array_unique(explode(',',$job_details['required_qualifications']));

                $job_details['skills'] = implode(',', array_unique(explode(',',$job_details['skills'])));

                if(!empty($job_details['required_qualifications'])){
                    array_walk($job_details['required_qualifications'], function(&$value){
                        if(!empty($value)){
                            $value = ___cache('degree_name',$value);
                        }
                    });
                }
                if(is_array($job_details['required_qualifications'])){
                    $job_details['required_qualifications'] = (string)implode(',',(array)$job_details['required_qualifications']);
                }else{
                    $job_details['required_qualifications'] = "";
                }

                $job_details['job_type'] = employment_types('post_job',$job_details['employment']);
                
                $job_details['price_unit'] = ___cache('currencies')[request()->currency];
                $job_details['price']      = ___format($job_details['price'],true,false);
                $job_details['price_max']  = ___formatblank($job_details['price_max'],true,false);

                return $job_details;
            }else if($fetch == 'all'){
                return json_decode(json_encode($table_projects->get()),true);
            }else if($fetch == 'rows'){
                $total = $table_projects->get()->count();

                $table_projects->offset($offset);
                $table_projects->limit($limit);

                $all_jobs  = json_decode(json_encode($table_projects->get()),true);

                return [
                    'total_result' => $total,
                    'total_filtered_result' => $table_projects->get()->count(),
                    'result' => $all_jobs,
                ];
            }else{
                return $table_projects->get();
            }
        }

        /**
         * [This method is used for getting user's] 
         * @param [Varchar]$user [Used for User]
         * @param [Varchar]$db_flag  [Used for db_flag]
         * @return Data Response
         */ 

        public static function get_user($user,$db_flag = true){
            $prefix = DB::getTablePrefix();
            $language = \App::getLocale();

            $keys = array(
                'id_user',
                'type',
                'first_name',
                'last_name',
                'email',
                'expected_salary',
                'other_expectations',
                'agree',
                'agree_pricing',
                'birthday',
                'gender',
                'country_code',
                'mobile',
                'address',
                'country',
                'state',
                'city',
                'postal_code',
                'picture',
                'industry',
                'subindustry',
                'expertise',
                'experience',
                'workrate',
                'workrate_max',
                'workrate_unit',
                'workrate_information',
                'agree_pricing',
                'cover_letter_description',
                'facebook_id',
                'instagram_id',
                'twitter_id',
                'linkedin_id',
                'googleplus_id',
                'is_mobile_verified',
                'chat_status',
                'users.created',
                'is_interview_popup_appeared',
                'newsletter_subscribed',
                'currency',
                'social_account',
                'paypal_id',
                'last_login',
                'identification_no',
                'is_register',
                'company_profile',
                'company_name',
                'social_picture',
                'company_website',
                'company_work_field',
                'company_biography',
                'show_profile',
                'notice_expired',
                'is_notice_period'
            ); 

            if(empty($db_flag)){
                $data = array_intersect_key(
                    json_decode(json_encode($user),true), 
                    array_flip($keys)
                );
            }else{
                $keys = array_merge($keys,[
                    \DB::Raw("IF(({$prefix}country_code.{$language} != ''),{$prefix}country_code.`{$language}`, {$prefix}country_code.`en`) as country_code_name"),
                    \DB::Raw("IF(({$prefix}countries.{$language} != ''),{$prefix}countries.`{$language}`, {$prefix}countries.`en`) as country_name"),
                    \DB::Raw("IF(({$prefix}state.{$language} != ''),{$prefix}state.`{$language}`, {$prefix}state.`en`) as state_name"),
                    \DB::Raw("IF(({$prefix}city.{$language} != ''),{$prefix}city.`{$language}`, {$prefix}city.`en`) as city_name"),
                ]);
                
                $data = json_decode(json_encode(self::findById($user->id_user,$keys)),true);
            }

            if(!empty($data)){
                
                if(!empty($data['expected_salary'])){
                    $data['expected_salary'] = ___format($data['expected_salary'],false,false);
                }
                
                if(!empty($data['workrate'])){
                    $data['workrate'] = ___format($data['workrate'],false,false);
                }
                
                if(!empty($data['workrate_max'])){
                    $data['workrate_max'] = ___format($data['workrate_max'],false,false);
                }
                
                $data['first_name']                         = ucwords($data['first_name']);
                $data['last_name']                          = ucwords($data['last_name']);
                
                
                $data['picture'] = get_file_url(self::get_file(sprintf(" type = 'profile' AND user_id = %s",$user->id_user),'single',['filename','folder']));
                /*$profileUrl = self::get_file(sprintf(" type = 'profile' AND user_id = %s",$user->id_user),'single',['filename','folder']);
                
                if(empty($profileUrl) && empty($data['social_picture'])){
                    $data['picture']  = get_file_url($profileUrl);
                }elseif (!empty($profileUrl)) {
                    $data['picture']  = get_file_url($profileUrl);
                }elseif (!empty($data['social_picture'])) {
                    $data['picture'] = $data['social_picture'];
                }*/
                
                $data['interested']                         = \Models\Talents::interested_in($user->id_user);
                $data['remuneration']                       = \Models\Talents::remuneration($user->id_user);
                $data['certificates']                       = \Models\Talents::certificates($user->id_user);
                $data['skills']                             = \Models\Talents::skills($user->id_user);
                $data['subindustry']                        = \Models\Talents::subindustry($user->id_user);
                $data['industry']                           = \Models\Talents::industry($user->id_user);
                $data['educations']                         = \Models\Talents::educations($user->id_user);
                $data['work_experiences']                   = \Models\Talents::work_experiences($user->id_user);
                $data['certificate_attachments']            = \Models\Talents::get_file(sprintf(" user_id = %s AND type = 'certificates' ",$user->id_user),'all',['id_file','filename','folder','size','extension as type','extension']);
                $data['availability']                       = \Models\Talents::get_availability($user->id_user, NULL, NULL, 'self');
                $data['jobdetails']                         = \Models\Talents::jobdetails($user->id_user);
                $data['talentCompany']                      = \Models\Talents::getCompanyDetails($user->id_user);
                // $data['talentCompany']                      = \Models\Talents::with(['getCompany'])->where('id_user',$user->id_user)->get();
                
                if(!empty($data['birthday'])){
                    $birthday = explode('-', $data['birthday']);
                    $data['birthdate']                          = $birthday[2];
                    $data['birthmonth']                         = $birthday[1];
                    $data['birthyear']                          = $birthday[0];
                }else{
                    $data['birthdate'] = $data['birthmonth'] = $data['birthyear'] = "";
                }
                
                $data['notification_count']                 = \Models\Notifications::unread_notifications($data['id_user']);
                $data['proposal_count']                     = \Models\Notifications::unread_notifications($data['id_user'],'proposals',$data['type']);

                $reviews = \Models\Reviews::summary($data['id_user']);
                $data = array_merge($data,$reviews);
                
                /*UPDATING PROFILE PERCENTAGE*/
                self::update_profile_percentage($data);
                $data = array_merge(self::get_profile_percentage($user->id_user),$data);

                $data['sender']                             = ucwords(trim(sprintf("%s %s",$data['first_name'],$data['last_name'])));
                $data['sender_id']                          = $data['id_user'];
                $data['sender_picture']                     = $data['picture'];
                $data['sender_email']                       = ___e($data['email']);
                $data['sender_profile_link']                = url(sprintf('%s/find-talents/profile?talent_id=%s',EMPLOYER_ROLE_TYPE,___encrypt($data['id_user'])));
            }

            return $data;
        }

        /**
         * [This method is used for getting user's view profile details] 
         * @param [Varchar]$userId [Used for User's Id]
         * @return Data Response
         */ 

        public static function view_talent_profile($userId){
            $prefix = DB::getTablePrefix();
            $language = \App::getLocale();

            $keys = array(
                'id_user',
                'type',
                'first_name',
                'last_name',
                'email',
                'expected_salary',
                'other_expectations',
                'agree',
                'agree_pricing',
                'birthday',
                'gender',
                'country_code',
                'mobile',
                'address',
                'country',
                'state',
                'city',
                'postal_code',
                'picture',
                'industry',
                'subindustry',
                'expertise',
                'experience',
                'workrate',
                'workrate_max',
                'workrate_unit',
                'workrate_information',
                'agree_pricing',
                'cover_letter_description',
                'facebook_id',
                'instagram_id',
                'twitter_id',
                'linkedin_id',
                'googleplus_id',
                'is_mobile_verified',
                'chat_status',
                'users.created',
                'is_interview_popup_appeared',
                'newsletter_subscribed',
                'currency',
                'social_account',
                'paypal_id',
                'last_login'
            ); 

            $keys = array_merge($keys,[
                \DB::Raw("IF(({$prefix}country_code.{$language} != ''),{$prefix}country_code.`{$language}`, {$prefix}country_code.`en`) as country_code_name"),
                \DB::Raw("IF(({$prefix}countries.{$language} != ''),{$prefix}countries.`{$language}`, {$prefix}countries.`en`) as country_name"),
                \DB::Raw("IF(({$prefix}state.{$language} != ''),{$prefix}state.`{$language}`, {$prefix}state.`en`) as state_name"),
                \DB::Raw("IF(({$prefix}city.{$language} != ''),{$prefix}city.`{$language}`, {$prefix}city.`en`) as city_name"),
            ]);
                
            $data = json_decode(json_encode(self::findById($userId,$keys)),true);

            if(!empty($data)){
                
                if(!empty($data['expected_salary'])){
                    $data['expected_salary'] = ___format($data['expected_salary'],false,false);
                }
                
                if(!empty($data['workrate'])){
                    $data['workrate'] = ___format($data['workrate'],false,false);
                }
                
                if(!empty($data['workrate_max'])){
                    $data['workrate_max'] = ___format($data['workrate_max'],false,false);
                }

                $data['first_name']                         = ucwords($data['first_name']);
                $data['last_name']                          = ucwords($data['last_name']);
                $data['picture']                            = get_file_url(self::get_file(sprintf(" type = 'profile' AND user_id = %s",$userId),'single',['filename','folder']));
                $data['interested']                         = \Models\Talents::interested_in($userId);
                $data['remuneration']                       = \Models\Talents::remuneration($userId);
                $data['certificates']                       = \Models\Talents::certificates($userId);
                $data['skills']                             = \Models\Talents::skills($userId);
                $data['subindustry']                        = \Models\Talents::subindustry($userId);
                $data['industry']                           = \Models\Talents::industry($userId);
                $data['educations']                         = \Models\Talents::educations($userId);
                $data['work_experiences']                   = \Models\Talents::work_experiences($userId);
                $data['certificate_attachments']            = \Models\Talents::get_file(sprintf(" user_id = %s AND type = 'certificates' ",$userId),'all',['id_file','filename','folder','size','extension as type','extension']);
                $data['availability']                       = \Models\Talents::get_availability($userId, NULL, NULL, 'self');
                $data['jobdetails']                         = \Models\Talents::jobdetails($userId);
                
                if(!empty($data['birthday'])){
                    $birthday = explode('-', $data['birthday']);
                    $data['birthdate']                          = $birthday[2];
                    $data['birthmonth']                         = $birthday[1];
                    $data['birthyear']                          = $birthday[0];
                }else{
                    $data['birthdate'] = $data['birthmonth'] = $data['birthyear'] = "";
                }
                
                $data['notification_count']                 = \Models\Notifications::unread_notifications($data['id_user']);
                $data['proposal_count']                     = \Models\Notifications::unread_notifications($data['id_user'],'proposals',$data['type']);

                $reviews = \Models\Reviews::summary($data['id_user']);
                $data = array_merge($data,$reviews);
                
                /*UPDATING PROFILE PERCENTAGE*/
                self::update_profile_percentage($data);
                $data = array_merge(self::get_profile_percentage($userId),$data);

                $data['sender']                             = ucwords(trim(sprintf("%s %s",$data['first_name'],$data['last_name'])));
                $data['sender_id']                          = $data['id_user'];
                $data['sender_picture']                     = $data['picture'];
                $data['sender_email']                       = ___e($data['email']);
                $data['sender_profile_link']                = url(sprintf('%s/find-talents/profile?talent_id=%s',EMPLOYER_ROLE_TYPE,___encrypt($data['id_user'])));
            }

            return $data;
        }

        /**
         * [This method is used to get profile percentage] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public static function get_profile_percentage($user_id){
            /*\DB::Raw('
                FLOOR(
                    IFNULL(
                        (
                            IFNULL(percentage_default,0)
                            +
                            IFNULL(percentage_step_one,0)
                            +
                            IFNULL(percentage_step_two,0)
                            +
                            IFNULL(percentage_step_three,0)
                            +
                            IFNULL(percentage_step_four,0)
                            +
                            IFNULL(percentage_step_five,0)
                        ),
                        0
                    )
                ) as profile_percentage_count
            '),*/

            $keys = [
                \DB::Raw('IFNULL(percentage_step_one,0) as profile_percentage_step_one'),
                \DB::Raw('IFNULL(percentage_step_two,0) as profile_percentage_step_two'),
                \DB::Raw('IFNULL(percentage_step_three,0) as profile_percentage_step_three'),
                \DB::Raw('IFNULL(percentage_step_four,0) as profile_percentage_step_four'),
                \DB::Raw('IFNULL(percentage_step_five,0) as profile_percentage_step_five'),
                \DB::Raw('IFNULL(percentage_step_paypal_id,0) as percentage_step_paypal_id')
            ];
            
            $result = (array) json_decode(json_encode(self::findById($user_id,$keys)),true);

            $result['profile_percentage_step_one']        = (string)(float)($result['profile_percentage_step_one']);
            $result['profile_percentage_step_two']        = (string)(float)($result['profile_percentage_step_two']);
            $result['profile_percentage_step_three']      = (string)(float)($result['profile_percentage_step_three']);
            $result['profile_percentage_step_four']       = (string)(float)($result['profile_percentage_step_four']);
            $result['profile_percentage_step_five']       = (string)(float)($result['profile_percentage_step_five']);
            $result['percentage_step_paypal_id']          = (string)(float)($result['percentage_step_paypal_id']);


            $result['profile_percentage_count']           = (string)(int)___rounding((float)(($result['profile_percentage_step_one']+ $result['profile_percentage_step_two']+ $result['profile_percentage_step_three']+ $result['profile_percentage_step_four']+ $result['profile_percentage_step_five']+ $result['percentage_step_paypal_id'] )),2);
            return $result;
        }

        /**
         * [This method is used to save job] 
         * @param [Integer]$user_id [Used for user_id]
         * @param [Integer]$job_id[Used for job id]
         * @return Data Response
         */ 

        public static function save_job($user_id, $job_id){
            $table_saved_job = DB::table('saved_jobs');

            $table_saved_job->where(['user_id' => $user_id, 'job_id' => $job_id]);

            if(!empty($table_saved_job->get()->count())){
                $isSaved = $table_saved_job->delete();

                if(!empty($isSaved)){
                    $result = [
                        'action' => 'deleted_saved_job',
                        'status' => true
                    ];
                }else{
                    $result = [
                        'action' => 'failed',
                        'status' => false
                    ];
                } 
            }else{
                $data = [
                    "user_id"   => $user_id,
                    "job_id"    => $job_id,
                    "created"   => date('Y-m-d H:i:s'),
                    "updated"   => date('Y-m-d H:i:s')
                ]; 
                $isSaved = $table_saved_job->insertGetId($data);


                if(!empty($isSaved)){
                    $result = [
                        'action' => 'saved_job',
                        'status' => true
                    ];
                }else{
                    $result = [
                        'action' => 'failed',
                        'status' => false
                    ];
                } 
            }

            return $result;
        } 

        /**
         * [This method is used for reviewing user's] 
         * @param [Integer]$talent_id[Used for user's id]
         * @param [Integer]$project_id[Used for project id]
         * @param [Integer]$page [Used for paging]
         * @param [Varchar]$keys[Used for Keys]
         * @param [Integer]$limit[Used for limit]
         * @return Data REsponse
         */ 

        public static function talent_reviews($talent_id,$project_id,$page = 0,$keys = NULL,$limit = DEFAULT_PAGING_LIMIT){
            $table_reviews = DB::table('talent_reviews as reviews');
            $prefix = DB::getTablePrefix();
            $offset = 0;
            
            if(empty($keys)){
                $keys           = [
                    'reviews.employer_id',
                    'reviews.description',
                    'reviews.review_average',
                    \DB::Raw("DATE({$prefix}reviews.created) as created"),
                    \DB::Raw("CONCAT({$prefix}employer.first_name,' ',{$prefix}employer.first_name) as employer_name"),
                ];
            }

            $table_reviews->select($keys);
            $table_reviews->leftJoin('users as employer','employer.id_user','=','reviews.employer_id');
            $table_reviews->where("reviews.project_id",$project_id);
            $table_reviews->where("reviews.talent_id",$talent_id);
            
            if(!empty($page)){
                $offset = ($page - 1)*$limit;
            }
            
            $table_reviews->groupBy(['reviews.talent_id']);
            $table_reviews->orderBy('reviews.id_talent_review');
            
            $total = $table_reviews->get()->count();

            $table_reviews->offset($offset);
            $table_reviews->limit($limit);

            $reviews  = json_decode(json_encode($table_reviews->get()),true);
            $total_filtered_result = $table_reviews->get()->count();
                
            if(!empty($reviews)){
                array_walk($reviews, function(&$item){
                    $item['created'] = ___d($item['created']);
                    $item['picture'] = get_file_url(\Models\Employers::get_file(sprintf(" type = 'profile' AND user_id = %s",$item['employer_id']),'single',['filename','folder']));
                });
            }

            return [
                'total' => $total,
                'result' => $reviews,
                'total_filtered_result' => $total_filtered_result,
            ];
        } 

        /**
         * [This method is used for remove] 
         * @param [Integer]$talent_id[Used for user's id]
         * @param [Integer]$page[Used for paging]
         * @param [Varchar]$keys [Used for keys]
         * @param [Integer]$limit[Used for limit]
         * @return Data Response
         */ 

        public static function active_proposals($talent_id,$page = 0,$keys = NULL,$limit = DEFAULT_PAGING_LIMIT){
            $table_proposals = DB::table('talent_proposals as proposals');
            $prefix = DB::getTablePrefix();
            $offset = 0;
            
            if(empty($keys)){
                $keys           = [
                    'projects.id_project',
                    'projects.title',
                    'employer.company_name',
                    'employer.id_user',
                    'proposals.created',
                    'proposals.status',
                    'proposals.id_proposal',
                ];
            }

            $table_proposals->select($keys);
            $table_proposals->leftJoin('projects as projects',function($leftjoin){
                $leftjoin->on('projects.id_project', '=', 'proposals.project_id');
                $leftjoin->on('projects.project_status','!=',DB::raw("'close'"));
            });
            $table_proposals->leftJoin('users as employer',function($leftjoin) use ($talent_id){
                $leftjoin->on('employer.id_user', '=', 'projects.user_id');
            });
            $table_proposals->where('proposals.status','=','accepted');
            $table_proposals->where('proposals.user_id','=',\DB::Raw($talent_id));
            
            if(!empty($page)){
                $offset = ($page - 1)*$limit;
            }
            
            $table_proposals->groupBy(['proposals.id_proposal']);
            $table_proposals->orderBy('proposals.id_proposal','DESC');
            
            $total = $table_proposals->get()->count();

            $table_proposals->offset($offset);
            $table_proposals->limit($limit);

            $proposals  = json_decode(json_encode($table_proposals->get()),true);
            $total_filtered_result = $table_proposals->get()->count();
                
            if(!empty($proposals)){
                array_walk($proposals, function(&$item){
                    $item['created'] = ___d($item['created']);
                });
            }

            return [
                'total' => $total,
                'result' => $proposals,
                'total_filtered_result' => $total_filtered_result,
            ];
        }

        /**
         * [This method is used for submitted proposals] 
         * @param [Integer]$talent_id [Used for user's id]
         * @param [Integer]$page[Used for paging]
         * @param [Varchar]$keys[Used for paging]
         * @param [Integer]$limit [Used for limit]
         * @return Data Response
         */ 

        public static function submitted_proposals($talent_id,$page = 0,$keys = NULL,$limit = DEFAULT_PAGING_LIMIT){
            $table_proposals = DB::table('talent_proposals as proposals');
            $prefix = DB::getTablePrefix();
            $offset = 0;
            
            if(empty($keys)){
                $keys           = [
                    'projects.id_project',
                    'projects.title',
                    'employer.company_name',
                    'employer.id_user',
                    'proposals.created',
                    'proposals.status',
                    'proposals.id_proposal',
                ];
            }

            $table_proposals->select($keys);
            $table_proposals->leftJoin('projects as projects',function($leftjoin){
                $leftjoin->on('projects.id_project', '=', 'proposals.project_id');
                $leftjoin->on('projects.project_status','!=',DB::raw("'close'"));
            });
            $table_proposals->leftJoin('users as employer',function($leftjoin) use ($talent_id){
                $leftjoin->on('employer.id_user', '=', 'projects.user_id');
            });
            $table_proposals->where('proposals.status','!=','accepted');
            $table_proposals->where('proposals.user_id','=',\DB::Raw($talent_id));
            
            if(!empty($page)){
                $offset = ($page - 1)*$limit;
            }
            
            $table_proposals->groupBy(['proposals.id_proposal']);
            $table_proposals->orderBy('proposals.id_proposal','DESC');
            
            $total = $table_proposals->get()->count();

            $table_proposals->offset($offset);
            $table_proposals->limit($limit);

            $proposals  = json_decode(json_encode($table_proposals->get()),true);
            $total_filtered_result = $table_proposals->get()->count();
                
            if(!empty($proposals)){
                array_walk($proposals, function(&$item){
                    $item['created'] = ___d($item['created']);
                });
            }

            return [
                'total' => $total,
                'result' => $proposals,
                'total_filtered_result' => $total_filtered_result,
            ];
        }

        /**
         * [This method is used to update profile percentage] 
         * @param [Varchar]$user_details[Used for user details]
         * @return Data Response
         */ 

        public static function update_profile_percentage($user_details){

            /* 
                Total fields = 23
                Each step % = 100/23 = 4.34782609;
            */

            $percentage = [];
            $step_one_percentage = array_intersect_key(
                $user_details,
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
                    )
                )
            );


            /*CALCULETING STEP ONE PERCENTAGE*/                
            $percentage['percentage_step_one'] = (count(array_filter($step_one_percentage))*TALENT_STEP_ONE_PROFILE_PERCENTAGE_WEIGHTAGE);

            $step_two_percentage = array_intersect_key(
                $user_details,
                array_flip(
                    array(
                        'subindustry',
                        'skills',
                        'expertise',
                        'experience'
                    )
                )
            );
            $step_two_percentage['subindustry'] = !empty($step_two_percentage['subindustry'])? true : false;
            $step_two_percentage['skills']      = !empty($step_two_percentage['skills'])? true : false;
            /*CALCULETING STEP TWO PERCENTAGE*/
            $percentage['percentage_step_two'] = (count(array_filter($step_two_percentage))*TALENT_STEP_TWO_PROFILE_PERCENTAGE_WEIGHTAGE);

            $step_three_percentage = array_intersect_key(
                $user_details,
                array_flip(
                    array(
                        'industry',
                        'certificate_attachments'
                    )
                )
            );


            $step_three_percentage['certificate_attachments'] = !empty($step_three_percentage['certificate_attachments']) ? true : false;
            $step_three_percentage['industry'] = !empty($step_three_percentage['industry']) ? true : false;
            
            /*CALCULETING STEP THREE PERCENTAGE*/                
            $percentage['percentage_step_three'] = (count(array_filter($step_three_percentage))*TALENT_STEP_THREE_PROFILE_PERCENTAGE_WEIGHTAGE);

            $step_four_percentage = array_intersect_key(
                $user_details, 
                array_flip(
                    array(
                        'remuneration',
                        'workrate_information'
                    )
                )
            );

            $step_four_percentage['remuneration'] = !empty($step_four_percentage['remuneration']) ? true : false;
            
            /*CALCULETING STEP FOUR PERCENTAGE*/                
            $percentage['percentage_step_four'] = ((count(array_filter($step_four_percentage)))*TALENT_STEP_FOUR_PROFILE_PERCENTAGE_WEIGHTAGE);

            $step_five_percentage = array_intersect_key(
                $user_details, 
                array_flip(
                    array(
                        'educations',
                        'work_experiences',
                    )
                )
            );

            $step_five_percentage['educations'] = !empty($step_five_percentage['educations']) ? true : false;
            $step_five_percentage['work_experiences'] = !empty($step_five_percentage['work_experiences']) ? true : false;

            /*CALCULETING STEP FIVE PERCENTAGE*/
            $percentage['percentage_step_five'] = ((count(array_filter($step_five_percentage)))*TALENT_STEP_FIVE_PROFILE_PERCENTAGE_WEIGHTAGE);

            /*CALCULETING STEP PAYPAL ID PERCENTAGE*/
            $step_paypal_id_percentage = array_intersect_key(
                $user_details,
                array_flip(
                    array(
                        'paypal_id',
                    )
                )
            );

            $percentage['percentage_step_paypal_id'] = ((count(array_filter($step_paypal_id_percentage)))*TALENT_STEP_PAYPAL_ID_PROFILE_PERCENTAGE_WEIGHTAGE);

            self::change($user_details['id_user'],$percentage);
        }

        /**
         * [This method is used for top talent users] 
         * @param [Integer]$current_talent_id[Used for current user's id]
         * @return Data Response
         */ 
        
        public static function top_talent_user($current_talent_id){
            $table_projects = DB::table('users as users');
            $prefix = DB::getTablePrefix();
            $offset = 0;
            $keys = array(
                'users.id_user',
                DB::Raw("CONCAT (IFNULL({$prefix}users.first_name,'".N_A."'),' ',IFNULL({$prefix}users.last_name,'".N_A."')) as name"),
                #DB::Raw("0.0 as rating"),
                DB::raw('(SELECT IFNULL(ROUND(AVG(review_average), 1), "0.0") FROM '.$prefix.'reviews AS rev WHERE rev.receiver_id = '.$prefix.'users.id_user) as rating')
            );
            $table_projects->select($keys);
            $table_projects->where(['users.type' => 'talent']);
            $table_projects->whereNotIn('users.id_user',[$current_talent_id]);
            $table_projects->offset($offset);
            $table_projects->limit(NUMBER_OF_TOP_TALENT_LIST);            
            $data = json_decode(json_encode($table_projects->get()),true);
            array_walk($data, function(&$item){
                $item['picture'] = get_file_url(self::get_file(sprintf(" type = 'profile' AND user_id = %s",$item['id_user']),'single',['filename','folder']));
            });
            return $data;
        }

        /**
         * [This method is used for getting chat count] 
         * @param [Integer]$user_id [Used for user id]
         * @return Data Response
         */ 

        public static function get_chat_count($user_id){
            $table_chat_requests    = DB::table('chat');
            
            $table_chat_requests->leftjoin('chat_requests','chat_requests.id_chat_request','=','chat.group_id');
            $table_chat_requests->where('chat_requests.is_terminated','=','no');
            $table_chat_requests->whereIn('chat_requests.chat_initiated',['employer','employer-accepted']);
            $table_chat_requests->where('chat.receiver_id','=',$user_id);
            $table_chat_requests->where('chat.seen_status','!=','read');

            return (int) $table_chat_requests->count();
        }

        /**
         * [This method is used to send chat request] 
         * @param [Integer]$sender_id[Used for Sender id]
         * @param [Integer]$receiver_id[Used for Receiver id]
         * @param [Integer]$project_id[Used for Project id]
         * @param [Integer]$proposal_id [Used for Proposal id]
         * @param [Varchar]$is_employer_initiated [Used for employer initiated]
         * @return Data Response
         */ 

        public static function send_chat_request($sender_id,$receiver_id,$project_id = NULL,$proposal_id = NULL, $is_employer_initiated = NULL, $edited = false){

            $table_chat_requests    = DB::table('chat_requests');
            
            $table_chat_requests->where([
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'project_id' => $project_id,
            ]);

            if(empty($table_chat_requests->count())){
                $result = $table_chat_requests->insertGetId([
                    'sender_id' => $sender_id,
                    'receiver_id' => $receiver_id,
                    'project_id' => $project_id,
                    'chat_initiated' => (!empty($is_employer_initiated))?'employer':NULL,
                    'created' => date('Y-m-d H:i:s'),
                    'updated' => date('Y-m-d H:i:s'),
                ]);
            }else{
                $result = $table_chat_requests->update([
                    'request_status' => 'pending',
                    'updated' => date('Y-m-d H:i:s'),
                ]);
            }

            if(!empty($project_id)){
                if(empty($edited)){
                    $message = 'JOB_PROPOSAL_SUBMITTED_BY_TALENT';
                }else{
                    $message = 'JOB_PROPOSAL_EDITED_BY_TALENT';
                }

                $isNotified = \Models\Notifications::notify(
                    $receiver_id,
                    $sender_id,
                    $message,
                    json_encode([
                        "receiver_id"   => (string) $receiver_id,
                        "sender_id"     => (string) $sender_id,
                        "project_id"    => (string) $project_id,
                        "proposal_id"   => (string) $proposal_id
                    ])
                );
            }

            return $result;
        }

        /**
         * [This method is used to get my chat list] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Search]$search[Used for searching]
         * @param [Integer]$employer_id [Used for employer Id]
         * @return Data Response
         */    

        public static function get_my_chat_list($user_id, $search = NULL, $employer_id = NULL){
            $table_chat_requests    = DB::table('chat_requests');
            $prefix                 = DB::getTablePrefix();
            $base_url               = ___image_base_url();

            $table_chat_requests->select([
                "id_chat_request",
                \DB::Raw("LPAD({$prefix}chat_requests.project_id, ".JOBID_PREFIX.", '0') as project_id"),
                \DB::Raw("{$user_id} as sender_id"),
                'talents.id_user as receiver_id', 
                \DB::Raw("TRIM(IF({$prefix}talents.last_name IS NULL, {$prefix}talents.first_name,CONCAT({$prefix}talents.first_name,' ',{$prefix}talents.last_name))) as receiver_name"),
                'talents.email as receiver_email',
                'talents.chat_status as status',
                \DB::Raw(
                    "IF(
                        ({$prefix}chat_requests.request_status = 'pending' AND {$prefix}chat_requests.chat_initiated != 'talent'),
                        'accepted',
                        {$prefix}chat_requests.request_status
                    ) as request_status"
                ),
                \DB::Raw("
                    IF(
                        {$prefix}files.filename IS NOT NULL,
                        CONCAT('{$base_url}','/',{$prefix}files.folder,{$prefix}files.filename),
                        CONCAT('{$base_url}','/','images/','".DEFAULT_AVATAR_IMAGE."')
                    ) as receiver_picture
                "),
                \DB::Raw("
                    (
                        IFNULL(
                            (
                                SELECT COUNT(id_chat) FROM {$prefix}chat 
                                WHERE (
                                    (receiver_id = {$user_id} AND sender_id = {$prefix}talents.id_user)
                                    AND 
                                    seen_status != 'read'
                                    AND 
                                    delete_sender_status = 'active'
                                )
                                AND group_id = id_chat_request
                            ),
                            0
                        )
                    ) as unread_messages
                "),
                \DB::Raw("(
                        SELECT message FROM {$prefix}chat 
                        WHERE (
                            ({$prefix}chat.sender_id = {$user_id} AND receiver_id = {$prefix}talents.id_user)
                            OR 
                            ({$prefix}chat.sender_id = {$prefix}talents.id_user AND  receiver_id = {$user_id})
                        )
                        AND delete_sender_status = 'active'
                        AND group_id = id_chat_request
                        ORDER BY {$prefix}chat.id_chat DESC
                        LIMIT 0,1
                    ) as last_message
                "),
                \DB::Raw("(
                        SELECT message_type FROM {$prefix}chat 
                        WHERE (
                            ({$prefix}chat.sender_id = {$user_id} AND receiver_id = {$prefix}talents.id_user)
                            OR 
                            ({$prefix}chat.sender_id = {$prefix}talents.id_user AND  receiver_id = {$user_id})
                        )
                        AND delete_sender_status = 'active'
                        AND group_id = id_chat_request
                        ORDER BY {$prefix}chat.id_chat DESC
                        LIMIT 0,1
                    ) as last_message_type
                "),
                \DB::Raw("
                    (
                        IFNULL(
                            (
                                SELECT created FROM {$prefix}chat 
                                WHERE (
                                    ({$prefix}chat.sender_id = {$user_id} AND receiver_id = {$prefix}talents.id_user)
                                    OR 
                                    ({$prefix}chat.sender_id = {$prefix}talents.id_user AND  receiver_id = {$user_id})
                                )
                                AND group_id = id_chat_request
                                ORDER BY {$prefix}chat.id_chat DESC
                                LIMIT 0,1
                            ),
                            {$prefix}chat_requests.created
                        )
                    ) as timestamp
                "),
                \DB::Raw("
                    (
                        IFNULL(
                            (
                                SELECT created FROM {$prefix}chat 
                                WHERE (
                                    ({$prefix}chat.sender_id = {$user_id} AND receiver_id = {$prefix}talents.id_user)
                                    OR 
                                    ({$prefix}chat.sender_id = {$prefix}talents.id_user AND  receiver_id = {$user_id})
                                )
                                AND group_id = id_chat_request
                                ORDER BY {$prefix}chat.id_chat DESC
                                LIMIT 0,1
                            ),
                            {$prefix}chat_requests.created
                        )
                    ) as requested_date
                ")
            ]);

            $table_chat_requests->leftJoin('users as talents','talents.id_user','=','chat_requests.receiver_id');
            $table_chat_requests->leftJoin('files as files',function($leftjoin){
                $leftjoin->on('files.user_id','=','talents.id_user');
                $leftjoin->on('files.type','=',\DB::Raw('"profile"'));
            });

            if(!empty($search)){
                $table_chat_requests->having("receiver_name","like","%{$search}%");
            }

            $table_chat_requests->where("sender_id",$user_id);
            $table_chat_requests->where("is_terminated","no");
            $table_chat_requests->where("talents.status",'active');
            $table_chat_requests->where("request_status","!=","rejected");
            $table_chat_requests->whereIn("chat_initiated",['employer','employer-accepted']);
            $table_chat_requests->orderBy('timestamp','DESC');
            
            if(!empty($employer_id)){
                $table_chat_requests->where('receiver_id',$employer_id);
                $result = json_decode(json_encode($table_chat_requests->get()),true);
            }else{
                $result = json_decode(json_encode($table_chat_requests->get()),true);
            }


            if(!empty($result)){
                array_walk($result, function(&$item) use($user_id){
                    $item['receiver_email']     = ___e($item['receiver_email']);
                    $item['ago']                = ___agoday($item['timestamp']);
                    $item['fulltime']           = ___d($item['timestamp']);
                    $item['timestamp']          = strtotime($item['timestamp']);
                    $item['last_message_code']  = "";

                    if(empty($item['last_message'])){
                        if($item['request_status'] != 'accepted'){
                            $item['last_message'] = trans('general.M0474');
                            $item['last_message_code'] = 'M0474';
                        }
                    }elseif($item['last_message_type'] == 'image'){
                        $item['last_message'] = trans('website.W0423');
                    }

                    $item['profile_link']   = "";
                });
            }

            return $result;
        }

        /**
         * [This method is used to setTalentAvailability] 
         * @param [Integer]$user_id [Used for User id]
         * @param [Varchar]$max_repeat_group[Used for max repeat group]
         * @param [Boolean]$insertArr[Used for insert array]
         * @param [Integer]$availability_id[Used for availability id]
         * @param [datetime]$availability_date[Used for availability date]
         * @param [Varchar] $deadline[Used for dead line]
         * @param [VArchar]$availability_type [Used for availability type]
         * @return Data Response
         */ 

        public static function setTalentAvailability($user_id, $max_repeat_group, $insertArr, $availability_id, $availability_date = NULL, $deadline = NULL, $availability_type = NULL){

            if($availability_type == 'unavailable'){
                DB::table('talent_availability')
                ->where('availability_date', '>=', $availability_date)
                ->where('availability_date', '<=', $deadline)
                ->where('availability_type', 'unavailable')
                ->where('user_id', $user_id)
                ->delete();
            }

            $repeat_group = $max_repeat_group;
            if($availability_id > 0){
                self::delete_availability($user_id,$availability_id);
                $repeat_group = $availability_id;
            }

            $isInserted = DB::table('talent_availability')
                ->insert($insertArr);
            $result = self::get_availability($user_id,$repeat_group, NULL, 'self');

            return $result;
        }

        /**
         * [This method is used to check availability] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Datetime]$availability_date[Used for availability date]
         * @param [Datetime]$from_time[Used for from_time ]
         * @param [Datetime]$to_time[Used for to_time]
         * @param [Varchar]$deadline[Used for deadline]
         * @param [Datetime]$availability_day[Used for availability day]
         * @param [Varchar]$repeat[Used for repeat]
         * @param [Integer]$availability_id [Used for availability id]
         * @return Data Response
         */ 

        public static function check_availability($user_id,$availability_date,$from_time,$to_time,$deadline,$availability_day,$repeat,$availability_id = NULL){

            if($availability_id > 0){
                DB::table('talent_availability')->where('repeat_group',$availability_id)->delete();
            }

            $table_talent_availability = DB::table('talent_availability');

            $dateWhereClause = '';
            if($repeat == 'daily' || $repeat == 'monthly'){
                $begin = new \DateTime( $availability_date );
                $endDate = date('Y-m-d', strtotime("+1 day", strtotime($deadline)));
                $end = new \DateTime( $endDate );

                if($repeat == 'daily'){
                    $repeat_type = '1 day';
                }
                elseif($repeat == 'monthly'){
                    $repeat_type = '1 month';
                }
                $interval = \DateInterval::createFromDateString($repeat_type);
                $period = new \DatePeriod($begin, $interval, $end);

                foreach ( $period as $dt ){
                    $dateWhereClause .= " IF(
                        (CONCAT(availability_date, ' ', from_time) = '".$dt->format( 'Y-m-d' )." ".$from_time."'),
                        true,
                        IF(
                            (CONCAT(availability_date, ' ', to_time) > '".$dt->format( 'Y-m-d' )." ".$from_time."' && CONCAT(availability_date, ' ', to_time) < '".$dt->format( 'Y-m-d' )." ".$from_time."'),
                            true,
                            IF(
                                (CONCAT(availability_date, ' ', from_time) > '".$dt->format( 'Y-m-d' )." ".$from_time."' && CONCAT(availability_date, ' ', from_time) < '".$dt->format( 'Y-m-d' )." ".$from_time."'),
                                true,
                                IF(
                                    (CONCAT(availability_date, ' ', from_time) < '".$dt->format( 'Y-m-d' )." ".$from_time."' && CONCAT(availability_date, ' ', to_time) > '".$dt->format( 'Y-m-d' )." ".$from_time."'),
                                    true,
                                    false
                                )
                            )
                        )
                    ) OR";
                }
            }
            elseif($repeat == 'weekly'){
                $date = ___days_between($availability_date, $deadline, $availability_day);

                foreach ($date as $d) {
                    $dateWhereClause .= " IF(
                        (CONCAT(availability_date, ' ', from_time) = '".$d." ".$from_time."'),
                        true,
                        IF(
                            (CONCAT(availability_date, ' ', to_time) > '".$d." ".$from_time."' && CONCAT(availability_date, ' ', to_time) < '".$d." ".$from_time."'),
                            true,
                            IF(
                                (CONCAT(availability_date, ' ', from_time) > '".$d." ".$from_time."' && CONCAT(availability_date, ' ', from_time) < '".$d." ".$from_time."'),
                                true,
                                IF(
                                    (CONCAT(availability_date, ' ', from_time) < '".$d." ".$from_time."' && CONCAT(availability_date, ' ', to_time) > '".$d." ".$from_time."'),
                                    true,
                                    false
                                )
                            )
                        )
                    ) OR";
                }
            }

            $dateWhereClause = rtrim($dateWhereClause, ' OR');

            if($availability_id > 0){
                $dateWhereClause = "user_id = {$user_id} AND ( " . $dateWhereClause . " ) ";
            } else{
                $dateWhereClause = "user_id = {$user_id} AND ( " . $dateWhereClause . " ) ";
            }

            $table_talent_availability->whereRaw($dateWhereClause);

            $availability = json_decode(json_encode($table_talent_availability->get()->first()),true);

            if(!empty($availability)){
                if($availability['repeat_group'] != $availability_id){
                    return false;
                }else{
                    #DB::table('talent_availability')->where('repeat_group',$availability_id)->delete();
                    return true;
                }
            }else{
                return true;
            }
        }

        /**
         * [This method is used to get calendar availability] 
         * @param [Integer]$user_id [Used for user id]
         * @param [Varchar]$date[Used for data]
         * @return Data Response
         */ 

        public static function get_calendar_availability($user_id, $date = NULL, $three_month_data = false){
            $prefix = DB::getTablePrefix();
            $table_talent_availability = DB::table('talent_availability');

            $table_talent_availability->select([
                'repeat_group as id_availability',
                DB::Raw('CONCAT(availability_date, "T", from_time) AS start'),
                DB::Raw('CONCAT(availability_date, "T", to_time) AS end'),
                'repeat AS type',
                'from_time',
                'to_time',
                DB::Raw('CONCAT(DATE_FORMAT(from_time, "%h:%i %p"), " - ", DATE_FORMAT(to_time, "%h:%i %p")) AS description'),
                DB::Raw('CONCAT(DATE_FORMAT(from_time, "%h:%i %p"), " - ", DATE_FORMAT(to_time, "%h:%i %p")) title'),
                DB::Raw('(SELECT IFNULL(GROUP_CONCAT(DISTINCT(ta.availability_day)),"") FROM '.$prefix.'talent_availability AS ta WHERE ta.repeat_group = '.$prefix.'talent_availability.repeat_group ORDER BY FIELD(ta.availability_day, "MONDAY", "TUESDAY", "WEDNESDAY", "THURSDAY", "FRIDAY", "SATURDAY", "SUNDAY") ASC LIMIT 1) AS availability_day'),
                'deadline', 
                DB::Raw("
                    IF(
                        date(availability_date) > CURDATE(), 
                        'future_availability',
                        IF(
                            date(availability_date) = CURDATE(), 
                            'today_availbility',
                            'yesterday_availability'
                        )
                    ) as availability_day_class"
                ),
                'availability_type'
            ]);

            if(!empty($three_month_data)){
                if(empty($date)){
                    $date = date('Y-m-d');
                }else{
                    $date = date('Y-m-d',strtotime(str_replace("/", "-", $date)));
                }

                $previous_calendar_date = date('Y-m-d', strtotime("-7 Days", strtotime(date('Y-m-01',strtotime($date)))));
                $next_calendar_date     = date('Y-m-d', strtotime("+7 Days", strtotime(date('Y-m-t',strtotime($date)))));
                $availability = json_decode(json_encode(
                    $table_talent_availability->where('availability_date', '>=', $previous_calendar_date)
                    ->where('availability_date', '<=', $next_calendar_date)
                    ->where('user_id', $user_id)
                    ->get()
                ),true);
            }else if(empty($date)){
                $availability = json_decode(json_encode(
                    $table_talent_availability->where('user_id', $user_id)
                    ->get()
                ),true);
            }else{
                $availability = json_decode(json_encode(
                    $table_talent_availability->where('user_id', $user_id)
                    ->where('availability_date', '>=', date('Y-m-01', strtotime($date)))
                    ->where('availability_date', '<=', date('Y-m-t', strtotime($date)))
                    ->get()
                ),true);
            }

            foreach ($availability as &$item) {
                /*if($item['availability_type'] == 'unavailable'){
                    $item['title'] = '<span class="out-of-office"></span>' . $item['title'];
                }
                else{
                    $item['title'] = '<span class="in-office"></span>' . $item['title'];
                }
                $item['textEscape'] = false;*/
                // if($item['availability_type'] == 'unavailable'){
                //     $item['title'] = 'OUT OF OFFICE';
                // }

                if($item['type'] == 'daily'){
                    $item['title'] = sprintf("%s - %s\n%s",___t($item['from_time']),___t($item['to_time']),sprintf("%s %s",'Daily', sprintf("\nuntil %s",___d($item['deadline'])))).' ('.ucfirst($item['availability_type']).')';

                }
                elseif($item['type'] == 'weekly'){
                    $item['title'] = sprintf(
                        "%s - %s\n%s",
                        ___t($item['from_time']),
                        ___t($item['to_time']),
                        sprintf(
                            "WEEKLY ON %s %s",
                            ___replace_last(
                                ',',
                                " AND ",
                                str_replace(
                                    array_values(days()),
                                    array_keys(days()),
                                    $item['availability_day']
                                )
                            ),
                            sprintf("\nuntil %s",___d($item['deadline']))
                        )
                    ).' ('.ucfirst($item['availability_type']).')';
                }
                elseif($item['type'] == 'monthly'){
                    $item['title'] = sprintf("%s - %s\n%s",___t($item['from_time']),___t($item['to_time']),sprintf("%s %s",'Monthly', sprintf("\nuntil %s",___d($item['deadline'])))).' ('.ucfirst($item['availability_type']).')';
                }

                // if($item['type'] == 'weekly'){
                //     $item['title'] = sprintf(
                //                 "%s\n%s",
                //                 $item['title'],
                //                 sprintf(
                //                     "WEEKLY ON %s %s",
                //                     ___replace_last(
                //                         ',',
                //                         " AND ",
                //                         str_replace(
                //                             array_values(days()),
                //                             array_keys(days()),
                //                             $item['availability_day']
                //                         )
                //                     ),
                //                     sprintf("until %s",___d($item['deadline']))
                //                 )
                //             );
                // }
                // unset($item['deadline']);
            }

            // dd($availability);

            return $availability;
        }


        /**
         * [This method is used to get_members] 
         * @param [None]
         * @return Data Response
         */ 

        public static function get_members($inCircle = ''){

            $prefix   = DB::getTablePrefix();
            $base_url = ___image_base_url();
            $language = \App::getLocale();

            $talents = DB::table('users');
            $talents->select('users.*',
                DB::Raw("
                IF(
                    {$prefix}files.filename IS NOT NULL,
                    CONCAT('{$base_url}',{$prefix}files.folder,{$prefix}files.filename),
                    CONCAT('{$base_url}','images/','".DEFAULT_AVATAR_IMAGE."')
                ) as picture
                "),
                DB::Raw("IF((`{$prefix}user_industry`.`{$language}` != ''),`{$prefix}user_industry`.`{$language}`, `{$prefix}user_industry`.`en`) as industry_name"),
                DB::Raw("IF((`{$prefix}countries`.`{$language}` != ''),`{$prefix}countries`.`{$language}`, `{$prefix}countries`.`en`) as country"),
                DB::Raw("(Select {$prefix}members.request_status from `{$prefix}members` where {$prefix}members.user_id = cb_users.id_user and  {$prefix}members.member_id = ".\Auth::user()->id_user.")  as if_added_member"),
                DB::Raw("(Select {$prefix}members.request_status from `{$prefix}members` 
                    where {$prefix}members.user_id = ".\Auth::user()->id_user." and  
                          {$prefix}members.member_id = cb_users.id_user)  as if_added_member2")
            );
            $talents->leftjoin('files',function($leftjoin){
                $leftjoin->on('files.user_id','=','users.id_user');
                $leftjoin->where('files.type','=',\DB::Raw("'profile'"));
            });
            $talents->leftjoin('countries','countries.id_country','=','users.country');
            $talents->leftJoin('talent_industries as talent_industry','talent_industry.user_id','=','users.id_user');
            $talents->leftJoin('industries as user_industry','user_industry.id_industry','=','talent_industry.industry_id');
            
            if($inCircle == 'yes'){
                $talents->leftJoin('members','members.member_id','=','users.id_user');
                $talents->where('members.user_id','=',\Auth::user()->id_user);
                //$talents->orWhere('members.member_id','=',\Auth::user()->id_user);
                $talents->where('members.request_status','=','accepted');
            }else{
                $talents->where('users.id_user','!=',\Auth::user()->id_user);
            }
            $talents->where('users.type','talent');
            $talents->where('users.status','active');
            $talent = $talents->get();

            return $talent;
        }

        /**
         * [This method is used to get_talent_email] 
         * @param [None]
         * @return Data Response
         */ 
        public static function get_talent_email($fetch = 'array', $where="", $keys=['*']){


            $final_circle = array();

            //Get Members
            $incircle = 'yes';
            $talents = \Models\Talents::get_members($incircle);
            $result = json_decode(json_encode($talents),true);

            if(!empty($result)){
                $final_circle = array_column($result, 'id_user');
            }else{
                $final_circle = array();
            }

            $table_users = DB::table('users')->select($keys);
            $table_users->where('type','=','talent');
            $table_users->where('status','=','active');
            $table_users->whereIn('id_user',$final_circle);

            if(!empty($where)){
                $table_users->whereRaw($where);
            }
            if($fetch === 'array'){
                return json_decode(
                    json_encode(
                        $table_users->get()
                    ),
                    true
                );
            }else{
                return $table_users->get();
            }
        }

        /**
         * [This method is used to get_talent_name] 
         * @param [None]
         * @return Data Response
         */ 
        public static function get_talent_name($email){

            $table_users = DB::table('users')->select('first_name')
                            ->where('type','=','talent')
                            ->where('email','=',$email)
                            ->first();

            return json_decode(json_encode($table_users),true);
            
        }

        public static function getCompanyDetails($talent_id){
            return  \DB::table('company_connected_talent as cct')->join('talent_company as tc','tc.talent_company_id','cct.id_talent_company')->where('cct.id_user',$talent_id)->first();
        }


        public  function getCompany(){
            return $this->belongsToMany('Models\TalentCompany', 'company_connected_talent', 'id_user', 'id_talent_company');
        }

    }