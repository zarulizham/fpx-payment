<?php

namespace JagdishJP\FpxPayment\Messages;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use JagdishJP\FpxPayment\Models\Bank;
use Illuminate\Support\Facades\Config;
use JagdishJP\FpxPayment\Contracts\Message as Contract;

class BankEnquiry extends Message implements Contract
{
    /**
     * Message code on the FPX side
     */
    public const CODE = 'BE';

    /**
     * Message Url
     */
    public $url;

    public function __construct($flow = null)
    {
        parent::__construct();

        $this->type = self::CODE;
        $this->flow = $flow ?? $this->flow;
        $this->url = App::environment('production') ?
            Config::get('fpx.urls.production.bank_enquiry') :
            Config::get('fpx.urls.uat.bank_enquiry');
    }

    /**
     * handle a message
     *
     * @param array $options
     * @return mixed
     */
    public function handle(array $options)
    {
        # code...
    }

    /**
     * get request data from
     *
     */
    public function getData()
    {
        return collect([
            'fpx_msgType' => urlencode($this->type),
            'fpx_msgToken' => urlencode($this->flow),
            'fpx_sellerExId' => urlencode($this->exchangeId),
            'fpx_version' => urlencode($this->version),
            'fpx_checkSum' => $this->getCheckSum($this->format()),
        ]);
    }

    /**
     * connect and excute the request to FPX server
     *
     */
    public function connect(Collection $dataList)
    {
        $response = Http::asForm()
            ->post($this->url, $dataList->toArray());

        return Str::replaceArray("\n", [''], $response->getBody());
    }

    /**
     * Parse the bank list response
     *
     */
    public function parseBanksList($response)
    {
        if ($response == 'ERROR' || !$response) {
            return false;
        }

        while ($response !== false) {
            list($key1, $value1) = explode("=", $response);
            $value1 = urldecode($value1);
            $response_value[$key1] = $value1;
            $response = strtok("&");
        }


        $data = $response_value['fpx_bankList'] . "|" .
            $response_value['fpx_msgToken'] . "|" .
            $response_value['fpx_msgType']  . "|" .
            $response_value['fpx_sellerExId'];

        $checksum = $response_value['fpx_checkSum'];

        if (Config::get('fpx.should_verify_response')) {
            $this->verifySign($checksum, $data);
        }

        $bankListToken = strtok($response_value['fpx_bankList'], ",");

        $i = 1;
        while ($bankListToken !== false) {
            list($key1, $value1) = explode("~", $bankListToken);
            $value1 = urldecode($value1);
            $bankList[$i  . ' - ' . $key1] = $value1;
            $i++;
            $bankListToken = strtok(",");
        }

        return $bankList;
    }


    /**
     * Banks List
     */
    public function getBanks($id = null, $type)
    {
        $banks = App::environment('production') ? 
            collect($this->getProductionBanks()) : 
            collect($this->getTestingBanks());

        foreach ($banks as $bank) {
            Bank::firstOrCreate([
                'bank_id' => $bank['bank_id'],
                'type' => $bank['type'],
            ], $bank);
        }
        

        if (is_null($id)) {
            return $banks;
        }

        return $banks->where('bank_id', $id)
            ->where('type', $type)
            ->first();
    }

