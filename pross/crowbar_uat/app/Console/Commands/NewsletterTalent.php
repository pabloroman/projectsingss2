<?php

namespace App\Console\Commands;
use DB;
use Illuminate\Console\Command;

class NewsletterTalent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'newslettertalent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly newsletter to talent';

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
        $talentList = \Models\Users::getUserForNewsLetter();

        foreach ($talentList as $emp) {
            $where = [];
            if(!empty($emp['city'])){
                $where[] = 'location = ' . $emp['city'];
            }
            if(!empty($emp['industry'])){
                $where[] = 'industry = ' . $emp['industry'];
            }
            if(!empty($emp['subindustry'])){
                $where[] = 'subindustry = ' . $emp['subindustry'];
            }

            $currency = \Cache::get('default_currency');
            if(!empty($emp['currency'])){
                $currency = $emp['currency'];
            }

            $projectList = \Models\Projects::getProjectForNewsLetter($where, $currency, $emp['sign']);

            $htmlLetter = '';
            if(!empty($projectList)){
                foreach ($projectList as $list) {
                    if($list['price_max'] !== NULL){
                        $price  = ___format($list['price_max'], true, true) .' - '. ___format($list['price'], true, true) . ' price range';
                    }
                    else{
                        $price  = ___format($list['price'], true, true) . ' price';
                    }
                    $htmlLetter .= sprintf(TALENT_NEWSLETTER_TEMPLATE, $list['title'], $list['company_name'], $price .' '. $list['employment'], $list['industries_name']);
                }
                /*Email to talent*/
                $email                  = $emp['email'];
                $emailData              = ___email_settings();
                $emailData['email']     = $email;
                $emailData['name']      = $emp['name'];
                $emailData['table']     = $htmlLetter;
                $emailData['unsubscribe']      = url(sprintf("newsletter/unsubscribe/%s",$emp['newsletter_token']));

                $template_name = "weekly_newsletter_talent";

                ___mail_sender($email,'',$template_name,$emailData);
            }
        }
        /*
        $allindustries = \Models\Industries::allindustries('array', 'parent = 0');

        foreach ($allindustries as $ind) {
            $projectList = \Models\Projects::getProjectListByIndustry($ind['id_industry']);

            $htmlLetter = '';
            if(!empty($projectList)){
                foreach ($projectList as $list) {
                    if($list['price_max'] !== NULL){
                        $price  = $list['price_max'] .' - '. $list['price'] . ' price range';
                    }
                    else{
                        $price  = $list['price'] . ' price';
                    }
                    $htmlLetter .= sprintf(TALENT_NEWSLETTER_TEMPLATE, $list['title'], $list['company_name'], $price .' '. $list['employment'], $list['industry']);
                }
            }

            $talentList = \Models\Users::getTalentByIndustry($ind['id_industry']);

            if(!empty($talentList) && !empty($projectList)){
                foreach ($talentList as $emp) {
                    $email                  = $emp['email'];
                    $emailData              = ___email_settings();
                    $emailData['email']     = $email;
                    $emailData['name']      = $emp['name'];
                    $emailData['table']     = $htmlLetter;
                    $emailData['unsubscribe']      = url(sprintf("newsletter/unsubscribe/%s",$emp['newsletter_token']));

                    $template_name = "weekly_newsletter_talent";

                    ___mail_sender($email,'',$template_name,$emailData);
                }
            }
        }

        $projectList = \Models\Projects::getProjectListForNewsLetter();

        $htmlLetter = '';
        if(!empty($projectList)){
            foreach ($projectList as $list) {
                if($list['price_max'] !== NULL){
                    $price  = $list['price_max'] .' - '. $list['price'] . ' price range';
                }
                else{
                    $price  = $list['price'] . ' price';
                }
                $htmlLetter .= sprintf(TALENT_NEWSLETTER_TEMPLATE, $list['title'], $list['company_name'], $price .' '. $list['employment'], $list['industry']);
            }
        }

        $talentList = \Models\Users::getTalentForNewsLetter();

        if(!empty($talentList) && !empty($projectList)){
            foreach ($talentList as $emp) {
                $email                  = $emp['email'];
                $emailData              = ___email_settings();
                $emailData['email']     = $email;
                $emailData['name']      = $emp['name'];
                $emailData['table']     = $htmlLetter;
                $emailData['unsubscribe']      = url(sprintf("newsletter/unsubscribe/%s",$emp['newsletter_token']));

                $template_name = "weekly_newsletter_talent";

                ___mail_sender($email,'',$template_name,$emailData);
            }
        }
        */
    }
}
