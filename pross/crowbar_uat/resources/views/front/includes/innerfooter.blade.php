<div class="footerWrapper">
    <div class="container">
        <div class="row">
            <div class="col-md-7 col-sm-7 col-xs-12 footer-left-sec">
                <div class="row">
                    <div class="col-md-3 col-sm-3 col-xs-6">
                        <div class="footerNav">
                            <p class="navTitle">About</p>
                            <ul>
                                <!-- <li><a href="{{ url('/') }}">Home</a></li> -->
                                <!-- <li><a href="{{ url('/page/about') }}">About</a></li> -->
                                <li><a href="javascript:void(0);">How It Works</a></li>
                                <li><a href="{{ url('/page/talent') }}">Talent</a></li>
                                <li><a href="{{ url('/page/employer') }}">Employer</a></li>
                                <!-- <li><a href="{{ url('/page/contact') }}">Contact Us</a></li> -->
                                <li><a href="{{ url('/page/pricing') }}">Pricing</a></li>
                                <!-- <li><a href="{{ url('/page/terms-and-conditions') }}">T&C</a></li> -->
                                <li><a href="{{ url('/page/faq') }}">FAQs</a></li>
                                <!-- <li><a href="{{ url('/page/privacy-policy') }}">Privacy Policy</a></li> -->
                                <!-- <li><a href="javascript:void(0);">Sitemap</a></li> -->
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 col-xs-6">
                        <div class="footerNav">
                            <p class="navTitle">Get Started</p>
                            <ul>
                                <li><a href="{{ url('/signup/talent') }}">Register as talent</a></li>
                                <li><a href="{{ url('/signup/employer') }}">Register as employer</a></li>
                                <li><a href="{{ url('/login') }}">Login</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 col-xs-6">
                        <div class="footerNav">
                            <p class="navTitle">Networks</p>
                            <ul>
                                <li><a href="{{$settings['social_linkedin_url']}}"/>LinkedIn</a></li>
                                <li><a href="{{$settings['social_facebook_url']}}"/>Facebook</a></li>
                                <li><a href="{{$settings['social_instagram_url']}}"/>Instagram</a></li>
                                <li><a href="{{$settings['social_youtube_url']}}"/>Youtube</a></li>
                                <li><a href="{{$settings['social_twitter_url']}}"/>Twitter</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-3 col-xs-6">
                        <div class="footerNav">
                            <p class="navTitle">Legal</p>
                            <ul>
                                <li><a href="{{ url('/page/dispute') }}">Dispute</a></li>
                                <li><a href="{{ url('/page/secure-payment') }}">Secure Payments</a></li>
                                <li><a href="{{ url('/page/terms-and-conditions') }}">T&C</a></li>
                                <li><a href="{{ url('/page/privacy-policy') }}">Privacy Policy</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-offset-1 col-md-4 col-sm-5 col-xs-12 footer-right-sec">
                <div class="footerNav">
                    <p class="navTitle">Download App</p>
                    <div class="downloadAppLinks">
                        <button type="button" class="appStoreBtn"></button>
                        <button type="button" class="playStoreBtn"></button>
                    </div>
                    <div class="copyright">
                        <p>© 2016 Crowbar. All rights reserved.</p>
                    </div>              
                </div>
            </div>
        </div>


        <!-- <div>
            <div class="col-md-7 col-sm-7 col-xs-12">
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                <div class="downloadAppLinks">
                    <button type="button" class="appStoreBtn"></button>
                    <button type="button" class="playStoreBtn"></button>
                </div>
            </div>
            <div class="col-md-5 col-sm-5 col-xs-12">
                <div class="subscriptionForm">
                    <p><strong>Stay In touch</strong></p>
                    <form role="add-talent" method="post" enctype="multipart/form-data" action="{{ url('newsletter-subscribed') }}">
                        <input type="hidden" name="_method" value="PUT">
                        {{ csrf_field() }}
                        <div class="formField form-group">
                            <input type="text" name="email" class="form-control">
                            <button type="button" data-request="ajax-submit" data-target='[role="add-talent"]' class="subscriptionBtn"></button>
                            <p><small>Subscribe to our newsletter to get the updates and know what’s happening around We don’t spam.</small></p>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        <div>
            <div class="col-md-7 col-sm-7 col-xs-12">
                <div class="row">
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="footerNav">
                            <p class="navTitle">Essentials</p>
                            <ul>
                                <li><a href="{{ url('/') }}">Home</a></li>
                                <li><a href="{{ url('/page/about') }}">About</a></li>
                                <li><a href="javascript:void(0);">How It Works</a></li>
                                <li><a href="{{ url('/page/contact') }}">Contact Us</a></li>
                                <li><a href="{{ url('/page/pricing') }}">Pricing</a></li>
                                <li><a href="{{ url('/page/terms-and-conditions') }}">T&C</a></li>
                                <li><a href="{{ url('/page/faq') }}">FAQs</a></li>
                                <li><a href="{{ url('/page/privacy-policy') }}">Privacy Policy</a></li>
                                <li><a href="javascript:void(0);">Sitemap</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="footerNav">
                            <p class="navTitle">Connect With Us</p>
                            <ul>
                                <li><a href="javascript:void(0);"><img src="{{ asset('images/visit-instagram.png') }}" />&nbsp;&nbsp;Instagram</a></li>
                                <li><a href="javascript:void(0);"><img src="{{ asset('images/visit-facebook.png') }}" />&nbsp;&nbsp;Facebook</a></li>
                                <li><a href="javascript:void(0);"><img src="{{ asset('images/visit-twitter.png') }}" />&nbsp;&nbsp;Twitter</a></li>
                                <li><a href="javascript:void(0);"><img src="{{ asset('images/visit-linked-in.png') }}" />&nbsp;&nbsp;LinkedIn</a></li>
                                <li><a href="javascript:void(0);"><img src="{{ asset('images/visit-google-plus.png') }}" />&nbsp;&nbsp;Google+</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-12">
                        <div class="footerNav">
                            <p class="navTitle">Get Started</p>
                            <ul>
                                <li><a href="{{ url('/login') }}">Login</a></li>
                                <li><a href="{{ url('/signup/employer') }}">Register As Job Provider</a></li>
                                <li><a href="{{ url('/signup/talent') }}">Register As Talent</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5 col-sm-5 col-xs-12">
                <div class="row languageForm">
                    <br>
                    <div class="col-md-12">
                        <p><strong>Select Language</strong></p>
                    </div>
                    <div class="col-md-6">
                        <form method="get" action="{{ url('/language') }}">
                            <select name="language" onchange="submit()" class="form-control">
                                @foreach(language() as $code => $language)
                                    <option value="{{ $code }}" @if(\Session::get('language') == $code) selected="selected" @endif>{{ $language }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="copyright">
                    <p>© 2016 Crowbar. All rights reserved.</p>
                </div>
            </div>
        </div> -->
    </div>
</div>
