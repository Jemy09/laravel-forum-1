<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Here we specify the policy classes to use. Change these if you want to
    | extend the provided classes and use your own instead.
    |
    */

    'policies' => [
        'forum' => Jemy09\Forum\Policies\ForumPolicy::class,
        'model' => [
            Jemy09\Forum\Models\Category::class  => Jemy09\Forum\Policies\CategoryPolicy::class,
            Jemy09\Forum\Models\Thread::class    => Jemy09\Forum\Policies\ThreadPolicy::class,
            Jemy09\Forum\Models\Post::class      => Jemy09\Forum\Policies\PostPolicy::class
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Application user model
    |--------------------------------------------------------------------------
    |
    | Your application's user model.
    |
    */

    'user_model' => App\User::class,

    /*
    |--------------------------------------------------------------------------
    | Application user name
    |--------------------------------------------------------------------------
    |
    | The attribute to use for the username.
    |
    */

    'user_name' => 'name',

];
