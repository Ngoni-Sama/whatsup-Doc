<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Appointment;
use App\Chat;
use App\Http\Resources\DoctorsAppointmentResource;
use App\Doctor;
use App\Http\Requests\GetPatientRecordRequest;
use App\Http\Requests\MakePrescriptionRequest;
use App\Http\Requests\WritePatientRecordRequest;
use App\Http\Resources\DoctorsPatient;
use App\PatientRecord;
use App\Prescription;
use App\User;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\App;

class DoctorsController extends Controller
{

    public function __construct()
    {
        $this->middleware(['multiauth:doctor']);
    }
    
    // List appointment
    public function list_appointment(){
        
        // $approved = $doctor->appointments->where('approved', true);

        // $unapproved = $doctor->appointments->where('approved', false);

        // $appointment = $doctor->appointments;
        $doctor = auth()->guard('doctor')->user()->id;

        $appointments = Appointment::where([
            'doctor_id' => $doctor,
        ])->get();


         if ($appointments->count() > 0) {

           return DoctorsAppointmentResource::collection($appointments);

        } else {

            return response()->json([
                'message' => 'No appointments'
            ]);
        } 
    }

    //Approve appointment
    public function approve_appointment(Appointment $appointment){

            $appointment->approved = true;

            $appointment->save();

            return response()->json([
                'message' => 'Appointment approved',
                'res' => 'approved'
            ]);

    }

    //Make prescription
    public function make_prescription(MakePrescriptionRequest $request){
        $prescription = Prescription::create([
            'user_id' => $request->user_id,
            'doctor_id' => auth()->guard('doctor')->id(),
            'case_history' => $request->case_history,
            'medication' => $request->medication,
            // 'medication_from_pharmacist' => $request->medication_from_pharmacist,
        ]);

        $data = [
            'patient_name' => User::find($request->user_id)->name,
            'doctor_name' => auth()->guard('doctor')->user()->name,
            'case_history' => $request->case_history,
            'medication' => $request->medication,
            'date_created' => $prescription->created_at
        ];


        $pdf = App::make('dompdf.wrapper');

        $pdf->loadView('prescription', compact('data'))
            ->save(public_path() .  '/prescriptions/' . $request->user_id . 'prescription' . $prescription->id. '.pdf');

        
        return response()->json([
        'prescription' => $prescription,
        'file' => url('/prescriptions/' . $request->user_id . 'prescription' . $prescription->id . '.pdf')
        ]);
    }

    //Write Patient Record
    public function write_patient_record(WritePatientRecordRequest $request)
    {
        $get_record = PatientRecord::create([
            'user_id' => $request->user_id,
            'prescription_id' => $request->prescription_id,
            'report_type' => $request->report_type,
            'description' => $request->description,
        ]);

        return response()->json(
            $get_record
        );
    }

    //Get Patient Record 
    public function get_patient_record(User $user){
            $get_record = PatientRecord::where([
                'user_id' => $user->id
            ])->get();

            if ($get_record->count() > 0) {

                return response()->json($get_record);
     
            } else {
     
                 return response()->json([
                     'message' => 'No Record found',
                     'res' => 'found'
                 ]);
            }
    }

    public function getDoctorsPatients()
    {
        $chats = Chat::where([
            'doctor_id' => auth()->guard('doctor')->id(),
        ])->groupBy('user_id')->get();
        
        $users = [];

        for ($i = 0; $i < count($chats); $i++) {

            $patient = User::where([
                'id' => $chats[$i]->user_id
            ])->get()->first();

            $lastChat = Chat::where([
                'user_id' => $patient->id
                ])->get()->last();
            $users[$i] = [$patient,$lastChat];
        }
      
        return response()->json($users);
    }

    public function chatWithPatient(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required',
            'sender' => 'required'
        ]);

        $chat = Chat::create([
            'user_id' => $request->user_id,
            'doctor_id' => auth()->guard('doctor')->id(),
            'message' => $request->message,
            'sender' => $request->sender
        ]);

    }
}
