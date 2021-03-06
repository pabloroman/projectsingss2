<div class="col-md-4 col-sm-12 left-sidebar clearfix">
    <div class="user-info-wrapper user-info-greyBox viewProfileBox clearfix">
        <div class="profile-left">
            <div class="user-profile-image">
                <div class="user-display-details">
                    <div class="user-display-image" style="background: url('{{ $user['picture'] }}') no-repeat center center;background-size:100% 100%"></div>
                </div>
            </div>
        </div>
        <div class="profile-right no-padding">
            <div class="user-profile-details">
                <div class="item-list">
                    <div class="rating-review">
                        <span class="rating-block">
                            {!! ___ratingstar($user['rating']) !!}
                        </span>
                        <a href="{{ url(sprintf('%s/profile/reviews',TALENT_ROLE_TYPE)) }}" class="reviews-block underline">{{ $user['review'] }} {{trans('website.W0213')}}</a>
                    </div>
                </div>
                @if(!empty($user['remuneration']))
                    <div class="item-list">
                        <span class="item-heading">{{trans('website.W0660')}}</span>
                        <span class="item-description">
                            @foreach($user['remuneration'] as $item)
                                @if($item['interest'] != 'fixed')
                                    {!!sprintf('%s/%s',___currency($item['workrate'],true,true),substr($item['interest'],0,-2)).'<br>'!!}
                                @else
                                    <span class="label-green color-grey">{{$item['interest']}}</span>
                                @endif
                            @endforeach
                        </span>
                    </div>
                @endif
                @if(!empty($user['country_name']))
                    <div class="item-list">
                        <span class="item-heading">{{trans('website.W0201')}}</span>
                        <span class="item-description">{{$user['country_name']}}</span>
                    </div>
                @endif
            </div>        
        </div>
        <div class="clearfix"></div>
        <div class="profile-completion-block profile-completion-list">
            <div class="edit-bar">
                <div class="completion-bar">
                    <span style="width: {{ ___decimal($user['profile_percentage_count']) }}%;">
                        <span class="percentage-completed floated-percent">{{ ___decimal($user['profile_percentage_count']) }}%</span>
                    </span>
                </div>
                <a title="{{trans('website.W0255')}}" href="{{url(sprintf('%s/profile/step/one',TALENT_ROLE_TYPE))}}"><img src="{{asset('images/big-pencil-con.png')}}"></a>
            </div>
        </div>
        <div class="view-profile-name">
            <div class="user-name-info">
                <p>{{ sprintf("%s %s",$user['first_name'],$user['last_name']) }}</p>
            </div>
            <div class="profile-expertise-column">
                @if(!empty($user['expertise']))
                    <span class="label-green color-grey">{{ expertise_levels($user['expertise']) }}</span>
                @endif
                @if(!empty($user['experience']))
                    <span class="experience">{{ sprintf("%s %s",$user['experience'],trans('website.W0669')) }}</span>
                @endif
            </div>
        </div>
        @php
            $shareUrl = '';
            if(!empty($user['first_name']) && !empty($user['id_user'])){
                $shareUrl = url('/showprofile/'.strtolower($user['first_name']).'-'.strtolower($user['last_name']).'/'.$user['id_user']);
            } 
        @endphp

        @if(!empty($shareUrl))
            <br/>
            <div class="talent-share-profile clearfix">
                <span class="item-description talent-share-links">{{trans('website.W0948')}}</span>
                <ul>
                    <li>
                        <a href="javascript:void(0);" class="linkdin_icon_talent">
                            <script src="//platform.linkedin.com/in.js" type="text/javascript"> lang: en_US</script>
                            <script type="IN/Share" data-url="{{$shareUrl}}"></script>
                            <img src="{{asset('images/linkedin.png')}}">
                        </a>
                    </li>
                    <li>
                        <a class="fb_icon" href="https://www.facebook.com/sharer/sharer.php?u={{$shareUrl}}" target="_blank"><img src="{{asset('images/facebook.png')}}">
                        </a>
                    </li>
                    <li>
                        <a href="{{ $shareUrl }}" target="_blank" id="whatsapp_link" data-action="share/whatsapp/share" ><img src="{{asset('images/whatsapp-logo.png')}}"></a>
                    </li>
                </ul>
            </div>
        @endif
        <div class="talent-share-profile clearfix">
            <a href="{{$shareUrl}}" target="_blank" class="">Public Url</a>
        </div>
    </div>
</div>
@push('inlinescript')
<script type="text/javascript">
    $(document).ready(function(){
        // console.log("page ready");
        // console.log("link is- "+'{{ $shareUrl }}');
        //Change Whatsapp link according to Web or Mobile
        var isMobile1 = window.orientation > -1;
        isMobile1 = isMobile1 ? 'Mobile' : 'Not mobile';
        if(isMobile1 == 'Mobile'){
            //Whatsapp Mobile link share
            $('#whatsapp_link').attr('href','whatsapp://send?text={{ $shareUrl }}');
        }else{
            //Whatsapp Web link share
            $('#whatsapp_link').attr('href','https://web.whatsapp.com/send?text={{ $shareUrl }}');
        }
    });
</script>
@endpush