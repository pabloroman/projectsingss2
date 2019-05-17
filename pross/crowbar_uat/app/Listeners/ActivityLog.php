<?php

    namespace App\Listeners;

    use App\Events\Activity;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Contracts\Queue\ShouldQueue;

    class ActivityLog{
        /**
         * Create the event listener.
         *
         * @return void
         */
        public function __construct(){
            //
        }

        /**
         * Handle the event.
         *
         * @param  SomeEvent  $event
         * @return void
         */
        public function handle(Activity $event){
            $data = $event->request;
            if(!empty($data)){
                $data['updated'] = date('Y-m-d H:i:s');
                $data['created'] = date('Y-m-d H:i:s');
                
                \DB::table('activity')->insert($data);
            }            
        }
    }