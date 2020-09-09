<?php

namespace App\Http\Controllers;

use Endropie\AccurateClient\Facade as Accurate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function GuzzleHttp\Promise\all;

class AccurateSalesInvoiceController extends Controller
{

    public function sync(Request $request)
    {
        $filter = [];
        foreach ($request->get('filter') ?? [] as $key => $value) {
            $filter['filter.'.$key] = $value;
        }

        $sp =  ["pageSize" => 100, "page" => 1];
        $RESULT = ["data" => array(), "response" => array()];

        // dd($filter);

        do {
            $parameter = array_merge([
                'fields' => 'id,number',
                // 'lastUpdateFilter' => '25/08/2020 00:00:00',
                'sp.pageSize' => $sp['pageSize'],
                'sp.page' => $sp['page']
            ], $filter);

            // dd($parameter);

            $client = Accurate::on('sales-invoice', 'list', $parameter);

            $isDone = false;
            if ($client->successful())
            {
                $response = $client->json();
                array_push($RESULT['response'], $response);

                foreach ($response["d"] as $value) {
                    $fetch = Accurate::on('sales-invoice', 'detail', ['id' => $value['id']]);
                    if ($fetch->successful() && $fetch->json()['s'])
                    {
                        $this->save($fetch->json()['d']);
                        array_push($RESULT['data'], $fetch->json()['d']);
                    }
                }

                $isDone = $sp['page'] >= $response["sp"]["rowCount"];
            }

            $sp['page']++;

        } while ($client->successful() && count($response['d']) && !$isDone);

        return $RESULT;
        // return response($client->body());
    }

    private function save ($record)
    {

        $exe = DB::table('sales_invoices')->updateOrInsert(['id' => $record['id'], 'number'=> $record['number']], [
            'date' =>  date("Y-m-d", strtotime(str_replace('/', '-', $record['transDate'] ))),
            'description' =>$record['description'],
            'customer_id' => $record['customer']['id'],
            'customer_name' => $record['customer']['name'],
        ]);

        if ($record['detailItem'])
        {
            DB::table('sales_invoice_items')->where('sales_invoice_id', $record['id'])->delete();

            foreach ($record['detailItem'] as $detail)
            {
                $exeDetail = DB::table('sales_invoice_items')->updateOrInsert(['id' => $detail['id']], [
                    'sales_invoice_id' => $record['id'],
                    'item_id' => $detail['item']['id'],
                    'name' => $detail['detailName'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['unitPrice'],
                    'notes' => $detail['detailNotes']
                ]);
            }

        }

        return $exe;
    }
}
