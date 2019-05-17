    @extends('layouts.talent.main')

    {{-- ******INCLUDE CSS PAGE-WISE****** --}}
    @section('requirecss')
        <link href="{{ asset('css/jquery.easyselect.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/easy-responsive-tabs.css') }}" rel="stylesheet">
        <link href="{{ asset('css/jquery.nstSlider.css') }}" rel="stylesheet">
    @endsection
    {{-- ******INCLUDE CSS PAGE-WISE****** --}}

    {{-- ******INCLUDE INLINE-JS PAGE-WISE****** --}}
    @section('inlinecss')
        {{-- CODE WILL GO HERE --}}
    @endsection
    {{-- ******INCLUDE INLINE-JS PAGE-WISE****** --}}

    {{-- ******INCLUDE JS PAGE-WISE****** --}}
    @section('requirejs')
        <script src="{{ asset('js/easyResponsiveTabs.js') }}"></script>
        <script src="{{ asset('js/jquery.easyselect.min.js') }}"></script>
        <script src="{{ asset('js/moment.min.js') }}"></script>
        <script src="{{ asset('js/bootstrap-datetimepicker.min.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}"></script>
        <script src="{{ asset('js/jquery.nstSlider.js') }}"></script>
    @endsection
    {{-- ******INCLUDE JS PAGE-WISE****** --}}
    
    {{-- ******INCLUDE INLINE-JS PAGE-WISE****** --}}
    @section('inlinejs')
        {{-- CODE WILL GO HERE --}}
    @endsection
    {{-- ******INCLUDE INLINE-JS PAGE-WISE****** --}}

    @section('content')
            <div class="contentWrapper">
                <div class="afterlogin-section has-nobanner viewProfile">
                    <div class="container">
                        <div class="col-md-3 left-sidebar">
                            <div class="user-display-details">
                                <div class="user-display-image" data-toggle="modal" data-target="#uploadModal">
                                    <img src="{{ asset('images/zenith.jpg')}}" />
                                </div>
                                <ul class="user-profile-links">
                                    <li class="active"><a href="javascript:void(0);" title="{{trans('website.W0015')}}">{{trans('website.W0015')}}</a></li>
                                    <li><a href="javascript:void(0);" title="{{trans('website.W0016')}}">{{trans('website.W0016')}}<span>(3)</span></a></li>
                                    <li><a href="javascript:void(0);" title="{{trans('website.W0017')}}">{{trans('website.W0017')}}<span>(23)</span></a></li>
                                </ul>
                            </div>
                            <div class="profile-completion-block">
                                <h3>{{trans('website.W0018')}}<span>80%</span></h3>
                                <div class="completion-bar">
                                    <span style="width: 80%;"></span>
                                </div>
                                <ul class="completion-list-group">
                                    <li class="completed">{{trans('website.W0019')}}</li>
                                    <li class="completed">{{trans('website.W0020')}}</li>
                                    <li class="completed">{{trans('website.W0021')}}</li>
                                    <li class="completed">{{trans('website.W0022')}}</li>
                                    <li class="completed">{{trans('website.W0023')}}</li>
                                    <li>{{trans('website.W0024')}}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-9 right-sidebar">
                            <div class="user-view-block">
                                <h2>Zenith Beckham</h2>
                                <span class="user-email">zenithb569@hotmail.com</span>
                                <div class="rating-review"><span class="rating-block"><img src="{{ asset('images/star-rating.png')}}" /></span><a href="javascript:void(0);" class="reviews-block">23 Reviews</a>
                                </div>
                                <div class="skills-tag">
                                    <a href="javascript:void(0);" >{{trans('website.W0025')}}</a><a href="javascript:void(0);">{{trans('website.W0026')}}</a><a href="javascript:void(0);">{{trans('website.W0027')}}</a><a href="javascript:void(0);" style="display: none;">3D Designer</a><a href="javascript:void(0);" style="display: none;">{{trans('website.W0028')}}</a>
                                    <span class="show-more">+ 5 More</span>
                                </div>
                            </div>
                            <div class="inner-profile-section">
                                <div id="parentHorizontalTab">
                                    <ul class="resp-tabs-list hor_1 clearfix">
                                        <li>{{trans('website.W0029')}}</li>
                                        <li>{{trans('website.W0030')}}</li>
                                        <li>{{trans('website.W0031')}}</li>
                                        <li>{{trans('website.W0032')}}</li>
                                        <li>{{trans('website.W0033')}}</li>
                                    </ul>
                                    <div class="resp-tabs-container hor_1">
                                        <div>
                                            <form class="form-horizontal" action="" method="get" accept-charset="utf-8">
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0034')}}</label>
                                                    <div class="col-md-9">
                                                        <div class="checkbox checkbox-inline">                
                                                            <input type="checkbox" id="int01">
                                                            <label for="int01"><span class="check"></span> {{trans('website.W0035')}}</label>
                                                        </div>
                                                        <div class="checkbox checkbox-inline">                
                                                            <input type="checkbox" id="int02">
                                                            <label for="int02"><span class="check"></span> {{trans('website.W0036')}}</label>
                                                        </div>
                                                        <div class="checkbox checkbox-inline">                
                                                            <input type="checkbox" id="int03">
                                                            <label for="int03"><span class="check"></span> {{trans('website.W0037')}}</label>
                                                        </div>
                                                        <div class="checkbox checkbox-inline">                
                                                            <input type="checkbox" id="int04">
                                                            <label for="int04"><span class="check"></span> {{trans('website.W0038')}}</label>
                                                        </div>
                                                        <div class="checkbox checkbox-inline">                
                                                            <input type="checkbox" id="int05">
                                                            <label for="int05"><span class="check"></span> {!! trans('website.W0039') !!}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0040')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="Expected Salary" placeholder="{{trans('website.W0069')}}" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0041')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="Other Perks" placeholder="{{trans('website.W0070')}}" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="col-md-12"><div class="checkbox">                
                                                            <input type="checkbox" id="terms">
                                                            <label for="terms">
                                                                <span class="check"></span>
                                                                {!! sprintf(trans('website.W0042'),'<a href="javascript:void(0);">'.trans('website.W0043').'</a>','<a href="javascript:void(0);">'.trans('website.W0044').'</a>') !!}
                                                            </label>
                                                        </div></div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="col-md-12"><div class="checkbox">                
                                                            <input type="checkbox" id="pricing">
                                                            <label for="pricing">
                                                                <span class="check"></span>{!! sprintf(trans('website.W0045'),'<a href="javascript:void(0);">'.trans('website.W0046').'</a>')!!}</label>
                                                        </div></div>
                                                </div>
                                                <div class="form-separator"></div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0047')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="row">
                                                            <div class="col-md-4 col-sm-4 col-xs-4 day-select">
                                                                <div class="custom-dropdown">
                                                                    <select name="day " class="form-control">
                                                                    <option value="">DD</option>
                                                                        {!! ___date_range(range(1, 31)) !!}
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 col-sm-4 col-xs-4 month-select">
                                                                <div class="custom-dropdown">
                                                                    <select name="month " class="form-control">
                                                                    <option value="">MM</option>
                                                                        {!! ___date_range(trans('website.W0048')) !!}
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 col-sm-4 col-xs-4 year-select">
                                                                <div class="custom-dropdown">
                                                                    <select name="year " class="form-control">
                                                                    <option value="">YYYY</option>
                                                                    {!!___date_range(range(2015, 1970))!!}
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0049')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="radio radio-inline">                
                                                            <input name="gender" type="radio" id="gen01">
                                                            <label for="gen01">{{trans('website.W0050')}}</label>
                                                        </div>
                                                        <div class="radio radio-inline">                
                                                            <input name="gender" type="radio" id="gen02">
                                                            <label for="gen02">{{trans('website.W0051')}}</label>
                                                        </div>
                                                        <div class="radio radio-inline">                
                                                            <input name="gender" type="radio" id="gen03">
                                                            <label for="gen03">{{trans('website.W0052')}}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0053')}}</label>
                                                    <div class="col-md-6 phonenumber-field">
                                                        <div class="custom-dropdown countrycode-dropdown">
                                                        <select name="mcode" class="form-control">
                                                        {!!___date_range($phone_codes)!!}
                                                        </select>
                                                        </div>
                                                        <input type="text" name="Phone Number" placeholder="{{trans('website.W0071')}}" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0054')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="Address" placeholder="{{trans('website.W0072')}}" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{sprintf(trans('website.W0055'),'')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select class="form-control">
                                                            <option value="">{{sprintf(trans('website.W0055'),trans('website.W0067'))}}</option>
                                                            {!!___date_range($country_name)!!}
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{sprintf(trans('website.W0056'),'')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select class="form-control">
                                                                <option value="">{{sprintf(trans('website.W0056'),trans('website.W0067'))}}</option>
                                                                {!!___date_range($state_name)!!}
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0057')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="Postal Code" placeholder="{{trans('website.W0073')}}" class="form-control">
                                                    </div>
                                                </div>
                                                <div class="form-group button-group">
                                                    <div class="col-md-6 col-sm-offset-2">
                                                        <div class="row">
                                                            <div class="col-md-6">&nbsp;</div>
                                                            <div class="col-md-6"><button type="button" class="button" value="Save">{{trans('website.W0058')}}</button></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div>
                                            <form class="form-horizontal" action="" method="get" accept-charset="utf-8">
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{sprintf(trans('website.W0059'),'')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select name="day " class="form-control">
                                                                <option value="">{{sprintf(trans('website.W0059'),trans('website.W0068'))}}</option>
                                                                {!!___date_range($industries_name)!!}
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{sprintf(trans('website.W0060'),'')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select name="day " class="form-control">
                                                                <option value="">{{sprintf(trans('website.W0060'),trans('website.W0068'))}}</option>
                                                                {!!___date_range($industries_name)!!}
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0061')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select id="skills" multiple>
                                                                <option value="01" selected>Experience Designer</option>
                                                                <option value="02" selected>Experience Visualizer</option>
                                                                <option value="03" selected>3D Designer</option>
                                                                <option value="04" selected>Graphic Designer</option>
                                                                <option value="05" selected>UX Expert</option>
                                                                <option value="06">Skills01</option>
                                                                <option value="07">Skills02</option>
                                                                <option value="08">Skills03</option>
                                                                <option value="09">Skills04</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0062')}}</label>
                                                    <div class="col-md-6">
                                                    @foreach(expertise_levels() as $key => $value)
                                                        <div class="radio radio-inline">                
                                                            <input name="expertise" type="radio" id="expert01" value="{{$value['level']}}">
                                                            <label for="expert01">{{$value['level_name']}}</label>
                                                        </div>
                                                    @endforeach
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0065')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="no_of_years" placeholder="{{trans('website.W0074')}}" class="form-control" />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2 has-biglabel">{!!trans('website.W0075')!!}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select name="mcode" class="form-control">
                                                                <option value="">{!!trans('website.W0077')!!}</option>
                                                                @foreach(work_rate() as $key => $value)
                                                                <option value="{{$value['level']}}">{{$value['level_name']}}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0054')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="Address" placeholder="{{trans('website.W0072')}}" class="form-control" />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0077')}}</label>
                                                    <div class="col-md-6">
                                                        <textarea name="Other Details" class="form-control" placeholder="{{trans('website.W0078')}}"></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2 has-biglabel">{!!trans('website.W0079')!!}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="Industry Affiliations  & Certifications" placeholder="{{trans('website.W0080')}}" class="form-control" />
                                                        <div class="add-more"><a href="javascript:void(0);">{{trans('website.W0081')}}</a></div>
                                                    </div>
                                                </div>
                                                <div class="form-group button-group">
                                                    <div class="col-md-6 col-sm-offset-2">
                                                        <div class="row">
                                                            <div class="col-md-6">&nbsp;</div>
                                                            <div class="col-md-6"><button type="button" class="button" value="Save">{{trans('website.W0058')}}</button></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div>
                                            <form class="form-horizontal" action="" method="get" accept-charset="utf-8">
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0082')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="School / College" placeholder="{{trans('website.W0083')}}" class="form-control" />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0084')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select name="Degree" class="form-control">
                                                                <option value="">{{trans('website.W0085')}}</option>
                                                                {!!___date_range($degree_name)!!}
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0086')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select name="Year of Passing" class="form-control">
                                                            <option value="">{{trans('website.W0087')}}</option>
                                                            {!!___date_range(range(2015, 1970))!!}
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0088')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="Area of Study" placeholder="{{trans('website.W0089')}}" class="form-control" />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0090')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select name="Degree" class="form-control">
                                                                <option value="">{{trans('website.W0091')}}</option>
                                                                @foreach(degree_status() as $key => $value)
                                                                <option value="{{$value['level']}}">{{$value['level_name']}}</option>
                                                                @endforeach;
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{sprintf(trans('website.W0092'),'')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select class="form-control">
                                                            <option value="">{{sprintf(trans('website.W0092'),trans('website.W0067'))}}</option>
                                                            {!!___date_range($country_name)!!}
                                                            </select>
                                                        </div>
                                                        <div class="add-more"><a href="javascript:void(0);">{{trans('website.W0093')}}</a></div>
                                                    </div>
                                                </div>
                                                <div class="form-group button-group">
                                                    <div class="col-md-6 col-sm-offset-2">
                                                        <div class="row">
                                                            <div class="col-md-6">&nbsp;</div>
                                                            <div class="col-md-6"><button type="button" class="button" value="Save">{{trans('website.W0058')}}</button></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div>
                                            <form class="form-horizontal" action="" method="get" accept-charset="utf-8">
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0094')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="No. of Years" placeholder="{{trans('website.W0095')}}" class="form-control" />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0096')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="No. of Years" placeholder="{{trans('website.W0097')}}" class="form-control" />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0098')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="row">
                                                            <div class="col-md-6 col-sm-6 col-xs-6 month-start">
                                                                <div class="custom-dropdown">
                                                                    <select name="month " class="form-control">
                                                                        <option value="">{{trans('website.W0100')}}</option>
                                                                        {!! ___date_range(trans('website.W0048')) !!}
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 col-sm-6 col-xs-6 year-start">
                                                                <div class="custom-dropdown">
                                                                    <select name="year " class="form-control">
                                                                    <option value="">{{trans('website.W0103')}}</option>
                                                                    {!!___date_range(range(2015, 1970))!!}
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0099')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="radio radio-inline">                
                                                            <input name="c_work" type="radio" id="c_work01">
                                                            <label for="c_work01"> {{trans('website.W0101')}}</label>
                                                        </div>
                                                        <div class="radio radio-inline">                
                                                            <input name="c_work" type="radio" id="c_work02">
                                                            <label for="c_work02"> {{trans('website.W0102')}}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0104')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="radio radio-inline">                
                                                            <input name="t_job" type="radio" id="t_job01">
                                                            <label for="t_job01">{{trans('website.W0105')}}</label>
                                                        </div>
                                                        <div class="radio radio-inline">                
                                                            <input name="t_job" type="radio" id="t_job02">
                                                            <label for="t_job02">{{trans('website.W0106')}}</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0107')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="row">
                                                            <div class="col-md-6 col-sm-6 col-xs-6 month-start">
                                                                <div class="custom-dropdown">
                                                                    <select name="month " class="form-control">
                                                                    <option value="">{{trans('website.W0100')}}</option>
                                                                    {!! ___date_range(trans('website.W0048')) !!}
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6 col-sm-6 col-xs-6 year-start">
                                                                <div class="custom-dropdown">
                                                                    <select name="year " class="form-control">
                                                                    <option value="">{{trans('website.W0103')}}</option>
                                                                    {!!___date_range(range(2015, 1970))!!}
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{sprintf(trans('website.W0092'),'')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="custom-dropdown">
                                                            <select class="form-control">
                                                            <option value="">{{sprintf(trans('website.W0092'),trans('website.W0067'))}}</option>
                                                                {!!___date_range($country_name)!!}
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-2">{{sprintf(trans('website.W0056'),'')}}</label>
                                                    <div class="col-md-6">
                                                        <input type="text" name="State/ Province" placeholder="{{trans('website.W0108')}}" class="form-control" />
                                                        <div class="add-more"><a href="javascript:void(0);">{{trans('website.W0109')}}</a></div>
                                                    </div>
                                                </div>
                                                <div class="form-separator"></div>
                                                <div class="form-group coverletter-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0110')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="uploaded-docx"></div>
                                                        <textarea name="Other Details" class="form-control" placeholder="{{trans('website.W0111')}}"></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-separator"></div>
                                                <div class="form-group attachment-group">
                                                    <label class="control-label col-md-2">{{trans('website.W0112')}}</label>
                                                    <div class="col-md-6">
                                                        <div class="uploaded-docx clearfix">
                                                            <img src="{{ asset('images/attachment-icon.png')}}" />
                                                            <div class="upload-info">
                                                                <p>Resume.pdf</p>
                                                                <span>19.5 KB</span>
                                                            </div>
                                                            <a href="javascript:void(0);"><img src="{{ asset('images/close-icon-md.png')}}" /></a>
                                                        </div>
                                                        <div class="fileUpload upload-docx"><span>{{trans('website.W0113')}}</span><input type="file" class="upload" /></div>
                                                        <span class="upload-hint">{{trans('website.W0114')}}</span>
                                                    </div>
                                                </div>
                                                <div class="form-group button-group">
                                                    <div class="col-md-6 col-sm-offset-2">
                                                        <div class="row">
                                                            <div class="col-md-6">&nbsp;</div>
                                                            <div class="col-md-6"><button type="button" class="button" value="Save">{{trans('website.W0058')}}</button></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div>
                                            <form class="form-horizontal verify-account-form" action="" method="get" accept-charset="utf-8">
                                                <div class="form-group">
                                                    <label class="control-label col-md-5">{{sprintf(trans('website.W0115'),trans('website.W0116'))}}</label>
                                                    <a href="javascript:void(0);" class="button-green">{{trans('website.W0118')}}</a>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-5">{{sprintf(trans('website.W0115'),trans('website.W0119'))}}</label>
                                                    <a href="javascript:void(0);" class="button-green">{{trans('website.W0118')}}</a>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-5">{{sprintf(trans('website.W0115'),trans('website.W0120'))}}</label>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-5">{{sprintf(trans('website.W0115'),trans('website.W0121'))}}</label>
                                                    <a href="javascript:void(0);" class="button-green">{{trans('website.W0118')}}</a>
                                                </div>
                                                <div class="form-group">
                                                    <label class="control-label col-md-5">{{sprintf(trans('website.W0115'),trans('website.W0122'))}}</label>
                                                    <a href="javascript:void(0);" class="button-green">{{trans('website.W0124')}}</a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    @endsection
