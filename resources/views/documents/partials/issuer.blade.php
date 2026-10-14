@if ($logo)<img src="{{ $logo }}" class="logo" alt=""><br>@endif
<div class="issuer-name">{{ $issuer['name'] }}</div>
@if ($issuer['legal_form'])<div class="muted">{{ $issuer['legal_form'] }}</div>@endif
@if ($issuer['address'])<div>{!! nl2br(e($issuer['address'])) !!}</div>@endif
@if ($issuer['email'])<div>{{ $issuer['email'] }}</div>@endif
@if ($issuer['phone'])<div>{{ $issuer['phone'] }}</div>@endif
@if ($issuer['registration_number'])<div class="muted">{{ $en ? 'Registration no.' : 'N° d’entreprise' }} : {{ $issuer['registration_number'] }}</div>@endif
