<div class="headerWrapper">
    <div class="afterlogin-header employer-header">
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                @includeIf('language')
                <div class="row">
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="navbar-header">
                            <a href="{{ url(sprintf('%s/find-talents',EMPLOYER_ROLE_TYPE)) }}" class="navbar-brand logo">
                                <img src="{{ asset('images/splashLogo.png') }}" class="web-logo">
                                <img src="{{ asset('images/responsive-logo.png') }}" class="responsive-logo">
                            </a>
                        </div>
                    </div>                
                    <div class="col-md-4 col-sm-4 col-xs-12 pull-right account-block">
                        <div class="header-innerWrapper">
                            <div class="collapse navbar-collapse pull-right" id="bs-example-navbar-collapse-1">
                                <ul class="nav navbar-nav">
                                    <li>
                                        <a href="{{ url(sprintf('/%s/chat',EMPLOYER_ROLE_TYPE)) }}" class="message-notification"><span data-target="chat-count" style="display: none;"></span></a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" id="notification-toggle" class="notification notification-toggle"><span data-target="notification-count" style="display: none;"></span></a>
                                        <ul class="dropdown-submenu notification-submenu" data-target="notification-list">
                                            <li><img style="margin: 30px auto 0;display: inherit;height: 20px;" src="{{asset('/images/loading.gif')}}"></li>
                                        </ul>
                                    </li>
                                    <li>
                                        <a id="usermenu-toggle" href="javascript:void(0);" class="username">
                                            <span class="hidden-xs" style="display: inline-block;text-align: right;"><span class="hello-msg">Hello</span><br>{{ sprintf("%s",$user['first_name']) }}</span>
                                            @if(0)
                                                <img src="{{ url($user['picture']) }}" height="37" alt="{{ sprintf("%s %s",$user['first_name'],$user['last_name']) }}" />
                                            @endif
                                           <img src="{{ asset('images/user-icon.png') }}" alt="user">
                                        </a>
                                        <ul class="dropdown-submenu usermenu-submenu">
                                            <li><a href="{{ url(sprintf('%s/profile',EMPLOYER_ROLE_TYPE))}}">{{ trans('website.W0606') }}</a></li>
                                            <li><a href="{{ url(sprintf('%s/profile/edit/one',EMPLOYER_ROLE_TYPE)) }}">{{ trans('website.W0610') }}</a></li>
                                            <li><a href="{{ url(sprintf('%s/settings',EMPLOYER_ROLE_TYPE)) }}">{{ trans('website.W0598') }}</a></li>
                                            {{-- <li><a href="{{ url(sprintf('%s/payment/card/manage',EMPLOYER_ROLE_TYPE)) }}">{{ trans('website.W0607') }}</a></li> --}}
                                            <li><a href="{{ url(sprintf('%s/invitation-list',EMPLOYER_ROLE_TYPE)) }}">{{ trans('website.W0703') }}</a></li>
                                            <li><a href="{{ url('logout') }}">{{ trans('website.W0609') }}</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        </div>                    
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12 search-block">
                        
                    </div>                    
                </div>
            </div>
        </nav>
    </div>
    @includeIf($subheader)
</div>
