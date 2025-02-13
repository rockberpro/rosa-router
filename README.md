# ROSA-Router: Smart REST router for PHP

## Introduction
**ROSA-Router** is a lightweight and efficient REST API engine built using PHP. It is designed to handle HTTP requests and route them to the appropriate controllers or functions based on the defined API endpoints. With a focus on simplicity and performance, ROSA-Router enables developers to quickly create and deploy RESTful web services.

---

## Key Features

- **Easy Routing System**: Define routes for your REST API with simple configurations.
- **Request Method Handling**: Supports various HTTP methods such as `GET`, `POST`, `PUT`, `PATCH` and `DELETE`
- **Error Handling**: Built-in error handling mechanisms to gracefully manage exceptions and invalid requests.
- **Lightweight and Fast**: Optimized for performance, making it ideal for projects that require fast and efficient REST APIs.

---

## How It Works

ROSA-Router listens for HTTP requests and maps them to the correct route handler based on the request's method and URI. It supports both static and dynamic routes and is fully customizable to fit different project needs.

### Simple Routes

```php
/ ** GET route * /
Route::get('/post/{post}/comment/{comment}', [
	PostController::class, 'get'
]);

/ ** GET route * /
Route::get('/user/{id}', [
	UserController::class, 'get'
]);

/ ** POST route * /
Route::post('/user', [
	UserController::class, 'post'
]);

/ ** PUT route * /
Route::put('/user/', [
	UserController::class, 'put'
]); 

/ ** PATCH route * /
Route::patch('/user/', [
	UserController::class, 'patch'
]);
 
 / ** DELETE route * /
Route::delete('/user/{id}', [
	UserController::class, 'delete'
]); 
```

### Grouped Routes

```php
Route::prefix('v1')->group(function() {
    Route::get('/example/{id}', [
        V1ExampleController::class, 'get'
    ]);

    Route::post('/example', [
        V1ExampleController::class, 'post'
    ]);
});

Route::prefix('v2')->group(function() {
    Route::get('/example/{id}', [
        V2ExampleController::class, 'get'
    ]);

    Route::post('/example', [
        V2ExampleController::class, 'post'
    ]);
});
```

### Nested Routes

```php
Route::prefix('multilevel')->group(function() {
    Route::prefix('1')->group(function() {
        Route::get('/example/{id}', [
            V2ExampleController::class, 'get'
        ]);

        Route::post('/example', [
            V2ExampleController::class, 'post'
        ]);

        Route::prefix('2')->group(function() {
            Route::get('/example/{id}', [
                V2ExampleController::class, 'get'
            ]);

            Route::post('/example', [
                V2ExampleController::class, 'post'
            ]);
        });
    });
});
```

### Namespaces

```php
Route::prefix('v1')
    ->namespace('Rockberpro\\RestRouter\\Controllers')
    ->group(function() {
        Route::get('/example1', 'V1ExampleController@example');
    }
);

Route::prefix('v2')
    ->namespace('Rockberpro\\RestRouter\\Controllers')
    ->group(function() {
        Route::get('/example2', 'V2ExampleController@example');
    }
);
```

### Middleware

```php
Route::prefix('v1')
    ->middleware(AuthMiddleware::class)
    ->namespace('Rockberpro\\RestRouter\\Controllers')
    ->group(function() {
        Route::get('/hello', 'HelloWorldController@hello');
    }
);

Route::middleware(AuthMiddleware::class)
    ->namespace('Rockberpro\\RestRouter\\Controllers')
    ->get('/hello', 'HelloWorldController@hello');
```

### Controllers

```php
Route::controller(HelloWorldController::class)->group(function() {
    Route::get('/hello', 'hello');
});
```

### Chaining
```php
Route::namespace('Rockberpro\RestRouter\Controllers')
    ->chain()
    ->get('/method1', 'HelloWorldController@method1')
    ->get('/method2', 'HelloWorldController@method2')
    ->get('/method3', 'HelloWorldController@method3')
    ->end();
```