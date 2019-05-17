<?php

    namespace App\Models; 

    use Illuminate\Notifications\Notifiable;
    use Illuminate\Foundation\Auth\User as Authenticatable;
    
    use DB;

    class Admins extends Authenticatable{
    
        use Notifiable;
        protected $table = 'users';
        protected $primaryKey = 'id_user';
        
        const CREATED_AT = 'created';
        const UPDATED_AT = 'updated';

        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        protected $fillable = [
            'name', 'email', 'password',
        ];

        /**
         * The attributes that should be hidden for arrays.
         *
         * @var array
         */
        protected $hidden = [
            'password', 'remember_token',
        ];

        /**
         * [This method is used to get all abuses report] 
         * @param [Integer]$id_admin[Used for admin id]
         * @return Data Responseta 
         */

        public static function get_authorized_menus($id_admin){
            if(!empty($id_admin)){
                $menu_visibility = (array)Self::get_menu_visibility($id_admin);
                
                $menus = array();
                if(!empty($menu_visibility)){
                    $table_admin_menu = DB::table('users_menu');

                    $table_admin_menu->select(DB::raw("REPLACE( action_url,LEFT(action_url,INSTR(action_url,'/')),'') as action_url"));
                    $table_admin_menu->whereIn('id',$menu_visibility);
                    $table_admin_menu->where('status','active');
                    $table_admin_menu->where('action_url','!=','#');
                    
                    $menus = (array)array_column(
                        (array)$table_admin_menu->get()->toArray(),
                        'action_url'
                    );
                }

                array_push($menus, 'view');
                array_push($menus, 'page_not_found');
                array_push($menus, 'logout');
                
                return $menus;
            }else{
                return $is_authorized = array(
                    'dashboard',
                    'merchants',
                    'consumers',
                    'view',
                    'add',
                    'general',
                    'emails',
                    'pages',
                    'slots',
                    'categories',
                    'products',
                    'requests',
                    'services',
                );

            }
        }

        /**
         * [This method is used to getting menu visibility] 
         * @param [Integer]$id_admin[Used for admin id]
         * @return Data Response
         */

        public static function get_menu_visibility($id_admin){
            $table_admin_menu_visibility = DB::table('users_menu_visibility');

            $table_admin_menu_visibility->select("menu_visibility");

            if($table_admin_menu_visibility->where(['id_user' => $id_admin])->count()){
                return (array) json_decode(
                    $table_admin_menu_visibility->where(
                        array(
                            'id_user' => $id_admin,
                        )
                    )->first()->menu_visibility,
                    true
                );
            }else{
                return [];
            }
        }

        /**
         * [This method is used to getting user menu] 
         * @param  [Varchar] $parent_category[Used for parent category]
         * @return Data Response
         */

        public static function getUserMenu($parent_category){
            $prefix = DB::getTablePrefix();

            $menuList = DB::table('users_menu')
            ->select('id','id as menu_id','name',DB::raw('(SELECT COUNT(id) FROM '.$prefix.'users_menu WHERE menu_id = parent) as total_child'))
            ->where('status','active')
            ->where('parent',$parent_category)
            ->whereIn('id',[61,68])
            ->orderBy('menu_order','ASC')
            ->get()
            ->toArray();

            return json_decode(json_encode($menuList), true);
        }

    }
