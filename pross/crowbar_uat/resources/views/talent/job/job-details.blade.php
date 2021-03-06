<div class="contentWrapper job-details-section">
    <div class="container">
        <div class="row mainContentWrapper">
            <div class="col-md-9 job-details-left">
                <h2 class="form-heading">{{ $job_details['title'] }}
                    @if($page == 'detail')
                        @if(substr(url()->previous(), strrpos(url()->previous(), '/') + 1) == 'find-jobs')
                            <a href="{{ url()->previous() }}" class="back-to-results">
                                {{trans('job.J00105')}}
                            </a>
                        @elseif(substr(url()->previous(), strrpos(url()->previous(), '/') + 1) == 'saved' || substr(url()->previous(), strrpos(url()->previous(), '/') + 1) == 'my-jobs')
                            <a href="{{ url()->previous() }}" class="back-to-results">
                                {{trans('job.J00106')}}
                            </a>
                        @elseif(substr(url()->previous(), strrpos(url()->previous(), '/') + 1) == 'current')
                            <a href="{{ url()->previous() }}" class="back-to-results">
                                {{trans('job.J00107')}}
                            </a>
                        @elseif(substr(url()->previous(), strrpos(url()->previous(), '/') + 1) == 'scheduled')
                            <a href="{{ url()->previous() }}" class="back-to-results">
                                {{trans('job.J00108')}}
                            </a>
                        @elseif(substr(url()->previous(), strrpos(url()->previous(), '/') + 1) == 'history')
                            <a href="{{ url()->previous() }}" class="back-to-results">
                                {{trans('job.J00109')}}
                            </a>
                        @endif
                    @endif
                </h2>
                <div class="content-box">              
                    <div class="content-box-header clearfix">
                        <img src="{{asset($job_details['company_logo'])}}" alt="profile" class="job-profile-image">
                        <div class="contentbox-header-title">
                            <h3>
                                <a href="javascript:void(0);">{{ $job_details['title'] }}</a>
                                @if($job_details['employment'] == 'fulltime')
                                    <span class="label-green">{{ trans('website.W0039') }}</span>
                                @endif                            
                            </h3>
                            <span class="company-name">{{ $job_details['company_name'] }}</span>
                        </div>
                        <div class="contentbox-price-range">
                            <span>  
                                {{ ___format($job_details['price'],true,true) }}
                                
                                @if(!empty($job_details['price_max']))
                                    {{ ' - '.___format($job_details['price_max'],true,true) }}
                                @endif
                                {{ job_types_rates_postfix($job_details['employment']) }}</span>
                            <small>{{ trans(sprintf('general.%s',$job_details['budget_type'])) }}</small>
                        </div>    
                    </div>
                    <div class="contentbox-minutes clearfix">
                        <div class="minutes-left">
                            <span>{{ trans('job.J0003')}}: <strong>{{ $job_details['industry_name'] }}</strong></span>
                            
                            @if($job_details['employment'] !== 'fulltime') 
                                <span>{{trans('job.J0004')}}: <strong>{{ ___d($job_details['startdate']).' - '.___d($job_details['enddate']) }}</strong></span>
                            @else
                                @if(!empty($job_details['bonus']))
                                    <span>{{ trans('website.W0292') }}: <strong>{{ ___format($job_details['bonus'],true,true) }}</strong></span>
                                @endif

                                @if(!empty($job_details['location_name']))
                                    <span>{{ trans('website.W0291') }}: <strong>{{ $job_details['location_name'] }}</strong></span>
                                @endif
                            @endif

                            <span>{{ trans('website.W0293') }}: <strong>{{ employment_types('post_job',$job_details['employment']) }}</strong></span>
                            
                            @if(!empty($job_details['expertise'])) 
                                <span>
                                    {{trans('job.J0006')}}: 
                                    <strong>
                                        {{expertise_levels($job_details['expertise']) }}
                                    </strong>
                                </span>
                            @endif
                        </div>
                        <div class="minutes-right">
                            <span class="posted-time">{{ $job_details['created'] }}</span>
                        </div>
                    </div>
                    <div class="content-box-description">
                        <p>{!! nl2br($job_details['description']) !!}</p>

                        <div class="required-info-list">
                            <span class="required-info">
                                {{trans('job.J0008')}}: 
                                @if(!empty($job_details['skills']))
                                    {!! ___tags($job_details['skills'],'<strong>%s</strong>') !!}
                                @else
                                    <strong>{{ N_A }}</strong>
                                @endif
                            </span>

                            @if($job_details['employment'] == 'fulltime')
                                <span class="required-info">
                                    {{trans('job.J0009')}}: 
                                    @if(!empty($job_details['qualifications']))
                                        {!! ___tags($job_details['qualifications'],'<strong>%s</strong>') !!}
                                    @else
                                        <strong>{{ N_A }}</strong>
                                    @endif
                                </span>                      
                            @endif  
                        </div>
                        @if($page == 'detail')
                            <div class="checkbox">
                                @if($job_details['is_saved'] == DEFAULT_YES_VALUE)
                                    <input type="checkbox" checked="checked" data-request="inline-ajax" data-url="{{ url(sprintf('%s/jobs/save-job?job_id=%s',TALENT_ROLE_TYPE,$job_details['id_project'])) }}" id="job-{{$job_details['id_project']}}">
                                @else
                                    <input type="checkbox" data-request="inline-ajax" data-url="{{ url(sprintf('%s/jobs/save-job?job_id=%s',TALENT_ROLE_TYPE,$job_details['id_project'])) }}" id="job-{{$job_details['id_project']}}">
                                @endif
                                <label for="job-{{$job_details['id_project']}}"><span class="check"></span>Save Job</label>
                            </div>
                        @endif
                    </div>
                </div>

                @if($page == 'detail')
                <div>
                    <ul class="payment-tabs-wrapper">
                        <li class="active-tab"><a href="javascript:void(0);" data-request="datatable" data-id="{{$job_details['company_id']}}">{{ trans('job.J0011') }}</a></li>
                        <li class="resp-tab-item"><a href="javascript:void(0);" data-request="datatable" data-id="{{\Auth::user()->id_user}}">{{ trans('job.J0012') }}</a></li>
                    </ul>
                    <div class="payment-tabs job-related-tabs">
                        <div class="datatable-listing">
                            <div data-target="reviews">
                                <div class="no-table">
                                    {!! $html->table() !!}
                                </div>
                            </div>                        
                        </div>
                    </div>
                </div>
                @elseif($page == 'review')
                    @includeIf('talent.review.view')
                @endif
            </div>
            <div class="col-md-3 job-details-right">            
                @if($page == 'detail')
                    <div data-request="job-actions" data-url="{{ url(sprintf('%s/job/actions?job_id=%s',TALENT_ROLE_TYPE,___encrypt($job_details['id_project']))) }}"></div>
                @endif

                @includeIf('talent.job.include.about-employer')          

                @includeIf('talent.job.include.employer-invitation')          
                
                @includeIf('talent.job.include.employer-other-jobs')

                @includeIf('talent.job.include.similar-jobs')
                
            </div>
            
        </div>

    </div>
</div>
@includeIf('talent.job.raise-dispute-popup')
@push('inlinescript')
    <script src="{{ asset('js/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap.js') }}"></script>
    {!! $html->scripts() !!}
    <script type="text/javascript">
        $(function(){
            $(document).on('click','[data-request="raiseDisputeModal"]',function(){
                $('#raiseDisputeModal').modal('show');
            });

            $('.no-table thead').remove();
            
            $(document).on('click','[data-request="datatable"]',function(e){
                e.preventDefault();
                
                var $this = $(this);

                $('.active-tab').removeClass('active-tab');
                $this.parent().addClass('active-tab');
                
                LaravelDataTables["dataTableBuilder"].on('preXhr.dt', function ( e, settings, data ) {
                    data.user_id    = $this.data('id');
                }); 

                window.LaravelDataTables.dataTableBuilder.draw();
            });
        });
    </script>
@endpush
