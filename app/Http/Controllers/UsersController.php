<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'auth',
            [
                'except' => [
                    'show',
                    'create',
                    'store',
                    'index',
                    'confirmEmail',
                ],
            ]
        );

        $this->middleware(
            'guest',
            [
                'only' => ['create'],
            ]
        );
    }

    public function index()
    {
        // $users = User::all();
        $users = User::paginate(10);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function show(User $user)
    {
        $statuses = $user->statuses()->orderBy('created_at', 'desc')->paginate(10);

        return view('users.show', compact('user', 'statuses'));
    }

    public function store(Request $request)
    {
        $this->validate(
            $request,
            [
                'name'     => 'required|unique:users|max:50',
                'email'    => 'required|email|unique:users|max:255',
                'password' => 'required|confirmed|min:6',
            ]
        );

        $user = User::create(
            [
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => bcrypt($request->password),
            ]
        );

        // \Auth::login($user);
        // session()->flash('success', '恭喜您，注册成功');
        // return redirect()->route('users.show', [$user]);

        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收');

        return redirect('/');
    }

    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        // $from    = '1332543018@qq.com';
        // $name    = 'admin123';
        $to      = $user->email;
        $subject = "感谢注册Weibo 应用！请确认你的邮箱。";

        // \Mail::send(
        //     $view,
        //     $data,
        //     function ($message) use ($from, $name, $to, $subject) {
        //         $message->from($from, $name)->to($to)->subject($subject);
        //     }
        // );

        \Mail::send(
            $view,
            $data,
            function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            }
        );
    }

    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated        = true;
        $user->activation_token = null;
        $user->save();

        \Auth::login($user);
        session()->flash('success', '恭喜您注册成功');

        return redirect()->route('users.show', [$user]);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('users.edit', compact('user'));
    }

    public function update(User $user, Request $request)
    {
        $this->authorize('update', $user);
        $this->validate(
            $request,
            [
                'name'     => 'required|max:50',
                'password' => 'nullable|confirmed|min:6',
            ]
        );

        $data         = [];
        $data['name'] = $request->name;
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }
        $user->update($data);

        // $user->update(
        //     [
        //         'name'     => $request->name,
        //         'password' => bcrypt($request->password),
        //     ]
        // );
        session()->flash('success', '个人资料更新成功！');

        return redirect()->route('users.show', $user->id);
    }

    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '删除成功');

        return back();
    }

    public function followings(User $user)
    {
        $users = $user->followings()->paginate(30);
        $title = $user->name.'关注的人';

        return view('users.show_follow', compact('users', 'title'));
    }

    public function followers(User $user)
    {
        $users = $user->followers()->paginate(30);
        $title = $user->name.'的粉丝';

        return view('users.show_follow', compact('users', 'title'));
    }
}
