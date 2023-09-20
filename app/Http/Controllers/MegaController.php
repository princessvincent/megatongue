<?php

namespace App\Http\Controllers;

use App\Models\faq;
use App\Models\User;
use App\Models\review;
use App\Models\history;
use App\Models\pricing;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(title="MegaTongue Api Documentation", version="1.0.0")
 */

class MegaController extends Controller
{
    public function apikey(Request $request)
    {
        $user =  User::find(Auth::user()->id);
        $apikey = Str::random(40);
        if ($user) {
            $user->api_key = $apikey;
            $user->update();

            return response()->json([
                'statusCode' => true,
                'message' => 'Apikey has been created successfully',
            ]);
        };
    }

    public function pricing(Request $request)
    {
        $request->validate([
            "name" => "required",
            "amount" => "required",
            "description" => "required",
        ]);

        $price = new pricing;
        $price->name = $request->name;
        $price->amount = $request->amount;
        $price->description = $request->description;
        $price->mode = $request->mode;
        $price->save();

        return response()->json([
            "statusCode" => 200,
            "message" => "Price has been updated successfully",
        ]);
    }

    /**
 * @OA\Post(
 *     path="/translator",
 *     summary="Translate Text",
 *     description="Translate text from one language to another.",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="q", type="string", description="Text to be translated."),
 *             @OA\Property(property="source", type="string", description="Source language code."),
 *             @OA\Property(property="target", type="string", description="Target language code."),
 *             @OA\Property(property="format", type="string", description="Translation format.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful translation.",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Translation successful"),
 *             @OA\Property(property="translated_text", type="string", example="Translated text goes here")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Error in translation.",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=422),
 *             @OA\Property(property="message", type="string", example="Translation error")
 *         )
 *     )
 * )
 */

    public function translator(Request $request)
    {
        $data = array(
            "q" => $request->q,
            "source" => $request->source,
            "target" => $request->target,
            "format" => $request->format
        );

        $json_data = json_encode($data);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://62.171.157.189:5000/translate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: session=edb08a19-057b-46e5-bd9e-00346901cf2e'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        // echo $response;
        $decoded_response = json_decode($response, true);
        // echo $json_decode["translatedText"];


        // Check if the decoded_response contains the 'translatedText' key
        if (isset($decoded_response['translatedText'])) {
            $translated_text = $decoded_response['translatedText'];
        } else {
            $translated_text = 'Translation not available.';
        }

        $history = new history;
        $history->user_id = Auth::user()->id;
        $history->text = $request->q;
        $history->source_language = $request->source;
        $history->destination_language = $request->target;
        $history->format = $request->format;
        $history->response = $translated_text;
        $history->save();
        if($history->save()){
            return response()->json([
                "status code" => 200,
                "message" => $translated_text
            ]);
        }else{
            return response()->json([
                "status code" => 422,
                "message" => "error",
            ]);
        }

    }

    //for reviews

    public function addreview(Request $request)
    {
        $add = review::create([
            "user_id" => Auth::user()->id,
            'review' => $request->review,
        ]);

        if($add)
        {
            return response()->json([
                "status" => true,
                "message" => "Added review",
            ],200);
        }else{
            return response()->json([
                "status" => false,
                "message" => "error",
            ],422);
        }
    }

    public function getreviews(Request $request)
    {
        $getreview = review::all();
        if($getreview)
        {
            return response()->json([
                "status" => true,
                "message" =>  $getreview,
            ],200);
        }else{
            return response()->json([
                "status" => 200,
                "message" => "No Review yet!",
            ],200);
        }
    }
/**
 * @OA\Get(
 *     path="/getapiusage",
 *     summary="Get API Usage History",
 *     description="Retrieve the history of API usage (entire data in the history table).",
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation.",
 *         @OA\JsonContent(
 *             @OA\Property(property="status_code", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="API usage history retrieved successfully")
 *         )
 *     )
 * )
 */

    //for api 
    public function getapiusage(Request $request)
    {
        $userId = Auth::user()->id;
        $apiusage = history::where('user_id', $userId)->get();
    
        if($apiusage->count() > 0)
        {
            return response()->json([
                "status" => true,
                "message" => $apiusage->count(),
                "date" => $apiusage->first()->created_at,
            ], 200);
        } else {
            return response()->json([
                "status" => true,
                "message" => "You have not made any request in the last month!",
            ], 200);
        }
    }
    

    public function getapikey()
    {
        $userkey = User::find(Auth::user()->id)->first();
        if($userkey)
        {
            return response()->json([
                            "status" => true,
                            "message" =>  $userkey->api_key,
                         ],200);
        }else{
            return response()->json([
                "status" => true,
                "message" => "You do not have Api Access key, You can request for it!",
            ],200);
        }
    }

    public function getfaq()
    {
        $faqs = faq::all();
        
        if($faqs) 
        {
            return response()->json([
                "status" => true,
                "message" =>  $faqs,
            ],200);
        }else{
            return response()->json([
                "status" => 200,
                "message" => "No Faq yet!",
            ],200);
        }
    }

  

}
