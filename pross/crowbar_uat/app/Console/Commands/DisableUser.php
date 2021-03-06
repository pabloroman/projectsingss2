<?php

namespace App\Console\Commands;
use DB;
use Illuminate\Console\Command;

class DisableUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disableuser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactive user after 14 days';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $prefix = DB::getTablePrefix();
        $interview_interval = \Cache::get('configuration')['interview_interval'];
        $cron_status = \Cache::get('configuration')['user_disable_cron_status'];

        if($cron_status == 'yes'){
            DB::table('users')
            ->leftJoin('talent_answer','talent_answer.id_user','=','users.id_user')
            ->where('users.type', 'talent')
            ->whereNull('talent_answer.id')
            ->whereRaw(DB::raw("DATEDIFF('".date('Y-m-d')."', ".$prefix."users.created) > ".$interview_interval))
            ->update(['status' => 'inactive']);
        }
    }
}
