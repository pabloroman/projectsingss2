<?php

namespace App\Http\Requests\RacesHotelsInvoices;

use App\RaceHotel;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;

class RacesHotelsInvoicesStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if (! empty(Request::input('invoice_type')) && Request::input('invoice_type') == 'confirmations') {
            $rules = [];

            $rules = [
                'due_on'      => 'required|date',
                'client_id'   => 'required',
                'currency_id' => 'required',
            ];

            foreach ($this->request->get('confirmation_items') as $key => $val) {

                $rules['confirmation_items.'.$key.'.room.id']   = 'required';
                $rules['confirmation_items.'.$key.'.rate']      = 'required';
                $rules['confirmation_items.'.$key.'.quantity']  = 'required';
                $rules['confirmation_items.'.$key.'.check_in']  = 'required|date';
                $rules['confirmation_items.'.$key.'.check_out'] = 'required|date';

                // if ($val['quantity'] > $val['max_rooms']) {
                //     $rules['confirmation_items.'.$key.'.quantity'] = 'numeric|integer|max:'.$val['max_rooms'];
                // }
            }

            foreach ($this->request->get('payments') as $key => $val) {
                $rules['payments.'.$key.'.payment_name']   = 'required';
                $rules['payments.'.$key.'.amount_due']     = 'required';
                $rules['payments.'.$key.'.due_on']         = 'required|date';
                $rules['payments.'.$key.'.to_accounts_on'] = 'sometimes|nullable|date';
                $rules['payments.'.$key.'.invoice_number'] = 'sometimes|nullable|max:25';
                $rules['payments.'.$key.'.invoice_date']   = 'sometimes|nullable|date';

            }

        } else {

            $rules = [
                'due_on'                      => 'required|date',
                'client_id'                   => 'required',
                'race_id'                     => 'required',
                'hotel_id'                    => 'required',
                'currency_id'                 => 'required',
                'invoice_items.*.date'        => 'required',
                'invoice_items.*.description' => 'required|string',
                'invoice_items.*.quantity'    => 'required|numeric',
                'invoice_items.*.rate'        => 'required|numeric',
                'payments.*.payment_name'     => 'required',
                'payments.*.amount_due'       => 'required|numeric',
                'payments.*.due_on'           => 'required|date',
                'payments.*.to_accounts_on'   => 'sometimes|nullable|date',
                'payments.*.invoice_number'   => 'sometimes|nullable|max:25',
                'payments.*.invoice_date'     => 'sometimes|nullable|date',
            ];
        }

        return $rules;
    }

     /**
     * Get the validation message that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        if (! empty(Request::input('invoice_type')) && Request::input('invoice_type') == 'confirmations') {

            $messages = [];

            $messages = [
                'due_on.required'      => 'Date is required',
                'client_id.required'   => 'Client is required',
                'currency_id.required' => 'Currency is required',
            ];

            foreach ($this->request->get('confirmation_items') as $key => $val) {
                $messages['confirmation_items.'.$key.'.room.id.required']   = 'Room Type is required';
                $messages['confirmation_items.'.$key.'.rate.required']      = 'Rate is required';
                $messages['confirmation_items.'.$key.'.quantity.required']  = 'Room is required';
                $messages['confirmation_items.'.$key.'.check_in.required']  = 'Required';
                $messages['confirmation_items.'.$key.'.check_out.required'] = 'Required';

                // if($val['quantity'] > $val['max_rooms']) {
                //     $messages['confirmation_items.'.$key.'.quantity.max'] = 'Max '.$val['max_rooms']. " Avail";
                // }
            }

            foreach ($this->request->get('payments') as $key => $val) {
                $messages['payments.'.$key.'.payment_name.required'] = 'Description is required';
                $messages['payments.'.$key.'.amount_due.required']   = 'Required';
                $messages['payments.'.$key.'.amount_due.numeric']    = 'Amount must be number';
                $messages['payments.'.$key.'.due_on.required']       = 'Required';
                $messages['payments.'.$key.'.to_accounts_on.date']   = 'Select valid date';
                $messages['payments.'.$key.'.invoice_number.max']    = 'Enter up to 25 characters';
                $messages['payments.'.$key.'.invoice_date.date']     = 'Select valid date';
            }

            return $messages;

        } else {

            return [
                'due_on.required'                       => 'Due date is required',
                'client_id.required'                    => 'Client is required',
                'race_id.required'                      => 'Race is required',
                'hotel_id.required'                     => 'Hotel is required',
                'currency_id.required'                  => 'Currency is required',
                'invoice_items.*.date.required'         => 'Date is required',
                'invoice_items.*.description.required'  => 'Description is required',
                'invoice_items.*.description.string'    => 'Description should only accept string value',
                'invoice_items.*.quantity.required'     => 'Quantity is required',
                'invoice_items.*.quantity.numeric'      => 'Quantity only accept numeric values',
                'invoice_items.*.rate.required'         => 'Rate is required',
                'invoice_items.*.rate.numeric'          => 'Rate only accept numeric values',
                'payments.*.payment_name.required'      => 'Description is required',
                'payments.*.amount_due.required'        => 'Amount is required',
                'payments.*.amount_due.numeric'         => 'Amount must be number',
                'payments.*.due_on.required'            => 'Due on is required',
                'payments.*.due_on.date'                => 'Select valid date',
                'payments.*.to_accounts_on.date'        => 'Select valid date',
                'payments.*.invoice_number.max'         => 'Enter up to 25 characters',
                'payments.*.invoice_date.date'          => 'Select valid date',
            ];
        }
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (! empty($this->request->get('race_hotel_id'))) {
                // Get the race hotels min check in/out dates.
                $check_in_out_dates = RaceHotel::where('id', $this->request->get('race_hotel_id'))->first();
                $check_in_date      = strtotime($check_in_out_dates->inventory_min_check_in);
                $check_out_date     = strtotime($check_in_out_dates->inventory_min_check_out);

                if (! empty($this->request->get('confirmation_items'))) {
                    foreach ($this->request->get('confirmation_items') as $key => $val) {
                        $posted_check_in_date  = strtotime($val['check_in']);
                        $posted_check_out_date = strtotime($val['check_out']);

                        // Check if posted check in and out dates are valid for selected range of dates.
                        if (($posted_check_in_date < $check_in_date && $posted_check_out_date > $check_in_date )
                            || ($posted_check_out_date > $check_out_date && $posted_check_in_date < $check_out_date )
                            ) {
                            $validator->errors()->add('confirmation_items.'.$key.'.check_out', '');
                        }
                        // Check if selected quantity exceeds available room quantity
                        if ($val['rooms_remaining'] < 0) {
                            $validator->errors()->add('confirmation_items.'.$key.'.quantity', "Only " . $val['rooms_available'] . " rooms available.");
                        }
                    }
                }
            }

            // Check if the seleted hotel belongs to selected race.
            $race_hotel = RaceHotel::select('id')
                ->where('race_id', $this->request->get('race_id'))
                ->where('hotel_id', $this->request->get('hotel_id'))
                ->whereNull('races_hotels.deleted_at')
                ->first();

            if (empty($race_hotel->id)) {
                $validator->errors()->add('hotel_id', "Seleted hotel is not associated with selected race.");
            }
        });
    }

}
