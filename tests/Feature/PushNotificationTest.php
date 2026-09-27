<?php

use App\Jobs\SendPushNotification;

test('sending a push notification never throws, whether Firebase is installed and configured or not', function () {
    // Le paquet Firebase (kreait/laravel-firebase) est optionnel, et sa configuration
    // (FIREBASE_CREDENTIALS) l'est tout autant : dans les deux cas, ce job doit
    // journaliser l'échec (voir SendPushNotification::handle) mais jamais le laisser
    // remonter et casser la requête publique qui a déclenché la notification.
    expect(fn () => (new SendPushNotification('Titre', 'Corps'))->handle())->not->toThrow(Throwable::class);
});
