@if(!empty($job_details['employer']))
    <div class="jobdetails-sidebar-content about-employer-wrapper">
        <h3>{{trans('job.J0014')}}</h3>
        <div class="employer-header-xs clearfix">
             <div class="employer-display-xs"><img src="{{ $job_details['employer']['picture'] }}" /></div>
             <div class="employer-details-xs">
                <h4>{{ $job_details['employer']['name'] }}</h4>
                <span class="company-name">{{ $job_details['employer']['company_name'] }}</span>
                <div class="rating-review">
                    <span class="rating-block">
                        {!! ___ratingstar($job_details['employer']['rating']) !!}
                    </span>
                    <a href="javascript:;" class="reviews-block">{{ $job_details['employer']['review'] }} Reviews</a>
                </div>
             </div>
        </div>
        <div class="employer-activity-xs">
             <ul>
                <li><strong>{{ $job_details['employer']['total_posted_jobs'] }} {{ trans('website.W0226') }}</strong> {{ trans('website.W0230') }}</li>
                <li><strong>{{ $job_details['employer']['total_hirings'] }} {{ trans('website.W0228') }}</strong> {{ trans('website.W0229') }}</li>
                <li><strong>{{ ___format($job_details['employer']['total_paid'],true,true,true) }} {{ trans('website.W0231') }}</strong> {{ trans('website.W0232') }}</li>
                <!-- <li><strong>$20 {{ trans('website.W0233') }}</strong> {{ trans('website.W0227') }}</li> -->
             </ul>
        </div>
    </div>
@endif