<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

use App\Models\Post;
use App\Models\User;
use App\Models\UserRelation;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = Auth::user();
    }

    public function update(Request $request)
    {
        $array = ['error' => ''];

        $name = $request->input('name');
        $email = $request->input('email');
        $city = $request->input('city');
        $work = $request->input('work');
        $birthdate = $request->input('birthdate');
        $password = $request->input('password');
        $password_confirmation = $request->input('password_confirmation');

        $user = User::find($this->loggedUser->id);

        if (!$user) {
            return $array['error'] = 'Usuário não cadastrado';
        }

        $rules = [
            'name' => 'string|max:100',
            'email' => 'email|max:100|string',
            'city' => 'string|max:100',
            'work' => 'string|max:100',
            'birthdate' => 'date_format:d/m/Y',
            'password' => 'string|min:4|same:password_confirmation',
            'password_confirmation' => 'string|min:4|same:password'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $array['errors'] = $validator->errors();
        }

        //Verificar se o email ja existe no banco de dados
        if (!empty($email && $email != $user->email)) {
            $hasEmail = User::where('email', $email)->first();
            if ($hasEmail) {
                return $array['error'] = 'E-mail já cadastrado!';
            } else {
                $user->email = $email;
            }
        }

        if(!empty($name)) {
            $user->name = $name;
        }

        //Corrigir data de aniversario
        if (!empty($birthdate)) {
            $birthdate = explode('/', $birthdate);
            $birthdate = $birthdate[2] . '-' . $birthdate[1] . '-' . $birthdate[0];
            
            $user->birthdate = $birthdate;
        }

        if($city) {
            $user->city = $city;
        }

        if($work) {
            $user->work = $work;
        }
        
        if(!empty($password && $password_confirmation)) {
            $user->password = password_hash($password, PASSWORD_DEFAULT);
        }

        $user->save();

        return $array;
    }

    public function updateAvatar(Request $request) {
        $array = ['error' => ''];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        
        $image = $request->file('avatar');

        if(!$image) {
            return $array['error'] = 'Imagem não enviada!';
        }

        if(!in_array($image->getClientMimeType(), $allowedTypes)) {
            return $array['error'] = 'Formato não permitido';
        }

        $fileName = md5(time()).'.jpg';
        $path = public_path('media/avatars');

        $imageMake = Image::make($image->getRealPath());
        $imageMake->fit(300, 300);
        $imageMake->save($path.$fileName);

        $user = User::find($this->loggedUser->id);

        if($user) {
            $user->avatar = $fileName;
            $user->save();

            $array['url'] = url('media/avatars/'.$fileName);
        }

        return $array;
    }

    public function updateCover(Request $request) {
        $array = ['error' => ''];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        
        $image = $request->file('cover');

        if(!$image) {
            return $array['error'] = 'Imagem não enviada!';
        }

        if(!in_array($image->getClientMimeType(), $allowedTypes)) {
            return $array['error'] = 'Formato não permitido';
        }

        $fileName = md5(time()).'.jpg';
        $path = public_path('media/covers/');

        $imageMake = Image::make($image->getRealPath());
        $imageMake->fit(850, 310);
        $imageMake->save($path.$fileName);

        $user = User::find($this->loggedUser->id);

        if($user) {
            $user->cover = $fileName;
            $user->save();

            $array['url'] = url('media/covers/'.$fileName);
        }

        return $array;
    }

    public function read($id = false) {
        $array = ['error' => ''];

        if($id) {
            $info = User::find($id);
            if(!$info) {
                $array['error'] = 'Usuário inexistente!';
            }
        }else {
            $info = $this->loggedUser;
        }
        $info->avatar = url('media/avatars/'.$info->avatar);
        $info->cover = url('media/covers/'.$info->cover);

        $info['me'] = ($info->id == $this->loggedUser->id) ? true : false;

        $dateFrom = new \DateTime($info->birthdate);
        $dateTo = new \DateTime('today');
        $info['age'] = $dateFrom->diff($dateTo)->y;

        $info['followers'] = UserRelation::where('user_to', $info->id)->count();
        $info['following'] = UserRelation::where('user_from', $info->id)->count();
        $info['photoCount'] = Post::where('id_user', $info->id)->where('type', 'photo')->count();

        $hasRelation = UserRelation::where('user_from', $this->loggedUser->id)->where('user_to', $info->id)->count();
        $info['isFollowing'] = ($hasRelation > 0) ? true : false;
         

        $array['data'] = $info;
        return $array;

    }

    public function follow($id) {
        $array = ['error' => ''];

        $user = User::where('id', $id)->first();

        if(!$user) {
            $array['error'] = 'Usuário inexistente!';
            return $array;
        }

        if($id == $this->loggedUser->id) {
            $array['error'] = 'Voce não pode seguir a si mesmo!';
            return $array;
        }

        $isFollowing = UserRelation::where('user_from', $this->loggedUser->id)->where('user_to', $id)->first();
        if($isFollowing) {
            $isFollowing->delete();
            $array['isFollowing'] = false;
        }else {
            $newFollower = new UserRelation();
            $newFollower->user_from = $this->loggedUser->id;
            $newFollower->user_to = $id;
            $newFollower->save();

            $array['isFollowing'] = true;
        }

        return $array;

    }

    public function followers($id) {
        $array = ['error' => ''];

        $userExists = User::where('id', $id)->first();
        if(!$userExists) {
            $array['error'] = 'Usuário inexistente!';
            return $array;
        }
        
        $array['following'] = [];
        $following = UserRelation::where('user_from', $id)->get();
        foreach($following as $item) {
            $user = User::find($item->user_to);
            $array['following'][] = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => url('media/avatars'.$user->avatar)
            ];
        }

        $array['followers'] = [];
        $followers = UserRelation::where('user_to', $id)->get();
        foreach($followers as $item) {
            $user = User::find($item->user_from);
            $array['followers'][] = [
                'id' => $user->id,
                'avatar' => url('media/avatars/'.$user->avatar),
                'name' => $user->name,
            ];
        }

        return $array;
    }
}
