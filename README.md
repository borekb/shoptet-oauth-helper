# Shoptet API token helper

To work with [Shoptet API](https://shoptet.docs.apiary.io/), one needs an "API access token":

```http
GET https://api.myshoptet.com/api/eshop
Shoptet-Access-Token: <API access token>
```

To get it, "OAuth access token" is needed:

```http
GET https://example.myshoptet.com/action/ApiOAuthServer/getAccessToken
Authorization: Bearer <OAuth acceess token>
```

And this small PHP project implements an endpoint that Shoptet can call to negotiate the token.

## Deploying the endpoint

The endpoint must be publicly available – Shoptet's servers will call it on add-on activation. Use one of the following options:

1. Upload the script to your server
2. Upload to ZEIT Now
3. Run it locally and tunnel using a service like ngrok.

### Your own server

Not much to add, just make sure the same environment variables as below are defined.

### ZEIT Now

Deploy to Now using something like:

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

### Run locally + ngrok tunnel

If you want to tinker with the PHP script, maybe the easiest is to run it locally and expose it to the internet via a service like [ngrok](https://ngrok.com/).

First, run ngrok:

```
ngrok http 8000
```

It will give you an URL like `https://975d4afe.ngrok.io` which you'll put to Shoptet admin and as a `REDIRECT_URL` below. Note that we'll start the site on port 8000 to match what we told ngrok above:

```
docker run --rm \
  -p 8000:80 \
  -v $(pwd):/var/www/html \
  -e SHOP_SUBDOMAIN="yourshop" \
  -e CLIENT_ID="9i2uwkr6zgt4pqzr" \
  -e REDIRECT_URL="https://975d4afe.ngrok.io/oauth-helper.php" \
  php:apache
```

## Create Shoptet add-on

In Shoptet admin, click Create add-on and enter the URL from above to the field labeled "Address to get your OAuth code".

If everything was deployed correctly with the right environment variables, you should be able to go to the Users tab and click the test installation button.

## Obtaining the "OAuth access token"

When adding the test user, Shoptet will call the URL specified in the admin. The script is programmed to print something like this **to server logs**:

```
[15 Feb 16:25:08] [php7:notice] OAuth access token: kfx4fsycmcjk8zobqm7c...
```

For example, with local Docker, this will be printed to the terminal, on ZEIT Now, you can find it in the logs (`<deployment-url>/_logs`), etc.

Save this token somewhere, e.g., in a password manager.

## Request "API access token"

Send a request to `/getAccessToken` endpoints of Shoptet's API, for example:

```
curl \
  -H 'Authorization: Bearer <OAuth access token>' \
  https://versionpress.myshoptet.com/action/ApiOAuthServer/getAccessToken
```

The response will be something like:

```
{
    "access_token": "8faghr48u46kh2...",
    "expires_in": 1800
}
```

You now have 30 minutes to make requests with this token. For example:

```
curl \
  -H 'Shoptet-Access-Token: 8faghr48u46kh2...' \
  -H 'Content-Type: application/vnd.shoptet.v1.0' \
  https://yourshop.myshoptet.com/api/eshop
```
