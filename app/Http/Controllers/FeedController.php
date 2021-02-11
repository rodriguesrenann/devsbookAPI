<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

use App\Models\Post;
use App\Models\UserRelation;
use App\Models\User;
use App\Models\PostLike;
use App\Models\PostComment;

class FeedController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = Auth::user();
    }

    public function create(Request $request)
    {
        $array = ['error' => ''];

        $body = $request->input('body');
        $photo = $request->file('photo');
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

        if (!empty($photo)) {
            $photoType = $photo->getClientMimeType();
            if (!in_array($photoType, $allowedTypes)) {
                $array['error'] = 'Arquivo não suportado!';
                return $array;
            }

            $fileName = md5(time()) . '.jpg';
            $path = public_path('media/uploads/');
            $imageMake = Image::make($photo->getRealPath());
            $imageMake->fit(500, 500);
            $imageMake->save($path . $fileName);

            $bodyQuery = $fileName;
            $type = 'photo';
        }

        if ($body) {
            $bodyQuery = $body;
            $type = 'text';
        }

        if (empty($body) && empty($photo)) {
            $array['error'] = 'Escreva alguma frase ou envie alguma foto!';
            return $array;
        }

        $newPost = new Post();
        $newPost->id_user = $this->loggedUser->id;
        $newPost->type = $type;
        $newPost->created_at = date('Y-m-d H:i:s');
        $newPost->body = $bodyQuery;
        $newPost->save();

        return $array;
    }

    public function read(Request $request)
    {   
        $limit = 0;
        $limit = $request->input('limit');

        $array = ['error' => ''];
        //Pegar lista de usuario que eu sigo incluindo eu mesmo
        $followingList = [];
        $query = UserRelation::where('user_from', $this->loggedUser->id)->get();

        foreach ($query as $item) {
            $followingList[] = $item->user_to;
        }
        $followingList[] = $this->loggedUser->id;
        
        //Pegar os post dessa galera ordenado pela data
        $postList = Post::whereIn('id_user', $followingList)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        
        //Pegar info adicional, se o post é meu, info do usuario que fez o post(avatar, likes, comments, se eu curti ou n)    
        $postList = $this->_postListToObject($postList, $this->loggedUser->id);
        $posts [] = $postList;
        $array['posts'] = $posts;
        return $array;
           


    }

    private function _postListToObject($postList, $loggedId) {
        //verificar se o post é meu
        foreach($postList as $postKey => $postValue) {
            if(intval($postValue->id_user) === intval($loggedId)) {
                
                $postList[$postKey]['mine'] = true;
            }else {
                $postList[$postKey]['mine'] = false;
            }

            //Info do usuario que fez o post
            $userInfo = User::find($postValue->id_user);
            $userInfo->avatar = url('media/avatars/'.$userInfo->avatar);
            $userInfo->cover = url('media/covers/'.$userInfo->cover);
            $postList[$postKey]['userInfo'] = $userInfo;

            //Pegar info de likes
            $likes = PostLike::where('id_post', $postValue->id)->count();
            $postList[$postKey]['likes'] = $likes;

            //Pegar info se eu ja curti o post

            $isLiked = PostLike::where('id_post', $postValue->id)->where('id_user', $loggedId)->count();
            
            $postList[$postKey]['liked'] = ($isLiked > 0) ? true : false;

            //Preencher info dos comments
            $comments = PostComment::where('id_post', $postValue->id)->get();

            foreach($comments as $commentKey => $comment) {
                $user = User::find($comment->id_user);
                $user->avatar = url('media/avatars/'.$user->avatar); 
                $user->avatar = url('media/covers/'.$user->cover);
                $comments[$commentKey]['userInfo'] = $user;
            }
            $postList[$postKey]['comments'] = $comments;

        }

        return $postList;
    }

    public function userFeed(Request $request, $id = false) {
        $array = ['error' => ''];
        
        $limit = $request->input('limit');

        if($id == false) {
            $id = $this->loggedUser->id;
        }

        $postList = Post::where('id_user', $id)->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        $posts = $this->_postListToObject($postList, $this->loggedUser->id);
        $array['posts'] = $posts;

        return $array;
    }

    public function getPhotos(Request $request, $id = false) {
        $array = ['error' => ''];
        
        if(!$id) {
            $id = $this->loggedUser->id;
        }
        $limit = $request->input('limit');

        if(empty($limit)) {
            $limit = null;
        }
        
        $posts = Post::where('id_user', $id)
        ->where('type', 'photo')
        ->orderBy('created_at', 'DESC')
        ->limit($limit)
        ->get();

        $photoInfo = $this->_postListToObject($posts, $this->loggedUser->id);
        foreach($photoInfo as $key => $photo) {
            $photoInfo[$key]['body'] = url('media/uploads/'.$photo->body);
        }
        $array['photoList'] = $photoInfo;

        return $array;
    }
}
