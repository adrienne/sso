# Social Sign-On ExpressionEngine Add-on

_Developed by [eecoder](http://eecoder.com/)._

## Requirements

* ExpressionEngine 2.3+
* PHP 5.3

## Installation

1. Move the sso folder to your `system/expressionengine/third_party/` folder.
2. Install the module and extension.
3. In the extension settings screen, add or remove providers (separated by `|`) based on your site's needs. By default, Twitter and Facebook are available.
4. If using the Twitter and Facebook providers, open their classes in `system/expressionengine/third_party/sso/models/` and add the correct API keys for your app below the _SET API KEYS FOR PROJECT_ comment. **Note:** you will need to create these apps with the provider themselves to get these settings.
5. In the provider classes, change the field mappings below the _EDIT FIELD MAPPINGS FOR PROJECT_ comment to comply with your site's needs. The field names should correspond to the names of fields in your site's registration form.

## Template Tags

### {exp:sso:register_start}

This is used to initiate the registration process through a third-party provider.

#### Parameters

* `provider`: The "short name" of the provider to use. If a provider is chosen that is not in the list of available providers, and error will be shown.
* `callback_uri`: A unique URI that the provider will redirect to after authorization. The template located at this URI should contain the `{exp:sso:register_finish}` tag.

### {exp:sso:register_finish}

This is used to complete the registration process through a third-party provider.

#### Parameters

* `provider`: The "short name" of the provider to use.
* `redirect`: The URI to redirect to after this completes. The URI that is redirected to should contain a standard registration form, which will be prefilled by the `{exp:sso:user_info}` tag.

### {exp:sso:user_info}

This is used to pre-populate a registration form with the user's info that was pulled from the third-party provider.

#### Tags

* `{sso_*}`: Any field name returned by the profile array in the provider's class can be used. For example, if you have a profile field in the class named _first-name_, you could display that value with the `{sso_first-name}` tag.
* `{sso_id}`: The primary key value for this provider's row in the `exp_sso_accounts` table. This will be set if the user has authorized with a third-party provider during this session.
* `{if has_sso_id}{/if}`: This conditional can be used to determine if the current user has already authorized with a third-party provider. Useful for displaying different messages to standard registrants vs. SSO ones.

### {exp:sso:login}

This is used to login a user via third-party provider.

#### Parameters

* `provider`: The "short name" of the provider to use.
* `redirect`: The URI to redirect to after successful login.

### {exp:sso:connections}

This is used to determine which providers a logged-in user has already connected with.

#### Parameters

* `user_id`: By default, this is set to the current logged-in user, but you can also manually provide a user ID if you choose.

#### Tags

* `{if has_*}{/if}`: This conditional will allow you to determine if a user has connected with any of your available providers, based on their "short name." For example, if you want to determine if the current user has connected with Facebook, you would use `{if has_facebook}...{if:else}...{/if}`.

### {exp:sso:disconnect}

This is used to disconnect a third-party provider from a user's account.

#### Parameters

* `provider`: The "short name" of the provider to use.
* `redirect`: The URI to redirect to after successful disconnect.

## Example

_Template for URI **/account/social-register**:_

```
{!-- redirect logged-in users --}
{if logged_in}
	{redirect="/"}
{/if}

{!-- redirect if no provider is designated --}
{if segment_3 == ""}
	{redirect="/"}
{/if}

{!-- begin the registration process --}
{if segment_4 != "callback"}
	{exp:sso:register_start provider="{segment_3}" callback_uri="account/social-register/{segment_3}/callback"}
{/if}

{!-- finish the registration process --}
{if segment_4 == "callback"}
	{exp:sso:register_finish provider="{segment_3}" redirect="account/register"}
{/if}
```

_Template for URI **/account/register**:_

```
{!-- redirect logged-in users --}
{if logged_in}
	{redirect="/"}
{/if}

{!-- allow registration form to be pre-populated --}
{exp:sso:user_info}
	{!-- only show this to sso registrants --}
	{if has_sso_id}
		<p>Almost done... just complete this form to finish creating your account:</p>
	{if:else}
		<p>Register with <a href="{path='account/social-register/facebook'}">Facebook</a> or <a href="{path='account/social-register/twitter'}">Twitter</a>.</p>
	{/if}
	
	{!-- form open --}
		Name: <input type="text" name="name" value="{sso_name}" /><br />
		Email: <input type="text" name="email" value="{sso_email}" /><br />
		Username: <input type="text" name="username" value="{sso_username}" />
	{!-- form close --}
{/exp:sso:user_info}
```

_Template for URI **/account/login**:_

```
{!-- redirect logged-in users --}
{if logged_in}
	{redirect="account"}
{/if}

<p>Login with <a href="{path='account/social-login/facebook'}">Facebook</a> or <a href="{path='account/social-login/twitter'}">Twitter</a></p>
```

_Template for URI **/account/social-login**:_

```
{!-- redirect logged-in users --}
{if logged_in}
	{redirect="account"}
{/if}

{!-- redirect if no provider is designated --}
{if segment_3 == ""}
	{redirect="/"}
{/if}

{exp:sso:login provider="{segment_3}" redirect="account"}
```

_Template for URI **/account**:_

```
{!-- redirect logged-out users --}
{if logged_out}
	{redirect="/"}
{/if}

{exp:sso:connections}
	{if has_facebook}
		<p><a href="{path='account/social-disconnect/facebook'}">Disconnect Facebook from account</a></p>
	{if:else}
		<p><a href="{path='account/social-register/facebook'}">Connect Facebook to account</a></p>
	{/if}
	
	{if has_twitter}
		<p><a href="{path='account/social-disconnect/twitter'}">Disconnect Twitter from account</a></p>
	{if:else}
		<p><a href="{path='account/social-register/twitter'}">Connect Twitter to account</a></p>
	{/if}
{/exp:sso:connections}
```

_Template for URI **/account/social-disconnect**:_

```
{!-- redirect logged-out users --}
{if logged_out}
	{redirect="/"}
{/if}

{!-- redirect if provider not designated --}
{if segment_3 == ""}
	{redirect="/"}
{/if}

{exp:sso:disconnect provider="{segment_3}" redirect="account"}
```