    public function getTestingBanks()
    {
        return [
            [
                "bank_id" => "ABB0234",
                "status" => "Offline",
                "name" => "Affin Bank Berhad B2C - Test ID",
                "short_name" => "Affin B2C - Test ID",
                "type" => "B2C",
                "position" => 1,
            ],
            [
                "bank_id" => "ABB0233",
                "status" => "Offline",
                "name" => "Affin Bank Berhad",
                "short_name" => "Affin Bank",
                "type" => "B2C",
                "position" => 2,
            ],
            [
                "bank_id" => "ABMB0212",
                "status" => "Offline",
                "name" => "Alliance Bank Malaysia Berhad",
                "short_name" => "Alliance Bank (Personal)",
                "type" => "B2C",
                "position" => 3,
            ],
            [
                "bank_id" => "AGRO01",
                "status" => "Offline",
                "name" => "BANK PERTANIAN MALAYSIA BERHAD (AGROBANK)",
                "short_name" => "AGRONet",
                "type" => "B2C",
                "position" => 4,
            ],
            [
                "bank_id" => "AMBB0209",
                "status" => "Offline",
                "name" => "AmBank Malaysia Berhad",
                "short_name" => "AmBank",
                "type" => "B2C",
                "position" => 5,
            ],
            [
                "bank_id" => "BIMB0340",
                "status" => "Offline",
                "name" => "Bank Islam Malaysia Berhad",
                "short_name" => "Bank Islam",
                "type" => "B2C",
                "position" => 6,
            ],
        
            [
                "bank_id" => "BMMB0341",
                "status" => "Offline",
                "name" => "Bank Muamalat Malaysia Berhad",
                "short_name" => "Bank Muamalat",
                "type" => "B2C",
                "position" => 7,
            ],
            [
                "bank_id" => "BKRM0602",
                "status" => "Offline",
                "name" => "Bank Kerjasama Rakyat Malaysia Berhad ",
                "short_name" => "Bank Rakyat",
                "type" => "B2C",
                "position" => 8,
            ],
            [
                "bank_id" => "BOCM01",
                "status" => "Offline",
                "name" => "Bank Of China (M) Berhad",
                "short_name" => "Bank Of China",
                "type" => "B2C",
                "position" => 9,
            ],
            [
                "bank_id" => "BSN0601",
                "status" => "Offline",
                "name" => "Bank Simpanan Nasional",
                "short_name" => "BSN",
                "type" => "B2C",
                "position" => 10,
            ],
            [
                "bank_id" => "BCBB0235",
                "status" => "Offline",
                "name" => "CIMB Bank Berhad",
                "short_name" => "CIMB Clicks",
                "type" => "B2C",
                "position" => 11,
            ],
            [
                "bank_id" => "CIT0219",
                "status" => "Offline",
                "name" => "CITI Bank Berhad",
                "short_name" => "Citibank",
                "type" => "B2C",
                "position" => 12,
            ],
            [
                "bank_id" => "HLB0224",
                "status" => "Offline",
                "name" => "Hong Leong Bank Berhad",
                "short_name" => "Hong Leong Bank",
                "type" => "B2C",
                "position" => 13,
            ],
            [
                "bank_id" => "HSBC0223",
                "status" => "Offline",
                "name" => "HSBC Bank Malaysia Berhad",
                "short_name" => "HSBC Bank",
                "type" => "B2C",
                "position" => 14,
            ],
            [
                "bank_id" => "KFH0346",
                "status" => "Offline",
                "name" => "Kuwait Finance House (Malaysia) Berhad",
                "short_name" => "KFH",
                "type" => "B2C",
                "position" => 15,
            ],
            [
                "bank_id" => "MBB0228",
                "status" => "Offline",
                "name" => "Malayan Banking Berhad (M2E)",
                "short_name" => "Maybank2E",
                "type" => "B2C",
                "position" => 16,
            ],
            [
                "bank_id" => "MB2U0227",
                "status" => "Offline",
                "name" => "Malayan Banking Berhad (M2U)",
                "short_name" => "Maybank2U",
                "type" => "B2C",
                "position" => 17,
            ],
            [
                "bank_id" => "OCBC0229",
                "status" => "Offline",
                "name" => "OCBC Bank Malaysia Berhad",
                "short_name" => "OCBC Bank",
                "type" => "B2C",
                "position" => 18,
            ],
            [
                "bank_id" => "PBB0233",
                "status" => "Offline",
                "name" => "Public Bank Berhad",
                "short_name" => "Public Bank",
                "type" => "B2C",
                "position" => 19,
            ],
            [
                "bank_id" => "RHB0218",
                "status" => "Offline",
                "name" => "RHB Bank Berhad",
                "short_name" => "RHB Bank",
                "type" => "B2C",
                "position" => 20,
            ],
            [
                "bank_id" => "TEST0021",
                "status" => "Offline",
                "name" => "SBI Bank A",
                "short_name" => "SBI Bank A",
                "type" => "B2C",
                "position" => 21,
            ],
            [
                "bank_id" => "TEST0022",
                "status" => "Offline",
                "name" => "SBI Bank B",
                "short_name" => "SBI Bank B",
                "type" => "B2C",
                "position" => 22,
            ],
            [
                "bank_id" => "TEST0023",
                "status" => "Offline",
                "name" => "SBI Bank C",
                "short_name" => "SBI Bank C",
                "type" => "B2C",
                "position" => 23,
            ],
            [
                "bank_id" => "SCB0216",
                "status" => "Offline",
                "name" => "Standard Chartered Bank",
                "short_name" => "Standard Chartered",
                "type" => "B2C",
                "position" => 24,
            ],
            [
                "bank_id" => "UOB0226",
                "status" => "Offline",
                "name" => "United Overseas Bank",
                "short_name" => "UOB Bank",
                "type" => "B2C",
                "position" => 25,
            ],
            [
                "bank_id" => "UOB0229",
                "status" => "Offline",
                "name" => "United Overseas Bank - B2C Test",
                "short_name" => "UOB Bank - Test ID",
                "type" => "B2C",
                "position" => 26,
            ],
        
        
        
        
        
        
            [
                "bank_id" => "ABB0232",
                "status" => "Offline",
                "name" => "Affin Bank Berhad",
                "short_name" => "Affin Bank",
                "type" => "B2B",
                "position" => 1,
            ],
            [
                "bank_id" => "ABB0235",
                "status" => "Offline",
                "name" => "Affin Bank Berhad B2B",
                "short_name" => "AFFINMAX",
                "type" => "B2B",
                "position" => 2,
            ],   
            [
                "bank_id" => "ABMB0213",
                "status" => "Offline",
                "name" => "Alliance Bank Malaysia Berhad",
                "short_name" => "Alliance Bank (Business)",
                "type" => "B2B",
                "position" => 3,
            ],
            [
                "bank_id" => "AGRO02",
                "status" => "Offline",
                "name" => "BANK PERTANIAN MALAYSIA BERHAD (AGROBANK)",
                "short_name" => "AGRONetBIZ",
                "type" => "B2B",
                "position" => 4,
            ],
            [
                "bank_id" => "AMBB0208",
                "status" => "Offline",
                "name" => "AmBank Malaysia Berhad",
                "short_name" => "AmBank",
                "type" => "B2B",
                "position" => 5,
            ],
            [
                "bank_id" => "BIMB0340",
                "status" => "Offline",
                "name" => "Bank Islam Malaysia Berhad",
                "short_name" => "Bank Islam",
                "type" => "B2B",
                "position" => 6,
            ],
            [
                "bank_id" => "BMMB0342",
                "status" => "Offline",
                "name" => "Bank Muamalat Malaysia Berhad",
                "short_name" => "Bank Muamalat",
                "type" => "B2B",
                "position" => 7,
            ],
            [
                "bank_id" => "BNP003",
                "status" => "Offline",
                "name" => "BNP Paribas Malaysian Berhad",
                "short_name" => "BNP Paribas",
                "type" => "B2B",
                "position" => 8,
            ],
            [
                "bank_id" => "BCBB0235",
                "status" => "Offline",
                "name" => "CIMB Bank Berhad",
                "short_name" => "CIMB Clicks",
                "type" => "B2B",
                "position" => 9,
            ],
            [
                "bank_id" => "CIT0218",
                "status" => "Offline",
                "name" => "CITI Bank Berhad",
                "short_name" => "Citibank Corporate Banking",
                "type" => "B2B",
                "position" => 10,
            ],
            [
                "bank_id" => "DBB0199",
                "status" => "Offline",
                "name" => "Deutsche Bank Berhad",
                "short_name" => "Deutsche Bank",
                "type" => "B2B",
                "position" => 11,
            ],
            [
                "bank_id" => "HLB0224",
                "status" => "Offline",
                "name" => "Hong Leong Bank Berhad",
                "short_name" => "Hong Leong Bank",
                "type" => "B2B",
                "position" => 12,
            ],
            [
                "bank_id" => "HSBC0223",
                "status" => "Offline",
                "name" => "HSBC Bank Malaysia Berhad",
                "short_name" => "HSBC Bank",
                "type" => "B2B",
                "position" => 13,
            ],
            [
                "bank_id" => "BKRM0602",
                "status" => "Offline",
                "name" => "Bank Kerjasama Rakyat Malaysia Berhad",
                "short_name" => "i-bizRAKYAT",
                "type" => "B2B",
                "position" => 14,
            ],
            [
                "bank_id" => "KFH0346",
                "status" => "Offline",
                "name" => "Kuwait Finance House (Malaysia) Berhad",
                "short_name" => "KFH",
                "type" => "B2B",
                "position" => 15,
            ],
            [
                "bank_id" => "MBB0228",
                "status" => "Offline",
                "name" => "Malayan Banking Berhad (M2E)",
                "short_name" => "Maybank2E",
                "type" => "B2B",
                "position" => 16,
            ],
            [
                "bank_id" => "OCBC0229",
                "status" => "Offline",
                "name" => "OCBC Bank Malaysia Berhad",
                "short_name" => "OCBC Bank",
                "type" => "B2B",
                "position" => 17,
            ],
            [
                "bank_id" => "PBB0233",
                "status" => "Offline",
                "name" => "Public Bank Berhad",
                "short_name" => "Public Bank PBe",
                "type" => "B2B",
                "position" => 18,
            ],
            [
                "bank_id" => "PBB0234",
                "status" => "Offline",
                "name" => "Public Bank Enterprise",
                "short_name" => "Public Bank PB enterprise",
                "type" => "B2B",
                "position" => 19,
            ],
            [
                "bank_id" => "RHB0218",
                "status" => "Offline",
                "name" => "RHB Bank Berhad",
                "short_name" => "RHB Bank",
                "type" => "B2B",
                "position" => 20,
            ],
            [
                "bank_id" => "TEST0021",
                "status" => "Offline",
                "name" => "SBI Bank A",
                "short_name" => "SBI Bank A",
                "type" => "B2B",
                "position" => 21,
            ],
            [
                "bank_id" => "TEST0022",
                "status" => "Offline",
                "name" => "SBI Bank B",
                "short_name" => "SBI Bank B",
                "type" => "B2B",
                "position" => 22,
            ],
            [
                "bank_id" => "TEST0023",
                "status" => "Offline",
                "name" => "SBI Bank C",
                "short_name" => "SBI Bank C",
                "type" => "B2B",
                "position" => 23,
            ],
            [
                "bank_id" => "SCB0215",
                "status" => "Offline",
                "name" => "Standard Chartered Bank",
                "short_name" => "Standard Chartered",
                "type" => "B2B",
                "position" => 24,
            ],
            [
                "bank_id" => "UOB0228",
                "status" => "Offline",
                "name" => "United Overseas Bank - B2B Regional",
                "short_name" => "UOB Regional",
                "type" => "B2B",
                "position" => 25,
            ],
        ];
    }

