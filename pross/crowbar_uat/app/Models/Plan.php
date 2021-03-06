<?php

	namespace Models;
    
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Crypt;
    use Illuminate\Support\Facades\Mail;

    class Plan extends Model
    {
        const CREATED_AT = 'created';
        const UPDATED_AT = 'updated';

        protected $fillable = [];

        protected $hidden = [];

        public function __construct(){
    	   
        }

        /**
         * [This method is used to get plan] 
         * @param [Integer]$id_plan[Used for plan id]
         * @return Data Response
         */	

		public static function getPlan($id_plan=NULL){
			\DB::statement(\DB::raw('set @row_number=0'));

			$keys = [
				\DB::raw('@row_number  := @row_number  + 1 AS row_number'),
				'id_plan',
				'name',
				'braintree_plan_id',
				'price',
				'created',
				'updated',
			];

			$table_plan = DB::table('plan');
			if(!empty($id_plan)){
				return $table_plan
				->select($keys)
				->leftJoin('plan_features','plan_features.plan_id','=','plan.id_plan')
				->leftJoin('features','features.id_feature','=','plan_features.feature_id')
				->where('id_plan',$id_plan)->get();
			}

			return $table_plan->select($keys)->get();
		}

		/**
         * [This method is used to get getPlanById] 
         * @param [Integer]$id_plan[Used for plan id]
         * @return Data Response
         */	

		public static function getPlanById($id_plan){
			return DB::table('plan')->select(['id_plan','name','braintree_plan_id','price'])->where('id_plan',$id_plan)->first();
		}

		/**
         * [This method is used to get features] 
         * @param [type]$fetch[Used for fetching]
         * @param [String]$keys[Used for key]
         * @param [type]$where[Used for where clause]
         * @param [type]$order_by[Used for sorting]
         * @return Json Response
         */	

		public static function getFeatures($fetch = 'array',$keys = array('*'), $where = "", $order_by = 'name'){

            $table_features = DB::table('features');
            DB::statement(DB::raw('set @row_number=0'));
            if(!empty($keys)){
                $table_features = $table_features->select($keys); 
            }
            
            if(!empty($where)){
            	$table_features->whereRaw($where);
            }
            
            if($fetch === 'array'){
                return json_decode(
                    json_encode(
                        $table_features->get()->toArray()
                    ),
                    true
                );
            }else if( $fetch == 'obj'){
            	return $table_features->get();
            }else if( $fetch == 'single'){
            	return $table_features->get()->first();
            }else{
                return $table_features->get();
            }
		}

		/**
         * [This method is used to add features] 
         * @param [String]$data[Used for data]
         * @return Boolean
         */	

        public static function  add_feature($data){
            $table_feature = DB::table('features');

            if(!empty($data)){
                $isInserted = $table_feature->insert($data); 
            }

            return (bool)$isInserted;
        } 

        /**
         * [This method is used to update features] 
         * @param [Integer]$id_feature [Used for feature id]
         * @param [String]$data[Used for data]
         * @return Boolean
         */	       

        public static function  update_feature($id_feature, $data){
            $table_feature = DB::table('features');
            if(!empty($data)){
                $isUpdated = $table_feature->where('id_feature','=',$id_feature)->update($data);
            }
            return (bool)$isUpdated;
        }	

        /**
         * [This method is used to getPlanFeaturesById] 
         * @param [Integer]$id_plan[Used for plan id]
         * @return Data Response
         */		

		public static function getPlanFeaturesById($id_plan){
			return DB::table('plan_features')
			->select([
				DB::raw("GROUP_CONCAT(feature_id) as feature_ids")
			])
			->where('plan_id',$id_plan)
			->groupBy('plan_features.plan_id')->first();
		}

		/**
         * [This method is used to update plan features] 
         * @param [Integer]$id_plan[Used for plan id]
         * @param [Integer]$features[Used for features]
         * @return Boolean
         */	

		public static function update_plan_featuers($id_plan,$features){
			if(!empty($features)){
				$table_plan_features = DB::table('plan_features');
				$isDeleted = $table_plan_features->where('plan_id',$id_plan)->delete();
				if($isDeleted){
					$plan_features_array = [];
					foreach ($features as $key => $value) {
						$plan_features = [
							'plan_id' 		=> $id_plan,
							'feature_id' 	=> $value,
						];
						array_push($plan_features_array, $plan_features);
					}
					return (bool)$table_plan_features->insert($plan_features_array);
				}
			}
			return false;
		}

		/**
         * [This method is used to get plan listing] 
         * @param null
         * @return Data Response
         */	

		public static function getPlanListing(){
			$table_plan = DB::table('plan')->whereIn('status',['active'])->get()->toArray();
			$prefix = DB::getTablePrefix();
			$newitem = [];
			array_walk($table_plan,function($item) use(&$newitem){
				$countfeatures = DB::table('plan_features')->where('plan_id',$item->id_plan)->count();
				if($countfeatures > 0){
					$newitem[] = DB::table('features')
					->select([
						'features.id_feature',
						\DB::Raw("IF(({$prefix}features.{$language} != ''),{$prefix}features.{$language}, {$prefix}features.en) as name")
					])
					->leftJoin('plan_features','features.id_feature','=','plan_features.feature_id')
					->where('plan_id',$item->id_plan)
					->get();
				}
			});
		}

		/**
         * [This method is used to get plan listing] 
         * @param null
         * @return Data Response
         */	

		public static function getPlanList(){
			$prefix = DB::getTablePrefix();
			$language = \App::getLocale();
			$defaultCurrency = \Models\Currency::getDefaultCurrency();

			$table_plan['plan'] = DB::table('plan')
			->select([
				'id_plan',
				'name',
				'plan_detail',
				'braintree_plan_id',
				\DB::Raw('`CONVERT_PRICE`('.$prefix.'plan.price, "'.$defaultCurrency->iso_code.'", "'.request()->currency.'") AS price'),
				'status',
				'created',
				'updated'
			])
			->whereIn('status',['active'])
			->get()
			->toArray();

			$table_plan['plan'] = json_decode(json_encode($table_plan['plan']), true);

			$table_plan['plan_features'] = DB::table('features')
			->select([
				'id_feature',
				\DB::Raw("IF(({$language} != ''),`{$language}`, `en`) as name")
			])
			->whereIn('status',['active'])
			->get()
			->toArray();

			$table_plan['plan_features'] = json_decode(json_encode($table_plan['plan_features']), true);


			foreach ($table_plan['plan'] as &$value) {
				$feature = DB::table('plan_features')
				->select(
					\DB::raw('GROUP_CONCAT(feature_id) AS feature_id')
					)
				->where('plan_id',$value['id_plan'])
				->get()
				->first();

				$feature = json_decode(json_encode($feature), true);
				$value['feature'] = $feature['feature_id'];
			}

			return $table_plan;
		}

		/**
         * [This method is used to get plan in detail] 
         * @param [Integer]$id_plan[Used for plan id]
         * @return Data Response
         */	

		public static function getPlanDetail($id_plan){
			$prefix = DB::getTablePrefix();
			$defaultCurrency = \Models\Currency::getDefaultCurrency();
			return DB::table('plan')
			->select([
				'id_plan',
				'name',
				'plan_detail',
				'braintree_plan_id',
				\DB::Raw('`CONVERT_PRICE`('.$prefix.'plan.price, "'.$defaultCurrency->iso_code.'", "'.request()->currency.'") AS price'),
				'price AS global_price',
				'status',
				'created',
				'updated'
			])
			->where('id_plan', $id_plan)
			->get()
			->first();
		}
    }
