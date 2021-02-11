<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;

class PostController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = Auth::user();
    }

    public function like($id) {
        $array = ['error' => ''];

        $post = Post::find($id);

        if(!$post) {
            $array['error'] = 'Post inexistente!';
            return $array;
        }

        $isLiked = PostLike::where('id_user', $this->loggedUser->id)->where('id_post', $id)->first();
        

        if($isLiked) {
            $isLiked->delete();

            $isLiked = false;

        }else {
            $newPostLike = new PostLike();
            $newPostLike->id_user = $this->loggedUser->id;
            $newPostLike->id_post = $id;
            $newPostLike->created_at = date('Y-m-d H:i:s');
            $newPostLike->save();

            $isLiked = true;
        }

        $array['isLiked'] = $isLiked;
        $array['likeCount'] = PostLike::where('id_post', $id)->count();
        return $array;

    }

    public function comment(Request $request, $id) {
        $array = ['error' => ''];

        $post = Post::find($id);

        if(!$post) {
            $array['error'] = 'Post inexistente!';
            return $array;
        }
       
        $body = $request->input('body');

        $validator = Validator::make($request->all(), [
            'body' => 'string|required'
        ]);
        if($validator->fails()) {
            $array['error'] = $validator->errors();
            return $array;
        }
        

        $newPostComment = new PostComment();
        $newPostComment->id_user = $this->loggedUser->id;
        $newPostComment->id_post = $id;
        $newPostComment->created_at = date('Y-m-d H:i:s');
        $newPostComment->body = $body;
        $newPostComment->save();

        return $array;
    }
}