    public function getProductionBanks()
    {
        return [
            [
                "bank_id" => "ABB0233",
                "status" => "Offline",
                "name" => "Affin Bank Berhad",
                "short_name" => "Affin Bank",
                "type" => "B2C",
                "position" => 1,
            ],
            [
                "bank_id" => "ABMB0212",
                "status" => "Offline",
                "name" => "Alliance Bank Malaysia Berhad",
                "short_name" => "Alliance Bank (Personal)",
                "type" => "B2C",
                "position" => 2,
            ],
            [
                "bank_id" => "AGRO01",
                "status" => "Offline",
                "name" => "BANK PERTANIAN MALAYSIA BERHAD (AGROBANK)",
                "short_name" => "AGRONet",
                "type" => "B2C",
                "position" => 3,
            ],
            [
                "bank_id" => "AMBB0209",
                "status" => "Offline",
                "name" => "AmBank Malaysia Berhad",
                "short_name" => "AmBank",
                "type" => "B2C",
                "position" => 4,
            ],
            [
                "bank_id" => "BIMB0340",
                "status" => "Offline",
                "name" => "Bank Islam Malaysia Berhad",
                "short_name" => "Bank Islam",
                "type" => "B2C",
                "position" => 5,
            ],
        
            [
                "bank_id" => "BMMB0341",
                "status" => "Offline",
                "name" => "Bank Muamalat Malaysia Berhad",
                "short_name" => "Bank Muamalat",
                "type" => "B2C",
                "position" => 6,
            ],
            [
                "bank_id" => "BKRM0602",
                "status" => "Offline",
                "name" => "Bank Kerjasama Rakyat Malaysia Berhad ",
                "short_name" => "Bank Rakyat",
                "type" => "B2C",
                "position" => 7,
            ],
            [
                "bank_id" => "BOCM01",
                "status" => "Offline",
                "name" => "Bank Of China (M) Berhad",
                "short_name" => "Bank Of China",
                "type" => "B2C",
                "position" => 8,
            ],
            [
                "bank_id" => "BSN0601",
                "status" => "Offline",
                "name" => "Bank Simpanan Nasional",
                "short_name" => "BSN",
                "type" => "B2C",
                "position" => 9,
            ],
            [
                "bank_id" => "BCBB0235",
                "status" => "Offline",
                "name" => "CIMB Bank Berhad",
                "short_name" => "CIMB Clicks",
                "type" => "B2C",
                "position" => 10,
            ],
            [
                "bank_id" => "HLB0224",
                "status" => "Offline",
                "name" => "Hong Leong Bank Berhad",
                "short_name" => "Hong Leong Bank",
                "type" => "B2C",
                "position" => 11,
            ],
            [
                "bank_id" => "HSBC0223",
                "status" => "Offline",
                "name" => "HSBC Bank Malaysia Berhad",
                "short_name" => "HSBC Bank",
                "type" => "B2C",
                "position" => 12,
            ],
            [
                "bank_id" => "KFH0346",
                "status" => "Offline",
                "name" => "Kuwait Finance House (Malaysia) Berhad",
                "short_name" => "KFH",
                "type" => "B2C",
                "position" => 13,
            ],   
            [
                "bank_id" => "MBB0228",
                "status" => "Offline",
                "name" => "Malayan Banking Berhad (M2E)",
                "short_name" => "Maybank2E",
                "type" => "B2C",
                "position" => 14,
            ],
            [
                "bank_id" => "MB2U0227",
                "status" => "Offline",
                "name" => "Malayan Banking Berhad (M2U)",
                "short_name" => "Maybank2U",
                "type" => "B2C",
                "position" => 15,
            ],
            [
                "bank_id" => "OCBC0229",
                "status" => "Offline",
                "name" => "OCBC Bank Malaysia Berhad",
                "short_name" => "OCBC Bank",
                "type" => "B2C",
                "position" => 16,
            ],
            [
                "bank_id" => "PBB0233",
                "status" => "Offline",
                "name" => "Public Bank Berhad",
                "short_name" => "Public Bank",
                "type" => "B2C",
                "position" => 17,
            ],
            [
                "bank_id" => "RHB0218",
                "status" => "Offline",
                "name" => "RHB Bank Berhad",
                "short_name" => "RHB Bank",
                "type" => "B2C",
                "position" => 18,
            ],
            [
                "bank_id" => "SCB0216",
                "status" => "Offline",
                "name" => "Standard Chartered Bank",
                "short_name" => "Standard Chartered",
                "type" => "B2C",
                "position" => 19,
            ],
            [
                "bank_id" => "UOB0226",
                "status" => "Offline",
                "name" => "United Overseas Bank",
                "short_name" => "UOB Bank",
                "type" => "B2C",
                "position" => 20,
            ],
        
        
            
        
        
        
        
            [
                "bank_id" => "ABB0235",
                "status" => "Offline",
                "name" => "Affin Bank Berhad B2B",
                "short_name" => "AFFINMAX",
                "type" => "B2B",
                "position" => 1,
            ],   
            [
                "bank_id" => "ABMB0213",
                "status" => "Offline",
                "name" => "Alliance Bank Malaysia Berhad",
                "short_name" => "Alliance Bank (Business)",
                "type" => "B2B",
                "position" => 2,
            ],
            [
                "bank_id" => "AGRO02",
                "status" => "Offline",
                "name" => "BANK PERTANIAN MALAYSIA BERHAD (AGROBANK)",
                "short_name" => "AGRONetBIZ",
                "type" => "B2B",
                "position" => 3,
            ],
            [
                "bank_id" => "AMBB0208",
                "status" => "Offline",
                "name" => "AmBank Malaysia Berhad",
                "short_name" => "AmBank",
                "type" => "B2B",
                "position" => 4,
            ],
            [
                "bank_id" => "BIMB0340",
                "status" => "Offline",
                "name" => "Bank Islam Malaysia Berhad",
                "short_name" => "Bank Islam",
                "type" => "B2B",
                "position" => 5,
            ],
            [
                "bank_id" => "BMMB0342",
                "status" => "Offline",
                "name" => "Bank Muamalat Malaysia Berhad",
                "short_name" => "Bank Muamalat",
                "type" => "B2B",
                "position" => 6,
            ],
            [
                "bank_id" => "BNP003",
                "status" => "Offline",
                "name" => "BNP Paribas Malaysian Berhad",
                "short_name" => "BNP Paribas",
                "type" => "B2B",
                "position" => 7,
            ],
            [
                "bank_id" => "BCBB0235",
                "status" => "Offline",
                "name" => "CIMB Bank Berhad",
                "short_name" => "CIMB Clicks",
                "type" => "B2B",
                "position" => 8,
            ],
            [
                "bank_id" => "CIT0218",
                "status" => "Offline",
                "name" => "CITI Bank Berhad",
                "short_name" => "Citibank Corporate Banking",
                "type" => "B2B",
                "position" => 9,
            ],
            [
                "bank_id" => "DBB0199",
                "status" => "Offline",
                "name" => "Deutsche Bank Berhad",
                "short_name" => "Deutsche Bank",
                "type" => "B2B",
                "position" => 10,
            ],
            [
                "bank_id" => "HLB0224",
                "status" => "Offline",
                "name" => "Hong Leong Bank Berhad",
                "short_name" => "Hong Leong Bank",
                "type" => "B2B",
                "position" => 11,
            ],
            [
                "bank_id" => "HSBC0223",
                "status" => "Offline",
                "name" => "HSBC Bank Malaysia Berhad",
                "short_name" => "HSBC Bank",
                "type" => "B2B",
                "position" => 12,
            ],
            [
                "bank_id" => "BKRM0602",
                "status" => "Offline",
                "name" => "Bank Kerjasama Rakyat Malaysia Berhad",
                "short_name" => "i-bizRAKYAT",
                "type" => "B2B",
                "position" => 13,
            ],
            [
                "bank_id" => "KFH0346",
                "status" => "Offline",
                "name" => "Kuwait Finance House (Malaysia) Berhad",
                "short_name" => "KFH",
                "type" => "B2B",
                "position" => 14,
            ],
            [
                "bank_id" => "MBB0228",
                "status" => "Offline",
                "name" => "Malayan Banking Berhad (M2E)",
                "short_name" => "Maybank2E",
                "type" => "B2B",
                "position" => 15,
            ],
            [
                "bank_id" => "OCBC0229",
                "status" => "Offline",
                "name" => "OCBC Bank Malaysia Berhad",
                "short_name" => "OCBC Bank",
                "type" => "B2B",
                "position" => 16,
            ],
            [
                "bank_id" => "PBB0233",
                "status" => "Offline",
                "name" => "Public Bank Berhad",
                "short_name" => "Public Bank PBe",
                "type" => "B2B",
                "position" => 17,
            ],
            [
                "bank_id" => "PBB0234",
                "status" => "Offline",
                "name" => "Public Bank Enterprise",
                "short_name" => "Public Bank PB enterprise",
                "type" => "B2B",
                "position" => 18,
            ],
            [
                "bank_id" => "RHB0218",
                "status" => "Offline",
                "name" => "RHB Bank Berhad",
                "short_name" => "RHB Bank",
                "type" => "B2B",
                "position" => 19,
            ],
            [
                "bank_id" => "SCB0215",
                "status" => "Offline",
                "name" => "Standard Chartered Bank",
                "short_name" => "Standard Chartered",
                "type" => "B2B",
                "position" => 20,
            ],
            [
                "bank_id" => "UOB0228",
                "status" => "Offline",
                "name" => "United Overseas Bank - B2B Regional",
                "short_name" => "UOB Regional",
                "type" => "B2B",
                "position" => 21,
            ],
        ];
    }

    /**
     * Format data for checksum
     * @return string
     */
    public function format()
    {
        $list = collect([
            $this->flow ?? '',
            $this->type ?? '',
            $this->exchangeId ?? '',
            $this->version ?? '',
        ]);

        return $list->join('|');
    }
}
