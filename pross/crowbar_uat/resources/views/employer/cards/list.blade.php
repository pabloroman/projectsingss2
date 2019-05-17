
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <h3 class="form-heading">{{trans('website.W0428')}}</h3>
        <form class="form-horizontal" role="select_card" action="{{url(sprintf('%s/payment/card/select',EMPLOYER_ROLE_TYPE))}}" method="post" accept-charset="utf-8">
            {{csrf_field()}}
            <div class="modal-body no-background no-padding">
            	<div class="card-box modal-card-box" style="padding-top: 10px;">
                    @php
                        foreach ($cards as $key => $item){
                            $url_delete = sprintf(
                                url('%s/payment/card/delete?card_id=%s'),
                                EMPLOYER_ROLE_TYPE,
                                $item['id_card']
                            );

                            echo sprintf(
                                ADD_CARD_TEMPLATE,
                                $item['id_card'],
                                $item['id_card'],
                                ($item['default'] == DEFAULT_YES_VALUE)?'checked="checked"':'',
                                $item['image_url'],
                                sprintf("%s%s",wordwrap(str_repeat(".",strlen($item['masked_number'])-4),4,' ',true),$item['last4']),
                                ($item['default'] == DEFAULT_YES_VALUE)?trans('website.W0427'):'',
                                $url_delete,
                                $item['id_card'],
                                asset('/'),
                                trans('general.M0378'),
                                asset('/')
                            );
                        }
                    @endphp
                </div>
            </div>
            <div class="modal-footer">
                <div class="form-group button-group">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="row form-btn-set">
                            <div class="col-md-7 col-sm-7 col-xs-6">
                                <button type="button" class="button-line" data-target="#add-cards" data-request="ajax-modal" data-url="{{url(sprintf('%s/payment/card/manage?load=add-cards',EMPLOYER_ROLE_TYPE))}}">{{ trans('website.W0430') }}</button>
                            </div>
                            <div class="col-md-5 col-sm-5 col-xs-6">
                                <button type="button" class="button" data-dismiss="#add-cards" data-content="#payment-checkout" data-request="add-card" data-target='[role="select_card"]' value="select_card">{{ trans('website.W0432') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>