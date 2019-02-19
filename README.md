# Shoptet API token helper

PHP script to obtain an OAuth access token to work with [Shoptet API](https://shoptet.docs.apiary.io/).

## Why

Shoptet's API is primarily meant to be used from add-ons which are server-side things. For local tinkering with the API, e.g. via `curl`, one needs to obtain an access token which this project helps with.

## How Shoptet's API authentication works

To send requests to the API, one needs an _API access token_:

```http
GET https://api.myshoptet.com/api/eshop
Shoptet-Access-Token: <API access token>
```

It can be requested from shop's `/action/ApiOAuthServer/getAccessToken` route but for that, one needs an _OAuth access token_:

```http
GET https://example.myshoptet.com/action/ApiOAuthServer/getAccessToken
Authorization: Bearer <OAuth acceess token>
```

This token can only be negotiated when an add-on is installed and for that, a public endpoints must be provided that Shoptet can call.

## Workflow

Follow the steps below to go through the process:

1. [Deploy an endpoint](#deploy-an-endpoint)
2. [Create Shoptet add-on](#create-shoptet-add-on)
3. [Obtain "OAuth access token"](#obtain-oauth-access-token)
4. [Request "API access token"](#request-api-access-token)
5. [Enable specific APIs](#enable-specific-api-endpoints)
6. [Work with the API](#-work-with-the-api)

### Deploy an endpoint

The endpoint, which is implemented in [`oauth-helper.php`](./oauth-helper.php), must be publicly available. Use one of the following options:

1. Upload the script to your server
2. Upload to ZEIT Now
3. Run it locally and tunnel using a service like ngrok.

#### Your own server

Not much to add, just make sure the same environment variables as below are defined.

#### ZEIT Now

Deploy to Now with the following environment variables:

```
now \
  -e SHOP_SUBDOMAIN="yourshop" \
  -e CLIENT_ID="9i2uwkr6zgt4pqzr" \
  -e REDIRECT_URL="https://your-alias.now.sh/oauth-helper.php"
```

You'll also need to alias the deployment (`@now/php` [isn't currently good](https://github.com/zeit/now-builders/issues/218) at constructing self URLs so we're providing an explicit URL via `REDIRECT_URL`):

```
now alias <https://url-of-the-deployment> your-alias
```

The URL you'll want to use in Shoptet admin is `https://your-alias.now.sh/oauth-helper.php`.

#### Local run + ngrok tunnel

In this scenario, the script runs locally as is made available to the internet via [ngrok](https://ngrok.com/).

First, run ngrok:

```
ngrok http 8000
```

It will give you an URL like `https://975d4afe.ngrok.io` which will be the base for the value you put to the `REDIRECT_URL`. In this example, we'll start the PHP script in a Docker container but something like built-in PHP server would work as well. Note that the port must be 8000 to match the above.

```
docker run --rm \
  -p 8000:80 \
  -v $(pwd):/var/www/html \
  -e SHOP_SUBDOMAIN="yourshop" \
  -e CLIENT_ID="9i2uwkr6zgt4pqzr" \
  -e REDIRECT_URL="https://975d4afe.ngrok.io/oauth-helper.php" \
  php:apache
```

#### Create Shoptet add-on

In Shoptet admin, click **Create add-on** and enter the URL from above to the field labeled **Address to get your OAuth code**.

If everything was deployed correctly with the right environment variables, you should be able to go to the **Users** tab and click the test installation button.

### Obtain OAuth access token

When adding the test user, Shoptet will call the URL specified in the admin. The script is programmed to print something like this to *server logs*:

```
[15 Feb 16:25:08] [php7:notice] OAuth access token: kfx4fsycmcjk8zobqm7c...
```

For example, with local Docker, this will be printed to the terminal, on ZEIT Now, you can find it in the logs (`<deployment-url>/_logs`), etc.

Save this token in your password manager.

### Request API access token

Send a request to your shop's `/action/ApiOAuthServer/getAccessToken` endpoint:

```
curl \
  -H 'Authorization: Bearer <OAuth access token>' \
  https://yourshop.myshoptet.com/action/ApiOAuthServer/getAccessToken
```

The response will be something like:

```
{
    "access_token": "8faghr48u46kh2...",
    "expires_in": 1800
}
```

You now have 30 minutes to make requests with this token. But first, you need to enable specific endpoints:

### Enable specific API endpoints

In Shoptet's web admin, go to API Partner > Add-ons > select specific add-on > Endpoints and enable specific endpoints you want to enable. To enable the whole API, enable each endpoints one by one.

<img width="895" alt="screenshot 2019-02-18 at 18 08 46" src="https://user-images.githubusercontent.com/101152/52973138-67833b80-33bd-11e9-9e0c-91e2656513c1.png">

⚠️ After you do this, go to the **Users** tab again and click **Add**. This has to be done every time endpoints are changed! If you don't do this, you'll see an error like this in the next step:

> Your access token \"8faghr48u46kh2...\" has no defined rights for this resource.

### 🎉 Work with the API

For example:

```
curl \
  -H 'Shoptet-Access-Token: 8faghr48u46kh2...' \
  -H 'Content-Type: application/vnd.shoptet.v1.0' \
  https://api.myshoptet.com/api/eshop
```
