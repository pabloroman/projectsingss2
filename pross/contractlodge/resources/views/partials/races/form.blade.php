<div class="row mb-3">
    <div class="col-sm-2 col-md-1">
        <label><strong>Race Code</strong></label>
        @if ($errors->has('race_code'))
            <input type="text" class="form-control is-invalid" placeholder="Race Code"
                name="race_code" dusk="race-code" value="{{ old('race_code', @$race->race_code) }}">
            <span class="invalid-feedback">
                {{ $errors->first('race_code') }}
            </span>
        @else
            <input type="text" class="form-control" dusk="race-code" placeholder="Race Code" name="race_code"
                value="{{ old('race_code', @$race->race_code) }}">
        @endif
        <small class="form-text text-muted">
            Ex: "AUS-1"
        </small>
    </div>
    <div class="col-sm-2 col-md-1">
        <label><strong>Year</strong></label>
        @if ($errors->has('year'))
            <input type="text" class="form-control is-invalid" placeholder="2025" name="year"
                value="{{ old('year', @$race->year) }}" dusk="year">
            <span class="invalid-feedback">
                {{ $errors->first('year') }}
            </span>
        @else
            <input type="text" class="form-control" placeholder="2025" name="year"
                value="{{ old('year', @$race->year) }}" dusk="year">
        @endif
        <small class="form-text text-muted">
            Ex: "2025"
        </small>
    </div>
    <div class="col-sm-2 col-md-4">
        <label><strong>Race</strong></label>
        @if ($errors->has('name'))
            <input type="text" class="form-control is-invalid" placeholder="Race Location / Name"
                name="name" value="{{ old('name', @$race->name) }}" dusk="name">
            <span class="invalid-feedback">
                {{ $errors->first('name') }}
            </span>
        @else
            <input type="text" class="form-control" placeholder="Race Location / Name" name="name"
                value="{{ old('name', @$race->name) }}" dusk="name">
        @endif
        <small class="form-text text-muted">
            Ex: "US Grand Prix"
        </small>
    </div>
    <div class="col-sm-2">
        <label><strong>Start Date</strong></label>
            <div class="navbar-item" id="date_picker">
                @isset($set_default_end_date)
                    <date-pick name="start_on" @input="setDefaultEndDate()"
                        v-model="start_on"
                        :input-attributes="{
                            name: 'start_on',
                            class: 'form-date-picker form-control {{ $errors->has('start_on') ? 'is-invalid' : '' }}',
                            placeholder: 'dd/mm/yyyy',
                            autocomplete: 'off'
                        }"
                        :display-format="'DD/MM/YYYY'"
                        :start-week-on-sunday="true">
                    </date-pick>
                @else
                    <date-pick name="start_on"
                        v-model="start_on"
                        :input-attributes="{
                            name: 'start_on',
                            class: 'form-date-picker form-control {{ $errors->has('start_on') ? 'is-invalid' : '' }}',
                            placeholder: 'dd/mm/yyyy',
                            autocomplete: 'off'
                        }"
                        :display-format="'DD/MM/YYYY'"
                        :start-week-on-sunday="true">
                    </date-pick>
                @endif
            </div>
            @if ($errors->has('start_on'))
                <span class="invalid-feedback">
                    {{ $errors->first('start_on') }}
                </span>
            @endif
        <small class="form-text text-muted">
            Ex: "31/12/2020"
        </small>
    </div>
    <div class="col-sm-2">
        <label><strong>End Date</strong></label>
            <div class="navbar-item" id="date_picker">
                <date-pick name="end_on"
                    v-model="end_on"
                    :input-attributes="{
                        name: 'end_on',
                        class: 'form-date-picker form-control {{ $errors->has('end_on') ? 'is-invalid' : '' }}',
                        placeholder: 'dd/mm/yyyy',
                        autocomplete: 'off'
                    }"
                    :display-format="'DD/MM/YYYY'"
                    :start-week-on-sunday="true">
                </date-pick>
            </div>
            @if ($errors->has('end_on'))
                <span class="invalid-feedback">
                    {{ $errors->first('end_on') }}
                </span>
            @endif
        <small class="form-text text-muted">
            Ex: "31/12/2020"
        </small>
    </div>
    <div class="col-sm-2">
        <label><strong>Currency</strong></label>
        <select class="form-control" name="currency_id">
            @foreach ($currencies as $currency)
                @if(isset($race)  && !empty($race))
                    <option value="{{ $currency->id }}" {{ $currency->id === $race->currency_id ? 'selected' : '' }}>
                        {{ $currency->name }}
                    </option>
                @else
                    <option value="{{ $currency->id }}">{{ $currency->name }}</option>
                @endif
            @endforeach
        </select>
        <small class="form-text text-muted">
            Ex: "USD"
        </small>
    </div>
</div>
