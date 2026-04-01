@props([
    'name',
    'email',
    'accountNumber',
    'contrast' => false,
    'compact' => false,
])

<div {{ $attributes->class([
    'account-identity',
    'account-identity-contrast' => $contrast,
    'account-identity-compact' => $compact,
]) }}>
    <div class="account-identity-name"><span class="account-identity-label">Titular:</span> {{ $name }}</div>
    <div class="account-identity-email"><span class="account-identity-label">E-mail:</span> {{ $email }}</div>
    <div class="account-identity-account"><span class="account-identity-label">Conta:</span> {{ $accountNumber }}</div>
</div>