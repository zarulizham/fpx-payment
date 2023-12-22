<?php

namespace JagdishJP\FpxPayment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use JagdishJP\FpxPayment\Models\Bank;
use JagdishJP\FpxPayment\Messages\AuthorizationRequest;

class PaymentController extends Controller
{

	/**
	 * Initiate the request authorization message to FPX
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function handle(Request $request)
	{
		return view('fpx-payment::redirect_to_bank', [
			'request' => (new AuthorizationRequest)->handle($request->all()),
		]);
	}

	public function banks(Request $request)
	{
		$banks = Bank::query()->select('bank_id', 'name', 'short_name', 'status');

		if ($request->type) {
			$banks->types($request->type == '01' ? ['B2C'] : ['B2B']);
		}

		if ($request->name) {
			$banks->where('name', 'LIKE', "%$request->name%");
		}

		$banks = $banks->orderBy('name', 'ASC')->get();

		return response()->json([
			'banks' => $banks,
		], 200);
	}
}
