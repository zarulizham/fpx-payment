<?php

use JagdishJP\FpxPayment\Fpx;
use Illuminate\Support\Facades\Route;
use JagdishJP\FpxPayment\Http\Controllers\PaymentController;

Route::get('fpx/transaction/status/{reference_id?}', function ($reference_id = '') {

	$response = Fpx::getTransactionStatus($reference_id);
	return $response;
})->name('fpx.transaction.status');


Route::get('banks', [PaymentController::class, 'banks'])->name('api.banks.index');
