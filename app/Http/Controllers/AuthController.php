<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{   
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => [
            'login',
            'create',
            'unauthorized',
        ]]);

        $this->loggedUser = Auth::user();
    }

    public function unauthorized() {
        return response()->json([
            'error' => 'Não autorizado!'
        ], 401);
    }

    public function create(Request $request) {
        $array = ['error' => ''];

        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');
        $city = $request->input('city');
        $work = $request->input('work');
        $birthdate = $request->input('birthdate');

        $rules = [
            'name' => 'required|max:100|string',
            'email' => 'required|max:100|email|string|unique:users',
            'password' => 'required|min:4|string|same:password_confirmation',
            'password_confirmation' => 'required|min:4|string|same:password',
            'city' => 'max:100|string',
            'work' => 'max:100|string',
            'birthdate' => 'date_format:d/m/Y',

        ];
       

        if(!$name && !$email && !$password && !$password_confirmation) {
            $array['error'] = 'Envie os dados obrigatórios (nome, email e senha)!';
            return $array;
        }
        //1999-07-15
        
    
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()) {
            $array['error'] = $validator->errors();
            return $array;
        }
            //Reorganizando a data
            $birthdate = explode('/', $birthdate);
            $birthdate = $birthdate[2].'-'.$birthdate[1].'-'.$birthdate[0];

            $newUser = new User();
            $newUser->name = $name;
            $newUser->email = $email;
            $newUser->password = password_hash($password, PASSWORD_DEFAULT);
            $newUser->city = $city;
            $newUser->work = $work;
            $newUser->birthdate = $birthdate;
            $newUser->save();

            $token = Auth::attempt([
                'email' => $email,
                'password' => $password
            ]);

            if(!$token) {
                $array['error'] = 'Ocorreu um erro!';
            }

            $array['token'] = $token;

            return $array;
       
    }

    public function login(Request $request) {
        $array = ['error' => ''];
        
        $data = $request->all();

        if(empty($data)) {
            return $array['error'] = 'E-mail e senha necessários para login';
        }
        
        $token = Auth::attempt($data);

        if(!$token) {
            return $array['error'] = 'E-mail e/ou senha incorreto(s)!';  
        }

        $array['token'] = $token;

        return $array;

    }

    

    public function logout() {
        $array = ['error' => ''];
        Auth::logout();

        return $array;
    }

    public function refresh() {
        $token = Auth::refresh();

        return $array = [
            'error' => '',
            'token' => $token
        ];
    }
}
