<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use  App\Constants;
use App\Faqs;
use DB;

class FaqsController extends Controller
{
     /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct(){
        $this->middleware('auth');
    }

    public function addFaqs(Request $request){
        $this->validate($request, [
            'faqs_question' => 'required|string',
            'faqs_answer' => 'required|string',
        ]);
        
        $data = $request->input();
        $faqs = new Faqs;
        if($faqs){
            $faqs->faqs_question = $data['faqs_question'];
            $faqs->faqs_answer = $data['faqs_answer'];
            $faqs->save();
    
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Faqs successfully added."
                ]
            ];
            return response()->json($response, 201);
        }
    }


    public function updateFaqs(Request $request){
        $faqs = Faqs::findorfail($request->id);

        if($faqs){
            $faqs->update($request->all());
            $response=(object)[
                "success" => true,
                "result" => [
                    "message" => "Faqs has been successfully updated",
                ]
            ];
            }else{
                $response=(object)[
                    "success" => false,
                    "result" => [
                        "message" => "Invalid parameters",
                    ]
                ];
            } 
            return response()->json($response, 200);
        }


        public function specificFaqs($id){
            $faqs_details = Faqs::find($id);

            if($faqs_details){
                $response=(object)[
                    "success" => true,  
                    "result" => [
                        "datas" => $faqs_details,
                        "message" => "Here are the details of faqs.",
                    ]
                ];
                return response()->json($response, 200);
            }else{
                $response=(object)[
                    "success" => true,
                    "result" => [
                        "message" => "Faqs not found.",
                    ]
                ];
                return response()->json($response, 200);
            }
        }


        public function faqsList(Request $request){
            $faqs = DB::table('faqs')->paginate($request->limit);

            if($faqs){
                $response=(object)[
                    "success" => true,  
                    "result" => [
                        "datas" => $faqs,
                        "message" => "Here are the list of faqs",
                    ]
                ];
            }else{
                $response=(object)[
                    "success" => false,
                    "result" => [
                        "message" => "There are no available faqs",
                    ]
                ];
            }
            return response()->json($response, 200);
        }
}


    
