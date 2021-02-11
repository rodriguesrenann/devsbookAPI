<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class SearchController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = Auth::user();
    }

    public function search(Request $request) {
        $array = ['error' => ''];

        $q = $request->input('q');

        if(empty($q)) {
            $array['error'] = 'Digite alguma coisa!';
            return $array;
        }

        $users = User::where('name', 'like', '%'.$q.'%')->get();
            foreach($users as $user) {
                $array['users'][] = [
                    'name' => $user->name,
                    'id' => $user->id,
                    'avatar' => url('media/avatars/'.$user->avatar)
                ];
            }
        
        return $array;
    }
}
