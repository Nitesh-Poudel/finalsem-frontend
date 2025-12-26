# Online Food Processing System - Setup Guide

Follow these steps to get the project running locally after cloning.

---


## 1. Install PHP dependencies

```bash
composer install
```

## 2. Copy and configure environment file

```bash
cp .env.example .env

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 3. Generate application key

```bash
php artisan key:generate
```

## 4. Run Migrations and seeders

```bash
php artisan migrate --seed
```

## 5. Start the Laravel server

```bash
php artisan serve
```

## 6. Route Lists.

```bash
  GET|HEAD  / ..........................................................................generated::bOQujdmTBarqShrR

  ## Login, Logout, Register and Users
  POST      api/login ...........................................generated::0ZvufhTW4vHpQfdl › AuthController@login
  POST      api/logout .........................................generated::OqBcbR3kAQT2rEaI › AuthController@logout
  POST      api/register .....................................generated::kCyUTcoa9JWFO1zb › AuthController@register
  GET|HEAD  api/user .............................................generated::v2Bt5kVn3Hra6Ocy › AuthController@user

  ## Categories
  GET|HEAD  api/categories ..................................generated::BmbBDSq2IRsWPbfd › CategoryController@index
  POST      api/categories ..................................generated::n6EosIwlQPljC5qv › CategoryController@store
  GET|HEAD  api/categories/{id} ..............................generated::IljK9A26CEZaudG2 › CategoryController@show
  PUT       api/categories/{id} ............................generated::vzF3DqYETizyjJ7P › CategoryController@update
  DELETE    api/categories/{id} ...........................generated::KPkumiyXk6KZSpwk › CategoryController@destroy

  ## Employees
  GET|HEAD  api/employees ...................................generated::0xt66faag67traMo › EmployeeController@index
  POST      api/employees ...................................generated::RAcPtm6HAaQoz930 › EmployeeController@store
  GET|HEAD  api/employees/{id} ...............................generated::g8puaoWGY0f1cGfX › EmployeeController@show
  PUT       api/employees/{id} .............................generated::eeSOKKKecxakK6Uo › EmployeeController@update
  DELETE    api/employees/{id} ............................generated::knMTQtQqIYFHTqJv › EmployeeController@destroy

  ## Kitchen
  GET|HEAD  api/kitchen/logs ..............................generated::vZ1uG5WDRdWv2FDu › KitchenLogController@index
  POST      api/kitchen/logs ..............................generated::2eWbgV40yjnuLbdY › KitchenLogController@store
  GET|HEAD  api/kitchen/logs/{id} ..........................generated::PCuMjhTv2YuPzWpc › KitchenLogController@show
  PUT       api/kitchen/logs/{id} ........................generated::x4dMYllv84GhuDJT › KitchenLogController@update
  DELETE    api/kitchen/logs/{id} .......................generated::LUoXVAfTH5wT9uGm › KitchenLogController@destroy

  ## Offers
  GET|HEAD  api/offers .........................................generated::hpQeMwUn137gHEKp › OfferController@index
  POST      api/offers .........................................generated::BsAMWnQ38L5vY0Qq › OfferController@store
  GET|HEAD  api/offers/{id} .....................................generated::pRAtjR6XOOhM27OL › OfferController@show
  PUT       api/offers/{id} ...................................generated::JYkwFyAuEDkg8lg6 › OfferController@update
  DELETE    api/offers/{id} ..................................generated::1iLsgacxyd1Hwy3U › OfferController@destroy

  ## Orders
  GET|HEAD  api/orders .........................................generated::XnJnbjGPMc7szxxa › OrderController@index
  POST      api/orders .........................................generated::oTLXKGAKr4vjpp6y › OrderController@store
  GET|HEAD  api/orders/{id} .....................................generated::VLgLW1xFsi82F8K3 › OrderController@show
  PUT       api/orders/{id} ...................................generated::nwl8XmCR1AU7mi9K › OrderController@update
  DELETE    api/orders/{id} ..................................generated::bUH0VvH0u47H7rhC › OrderController@destroy

  ## Order Items
  GET|HEAD  api/orders/{orderId}/items .....................generated::sbNa6x5rumYuSrm3 › OrderItemController@index
  POST      api/orders/{orderId}/items .....................generated::WKjLKqTZ5qoxnFhF › OrderItemController@store
  PUT       api/orders/{orderId}/items/{id} ...............generated::QCgmLvL5kR891gTz › OrderItemController@update
  DELETE    api/orders/{orderId}/items/{id} ..............generated::oIp4vFZKWpc67Mrp › OrderItemController@destroy

  ## Products
  GET|HEAD  api/products .....................................generated::BPJkMZx3Sr7DfbyR › ProductController@index
  POST      api/products .....................................generated::AVzcEqyO0R0OVD0c › ProductController@store
  GET|HEAD  api/products/status/{id} ................generated::vGSUjhtuAd2CLc47 › ProductController@productsStatus
  GET|HEAD  api/products/{id} .................................generated::IlyvN7nJ6MUuFzCf › ProductController@show
  PUT       api/products/{id} ...............................generated::R70wVMMthJeRTW2g › ProductController@update
  DELETE    api/products/{id} ..............................generated::k9APGUZWQx0YkSTt › ProductController@destroy


  GET|HEAD  sanctum/csrf-cookie ..................sanctum.csrf-cookie › Laravel\Sanctum › CsrfCookieController@show
  GET|HEAD  storage/{path} ...........................................................................storage.local
  GET|HEAD  up .........................................................................generated::wYMC2wyRVM0zcGP7
```
# finalsem-frontend
