@section('content')
    <div class="contentWrapper">
        <div class="postjob-beforesubmit premiumAccount">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="right-sidebar" style="padding: 0;padding-top: 80px;">
                            <h2 class="form-heading">Upgrade To Premium Account</h2>
                        </div>
                        <div class="table-scroll">
                            <div class="premiumAccountSec premiumAccountSecList">

                                <ul class="payment-blocks clearfix">
                                    <li> 
                                        <a href="javascript:void(0);" class="total-amount-block"> <span class="payment-text"> <h3>Features</h3></a>
                                    </li>
                                     @foreach($plan['plan'] as $p)
                                        @php $s_plan = explode(',',$p['feature']); @endphp
                                            <li> 
                                                <a href="javascript:void(0);" class="total-amount-due-block"> 
                                                    <span class="payment-text">
                                                        <h3>{{$p['name']}}</h3> 
                                                        <span>{{___format($p['price'], true, true)}}</span>
                                                        <span class="plan-heading">{{$p['plan_detail']}}</span>
                                                    </span> 
                                                </a> 
                                            </li>
                                    @endforeach                         
                                </ul>
                                <table class="table premium-content-table">
                                    <tr>
                                        @foreach($plan['plan_features'] as $feature)
                                            <td>{{$feature['name']}}</td>
                                        @endforeach                                        
                                    </tr>

                                    @foreach($plan['plan'] as $p)
                                        @php $s_plan = explode(',',$p['feature']); @endphp
                                        <tr>
                                            @foreach($plan['plan_features'] as $feature)
                                                <td>
                                                    @if(in_array($feature['id_feature'], $s_plan))
                                                        <span class="tickIcon"></span>
                                                    @else
                                                        <span class="crossIcon"></span>
                                                    @endif
                                                </td>
                                            @endforeach                                            
                                        </tr>
                                    @endforeach
                                </table>
                            </div>                                                    
                        </div>
                        <div class="plan-upgrade">
                            <a class="button" href="{{ url('employer/plan-purchase/' . ___encrypt($p['id_plan'])) }}">Pay to upgrade</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection