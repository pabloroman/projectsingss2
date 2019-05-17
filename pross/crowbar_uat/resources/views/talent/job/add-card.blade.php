@section('content')        
    <div class="contentWrapper">
        <div class="postjob-beforesubmit premiumAccount">
            <div class="container">
                <div class="right-sidebar">
                    <h2>Manage Card</h2>
                    <div class="premiumAccountSec">
                        <form class="form-horizontal" role="add_card" action="{{url(sprintf('%s/add_payment_card',TALENT_ROLE_TYPE))}}" method="post" accept-charset="utf-8">
                            <div class="card-box">
                                @php
                                    foreach ($user_card as $key => $item){
                                        $url_delete = sprintf(
                                                    url('ajax/%s?card_id=%s'),
                                                    DELETE_CARD,
                                                    $item['id_card']
                                                );
                                        echo sprintf(
                                                ADD_CARD_TEMPLATE,
                                                $item['id_card'],
                                                $item['image_url'],
                                                $item['masked_number'],
                                                $url_delete,
                                                $item['id_card'],
                                                url('/'),
                                                url('/')
                                            );
                                    }
                                @endphp
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-2">Card Number</label>
                                <div class="col-md-4">
                                    <input type="text" name="credit_card[number]" placeholder="Enter card number" class="form-control" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-2">Card Holder Name</label>
                                <div class="col-md-4">
                                    <input type="text" name="credit_card[cardholder_name]" placeholder="Enter card holder's name" class="form-control" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-2">Expiry Month</label>
                                <div class="col-md-4">
                                    <input type="text" name="credit_card[expiry_month]" placeholder="Enter card expiry month" class="form-control" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-2">Expiry Year</label>
                                <div class="col-md-4">
                                    <input type="text" name="credit_card[expiry_year]" placeholder="Enter card expiry year" class="form-control" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-md-2">CVV</label>
                                <div class="col-md-4">
                                    <input type="text" name="credit_card[cvv]" placeholder="Enter card cvv number" class="form-control" />
                                </div>
                            </div>
                            <div class="form-group button-group">
                                <div class="col-md-4 col-sm-offset-2">
                                    <div class="row">
                                        <div class="col-md-6"></div>
                                        <div class="col-md-6">
                                            <button type="button" data-box=".card-box" data-request="multi-ajax" data-target='[role="add_card"]' data-box-id="[name='id_card']" data-toremove="box" class="button" value="Add Card">Add Card</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection