<?php
    
    use Illuminate\Support\Facades\Mail;
    
    if (! function_exists('bcrypt')) {
        /**
        * Hash the given value.
        *
        * @param  string  $value
        * @param  array   $options
        * @return string
        */
        
        function bcrypt($value, $options = []){
           return app('hash')->make($value, $options);
        }
    }

    function ___alert($alert){
        if(empty($alert)){  
            if(Session::has('alert')){
                $alert = Session::get('alert');
            }
        }

        if(!empty($alert)){
            echo $alert;
        }
    }

    function ___encrypt($record_id) {
        return sprintf('%s%s',md5('singsys'),$record_id);
    }

    function ___decrypt($encrypted_id) {
        $encryption = md5('singsys');
        return str_replace($encryption,'', $encrypted_id);
    }

    function ___get_text_avatar($name,$size = '45') {
        if($size == 45){
            $fontsize = 28;
        }elseif($size == 32){
            $fontsize = 18;
        }

        echo "<span class='text-avatar' style='width: {$size}px;height: {$size}px;font-size: {$fontsize}px;line-height:normal;'>".substr($name, 0,1)."</span>";
    }

    function ___url($url = "",$folder = "",$echo = true) {
        if($folder == 'backend'){
            $url = ADMINPATH."/$url";
        }

        if(preg_match( '/^(http|https):\\/\\/[a-z0-9]+([\\-\\.]{1}[a-z0-9]+)*\\.[a-z]{2,5}'.'((:[0-9]{1,5})?\\/.*)?$/i' ,$url)){
            if($echo == true){
                echo $url;
            }else{
                return $url;
            }
        }else{
            if($echo == true){
                echo URL::to($url);
            }else{
                if($url === '/'.ADMIN_FOLDER.'/#'){
                    return 'javascript:void(0);';
                }else{
                    return URL::to($url);
                }
            }
        }
    }

    function ___ago($timestamp){
        if(1/*(int)$timestamp > 0*/){
            //type cast, current time, difference in timestamps
            if(!empty(strtotime($timestamp))){
                /*$current_time   = \Carbon\Carbon::parse($timestamp);
                $current_time   = $current_time->tz(___current_timezone());
                $timestamp      = $current_time->toDateTimeString();
                */
                $timestamp      = (int) strtotime($timestamp);
                
                $current_time   = date('Y-m-d H:i:s');
                /*$current_time   = \Carbon\Carbon::parse($current_time);
                $current_time   = $current_time->tz(___current_timezone());
                $current_time   = $current_time->toDateTimeString();
                */
                $current_time   = strtotime($current_time);
                
                $diff           = $current_time - $timestamp;
                $intervals      = array (
                    'year' => 31556926, 'month' => 2629744, 'week' => 604800, 'day' => 86400, 'hour' => 3600, 'minute'=> 60
                );

                //now we just find the difference
                if ($diff < 5){
                    return trans('website.W0561');
                }

                if ($diff < 10){
                    return trans('website.W0562');
                }  

                if ($diff < 59){
                    return $diff == 1 ? $diff . trans('website.W0563') : $diff . trans('website.W0564');
                }       

                if ($diff >= 60 && $diff < $intervals['hour']){
                    $diff = floor($diff/$intervals['minute']);
                    return $diff == 1 ? $diff . trans('website.W0565') : $diff . trans('website.W0566');
                }       

                if ($diff >= $intervals['hour'] && $diff < $intervals['day']){
                    $diff = floor($diff/$intervals['hour']);
                    return $diff == 1 ? $diff . trans('website.W0567') : $diff . trans('website.W0568');
                }   

                if ($diff >= $intervals['day'] && $diff < $intervals['week']){
                    $diff = floor($diff/$intervals['day']);
                    return $diff == 1 ? $diff . trans('website.W0569') : $diff . trans('website.W0570');
                }   

                if ($diff >= $intervals['week'] && $diff < $intervals['month']){
                    $diff = floor($diff/$intervals['week']);
                    return $diff == 1 ? $diff . trans('website.W0571') : $diff . trans('website.W0572');
                }   

                if ($diff >= $intervals['month'] && $diff < $intervals['year']){
                    $diff = floor($diff/$intervals['month']);
                    return $diff == 1 ? $diff . trans('website.W0573') : $diff . trans('website.W0574');
                }   

                if ($diff >= $intervals['year']){
                    $diff = floor($diff/$intervals['year']);
                    // return $diff == 1 ? $diff . ' year ago' : $diff . ' years ago';
                    return 'Posted '. date('M d,Y', ($timestamp) ) ;
                }
            }
        }else{
            return N_A;
        }
    }

    function ___dd($date,$format = ""){
        if(($date != '0000-00-00 00:00:00' || $date != '0000-00-00') && !empty($date)){
            $date_format = (!empty($format))?$format:"d M Y";
            $time_format = "h:i A";

            if(strstr($date, '00:00:00') === false && strlen($date) == 19){
                return date("{$date_format}, {$time_format}", strtotime($date));
            }else{
                return date($date_format, strtotime($date));
            }
        }else{
            return 'N/A';
        }
    }

    function ___d($date,$format = ""){
        if(($date != '0000-00-00 00:00:00' || $date != '0000-00-00') && !empty($date) && !empty(strtotime($date))){
            $date_format = (!empty($format)) ? $format : \Cache::get('configuration')['format_date'];
            $time_format = \Cache::get('configuration')['format_time'];

            if(strstr($date, '00:00:00') === false && strlen($date) == 19){
                $current_time = \Carbon\Carbon::parse($date);
                $current_time = $current_time->tz(___current_timezone());
            
                $date = $current_time->toDateTimeString();
                
                return date("{$date_format}, {$time_format}",strtotime($date));
            }else if(!empty(strtotime($date))){
                $current_time = \Carbon\Carbon::parse($date);
                $current_time = $current_time->tz(___current_timezone());
            
                $date = $current_time->toDateTimeString();
                
                return date($date_format, strtotime($date));
            }
        }else{
            return 'N/A';
        }
    }
    
    function ___current_timezone(){
        $COUNTRY = ___country();

        if(function_exists('geoip_time_zone_by_country_and_region')){
            if(!empty(geoip_time_zone_by_country_and_region($COUNTRY))){
                return geoip_time_zone_by_country_and_region($COUNTRY);
            }else{
                return DEFAULT_TIMEZONE;
            }
        } else{
            return DEFAULT_TIMEZONE;
        }
    }
    
    function ___country(){
        if(!empty($_SERVER['GEOIP_COUNTRY_CODE'])){
            return $_SERVER['GEOIP_COUNTRY_CODE'];
        }else{
            return DEFAULT_COUNTRY_CODE;    
        }

        $IP_ADDRESS = NULL;

        if(!empty($_SERVER['REMOTE_ADDR'])){
            $IP_ADDRESS = $_SERVER['REMOTE_ADDR'];
        }

        if(function_exists('geoip_country_code_by_name') && 0){
            $country = geoip_country_code_by_name($IP_ADDRESS);

            if(!empty($country)){
                return $country;
            }else{
                return DEFAULT_COUNTRY_CODE;
            }
        } else{
            return DEFAULT_COUNTRY_CODE;
        }
    }

    function ___t($time,$format = ""){
        if(!empty($time)){
            $format = (!empty($format))?$format:\Cache::get('configuration')['format_time'];
            $TIMEZONE = ___current_timezone();
            $current_time = \Carbon\Carbon::parse($time);
            $current_time = $current_time->tz($TIMEZONE);
        
            $date = $current_time->toDateTimeString();

            if(!empty($format)){
                $date = date($format,strtotime($date));
            }

            return $date;
        }else{
            return 'N/A';
        }
    }

    function ___time($time,$format = ""){
        if(!empty($time)){
            $format = (!empty($format))?$format:\Cache::get('configuration')['format_time'];
            $current_time = \Carbon\Carbon::parse($time);
            
            $date = $current_time->toDateTimeString();

            if(!empty($format)){
                $date = date($format,strtotime($date));
            }

            return $date;
        }else{
            return 'N/A';
        }
    }

    function ___convert_timestamp(&$data,$keys,$format = "") {
        array_walk_recursive($data, function(&$item, $key) use($keys,$format){
            if(in_array($key, $keys)){
                $item = ___d($item,$format);
            }
        });
    }


    function ___get_country_phone_code_by_country($country){
        $result = DB::table('countries')->select(['phone_country_code'])->where(
            [
                'id_country' => $country,
            ]
        )->get()->first()->phone_country_code;

        if(!empty($result)){
            return $result;
        }else{
            return NULL;
        }
    }

    /* MENU */

    function ___get_category_list(&$categoryList,$parent_category = 0){
        $id_admin = Auth::guard('admin')->user()->id_user;
        static $index = 0;$page = "";

        $admin_menus = \App\Models\Admins::get_menu_visibility($id_admin);
        
        $result = DB::table('users_menu')->where(
            [
                'status' => 'active',
                'parent' => $parent_category
            ]
        )->get()->toArray();
        
        foreach($result as $row){
            if(in_array($row->id, $admin_menus)){
                $callback = $row->callback;
                $categoryList[$row->id] = array(
                    'menu_id' => $row->id,
                    'name' => $row->name,
                    'action_url' => $row->action_url,
                    'menu_icon' => $row->menu_icon,
                    'disable_list_view' => $row->disable_list_view,
                    'callback' => (!empty($row->callback) && function_exists($row->callback))?$callback():'',
                    'class' => ($page == $row->action_url)?sprintf('active %s',$row->menu_class):$row->menu_class
                );
            }
            if(check_parent_category_by_id($row->id) > 0){
                ___get_category_list($categoryList[$row->id]['child'],$row->id);
            }
        }
    }

    function ___get_user_menu($menu = array()){
        $option = array(
            'depth' => array(
                'sidebar-menu',
                'treeview-menu',
            ) 
        );
        
        ___get_category_list($menu);
        echo sprintf('<ul class="%s"><li class="header">MAIN NAVIGATION</li>%s</ul>',$option['depth'][0],___menu($menu,$option));
    }

    function ___menu($menu,$option = array(),$depth = 0){
        static $html = '';
        $html .= add_menu_item($menu,$option,$depth,$html);
        return $html;
    }

    function ___getmenu($section,$format,$active_class,$active_list = false,$active_value= false, $query_string = null,$footer=null){
        $html = "";
        $current_name = "";
        $result = DB::table('users_menu')->select(['name','action_url','menu_class','menu_icon'])->where(
            [
                'status' => 'active',
                'section' => $section,
                'parent' => 0
            ]
        )->orderBy('menu_order','ASC')->get()->toArray();
        // dd($result,$section,$format,$active_class,$active_list = false,$active_value= false, $query_string = null,$footer=null);

        array_walk($result,function($item,$index) use(&$html,$active_class,$section,$active_list,&$current_name,$query_string,$footer){
            if($active_list !== true){
                if((strpos(Request::path(),$item->action_url) === false)){
                    $active_class = "";
                }

                if(!empty($item->menu_class) && $footer == null){
                    $active_class = "active";
                }
            }else{
                $path = str_replace(array_map(function($item){return $item.'/';}, array_keys(language())), "", Request::path());

                if((strpos(Request::path(),$item->action_url) !== false)){
                    $item->menu_class = $active_class;
                    $active_class = "";
                    $current_name = $item->name;
                }else if((strpos($item->action_url,$path) !== false) && ($index == 0)){
                    $item->menu_class = $active_class;
                    $active_class = "";
                    $current_name = $item->name;
                }else{
                    $active_class = "";
                }

                  
            }
            /*To disable connected talent in proposal tab*/
            if($section == 'employer-talent-profile' && !empty(\Auth::user()->id_user)){
                $talent_id = ___decrypt(Request::get('talent_id'));
                $isOwner = \Models\companyConnectedTalent::where('id_user',$talent_id)->where('user_type','owner')->count();

                if($item->name == 'Connected Talent' && $isOwner == '0'){
                    #$item->action_url = '#';
                }else if($item->name == 'Availability' && $isOwner > '0'){

                }
                else{
                    $html .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);  
                }
            }
            /*To disable My Proposal menu if Talent has no proposal*/
            else if($section == 'talent-top-after-login' && !empty(\Auth::user()->id_user)){
                $proposal_count = \Models\Proposals::get_talent_proposal_count(\Auth::user()->id_user);
                if($item->name == 'My Proposals' && $proposal_count == 0){
                    #$item->action_url = '#';
                }
                else{
                    $html .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);  
                }
            }

            /*To disable connect with talent menu if Talent has already connected*/
            else if($section == 'talent-settings' && !empty(\Auth::user()->id_user)){
                $connecedUser = \Models\companyConnectedTalent::where('id_user',\Auth::user()->id_user)->where('user_type','owner')->first();
                // dd($connecedUser);
                // dd(\Auth::user()->company_profile);
                if($item->name == 'Connect with Talent' && count($connecedUser) > 0){
                    #$item->action_url = '#';
                }
                else if($item->name == 'Transfer Ownership' && empty($connecedUser)){
                    #$item->action_url = '#';
                }
                else{
                    $html .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);  
                }
            }
            else{
                $html .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);
            }

        });

        if($active_value !== true){
            $current_name = "";
        }

        return sprintf($format,$current_name,$html);
    }

    function ___getTalentMenu($uppersection,$section,$format,$active_class,$active_list = false,$active_value= false, $query_string = null,$footer=null){

        $html = '<div class="container-fluid"><ul class="navigation-group-list">';
        $current_name = "";
        $result = DB::table('users_menu')->select(['name','parent','action_url','menu_class','menu_icon'])->where(
            [
                'status' => 'active',
                'section' => $section
            ]
        )->orderBy('menu_order','ASC')->get()->toArray();

        array_walk($result,function($item,$index) use(&$html,$active_class,$section,$active_list,&$current_name,$query_string,$footer){
            if($active_list !== true){
                if((strpos(Request::path(),$item->action_url) === false)){
                    $active_class = "";
                }

                if(!empty($item->menu_class) && $footer == null){
                    $active_class = "active";
                }
            }else{
                $path = str_replace(array_map(function($item){return $item.'/';}, array_keys(language())), "", Request::path());

                if((strpos(Request::path(),$item->action_url) !== false)){
                    $item->menu_class = $active_class;
                    $active_class = "";
                    $current_name = $item->name;
                }else if((strpos($item->action_url,$path) !== false) && ($index == 0)){
                    $item->menu_class = $active_class;
                    $active_class = "";
                    $current_name = $item->name;
                }else{
                    $active_class = "";
                }
            }

            /*To disable My Proposal menu if Talent has no proposal*/
            if($section == 'talent-top-after-login' && !empty(\Auth::user()->id_user)){
                $proposal_count = \Models\Proposals::get_talent_proposal_count(\Auth::user()->id_user);
                if($item->name == 'My Proposals' && $proposal_count == 0){
                    #$item->action_url = '#';
                }
                else{
                    $html .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);  
                }
            }
            else{
                $html .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);  
            }

        });

        $html = $html.'</ul></div>';

        $result1 = DB::table('users_menu')->select(['id','name','action_url','menu_class','menu_icon'])->where(
            [
                'status' => 'active',
                'section' => $uppersection,
                'parent' => 0
            ]
        )->orderBy('menu_order','ASC')->get()->toArray();

        $create_sub_parent = json_decode(json_encode($result),true);
        $sub_parent = array_column($create_sub_parent, 'parent')[0];

        $html1 = '<div class="talent-new-menu"><div class="container-fluid"><ul class="user-profile-links user_submenu">';

        array_walk($result1,function($item,$index) use(&$html1,$active_class,$uppersection,&$current_name,$query_string,$footer,$section,$sub_parent){

            if($sub_parent == $item->id){
                $active_class = 'active';
            }else{
                $active_class = '';
            }

            if($section == 'talent-top-after-login'){
                $html1 .= '1';
                $html1 .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);
            }else{
                $html1 .= '2';
                $html1 .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);
            }
        });

        $html1 .= '</ul></div></div>';
        $html_before = $html1.''.$html;

        if($active_value !== true){
            $current_name = "";
        }
        return sprintf($format,$current_name,$html_before);
    }

    function ___getEmployerMenu($uppersection,$section,$format,$active_class,$active_list = false,$active_value= false, $query_string = null,$footer=null){
        $html = '<div class="container-fluid"><ul class="navigation-group-list">';
        $current_name = "";
        $result = DB::table('users_menu')->select(['name','action_url','menu_class','menu_icon'])->where(
            [
                'status' => 'active',
                'section' => $section,
                'parent' => 0
            ]
        )->orderBy('menu_order','ASC')->get()->toArray();

        array_walk($result,function($item,$index) use(&$html,$active_class,$section,$active_list,&$current_name,$query_string,$footer){
            if($active_list !== true){
                if((strpos(Request::path(),$item->action_url) === false)){
                    $active_class = "";
                }

                if(!empty($item->menu_class) && $footer == null){
                    $active_class = "active";
                }
            }else{
                $path = str_replace(array_map(function($item){return $item.'/';}, array_keys(language())), "", Request::path());

                if((strpos(Request::path(),$item->action_url) !== false)){
                    $item->menu_class = $active_class;
                    $active_class = "";
                    $current_name = $item->name;
                }else if((strpos($item->action_url,$path) !== false) && ($index == 0)){
                    $item->menu_class = $active_class;
                    $active_class = "";
                    $current_name = $item->name;
                }else{
                    $active_class = "";
                }
            }

            /*To disable My Proposal menu if Talent has no proposal*/
            if($section == 'talent-top-after-login' && !empty(\Auth::user()->id_user)){
                $proposal_count = \Models\Proposals::get_talent_proposal_count(\Auth::user()->id_user);
                if($item->name == 'My Proposals' && $proposal_count == 0){
                    #$item->action_url = '#';
                }
                else{
                    $html .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);  
                }
            }
            else{
                $html .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);  
            }

        });

        $html = $html.'</ul></div>';

        $result1 = DB::table('users_menu')->select(['name','action_url','menu_class','menu_icon'])->where(
            [
                'status' => 'active',
                'section' => $uppersection,
                'parent' => 0
            ]
        )->orderBy('menu_order','ASC')->get()->toArray();

        $html1 = '<div class="talent-new-menu"><div class="container-fluid"><ul class="user-profile-links user_submenu">';

        array_walk($result1,function($item,$index) use(&$html1,$active_class,$uppersection,&$current_name,$query_string,$footer,$section){
            if($section == 'talent-top-after-login'){
                $html1 .= '1';
                $html1 .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);
            }else{
                $html1 .= '2';
                $html1 .= sprintf('<li class="%s"><a href="%s%s" class="%s %s">%s</a></li>',($footer == null ? $item->menu_class : 'footer-menu'),($item->action_url !== '#')?url($item->action_url):'javascript:void(0);',$query_string,$active_class,($footer == null ? $item->menu_icon : 'footer-menu'),$item->name);
            }
        });

        $html1 .= '</ul></div></div>';
        $html_before = $html1.'<br/>'.$html;

        if($active_value !== true){
            $current_name = "";
        }

        return sprintf($format,$current_name,$html_before);
    }

    function add_menu_item($menu,$option,$depth,&$html){
        $id_admin = Auth::guard('admin')->user()->id_user;
        $admin_menus = \App\Models\Admins::get_menu_visibility($id_admin);

        foreach ($menu as $item) {
            if(!empty($item['menu_id'])){
                if(in_array($item['menu_id'], $admin_menus)){
                    if(empty($item['disable_list_view'])){

                        if(!empty($item['child'])){
                            $classFlag = false;
                            foreach ($item['child'] as $child) {
                                if($child['class'] == 'active ' ){
                                    $classFlag = true;
                                }else if(strpos(Request::url(),___url($child['action_url'],'backend',false)) === 0){
                                    $classFlag = true;
                                }
                            }
                            if($classFlag == true){
                                $html .= '<li class="active treeview" >';
                            }else{
                                $html .= '<li class="treeview" >';
                            }
                        }else{
                            if(!empty($item['class'])){
                                $html .= '<li class="'.$item['class'].'">';
                            }else if(strpos(Request::url(),___url($item['action_url'],'backend',false)) === 0){
                                $html .= '<li class="active">';
                            }else{
                                $html .= '<li>';
                            }
                        }
                    }

                    if(!empty($item['disable_list_view'])){
                        $html .= '<a href="'.___url($item['action_url'],'backend',false).'" class="'.$item['class'].'">'.$item['menu_icon'].'<span>'.$item['name'].'</span>';
                    }else{
                        $html .= '<a href="'.___url($item['action_url'],'backend',false).'">'.$item['menu_icon'].'<span>'.$item['name'].'</span>';
                    }
                    if($depth == 0 && !empty($item['child'])){
                        $html .= '<span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>';
                    }
                    
                    $html .= '</a>';

                    if(!empty($item['child'])){
                        $depth++;
                        if(!empty($option['depth'][$depth])){
                            $html .= '<ul class="'.$option['depth'][$depth].'">';
                        }else{
                            $html .= '<ul>';
                        }

                        ___menu($item['child'],$option,$depth);
                        $depth--;
                        $html .= '</ul>';
                    }
                    if(empty($item['disable_list_view'])){
                        $html .= '</li>';
                    }
                }
            }
        }
    }

    function check_parent_category_by_id($category_id){
        return DB::table('users_menu')->where(
            array(
                'parent' => $category_id
            )
        )->count();
    }

    function __random_string($length = 10){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function ___firstname($name){
        $name = explode(" ", $name);
        return $name[0];
    }

    function ___lastname($name){
        $name = explode(" ", $name);
        return $name[count($name) - 1];
    }

    function ___decimal($number,$notation = 'no-decimal'){
        if($notation == 'english'){
            return number_format($number);
        }elseif($notation == 'no-decimal'){
            return floor($number);
        }else{
            return number_format($number, 2, '.', '');
        }
    }

    function ___formatDefault($price, $formatting = false, $sign = false, $display = true, $local = true){
        if($formatting == true){
            $formatting = ',';
        }

        $currency_sign = \Cache::get('default_currency');
        $currency   = \Models\Currency::getCurrencyByISOCode($currency_sign);

        if(!empty($currency->sign)){
            if($sign === true){
                $sign = $currency->sign;
            }
            return sprintf("%s%s",$sign, number_format((float)___round(str_replace(",", "", $price),$display), 2, '.', $formatting));
        }else{
            return number_format(___round(str_replace(",", "", $price),$display), 2, '.', $formatting);
        }
    }

    function ___format($price, $formatting = false, $sign = false, $display = true, $local = true){

        $set_format = 2;

        if($formatting == true){
            $formatting = ',';
        }

        $currency_sign = (request()->currency)?:\Session::get('site_currency');
        $currency   = \Models\Currency::getCurrencyByISOCode($currency_sign);
        
        if(!empty($currency->sign)){
            if($sign === true){
                $sign = $currency->sign;
            }

            $this_price = explode('.', $price);
            if(!empty($this_price[1])){
                $set_format = 2;
            }else{
                $set_format = 0;
            }

            $value = number_format((float)___round(str_replace(",", "",$price),$display),$set_format, '.', $formatting);
        
            return sprintf("%s%s",$sign, $value);
        }else{
            return number_format(___round(str_replace(",", "", $price),$display), 2, '.', $formatting);
        }
    }

    function ___formatdoller($price, $formatting = false, $sign = false, $display = true, $local = true){

        if($formatting == true){
            $formatting = ',';
        }

        if($sign === true){
            $sign = "$";
        }
    
        return sprintf("%s%s",$sign, number_format((float)___round(str_replace(",", "", $price),$display), 2, '.', $formatting));
    }

    function ___formatblank($price, $formatting = false, $sign = false, $display = true, $local = true){

        if($formatting == true){
            $formatting = ',';
        }

        $currency_sign = (request()->currency)?:\Session::get('site_currency');
        $currency   = \Models\Currency::getCurrencyByISOCode($currency_sign);
        
        if(!empty($currency->sign)){
            if($sign === true){
                $sign = $currency->sign;
            }

            if(!empty($price)){
                return sprintf("%s%s",$sign, number_format((float)___round($price,$display), 2, '.', $formatting));
            }else{
                return "";
            }
        }else{
            if(!empty($price)){
                return number_format(___round($price,$display), 2, '.', $formatting);
            }else{
                return "";
            }
        }
    }

    function ___currency($price, $formatting = false, $sign = false, $user_id = null){

        $set_format = 2;

        if($formatting == true){
            $formatting = ',';
        }

        if(0){
            if(empty($user_id)){
                $user_country   = \Auth::user()->country;
                $currency       = \Models\Currency::getCurrencyByCountryId($user_country);

                if(empty($currency)){
                    $currency   = \Models\Currency::getCurrencyByCountryCode(___country());
                }
            }else{
                $user           = \Models\Users::findById($user_id,['country']);
                $currency       = \Models\Currency::getCurrencyByCountryId($user['country']);

                if(empty($currency)){
                    $currency   = \Models\Currency::getCurrencyByCountryCode(___country());
                }
            }
        }else{
            $currency_sign = (request()->currency)?:\Session::get('site_currency');

            $currency   = \Models\Currency::getCurrencyByISOCode($currency_sign);
        }
    
        if(!empty($currency->sign)){
            if($sign === true){
                $sign = $currency->sign;
            }

            $this_price = explode('.', $price); 
            if(!empty($this_price[1]) && $this_price[1] != '00'){
                $set_format = 2;
            }else{
                $set_format = 0;
            }

            return sprintf("%s%s",$sign, number_format($price, $set_format, '.', $formatting));
        }else{
            return sprintf("%s",number_format($price, $set_format, '.', $formatting));
        }
    }

    function ___round($price,$display = true){
        return $price;
        if($display === false){
            return round($price,DEFAULT_PRECISION);
        }else{
            return round($price);
        }
    }

    function ___rounding($price,$precision = DEFAULT_PRECISION){
        return round($price,$precision);
    }

    function availablity_type($language = 'en'){
        $availablity_type = [
            [
                'type' => 'available',
                'type_name' => trans('website.W0613',[],NULL,$language),
            ],[
                'type' => 'busy',
                'type_name' => trans('website.W0614b',[],NULL,$language),
            ]
        ];

        return $availablity_type;
    }

    function employment_types($type="all",$fetch = '',$language = 'en'){
        if($type == 'talent_availability'){
            $employment_types = [
                [
                    'type' => 'daily',
                    'type_name' => trans('website.W0036',[],NULL,$language),
                ],[
                    'type' => 'weekly',
                    'type_name' => trans('website.W0037',[],NULL,$language),
                ],[
                    'type' => 'monthly',
                    'type_name' => trans('website.W0038',[],NULL,$language),
                ]
            ];
        }else if($type=="talent_availability_2"){ /*This is without monthly array*/
            $employment_types = [
                [
                    'type' => 'daily',
                    'type_name' => trans('website.W0036',[],NULL,$language),
                ],[
                    'type' => 'weekly',
                    'type_name' => trans('website.W0037',[],NULL,$language),
                ]
            ];
        }else if($type=="post_job"){
            $employment_types = [
                [
                    'type' => 'hourly',
                    'type_name' => trans('website.W0035',[],NULL,$language),
                ],[
                    'type' => 'monthly',
                    'type_name' => trans('website.W0038',[],NULL,$language),
                ],[
                    'type' => 'fixed',
                    'type_name' => trans('website.W0521',[],NULL,$language),
                ]
            ];
        }else if($type=="web_post_job"){
            $employment_types = [
                [
                    'type' => 'hourly',
                    'type_name' => trans('website.W0035',[],NULL,$language),
                ],[
                    'type' => 'monthly',
                    'type_name' => trans('website.W0038',[],NULL,$language),
                ],[
                    'type' => 'fixed',
                    'type_name' => trans('website.W0521',[],NULL,$language),
                ]
            ];
        }else if($type=="talent_personal_information"){
            $employment_types = [
                [
                    'type' => 'hourly',
                    'type_name' => trans('website.W0035',[],NULL,$language),
                ],[
                    'type' => 'monthly',
                    'type_name' => trans('website.W0038',[],NULL,$language),
                ],[
                    'type' => 'fixed',
                    'type_name' => trans('website.W0521',[],NULL,$language),
                ]
            ];
        }else if($type=="web_talent_personal_information"){
            $employment_types = [
                [
                    'type' => 'hourly',
                    'type_name' => trans('website.W0035',[],NULL,$language),
                ],[
                    'type' => 'monthly',
                    'type_name' => trans('website.W0038',[],NULL,$language),
                ],[
                    'type' => 'fixed',
                    'type_name' => trans('website.W0521',[],NULL,$language),
                ]
            ];
        }else if($type == "talent_curriculum_vitae"){
            $employment_types = [
                [
                    'type' => 'fulltime',
                    'type_name' => trans('website.W0625',[],NULL,$language),
                ],[
                    'type' => 'temporary',
                    'type_name' => trans('website.W0106',[],NULL,$language),
                ]
            ];
        }else {
            $employment_types = [
                [
                    'type' => 'daily',
                    'type_name' => trans('website.W0036',[],NULL,$language),
                ],[
                    'type' => 'monthly',
                    'type_name' => trans('website.W0038',[],NULL,$language),
                ],[
                    'type' => 'fixed',
                    'type_name' => trans('website.W0521',[],NULL,$language),
                ]
            ];
        }

        if($fetch == 'keys'){
            return array_column($employment_types, 'type');
        }else if(!empty($fetch)){
            return $employment_types[array_search($fetch, array_column($employment_types, 'type'))]['type_name'];
        }else{
            return $employment_types;
        }
    }

    function days($day_key = "",$language="en"){
        $days = [
            'Mon' => trans('website.W0523',[],NULL,$language),
            'Tue' => trans('website.W0524',[],NULL,$language),
            'Wed' => trans('website.W0525',[],NULL,$language),
            'Thu' => trans('website.W0526',[],NULL,$language),
            'Fri' => trans('website.W0527',[],NULL,$language),
            'Sat' => trans('website.W0528',[],NULL,$language),
            'Sun' => trans('website.W0529',[],NULL,$language),
        ];

        if(!empty($day_key)){
            return $days[trim($day_key)];
        }else{
            return $days;
        }
    }

    function months($month_key = "",$language = "en"){
        $months = [
            'Jan' => trans('website.W0530',[],NULL,$language),
            'Feb' => trans('website.W0531',[],NULL,$language),
            'Mar' => trans('website.W0532',[],NULL,$language),
            'Apr' => trans('website.W0533',[],NULL,$language),
            'May' => trans('website.W0534',[],NULL,$language),
            'Jun' => trans('website.W0535',[],NULL,$language),
            'Jul' => trans('website.W0536',[],NULL,$language),
            'Aug' => trans('website.W0537',[],NULL,$language),
            'Sep' => trans('website.W0538',[],NULL,$language),
            'Oct' => trans('website.W0539',[],NULL,$language),
            'Nov' => trans('website.W0540',[],NULL,$language),
            'Dec' => trans('website.W0541',[],NULL,$language),
        ];

        if(!empty($month_key)){
            return $months[$month_key];
        }else{
            return $months;
        }
    }

    function job_interests($language = 'en'){
        return employment_types('talent_personal_information','',$language);
    }

    function job_types($fetch = '',$language = 'en'){
        $job_types= [
            [
                'type' => 'fixed_price',
                'type_name' => trans('website.W0542',[],NULL,$language),
            ],[
                'type' => 'hourly',
                'type_name' => trans('website.W0035',[],NULL,$language),
            ]
        ];

        if($fetch == 'keys'){
            return array_column($job_types, 'type');
        }elseif(!empty($fetch)){
            return $job_types[array_search($fetch, array_column($job_types, 'type'))]['type_name'];
        }else{
            return $job_types;
        }        
    }

    function job_types_rates_postfix($type,$language = 'en'){
        if($type == 'hourly'){
            return trans('general.M0247',[],NULL,$language);
        }else if($type == 'daily'){
            return trans('general.M0248',[],NULL,$language);
        }else if($type == 'weekly'){
            return trans('general.M0249',[],NULL,$language);
        }else if($type == 'monthly'){
            return trans('general.M0250',[],NULL,$language);
        }else{
            return " ";
        }
    }

    function salary_range(){
        return [
            [
                'type' => 'temporary',
                "min" => "10",
                "max" => "2000",
            ],[
                "type" => "permanent",
                "min" => "10",
                "max" => "2000",
            ]
        ];
    }

    function expertise_levels($fetch = '',$language = 'en'){
        $expertise_levels =  [
            [
                'level' => 'novice',
                'level_name' => trans('website.W0063',[],NULL,$language),
                'level_exp' => trans('website.W0926'),
            ],[
                'level' => 'proficient',
                'level_name' => trans('website.W0064',[],NULL,$language),
                'level_exp' => trans('website.W0927'),
            ],[
                'level' => 'expert',
                'level_name' => trans('website.W0065',[],NULL,$language),
                'level_exp' => trans('website.W0928'),
            ]
        ];

        if($fetch == 'keys'){
            return array_column($expertise_levels, 'level');
        }elseif(!empty($fetch)){
            return $expertise_levels[array_search($fetch, array_column($expertise_levels, 'level'))]['level_name'];
        }else{
            return $expertise_levels;
        } 
    }

    function degree_status($key='',$language = 'en'){
        $degree_status = [
            [
                'level' => 'passed',
                'level_name' => trans('website.W0543',[],NULL,$language),
            ],[
                'level' => 'appearing',
                'level_name' => trans('website.W0544',[],NULL,$language),
            ]
        ];
        if(empty($key)){
            return $degree_status;
        }else{
            return $degree_status[array_search($key, array_column($degree_status,'level'))]['level_name'];
        }         
    }

    function company_profile($select = '',$language = 'en'){
        $company_profile = [
            [
                'level' => 'individual',
                'level_name' => trans('website.W0545',[],NULL,$language)
            ],[
                'level' => 'company',
                'level_name' => trans('website.W0546',[],NULL,$language),
            ]
        ];

        if($select === 'key'){
            return array_column($company_profile, 'level');
        }else{
            return $company_profile;
        }
    }

    function passing_year(){
        $year = date('Y')-100;
        $years = [];
        for($i = date('Y'); $i >= $year; $i--){
            $years[] = [
                'level' => (string)$i,
                'level_name' => (string)$i,
            ];
        }

        return $years;
    }

    function work_rate($language = 'en'){
        return [
            [
                'level' => '05',
                'level_name' => sprintf('$5%s',trans('general.M0247',[],NULL,$language)),
            ],[
                'level' => '10',
                'level_name' => sprintf('$10%s',trans('general.M0247',[],NULL,$language)),
            ],[
                'level' => '15',
                'level_name' => sprintf('$15%s',trans('general.M0247',[],NULL,$language)),
            ],[
                'level' => '20',
                'level_name' => sprintf('$20%s',trans('general.M0247',[],NULL,$language)),
            ],[
                'level' => '25',
                'level_name' => sprintf('$25%s',trans('general.M0247',[],NULL,$language)),
            ],[
                'level' => '30',
                'level_name' => sprintf('$30%s',trans('general.M0247',[],NULL,$language)),
            ],[
                'level' => '35',
                'level_name' => sprintf('$35%s',trans('general.M0247',[],NULL,$language)),
            ]
        ];
    }

    function gender($language = 'en'){
        return [
            [
                'label' => 'male',
                'label_name' => trans('website.W0050',[],NULL,$language),
            ],[
                'label' => 'female',
                'label_name' => trans('website.W0051',[],NULL,$language),
            ],[
                'label' => 'other',
                'label_name' => trans('website.W0052',[],NULL,$language),
            ]
        ];
    }

    function inverse_site_envionment($site_envionment){
        $envionment =  [
            'development'   => 'production',
            'production'    => 'development'
        ];

        return $envionment[$site_envionment];

    }

    function valid_email($email){
        return !empty($email) && preg_match(cleanNonUnicodeSupport('/^[a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]+[.a-z\p{L}0-9!#$%&\'*+\/=?^`{}|~_-]*@[a-z\p{L}0-9]+(?:[.]?[_a-z\p{L}0-9-])*\.[a-z\p{L}0-9]+$/ui'), $email);
    }

    function ___email_settings() {
        $configuration = (array)\App\Lib\Dash::combine((array)\DB::table('config')->get()->toArray(),'{n}.key','{n}.value');
        
        return array(
            'site'              => $configuration['site_name'],
            'slogan'            => $configuration['site_description'],
            'site_link'         => sprintf("%s/",asset('/')),
            'office_address'    => $configuration['office_address'],
            'help_email'        => $configuration['help_email'],
            'info_email'        => $configuration['info_email'],
        );
    }

    function ___mail_sender($email,$fullname,$template_code,$data) {
        $template = \DB::table('emails')->select(['subject','content','variables'])->where('title',$template_code)->where('language',app()->getLocale())->first();
        if(!empty($template)){
            $variables  = explode(',',$template->variables);
            $subject    = $template->subject;           
            $body       = $template->content;

            foreach ($variables as $item) {
                $subject    = str_replace($item,$data[str_replace(array('{','}'),'', $item)],stripslashes(html_entity_decode($subject)));
                $body       = str_replace($item,$data[str_replace(array('{','}'),'', $item)],stripslashes(html_entity_decode($body)));
            }

            $body = str_replace('{site}',$data['site'],$body);

            $configuration = ___configuration(['smtp_mode','smtp_host','smtp_port','smtp_username','smtp_password','site_name']);

            if($configuration['smtp_mode'] == 'ssl'){
                $configuration['smtp_host'] = sprintf("ssl://%s",$configuration['smtp_host']);
            }

            \Config::set(['port' => $configuration['smtp_port'],'host' => $configuration['smtp_host'],'username' => $configuration['smtp_username'],'password' => $configuration['smtp_password']]);

            $sender = ['subject' => $subject,'email' => $email,'name' => $fullname,'from' => ['address' => $configuration['smtp_username'],'name' => $configuration['site_name']]];
            
            if(!empty($body) && !empty($email)){
                if($template_code == 'notification' || $template_code == 'invite_talent'){
                    $send = Mail::queue('emails.default', ['body' => $body], function($message) use ($sender){
                        $message->to(
                            $sender['email'], 
                            $sender['name']
                        )
                        ->subject($sender['subject'])
                        ->from(
                            $sender['from']['address'],
                            $sender['from']['name']
                        );
                    });
                }else{
                    $send = Mail::send('emails.default', ['body' => $body], function($message) use ($sender){
                        $message->to(
                            $sender['email'], 
                            $sender['name']
                        )
                        ->subject($sender['subject'])
                        ->from(
                            $sender['from']['address'],
                            $sender['from']['name']
                        );
                    });
                }
            }

            return $send;
        }
    }

    function ___getFirstName($name){
        $name = explode(" ", $name);
        return $name[0];
    }

    function ___getLastName($name){
        $name = explode(" ", $name);
        return $name[count($name) - 1];
    }

    function ___configuration($keys = []){
        $config_table = \DB::table('config');

        if(!empty($keys)){
            $config_table->whereIn('key',$keys);
        }

        $configData = $config_table->get()->toArray();
        
        return \App\Lib\Dash::combine((array)$configData,'{n}.key','{n}.value');
    }

    function ___http($url) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        return $url;
    }

    function ___filter_null(&$data) {
        array_walk_recursive($data, function(&$item){
            if ($item === ''){
                $item = NULL;
            }else{
                $item = trim($item);
            }
        });
    }

    /**
     * Delete unicode class from regular expression patterns
     * @param string $pattern
     * @return string pattern
     */
    function cleanNonUnicodeSupport($pattern){
        return preg_replace('/\\\[px]\{[a-z]{1,2}\}|(\/[a-z]*)u([a-z]*)$/i', '$1$2', $pattern);
    }

    function _get_NumcouponCode($code_len = '5') {
        $chars = "0123456789";
        $code = "";
        for ($i = 0; $i < $code_len; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $code;
    }

    function _get_couponCode($code_len = '5') {
        $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $code = "";
        for ($i = 0; $i < $code_len; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $code;
    }

    function luhn_check($number) {

        // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
        $number=preg_replace('/\D/', '', $number);

        // Set the string length and parity
        $number_length=strlen($number);
        $parity=$number_length % 2;

        // Loop through each digit and do the maths
        $total=0;
        for ($i=0; $i<$number_length; $i++) {
            $digit=$number[$i];
            // Multiply alternate digits by two
            if ($i % 2 == $parity) {
              $digit*=2;
              // If the sum is two digits, add them together (in effect)
              if ($digit > 9) {
                $digit-=9;
              }
            }
            // Total up the digits
            $total+=$digit;
        }

        // If the total mod 10 equals 0, the number is valid
        return ($total % 10 == 0) ? TRUE : FALSE;

    }

    function getMonthList(){
        $months = array();
        for ($i = 0; $i < 12; $i++) {
            $timestamp = mktime(0, 0, 0, date('n') - $i, 1);
            $months[date('n', $timestamp)] = date('F', $timestamp);
        }

        ksort($months);
        return $months;
    }

    function ___date_range($arr = array()){
        // $day_numbers = range(1, 31); $option = "";
        $day_numbers = $arr; $option = "";
        
        array_walk($day_numbers, function($item) use(&$option){
            if(gettype($item) == "string"){
                $option .= '<option value="'.$item.'">'.$item.'</option>';
            }else{
                $option .= '<option value="'.$item.'">'.sprintf("%'.02d",$item).'</option>';
            }
        });

        return $option;
    }

    function ___dropdown_options($options,$empty = "",$selected = "",$padder = false){        
        $html = sprintf('<option value="">%s</option>',(!empty($empty))?$empty:trans('general.M0109'));
        
        if(gettype($selected) == 'string' || gettype($selected) == 'integer' || gettype($selected) == 'NULL'){
            $selected = (array)$selected;
        }
        
        array_walk($options, function($item,$key) use($selected,&$html,$padder){
            if(empty($padder)){
                $html .= sprintf('<option value="%s"%s>%s</option>',$key,(in_array($key,$selected))?' selected':'',$item);
            }else{                
                $html .= sprintf('<option value="%\'.02d"%s>%\'.02d</option>',$key,(in_array($key,$selected))?' selected':'',$item);
            }
        });
        return $html;
    }

    function ___dropdown_options2($options,$empty = "",$selected = "",$padder = false){        
        // $html = sprintf('<option value="">%s</option>',(!empty($empty))?$empty:trans('general.M0109'));
        
        if(gettype($selected) == 'string' || gettype($selected) == 'integer' || gettype($selected) == 'NULL'){
            $selected = (array)$selected;
        }
        
        array_walk($options, function($item,$key) use($selected,&$html,$padder){
            if(empty($padder)){
                $html .= sprintf('<option value="%s"%s>%s</option>',$key,(in_array($key,$selected))?' selected':'',$item);
            }else{                
                $html .= sprintf('<option value="%\'.02d"%s>%\'.02d</option>',$key,(in_array($key,$selected))?' selected':'',$item);
            }
        });
        return $html;
    }

    function ___error_sanatizer($data){        
        return $data;
    }

    function ___range($options,$type=""){
        if($type == "multi_dimension")
        {
            $range_array = (array)\App\Lib\Dash::combine(
                $options,
                '{n}.level',
                '{n}.level_name'
            );
        }else{
            $range_array = (array)\App\Lib\Dash::combine(
                $options,
                '{n}',
                '{n}'
            );
        }

        return $range_array;
    }

    function file_name($original_file_name,$original_extension){
        /*TRUNCATING CHARS*/
        $file_name = ___truncate($original_file_name);

        /*SLUGIFY STRING*/
        $file_name = strtolower(trim(preg_replace('/[^A-Za-z0-9-.]+/', '-', $file_name)));

        /*REMOVE DUPLICATE -*/
        $file_name = preg_replace('~-+~', '-', $file_name);

        return strtoupper($original_extension).'-'.__random_string(5).'-'.$file_name;
    }

    function get_file_size($size,$conversion){
        if($conversion == 'KB'){
            return sprintf("%s KB",number_format(($size/1024),2));
        }

        return (string)$size;
    }

    function slugify($text){
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    function upload_file($request, $file_key_name = 'file', $folder = 'uploads/', $thumbnail = false, $resize = array()) {
        $file       = $request->file($file_key_name);
        $file_size  = get_file_size($file->getClientSize(),'KB');
        $extension  = $file->getClientOriginalExtension();
        $file_name  = file_name($file->getClientOriginalName(),$extension);
        
        if($thumbnail == true){
            @mkdir(public_path(sprintf('%s%s',$folder,'thumbnail/')),0755);
            $thumbnail_image    = \Image::make($file->getRealPath())->fit(CROP_WIDTH)->crop(CROP_WIDTH,CROP_HEIGHT)->save(public_path(sprintf('%s%s%s',$folder,'thumbnail/',$file_name)));
        }

        if(!empty($resize)){
            @mkdir(public_path(sprintf('%s%s',$folder,'resize/')),0755);
            $thumbnail_image    = \Image::make($file->getRealPath())->fit($resize['width'], $resize['height'])->resize($resize['width'],$resize['height'])->save(public_path(sprintf('%s%s%s',$folder,'resize/',$file_name)));
        }

        $file->move($folder,$file_name);

        return [
            'file_path' => public_path(sprintf('%s%s',$folder,$file_name)),
            'file_url' => asset(sprintf('%s%s',$folder,$file_name)),
            'filename' => $file_name,
            'thumbnail' => asset(sprintf('%s%s%s',$folder,'thumbnail/',$file_name)),
            'size' => $file_size,
            'extension' => $extension,
        ];
    }

    function valid_social_keys() {
        return [
            'facebook_id',
            'twitter_id',
            'instagram_id',
            'linkedin_id',
            'googleplus_id',
        ];
    }

    function get_file_url($file_data) {

        if(strpos(@$file_data['filename'],'http') !== false){
            return $file_data['filename'];
        }else{
            
            if(!empty($file_data)){
                if(file_exists(public_path(sprintf("%s%s",$file_data['folder'],$file_data['filename'])))){
                    $file_url = asset(sprintf("%s%s",$file_data['folder'],$file_data['filename']));
                }else{
                    $file_url = asset(sprintf('images/%s',DEFAULT_AVATAR_IMAGE));
                }
            }else{
                $file_url = asset(sprintf('images/%s',DEFAULT_AVATAR_IMAGE));
            }

            return str_replace("/profile", "/profile/thumbnail", $file_url);
        }
    }

    function ___replace_last( $search , $replace , $str ) {
        if( ( $pos = strrpos( $str , $search ) ) !== false ) {
            $search_length  = strlen( $search );
            $str    = substr_replace( $str , $replace , $pos , $search_length );
        }
        return $str;
    }

    function added_skills($skills){
        $i = 1;
        $html = '';
        array_walk($skills, function($item) use(&$i, &$html){
            if($i <= 3){
                $html .=  sprintf('%s %s %s','<a href="javascript:void(0);" >',$item,'</a>');
            }
            else
            {
                $html .= sprintf('%s %s %s','<a href="javascript:void(0);" style="display: none;">',$item,'</a>');
            }
            $i++;
        });

        if($i > 4){
            $html .= '<span class="show-more">+'.(count($skills)-3).' '.trans('website.W0440').'</span>';
        }
        return $html;
    }

    function ___availability_list($availability, $html = NULL) {
        array_walk($availability, function(&$item) use(&$html){
            if($item['repeat'] == 'weekly'){
                $item['availability_day'] = sprintf("WEEKLY ON %s  (".ucfirst($item['availability_type']).")",___replace_last(',',' AND ',str_replace(array_values(days()),array_keys(days()),implode(",", $item['availability_day']))));
                $item['deadline'] = sprintf("until %s",___d($item['deadline']));
            }else if($item['repeat'] == 'monthly'){
                $item['availability_day'] = 'MONTHLY ('.ucfirst($item['availability_type']).')';
                $item['deadline'] = sprintf("until %s",___d($item['deadline']));
            }else{
                $item['availability_day'] = 'DAILY ('.ucfirst($item['availability_type']).')';
                $item['deadline'] = sprintf("until %s",___d($item['deadline']));
            }
            
            $html .= sprintf(
                AVAILABILITY_TEMPLATE,
                $item['id_availability'],
                ___time($item['from_time']),
                ___time($item['to_time']),
                $item['availability_day'],
                $item['deadline'],
                url(sprintf('/talent/profile/availability/edit?id_availability=%s',$item['id_availability'])),
                $item['id_availability'],
                asset('/'),
                url(sprintf('/talent/profile/availability/delete?id_availability=%s',$item['id_availability'])),
                $item['id_availability'],
                asset('/')
            );
        }); 

        return $html;
    }

    function ___date_difference($startdate,$enddate) {
        // $date = date_diff(date_create($startdate),date_create($enddate));
        
        // $difference = $date->format("%a ".trans('general.M0184'));

        // return $difference;

        // if(($difference%7) === 0){
        //     return sprintf("%s %s",$difference,($difference != 7)?trans('general.M0182'):trans('general.M0181'));
        // }else if(($difference%30) === 0){
        //     return sprintf("%s %s",$difference,($difference != 30)?trans('general.M0186'):trans('general.M0185'));
        // }else if(($difference%365) === 0){
        //     return sprintf("%s %s",$difference,($difference != 365)?trans('general.M0188'):trans('general.M0187'));
        // }else{
        //     return sprintf("%s %s",$difference,($difference != 1)?trans('general.M0184'):trans('general.M0183'));
        // }

        if(($startdate !== '0000-00-00 00:00:00' || $startdate !== '0000-00-00') && ($enddate !== '0000-00-00 00:00:00' || $enddate !== '0000-00-00')){
            return sprintf("%s - %s",___d($startdate),___d($enddate));
        }else{
            return trans('website.W0039');
        }
    }

    function ___get_total_days($startdate,$enddate) {
        if($startdate == $enddate){
            return 1;
        }else{
            $date = date_diff(date_create($startdate),date_create($enddate));
            $difference = $date->format("%a");
            
            return $difference+1;
        }
    }

    function ___ellipsis($string,$length) {
        return strlen($string) > $length ? substr($string,0,$length)."..." : $string;
    }

    function ___tags($data,$format,$seperator = ", ",$callback = false,$type = NULL,$limit = NULL) {

        if(gettype($data) == 'string'){
            return sprintf($format,str_replace(",", $seperator, $data)); 
        }else{
            $tags = array_map(function($item) use($format,$callback,$type,$seperator){
                if($callback === false){
                    return sprintf($format,str_replace("/profile", "/profile/thumbnail", $item));    
                }else{
                    if($callback == 'employment_types'){
                        return sprintf($format,$callback($type,$item));    
                    }else{
                        return sprintf($format,$callback($item));    
                    }
                }
            }, $data);

            if(empty($limit)){
                return implode($seperator, $tags);
            }else{
                if(empty(count($tags))){
                    return '';
                }else if(count($tags) > $limit){
                    return implode($seperator, array_slice($tags, 0, $limit)).' <span class="text-red">+'.(count($tags)-$limit).' '.trans('website.W0440').'</span>';
                }else{
                    return implode($seperator, array_slice($tags, 0, $limit));
                }
            }
        }
    }

    function ___cache($key,$value = ""){
        if(empty($value)){
            return \Cache::get($key);
        }else if(!empty(\Cache::get($key)[$value])){
            return \Cache::get($key)[$value];
        }else{
            return N_A;
        }
    }

    function ___ucfirst($name){
        return ucfirst($name);
    }

    function ___ratingstar($rating,$image = "filled"){
        $html = "";

        for($x = 1; $x <= $rating; $x++) {
            $html .='<img src="'.asset("images/{$image}/star-filled.png").'" />';
        }

        if (strpos($rating,'.') && ((int)$rating != (float)$rating)) {
            $html .='<img src="'.asset("images/{$image}/star-half-filled.png").'" />';
            $x++;
        }

        while ($x <= 5) {
            $html .='<img src="'.asset("images/{$image}/star-grey.png").'" />';
            $x++;
        }

        return $html;
    }

    function ___filter($type,$fetch = 'all'){
        
        switch ($type) {
            case 'talent_sorting_filter':
            case 'premium_filter':{
                $filter_array = array(
                    'users.name-asc'        => trans('website.W0336'),
                    'users.name-desc'       => trans('website.W0337'),
                    # 'users.workrate-asc'    => trans('website.W0627'),
                    # 'users.workrate-desc'   => trans('website.W0628'),
                    # 'users.workrate-asc'    => trans('website.W0627'),
                    # 'users.workrate-desc'   => trans('website.W0628'),

                );

                if($fetch == 'all'){
                    $filter = array();$i = 0;
                    foreach ($filter_array as $filter_key => $filter_name) {
                        $filter[$i]['filter_key'] = $filter_key;
                        $filter[$i++]['filter_name'] = $filter_name;
                    }
                    return $filter;
                }else{
                    return array_keys($filter_array);
                }
                break;
            }
            case 'job_sorting_filter':{
                $filter_array = array(
                    'projects.created-asc'  => trans('website.W0629'),
                    'projects.created-desc' => trans('website.W0630'),
                    'projects.price-asc'    => trans('website.W0615'),
                    'projects.price-desc'   => trans('website.W0616'),
                );

                if($fetch == 'all'){
                    $filter = array();$i = 0;
                    foreach ($filter_array as $filter_key => $filter_name) {
                        $filter[$i]['filter_key'] = $filter_key;
                        $filter[$i++]['filter_name'] = $filter_name;
                    }
                    return $filter;
                }else{
                    return array_keys($filter_array);
                }
                break;
            }
            case 'proposal_sorting':{
                $filter_array = array(
                    'name-asc'      => trans('website.W0632'),
                    'name-desc'     => trans('website.W0633'),
                    'created-asc'   => trans('website.W0634'),
                    'created-desc'  => trans('website.W0635'),
                );

                if($fetch == 'all'){
                    $filter = array();$i = 0;
                    foreach ($filter_array as $filter_key => $filter_name) {
                        $filter[$i]['filter_key'] = $filter_key;
                        $filter[$i++]['filter_name'] = $filter_name;
                    }
                    return $filter;
                }else{
                    return array_keys($filter_array);
                }
                break;
            }
            case 'proposal_filter':{
                $filter_array = array(
                    'tagged_listing'    => trans('website.W0341'),
                    'accepted_proposal' => trans('website.W0780'),
                    'applied_proposal'  => trans('website.W0756'),
                    'declined_proposal' => trans('website.W0755'),
                );

                if($fetch == 'all'){
                    $filter = array();$i = 0;
                    foreach ($filter_array as $filter_key => $filter_name) {
                        $filter[$i]['filter_key'] = $filter_key;
                        $filter[$i++]['filter_name'] = $filter_name;
                    }
                    return $filter;
                }else{
                    return array_keys($filter_array);
                }
                break;
            }
            default:{
                return [];
                break;
            }
        }
    }

    function ___decodefilter($filter_key){
        $result = "";
        if(!empty($filter_key)){
            $filter_keys =  (array) explode("-", $filter_key);

            if(!empty($filter_keys)){
                $result = sprintf("%s %s",$filter_keys[0],(!empty($filter_keys[1]))?strtoupper($filter_keys[1]):'ASC');
            }else{
                $result = sprintf("%s %s",$filter_key,'ASC');
            }
        }

        return $result;
    }

    function ___dates_between($start_date, $end_date, $format="Y-m-d"){
        $dates = array();
        $days = (abs(strtotime("$end_date") - strtotime("$start_date")) / 86400);
       
        if($days < 1){
            $dates[] = date($format, strtotime("{$start_date}"));
            return $dates;
        }
       
        $total_days = round(abs(strtotime($end_date) - strtotime($start_date)) / 86400, 0) + 1;
        if($total_days < 0) { return false; }
       
        for($day=0; $day<$total_days; $day++){
           $dates[] = date($format, strtotime("{$start_date} + {$day} days"));
        }

        return $dates;
    }

    function ___days_between($start_date, $end_date, $daynames = [],$format = "Y-m-d"){
        $dates = array();
        $days = (abs(strtotime("$end_date") - strtotime("$start_date")) / 86400);
       
        if($days < 1){
            $dates[] = date($format, strtotime("{$start_date}"));
            return $dates;
        }
       
        $total_days = round(abs(strtotime($end_date) - strtotime($start_date)) / 86400, 0) + 1;
        if($total_days < 0) { return false; }
       
        for($day = 0; $day < $total_days; $day++){
            $timestamp = strtotime("{$start_date} + {$day} days");

            if(in_array(date('D',$timestamp), $daynames)){
                $dates[] = date($format, strtotime("{$start_date} + {$day} days"));
            }
        }

        return $dates;
    }
    
    function ___month_between($start_date, $end_date,$format="Y-m-d"){
        $dates = array();
        
        $start    = new DateTime($start_date);
        $end      = new DateTime($end_date);
        $interval = DateInterval::createFromDateString('1 month');
        $period   = new DatePeriod($start,$interval,$end);
        
        foreach ($period as $dt) {
           $dates[] = sprintf("%s-%s",$dt->format("Y-m"),date('d',strtotime($start_date)));
        }

        return $dates;
    }

    function ___get_scalar_availability($availability){
        $json = [];
        foreach ($availability as $item) {
            if($item['repeat'] == 'daily'){
                $dates_between = ___dates_between($item['availability_date'],$item['deadline']);
                
                $daily_availability = array_map(
                    function($date) use($item){ 
                        return array(
                            'id_availability' => $item['repeat_group'],
                            'start' => sprintf("%sT%s",$date,$item['from_time']),
                            'end'   => sprintf("%sT%s",$date,$item['to_time']),
                            'title' => sprintf("%s - %s\n%s",___t($item['from_time']),___t($item['to_time']),sprintf("%s %s",'Daily', sprintf("\nuntil %s",___d($item['deadline'])))),
                            'type' => employment_types('talent_personal_information',$item['repeat']),
                            'description' => sprintf("%s - %s",___t($item['from_time']),___t($item['to_time'])),
                            'availability_type' => ($item['availability_type']),
                        ); 
                    }, 
                    $dates_between
                );

                $json = array_merge($daily_availability,$json);
            }else if($item['repeat'] == 'weekly'){
                
                $days_between = ___days_between($item['availability_date'],$item['deadline'],$item['availability_day']);
                
                $weekly_availability = array_map(
                    function($date) use($item){ 
                        return array(
                            'id_availability' => $item['repeat_group'],
                            'start' => sprintf("%sT%s",$date,$item['from_time']),
                            'end'   => sprintf("%sT%s",$date,$item['to_time']),
                            'title' => sprintf(
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
                                            implode(", ", $item['availability_day'])
                                        )
                                    ),
                                    sprintf("until %s",___d($item['deadline']))
                                )
                            ),
                            'type' => employment_types('talent_personal_information',$item['repeat']),
                            'description' => sprintf("%s - %s",___t($item['from_time']),___t($item['to_time'])),
                        ); 
                    }, 
                    $days_between
                );

                $json = array_merge($weekly_availability,$json);
            }else if($item['repeat'] == 'monthly'){
                
                $days_between = ___month_between($item['availability_date'],$item['deadline']);
                
                $weekly_availability = array_map(
                    function($date) use($item){ 
                        return array(
                            'id_availability' => $item['repeat_group'],
                            'start' => sprintf("%sT%s",$date,$item['from_time']),
                            'end'   => sprintf("%sT%s",$date,$item['to_time']),
                            'title' => sprintf("%s - %s\n%s",___t($item['from_time']),___t($item['to_time']),sprintf("%s %s",'Monthly', sprintf("until %s",___d($item['deadline'])))),
                            'type' => employment_types('talent_personal_information',$item['repeat']),
                            'description' =>  sprintf("%s - %s",___t($item['from_time']),___t($item['to_time'])),
                        ); 
                    }, 
                    $days_between
                );

                $json = array_merge($weekly_availability,$json);
            }
        }

        return($json);
    }

    function ___convert_date($date,$type = 'MYSQL',$format = 'Y-m-d'){
        if(!empty($date)){
            if($type == 'MYSQL'){
                return date($format,strtotime(str_replace("/", "-", $date)));
            }else{
                return date($format,strtotime($date));
            }
        }else{
            return '';
        }
    }

    function ___print($data){
        if(!empty($data)){
            return $data;
        }else{
            return N_A;
        }
    }

    function back_url_name($path=""){
        $back_url_name = [
            url('employer/my-jobs/current')      => sprintf(trans('website.W0238'),trans('website.W0239')),
            url('employer/my-jobs/scheduled')    => sprintf(trans('website.W0238'),trans('website.W0240')),
            url('employer/my-jobs/completed')    => sprintf(trans('website.W0238'),trans('website.W0241')),
            url('employer/my-jobs/submitted')    => sprintf(trans('website.W0238'),trans('website.W0242')),
        ];

        if(!empty($back_url_name[$path])){
            return $back_url_name[$path];
        }else{
            return sprintf(trans('website.W0238'),trans('website.W0239'));
        }
    }

    function range_filter($currency){
        return [
            'temporary_salary_range' =>[
                'minimum' => \Models\Listings::getConvertPrice(TEMPORARY_PRICE_MIN_LENGTH, $currency),
                'maximum' => \Models\Listings::getConvertPrice(TEMPORARY_PRICE_MAX_LENGTH, $currency),
                'price_unit' => $currency
            ],
            'permanent_salary_range' =>[
                'minimum' => \Models\Listings::getConvertPrice(PERMANENT_SALARY_MIN_LENGTH, $currency),
                'maximum' => \Models\Listings::getConvertPrice(PERMANENT_SALARY_MAX_LENGTH, $currency),
                'price_unit' => $currency
            ],
        ];
    }

    function time_difference_in_hours($start_time, $end_time, $std_format = false){       
        $start_time = new DateTime($start_time);
        $end_time = new DateTime($end_time);
        $interval = $start_time->diff($end_time);
        
        return (int)$interval->format('%h');
    }

    function time_difference($start_time, $end_time){

        if(strtotime($start_time) <= strtotime($end_time)){
            $start_time = new DateTime($start_time);
            $end_time = new DateTime($end_time);
            $interval = $start_time->diff($end_time);
         
            return sprintf("%'.02d:%'.02d:%'.02d", $interval->format('%h'),$interval->format('%i'),$interval->format('%s'));
        }else{
            return "00:00:00";
        }
    }

    function timetosecond($time){       
        $time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $time);

        sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    function get_time_difference($time, $format = 'y'){       
        return (new DateTime)->diff(new DateTime($time))->$format;
    }

    function ___agoday($timestamp,$date_format = "d/m/Y",$time_format = "h:i A"){       
        $TIMEZONE = ___current_timezone();
        $current_date = \Carbon\Carbon::parse(date('Y-m-d H:i:s'));
        $current_date = $current_date->tz($TIMEZONE);
    
        $difference = ___get_total_days(date('Y-m-d',strtotime($current_date)),date('Y-m-d',strtotime($timestamp)));
        
        $TIMEZONE = ___current_timezone();
        $selected_date = \Carbon\Carbon::parse($timestamp);
        $selected_date = $selected_date->tz($TIMEZONE);
    
        $date = $selected_date->toDateTimeString();

        if(0/*$difference >= 1*/){
            return date($date_format,strtotime($date));
        }else{
            return date($time_format,strtotime($date));
        }
    }

    function language(){  
        return \Cache::get('languages');
    }

    function currencies(){
        return \Cache::get('currencies');
    }

    function ___image_base_url(){       
        return asset('/');
    }

    function ___e($text){
        $email_pattern      = '/[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/';
        /* $mobile_pattern     = '/(\+\d{1,3}[- ]?)?\d{8,10}/'; */

        $text = str_ireplace(
            array_keys(
                \Cache::get('abusive_words')
            ), 
            array_values(
                \Cache::get('abusive_words')
            ), 
            $text
        );

        $text = preg_replace_callback($email_pattern, "___encode_email", $text);
        
        /* REMOVED - CLIENT FEEDBACK 3rd NOVEMBER 2017 */
        /* $text = preg_replace_callback($mobile_pattern, "___encode_mobile", $text);*/
        
        return $text;
    }


    function ___encode_mobile($matches){
        return '*****';
    }

    function ___encode_email($matches){
        $masked_string = [];
        
        if(!empty($matches[0])){
            return  ___mask_email($matches[0]);
        }else{
            return '*****';       
        }
        
    }

    function ___mask_email($email, $mask_char = "*", $percent = 90) { 
        list( $user, $domain ) = preg_split("/@/", $email );

        $len = strlen( $user ); 
        $mask_count = floor( $len * $percent /100 ); 
        $offset = floor( ( $len - $mask_count ) / 2 ); 
        $masked = substr( $user, 0, $offset).str_repeat( $mask_char, $mask_count ).substr( $user, $mask_count+$offset ); 

        return( $masked.'@'.$domain ); 
    } 

    function filter_bad_words($matches) {
        $bad_words = \Cache::get('abusive_words');

        $replace = array_key_exists($matches[0], $bad_words) ? $bad_words[$matches[0]] : false;
        return $replace ?: $matches[0];
    }

    function ___get_notification_url($keyword,$response_json){
        $response = json_decode($response_json,true);

        switch ($keyword) {
            case 'JOB_ACCEPTED_BY_EMPLOYER':
            case 'JOB_DISPUTED_BY_EMPLOYER':
            case 'JOB_COMPLETED_BY_EMPLOYER':
            case 'JOB_REJECTED_BY_EMPLOYER':
            case 'JOB_STARTED_BY_EMPLOYER':
            case 'JOB_FOR_SELECTED_TALENT':
            case 'JOB_INVITATION_SENT_BY_EMPLOYER':
            case 'JOB_CANCELLED_BY_EMPLOYER':
                $redirect = sprintf('talent/project/details?job_id=%s',___encrypt($response['project_id']));
                break;
            case 'JOB_UPDATED_BY_EMPLOYER':
                $redirect = sprintf('talent/find-jobs/proposal?job_id=%s&proposal_id=%s&action=edit',___encrypt($response['project_id']),___encrypt($response['proposal']));
                break;
            case 'JOB_PAYMENT_RELEASED_BY_CROWBAR':
                $redirect = sprintf('talent/wallet/received');
                break;
            case 'JOB_RAISE_DISPUTE_RECEIVED':
            case 'JOB_RAISE_DISPUTE_RECEIVED_REPLY':
                $redirect = sprintf('project/dispute/details?job_id=%s',___encrypt($response['project_id']));
                break;
            case 'JOB_CHAT_REQUEST_ACCEPTED_BY_EMPLOYER':
                $redirect = sprintf('talent/chat?receiver_id=%s',___encrypt($response['receiver_id']));
                break;
            case 'JOB_CHAT_REQUEST_REJECTED_BY_EMPLOYER':
                $redirect = sprintf('talent/chat?receiver_id=%s',___encrypt($response['receiver_id']));
                break;
            case 'JOB_CHAT_REQUEST_SENT_BY_TALENT':
                $redirect = sprintf('employer/chat?receiver_id=%s',___encrypt($response['chat'][0]['id_chat_request']));
                break;
            case 'JOB_PROPOSAL_SUBMITTED_BY_TALENT':
            case 'JOB_PROPOSAL_EDITED_BY_TALENT':
                $redirect = sprintf('employer/project/proposals/talent?proposal_id=%s&project_id=%s',___encrypt($response['proposal_id']),___encrypt($response['project_id']));
                break;
            case 'JOB_APPLICATION_SUBMITTED_BY_TALENT':
                $redirect = sprintf('employer/project/proposals/detail?id_project=%s',___encrypt($response['project_id']));
                break;
            case 'JOB_STARTED_BY_TALENT':
            case 'JOB_COMPLETED_BY_TALENT':
            case 'JOB_DISPUTED_BY_TALENT':
            case 'JOB_REQUEST_PAYOUT_BY_TALENT':
                $redirect = sprintf('employer/project/details?job_id=%s',___encrypt($response['project_id']));
                break;
            case 'JOB_REVIEW_REQUEST_BY_TALENT':
                $redirect = sprintf('employer/project/received/reviews?job_id=%s',___encrypt($response['project_id']));
                break;
            case 'JOB_REVIEW_REQUEST_BY_EMPLOYER':
                $redirect = sprintf('talent/project/received/reviews?job_id=%s',___encrypt($response['project_id']));
                break;
            default:
                $redirect = "/";
                break;
        }

        return url($redirect);
    }    

    function ___devices(){
        $devices = array(
            'android',
            'iphone'
        );

        return $devices;
    }

    function ___calculate_commission($price,$commission, $commission_type = 'per'){
        return 0;
    }

    function ___talent_commission($price){
        $commission         = ___cache('configuration')['commission'];
        $commission_type    = ___cache('configuration')['commission_type'];

        if($commission_type == 'per'){
            $calculated_commission = ___format(round(((($price*$commission)/100)),2));
        }else{
            $calculated_commission = ___format(round(($commission),2));
        }

        return $calculated_commission;
    }

    
    function ___employer_commission($price){
        $commission         = ___cache('configuration')['raise_dispute_commission'];
        $commission_type    = ___cache('configuration')['raise_dispute_commission_type'];

        if($commission_type == 'per'){
            $calculated_commission = ___format(round(((($price*$commission)/100)),2));
        }else{
            $calculated_commission = ___format(round(($commission),2));
        }

        return $calculated_commission;
    }

    function ___project_price($price,$price_max,$bonus,$price_unit,$employment,$html = TRUE){
        if(empty($price_max)){
            $price_tag = ___format($price,true,true);
        }else{
            $price_tag = sprintf("%s - %s",___format($price,true,true),___format($price_max,true,true));
        }

        $result = sprintf("%s %s",$price_tag, job_types_rates_postfix($employment));

        if($html !== false){
            return $result;
        }else{
            return strip_tags($result);
        }
    }

    function ___readable($key,$capitalize = false){
        if($capitalize == false){
            return str_replace(array('_'), ' ', $key);
        }else{
            return ucwords(str_replace(array('_',',','-'), array(' ',', ',' '), $key));
        }
    }
    
    function ___get_transaction_id(){
        return sprintf("TXN%s",__random_string(10));
    }

    function get_project_template($project,$type = 'employer'){
        $closed_class = "";
        if($project->project_status == 'closed'){
            $closed_class = 'job-completed';
        }

        $html = '<div class="content-box find-job-listing '.$closed_class.' clearfix">';
            $html .= '<div class="find-job-left">';
                $html .= '<div class="content-box-header clearfix">';
                    // $html .= '<img src="'.str_replace("/profile", "/profile/thumbnail", $project->company_logo).'" alt="profile" class="job-profile-image">';
                    $html .= '<img src="'.$project->company_logo.'" alt="profile" class="job-profile-image">';
                    $html .= '<div class="contentbox-header-title">';
                        if($type == 'employer'){
                            $html .= '<h3><a href="'.'javascript:void(0);'/*url(sprintf('%s/my-jobs/job_details?job_id=%s',EMPLOYER_ROLE_TYPE,___encrypt($project->id_project)))*/.'">'.$project->title.'</a></h3>';
                        }else{
                            $html .= '<h3><a href="'.url(sprintf('%s/find-jobs/details?job_id=%s',TALENT_ROLE_TYPE,___encrypt($project->id_project))).'">'.$project->title.'</a></h3>';
                        }

                        $html .= '<span class="company-name">'.$project->company_name.'</span>';
                    $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="content-box-description">';
                    if(strlen($project->description) > READ_MORE_LENGTH){
                        $html .= '<p>'.___e(substr(strip_tags($project->description), 0,READ_MORE_LENGTH)).'..</p>';
                    }else{
                        $html .= '<p>'.___e(strip_tags($project->description)).'</p>';
                    }
                $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="find-job-right">';
                $html .= '<div class="contentbox-price-range">';
                    $html .= '<span>';
                    $html .= ___format($project->price,true,true);
                    $html .= '<br>';
                    if($project->employment == 'fixed'){
                        $html .= '<span class="label-green color-grey">'.ucfirst($project->employment).'</span>';
                    }else{
                        $html .= '<span class="label-green color-grey">'.job_types_rates_postfix($project->employment).'</span>';
                        // $html .= '<span class="small-price-type">'.job_types_rates_postfix($project->employment).'</span>';
                    }
                    $html .= '</span>';
                    
                    if(!empty($project->last_viewed)){
                        $html .= '<span class="last-viewed-icon active"></span>';
                    }
                $html .= '</div>';
                $html .= '<div class="contentbox-minutes clearfix">';
                    if(0/*$type == 'talent'*/){
                        if(!empty($project->is_saved == DEFAULT_YES_VALUE)){
                            $html .= '<a href="javascript:void(0);" class="save-icon active" data-request="favorite-save" data-url="'.url(sprintf('%s/jobs/save-job?job_id=%s',TALENT_ROLE_TYPE,$project->id_project)).'"></a>';
                        }else{
                            $html .= '<a href="javascript:void(0);" class="save-icon" data-request="favorite-save" data-url="'.url(sprintf('%s/jobs/save-job?job_id=%s',TALENT_ROLE_TYPE,$project->id_project)).'"></a>';
                        }    
                    }else{
                        $html .= '<br>';
                    }

                    $html .= '<div class="minutes-right">';
                        if($project->is_cancelled == DEFAULT_YES_VALUE){
                            $html .= '<span class="posted-time">'.trans('general.M0578').' '.___ago($project->canceldate).'</span>';
                        }else if($project->project_status !== 'closed'){
                            $html .= '<span class="posted-time '.$closed_class.'">'.trans('general.M0177').'<b>'.___ago($project->created).'</b></span>';
                        }else{
                            $html .= '<span class="posted-time '.$closed_class.'">'.trans('general.M0520').'<b>'.___ago($project->closedate).'</b></span>';
                        }
                    $html .= '</div>';
                $html .= '</div>';
                
            $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    function get_myproject_template($project,$type = 'talent'){
        $html = '<div class="content-box find-job-listing clearfix no-margin-bottom">';
            $html .= '<div class="find-job-left">';
                $html .= '<div class="content-box-header clearfix">';
                    // $html .= '<img src="'.str_replace("/profile", "/profile/thumbnail", $project->company_logo).'" alt="profile" class="job-profile-image">';
                    $html .= '<img src="'.$project->company_logo.'" alt="profile" class="job-profile-image">';
                    $html .= '<div class="contentbox-header-title">';
                        $html .= '<h3><a href="'.url(sprintf('%s/project/details?job_id=%s',$type,___encrypt($project->id_project))).'">'.$project->title.'</a></h3>';
                        $html .= '<span class="company-name">'.$project->company_name.'</span>';
                        
                        if($type == 'employer'){
                            if($project->project_status == 'pending' && $project->awarded == DEFAULT_NO_VALUE){

                                $html .= '<span>'.str_plural(trans('website.W0712'), $project->proposals_count).' '.trans('website.W0712a').' '.$project->proposals_count.'</span>';
                                $html .= '<a href="'.url(sprintf('%s/project/details?job_id=%s',$type,___encrypt($project->id_project))).'" class="btn btn-primary btn-small">'.trans('website.W0473').'</a>';
                                $html .= '<a href="'.url(sprintf('%s/project/proposals/detail?id_project=%s',$type,___encrypt($project->id_project))).'" class="btn btn-primary btn-small">'.trans('website.W0501').'</a>';
                            }else {
                                $html .= '<a href="'.url(sprintf('%s/project/details?job_id=%s',$type,___encrypt($project->id_project))).'" class="btn btn-primary btn-small m-l-n">'.trans('website.W0473').'</a>';
                            }
                        }

                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="find-job-right">';
                $html .= '<div class="contentbox-price-range">';
                    $html .= '<span>';
                    $html .= ___format($project->price,true,true);
                    $html .= '<br>';
                    if($project->employment == 'fixed'){
                        $html .= '<span class="label-green color-grey">'.ucfirst($project->employment).'</span>';
                    }else{
                        $html .= '<span class="label-green color-grey">'.job_types_rates_postfix($project->employment).'</span>';
                        // $html .= '<span class="small-price-type">'.job_types_rates_postfix($project->employment).'</span>';
                    }
                    $html .= '</span>';
                    
                    if(!empty($project->last_viewed)){
                        $html .= '<span class="last-viewed-icon active"></span>';
                    }
                $html .= '</div>';
                $html .= '<div class="contentbox-minutes clearfix">';
                    $html .= '<div class="minutes-right">';
                        if($project->is_cancelled == DEFAULT_YES_VALUE){
                            $html .= '<span class="posted-time">'.trans('general.M0578').' '.___ago($project->canceldate).'</span>';
                        }else if($project->project_status !== 'closed'){
                            $html .= '<span class="posted-time ">'.trans('general.M0177').'<b>'.___ago($project->created).'</b></span>';
                        }else{
                            $html .= '<span class="posted-time ">'.trans('general.M0520').'<b>'.___ago($project->closedate).'</b></span>';
                        }
                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="clearfix"></div>';
            if($type == 'talent' && 0){
                if(!empty($project->proposal) && $project->proposal->status == 'accepted' && $project->project_status == 'initiated'){
                    $html .= '<div class="m-t-10px">';
                        if(!empty($project->projectlog)){
                            $html .= '<div class="job-total-time  m-b-10">';
                                $html .= '<span class="total-time-text">'.trans('website.W0706').'</span>';
                                $html .= '<span class="total-time" id="total_working_hours_'.$project->id_project.'">'.___hours(substr($project->projectlog->total_working_hours, 0, -3)).'</span>';
                            $html .= '</div>';
                        }else{
                            $html .= '<div class="job-total-time  m-b-10">';
                                $html .= '<span class="total-time-text">'.trans('website.W0706').'</span>';
                                $html .= '<span class="total-time" id="total_working_hours_'.$project->id_project.'">'.___hours('00:00').'</span>';
                            $html .= '</div>';
                        }
                        $html .= '<div class="jobtimer white-wrapper m-b-10">';
                            $html .= '<div class="submit-timesheet">';
                                $html .= '<form role="working-hours-'.$project->id_project.'" method="post" action="'.url("talent/save/working/hours?project_id=".$project->id_project).'" autocomplete="off" >';
                                    $html .= '<div class="row">';
                                        $html .= '<div class="col-md-2 col-sm-3 col-xs-5">';
                                            $html .= '<label class="working-label m-t-5px">'.trans('website.W0700').'</label>';
                                        $html .= '</div>';
                                        $html .= '<div class="col-md-8 col-sm-6 col-xs-7">';
                                            $html .= '<div class="form-group no-margin-bottom w-100">';
                                                $html .= '<input type="text" name="working_hours_'.$project->id_project.'" autocomplete="off" class="form-control timepicker  working_hours_textbox" placeholder="'.trans('website.W0701').'" />';
                                            $html .= '</div>';
                                            $html .= '<input type="text" class="hide"/>';
                                        $html .= '</div>';
                                        $html .= '<div class="col-md-2 col-sm-3 col-xs-12 xs-p-t-15">';
                                            $html .= '<button class="btn btn-sm redShedBtn small-button pull-right" type="button" data-request="ajax-submit" data-target=\'[role="working-hours-'.$project->id_project.'"]\' >'.trans('website.W0013').'</button>';
                                        $html .= '</div>';
                                    $html .= '</div>';
                                $html .= '</form>';
                            $html .= '</div>';
                        $html .= '</div>';
                    $html .= '</div>';
                }
            }
        $html .= '</div>';

        return $html;
    }

    function get_talent_template($talent){
        $html = '<div class="content-box find-job-listing clearfix">';
            $html .= '<div class="find-job-left">';
                $html .= '<div class="content-box-header clearfix">';
                    if(isset($talent['get_company'][0])){
                        if($talent['get_company'][0]['company_logo'] != null){
                            $html .= '<img src="'.$talent['get_company'][0]['company_logo'].'" alt="profile" class="job-profile-image" height="60px">';
                        }else{
                            $file_url = asset(sprintf('images/%s',DEFAULT_AVATAR_IMAGE));
                            $html .= '<img src="'.$file_url.'" alt="profile" class="job-profile-image">';
                        }
                    } else if(!empty($talent['picture'])){
                        $html .= '<img src="'.$talent['picture'].'" alt="profile" class="job-profile-image">';
                    }
                    $html .= '<div class="contentbox-header-title">';
                        $html .= '<h3>';
                            $html .= '<a href="'.url("employer/find-talents/profile?talent_id=".___encrypt($talent['id_user'])).'">';
                            if($talent['company_profile']=='individual' ){
                                $html .= ___ucfirst($talent['name']);
                            } else if(isset($talent['get_company'][0])) {
                                $html .= ___ucfirst(@$talent['get_company'][0]['company_name']);
                            } else {
                                $html .= ___ucfirst($talent['name']);
                            }
                            $html .= '</a>';
                        $html .= '</h3>';
                        if($talent['company_profile']=='individual' ){
                            if(!empty($talent['country_name'])){
                                $html .= '<span class="company-name">'.$talent['country_name'].'</span>';
                                $html .= '<span class="">'.$talent['industries'][0]['industries']['name'].'</span>';
                            }
                        } else{
                            $html .= '<span class="company-name">'.@$talent['talent_company_country']->country_name.'</span>';
                            $html .= '<span class="">'.@$talent['industries'][0]['industries']['name'].'</span>';
                        }
                        $html .= '<div class="rating-review">';
                            $html .= '<span class="rating-block">';
                                $html .= ___ratingstar($talent['rating']);
                            $html .= '</span>';
                            $html .= '<a href="'.url(sprintf('%s/find-talents/reviews?talent_id=%s',EMPLOYER_ROLE_TYPE,___encrypt($talent['id_user']))).'" class="reviews-block" style="color:#444444;">'.$talent['review'].' '.trans('website.W0213').'</a>';
                        $html .= '</div>';
                        //$html .= 'Member since '.date("Y",strtotime($talent['created']));
                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="find-job-right">';
                $html .= '<div class="contentbox-price-range">';
                    $html .= '<span>';
                        if(!empty($talent['interests'])){ 
                            foreach ($talent['interests'] as $interests) {
                                if(!empty($interests['interest']) && $interests['interest'] != 'fixed'){
                                    $html .= '<span>'.___format($interests['workrate'],true,true).'<span class="per-time">/'.substr($interests['interest'], 0, 1).'</span></span><br>';
                                }
                            }
                        }
                    $html .= '</span>';
                $html .= '</div>';
                $html .= '<div class="contentbox-minutes clearfix">';

                    if(!empty($talent['interests'])){
                        foreach ($talent['interests'] as $interests) {
                            if(!empty($interests['interest']) && $interests['interest'] == 'fixed'){
                                $html .= '<span class="label-green color-grey">'.ucfirst($interests['interest']).'</span>';
                            }
                        }
                    }
   
                    if(!empty($talent['last_viewed'])){
                        $html .= '<span class="last-viewed-icon active"></span>';
                    }                
                    if($talent['is_saved'] == DEFAULT_YES_VALUE){
                        $html .= '<a href="javascript:void(0);" class="save-icon active" data-request="favorite-save" data-url="'.url(sprintf('%s/save?talent_id=%s',EMPLOYER_ROLE_TYPE,$talent['id_user'])).'"></a>';
                    }else{
                        $html .= '<a href="javascript:void(0);" class="save-icon" data-request="favorite-save" data-url="'.url(sprintf('%s/save?talent_id=%s',EMPLOYER_ROLE_TYPE,$talent['id_user'])).'"></a>';
                    }
                $html .= '</div>';
            $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    function get_project_small_template($project,$type = 'employer'){
        $closed_class = "";
        if($project->project_status == 'closed'){
            $closed_class = 'job-completed';
        }

        $html = '<div class="content-box '.$closed_class.' ">';
            $html .= '<div class="content-box-header clearfix">';
                $html .= '<img src="'.str_replace("/profile", "/profile/thumbnail", $project->company_logo).'" alt="profile" class="job-profile-image">';
                $html .= '<div class="contentbox-header-title">';
                    if($type == 'employer'){
                        if($project->company_id == auth()->user()->id_user){
                            $html .= '<h3><a class="ellipsis" href="'.url(sprintf('%s/project/details?job_id=%s',EMPLOYER_ROLE_TYPE,___encrypt($project->id_project))).'">'.$project->title.'</a></h3>';
                        }else{
                            $html .= '<h3><a class="ellipsis">'.$project->title.'</a></h3>';
                        }
                    }else{
                        $html .= '<h3><a class="ellipsis" href="'.url(sprintf('%s/find-jobs/details?job_id=%s',TALENT_ROLE_TYPE,___encrypt($project->id_project))).'">'.$project->title.'</a></h3>';
                    }

                    if($project->employment == 'fulltime'){
                        $html .= '<span class="label-green">'.trans('website.W0039').'</span>';
                    }

                    $html .= '<span class="company-name">'.$project->company_name.'</span>';
                $html .= '</div>';
                $html .= '<div class="contentbox-price-range">';
                    $html .= '<span>';
                    $html .= ___format($project->price,true,true);
                    $html .= '<br>';
                    if($project->employment == 'fixed'){
                        $html .= '<span class="label-green color-grey">'.ucfirst($project->employment).'</span>';
                    }else{
                        $html .= '<span class="label-green color-grey">'.job_types_rates_postfix($project->employment).'</span>';
                        // $html .= '<span class="small-price-type">'.job_types_rates_postfix($project->employment).'</span>';
                    }
                    $html .= '</span>';
                $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="contentbox-minutes clearfix">';
                $html .= '<div class="minutes-left">';
                    $html .= '<span> <strong> </strong></span>';
                $html .= '</div>';
                $html .= '<div class="minutes-right">';
                    if($project->project_status !== 'closed'){
                        $html .= '<span class="posted-time '.$closed_class.'">'.trans('general.M0177').'<b>'.___ago($project->created).'</b></span>';
                    }else{
                        $html .= '<span class="posted-time '.$closed_class.'">'.trans('general.M0520').'<b>'.___ago($project->enddate).'</b></span>';
                    }
                $html .= '</div>';
            $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    function get_talent_proposal_template($proposal){
        $html = '<div class="content-box find-job-listing clearfix" style="padding: 10px 20px;">';
            $html .= '<div class="find-job-left no-border">';
                $html .= '<div class="content-box-header clearfix">';
                    $html .= '<div class="contentbox-header-title">';
                        $html .= '<h3><a href="'.url(sprintf('%s/find-jobs/proposal?job_id=%s&proposal_id=%s',TALENT_ROLE_TYPE,___encrypt($proposal->project->id_project),___encrypt($proposal->id_proposal))).'">'.$proposal->project->title.'</a></h3>';
                        $html .= '<span class="company-name">'.$proposal->project->company_name.'</span>';

                        $html .= '<span class="text-grey">'.trans('website.W0690').' '.___d($proposal->created).'</span>';
                        $html .= '<span class="pull-right" style="position:relative;top:-5px;">';                   
                            $html .= '<a href="'.url(sprintf('%s/project/details?job_id=%s',TALENT_ROLE_TYPE,___encrypt($proposal->project->id_project))).'" class="btn btn-primary btn-small m-l-n">'.trans('website.W0473').'</a>';
                            $html .= '<a href="'.url(sprintf('%s/find-jobs/proposal?job_id=%s&proposal_id=%s',TALENT_ROLE_TYPE,___encrypt($proposal->project->id_project),___encrypt($proposal->id_proposal))).'" class="btn btn-primary btn-small">'.trans('website.W0501').'</a>';
                        $html .= '</span>';
                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    function talent_job_list($proposal){
        $html = '<div class="content-box find-job-listing clearfix" style="padding: 10px 20px;">';
            $html .= '<div class="">';
                $html .= '<div class="content-box-header clearfix">';
                    $html .= '<div class="contentbox-header-title">';
                        $html .= '<h3><a href="'.url(sprintf('%s/find-jobs/proposal?job_id=%s&proposal_id=%s',TALENT_ROLE_TYPE,___encrypt($proposal->project->id_project),___encrypt($proposal->id_proposal))).'">'.$proposal->project->title.'</a></h3>';
                        $html .= '<span class="company-name">'.$proposal->project->company_name.'</span>';

                        $html .= '<span class="text-grey">'.trans('website.W0690').' '.___d($proposal->created).'</span>';
                        $html .= '<span class="pull-right" style="position:relative;top:-5px;">';                   
                            $html .= '<a href="'.url(sprintf('%s/project/connected-talent?job_id=%s',TALENT_ROLE_TYPE,___encrypt($proposal->project->id_project))).'"  class="btn btn-primary btn-small m-l-n">'.trans('website.W0996').'</a>';
                            // $html .= '<a href="'.url(sprintf('%s/find-jobs/proposal?job_id=%s&proposal_id=%s',TALENT_ROLE_TYPE,___encrypt($proposal->project->id_project),___encrypt($proposal->id_proposal))).'" class="btn btn-primary btn-small">'.trans('website.W0501').'</a>';
                        $html .= '</span>';
                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    function connected_talent_list($proposal,$job_id){
        $html = '<div class="content-box find-job-listing clearfix" style="padding: 10px 20px;">';
            $html .= '<div class="">';
                $html .= '<div class="content-box-header clearfix">';
                    $html .= '<div class="contentbox-header-title">';
                        // $html .= '<h3><a href="'.url(sprintf('%s/find-jobs/proposal?job_id=%s&proposal_id=%s',TALENT_ROLE_TYPE,___encrypt($proposal->project->id_project),___encrypt($proposal->id_proposal))).'">'.$proposal->project->title.'</a></h3>';
                        $html .= '<span class="company-name">'.$proposal->user->name.'</span>';

                        $html .= '<span class="text-grey">Profession : '.$proposal->industry[0]['name'].'</span>';
                        $html .= '<span class="pull-right" style="position:relative;top:-5px;">';                   
                            $html .= '<a href="javascript:;" data-url="'.url(sprintf('%s/project/transfer-project?job_id=%s&talent_id=%s',TALENT_ROLE_TYPE,___encrypt($job_id),___encrypt($proposal->user->id_user))).'" data-request="invite-to-crowbar" data-target="#hire-me" class="btn btn-primary btn-small m-l-n">'.trans('website.W0997').'</a>';
                            // $html .= '<a href="'.url(sprintf('%s/find-jobs/proposal?job_id=%s&proposal_id=%s',TALENT_ROLE_TYPE,___encrypt($proposal->project->id_project),___encrypt($proposal->id_proposal))).'" class="btn btn-primary btn-small">'.trans('website.W0501').'</a>';
                        $html .= '</span>';
                    $html .= '</div>';
                $html .= '</div>';
            $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    function user_menu($selected = array(),$parent_category = 0,$inforceChild = true){

        $result = \App\Models\Admins::getUserMenu($parent_category);

        $html = '';
        if($inforceChild == true){
            $html .= '<ol class="sortable">';
        }

        foreach ($result as $i => $row) {
            if(@in_array($row['id'], $selected)){
                $checked = " checked ";
            }else{
                $checked = "  ";
            }

            if(!empty($row['total_child'])){
                if(in_array($row['id'], $selected)){
                    $html .= '<li id="list_'.$row['id'].'" class="mjs-nestedSortable-branch mjs-nestedSortable-expanded" >';
                }else{
                    $html .= '<li id="list_'.$row['id'].'" class="mjs-nestedSortable-branch mjs-nestedSortable-collapsed" >';
                }
                $attribute = ' data-request="has-child" ';
            }else{
                $html .= '<li id="list_'.$row['id'].'" class="mjs-nestedSortable-leaf" >';
                $attribute = ' data-request="is-child" ';
            }

            $html .= '<div>
                <span class="disclose"><span></span></span>
                <span class="list-item">
                    <div class="checkbox icheck" style="display: inline;">
                        <label>
                            <input'.$attribute.$checked.'type="checkbox" name="menus[]" value="'.$row['id'].'">&nbsp;&nbsp;&nbsp;' . $row['name'].'
                        </label>
                    </div>
                </span>
            </div>';
            $child = user_menu($selected,$row['id'],false);
            if(!empty($child)){
                $html .= sprintf("<ol>%s</ol>",$child);
            }
            $html .= '</li>';
        }

        if($inforceChild == true){
            $html .= '</ol>';
        }
        return $html;
    }

    function _ticketid($ticket_id){
        return sprintf("#%'.0".MAX_LENGTH_DYANIMIC_ID."d",$ticket_id);
    }


    function ___calculate_payment($employment,$quoted_price){
        return $quoted_price;
    }

    function ___convert_time($decimal){
        // start by converting to seconds
        $seconds = floor($decimal * 3600);
        // we're given hours, so let's get those the easy way
        $hours = floor($decimal);
        // since we've "calculated" hours, let's remove them from the seconds variable
        $seconds -= $hours * 3600;
        // calculate minutes left
        $minutes = floor($seconds / 60);
        // remove those from seconds as well
        $seconds -= $minutes * 60;
        // return the time formatted HH:MM:SS
        return ___lz($hours).":".___lz($minutes).":".___lz($seconds);
    }

    // lz = leading zero
    function ___lz($num){
        return (strlen($num) < 2) ? "0{$num}" : $num;
    }

    function getChildReply($replyArr, $replyHtml = ''){
        if(!empty($replyArr)){
            foreach ($replyArr as $value) {
                $replyHtml .= $value['answer_description'] . '<br />';
                #dd($value['children']);
                if(!empty($value['children'])){
                    getChildReply($value['children'], $replyHtml);
                }
            }
        }
        return $replyHtml;
    }

    function strip_tags_content($text, $tags = '', $invert = FALSE) { 
        preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags); 
        $tags = array_unique($tags[1]); 
        
        if(is_array($tags) AND count($tags) > 0) { 
            if($invert == FALSE) { 
                return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text); 
            }else { 
                return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text); 
            } 
        } elseif($invert == FALSE) { 
            return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text); 
        } 
        
        return $text; 
    }

    function validatePayPalEmail($emailAddress){
        // create a new cURL resource
        $ch = curl_init();

        $ppUserID = env('PAYPAL_USERNAME'); //Take it from   sandbox dashboard for test mode or take it from paypal.com account in production mode, help: https://developer.paypal.com/docs/classic/api/apiCredentials/
        $ppPass = env('PAYPAL_PASSWORD'); //Take it from sandbox dashboard for test mode or take it from paypal.com account in production mode, help: https://developer.paypal.com/docs/classic/api/apiCredentials/
        $ppSign = env('PAYPAL_SIGN'); //Take it from sandbox dashboard for test mode or take it from paypal.com account in production mode, help: https://developer.paypal.com/docs/classic/api/apiCredentials/
        $ppAppID = env('PAYPAL_APPID'); //if it is sandbox then app id is always: APP-80W284485P519543T
        $sandboxEmail = env('PAYPAL_SANDBOX_EMAIL'); //comment this line if you want to use it in production mode.It is just for sandbox mode

        #$emailAddress = "anksrizzz-buyer@gmail.com"; //The email address you wana verify

        //parameters of requests
        $nvpStr = 'emailAddress='.$emailAddress.'&matchCriteria=NONE';

        // RequestEnvelope fields
        $detailLevel    = urlencode("ReturnAll");
        $errorLanguage  = urlencode("en_US");
        $nvpreq = "requestEnvelope.errorLanguage=$errorLanguage&requestEnvelope.detailLevel=$detailLevel&";
        $nvpreq .= "&$nvpStr";
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        $headerArray = array(
        "X-PAYPAL-SECURITY-USERID:$ppUserID",
        "X-PAYPAL-SECURITY-PASSWORD:$ppPass",
        "X-PAYPAL-SECURITY-SIGNATURE:$ppSign",
        "X-PAYPAL-REQUEST-DATA-FORMAT:NV",
        "X-PAYPAL-RESPONSE-DATA-FORMAT:JSON",
        "X-PAYPAL-APPLICATION-ID:$ppAppID",
        "X-PAYPAL-SANDBOX-EMAIL-ADDRESS:$sandboxEmail" //comment this line in production mode. IT IS JUST FOR SANDBOX TEST
        );

        if(env('PAYPAL_ENV') == 'sandbox'){
            $url="https://svcs.sandbox.paypal.com/AdaptiveAccounts/GetVerifiedStatus";
        }
        else{
            $url="https://svcs.paypal.com/AdaptiveAccounts/GetVerifiedStatus";
        }

        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        $paypalResponse = curl_exec($ch);
        curl_close($ch);

        $paypalResponse = json_decode($paypalResponse, true);
        return $paypalResponse['responseEnvelope']['ack'];
        #Success,Failure
    }

    function validatePayPalEmail2($emailAddress){

        //Check PayPal mode, and change PayPal url according for Sandbox or Live.
        $PayPal_BASE_URL = PayPal_BASE_URL_SANDBOX;
        if(env('PAYPAL_ENV') == 'sandbox'){
          $PayPal_BASE_URL = PayPal_BASE_URL_SANDBOX;
        }else{
          $PayPal_BASE_URL = PayPal_BASE_URL_LIVE;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $PayPal_BASE_URL . 'oauth2/token');
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERPWD, env('PAYPAL_CLIENT_ID') . ":" . env('PAYPAL_SECRET'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        $result = curl_exec($ch);            

        $json = json_decode($result);

        $accessToken = $json->access_token;

        $curl = curl_init();
        $data = '{
                    "customer_data": {
                        "customer_type": "MERCHANT"
                    },
                    "web_experience_preference": {
                        "partner_logo_url": "'.asset('splashLogo.png').'",
                        "return_url": "'.url(sprintf('%s/verified-paypal-email',TALENT_ROLE_TYPE)).'",
                        "action_renewal_url": "'.url('/').'/renew'.'"
                    }
                }';

        curl_setopt($curl, CURLOPT_URL, $PayPal_BASE_URL.'customer/partner-referrals');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Authorization: Bearer ".$accessToken) 
        );

        $response2 = curl_exec($curl);
        $paypalResponse = json_decode($response2, true);

        //save response
        \Models\PaypalPayment::paypal_response([
            'user_id'                   => \Auth::user()->id_user,
            'response_json'             =>  json_encode(json_decode($response2)),
            'request_type'              => 'Add Paypal Email',
            'status'                    => 'true',
            'created'                   => date('Y-m-d H:i:s')
        ]);

        if(!empty($paypalResponse)){
            return $paypalResponse['links'][1];
        }else{
            return false;
        }

    }

    function validatePayPalEmail_mobile($emailAddress,$user_id){

        //Check PayPal mode, and change PayPal url according for Sandbox or Live.
        $PayPal_BASE_URL = PayPal_BASE_URL_SANDBOX;
        if(env('PAYPAL_ENV') == 'sandbox'){
          $PayPal_BASE_URL = PayPal_BASE_URL_SANDBOX;
        }else{
          $PayPal_BASE_URL = PayPal_BASE_URL_LIVE;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $PayPal_BASE_URL . 'oauth2/token');
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERPWD, env('PAYPAL_CLIENT_ID') . ":" . env('PAYPAL_SECRET'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        $result = curl_exec($ch);            

        $json = json_decode($result);

        $accessToken = $json->access_token;

        $curl = curl_init();
        $data = '{
                    "customer_data": {
                        "customer_type": "MERCHANT"
                    },
                    "web_experience_preference": {
                        "partner_logo_url": "'.asset('splashLogo.png').'",
                        "return_url": "'.url(sprintf('%s/verified-mobile-paypal-email',TALENT_ROLE_TYPE)).'?userID='.$user_id.'&pp_email='.$emailAddress.' ",
                        "action_renewal_url": "'.url('/').'/renew'.'"
                    }
                }';

        curl_setopt($curl, CURLOPT_URL, $PayPal_BASE_URL.'customer/partner-referrals');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Authorization: Bearer ".$accessToken) 
        );

        $response2 = curl_exec($curl);
        $paypalResponse = json_decode($response2, true);

        //save response
        \Models\PaypalPayment::paypal_response([
            'user_id'                   => $user_id,
            'response_json'             => json_encode(json_decode($response2)),
            'request_type'              => 'Add Paypal Email Mobile',
            'status'                    => 'true',
            'created'                   => date('Y-m-d H:i:s')
        ]);

        if(!empty($paypalResponse)){
            return $paypalResponse['links'][1];
        }else{
            return false;
        }

    }


    function forget_cache($cache_key=[]){
        foreach ($cache_key as $key) {
            \Cache::forget($key);
        }
        return true;
    }

    function ___validateDate($date){
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    function ___get_steps($step){
        switch ($step) {
            case 'one':return ['one'];break;
            case 'two':return ['one','two'];break;
            case 'three':return ['one','two','three'];break;
            case 'four':return ['one','two','three','four'];break;
            case 'five':return ['one','two','three','four','five'];break;
        }
    }

    /**
     * Convert a string such as "one hundred thousand" to 100000.00.
     *
     * @param string $data The numeric string.
     *
     * @return float or false on error
     */
    function __words_to_number($data) {
        // Replace all number words with an equivalent numeric value
        $data = strtr(
            $data,
            array(
                'zero'      => '0',
                'a'         => '1',
                'one'       => '1',
                'two'       => '2',
                'three'     => '3',
                'four'      => '4',
                'five'      => '5',
                'six'       => '6',
                'seven'     => '7',
                'eight'     => '8',
                'nine'      => '9',
                'ten'       => '10',
                'eleven'    => '11',
                'twelve'    => '12',
                'thirteen'  => '13',
                'fourteen'  => '14',
                'fifteen'   => '15',
                'sixteen'   => '16',
                'seventeen' => '17',
                'eighteen'  => '18',
                'nineteen'  => '19',
                'twenty'    => '20',
                'thirty'    => '30',
                'forty'     => '40',
                'fourty'    => '40', // common misspelling
                'fifty'     => '50',
                'sixty'     => '60',
                'seventy'   => '70',
                'eighty'    => '80',
                'ninety'    => '90',
                'hundred'   => '100',
                'thousand'  => '1000',
                'million'   => '1000000',
                'billion'   => '1000000000',
                'and'       => '',
            )
        );

        // Coerce all tokens to numbers
        $parts = array_map(
            function ($val) {
                return floatval($val);
            },
            preg_split('/[\s-]+/', $data)
        );

        $stack = new SplStack; // Current work stack
        $sum   = 0; // Running total
        $last  = null;

        foreach ($parts as $part) {
            if (!$stack->isEmpty()) {
                // We're part way through a phrase
                if ($stack->top() > $part) {
                    // Decreasing step, e.g. from hundreds to ones
                    if ($last >= 1000) {
                        // If we drop from more than 1000 then we've finished the phrase
                        $sum += $stack->pop();
                        // This is the first element of a new phrase
                        $stack->push($part);
                    } else {
                        // Drop down from less than 1000, just addition
                        // e.g. "seventy one" -> "70 1" -> "70 + 1"
                        $stack->push($stack->pop() + $part);
                    }
                } else {
                    // Increasing step, e.g ones to hundreds
                    $stack->push($stack->pop() * $part);
                }
            } else {
                // This is the first element of a new phrase
                $stack->push($part);
            }

            // Store the last processed part
            $last = $part;
        }

        return $sum + $stack->pop();
    }

    /**
     * Prepare json response for displaying hours
     * @param \Project
     * @return JSON
     */
    function ___hours($time) {
        $times = explode(":", $time);

        if(!empty($times[1]) && !empty($times[1])){
            return $times[0].'h'.' '.$times[1].'m';
        }else{
            return '00h 00m';
        }
    }

    /**
     * Prepare json response for api response 
     * @param \Project
     * @return JSON
     */
    function api_resonse_project($project) {
        $project = json_decode(json_encode($project),true);

        array_walk($project,function(&$item){
            $item['company_logo'] = str_replace("/profile", "/profile/thumbnail", $item['company_logo']);
            if(is_array($item) && array_key_exists('startdate', $item) && array_key_exists('enddate', $item)){
                $item['timeline'] = ___date_difference($item['startdate'],$item['enddate']);
            }

            if($item['is_cancelled'] == DEFAULT_YES_VALUE){
                $item['created'] = trans('general.M0578').' '.___ago($item['canceldate']);
            }elseif($item['project_status'] == 'closed'){
                $item['created'] = trans('general.M0520').' '.___ago($item['closedate']);
            }else{
                $item['created'] = trans('general.M0177').' '.___ago($item['created']);
            }

            if(is_array($item) && array_key_exists('projectlog', $item)){
                if(empty($item['projectlog'])){
                    $item['projectlog'] = (object)['total_working_hours' => ___hours('00:00')];
                }else{
                    $item['projectlog']['total_working_hours'] = ___hours(substr($item['projectlog']['total_working_hours'], 0, -3));
                }
            }

            $item['price_unit'] = ___cache('currencies')[request()->currency];
            $item['price']      = ___format($item['price'],true,false);
        });

        return $project;
    }

    /**
     * Prepare json response for api response 
     * @param \Project
     * @return JSON
     */
    function api_resonse_common($data, $ago = false, $keys = []) {
        $data = json_decode(json_encode($data),true);
        
        if(is_array($data) && array_key_exists('startdate', $data) && array_key_exists('enddate', $data)){
            $data['timeline'] = ___date_difference($data['startdate'],$data['enddate']);
        }
        
        array_walk_recursive($data,function(&$item, $key) use($ago,$keys){
            if(!in_array($key, $keys)){
                if(!empty($ago)){
                    if($key === 'created'){
                        $item = ___ago($item);
                    }
                }else{
                    if($key === 'created'){
                        $item = ___d($item);
                    }
                }
            }

            if($key === 'proposal_sent'){
                $item = ___d($item);
            }

            if($key === 'price_unit'){
                $item = ___cache('currencies')[request()->currency];
            }

            if($key === 'price'){
                $item = ___format($item,true,false);
            }

            if($key === 'quoted_price'){
                $item = ___format($item,true,false);
            }

            if($key === 'price_unit'){
                $item = ___cache('currencies')[request()->currency];
            }

            if($key === 'last_viewed'){
                $item = ___ago($item);
            }
            
            if($key === 'workrate'){
                $item = ___format($item,true,false);
            }
            
            if($key === 'total_paid_by_employer'){
                $item = ___format($item,true,false);
            }
        });

        return $data;
    }

    function ___editSkipUrl($step,$user_type){
        if($user_type == 'talent'){
            switch ($step) {
                case 'one':return url(sprintf("%s/profile/edit/step/two",TALENT_ROLE_TYPE));break;
                case 'two':return url(sprintf("%s/profile/edit/step/three",TALENT_ROLE_TYPE));break;
                case 'three':return url(sprintf("%s/profile/edit/step/four",TALENT_ROLE_TYPE));break;
                case 'four':return url(sprintf("%s/profile/edit/step/five",TALENT_ROLE_TYPE));break;
                case 'five':return url(sprintf("%s/find-jobs",TALENT_ROLE_TYPE));break;
            }
        }else{
            switch ($step) {
                case 'one':return url(sprintf("%s/profile/edit/step/two",EMPLOYER_ROLE_TYPE));break;
                case 'two':return url(sprintf("%s/profile/edit/step/three",TALENT_ROLE_TYPE));break;
                case 'three':return url(sprintf("%s/find-talents",TALENT_ROLE_TYPE));break;
            }  
        }
    }

    function ___title_job_steps($step){
        switch ($step) {
            case 'one':return trans('website.W0645');break;
            case 'two':return trans('website.W0646');break;
            case 'three':return trans('website.W0647');break;
            case 'four':return trans('website.W0648');break;
            case 'five':return trans('website.W0649');break;
        }
    }

    function ___calculate_paypal_commission($price){
        $paypal_commission      = \Cache::get('configuration')['paypal_commission'];
        $paypal_commission_flat = \Cache::get('configuration')['paypal_commission_flat'];
        $calculated_price       = ___format(round(((($price*$paypal_commission)/100)+$paypal_commission_flat) ,2 ) );
        return $calculated_price;
    }

    function ___truncate($string, $limit = 20, $separator = '...'){
        if (strlen($string) <= $limit){
            return $string;
        }else{
            $separator_length   = strlen($separator);
            $chars_to_show      = ($limit - $separator_length);
            $front_chars        = ceil($chars_to_show/2);
            $back_chars         = floor($chars_to_show/2);

            return substr($string, 0, $front_chars).$separator.substr($string, (strlen($string) - $back_chars));
        } 
    }

    function ip_info($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
        $output = NULL;
        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            $ip = $_SERVER["REMOTE_ADDR"];
            if ($deep_detect) {
                if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP))
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
        $support    = array("country", "countrycode", "state", "region", "city", "location", "address");
        
        $continents = \Models\Countries::getCountries();

        if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
            $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
            if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
                switch ($purpose) {
                    case "location":
                        $output = array(
                            "city"           => @$ipdat->geoplugin_city,
                            "state"          => @$ipdat->geoplugin_regionName,
                            "country"        => @$ipdat->geoplugin_countryName,
                            "country_code"   => @$ipdat->geoplugin_countryCode,
                            "continent"      => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                            "continent_code" => @$ipdat->geoplugin_continentCode
                        );
                        break;
                    case "address":
                        $address = array($ipdat->geoplugin_countryName);
                        if (@strlen($ipdat->geoplugin_regionName) >= 1)
                            $address[] = $ipdat->geoplugin_regionName;
                        if (@strlen($ipdat->geoplugin_city) >= 1)
                            $address[] = $ipdat->geoplugin_city;
                        $output = implode(", ", array_reverse($address));
                        break;
                    case "city":
                        $output = @$ipdat->geoplugin_city;
                        break;
                    case "state":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "region":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "country":
                        $output = @$ipdat->geoplugin_countryName;
                        break;
                    case "countrycode":
                        $output = @$ipdat->geoplugin_countryCode;
                        break;
                }
            }
        }

        /*This returns ISO Code*/
        // dd($output); 

        /*Get Currency by ISO Code*/
        $currency = \Models\Countries::getCountryIdByCode($output);

        return $currency;
    }

    function get_client_ip() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
           $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    function getLoc(){
        $ip = $_SERVER['REMOTE_ADDR'];
        $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
        dd($ip, $details);
    }